<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';

final class Usuario
{
    /**
     * Busca un usuario por su nombre de usuario.
     */
    public function buscarPorUsuario(string $usuario): ?array
    {
        $sql = '
            SELECT
                u.id,
                u.nombre_completo,
                u.usuario,
                u.password_hash,
                u.rol_id,
                u.activo,
                r.nombre AS rol
            FROM usuarios AS u
            INNER JOIN roles AS r
                ON r.id = u.rol_id
            WHERE u.usuario = :usuario
            LIMIT 1
        ';

        $consulta = obtenerConexion()->prepare($sql);

        $consulta->execute([
            'usuario' => $usuario,
        ]);

        $resultado = $consulta->fetch();

        return $resultado ?: null;
    }

    /**
     * Actualiza la fecha del último acceso del usuario.
     */
    public function actualizarUltimoAcceso(int $id): void
    {
        $sql = '
            UPDATE usuarios
            SET ultimo_acceso = NOW()
            WHERE id = :id
        ';

        $consulta = obtenerConexion()->prepare($sql);

        $consulta->execute([
            'id' => $id,
        ]);
    }
}
