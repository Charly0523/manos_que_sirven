<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/middleware/AuthMiddleware.php';

AuthMiddleware::requiereLogin();

$nombre = (string) ($_SESSION['nombre_usuario'] ?? 'Usuario');
$rol = (string) ($_SESSION['rol'] ?? 'Sin rol');
$paginaActiva = 'dashboard';
$fechaActual = (new DateTimeImmutable('now', new DateTimeZone('America/El_Salvador')))
    ->format('d/m/Y');

$puedeGestionarBeneficencia = AuthMiddleware::puedeAcceder('beneficiarios.php');
$indicadores = null;
$errorIndicadores = false;

if ($puedeGestionarBeneficencia) {
    try {
        $conexion = obtenerConexion();
        $indicadores = [
            'beneficiarios' => (int) $conexion->query(
                'SELECT COUNT(*) FROM beneficiarios'
            )->fetchColumn(),
            'donaciones' => (int) $conexion->query(
                "SELECT COUNT(*) FROM donaciones WHERE estado_recepcion = 'Recibida'"
            )->fetchColumn(),
            'ayudas' => (int) $conexion->query(
                "SELECT COUNT(*) FROM ayudas WHERE estado = 'Entregada'"
            )->fetchColumn(),
        ];
    } catch (Throwable $error) {
        error_log('Error al obtener indicadores del dashboard: ' . $error->getMessage());
        $errorIndicadores = true;
    }
}

$modulos = [
    ['beneficiarios.php', '♙', 'Beneficiarios', 'Registrar y consultar personas beneficiarias.'],
    ['donaciones.php', '$', 'Donaciones', 'Registrar donaciones monetarias y en especie.'],
    ['inventario.php', '☷', 'Inventario', 'Consultar productos y movimientos de existencias.'],
    ['ayudas.php', '♥', 'Ayudas entregadas', 'Registrar y consultar las ayudas proporcionadas.'],
    ['reportes.php', '▦', 'Reportes', 'Consultar los informes del sistema.'],
    ['usuarios.php', '⚙', 'Usuarios y permisos', 'Gestionar cuentas, contraseñas y roles.'],
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel principal | Manos que Sirven</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/sidebar.css">
</head>
<body>
<div class="app-layout">
    <?php require __DIR__ . '/../app/views/layout/sidebar.php'; ?>

    <main class="main-content">
        <header class="topbar">
            <div>
                <h1 class="page-heading">Panel principal</h1>
                <p class="page-subtitle">Sistema de gestión de la Iglesia de San Isidro.</p>
            </div>
            <div class="date-label"><?= escapar($fechaActual) ?></div>
        </header>

        <section class="welcome-card">
            <h2>Bienvenido, <?= escapar($nombre) ?></h2>
            <p>Has ingresado como <?= escapar($rol) ?>. Aquí encontrarás tus módulos autorizados.</p>
        </section>

        <?php if ($puedeGestionarBeneficencia): ?>
            <section aria-labelledby="resumen-titulo">
                <h2 class="section-heading" id="resumen-titulo">Resumen general</h2>

                <?php if ($errorIndicadores): ?>
                    <div class="alert alert-warning" role="alert">
                        No se pudieron cargar los indicadores. Revisa la conexión con la base de datos.
                    </div>
                <?php endif; ?>

                <div class="stats-grid">
                    <article class="stat-card">
                        <p class="stat-label">Beneficiarios registrados</p>
                        <p class="stat-value"><?= $indicadores !== null ? number_format($indicadores['beneficiarios'], 0, ',', '.') : '—' ?></p>
                        <p class="stat-note">Total de beneficiarios registrados.</p>
                    </article>
                    <article class="stat-card">
                        <p class="stat-label">Donaciones recibidas</p>
                        <p class="stat-value"><?= $indicadores !== null ? number_format($indicadores['donaciones'], 0, ',', '.') : '—' ?></p>
                        <p class="stat-note">Solo donaciones con estado Recibida.</p>
                    </article>
                    <article class="stat-card">
                        <p class="stat-label">Ayudas entregadas</p>
                        <p class="stat-value"><?= $indicadores !== null ? number_format($indicadores['ayudas'], 0, ',', '.') : '—' ?></p>
                        <p class="stat-note">Solo ayudas con estado Entregada.</p>
                    </article>
                </div>
            </section>
        <?php endif; ?>

        <section aria-labelledby="modulos-titulo">
            <h2 class="section-heading" id="modulos-titulo">Accesos principales</h2>
            <div class="modules-grid">
                <?php $modulosDisponibles = 0; ?>
                <?php foreach ($modulos as [$url, $icono, $titulo, $descripcion]): ?>
                    <?php if (!AuthMiddleware::puedeAcceder($url)) { continue; } ?>
                    <?php $modulosDisponibles++; ?>
                    <a href="<?= escapar($url) ?>" class="module-card">
                        <div class="module-icon" aria-hidden="true"><?= escapar($icono) ?></div>
                        <div>
                            <h3><?= escapar($titulo) ?></h3>
                            <p><?= escapar($descripcion) ?></p>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>

            <?php if ($modulosDisponibles === 0): ?>
                <p class="text-secondary">
                    Tu cuenta no tiene módulos operativos asignados por el momento.
                    Contacta a un administrador para consultar tus permisos.
                </p>
            <?php endif; ?>
        </section>

        <footer class="footer-note">Manos que Sirven · Iglesia de San Isidro, Cabañas</footer>
    </main>
</div>
</body>
</html>
