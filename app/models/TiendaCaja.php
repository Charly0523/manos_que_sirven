<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/TiendaProducto.php';

final class TiendaCaja
{
    private PDO $db;

    public function __construct() { $this->db = obtenerConexion(); }

    public function abierta(): ?array
    {
        $fila = $this->db->query("SELECT c.*, u.usuario AS responsable
            FROM tienda_cajas c JOIN usuarios u ON u.id=c.usuario_apertura_id
            WHERE c.estado='Abierta' ORDER BY c.id DESC LIMIT 1")->fetch();
        return $fila ?: null;
    }

    public function ultimas(): array
    {
        return $this->db->query('SELECT c.*, ua.usuario AS aperturo, uc.usuario AS cerro
            FROM tienda_cajas c JOIN usuarios ua ON ua.id=c.usuario_apertura_id
            LEFT JOIN usuarios uc ON uc.id=c.usuario_cierre_id
            ORDER BY c.id DESC LIMIT 25')->fetchAll();
    }

    public function abrir(int $usuario, int $fondoCentavos): void
    {
        if ($fondoCentavos < 0 || $fondoCentavos > 100000000) {
            throw new InvalidArgumentException('El fondo inicial no es válido.');
        }
        $adquirido = ((int) $this->db->query("SELECT GET_LOCK('mqs_tienda_apertura', 10)")->fetchColumn()) === 1;
        if (!$adquirido) { throw new RuntimeException('No se pudo bloquear la apertura de caja.'); }
        try {
            $this->db->beginTransaction();
            if ($this->abierta() !== null) {
                throw new DomainException('Ya existe una caja abierta. Ciérrala antes de abrir otra.');
            }
            $stmt = $this->db->prepare('INSERT INTO tienda_cajas (usuario_apertura_id,fondo_inicial) VALUES (?,?)');
            $stmt->execute([$usuario, TiendaProducto::decimal($fondoCentavos)]);
            $this->db->commit();
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) { $this->db->rollBack(); }
            throw $e;
        } finally {
            $this->db->query("SELECT RELEASE_LOCK('mqs_tienda_apertura')");
        }
    }

    public function movimiento(int $cajaId, int $usuario, string $tipo, int $centavos, string $concepto): void
    {
        if (!in_array($tipo, ['Ingreso','Egreso'], true) || $centavos <= 0 || $centavos > 100000000) {
            throw new InvalidArgumentException('Tipo o monto del movimiento no válido.');
        }
        $concepto = trim($concepto);
        if ($concepto === '' || mb_strlen($concepto) > 180) {
            throw new InvalidArgumentException('Debes indicar el concepto del movimiento.');
        }
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare('SELECT id, estado FROM tienda_cajas WHERE id=? FOR UPDATE');
            $stmt->execute([$cajaId]);
            $caja = $stmt->fetch();
            if (!$caja || $caja['estado'] !== 'Abierta') {
                throw new DomainException('La caja ya no está abierta.');
            }
            if ($tipo === 'Egreso') {
                $stmt = $this->db->prepare('SELECT fondo_inicial FROM tienda_cajas WHERE id=?');
                $stmt->execute([$cajaId]);
                $fondo = TiendaProducto::centavos((string) $stmt->fetchColumn());
                $r = $this->resumen($cajaId);
                $efectivo = $fondo + TiendaProducto::centavos((string) $r['efectivo'])
                    + TiendaProducto::centavos((string) $r['ingresos'])
                    - TiendaProducto::centavos((string) $r['egresos']);
                if ($centavos > $efectivo) {
                    throw new DomainException('El egreso supera el efectivo disponible en caja.');
                }
            }
            $stmt = $this->db->prepare('INSERT INTO tienda_caja_movimientos (caja_id,usuario_id,tipo,concepto,monto)
                VALUES (?,?,?,?,?)');
            $stmt->execute([$cajaId,$usuario,$tipo,$concepto,TiendaProducto::decimal($centavos)]);
            $this->db->commit();
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) { $this->db->rollBack(); }
            throw $e;
        }
    }

    public function resumen(int $cajaId): array
    {
        $stmt = $this->db->prepare("SELECT
            COALESCE(SUM(CASE WHEN estado='Completada' AND metodo_pago='Efectivo' THEN total ELSE 0 END),0) AS efectivo,
            COALESCE(SUM(CASE WHEN estado='Completada' AND metodo_pago<>'Efectivo' THEN total ELSE 0 END),0) AS otros,
            COUNT(CASE WHEN estado='Completada' THEN 1 END) AS ventas
            FROM tienda_ventas WHERE caja_id=?");
        $stmt->execute([$cajaId]);
        $datos = $stmt->fetch();
        $stmt = $this->db->prepare("SELECT
            COALESCE(SUM(CASE WHEN tipo='Ingreso' THEN monto ELSE 0 END),0) AS ingresos,
            COALESCE(SUM(CASE WHEN tipo='Egreso' THEN monto ELSE 0 END),0) AS egresos
            FROM tienda_caja_movimientos WHERE caja_id=?");
        $stmt->execute([$cajaId]);
        return array_merge($datos, $stmt->fetch());
    }

    public function movimientos(int $cajaId): array
    {
        $stmt = $this->db->prepare('SELECT m.*, u.usuario FROM tienda_caja_movimientos m
            JOIN usuarios u ON u.id=m.usuario_id WHERE m.caja_id=? ORDER BY m.id DESC');
        $stmt->execute([$cajaId]);
        return $stmt->fetchAll();
    }

    public function cerrar(int $cajaId, int $usuario, int $contado, string $observaciones): void
    {
        if ($contado < 0 || $contado > 1000000000 || mb_strlen($observaciones) > 5000) {
            throw new InvalidArgumentException('Datos del cierre inválidos.');
        }
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare('SELECT * FROM tienda_cajas WHERE id=? FOR UPDATE');
            $stmt->execute([$cajaId]);
            $caja = $stmt->fetch();
            if (!$caja || $caja['estado'] !== 'Abierta') {
                throw new DomainException('La caja ya está cerrada.');
            }
            $r = $this->resumen($cajaId);
            $esperado = TiendaProducto::centavos((string) $caja['fondo_inicial'])
                + TiendaProducto::centavos((string) $r['efectivo'])
                + TiendaProducto::centavos((string) $r['ingresos'])
                - TiendaProducto::centavos((string) $r['egresos']);
            if ($esperado < 0) {
                throw new DomainException('El efectivo esperado es negativo. Revisa los movimientos antes del cierre.');
            }
            $diferencia = $contado - $esperado;
            $diferenciaDecimal = ($diferencia < 0 ? '-' : '') . TiendaProducto::decimal(abs($diferencia));
            $stmt = $this->db->prepare("UPDATE tienda_cajas SET estado='Cerrada', fecha_cierre=NOW(),
                usuario_cierre_id=?, efectivo_esperado=?, efectivo_contado=?, diferencia=?, observaciones=?
                WHERE id=?");
            $stmt->execute([$usuario,TiendaProducto::decimal($esperado),TiendaProducto::decimal($contado),
                $diferenciaDecimal, $observaciones !== '' ? $observaciones : null,$cajaId]);
            $this->db->commit();
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) { $this->db->rollBack(); }
            throw $e;
        }
    }
}
