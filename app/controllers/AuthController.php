<?php
declare(strict_types=1);

require_once __DIR__ . '/../Models/Usuario.php';
require_once __DIR__ . '/../../config/app.php';

final class AuthController
{
    public function iniciarSesion(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: login.php');
            exit;
        }

        // Validar el token de seguridad del formulario.
        if (!verificarTokenCSRF($_POST['csrf_token'] ?? null)) {
            http_response_code(403);
            exit(
                'La solicitud ha expirado. Recarga el formulario e inténtalo nuevamente.'
            );
        }

        $usuarioIngresado = trim(
            (string) ($_POST['usuario'] ?? '')
        );

        $password = (string) ($_POST['password'] ?? '');

        if (
            $usuarioIngresado === ''
            || $password === ''
            || strlen($usuarioIngresado) > 60
            || strlen($password) > 1024
        ) {
            $_SESSION['login_error'] =
                'Usuario o contraseña incorrectos.';

            header('Location: login.php');
            exit;
        }

        try {
            $modelo = new Usuario();

            $usuario = $modelo->buscarPorUsuario(
                $usuarioIngresado
            );

            // Comprobar que la cuenta exista, esté activa
            // y tenga una contraseña válida.
            if (
                $usuario === null
                || (int) $usuario['activo'] !== 1
                || !password_verify(
                    $password,
                    $usuario['password_hash']
                )
            ) {
                $_SESSION['login_error'] =
                    'Usuario o contraseña incorrectos.';

                header('Location: login.php');
                exit;
            }

            // Renovar el identificador de sesión al iniciar.
            session_regenerate_id(true);

            $_SESSION['usuario_id'] = (int) $usuario['id'];
            $_SESSION['nombre_usuario'] =
                $usuario['nombre_completo'];
            $_SESSION['rol'] = $usuario['rol'];

            // Crear un nuevo token para la sesión autenticada.
            $_SESSION['csrf_token'] = bin2hex(
                random_bytes(32)
            );

            $modelo->actualizarUltimoAcceso(
                (int) $usuario['id']
            );

            header('Location: dashboard.php');
            exit;

        } catch (Throwable $error) {
            error_log(
                'Error durante el inicio de sesión: '
                . $error->getMessage()
            );

            http_response_code(500);

            exit(
                'No fue posible procesar el acceso. Inténtalo más tarde.'
            );
        }
    }
}
