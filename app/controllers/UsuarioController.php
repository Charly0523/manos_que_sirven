<?php
declare(strict_types=1);

require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';

final class UsuarioController
{
    private Usuario $modelo;

    public function __construct()
    {
        AuthMiddleware::requiereRol(['Administrador']);
        $this->modelo = new Usuario();

        // Vuelve a comprobar el acceso en la BD por si el rol cambió durante la sesión.
        $usuarioId = (int) ($_SESSION['usuario_id'] ?? 0);
        $usuario = $usuarioId > 0 ? $this->modelo->obtenerPorId($usuarioId) : null;
        $esAdministrador = false;

        foreach ($this->modelo->listarRoles() as $rol) {
            if (
                $usuario !== null
                && $rol['nombre'] === 'Administrador'
                && (int) $rol['id'] === (int) $usuario['rol_id']
            ) {
                $esAdministrador = true;
                break;
            }
        }

        if ($usuario === null || (int) $usuario['activo'] !== 1 || !$esAdministrador) {
            http_response_code(403);
            exit('Acceso denegado. Tu cuenta no tiene permisos de administrador.');
        }
    }

    public function listar(): array
    {
        return $this->modelo->listar();
    }

    public function listarRoles(): array
    {
        return $this->modelo->listarRoles();
    }

    public function obtenerPorId(int $id): ?array
    {
        return $id > 0 ? $this->modelo->obtenerPorId($id) : null;
    }

    public function registrar(array $datos): array
    {
        try {
            $campos = $this->validarDatos($datos);
            $password = $this->validarPassword($datos);

            $this->modelo->crear(
                $campos['nombre'],
                $campos['usuario'],
                $campos['correo'],
                $password,
                $campos['rol_id']
            );

            return $this->respuesta(true, 'Usuario creado correctamente.');
        } catch (Throwable $error) {
            return $this->manejarError($error);
        }
    }

    public function actualizar(int $id, array $datos): array
    {
        try {
            if ($id <= 0 || $this->modelo->obtenerPorId($id) === null) {
                throw new InvalidArgumentException('El usuario seleccionado no existe.');
            }

            $campos = $this->validarDatos($datos);
            $operadorId = (int) $_SESSION['usuario_id'];

            $this->modelo->actualizarDatos(
                $id,
                $campos['nombre'],
                $campos['usuario'],
                $campos['correo'],
                $campos['rol_id'],
                $operadorId
            );

            if ($id === $operadorId) {
                $_SESSION['nombre_usuario'] = $campos['nombre'];
            }

            return $this->respuesta(true, 'Usuario actualizado correctamente.');
        } catch (Throwable $error) {
            return $this->manejarError($error);
        }
    }

    public function cambiarEstado(int $id, string $estado): array
    {
        try {
            if ($id <= 0 || !in_array($estado, ['0', '1'], true)) {
                throw new InvalidArgumentException('Los datos para cambiar el estado no son válidos.');
            }

            $this->modelo->cambiarEstado(
                $id,
                $estado === '1',
                (int) $_SESSION['usuario_id']
            );

            return $this->respuesta(true, 'Estado del usuario actualizado correctamente.');
        } catch (Throwable $error) {
            return $this->manejarError($error);
        }
    }

    public function cambiarContrasena(int $id, array $datos): array
    {
        try {
            if ($id <= 0 || $this->modelo->obtenerPorId($id) === null) {
                throw new InvalidArgumentException('El usuario seleccionado no existe.');
            }

            $password = $this->validarPassword($datos);
            $this->modelo->cambiarContrasena($id, $password);

            return $this->respuesta(true, 'Contraseña actualizada correctamente.');
        } catch (Throwable $error) {
            return $this->manejarError($error);
        }
    }

    private function validarDatos(array $datos): array
    {
        $nombre = trim((string) ($datos['nombre_completo'] ?? ''));
        $usuario = trim((string) ($datos['usuario'] ?? ''));
        $correo = trim((string) ($datos['correo'] ?? ''));
        $rolId = filter_var($datos['rol_id'] ?? null, FILTER_VALIDATE_INT);

        if (mb_strlen($nombre) < 3 || mb_strlen($nombre) > 150) {
            throw new InvalidArgumentException('El nombre debe tener entre 3 y 150 caracteres.');
        }

        if (!preg_match('/\A[a-zA-Z0-9._-]{3,60}\z/', $usuario)) {
            throw new InvalidArgumentException(
                'El usuario debe tener entre 3 y 60 caracteres: letras, números, puntos, guiones o guion bajo.'
            );
        }

        if ($correo !== '' && (strlen($correo) > 190 || !filter_var($correo, FILTER_VALIDATE_EMAIL))) {
            throw new InvalidArgumentException('Ingresa un correo electrónico válido.');
        }

        $rolValido = false;
        foreach ($this->modelo->listarRoles() as $rol) {
            if ((int) $rol['id'] === $rolId) {
                $rolValido = true;
                break;
            }
        }

        if (!$rolValido) {
            throw new InvalidArgumentException('Selecciona un rol válido.');
        }

        return [
            'nombre' => $nombre,
            'usuario' => $usuario,
            'correo' => $correo !== '' ? $correo : null,
            'rol_id' => (int) $rolId,
        ];
    }

    private function validarPassword(array $datos): string
    {
        $password = (string) ($datos['password'] ?? '');
        $confirmacion = (string) ($datos['confirmar_password'] ?? '');

        if (mb_strlen($password) < 8 || strlen($password) > 72) {
            throw new InvalidArgumentException('La contraseña debe tener al menos 8 caracteres y máximo 72 bytes.');
        }

        if ($password !== $confirmacion) {
            throw new InvalidArgumentException('Las contraseñas no coinciden.');
        }

        return $password;
    }

    private function manejarError(Throwable $error): array
    {
        if ($error instanceof PDOException && (string) $error->getCode() === '23000') {
            return $this->respuesta(false, 'El nombre de usuario o correo ya está registrado.');
        }

        if ($error instanceof InvalidArgumentException || $error instanceof RuntimeException) {
            return $this->respuesta(false, $error->getMessage());
        }

        error_log('Error al gestionar usuarios: ' . $error->getMessage());
        return $this->respuesta(false, 'No fue posible completar la operación. Inténtalo nuevamente.');
    }

    private function respuesta(bool $exito, string $mensaje): array
    {
        return ['exito' => $exito, 'mensaje' => $mensaje];
    }
}
