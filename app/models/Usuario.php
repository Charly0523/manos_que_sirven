<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';

final class Usuario
{
    public function buscarPorUsuario(string $usuario): ?array
    {
        $sql = '
            SELECT u.id, u.nombre_completo, u.usuario, u.password_hash,
                   u.rol_id, u.activo, r.nombre AS rol
            FROM usuarios u
            INNER JOIN roles r ON r.id = u.rol_id
            WHERE u.usuario = :usuario
            LIMIT 1
        ';

        $consulta = obtenerConexion()->prepare($sql);
        $consulta->execute(['usuario' => $usuario]);
        return $consulta->fetch() ?: null;
    }

    public function actualizarUltimoAcceso(int $id): void
    {
        $consulta = obtenerConexion()->prepare(
            'UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = :id'
        );
        $consulta->execute(['id' => $id]);
    }

    public function listar(): array
    {
        $sql = '
            SELECT u.id, u.nombre_completo, u.usuario, u.correo,
                   u.rol_id, r.nombre AS rol, u.activo,
                   u.ultimo_acceso, u.creado_en
            FROM usuarios u
            INNER JOIN roles r ON r.id = u.rol_id
            ORDER BY u.nombre_completo ASC, u.id ASC
        ';

        return obtenerConexion()->query($sql)->fetchAll();
    }

    public function listarRoles(): array
    {
        return obtenerConexion()
            ->query('SELECT id, nombre FROM roles ORDER BY id ASC')
            ->fetchAll();
    }

    public function obtenerPorId(int $id): ?array
    {
        $consulta = obtenerConexion()->prepare('
            SELECT id, nombre_completo, usuario, correo, rol_id, activo
            FROM usuarios WHERE id = :id LIMIT 1
        ');
        $consulta->execute(['id' => $id]);
        return $consulta->fetch() ?: null;
    }

    public function crear(
        string $nombre,
        string $usuario,
        ?string $correo,
        string $contrasena,
        int $rolId
    ): int {
        $hash = password_hash($contrasena, PASSWORD_DEFAULT);
        $consulta = obtenerConexion()->prepare('
            INSERT INTO usuarios
                (nombre_completo, usuario, correo, password_hash, rol_id, activo)
            VALUES
                (:nombre, :usuario, :correo, :hash, :rol_id, 1)
        ');
        $consulta->execute([
            'nombre' => $nombre,
            'usuario' => $usuario,
            'correo' => $correo,
            'hash' => $hash,
            'rol_id' => $rolId,
        ]);
        return (int) obtenerConexion()->lastInsertId();
    }

    public function actualizarDatos(
        int $id,
        string $nombre,
        string $usuario,
        ?string $correo,
        int $rolId,
        int $operadorId
    ): void {
        $db = obtenerConexion();
        $db->beginTransaction();
        try {
            $this->validarCambioAdministrador($db, $id, $rolId, null, $operadorId);
            $consulta = $db->prepare('
                UPDATE usuarios
                SET nombre_completo = :nombre, usuario = :usuario,
                    correo = :correo, rol_id = :rol_id
                WHERE id = :id
            ');
            $consulta->execute([
                'nombre' => $nombre,
                'usuario' => $usuario,
                'correo' => $correo,
                'rol_id' => $rolId,
                'id' => $id,
            ]);
            $db->commit();
        } catch (Throwable $error) {
            $db->rollBack();
            throw $error;
        }
    }

    public function cambiarEstado(int $id, bool $activo, int $operadorId): void
    {
        $db = obtenerConexion();
        $db->beginTransaction();
        try {
            $this->validarCambioAdministrador($db, $id, null, $activo, $operadorId);
            $consulta = $db->prepare(
                'UPDATE usuarios SET activo = :activo WHERE id = :id'
            );
            $consulta->bindValue(':activo', $activo ? 1 : 0, PDO::PARAM_INT);
            $consulta->bindValue(':id', $id, PDO::PARAM_INT);
            $consulta->execute();
            $db->commit();
        } catch (Throwable $error) {
            $db->rollBack();
            throw $error;
        }
    }

    public function cambiarContrasena(int $id, string $contrasena): void
    {
        $consulta = obtenerConexion()->prepare('
            UPDATE usuarios SET password_hash = :hash WHERE id = :id
        ');
        $consulta->execute([
            'hash' => password_hash($contrasena, PASSWORD_DEFAULT),
            'id' => $id,
        ]);
    }

    private function validarCambioAdministrador(
        PDO $db,
        int $id,
        ?int $nuevoRolId,
        ?bool $nuevoEstado,
        int $operadorId
    ): void {
        // Serializa los cambios administrativos para proteger la última cuenta activa.
        $consultaRol = $db->query(
            "SELECT id FROM roles WHERE nombre = 'Administrador' LIMIT 1 FOR UPDATE"
        );
        $idAdministrador = (int) $consultaRol->fetchColumn();
        if ($idAdministrador <= 0) {
            throw new RuntimeException('No existe el rol Administrador.');
        }

        $consulta = $db->prepare(
            'SELECT rol_id, activo FROM usuarios WHERE id = :id FOR UPDATE'
        );
        $consulta->execute(['id' => $id]);
        $actual = $consulta->fetch();
        if (!$actual) {
            throw new RuntimeException('El usuario seleccionado no existe.');
        }

        $rolFinal = $nuevoRolId ?? (int) $actual['rol_id'];
        $estadoFinal = $nuevoEstado ?? ((int) $actual['activo'] === 1);

        if ($id === $operadorId && ($rolFinal !== $idAdministrador || !$estadoFinal)) {
            throw new RuntimeException('No puedes quitarte tu propio acceso de administrador.');
        }

        $eraAdministradorActivo = (int) $actual['rol_id'] === $idAdministrador
            && (int) $actual['activo'] === 1;
        $seraAdministradorActivo = $rolFinal === $idAdministrador && $estadoFinal;
        if ($eraAdministradorActivo && !$seraAdministradorActivo) {
            $cuenta = $db->prepare(
                'SELECT COUNT(*) FROM usuarios WHERE rol_id = :rol_id AND activo = 1'
            );
            $cuenta->execute(['rol_id' => $idAdministrador]);
            if ((int) $cuenta->fetchColumn() <= 1) {
                throw new RuntimeException('Debe permanecer al menos un administrador activo.');
            }
        }
    }
}
