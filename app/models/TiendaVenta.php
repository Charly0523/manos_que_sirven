<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/TiendaProducto.php';

final class TiendaVenta
{
    private PDO $db;
    public function __construct() { $this->db = obtenerConexion(); }

    public function listar(): array
    {
        return $this->db->query('SELECT v.*, u.usuario AS vendedor, a.usuario AS anulador
            FROM tienda_ventas v JOIN usuarios u ON u.id=v.usuario_id
            LEFT JOIN usuarios a ON a.id=v.usuario_anulacion_id
            ORDER BY v.id DESC LIMIT 80')->fetchAll();
    }

    public function comprobante(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT v.*, u.usuario AS vendedor
            FROM tienda_ventas v JOIN usuarios u ON u.id=v.usuario_id WHERE v.id=? LIMIT 1');
        $stmt->execute([$id]);
        $venta = $stmt->fetch();
        if (!$venta) { return null; }
        $stmt = $this->db->prepare('SELECT nombre_producto,cantidad,precio_unitario,subtotal
            FROM tienda_venta_detalles WHERE venta_id=? ORDER BY id');
        $stmt->execute([$id]);
        $venta['lineas'] = $stmt->fetchAll();
        return $venta;
    }

    public function vender(int $usuarioId, array $lineas, string $metodo, int $descuento, ?int $recibido): int
    {
        if (!in_array($metodo, ['Efectivo','Transferencia','Otro'], true)) {
            throw new InvalidArgumentException('Selecciona un método de pago válido.');
        }
        if (count($lineas) < 1 || count($lineas) > 100) {
            throw new InvalidArgumentException('Agrega entre 1 y 100 productos a la venta.');
        }
        $cantidades = [];
        foreach ($lineas as $linea) {
            if (!is_array($linea)) {
                throw new InvalidArgumentException('El carrito tiene un formato incorrecto.');
            }
            $id = filter_var($linea['id'] ?? null, FILTER_VALIDATE_INT);
            $cantidad = filter_var($linea['cantidad'] ?? null, FILTER_VALIDATE_INT);
            if (!$id || !$cantidad || $id < 1 || $cantidad < 1 || $cantidad > 10000) {
                throw new InvalidArgumentException('Revisa las cantidades del carrito.');
            }
            if (isset($cantidades[$id])) {
                throw new InvalidArgumentException('Hay productos repetidos en el carrito.');
            }
            $cantidades[$id] = $cantidad;
        }
        ksort($cantidades, SORT_NUMERIC);
        $this->db->beginTransaction();
        try {
            $caja = $this->db->query("SELECT id FROM tienda_cajas
                WHERE estado='Abierta' ORDER BY id DESC LIMIT 1 FOR UPDATE")->fetch();
            if (!$caja) { throw new DomainException('Debes abrir caja antes de registrar una venta.'); }
            $detalles = [];
            $subtotal = 0;
            $consulta = $this->db->prepare('SELECT id,nombre,precio_venta,stock_actual,activo
                FROM tienda_productos WHERE id=? FOR UPDATE');
            foreach ($cantidades as $id => $cantidad) {
                $consulta->execute([$id]);
                $producto = $consulta->fetch();
                if (!$producto || (int) $producto['activo'] !== 1) {
                    throw new DomainException('Un producto ya no está disponible. Actualiza el carrito.');
                }
                if ((int) $producto['stock_actual'] < $cantidad) {
                    throw new DomainException('Existencias insuficientes para ' . $producto['nombre'] . '.');
                }
                $precio = TiendaProducto::centavos((string) $producto['precio_venta']);
                $lineaTotal = $precio * $cantidad;
                $subtotal += $lineaTotal;
                if ($subtotal > 100000000000) {
                    throw new InvalidArgumentException('El importe de la venta es demasiado alto.');
                }
                $detalles[] = [$producto, $cantidad, $precio, $lineaTotal];
            }
            if ($descuento < 0 || $descuento > $subtotal) {
                throw new InvalidArgumentException('El descuento no puede superar el subtotal.');
            }
            $total = $subtotal - $descuento;
            if ($metodo === 'Efectivo' && ($recibido === null || $recibido < $total)) {
                throw new InvalidArgumentException('El efectivo recibido no cubre el total.');
            }
            $cambio = $metodo === 'Efectivo' ? $recibido - $total : 0;
            $stmt = $this->db->prepare('INSERT INTO tienda_ventas
                (caja_id,usuario_id,subtotal,descuento,total,metodo_pago,monto_recibido,cambio)
                VALUES (?,?,?,?,?,?,?,?)');
            $stmt->execute([
                (int) $caja['id'], $usuarioId, TiendaProducto::decimal($subtotal),
                TiendaProducto::decimal($descuento), TiendaProducto::decimal($total), $metodo,
                $metodo === 'Efectivo' ? TiendaProducto::decimal($recibido) : null,
                TiendaProducto::decimal($cambio),
            ]);
            $ventaId = (int) $this->db->lastInsertId();
            $codigo = 'PAR-' . str_pad((string) $ventaId, 7, '0', STR_PAD_LEFT);
            $stmt = $this->db->prepare('UPDATE tienda_ventas SET codigo_venta=? WHERE id=?');
            $stmt->execute([$codigo, $ventaId]);
            $detalleStmt = $this->db->prepare('INSERT INTO tienda_venta_detalles
                (venta_id,producto_id,nombre_producto,cantidad,precio_unitario,subtotal)
                VALUES (?,?,?,?,?,?)');
            $stockStmt = $this->db->prepare('UPDATE tienda_productos SET stock_actual=? WHERE id=?');
            $movStmt = $this->db->prepare('INSERT INTO tienda_inventario_movimientos
                (producto_id,venta_id,usuario_id,tipo,origen,cantidad,stock_anterior,stock_nuevo,observaciones)
                VALUES (?,?,?,\'Salida\',\'Venta\',?,?,?,NULL)');
            foreach ($detalles as [$producto, $cantidad, $precio, $lineaTotal]) {
                $antes = (int) $producto['stock_actual'];
                $despues = $antes - $cantidad;
                $detalleStmt->execute([
                    $ventaId, (int) $producto['id'], $producto['nombre'], $cantidad,
                    TiendaProducto::decimal($precio), TiendaProducto::decimal($lineaTotal),
                ]);
                $stockStmt->execute([$despues, (int) $producto['id']]);
                $movStmt->execute([(int) $producto['id'],$ventaId,$usuarioId,$cantidad,$antes,$despues]);
            }
            $this->db->commit();
            return $ventaId;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) { $this->db->rollBack(); }
            throw $e;
        }
    }

    public function anular(int $id, int $usuario, string $motivo): void
    {
        $motivo = trim($motivo);
        if ($id < 1 || $motivo === '' || mb_strlen($motivo) > 255) {
            throw new InvalidArgumentException('Indica el motivo de anulación (máximo 255 caracteres).');
        }
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare('SELECT caja_id FROM tienda_ventas WHERE id=?');
            $stmt->execute([$id]);
            $cajaId = $stmt->fetchColumn();
            if (!$cajaId) { throw new InvalidArgumentException('Venta no encontrada.'); }
            $stmt = $this->db->prepare('SELECT estado FROM tienda_cajas WHERE id=? FOR UPDATE');
            $stmt->execute([(int) $cajaId]);
            if ($stmt->fetchColumn() !== 'Abierta') {
                throw new DomainException('No se pueden anular ventas de una caja cerrada.');
            }
            $stmt = $this->db->prepare('SELECT estado FROM tienda_ventas WHERE id=? FOR UPDATE');
            $stmt->execute([$id]);
            if ($stmt->fetchColumn() !== 'Completada') {
                throw new DomainException('Esta venta ya está anulada.');
            }
            $stmt = $this->db->prepare('SELECT producto_id,cantidad FROM tienda_venta_detalles
                WHERE venta_id=? ORDER BY producto_id');
            $stmt->execute([$id]);
            $items = $stmt->fetchAll();
            $prodStmt = $this->db->prepare('SELECT stock_actual FROM tienda_productos WHERE id=? FOR UPDATE');
            $updateStmt = $this->db->prepare('UPDATE tienda_productos SET stock_actual=? WHERE id=?');
            $movStmt = $this->db->prepare('INSERT INTO tienda_inventario_movimientos
                (producto_id,venta_id,usuario_id,tipo,origen,cantidad,stock_anterior,stock_nuevo,observaciones)
                VALUES (?,?,?,\'Entrada\',\'Anulacion\',?,?,?,?)');
            foreach ($items as $item) {
                $prodId = (int) $item['producto_id'];
                $cantidad = (int) $item['cantidad'];
                $prodStmt->execute([$prodId]);
                $anterior = $prodStmt->fetchColumn();
                if ($anterior === false) { throw new DomainException('Producto no encontrado para restaurar stock.'); }
                $nuevo = (int) $anterior + $cantidad;
                if ($nuevo > 4294967295) { throw new DomainException('Stock máximo superado.'); }
                $updateStmt->execute([$nuevo,$prodId]);
                $movStmt->execute([$prodId,$id,$usuario,$cantidad,(int)$anterior,$nuevo,'Venta anulada: '.$motivo]);
            }
            $stmt = $this->db->prepare("UPDATE tienda_ventas SET estado='Anulada',
                usuario_anulacion_id=?,fecha_anulacion=NOW(),motivo_anulacion=? WHERE id=?");
            $stmt->execute([$usuario,$motivo,$id]);
            $this->db->commit();
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) { $this->db->rollBack(); }
            throw $e;
        }
    }
}
