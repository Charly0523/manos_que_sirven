<?php
declare(strict_types=1);

require_once __DIR__ . '/../Models/Donacion.php';
require_once __DIR__ . '/../../config/app.php';

final class DonacionController
{
    private Donacion $modelo;

    public function __construct()
    {
        $this->modelo = new Donacion();
    }

    /**
     * Obtener productos activos del inventario.
     */
    public function obtenerProductosInventario(): array
    {
        return $this->modelo->obtenerProductosInventario();
    }

    /**
     * Listar donaciones.
     */
    public function listar(
        string $busqueda = '',
        string $tipoDonacion = '',
        string $estadoRecepcion = ''
    ): array {
        $tiposPermitidos = [
            '',
            'Monetaria',
            'En especie',
        ];

        $estadosPermitidos = [
            '',
            'Recibida',
            'Pendiente',
            'Anulada',
        ];

        if (!in_array($tipoDonacion, $tiposPermitidos, true)) {
            $tipoDonacion = '';
        }

        if (!in_array($estadoRecepcion, $estadosPermitidos, true)) {
            $estadoRecepcion = '';
        }

        return $this->modelo->obtenerTodas(
            trim($busqueda),
            $tipoDonacion,
            $estadoRecepcion
        );
    }

    /**
     * Obtener una donación por ID.
     */
    public function obtenerPorId(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        return $this->modelo->obtenerPorId($id);
    }

    /**
     * Registrar donación.
     */
    public function registrar(
        array $datos,
        ?int $usuarioId
    ): array {
        $validacion = $this->validarDatos($datos);

        if (!$validacion['exito']) {
            return $validacion;
        }

        if ($usuarioId !== null && $usuarioId <= 0) {
            $usuarioId = null;
        }

        $campos = $validacion['datos'];

        try {
            $creado = $this->modelo->crear(
                $campos['tipo_donacion'],
                $campos['donante'],
                $campos['es_anonima'],
                $campos['monto'],
                $campos['inventario_producto_id'],
                $campos['cantidad'],
                $campos['descripcion'],
                $campos['fecha_donacion'],
                $campos['estado_recepcion'],
                $campos['destino'],
                $campos['observaciones'],
                $usuarioId
            );

            if (!$creado) {
                return [
                    'exito' => false,
                    'mensaje' => 'No fue posible registrar la donación.',
                ];
            }

            $mensaje = $campos['tipo_donacion'] === 'En especie'
                && $campos['estado_recepcion'] === 'Recibida'
                    ? 'La donación se registró correctamente y los productos ingresaron al inventario.'
                    : 'La donación se registró correctamente.';

            return [
                'exito' => true,
                'mensaje' => $mensaje,
            ];

        } catch (PDOException $e) {
            error_log(
                'Error de base de datos al registrar donación: '
                . $e->getMessage()
            );

            if ((string) $e->getCode() === '23000') {
                return [
                    'exito' => false,
                    'mensaje' => 'No fue posible relacionar el producto con el inventario. Verificá que el producto exista.',
                ];
            }

            return [
                'exito' => false,
                'mensaje' => 'Ocurrió un problema con la base de datos al registrar la donación.',
            ];

        } catch (InvalidArgumentException | RuntimeException $e) {
            error_log(
                'Validación al registrar donación: '
                . $e->getMessage()
            );

            return [
                'exito' => false,
                'mensaje' => $e->getMessage(),
            ];

        } catch (Throwable $e) {
            error_log(
                'Error al registrar donación: '
                . $e->getMessage()
            );

            return [
                'exito' => false,
                'mensaje' => 'No fue posible registrar la donación. Verificá los datos e intentá nuevamente.',
            ];
        }
    }

    /**
     * Actualizar donación.
     */
    public function actualizar(
        array $datos,
        ?int $usuarioId = null
    ): array {
        $id = filter_var(
            $datos['donacion_id'] ?? null,
            FILTER_VALIDATE_INT
        );

        if ($id === false || $id === null || $id <= 0) {
            return [
                'exito' => false,
                'mensaje' => 'El identificador de la donación no es válido.',
            ];
        }

        $donacionExistente = $this->modelo->obtenerPorId((int) $id);

        if ($donacionExistente === null) {
            return [
                'exito' => false,
                'mensaje' => 'La donación que intentás editar no existe.',
            ];
        }

        if (($donacionExistente['estado_recepcion'] ?? '') === 'Anulada') {
            return [
                'exito' => false,
                'mensaje' => 'No se pueden modificar donaciones anuladas.',
            ];
        }

        $validacion = $this->validarDatos($datos);

        if (!$validacion['exito']) {
            return $validacion;
        }

        if ($usuarioId !== null && $usuarioId <= 0) {
            $usuarioId = null;
        }

        $campos = $validacion['datos'];

        try {
            $actualizado = $this->modelo->actualizar(
                (int) $id,
                $campos['tipo_donacion'],
                $campos['donante'],
                $campos['es_anonima'],
                $campos['monto'],
                $campos['inventario_producto_id'],
                $campos['cantidad'],
                $campos['descripcion'],
                $campos['fecha_donacion'],
                $campos['estado_recepcion'],
                $campos['destino'],
                $campos['observaciones'],
                $usuarioId
            );

            if (!$actualizado) {
                return [
                    'exito' => false,
                    'mensaje' => 'No fue posible actualizar la donación.',
                ];
            }

            return [
                'exito' => true,
                'mensaje' => 'La donación se actualizó correctamente y las existencias del inventario fueron sincronizadas.',
            ];

        } catch (PDOException $e) {
            error_log(
                'Error de base de datos al actualizar donación: '
                . $e->getMessage()
            );

            return [
                'exito' => false,
                'mensaje' => 'Ocurrió un problema con la base de datos al actualizar la donación.',
            ];

        } catch (InvalidArgumentException | RuntimeException $e) {
            error_log(
                'Validación al actualizar donación: '
                . $e->getMessage()
            );

            return [
                'exito' => false,
                'mensaje' => $e->getMessage(),
            ];

        } catch (Throwable $e) {
            error_log(
                'Error al actualizar donación: '
                . $e->getMessage()
            );

            return [
                'exito' => false,
                'mensaje' => 'Ocurrió un error al actualizar la donación. Intentá nuevamente.',
            ];
        }
    }

    /**
     * Anular donación sin eliminar su historial.
     */
    public function anular(
        int $id,
        ?int $usuarioId = null
    ): array {
        if ($id <= 0) {
            return [
                'exito' => false,
                'mensaje' => 'El identificador de la donación no es válido.',
            ];
        }

        try {
            $donacion = $this->modelo->obtenerPorId($id);

            if ($donacion === null) {
                return [
                    'exito' => false,
                    'mensaje' => 'La donación no existe o ya fue eliminada.',
                ];
            }

            if (($donacion['estado_recepcion'] ?? '') === 'Anulada') {
                return [
                    'exito' => false,
                    'mensaje' => 'Esta donación ya se encuentra anulada.',
                ];
            }

            $anulada = $this->modelo->anular(
                $id,
                $usuarioId
            );

            if (!$anulada) {
                return [
                    'exito' => false,
                    'mensaje' => 'No fue posible anular la donación.',
                ];
            }

            return [
                'exito' => true,
                'mensaje' => 'La donación se anuló correctamente y se revirtieron sus existencias del inventario cuando correspondía.',
            ];

        } catch (PDOException $e) {
            error_log(
                'Error de base de datos al anular donación: '
                . $e->getMessage()
            );

            return [
                'exito' => false,
                'mensaje' => 'No fue posible completar la anulación por un problema con la base de datos.',
            ];

        } catch (InvalidArgumentException | RuntimeException $e) {
            error_log(
                'Validación al anular donación: '
                . $e->getMessage()
            );

            return [
                'exito' => false,
                'mensaje' => $e->getMessage(),
            ];

        } catch (Throwable $e) {
            error_log(
                'Error al anular donación: '
                . $e->getMessage()
            );

            return [
                'exito' => false,
                'mensaje' => 'Ocurrió un error al anular la donación. Intentá nuevamente.',
            ];
        }
    }

    /**
     * Obtener resumen de donaciones.
     */
    public function obtenerResumen(): array
    {
        return $this->modelo->obtenerResumen();
    }

    /**
     * Validar todos los datos del formulario.
     */
    private function validarDatos(array $datos): array
    {
        $tipoDonacion = trim(
            (string) ($datos['tipo_donacion'] ?? '')
        );

        if (!in_array(
            $tipoDonacion,
            ['Monetaria', 'En especie'],
            true
        )) {
            return [
                'exito' => false,
                'mensaje' => 'Seleccioná un tipo de donación válido.',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Donante
        |--------------------------------------------------------------------------
        */

        $anonimaValor = $datos['es_anonima'] ?? '';

        $esAnonima = in_array(
            (string) $anonimaValor,
            ['1', 'on', 'true'],
            true
        );

        $donante = trim(
            (string) ($datos['donante'] ?? '')
        );

        if ($esAnonima) {
            $donanteFinal = null;
        } else {
            if (
                $donante === ''
                || mb_strlen($donante) > 150
            ) {
                return [
                    'exito' => false,
                    'mensaje' => 'Ingresá el nombre del donante (máximo 150 caracteres) o marcá la donación como anónima.',
                ];
            }

            $donanteFinal = $donante;
        }

        /*
        |--------------------------------------------------------------------------
        | Fecha
        |--------------------------------------------------------------------------
        */

        $fechaDonacion = trim(
            (string) ($datos['fecha_donacion'] ?? '')
        );

        $fecha = DateTimeImmutable::createFromFormat(
            '!Y-m-d',
            $fechaDonacion
        );

        $erroresFecha = DateTimeImmutable::getLastErrors();

        if (
            !$fecha
            || (
                $erroresFecha !== false
                && (
                    $erroresFecha['warning_count'] > 0
                    || $erroresFecha['error_count'] > 0
                )
            )
            || $fecha->format('Y-m-d') !== $fechaDonacion
        ) {
            return [
                'exito' => false,
                'mensaje' => 'Ingresá una fecha de donación válida.',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Estado
        |--------------------------------------------------------------------------
        */

        $estadoRecepcion = trim(
            (string) ($datos['estado_recepcion'] ?? 'Recibida')
        );

        if (!in_array(
            $estadoRecepcion,
            ['Recibida', 'Pendiente'],
            true
        )) {
            return [
                'exito' => false,
                'mensaje' => 'Seleccioná un estado de recepción válido.',
            ];
        }

        if (
            $estadoRecepcion === 'Recibida'
            && $fechaDonacion > date('Y-m-d')
        ) {
            return [
                'exito' => false,
                'mensaje' => 'La fecha de una donación recibida no puede ser futura.',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Valores iniciales
        |--------------------------------------------------------------------------
        */

        $monto = null;
        $inventarioProductoId = null;
        $cantidad = null;

        /*
        |--------------------------------------------------------------------------
        | Donación monetaria
        |--------------------------------------------------------------------------
        */

        if ($tipoDonacion === 'Monetaria') {
            $montoTexto = str_replace(
                ',',
                '.',
                trim((string) ($datos['monto'] ?? ''))
            );

            if (
                !preg_match(
                    '/^\d{1,10}(?:\.\d{1,2})?$/',
                    $montoTexto
                )
                || !is_numeric($montoTexto)
                || (float) $montoTexto <= 0
            ) {
                return [
                    'exito' => false,
                    'mensaje' => 'Ingresá un monto válido mayor que cero, con máximo dos decimales.',
                ];
            }

            $monto = number_format(
                (float) $montoTexto,
                2,
                '.',
                ''
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Donación en especie
        |--------------------------------------------------------------------------
        */

        if ($tipoDonacion === 'En especie') {
            $productoId = filter_var(
                $datos['inventario_producto_id'] ?? null,
                FILTER_VALIDATE_INT
            );

            if (
                $productoId === false
                || $productoId === null
                || $productoId <= 0
            ) {
                return [
                    'exito' => false,
                    'mensaje' => 'Seleccioná un producto válido del inventario.',
                ];
            }

            $productoInventario = $this->modelo->obtenerProductoInventario(
                (int) $productoId
            );

            if ($productoInventario === null) {
                return [
                    'exito' => false,
                    'mensaje' => 'El producto seleccionado no existe en el inventario.',
                ];
            }

            if (($productoInventario['estado'] ?? '') !== 'Activo') {
                return [
                    'exito' => false,
                    'mensaje' => 'El producto seleccionado está inactivo.',
                ];
            }

            $cantidadTexto = str_replace(
                ',',
                '.',
                trim((string) ($datos['cantidad'] ?? ''))
            );

            if (
                !preg_match(
                    '/^\d{1,8}(?:\.\d{1,2})?$/',
                    $cantidadTexto
                )
                || !is_numeric($cantidadTexto)
                || (float) $cantidadTexto <= 0
            ) {
                return [
                    'exito' => false,
                    'mensaje' => 'Ingresá una cantidad válida mayor que cero, con máximo dos decimales.',
                ];
            }

            $unidadMedida = mb_strtolower(
                trim((string) $productoInventario['unidad_medida']),
                'UTF-8'
            );

            /*
             * Las unidades individuales no admiten decimales.
             */
            if (
                in_array(
                    $unidadMedida,
                    ['unidad', 'unidades'],
                    true
                )
                && str_contains($cantidadTexto, '.')
                && (int) substr(
                    strrchr($cantidadTexto, '.'),
                    1
                ) !== 0
            ) {
                return [
                    'exito' => false,
                    'mensaje' => 'Este producto se registra por unidades completas. Ingresá un número entero, por ejemplo 5 y no 5.50.',
                ];
            }

            $cantidad = number_format(
                (float) $cantidadTexto,
                2,
                '.',
                ''
            );

            $inventarioProductoId = (int) $productoId;
            $monto = null;
        }

        /*
        |--------------------------------------------------------------------------
        | Información adicional
        |--------------------------------------------------------------------------
        */

        $descripcion = trim(
            (string) ($datos['descripcion'] ?? '')
        );

        $destino = trim(
            (string) ($datos['destino'] ?? '')
        );

        $observaciones = trim(
            (string) ($datos['observaciones'] ?? '')
        );

        if (mb_strlen($descripcion) > 5000) {
            return [
                'exito' => false,
                'mensaje' => 'La descripción no debe superar los 5000 caracteres.',
            ];
        }

        if (mb_strlen($destino) > 180) {
            return [
                'exito' => false,
                'mensaje' => 'El destino no debe superar los 180 caracteres.',
            ];
        }

        if (mb_strlen($observaciones) > 5000) {
            return [
                'exito' => false,
                'mensaje' => 'Las observaciones no deben superar los 5000 caracteres.',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Resultado
        |--------------------------------------------------------------------------
        */

        return [
            'exito' => true,

            'datos' => [
                'tipo_donacion' => $tipoDonacion,
                'donante' => $donanteFinal,
                'es_anonima' => $esAnonima,
                'monto' => $monto,
                'inventario_producto_id' => $inventarioProductoId,
                'cantidad' => $cantidad,

                'descripcion' => $descripcion !== ''
                    ? $descripcion
                    : null,

                'fecha_donacion' => $fechaDonacion,
                'estado_recepcion' => $estadoRecepcion,

                'destino' => $destino !== ''
                    ? $destino
                    : null,

                'observaciones' => $observaciones !== ''
                    ? $observaciones
                    : null,
            ],
        ];
    }
}
