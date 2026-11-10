<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../middleware/AuthMiddleware.php';

AuthMiddleware::requiereLogin();

$nombreMenu = (string) ($_SESSION['nombre_usuario'] ?? 'Usuario');
$rolMenu = (string) ($_SESSION['rol'] ?? 'Sin rol');

$paginaActiva = $paginaActiva
    ?? basename((string) ($_SERVER['SCRIPT_NAME'] ?? 'dashboard.php'), '.php');

$elementosMenu = [
    'dashboard' => [
        'url' => 'dashboard.php',
        'icono' => '□',
        'nombre' => 'Panel principal',
    ],
    'beneficiarios' => [
        'url' => 'beneficiarios.php',
        'icono' => '♙',
        'nombre' => 'Beneficiarios',
    ],
    'donaciones' => [
        'url' => 'donaciones.php',
        'icono' => '$',
        'nombre' => 'Donaciones',
    ],
    'inventario' => [
        'url' => 'inventario.php',
        'icono' => '☷',
        'nombre' => 'Inventario',
    ],
    'ayudas' => [
        'url' => 'ayudas.php',
        'icono' => '♥',
        'nombre' => 'Ayudas entregadas',
    ],
    'reportes' => [
        'url' => 'reportes.php',
        'icono' => '▦',
        'nombre' => 'Reportes',
    ],
    'tienda' => [
    'url' => 'tienda.php',
    'icono' => '▣',
    'nombre' => 'Tienda parroquial',
    
    ],
    'catequesis' => [
    'url' => 'catequesis.php',
    'icono' => '✎',
    'nombre' => 'Catequesis',
    ],

    'usuarios' => [
        'url' => 'usuarios.php',
        'icono' => '⚙',
        'nombre' => 'Usuarios',
    ],
];
?>

<aside class="sidebar">
    <div class="brand">
        <div class="brand-icon">MS</div>
        <div>
            <h1>Manos que Sirven</h1>
            <p>Iglesia de San Isidro</p>
        </div>
    </div>

    <p class="menu-title">Menú principal</p>

    <nav class="sidebar-menu" aria-label="Menú principal">
        <?php foreach ($elementosMenu as $clave => $elemento): ?>
            <?php
            if (!AuthMiddleware::puedeAcceder($elemento['url'])) {
                continue;
            }
            $activo = $paginaActiva === $clave;
            ?>
            <a
                href="<?= escapar($elemento['url']) ?>"
                class="<?= $activo ? 'active' : '' ?>"
                <?= $activo ? 'aria-current="page"' : '' ?>
            >
                <span class="menu-symbol" aria-hidden="true">
                    <?= escapar($elemento['icono']) ?>
                </span>
                <span><?= escapar($elemento['nombre']) ?></span>
            </a>
        <?php endforeach; ?>
    </nav>

    <div class="sidebar-footer">
        <p class="user-name"><?= escapar($nombreMenu) ?></p>
        <p class="user-role"><?= escapar($rolMenu) ?></p>

        <form action="logout.php" method="POST">
            <input
                type="hidden"
                name="csrf_token"
                value="<?= escapar(generarTokenCSRF()) ?>"
            >
            <button type="submit" class="logout-button">Cerrar sesión</button>
        </form>
    </div>
</aside>
