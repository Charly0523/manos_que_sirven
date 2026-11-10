<?php
declare(strict_types=1);

require_once __DIR__ . '/../models/TiendaProducto.php';
require_once __DIR__ . '/../models/TiendaCaja.php';
require_once __DIR__ . '/../models/TiendaVenta.php';

final class TiendaController
{
    public TiendaProducto $productos;
    public TiendaCaja $cajas;
    public TiendaVenta $ventas;

    public function __construct()
    {
        $this->productos = new TiendaProducto();
        $this->cajas = new TiendaCaja();
        $this->ventas = new TiendaVenta();
    }

    public function ejecutar(string $accion, array $datos, int $usuarioId, bool $admin): array
    {
        try {
            switch ($accion) {
                case 'categoria_crear':
                    $this->exigirAdmin($admin);
                    $this->productos->crearCategoria((string) ($datos['nombre_categoria'] ?? ''));
                    return ['tab' => 'productos', 'mensaje' => 'Categoría agregada.'];
                case 'producto_guardar':
                    $this->exigirAdmin($admin);
                    $this->productos->guardar($datos);
                    return ['tab' => 'productos', 'mensaje' => 'Producto guardado.'];
                case 'stock_ajustar':
                    $this->exigirAdmin($admin);
                    $this->productos->ajustarExistencias(
                        $this->entero($datos['producto_id'] ?? null),
                        (string) ($datos['tipo'] ?? ''),
                        $this->entero($datos['cantidad'] ?? null),
                        $usuarioId,
                        trim((string) ($datos['observaciones'] ?? ''))
                    );
                    return ['tab' => 'productos', 'mensaje' => 'Existencias actualizadas.'];
                case 'caja_abrir':
                    $this->cajas->abrir($usuarioId, TiendaProducto::centavos((string) ($datos['fondo_inicial'] ?? '')));
                    return ['tab' => 'caja', 'mensaje' => 'Caja abierta.'];
                case 'caja_movimiento':
                    $this->cajas->movimiento(
                        $this->entero($datos['caja_id'] ?? null),
                        $usuarioId,
                        (string) ($datos['tipo'] ?? ''),
                        TiendaProducto::centavos((string) ($datos['monto'] ?? '')),
                        (string) ($datos['concepto'] ?? '')
                    );
                    return ['tab' => 'caja', 'mensaje' => 'Movimiento de caja registrado.'];
                case 'caja_cerrar':
                    $this->exigirAdmin($admin);
                    $this->cajas->cerrar(
                        $this->entero($datos['caja_id'] ?? null),
                        $usuarioId,
                        TiendaProducto::centavos((string) ($datos['efectivo_contado'] ?? '')),
                        trim((string) ($datos['observaciones'] ?? ''))
                    );
                    return ['tab' => 'caja', 'mensaje' => 'Caja cerrada y arqueada.'];
                case 'venta_registrar':
                    $carrito = json_decode((string) ($datos['carrito'] ?? ''), true, 512, JSON_THROW_ON_ERROR);
                    if (!is_array($carrito)) { throw new InvalidArgumentException('Carrito no válido.'); }
                    $metodo = (string) ($datos['metodo_pago'] ?? '');
                    $descuento = TiendaProducto::centavos((string) ($datos['descuento'] ?? '0'));
                    $recibido = $metodo === 'Efectivo'
                        ? TiendaProducto::centavos((string) ($datos['monto_recibido'] ?? '')) : null;
                    $id = $this->ventas->vender($usuarioId, $carrito, $metodo, $descuento, $recibido);
                    return ['tab' => 'ventas', 'recibo' => $id, 'mensaje' => 'Venta registrada correctamente.'];
                case 'venta_anular':
                    $this->exigirAdmin($admin);
                    $this->ventas->anular(
                        $this->entero($datos['venta_id'] ?? null),
                        $usuarioId,
                        (string) ($datos['motivo_anulacion'] ?? '')
                    );
                    return ['tab' => 'ventas', 'mensaje' => 'Venta anulada y existencias restauradas.'];
                default:
                    throw new InvalidArgumentException('Acción no reconocida.');
            }
        } catch (PDOException $error) {
            error_log('Tienda POS: ' . $error->getMessage());
            if ($error->getCode() === '23000') {
                throw new DomainException('Ya existe un registro con esos datos (por ejemplo, un código o categoría).');
            }
            throw new RuntimeException('Ocurrió un error al guardar. Comprueba que las tablas del POS estén importadas.');
        }
    }

    private function entero(mixed $valor): int
    {
        $numero = filter_var($valor, FILTER_VALIDATE_INT);
        if (!is_int($numero) || $numero <= 0) {
            throw new InvalidArgumentException('El identificador o cantidad no es válido.');
        }
        return $numero;
    }

    private function exigirAdmin(bool $admin): void
    {
        if (!$admin) {
            throw new DomainException('Esta operación requiere permisos de Administrador.');
        }
    }
}
