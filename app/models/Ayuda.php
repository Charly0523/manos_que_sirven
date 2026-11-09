<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';

final class Ayuda
{
    public function obtenerBeneficiariosActivos(): array
    {
        $sql = 'SELECT id, nombre_completo, comunidad
                FROM beneficiarios
                WHERE estado = :estado
                ORDER BY nombre_completo ASC';

        $consulta = obtenerConexion()->prepare($sql);
        $consulta->execute(['estado' => 'Activo']);

        return $consulta->fetchAll();
    }

    public function crear(
        int $beneficiarioId,
        string $tipoAyuda,
        string $descripcion,
        int $cantidad,
        string $fechaEntrega,
        ?string $observaciones,
        ?int $usuarioId
    ): bool {
        $sql = 'INSERT INTO ayudas (
                    beneficiario_id,
                    tipo_ayuda,
                    descripcion,
                    cantidad,
                    fecha_entrega,
                    observaciones,
                    creado_por
                ) VALUES (
                    :beneficiario_id,
                    :tipo_ayuda,
                    :descripcion,
                    :cantidad,
                    :fecha_entrega,
                    :observaciones,
                    :creado_por
                )';

        $consulta = obtenerConexion()->prepare($sql);

        return $consulta->execute([
            'beneficiario_id' => $beneficiarioId,
            'tipo_ayuda' => $tipoAyuda,
            'descripcion' => $descripcion,
            'cantidad' => $cantidad,
            'fecha_entrega' => $fechaEntrega,
            'observaciones' => $observaciones,
            'creado_por' => $usuarioId,
        ]);
    }

    public function obtenerTodas(string $busqueda = ''): array
    {
        $sql = 'SELECT
                    a.id,
                    a.beneficiario_id,
                    b.nombre_completo AS beneficiario,
                    b.comunidad,
                    a.tipo_ayuda,
                    a.descripcion,
                    a.cantidad,
                    a.fecha_entrega,
                    a.observaciones,
                    a.creado_en,
                    u.nombre_completo AS registrado_por
                FROM ayudas a
                INNER JOIN beneficiarios b
                    ON b.id = a.beneficiario_id
                LEFT JOIN usuarios u
                    ON u.id = a.creado_por';

        if ($busqueda !== '') {
            $sql .= ' WHERE b.nombre_completo LIKE :nombre
                      OR b.comunidad LIKE :comunidad
                      OR a.tipo_ayuda LIKE :tipo
                      OR a.descripcion LIKE :descripcion';
        }

        $sql .= ' ORDER BY a.fecha_entrega DESC, a.id DESC';

        $consulta = obtenerConexion()->prepare($sql);

        if ($busqueda !== '') {
            $termino = '%' . $busqueda . '%';

            $consulta->execute([
                'nombre' => $termino,
                'comunidad' => $termino,
                'tipo' => $termino,
                'descripcion' => $termino,
            ]);
        } else {
            $consulta->execute();
        }

        return $consulta->fetchAll();
    }

    public function obtenerPorId(int $id): ?array
    {
        $sql = 'SELECT
                    a.id,
                    a.beneficiario_id,
                    b.nombre_completo AS beneficiario,
                    b.comunidad,
                    b.estado AS estado_beneficiario,
                    a.tipo_ayuda,
                    a.descripcion,
                    a.cantidad,
                    a.fecha_entrega,
                    a.observaciones,
                    a.creado_por,
                    a.creado_en
                FROM ayudas a
                INNER JOIN beneficiarios b
                    ON b.id = a.beneficiario_id
                WHERE a.id = :id
                LIMIT 1';

        $consulta = obtenerConexion()->prepare($sql);
        $consulta->execute(['id' => $id]);

        $ayuda = $consulta->fetch();

        return $ayuda ?: null;
    }

    public function actualizar(
        int $id,
        int $beneficiarioId,
        string $tipoAyuda,
        string $descripcion,
        int $cantidad,
        string $fechaEntrega,
        ?string $observaciones
    ): bool {
        $sql = 'UPDATE ayudas
                SET beneficiario_id = :beneficiario_id,
                    tipo_ayuda = :tipo_ayuda,
                    descripcion = :descripcion,
                    cantidad = :cantidad,
                    fecha_entrega = :fecha_entrega,
                    observaciones = :observaciones
                WHERE id = :id';

        $consulta = obtenerConexion()->prepare($sql);

        return $consulta->execute([
            'id' => $id,
            'beneficiario_id' => $beneficiarioId,
            'tipo_ayuda' => $tipoAyuda,
            'descripcion' => $descripcion,
            'cantidad' => $cantidad,
            'fecha_entrega' => $fechaEntrega,
            'observaciones' => $observaciones,
        ]);
    }

    public function eliminar(int $id): bool
    {
        if ($id <= 0) {
            return false;
        }

        $sql = 'DELETE FROM ayudas WHERE id = :id';

        $consulta = obtenerConexion()->prepare($sql);
        $consulta->execute(['id' => $id]);

        return $consulta->rowCount() > 0;
    }

    public function obtenerResumen(): array
    {
        $sql = 'SELECT
                    COUNT(*) AS total_ayudas,
                    COALESCE(SUM(cantidad), 0) AS total_unidades,
                    COUNT(DISTINCT beneficiario_id) AS beneficiarios_atendidos
                FROM ayudas';

        $consulta = obtenerConexion()->query($sql);

        return $consulta->fetch() ?: [
            'total_ayudas' => 0,
            'total_unidades' => 0,
            'beneficiarios_atendidos' => 0,
        ];
    }
}
