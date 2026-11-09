<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';

final class Inventario
{
    /**
     * Obtener todos los productos.
     */
    public function obtenerProductos(
        string $busqueda = '',
        string $categoria = '',
        string $estado = ''
    ): array {
        $sql = 'SELECT
                    id,
                    nombre,
                    categoria,
                    unidad_medida,
                    stock_actual,
                    stock_minimo,
                    descripcion,
                    estado,
                    creado_en,
                    actualizado_en,
                    CASE
                        WHEN stock_actual <= stock_minimo THEN 1
                        ELSE 0
                    END AS stock_bajo
                FROM inventario_productos';

        $condiciones = [];
        $parametros = [];

        if ($busqueda !== '') {
            $condiciones[] = '(
                nombre LIKE :nombre
                OR categoria LIKE :categoria
                OR descripcion LIKE :descripcion
            )';

            $termino = '%' . $busqueda . '%';

            $parametros['nombre'] = $termino;
            $parametros['categoria'] = $termino;
            $parametros['descripcion'] = $termino;
        }

        if ($categoria !== '') {
            $condiciones[] = 'categoria = :categoria_filtro';
            $parametros['categoria_filtro'] = $categoria;
        }

        if ($estado !== '') {
            $condiciones[] = 'estado = :estado';
            $parametros['estado'] = $estado;
        }

        if ($condiciones !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $condiciones);
        }

        $sql .= ' ORDER BY nombre ASC';

        $consulta = obtenerConexion()->prepare($sql);
        $consulta->execute($parametros);

        return $consulta->fetchAll();
    }

    /**
     * Obtener un producto por su identificador.
     */
    public function obtenerProductoPorId(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $sql = 'SELECT *
                FROM inventario_productos
                WHERE id = :id
                LIMIT 1';

        $consulta = obtenerConexion()->prepare($sql);
        $consulta->execute(['id' => $id]);

        $producto = $consulta->fetch();

        return $producto ?: null;
    }

    /**
     * Obtener las categorías existentes.
     */
    public function obtenerCategorias(): array
    {
        $sql = 'SELECT DISTINCT categoria
                FROM inventario_productos
                ORDER BY categoria ASC';

        return obtenerConexion()->query($sql)->fetchAll();
    }

    /**
     * Registrar un producto nuevo.
     *
     * Todos los productos comienzan con existencia cero.
     * Las existencias iniciales se ingresan mediante
     * un movimiento de inventario.
     */
    public function crearProducto(
        string $nombre,
        string $categoria,
        string $unidadMedida,
        string $stockMinimo,
        ?string $descripcion,
        ?int $usuarioId
    ): int {
        $nombre = trim($nombre);
        $categoria = trim($categoria);
        $unidadMedida = trim($unidadMedida);

        if (
            $nombre === ''
            || mb_strlen($nombre) > 150
            || $categoria === ''
            || mb_strlen($categoria) > 100
            || $unidadMedida === ''
            || mb_strlen($unidadMedida) > 30
        ) {
            throw new InvalidArgumentException(
                'Los datos del producto no son válidos.'
            );
        }

        $stockMinimo = $this->normalizarCantidad(
            $stockMinimo,
            true
        );

        $sql = 'INSERT INTO inventario_productos (
                    nombre,
                    categoria,
                    unidad_medida,
                    stock_actual,
                    stock_minimo,
                    descripcion,
                    estado,
                    creado_por
                ) VALUES (
                    :nombre,
                    :categoria,
                    :unidad_medida,
                    0.00,
                    :stock_minimo,
                    :descripcion,
                    :estado,
                    :creado_por
                )';

        $consulta = obtenerConexion()->prepare($sql);

        $consulta->execute([
            'nombre' => $nombre,
            'categoria' => $categoria,
            'unidad_medida' => $unidadMedida,
            'stock_minimo' => $stockMinimo,
            'descripcion' => $descripcion,
            'estado' => 'Activo',
            'creado_por' => $usuarioId,
        ]);

        return (int) obtenerConexion()->lastInsertId();
    }

    /**
     * Actualizar información de un producto.
     * No modifica las existencias.
     */
    public function actualizarProducto(
        int $id,
        string $nombre,
        string $categoria,
        string $unidadMedida,
        string $stockMinimo,
        ?string $descripcion
    ): bool {
        if ($id <= 0) {
            return false;
        }

        $nombre = trim($nombre);
        $categoria = trim($categoria);
        $unidadMedida = trim($unidadMedida);

        if (
            $nombre === ''
            || mb_strlen($nombre) > 150
            || $categoria === ''
            || mb_strlen($categoria) > 100
            || $unidadMedida === ''
            || mb_strlen($unidadMedida) > 30
        ) {
            throw new InvalidArgumentException(
                'Los datos del producto no son válidos.'
            );
        }

        $stockMinimo = $this->normalizarCantidad(
            $stockMinimo,
            true
        );

        $sql = 'UPDATE inventario_productos
                SET nombre = :nombre,
                    categoria = :categoria,
                    unidad_medida = :unidad_medida,
                    stock_minimo = :stock_minimo,
                    descripcion = :descripcion
                WHERE id = :id';

        $consulta = obtenerConexion()->prepare($sql);

        $consulta->execute([
            'id' => $id,
            'nombre' => $nombre,
            'categoria' => $categoria,
            'unidad_medida' => $unidadMedida,
            'stock_minimo' => $stockMinimo,
            'descripcion' => $descripcion,
        ]);

        return $consulta->rowCount() > 0;
    }

    /**
     * Cambiar el estado de un producto.
     */
    public function cambiarEstadoProducto(
        int $id,
        string $estado
    ): bool {
        if (
            $id <= 0
            || !in_array($estado, ['Activo', 'Inactivo'], true)
        ) {
            return false;
        }

        $sql = 'UPDATE inventario_productos
                SET estado = :estado
                WHERE id = :id';

        $consulta = obtenerConexion()->prepare($sql);

        $consulta->execute([
            'id' => $id,
            'estado' => $estado,
        ]);

        return $consulta->rowCount() > 0;
    }

    /**
     * Registrar un ajuste manual de existencias.
     *
     * El movimiento y la actualización del stock
     * se realizan dentro de una transacción.
     */
    public function registrarAjusteManual(
        int $productoId,
        string $tipoMovimiento,
        string $cantidad,
        string $observaciones,
        ?int $usuarioId
    ): bool {
        if ($productoId <= 0) {
            throw new InvalidArgumentException(
                'El producto seleccionado no es válido.'
            );
        }

        if (!in_array(
            $tipoMovimiento,
            ['Ajuste positivo', 'Ajuste negativo'],
            true
        )) {
            throw new InvalidArgumentException(
                'El tipo de ajuste no es válido.'
            );
        }

        $cantidad = $this->normalizarCantidad($cantidad);

        $centavos = $this->cantidadACentavos($cantidad);

        if ($centavos <= 0) {
            throw new InvalidArgumentException(
                'La cantidad debe ser mayor que cero.'
            );
        }

        $observaciones = trim($observaciones);

        if (
            $observaciones === ''
            || mb_strlen($observaciones) > 5000
        ) {
            throw new InvalidArgumentException(
                'Debés indicar el motivo del ajuste.'
            );
        }

        $conexion = obtenerConexion();

        if ($conexion->inTransaction()) {
            throw new RuntimeException(
                'No se puede iniciar el ajuste porque ya existe una transacción activa.'
            );
        }

        $conexion->beginTransaction();

        try {
            // Bloquear el producto mientras se realiza el ajuste.
            $consulta = $conexion->prepare(
                'SELECT id, stock_actual, estado
                 FROM inventario_productos
                 WHERE id = :id
                 LIMIT 1
                 FOR UPDATE'
            );

            $consulta->execute(['id' => $productoId]);

            $producto = $consulta->fetch();

            if ($producto === false) {
                throw new RuntimeException(
                    'El producto no existe.'
                );
            }

            if ($producto['estado'] !== 'Activo') {
                throw new RuntimeException(
                    'No se pueden modificar las existencias de un producto inactivo.'
                );
            }

            $stockAnterior = $this->cantidadACentavos(
                (string) $producto['stock_actual']
            );

            if ($tipoMovimiento === 'Ajuste positivo') {
                $stockNuevo = $stockAnterior + $centavos;
            } else {
                $stockNuevo = $stockAnterior - $centavos;

                if ($stockNuevo < 0) {
                    throw new RuntimeException(
                        'El ajuste supera las existencias disponibles.'
                    );
                }
            }

            // Registrar el movimiento.
            $sqlMovimiento = 'INSERT INTO inventario_movimientos (
                                producto_id,
                                tipo_movimiento,
                                cantidad,
                                stock_anterior,
                                stock_nuevo,
                                referencia_tipo,
                                referencia_id,
                                observaciones,
                                usuario_id
                              ) VALUES (
                                :producto_id,
                                :tipo_movimiento,
                                :cantidad,
                                :stock_anterior,
                                :stock_nuevo,
                                :referencia_tipo,
                                NULL,
                                :observaciones,
                                :usuario_id
                              )';

            $movimiento = $conexion->prepare($sqlMovimiento);

            $movimiento->execute([
                'producto_id' => $productoId,
                'tipo_movimiento' => $tipoMovimiento,
                'cantidad' => $cantidad,
                'stock_anterior' => $this->centavosACantidad(
                    $stockAnterior
                ),
                'stock_nuevo' => $this->centavosACantidad(
                    $stockNuevo
                ),
                'referencia_tipo' => 'Manual',
                'observaciones' => $observaciones,
                'usuario_id' => $usuarioId,
            ]);

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
     * Consultar historial de movimientos.
     */
    public function obtenerMovimientos(
        string $busqueda = '',
        string $tipoMovimiento = '',
        int $productoId = 0,
        int $limite = 100
    ): array {
        $sql = 'SELECT
                    m.id,
                    m.producto_id,
                    p.nombre AS producto,
                    p.unidad_medida,
                    m.tipo_movimiento,
                    m.cantidad,
                    m.stock_anterior,
                    m.stock_nuevo,
                    m.referencia_tipo,
                    m.referencia_id,
                    m.reversa_de,
                    m.observaciones,
                    m.creado_en,
                    u.nombre_completo AS registrado_por
                FROM inventario_movimientos m
                INNER JOIN inventario_productos p
                    ON p.id = m.producto_id
                LEFT JOIN usuarios u
                    ON u.id = m.usuario_id';

        $condiciones = [];
        $parametros = [];

        if ($busqueda !== '') {
            $condiciones[] = '(
                p.nombre LIKE :nombre
                OR m.observaciones LIKE :observaciones
            )';

            $termino = '%' . $busqueda . '%';

            $parametros['nombre'] = $termino;
            $parametros['observaciones'] = $termino;
        }

        if ($tipoMovimiento !== '') {
            $condiciones[] = 'm.tipo_movimiento = :tipo';
            $parametros['tipo'] = $tipoMovimiento;
        }

        if ($productoId > 0) {
            $condiciones[] = 'm.producto_id = :producto_id';
            $parametros['producto_id'] = $productoId;
        }

        if ($condiciones !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $condiciones);
        }

        $limite = max(1, min($limite, 500));

        $sql .= ' ORDER BY m.creado_en DESC, m.id DESC';
        $sql .= ' LIMIT ' . $limite;

        $consulta = obtenerConexion()->prepare($sql);
        $consulta->execute($parametros);

        return $consulta->fetchAll();
    }

    /**
     * Obtener estadísticas del inventario.
     */
    public function obtenerResumen(): array
    {
        $sql = "SELECT
                    COUNT(*) AS total_productos,

                    COALESCE(SUM(
                        CASE
                            WHEN stock_actual > 0
                             AND stock_actual <= stock_minimo
                            THEN 1 ELSE 0
                        END
                    ), 0) AS productos_stock_bajo,

                    COALESCE(SUM(
                        CASE
                            WHEN stock_actual = 0
                            THEN 1 ELSE 0
                        END
                    ), 0) AS productos_agotados,

                    COALESCE(SUM(
                        CASE
                            WHEN estado = 'Activo'
                            THEN 1 ELSE 0
                        END
                    ), 0) AS productos_activos

                FROM inventario_productos";

        $consulta = obtenerConexion()->query($sql);

        return $consulta->fetch() ?: [
            'total_productos' => 0,
            'productos_stock_bajo' => 0,
            'productos_agotados' => 0,
            'productos_activos' => 0,
        ];
    }

    /**
     * Validar y normalizar cantidades decimales.
     */
    private function normalizarCantidad(
        string $cantidad,
        bool $permitirCero = false
    ): string {
        $cantidad = trim(str_replace(',', '.', $cantidad));

        if (!preg_match(
            '/^\d{1,10}(?:\.\d{1,2})?$/',
            $cantidad
        )) {
            throw new InvalidArgumentException(
                'La cantidad ingresada no tiene un formato válido.'
            );
        }

        $valor = $this->cantidadACentavos($cantidad);

        if (!$permitirCero && $valor <= 0) {
            throw new InvalidArgumentException(
                'La cantidad debe ser mayor que cero.'
            );
        }

        return $this->centavosACantidad($valor);
    }

    /**
     * Convertir una cantidad decimal a centavos enteros.
     * Evita errores de precisión al realizar operaciones.
     */
    private function cantidadACentavos(string $cantidad): int
    {
        $cantidad = trim($cantidad);

        if (!preg_match(
            '/^(\d{1,10})(?:\.(\d{1,2}))?$/',
            $cantidad,
            $coincidencias
        )) {
            throw new InvalidArgumentException(
                'La cantidad almacenada no tiene un formato válido.'
            );
        }

        $enteros = (int) $coincidencias[1];

        $decimales = isset($coincidencias[2])
            ? (int) str_pad($coincidencias[2], 2, '0')
            : 0;

        return ($enteros * 100) + $decimales;
    }

    /**
     * Convertir centavos enteros a decimal para MySQL.
     */
    private function centavosACantidad(int $centavos): string
    {
        $enteros = intdiv($centavos, 100);
        $decimales = $centavos % 100;

        return $enteros . '.'
            . str_pad((string) $decimales, 2, '0', STR_PAD_LEFT);
    }
}
