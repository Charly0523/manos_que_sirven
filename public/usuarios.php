<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/controllers/UsuarioController.php';

$controlador = new UsuarioController();
$paginaActiva = 'usuarios';
$mensaje = $_SESSION['mensaje_usuarios'] ?? null;
unset($_SESSION['mensaje_usuarios']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verificarTokenCSRF(is_string($_POST['csrf_token'] ?? null) ? $_POST['csrf_token'] : null)) {
        http_response_code(403);
        exit('Solicitud no válida. Recarga la página e inténtalo nuevamente.');
    }

    $accionPost = is_string($_POST['accion'] ?? null) ? $_POST['accion'] : '';
    $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
    $id = ($id !== false && $id !== null && $id > 0) ? (int) $id : 0;

    switch ($accionPost) {
        case 'registrar':
            $resultado = $controlador->registrar($_POST);
            break;
        case 'actualizar':
            $resultado = $controlador->actualizar($id, $_POST);
            break;
        case 'cambiar_estado':
            $estado = is_string($_POST['estado'] ?? null) ? $_POST['estado'] : '';
            $resultado = $controlador->cambiarEstado($id, $estado);
            break;
        case 'cambiar_contrasena':
            $resultado = $controlador->cambiarContrasena($id, $_POST);
            break;
        default:
            $resultado = ['exito' => false, 'mensaje' => 'Acción no reconocida.'];
    }

    $_SESSION['mensaje_usuarios'] = $resultado;
    $destino = 'usuarios.php';
    if (empty($resultado['exito']) && $id > 0 && $accionPost === 'actualizar') {
        $destino = 'usuarios.php?accion=editar&id=' . $id;
    } elseif (empty($resultado['exito']) && $id > 0 && $accionPost === 'cambiar_contrasena') {
        $destino = 'usuarios.php?accion=clave&id=' . $id;
    } elseif (empty($resultado['exito']) && $accionPost === 'registrar') {
        $destino = 'usuarios.php?accion=nuevo';
    }
    header('Location: ' . $destino, true, 303);
    exit;
}

try {
    $todos = $controlador->listar();
    $roles = $controlador->listarRoles();
} catch (Throwable $error) {
    error_log('Error al consultar usuarios: ' . $error->getMessage());
    http_response_code(500);
    exit('No fue posible cargar los usuarios. Revisa la conexión a MySQL.');
}

$accion = is_string($_GET['accion'] ?? null) ? $_GET['accion'] : '';
$edicion = null;
if (in_array($accion, ['editar', 'clave'], true)) {
    $idEditar = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
    $edicion = ($idEditar !== false && $idEditar !== null && $idEditar > 0)
        ? $controlador->obtenerPorId((int) $idEditar)
        : null;
    if ($edicion === null) {
        $mensaje = ['exito' => false, 'mensaje' => 'No se encontró el usuario seleccionado.'];
        $accion = '';
    }
}

$busqueda = trim(is_string($_GET['buscar'] ?? null) ? $_GET['buscar'] : '');
$usuarios = $busqueda === '' ? $todos : array_values(array_filter($todos, static function (array $usuario) use ($busqueda): bool {
    foreach (['nombre_completo', 'usuario', 'correo', 'rol'] as $campo) {
        if (stripos((string) ($usuario[$campo] ?? ''), $busqueda) !== false) {
            return true;
        }
    }
    return false;
}));

$totalActivos = count(array_filter($todos, static fn (array $u): bool => (int) $u['activo'] === 1));
$totalAdministradores = count(array_filter($todos, static fn (array $u): bool => $u['rol'] === 'Administrador' && (int) $u['activo'] === 1));
$mostrarFormulario = $accion === 'nuevo' || ($accion === 'editar' && $edicion !== null);
$mostrarClave = $accion === 'clave' && $edicion !== null;
$esEdicion = $accion === 'editar';
$datos = $edicion ?? ['id' => 0, 'nombre_completo' => '', 'usuario' => '', 'correo' => '', 'rol_id' => 0];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuarios | Manos que Sirven</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/sidebar.css">
    <style>
        body { background: #f4f7f5; color: #26352d; }
        .usuarios-titulo { font-size: 27px; font-weight: 700; margin: 0; }
        .usuarios-subtitulo { color: #6b776f; margin: 7px 0 0; }
        .usuarios-panel { background: #fff; border: 1px solid #e1e8e3; border-radius: 13px; padding: 24px; margin-bottom: 22px; }
        .usuarios-indicador { background: #fff; border: 1px solid #e1e8e3; border-radius: 12px; padding: 19px; }
        .usuarios-indicador span { color: #6b776f; font-size: 13px; }
        .usuarios-indicador strong { display: block; font-size: 28px; margin-top: 7px; }
        .btn-verde { background: #176b4d; border-color: #176b4d; color: white; }
        .btn-verde:hover, .btn-verde:focus { background: #10543c; border-color: #10543c; color: white; }
        .form-control:focus, .form-select:focus { border-color: #176b4d; box-shadow: 0 0 0 .2rem rgba(23,107,77,.13); }
        .usuarios-table th { font-size: 12px; color: #637168; text-transform: uppercase; letter-spacing: .04em; white-space: nowrap; }
        .usuarios-table td { vertical-align: middle; }
        .usuarios-acciones { display: flex; flex-wrap: wrap; gap: 6px; }
        .usuarios-acciones form { margin: 0; }
        .usuarios-meta { color: #6b776f; font-size: 12px; }
        @media (max-width: 768px) { .usuarios-panel { padding: 17px; } }
    </style>
</head>
<body>
<div class="app-layout">
    <?php require __DIR__ . '/../app/views/layout/sidebar.php'; ?>
    <main class="main-content">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
            <div>
                <h1 class="usuarios-titulo">Administración de usuarios</h1>
                <p class="usuarios-subtitulo">Gestiona las cuentas y los roles de acceso al sistema.</p>
            </div>
            <a href="usuarios.php?accion=nuevo" class="btn btn-verde">+ Nuevo usuario</a>
        </div>

        <?php if (is_array($mensaje) && isset($mensaje['mensaje'])): ?>
            <div class="alert alert-<?= !empty($mensaje['exito']) ? 'success' : 'danger' ?>" role="alert">
                <?= escapar((string) $mensaje['mensaje']) ?>
            </div>
        <?php endif; ?>

        <div class="row g-3 mb-4">
            <div class="col-md-4"><div class="usuarios-indicador"><span>Usuarios registrados</span><strong><?= count($todos) ?></strong></div></div>
            <div class="col-md-4"><div class="usuarios-indicador"><span>Usuarios activos</span><strong><?= $totalActivos ?></strong></div></div>
            <div class="col-md-4"><div class="usuarios-indicador"><span>Administradores activos</span><strong><?= $totalAdministradores ?></strong></div></div>
        </div>

        <?php if ($mostrarFormulario): ?>
            <section class="usuarios-panel" id="formulario">
                <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
                    <h2 class="h5 mb-0"><?= $esEdicion ? 'Editar usuario' : 'Registrar usuario' ?></h2>
                    <a href="usuarios.php" class="btn btn-outline-secondary btn-sm">Cerrar</a>
                </div>
                <form method="post" action="usuarios.php">
                    <input type="hidden" name="csrf_token" value="<?= escapar(generarTokenCSRF()) ?>">
                    <input type="hidden" name="accion" value="<?= $esEdicion ? 'actualizar' : 'registrar' ?>">
                    <?php if ($esEdicion): ?><input type="hidden" name="id" value="<?= (int) $datos['id'] ?>"><?php endif; ?>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="nombre_completo">Nombre completo *</label>
                            <input class="form-control" id="nombre_completo" name="nombre_completo" maxlength="150" required value="<?= escapar((string) $datos['nombre_completo']) ?>" autocomplete="name">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="usuario">Nombre de usuario *</label>
                            <input class="form-control" id="usuario" name="usuario" maxlength="60" minlength="3" pattern="[a-zA-Z0-9._-]{3,60}" required value="<?= escapar((string) $datos['usuario']) ?>" autocomplete="username">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="correo">Correo electrónico (opcional)</label>
                            <input class="form-control" type="email" id="correo" name="correo" maxlength="190" value="<?= escapar((string) ($datos['correo'] ?? '')) ?>" autocomplete="email">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="rol_id">Rol *</label>
                            <select class="form-select" id="rol_id" name="rol_id" required>
                                <option value="">Selecciona un rol</option>
                                <?php foreach ($roles as $rol): ?>
                                    <option value="<?= (int) $rol['id'] ?>" <?= (int) $datos['rol_id'] === (int) $rol['id'] ? 'selected' : '' ?>>
                                        <?= escapar((string) $rol['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php if (!$esEdicion): ?>
                            <div class="col-md-6">
                                <label class="form-label" for="password">Contraseña *</label>
                                <input class="form-control" type="password" id="password" name="password" minlength="8" maxlength="72" required autocomplete="new-password">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="confirmar_password">Confirmar contraseña *</label>
                                <input class="form-control" type="password" id="confirmar_password" name="confirmar_password" minlength="8" maxlength="72" required autocomplete="new-password">
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="d-flex flex-wrap gap-2 mt-4">
                        <button class="btn btn-verde" type="submit"><?= $esEdicion ? 'Guardar cambios' : 'Crear usuario' ?></button>
                        <a href="usuarios.php" class="btn btn-outline-secondary">Cancelar</a>
                    </div>
                </form>
            </section>
        <?php endif; ?>

        <?php if ($mostrarClave): ?>
            <section class="usuarios-panel" id="contrasena">
                <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
                    <h2 class="h5 mb-0">Cambiar contraseña: <?= escapar((string) $edicion['usuario']) ?></h2>
                    <a href="usuarios.php" class="btn btn-outline-secondary btn-sm">Cerrar</a>
                </div>
                <form action="usuarios.php" method="post">
                    <input type="hidden" name="csrf_token" value="<?= escapar(generarTokenCSRF()) ?>">
                    <input type="hidden" name="accion" value="cambiar_contrasena">
                    <input type="hidden" name="id" value="<?= (int) $edicion['id'] ?>">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="password">Nueva contraseña *</label>
                            <input class="form-control" type="password" id="password" name="password" minlength="8" maxlength="72" required autocomplete="new-password">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="confirmar_password">Confirmar contraseña *</label>
                            <input class="form-control" type="password" id="confirmar_password" name="confirmar_password" minlength="8" maxlength="72" required autocomplete="new-password">
                        </div>
                    </div>
                    <div class="d-flex gap-2 mt-4">
                        <button class="btn btn-verde" type="submit">Actualizar contraseña</button>
                        <a href="usuarios.php" class="btn btn-outline-secondary">Cancelar</a>
                    </div>
                </form>
            </section>
        <?php endif; ?>

        <section class="usuarios-panel">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
                <div>
                    <h2 class="h5 mb-1">Usuarios del sistema</h2>
                    <p class="usuarios-meta mb-0">Mostrando <?= count($usuarios) ?> de <?= count($todos) ?> cuentas</p>
                </div>
                <form class="d-flex gap-2" method="get" action="usuarios.php" role="search">
                    <input class="form-control" type="search" name="buscar" aria-label="Buscar usuario" placeholder="Buscar usuario o rol" value="<?= escapar($busqueda) ?>">
                    <button type="submit" class="btn btn-outline-success">Buscar</button>
                    <?php if ($busqueda !== ''): ?><a href="usuarios.php" class="btn btn-outline-secondary">Limpiar</a><?php endif; ?>
                </form>
            </div>
            <div class="table-responsive">
                <table class="table table-hover usuarios-table align-middle">
                    <thead><tr><th>Nombre</th><th>Usuario</th><th>Rol</th><th>Estado</th><th>Acciones</th></tr></thead>
                    <tbody>
                    <?php foreach ($usuarios as $u): ?>
                        <tr>
                            <td>
                                <strong><?= escapar((string) $u['nombre_completo']) ?></strong>
                                <?php if (!empty($u['correo'])): ?><div class="usuarios-meta"><?= escapar((string) $u['correo']) ?></div><?php endif; ?>
                            </td>
                            <td><?= escapar((string) $u['usuario']) ?></td>
                            <td><?= escapar((string) $u['rol']) ?></td>
                            <td><span class="badge <?= (int) $u['activo'] === 1 ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= (int) $u['activo'] === 1 ? 'Activo' : 'Inactivo' ?></span></td>
                            <td>
                                <div class="usuarios-acciones">
                                    <a class="btn btn-sm btn-outline-primary" href="usuarios.php?accion=editar&amp;id=<?= (int) $u['id'] ?>">Editar</a>
                                    <a class="btn btn-sm btn-outline-secondary" href="usuarios.php?accion=clave&amp;id=<?= (int) $u['id'] ?>">Contraseña</a>
                                    <?php if ((int) $u['id'] !== (int) $_SESSION['usuario_id']): ?>
                                        <form action="usuarios.php" method="post" onsubmit="return confirm('¿Confirmas el cambio de estado de esta cuenta?');">
                                            <input type="hidden" name="csrf_token" value="<?= escapar(generarTokenCSRF()) ?>">
                                            <input type="hidden" name="accion" value="cambiar_estado">
                                            <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                                            <input type="hidden" name="estado" value="<?= (int) $u['activo'] === 1 ? '0' : '1' ?>">
                                            <button type="submit" class="btn btn-sm <?= (int) $u['activo'] === 1 ? 'btn-outline-danger' : 'btn-outline-success' ?>">
                                                <?= (int) $u['activo'] === 1 ? 'Desactivar' : 'Activar' ?>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$usuarios): ?>
                        <tr><td colspan="5" class="text-center py-4 text-secondary">No se encontraron usuarios.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</div>
</body>
</html>
