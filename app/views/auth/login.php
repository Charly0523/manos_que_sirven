<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../config/app.php';

$error = $_SESSION['login_error'] ?? '';
unset($_SESSION['login_error']);

$csrfToken = generarTokenCSRF();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Iniciar sesión | Manos que Sirven</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link rel="stylesheet" href="assets/css/login.css">
</head>
<body>

<main class="login-container">
    <section class="login-card">

        <div class="brand-icon" aria-hidden="true">MS</div>

        <h1>Manos que Sirven</h1>

        <p class="subtitle">
            Sistema de gestión de beneficencia
        </p>

        <div class="separator"></div>

        <h2>Iniciar sesión</h2>

        <p class="description">
            Ingresa tus credenciales para continuar.
        </p>

        <?php if ($error !== ''): ?>
            <div class="alert alert-danger" role="alert">
                <?= escapar((string) $error) ?>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= escapar($csrfToken) ?>"
            >

            <div class="mb-3">
                <label for="usuario" class="form-label">
                    Nombre de usuario
                </label>

                <input
                    type="text"
                    class="form-control"
                    id="usuario"
                    name="usuario"
                    maxlength="60"
                    autocomplete="username"
                    required
                    autofocus
                >
            </div>

            <div class="mb-4">
                <label for="password" class="form-label">
                    Contraseña
                </label>

                <input
                    type="password"
                    class="form-control"
                    id="password"
                    name="password"
                    autocomplete="current-password"
                    required
                >
            </div>

            <button
                type="submit"
                class="btn btn-login w-100"
            >
                Iniciar sesión
            </button>

        </form>

        <p class="footer-text">
            Iglesia de San Isidro · Cabañas, El Salvador
        </p>

    </section>
</main>

</body>
</html>
