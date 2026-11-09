<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';

final class Beneficiario
{
    /**
     * Obtener todos los beneficiarios.
     */
    public function obtenerTodos(): array
    {
        $sql = 'SELECT
                    b.id,
                    b.nombre_completo,
                    b.telefono,
                    b.direccion,
                    b.comunidad,
                    b.estado,
                    b.creado_en,
                    u.nombre_completo AS registrado_por
                FROM beneficiarios b
                LEFT JOIN usuarios u ON u.id = b.creado_por
                ORDER BY b.creado_en DESC, b.id DESC';

        $consulta = obtenerConexion()->query($sql);

        return $consulta->fetchAll();
    }

    /**
     * Buscar beneficiarios por nombre, teléfono o comunidad.
     */
    public function buscar(string $termino): array
    {
        $sql = 'SELECT
                    b.id,
                    b.nombre_completo,
                    b.telefono,
                    b.direccion,
                    b.comunidad,
                    b.estado,
                    b.creado_en,
                    u.nombre_completo AS registrado_por
                FROM beneficiarios b
                LEFT JOIN usuarios u ON u.id = b.creado_por
                WHERE b.nombre_completo LIKE :nombre
                   OR b.telefono LIKE :telefono
                   OR b.comunidad LIKE :comunidad
                ORDER BY b.nombre_completo ASC';

        $consulta = obtenerConexion()->prepare($sql);

        $busqueda = '%' . $termino . '%';

        $consulta->execute([
            'nombre' => $busqueda,
            'telefono' => $busqueda,
            'comunidad' => $busqueda,
        ]);

        return $consulta->fetchAll();
    }

    /**
     * Registrar un beneficiario nuevo.
     */
    public function crear(
        string $nombreCompleto,
        ?string $telefono,
        ?string $direccion,
        ?string $comunidad,
        int $usuarioId
    ): bool {
        $sql = 'INSERT INTO beneficiarios (
                    nombre_completo,
                    telefono,
                    direccion,
                    comunidad,
                    creado_por
                ) VALUES (
                    :nombre_completo,
                    :telefono,
                    :direccion,
                    :comunidad,
                    :creado_por
                )';

        $consulta = obtenerConexion()->prepare($sql);

        return $consulta->execute([
            'nombre_completo' => $nombreCompleto,
            'telefono' => $telefono,
            'direccion' => $direccion,
            'comunidad' => $comunidad,
            'creado_por' => $usuarioId,
        ]);
    }

    /**
     * Obtener un beneficiario por su ID.
     */
    public function obtenerPorId(int $id): ?array
    {
        $sql = 'SELECT
                    id,
                    nombre_completo,
                    telefono,
                    direccion,
                    comunidad,
                    estado
                FROM beneficiarios
                WHERE id = :id
                LIMIT 1';

        $consulta = obtenerConexion()->prepare($sql);
        $consulta->execute(['id' => $id]);

        $beneficiario = $consulta->fetch();

        return $beneficiario ?: null;
    }

    /**
     * Actualizar los datos de un beneficiario.
     */
    public function actualizar(
        int $id,
        string $nombreCompleto,
        ?string $telefono,
        ?string $direccion,
        ?string $comunidad
    ): bool {
        $sql = 'UPDATE beneficiarios
                SET nombre_completo = :nombre_completo,
                    telefono = :telefono,
                    direccion = :direccion,
                    comunidad = :comunidad
                WHERE id = :id';

        $consulta = obtenerConexion()->prepare($sql);

        return $consulta->execute([
            'id' => $id,
            'nombre_completo' => $nombreCompleto,
            'telefono' => $telefono,
            'direccion' => $direccion,
            'comunidad' => $comunidad,
        ]);
    }

    /**
     * Cambiar el estado sin eliminar el registro.
     */
    public function cambiarEstado(int $id, string $estado): bool
    {
        if (!in_array($estado, ['Activo', 'Inactivo'], true)) {
            return false;
        }

        $sql = 'UPDATE beneficiarios
                SET estado = :estado
                WHERE id = :id';

        $consulta = obtenerConexion()->prepare($sql);

        return $consulta->execute([
            'id' => $id,
            'estado' => $estado,
        ]);
    }

    /**
     * Contar beneficiarios por estado.
     */
    public function contarPorEstado(string $estado): int
    {
        if (!in_array($estado, ['Activo', 'Inactivo'], true)) {
            return 0;
        }

        $sql = 'SELECT COUNT(*)
                FROM beneficiarios
                WHERE estado = :estado';

        $consulta = obtenerConexion()->prepare($sql);
        $consulta->execute(['estado' => $estado]);

        return (int) $consulta->fetchColumn();
    }
}
