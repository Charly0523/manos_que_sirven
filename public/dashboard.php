<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/Middleware/AuthMiddleware.php';

AuthMiddleware::requiereLogin();

$nombre = (string) ($_SESSION['nombre_usuario'] ?? 'Usuario');
$rol = (string) ($_SESSION['rol'] ?? 'Sin rol');

$esAdministrador = $rol === 'Administrador';

$fechaActual = date('d/m/Y');

$paginaActiva = 'dashboard';
?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Panel principal | Manos que Sirven</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link rel="stylesheet" href="assets/css/dashboard.css">

    <link rel="stylesheet" href="assets/css/sidebar.css">

</head>

<body>

<div class="app-layout">

    <?php
    require_once __DIR__ . '/../app/Views/layout/sidebar.php';
    ?>

    <main class="main-content">

        <header class="topbar">

            <div>
                <h1 class="page-heading">Panel principal</h1>

                <p class="page-subtitle">
                    Administración de la beneficencia de la iglesia.
                </p>
            </div>

            <div class="date-label">
                <?= escapar($fechaActual) ?>
            </div>

        </header>

        <section class="welcome-card">

            <h2>
                Bienvenido, <?= escapar($nombre) ?>
            </h2>

            <p>
                Desde aquí podrás administrar beneficiarios, registrar
                donaciones y supervisar las ayudas de la institución.
            </p>

        </section>

        <section aria-labelledby="resumen-titulo">

            <h2 class="section-heading" id="resumen-titulo">
                Resumen general
            </h2>

            <div class="stats-grid">

                <article class="stat-card">

                    <p class="stat-label">
                        Beneficiarios registrados
                    </p>

                    <p class="stat-value">—</p>

                    <p class="stat-note">
                        Se mostrará el total cuando implementemos el módulo.
                    </p>

                </article>

                <article class="stat-card">

                    <p class="stat-label">
                        Donaciones recibidas
                    </p>

                    <p class="stat-value">—</p>

                    <p class="stat-note">
                        Dinero y productos registrados en el sistema.
                    </p>

                </article>

                <article class="stat-card">

                    <p class="stat-label">
                        Ayudas entregadas
                    </p>

                    <p class="stat-value">—</p>

                    <p class="stat-note">
                        Se mostrará el total cuando implementemos el módulo.
                    </p>

                </article>

            </div>

        </section>

        <section aria-labelledby="modulos-titulo">

            <h2 class="section-heading" id="modulos-titulo">
                Accesos principales
            </h2>

            <div class="modules-grid">

                <a href="beneficiarios.php" class="module-card">

                    <div class="module-icon" aria-hidden="true">♙</div>

                    <div>
                        <h3>Beneficiarios</h3>

                        <p>
                            Registrar personas, consultar sus datos
                            y mantener actualizada su información.
                        </p>
                    </div>

                </a>

                <a href="donaciones.php" class="module-card">

                    <div class="module-icon" aria-hidden="true">$</div>

                    <div>
                        <h3>Donaciones</h3>

                        <p>
                            Registrar aportes monetarios y donaciones
                            de alimentos, ropa y otros productos.
                        </p>
                    </div>

                </a>

                <a href="inventario.php" class="module-card">

                    <div class="module-icon" aria-hidden="true">☷</div>

                    <div>
                        <h3>Inventario</h3>

                        <p>
                            Consultar las existencias de productos
                            disponibles para las ayudas.
                        </p>
                    </div>

                </a>

                <a href="ayudas.php" class="module-card">

                    <div class="module-icon" aria-hidden="true">♥</div>

                    <div>
                        <h3>Ayudas entregadas</h3>

                        <p>
                            Registrar las ayudas proporcionadas
                            a los beneficiarios.
                        </p>
                    </div>

                </a>

                <a href="reportes.php" class="module-card">

                    <div class="module-icon" aria-hidden="true">▦</div>

                    <div>
                        <h3>Reportes</h3>

                        <p>
                            Consultar resúmenes de donaciones,
                            inventario y ayudas.
                        </p>
                    </div>

                </a>

                <?php if ($esAdministrador): ?>

                    <a href="usuarios.php" class="module-card">

                        <div class="module-icon" aria-hidden="true">⚙</div>

                        <div>
                            <h3>Usuarios y permisos</h3>

                            <p>
                                Administrar las cuentas de acceso
                                y los permisos de los empleados.
                            </p>
                        </div>

                    </a>

                <?php endif; ?>

            </div>

        </section>

        <footer class="footer-note">

            Manos que Sirven · Iglesia de San Isidro, Cabañas

        </footer>

    </main>

</div>

</body>
</html>