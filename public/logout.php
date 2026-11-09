<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';

// El cierre de sesión solo se permite mediante POST.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

// Verificar el token de seguridad.
if (!verificarTokenCSRF($_POST['csrf_token'] ?? null)) {
    http_response_code(403);
    exit(
        'Solicitud no válida. Regresa al sistema e inténtalo nuevamente.'
    );
}

// Vaciar los datos de la sesión.
$_SESSION = [];

// Eliminar la cookie de sesión del navegador.
if (ini_get('session.use_cookies')) {
    $parametros = session_get_cookie_params();

    setcookie(session_name(), '', [
        'expires' => time() - 42000,
        'path' => $parametros['path'],
        'secure' => $parametros['secure'],
        'httponly' => $parametros['httponly'],
        'samesite' => $parametros['samesite'] ?? 'Lax',
    ]);
}

// Destruir la sesión en el servidor.
session_destroy();

// Regresar al formulario de acceso.
header('Location: login.php');
exit;
