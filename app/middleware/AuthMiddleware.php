<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/app.php';

final class AuthMiddleware
{
    /**
     * Comprueba que exista una sesión autenticada.
     */
    public static function requiereLogin(): void
    {
        if (!isset($_SESSION['usuario_id'])) {
            header('Location: login.php');
            exit;
        }
    }

    /**
     * Comprueba que el usuario tenga uno de los roles permitidos.
     *
     * Ejemplo:
     * AuthMiddleware::requiereRol(['Administrador']);
     */
    public static function requiereRol(array $rolesPermitidos): void
    {
        self::requiereLogin();

        $rolActual = (string) ($_SESSION['rol'] ?? '');

        if (!in_array($rolActual, $rolesPermitidos, true)) {
            http_response_code(403);

            exit(
                'Acceso denegado. No tienes permiso para realizar esta acción.'
            );
        }
    }

    /**
     * Comprueba si el usuario tiene un rol específico.
     */
    public static function tieneRol(string $rol): bool
    {
        if (!isset($_SESSION['usuario_id'])) {
            return false;
        }

        return ($_SESSION['rol'] ?? '') === $rol;
    }
}
