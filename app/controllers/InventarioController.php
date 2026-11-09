<?php
declare(strict_types=1);

require_once __DIR__ . '/../Models/Inventario.php';

final class InventarioController
{
    private Inventario $modelo;

    public function __construct()
    {
        $this->modelo = new Inventario();
    }

    /**
     * Listar productos con filtros.
     */
    public function listar(
        string $busqueda = '',
        string $categoria = '',
        string $estado = ''
    ): array {
        $estadosPermitidos = [
            '',
            'Activo',
            'Inactivo',
        ];

        $busqueda = trim($busqueda);
        $categoria = trim($categoria);

        if (!in_array($estado, $estadosPermitidos, true)) {
            $estado = '';
        }

        if (mb_strlen($categoria) > 100) {
            $categoria = '';
        }

        return $this->modelo->obtenerProductos(
            $busqueda,
            $categoria,
            $estado
        );
    }

    /**
     * Obtener un producto por ID.
     */
    public function obtenerPorId(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        return $this->modelo->obtenerProductoPorId($id);
    }

    /**
     * Obtener categorías disponibles.
     */
    public function obtenerCategorias(): array
    {
        return $this->modelo->obtenerCategorias();
    }

    /**
     * Obtener estadísticas.
     */
    public function obtenerResumen(): array
    {
        return $this->modelo->obtenerResumen();
    }

    /**
     * Registrar un producto.
     */
    public function registrar(
        array $datos,
        ?int $usuarioId
    ): array {
        $validacion = $this->validarDatosProducto($datos);

        if (!$validacion['exito']) {
            return $validacion;
        }

        if ($usuarioId !== null && $usuarioId <= 0) {
            $usuarioId = null;
        }

        $campos = $validacion['datos'];

        try {
            $id = $this->modelo->crearProducto(
                $campos['nombre'],
                $campos['categoria'],
                $campos['unidad_medida'],
                $campos['stock_minimo'],
                $campos['descripcion'],
                $usuarioId
            );

            return [
                'exito' => true,
                'mensaje' => 'El producto se registró correctamente.',
                'producto_id' => $id,
            ];

        } catch (Throwable $e) {
            error_log(
                'Error al registrar producto: ' . $e->getMessage()
            );

            if ($e instanceof PDOException && (string) $e->getCode() === '23000') {
                return [
                    'exito' => false,
                    'mensaje' => 'Ya existe un producto con ese nombre y unidad de medida.',
                ];
            }

            return [
                'exito' => false,
                'mensaje' => 'No fue posible registrar el producto. Intentá nuevamente.',
            ];
        }
    }

    /**
     * Actualizar información de un producto.
     */
    public function actualizar(array $datos): array
    {
        $id = filter_var(
            $datos['producto_id'] ?? null,
            FILTER_VALIDATE_INT
        );

        if ($id === false || $id === null || $id <= 0) {
            return [
                'exito' => false,
                'mensaje' => 'El identificador del producto no es válido.',
            ];
        }

        $validacion = $this->validarDatosProducto($datos);

        if (!$validacion['exito']) {
            return $validacion;
        }

        $campos = $validacion['datos'];

        try {
            $producto = $this->modelo->obtenerProductoPorId((int) $id);

            if ($producto === null) {
                return [
                    'exito' => false,
                    'mensaje' => 'El producto que intentás modificar no existe.',
                ];
            }

            $this->modelo->actualizarProducto(
                (int) $id,
                $campos['nombre'],
                $campos['categoria'],
                $campos['unidad_medida'],
                $campos['stock_minimo'],
                $campos['descripcion']
            );

            return [
                'exito' => true,
                'mensaje' => 'El producto se actualizó correctamente.',
            ];

        } catch (Throwable $e) {
            error_log(
                'Error al actualizar producto: ' . $e->getMessage()
            );

            if ($e instanceof PDOException && (string) $e->getCode() === '23000') {
                return [
                    'exito' => false,
                    'mensaje' => 'Ya existe un producto con ese nombre y unidad de medida.',
                ];
            }

            return [
                'exito' => false,
                'mensaje' => 'Ocurrió un error al actualizar el producto.',
            ];
        }
    }

    /**
     * Activar o desactivar un producto.
     */
    public function cambiarEstado(
        int $id,
        string $estado
    ): array {
        if ($id <= 0) {
            return [
                'exito' => false,
                'mensaje' => 'El identificador del producto no es válido.',
            ];
        }

        if (!in_array($estado, ['Activo', 'Inactivo'], true)) {
            return [
                'exito' => false,
                'mensaje' => 'El estado seleccionado no es válido.',
            ];
        }

        try {
            $producto = $this->modelo->obtenerProductoPorId($id);

            if ($producto === null) {
                return [
                    'exito' => false,
                    'mensaje' => 'El producto no existe.',
                ];
            }

            $this->modelo->cambiarEstadoProducto(
                $id,
                $estado
            );

            return [
                'exito' => true,
                'mensaje' => $estado === 'Activo'
                    ? 'El producto se activó correctamente.'
                    : 'El producto se desactivó correctamente.',
            ];

        } catch (Throwable $e) {
            error_log(
                'Error al cambiar estado del producto: ' . $e->getMessage()
            );

            return [
                'exito' => false,
                'mensaje' => 'No fue posible cambiar el estado del producto.',
            ];
        }
    }

    /**
     * Registrar ajustes manuales de existencias.
     */
    public function registrarAjuste(
        array $datos,
        ?int $usuarioId
    ): array {
        $productoId = filter_var(
            $datos['producto_id'] ?? null,
            FILTER_VALIDATE_INT
        );

        if (
            $productoId === false
            || $productoId === null
            || $productoId <= 0
        ) {
            return [
                'exito' => false,
                'mensaje' => 'Seleccioná un producto válido.',
            ];
        }

        $tipoMovimiento = trim(
            (string) ($datos['tipo_movimiento'] ?? '')
        );

        if (!in_array(
            $tipoMovimiento,
            ['Ajuste positivo', 'Ajuste negativo'],
            true
        )) {
            return [
                'exito' => false,
                'mensaje' => 'Seleccioná un tipo de ajuste válido.',
            ];
        }

        $cantidad = $this->validarCantidad(
            $datos['cantidad'] ?? null
        );

        if ($cantidad === null) {
            return [
                'exito' => false,
                'mensaje' => 'Ingresá una cantidad mayor que cero, con máximo dos decimales.',
            ];
        }

        $observaciones = trim(
            (string) ($datos['observaciones'] ?? '')
        );

        if (
            $observaciones === ''
            || mb_strlen($observaciones) > 5000
        ) {
            return [
                'exito' => false,
                'mensaje' => 'Indicá el motivo del ajuste (máximo 5000 caracteres).',
            ];
        }

        if ($usuarioId !== null && $usuarioId <= 0) {
            $usuarioId = null;
        }

        try {
            $this->modelo->registrarAjusteManual(
                (int) $productoId,
                $tipoMovimiento,
                $cantidad,
                $observaciones,
                $usuarioId
            );

            return [
                'exito' => true,
                'mensaje' => 'El ajuste de inventario se registró correctamente.',
            ];

        } catch (Throwable $e) {
            error_log(
                'Error al registrar ajuste de inventario: ' . $e->getMessage()
            );

            return [
                'exito' => false,
                'mensaje' => $e instanceof InvalidArgumentException
                    ? $e->getMessage()
                    : (
                        str_contains($e->getMessage(), 'existencias disponibles')
                            ? $e->getMessage()
                            : 'No fue posible realizar el ajuste. Verificá las existencias e intentá nuevamente.'
                    ),
            ];
        }
    }

    /**
     * Consultar el historial de movimientos.
     */
    public function listarMovimientos(
        string $busqueda = '',
        string $tipoMovimiento = '',
        int $productoId = 0,
        int $limite = 100
    ): array {
        $tiposPermitidos = [
            '',
            'Entrada',
            'Salida',
            'Ajuste positivo',
            'Ajuste negativo',
        ];

        if (!in_array($tipoMovimiento, $tiposPermitidos, true)) {
            $tipoMovimiento = '';
        }

        if ($productoId < 0) {
            $productoId = 0;
        }

        $limite = max(1, min($limite, 500));

        return $this->modelo->obtenerMovimientos(
            trim($busqueda),
            $tipoMovimiento,
            $productoId,
            $limite
        );
    }

    /**
     * Validar los datos de un producto.
     */
    private function validarDatosProducto(array $datos): array
    {
        $nombre = trim(
            (string) ($datos['nombre'] ?? '')
        );

        $categoria = trim(
            (string) ($datos['categoria'] ?? '')
        );

        $unidadMedida = trim(
            (string) ($datos['unidad_medida'] ?? '')
        );

        $descripcion = trim(
            (string) ($datos['descripcion'] ?? '')
        );

        $stockMinimo = $this->validarCantidad(
            $datos['stock_minimo'] ?? '0',
            true
        );

        if (
            $nombre === ''
            || mb_strlen($nombre) > 150
        ) {
            return [
                'exito' => false,
                'mensaje' => 'El nombre es obligatorio y debe tener máximo 150 caracteres.',
            ];
        }

        if (
            $categoria === ''
            || mb_strlen($categoria) > 100
        ) {
            return [
                'exito' => false,
                'mensaje' => 'Seleccioná una categoría válida.',
            ];
        }

        if (
            $unidadMedida === ''
            || mb_strlen($unidadMedida) > 30
        ) {
            return [
                'exito' => false,
                'mensaje' => 'Seleccioná una unidad de medida válida.',
            ];
        }

        if ($stockMinimo === null) {
            return [
                'exito' => false,
                'mensaje' => 'El stock mínimo debe ser cero o un número positivo con máximo dos decimales.',
            ];
        }

        if (mb_strlen($descripcion) > 5000) {
            return [
                'exito' => false,
                'mensaje' => 'La descripción no debe superar los 5000 caracteres.',
            ];
        }

        return [
            'exito' => true,
            'datos' => [
                'nombre' => $nombre,
                'categoria' => $categoria,
                'unidad_medida' => $unidadMedida,
                'stock_minimo' => $stockMinimo,
                'descripcion' => $descripcion !== ''
                    ? $descripcion
                    : null,
            ],
        ];
    }

    /**
     * Validar y normalizar una cantidad decimal.
     */
    private function validarCantidad(
        mixed $valor,
        bool $permitirCero = false
    ): ?string {
        if (!is_scalar($valor)) {
            return null;
        }

        $cantidad = trim(
            str_replace(',', '.', (string) $valor)
        );

        if (!preg_match(
            '/^\d{1,10}(?:\.\d{1,2})?$/',
            $cantidad
        )) {
            return null;
        }

        [$enteros, $decimales] = array_pad(
            explode('.', $cantidad, 2),
            2,
            ''
        );

        $enteros = ltrim($enteros, '0');

        if ($enteros === '') {
            $enteros = '0';
        }

        $cantidadNormalizada = $enteros . '.'
            . str_pad($decimales, 2, '0');

        if (
            !$permitirCero
            && (float) $cantidadNormalizada <= 0
        ) {
            return null;
        }

        return $cantidadNormalizada;
    }
}
