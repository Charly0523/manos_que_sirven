<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/Controllers/AuthController.php';

// Si ya inició sesión, dirigirlo al panel principal.
if (isset($_SESSION['usuario_id'])) {
    header('Location: dashboard.php');
    exit;
}

// Procesar el formulario cuando se envíe.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $controlador = new AuthController();
    $controlador->iniciarSesion();
    exit;
}

// Mostrar el formulario.
require_once __DIR__ . '/../app/Views/auth/login.php';
