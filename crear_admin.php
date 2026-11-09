<?php
declare(strict_types=1);

require_once __DIR__ . '/config/database.php';

header('Content-Type: text/html; charset=UTF-8');

$mensaje = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim((string) ($_POST['nombre'] ?? ''));
    $usuario = trim((string) ($_POST['usuario'] ?? ''));
    $correo = trim((string) ($_POST['correo'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if (
        $nombre === ''
        || $usuario === ''
        || $password === ''
        || strlen($nombre) > 150
        || strlen($usuario) > 60
        || strlen($password) < 12
        || strlen($password) > 1024
        || ($correo !== '' && (
            strlen($correo) > 190
            || !filter_var($correo, FILTER_VALIDATE_EMAIL)
        ))
    ) {
        $error = 'Completa los campos correctamente. La contraseña debe tener al menos 12 caracteres.';
    } else {
        try {
            $conexion = obtenerConexion();

            $consultaRol = $conexion->prepare(
                "SELECT id FROM roles WHERE nombre = 'Administrador' LIMIT 1"
            );
            $consultaRol->execute();
            $rol = $consultaRol->fetch();

            if (!$rol) {
                throw new RuntimeException(
                    'No existe el rol Administrador.'
                );
            }

            $passwordHash = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            $consulta = $conexion->prepare(
                'INSERT INTO usuarios
                (nombre_completo, usuario, correo, password_hash, rol_id, activo)
                VALUES
                (:nombre, :usuario, :correo, :password_hash, :rol_id, 1)'
            );

            $consulta->execute([
                'nombre' => $nombre,
                'usuario' => $usuario,
                'correo' => $correo !== '' ? $correo : null,
                'password_hash' => $passwordHash,
                'rol_id' => (int) $rol['id'],
            ]);

            $mensaje = 'La cuenta de administrador se creó correctamente.';

        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $error = 'Ese nombre de usuario o correo ya está registrado.';
            } else {
                error_log('Error al crear administrador: ' . $e->getMessage());
                $error = 'No se pudo crear la cuenta. Revisa la configuración e inténtalo de nuevo.';
            }
        } catch (Throwable $e) {
            error_log('Error al crear administrador: ' . $e->getMessage());
            $error = 'No se pudo crear la cuenta de administrador.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear administrador | Manos que Sirven</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<main class="container py-5">
    <section class="card shadow-sm border-0 mx-auto" style="max-width: 560px;">
        <div class="card-body p-4 p-md-5">
            <h1 class="h3 mb-3">Crear administrador</h1>
            <p class="text-secondary">
                Registra la primera cuenta de administración del sistema.
            </p>

            <?php if ($mensaje !== ''): ?>
                <div class="alert alert-success">
                    <?= htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <?php if ($error !== ''): ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <?php if ($mensaje === ''): ?>
                <form method="POST">
                    <div class="mb-3">
                        <label for="nombre" class="form-label">Nombre completo</label>
                        <input type="text" class="form-control" id="nombre" name="nombre" maxlength="150" required>
                    </div>

                    <div class="mb-3">
                        <label for="usuario" class="form-label">Nombre de usuario</label>
                        <input type="text" class="form-control" id="usuario" name="usuario" maxlength="60" autocomplete="username" required>
                    </div>

                    <div class="mb-3">
                        <label for="correo" class="form-label">Correo electrónico (opcional)</label>
                        <input type="email" class="form-control" id="correo" name="correo" maxlength="190" autocomplete="email">
                    </div>

                    <div class="mb-4">
                        <label for="password" class="form-label">Contraseña</label>
                        <input type="password" class="form-control" id="password" name="password" minlength="12" maxlength="1024" autocomplete="new-password" required>
                        <div class="form-text">Utiliza al menos 12 caracteres y no reutilices una contraseña de otra cuenta.</div>
                    </div>

                    <button type="submit" class="btn btn-success w-100">
                        Crear cuenta
                    </button>
                </form>
            <?php else: ?>
                <a href="public/login.php" class="btn btn-success">
                    Ir al inicio de sesión
                </a>
            <?php endif; ?>
        </div>
    </section>
</main>
</body>
</html>
