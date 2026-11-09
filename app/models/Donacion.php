<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';

final class Donacion
{
    /**
     * Obtener productos activos del inventario.
     */
    public function obtenerProductosInventario(): array
    {
        $sql = "SELECT
                    id,
                    nombre,
                    categoria,
                    unidad_medida,
                    stock_actual,
                    stock_minimo
                FROM inventario_productos
                WHERE estado = 'Activo'
                ORDER BY nombre ASC";

        return obtenerConexion()->query($sql)->fetchAll();
    }

    /**
     * Obtener un producto del inventario.
     */
    public function obtenerProductoInventario(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $sql = 'SELECT
                    id,
                    nombre,
                    categoria,
                    unidad_medida,
                    stock_actual,
                    stock_minimo,
                    estado
                FROM inventario_productos
                WHERE id = :id
                LIMIT 1';

        $consulta = obtenerConexion()->prepare($sql);
        $consulta->execute(['id' => $id]);

        $producto = $consulta->fetch();

        return $producto ?: null;
    }

    /**
     * Registrar una donación.
     *
     * Si es una donación en especie recibida,
     * aumenta automáticamente las existencias.
     */
    public function crear(
        string $tipoDonacion,
        ?string $donante,
        bool $esAnonima,
        ?string $monto,
        ?int $inventarioProductoId,
        ?string $cantidad,
        ?string $descripcion,
        string $fechaDonacion,
        string $estadoRecepcion,
        ?string $destino,
        ?string $observaciones,
        ?int $usuarioId
    ): bool {
        $conexion = obtenerConexion();

        $conexion->beginTransaction();

        try {
            $productoInventario = null;
            $producto = null;
            $unidadMedida = null;
            $cantidadNormalizada = null;

            if ($tipoDonacion === 'En especie') {
                $productoInventario = $this->obtenerProductoBloqueado(
                    $conexion,
                    $inventarioProductoId
                );

                if ($productoInventario['estado'] !== 'Activo') {
                    throw new RuntimeException(
                        'El producto seleccionado está inactivo.'
                    );
                }

                if ($cantidad === null) {
                    throw new InvalidArgumentException(
                        'La cantidad de la donación es obligatoria.'
                    );
                }

                $producto = (string) $productoInventario['nombre'];
                $unidadMedida = (string) $productoInventario['unidad_medida'];

                $cantidadNormalizada = $this->normalizarCantidad(
                    $cantidad,
                    $unidadMedida
                );

                $monto = null;
            } else {
                $inventarioProductoId = null;
                $monto = $monto;
                $cantidadNormalizada = null;
            }

            $sql = 'INSERT INTO donaciones (
                        tipo_donacion,
                        donante,
                        es_anonima,
                        monto,
                        inventario_producto_id,
                        producto,
                        cantidad,
                        unidad_medida,
                        descripcion,
                        fecha_donacion,
                        estado_recepcion,
                        destino,
                        observaciones,
                        creado_por
                    ) VALUES (
                        :tipo_donacion,
                        :donante,
                        :es_anonima,
                        :monto,
                        :inventario_producto_id,
                        :producto,
                        :cantidad,
                        :unidad_medida,
                        :descripcion,
                        :fecha_donacion,
                        :estado_recepcion,
                        :destino,
                        :observaciones,
                        :creado_por
                    )';

            $consulta = $conexion->prepare($sql);

            $consulta->execute([
                'tipo_donacion' => $tipoDonacion,
                'donante' => $donante,
                'es_anonima' => $esAnonima ? 1 : 0,
                'monto' => $monto,
                'inventario_producto_id' => $inventarioProductoId,
                'producto' => $producto,
                'cantidad' => $cantidadNormalizada,
                'unidad_medida' => $unidadMedida,
                'descripcion' => $descripcion,
                'fecha_donacion' => $fechaDonacion,
                'estado_recepcion' => $estadoRecepcion,
                'destino' => $destino,
                'observaciones' => $observaciones,
                'creado_por' => $usuarioId,
            ]);

            $donacionId = (int) $conexion->lastInsertId();

            if (
                $tipoDonacion === 'En especie'
                && $estadoRecepcion === 'Recibida'
                && $inventarioProductoId !== null
                && $cantidadNormalizada !== null
            ) {
                $cantidadCentavos = $this->cantidadACentavos(
                    $cantidadNormalizada
                );

                $this->aplicarMovimientoDonacion(
                    $conexion,
                    $inventarioProductoId,
                    $cantidadCentavos,
                    $donacionId,
                    $usuarioId,
                    'Entrada por donación #' . $donacionId,
                    true
                );
            }

            $conexion->commit();

            return true;
        } catch (Throwable $e) {
            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }

            throw $e;
        }
    }

    /**
     * Obtener todas las donaciones.
     */
    public function obtenerTodas(
        string $busqueda = '',
        string $tipoDonacion = '',
        string $estadoRecepcion = ''
    ): array {
        $sql = 'SELECT
                    d.id,
                    d.tipo_donacion,
                    d.donante,
                    d.es_anonima,
                    d.monto,
                    d.inventario_producto_id,
                    d.producto,
                    d.cantidad,
                    d.unidad_medida,
                    d.descripcion,
                    d.fecha_donacion,
                    d.estado_recepcion,
                    d.destino,
                    d.observaciones,
                    d.creado_por,
                    d.creado_en,
                    d.actualizado_en,
                    u.nombre_completo AS registrado_por
                FROM donaciones d
                LEFT JOIN usuarios u
                    ON u.id = d.creado_por';

        $condiciones = [];
        $parametros = [];

        if ($busqueda !== '') {
            $condiciones[] = '(
                COALESCE(d.donante, \'\') LIKE :busqueda_donante
                OR COALESCE(d.producto, \'\') LIKE :busqueda_producto
                OR COALESCE(d.descripcion, \'\') LIKE :busqueda_descripcion
                OR COALESCE(d.destino, \'\') LIKE :busqueda_destino
                OR d.tipo_donacion LIKE :busqueda_tipo
            )';

            $termino = '%' . $busqueda . '%';

            $parametros['busqueda_donante'] = $termino;
            $parametros['busqueda_producto'] = $termino;
            $parametros['busqueda_descripcion'] = $termino;
            $parametros['busqueda_destino'] = $termino;
            $parametros['busqueda_tipo'] = $termino;
        }

        if ($tipoDonacion !== '') {
            $condiciones[] = 'd.tipo_donacion = :tipo_donacion';
            $parametros['tipo_donacion'] = $tipoDonacion;
        }

        if ($estadoRecepcion !== '') {
            $condiciones[] = 'd.estado_recepcion = :estado_recepcion';
            $parametros['estado_recepcion'] = $estadoRecepcion;
        }

        if ($condiciones !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $condiciones);
        }

        $sql .= ' ORDER BY d.fecha_donacion DESC, d.id DESC';

        $consulta = obtenerConexion()->prepare($sql);
        $consulta->execute($parametros);

        return $consulta->fetchAll();
    }

    /**
     * Obtener una donación por ID.
     */
    public function obtenerPorId(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $sql = 'SELECT
                    d.id,
                    d.tipo_donacion,
                    d.donante,
                    d.es_anonima,
                    d.monto,
                    d.inventario_producto_id,
                    d.producto,
                    d.cantidad,
                    d.unidad_medida,
                    d.descripcion,
                    d.fecha_donacion,
                    d.estado_recepcion,
                    d.destino,
                    d.observaciones,
                    d.creado_por,
                    d.creado_en,
                    d.actualizado_en,
                    u.nombre_completo AS registrado_por
                FROM donaciones d
                LEFT JOIN usuarios u
                    ON u.id = d.creado_por
                WHERE d.id = :id
                LIMIT 1';

        $consulta = obtenerConexion()->prepare($sql);
        $consulta->execute(['id' => $id]);

        $donacion = $consulta->fetch();

        return $donacion ?: null;
    }

    /**
     * Actualizar una donación.
     *
     * Ajusta solamente la diferencia cuando se conserva
     * el mismo producto y la donación continúa recibida.
     */
    public function actualizar(
        int $id,
        string $tipoDonacion,
        ?string $donante,
        bool $esAnonima,
        ?string $monto,
        ?int $inventarioProductoId,
        ?string $cantidad,
        ?string $descripcion,
        string $fechaDonacion,
        string $estadoRecepcion,
        ?string $destino,
        ?string $observaciones,
        ?int $usuarioId = null
    ): bool {
        if ($id <= 0) {
            return false;
        }

        $conexion = obtenerConexion();

        $conexion->beginTransaction();

        try {
            // Bloquear la donación durante la actualización.
            $consultaDonacion = $conexion->prepare(
                'SELECT *
                 FROM donaciones
                 WHERE id = :id
                 LIMIT 1
                 FOR UPDATE'
            );

            $consultaDonacion->execute(['id' => $id]);

            $donacionAnterior = $consultaDonacion->fetch();

            if ($donacionAnterior === false) {
                throw new RuntimeException(
                    'La donación no existe.'
                );
            }

            if ($donacionAnterior['estado_recepcion'] === 'Anulada') {
                throw new RuntimeException(
                    'No se pueden modificar donaciones anuladas.'
                );
            }

            $productoInventario = null;
            $producto = null;
            $unidadMedida = null;
            $cantidadNormalizada = null;

            if ($tipoDonacion === 'En especie') {
                $productoInventario = $this->obtenerProductoBloqueado(
                    $conexion,
                    $inventarioProductoId
                );

                if ($productoInventario['estado'] !== 'Activo') {
                    throw new RuntimeException(
                        'El producto seleccionado está inactivo.'
                    );
                }

                if ($cantidad === null) {
                    throw new InvalidArgumentException(
                        'La cantidad de la donación es obligatoria.'
                    );
                }

                $producto = (string) $productoInventario['nombre'];
                $unidadMedida = (string) $productoInventario['unidad_medida'];

                $cantidadNormalizada = $this->normalizarCantidad(
                    $cantidad,
                    $unidadMedida
                );

                $monto = null;
            } else {
                $inventarioProductoId = null;
                $cantidadNormalizada = null;
                $producto = null;
                $unidadMedida = null;
            }

            $efectosAnteriores = $this->obtenerEfectosNetosDonacion(
                $conexion,
                $id
            );

            $mismoProductoRecibido =
                $tipoDonacion === 'En especie'
                && $estadoRecepcion === 'Recibida'
                && $donacionAnterior['tipo_donacion'] === 'En especie'
                && $donacionAnterior['estado_recepcion'] === 'Recibida'
                && (int) ($donacionAnterior['inventario_producto_id'] ?? 0)
                    === (int) $inventarioProductoId
                && count($efectosAnteriores) <= 1;

            if ($mismoProductoRecibido) {
                $stockRegistrado = 0;

                if (count($efectosAnteriores) === 1) {
                    $efecto = $efectosAnteriores[0];

                    if (
                        (int) $efecto['producto_id']
                        === (int) $inventarioProductoId
                    ) {
                        $stockRegistrado = $this->cantidadConSignoACentavos(
                            (string) $efecto['cantidad_neta']
                        );
                    } else {
                        $mismoProductoRecibido = false;
                    }
                }

                if ($mismoProductoRecibido) {
                    $cantidadNueva = $this->cantidadACentavos(
                        $cantidadNormalizada
                    );

                    $diferencia = $cantidadNueva - $stockRegistrado;

                    if ($diferencia !== 0) {
                        $this->aplicarMovimientoDonacion(
                            $conexion,
                            (int) $inventarioProductoId,
                            $diferencia,
                            $id,
                            $usuarioId,
                            'Ajuste por edición de donación #' . $id,
                            $diferencia > 0
                        );
                    }
                }
            }

            if (!$mismoProductoRecibido) {
                // Revertir los movimientos anteriores de la donación.
                $this->revertirEfectosDonacion(
                    $conexion,
                    $id,
                    $usuarioId
                );

                // Registrar la nueva entrada si la donación fue recibida.
                if (
                    $tipoDonacion === 'En especie'
                    && $estadoRecepcion === 'Recibida'
                ) {
                    $this->aplicarMovimientoDonacion(
                        $conexion,
                        (int) $inventarioProductoId,
                        $this->cantidadACentavos($cantidadNormalizada),
                        $id,
                        $usuarioId,
                        'Entrada por actualización de donación #' . $id,
                        true
                    );
                }
            }

            $sql = 'UPDATE donaciones
                    SET tipo_donacion = :tipo_donacion,
                        donante = :donante,
                        es_anonima = :es_anonima,
                        monto = :monto,
                        inventario_producto_id = :inventario_producto_id,
                        producto = :producto,
                        cantidad = :cantidad,
                        unidad_medida = :unidad_medida,
                        descripcion = :descripcion,
                        fecha_donacion = :fecha_donacion,
                        estado_recepcion = :estado_recepcion,
                        destino = :destino,
                        observaciones = :observaciones
                    WHERE id = :id';

            $consulta = $conexion->prepare($sql);

            $consulta->execute([
                'id' => $id,
                'tipo_donacion' => $tipoDonacion,
                'donante' => $donante,
                'es_anonima' => $esAnonima ? 1 : 0,
                'monto' => $monto,
                'inventario_producto_id' => $inventarioProductoId,
                'producto' => $producto,
                'cantidad' => $cantidadNormalizada,
                'unidad_medida' => $unidadMedida,
                'descripcion' => $descripcion,
                'fecha_donacion' => $fechaDonacion,
                'estado_recepcion' => $estadoRecepcion,
                'destino' => $destino,
                'observaciones' => $observaciones,
            ]);

            $conexion->commit();

            return true;
        } catch (Throwable $e) {
            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }

            throw $e;
        }
    }

    /**
     * Anular una donación conservando su historial.
     *
     * Si la donación en especie ya fue recibida,
     * revierte su efecto neto sobre el inventario.
     */
    public function anular(int $id, ?int $usuarioId = null): bool
    {
        if ($id <= 0) {
            return false;
        }

        $conexion = obtenerConexion();

        $conexion->beginTransaction();

        try {
            $consulta = $conexion->prepare(
                'SELECT id, estado_recepcion
                 FROM donaciones
                 WHERE id = :id
                 LIMIT 1
                 FOR UPDATE'
            );

            $consulta->execute(['id' => $id]);

            $donacion = $consulta->fetch();

            if (
                $donacion === false
                || $donacion['estado_recepcion'] === 'Anulada'
            ) {
                $conexion->rollBack();
                return false;
            }

            $this->revertirEfectosDonacion(
                $conexion,
                $id,
                $usuarioId
            );

            $actualizacion = $conexion->prepare(
                'UPDATE donaciones
                 SET estado_recepcion = :estado
                 WHERE id = :id'
            );

            $actualizacion->execute([
                'estado' => 'Anulada',
                'id' => $id,
            ]);

            $conexion->commit();

            return true;
        } catch (Throwable $e) {
            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }

            throw $e;
        }
    }

    /**
     * Obtener los efectos netos de una donación.
     *
     * Considera entradas y salidas anteriores asociadas
     * a la misma donación.
     */
    private function obtenerEfectosNetosDonacion(
        PDO $conexion,
        int $donacionId
    ): array {
        $sql = "SELECT
                    producto_id,
                    SUM(
                        CASE
                            WHEN tipo_movimiento IN (
                                'Entrada',
                                'Ajuste positivo'
                            )
                            THEN cantidad
                            ELSE -cantidad
                        END
                    ) AS cantidad_neta
                FROM inventario_movimientos
                WHERE referencia_tipo = 'Donacion'
                  AND referencia_id = :donacion_id
                GROUP BY producto_id
                HAVING SUM(
                    CASE
                        WHEN tipo_movimiento IN (
                            'Entrada',
                            'Ajuste positivo'
                        )
                        THEN cantidad
                        ELSE -cantidad
                    END
                ) <> 0
                ORDER BY producto_id ASC";

        $consulta = $conexion->prepare($sql);
        $consulta->execute(['donacion_id' => $donacionId]);

        return $consulta->fetchAll();
    }

    /**
     * Revertir el efecto neto de una donación.
     */
    private function revertirEfectosDonacion(
        PDO $conexion,
        int $donacionId,
        ?int $usuarioId
    ): void {
        $efectos = $this->obtenerEfectosNetosDonacion(
            $conexion,
            $donacionId
        );

        foreach ($efectos as $efecto) {
            $cantidadNeta = $this->cantidadConSignoACentavos(
                (string) $efecto['cantidad_neta']
            );

            if ($cantidadNeta === 0) {
                continue;
            }

            $this->aplicarMovimientoDonacion(
                $conexion,
                (int) $efecto['producto_id'],
                -$cantidadNeta,
                $donacionId,
                $usuarioId,
                'Reversión de donación #' . $donacionId,
                false
            );
        }
    }

    /**
     * Aplicar un movimiento de inventario relacionado
     * con una donación.
     *
     * Una cantidad positiva registra Entrada.
     * Una cantidad negativa registra Salida.
     */
    private function aplicarMovimientoDonacion(
        PDO $conexion,
        int $productoId,
        int $diferenciaCentavos,
        int $donacionId,
        ?int $usuarioId,
        string $observaciones,
        bool $exigirActivo
    ): void {
        if ($diferenciaCentavos === 0) {
            return;
        }

        $producto = $this->obtenerProductoBloqueado(
            $conexion,
            $productoId
        );

        if (
            $exigirActivo
            && $producto['estado'] !== 'Activo'
        ) {
            throw new RuntimeException(
                'El producto del inventario está inactivo.'
            );
        }

        $unidadMedida = (string) $producto['unidad_medida'];

        if (
            $this->esUnidadEntera($unidadMedida)
            && abs($diferenciaCentavos) % 100 !== 0
        ) {
            throw new InvalidArgumentException(
                'Este producto se registra por unidad. La cantidad debe ser un número entero, sin decimales.'
            );
        }

        $stockAnterior = $this->cantidadACentavos(
            (string) $producto['stock_actual']
        );

        $stockNuevo = $stockAnterior + $diferenciaCentavos;

        if ($stockNuevo < 0) {
            throw new RuntimeException(
                'No hay existencias suficientes para modificar o anular esta donación. Parte de los productos ya pudo haber sido entregada.'
            );
        }

        // DECIMAL(12,2): máximo 9,999,999,999.99.
        if ($stockNuevo > 999999999999) {
            throw new RuntimeException(
                'La cantidad supera el límite permitido para el inventario.'
            );
        }

        $tipoMovimiento = $diferenciaCentavos > 0
            ? 'Entrada'
            : 'Salida';

        $cantidadMovimiento = abs($diferenciaCentavos);

        // Actualizar existencias.
        $actualizacion = $conexion->prepare(
            'UPDATE inventario_productos
             SET stock_actual = :stock
             WHERE id = :id'
        );

        $actualizacion->execute([
            'stock' => $this->centavosACantidad($stockNuevo),
            'id' => $productoId,
        ]);

        // Registrar historial del movimiento.
        $sqlMovimiento = 'INSERT INTO inventario_movimientos (
                            producto_id,
                            tipo_movimiento,
                            cantidad,
                            stock_anterior,
                            stock_nuevo,
                            referencia_tipo,
                            referencia_id,
                            reversa_de,
                            observaciones,
                            usuario_id
                          ) VALUES (
                            :producto_id,
                            :tipo_movimiento,
                            :cantidad,
                            :stock_anterior,
                            :stock_nuevo,
                            :referencia_tipo,
                            :referencia_id,
                            NULL,
                            :observaciones,
                            :usuario_id
                          )';

        $movimiento = $conexion->prepare($sqlMovimiento);

        $movimiento->execute([
            'producto_id' => $productoId,
            'tipo_movimiento' => $tipoMovimiento,
            'cantidad' => $this->centavosACantidad(
                $cantidadMovimiento
            ),
            'stock_anterior' => $this->centavosACantidad(
                $stockAnterior
            ),
            'stock_nuevo' => $this->centavosACantidad(
                $stockNuevo
            ),
            'referencia_tipo' => 'Donacion',
            'referencia_id' => $donacionId,
            'observaciones' => $observaciones,
            'usuario_id' => $usuarioId,
        ]);
    }

    /**
     * Obtener un producto bloqueando su fila.
     */
    private function obtenerProductoBloqueado(
        PDO $conexion,
        ?int $productoId
    ): array {
        if ($productoId === null || $productoId <= 0) {
            throw new InvalidArgumentException(
                'Seleccioná un producto válido del inventario.'
            );
        }

        $consulta = $conexion->prepare(
            'SELECT
                id,
                nombre,
                categoria,
                unidad_medida,
                stock_actual,
                estado
             FROM inventario_productos
             WHERE id = :id
             LIMIT 1
             FOR UPDATE'
        );

        $consulta->execute(['id' => $productoId]);

        $producto = $consulta->fetch();

        if ($producto === false) {
            throw new RuntimeException(
                'El producto seleccionado no existe en el inventario.'
            );
        }

        return $producto;
    }

    /**
     * Identificar unidades que no permiten decimales.
     */
    private function esUnidadEntera(string $unidadMedida): bool
    {
        $unidad = mb_strtolower(trim($unidadMedida), 'UTF-8');

        return in_array(
            $unidad,
            ['unidad', 'unidades'],
            true
        );
    }

    /**
     * Normalizar cantidades y validar unidades enteras.
     */
    private function normalizarCantidad(
        string $cantidad,
        string $unidadMedida
    ): string {
        $cantidad = trim(
            str_replace(',', '.', $cantidad)
        );

        if (!preg_match(
            '/^\d{1,10}(?:\.\d{1,2})?$/',
            $cantidad
        )) {
            throw new InvalidArgumentException(
                'Ingresá una cantidad válida mayor que cero, con máximo dos decimales.'
            );
        }

        $centavos = $this->cantidadACentavos($cantidad);

        if ($centavos <= 0) {
            throw new InvalidArgumentException(
                'La cantidad debe ser mayor que cero.'
            );
        }

        if (
            $this->esUnidadEntera($unidadMedida)
            && $centavos % 100 !== 0
        ) {
            throw new InvalidArgumentException(
                'El producto está configurado por Unidad. Ingresá solamente números enteros, sin decimales.'
            );
        }

        return $this->centavosACantidad($centavos);
    }

    /**
     * Convertir cantidad decimal positiva a centavos.
     */
    private function cantidadACentavos(string $cantidad): int
    {
        $cantidad = trim(
            str_replace(',', '.', $cantidad)
        );

        if (!preg_match(
            '/^(\d{1,10})(?:\.(\d{1,2}))?$/',
            $cantidad,
            $coincidencias
        )) {
            throw new InvalidArgumentException(
                'La cantidad ingresada no tiene un formato válido.'
            );
        }

        $enteros = (int) $coincidencias[1];

        $decimales = isset($coincidencias[2])
            ? (int) str_pad($coincidencias[2], 2, '0')
            : 0;

        return ($enteros * 100) + $decimales;
    }

    /**
     * Convertir un valor decimal, incluso negativo,
     * a centavos enteros.
     */
    private function cantidadConSignoACentavos(string $cantidad): int
    {
        $cantidad = trim(
            str_replace(',', '.', $cantidad)
        );

        $negativo = str_starts_with($cantidad, '-');

        if ($negativo) {
            $cantidad = substr($cantidad, 1);
        }

        $centavos = $this->cantidadACentavos($cantidad);

        return $negativo ? -$centavos : $centavos;
    }

    /**
     * Convertir centavos a decimal para MySQL.
     */
    private function centavosACantidad(int $centavos): string
    {
        $enteros = intdiv($centavos, 100);
        $decimales = $centavos % 100;

        return $enteros . '.'
            . str_pad(
                (string) $decimales,
                2,
                '0',
                STR_PAD_LEFT
            );
    }

    /**
     * Anterior resumen de donaciones.
     */
    public function obtenerResumen(): array
    {
        $sql = "SELECT
                    COUNT(*) AS total_donaciones,

                    COALESCE(SUM(
                        CASE WHEN estado_recepcion = 'Recibida'
                        THEN 1 ELSE 0 END
                    ), 0) AS donaciones_recibidas,

                    COALESCE(SUM(
                        CASE WHEN estado_recepcion = 'Pendiente'
                        THEN 1 ELSE 0 END
                    ), 0) AS donaciones_pendientes,

                    COALESCE(SUM(
                        CASE
                            WHEN tipo_donacion = 'Monetaria'
                             AND estado_recepcion = 'Recibida'
                            THEN monto ELSE 0
                        END
                    ), 0) AS total_monetario,

                    COALESCE(SUM(
                        CASE
                            WHEN tipo_donacion = 'En especie'
                             AND estado_recepcion = 'Recibida'
                            THEN 1 ELSE 0
                        END
                    ), 0) AS donaciones_en_especie,

                    COALESCE(SUM(
                        CASE
                            WHEN tipo_donacion = 'En especie'
                             AND estado_recepcion = 'Recibida'
                            THEN cantidad ELSE 0
                        END
                    ), 0) AS total_unidades_especie

                FROM donaciones";

        $consulta = obtenerConexion()->query($sql);

        return $consulta->fetch() ?: [
            'total_donaciones' => 0,
            'donaciones_recibidas' => 0,
            'donaciones_pendientes' => 0,
            'total_monetario' => 0,
            'donaciones_en_especie' => 0,
            'total_unidades_especie' => 0,
        ];
    }
}
