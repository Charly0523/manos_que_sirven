<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';

final class AuthMiddleware
{
    private static bool $sesionVerificada = false;

    private const ACCESO_POR_PAGINA = [
        'dashboard.php'        => ['Administrador', 'Empleado', 'Voluntario'],
        'beneficiarios.php'    => ['Administrador', 'Empleado'],
        'donaciones.php'       => ['Administrador', 'Empleado'],
        'inventario.php'       => ['Administrador', 'Empleado'],
        'ayudas.php'           => ['Administrador', 'Empleado'],
        'reportes.php'         => ['Administrador', 'Empleado'],
        'descargar_reporte.php'=> ['Administrador', 'Empleado'],
        'usuarios.php'         => ['Administrador'],
    ];

    public static function requiereLogin(): void
    {
        if (!self::$sesionVerificada) {
            $usuarioId = filter_var(
                $_SESSION['usuario_id'] ?? null,
                FILTER_VALIDATE_INT
            );

            if (!is_int($usuarioId) || $usuarioId <= 0) {
                self::redirigirAlLogin();
            }

            try {
                $consulta = obtenerConexion()->prepare('
                    SELECT u.nombre_completo, u.activo, r.nombre AS rol
                    FROM usuarios u
                    INNER JOIN roles r ON r.id = u.rol_id
                    WHERE u.id = :id
                    LIMIT 1
                ');
                $consulta->execute(['id' => $usuarioId]);
                $usuario = $consulta->fetch();
            } catch (Throwable $error) {
                error_log('Error al verificar permisos: ' . $error->getMessage());
                http_response_code(503);
                exit('No se pudo verificar tu sesión. Inténtalo de nuevo.');
            }

            if (
                !$usuario
                || (int) $usuario['activo'] !== 1
                || !in_array(
                    (string) $usuario['rol'],
                    ['Administrador', 'Empleado', 'Voluntario'],
                    true
                )
            ) {
                self::redirigirAlLogin();
            }

            $_SESSION['rol'] = (string) $usuario['rol'];
            $_SESSION['nombre_usuario'] = (string) $usuario['nombre_completo'];
            self::$sesionVerificada = true;
        }

        $pagina = strtolower(basename((string) ($_SERVER['SCRIPT_NAME'] ?? '')));
        if (!self::puedeAccederPagina($pagina)) {
            self::denegarAcceso();
        }
    }

    public static function requiereRol(array $rolesPermitidos): void
    {
        self::requiereLogin();

        if (!in_array((string) $_SESSION['rol'], $rolesPermitidos, true)) {
            self::denegarAcceso();
        }
    }

    public static function tieneRol(string $rol): bool
    {
        return self::$sesionVerificada
            && (string) ($_SESSION['rol'] ?? '') === $rol;
    }

    public static function puedeAcceder(string $pagina): bool
    {
        self::requiereLogin();
        return self::puedeAccederPagina(strtolower(basename($pagina)));
    }

    private static function puedeAccederPagina(string $pagina): bool
    {
        $roles = self::ACCESO_POR_PAGINA[$pagina] ?? ['Administrador'];
        return in_array((string) ($_SESSION['rol'] ?? ''), $roles, true);
    }

    private static function denegarAcceso(): never
    {
        http_response_code(403);
        exit('Acceso denegado. No tienes permiso para entrar a esta página.');
    }

    private static function redirigirAlLogin(): never
    {
        $_SESSION = [];
        session_regenerate_id(true);
        header('Location: login.php');
        exit;
    }
}
