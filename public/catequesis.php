<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/middleware/AuthMiddleware.php';
require_once __DIR__ . '/../app/controllers/CatequesisController.php';

AuthMiddleware::requiereRol(['Administrador', 'Empleado']);
$admin = AuthMiddleware::tieneRol('Administrador');
$controlador = new CatequesisController();
$modelo = $controlador->modelo;
$tabs = ['alumnos', 'grupos', 'catequistas', 'evaluaciones', 'notas', 'asistencia'];
$tab = is_string($_GET['tab'] ?? null) ? $_GET['tab'] : 'alumnos';
if (!in_array($tab, $tabs, true)) { $tab = 'alumnos'; }
if (!$admin && $tab === 'catequistas') { $tab = 'alumnos'; }
$grupoId = max(0, (int) ($_GET['grupo'] ?? 0));
$evaluacionId = max(0, (int) ($_GET['evaluacion'] ?? 0));
$fecha = (string) ($_GET['fecha'] ?? date('Y-m-d'));
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) { $fecha = date('Y-m-d'); }

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $mensaje = '';
    try {
        if (!verificarTokenCSRF(is_string($_POST['csrf_token'] ?? null) ? $_POST['csrf_token'] : null)) {
            throw new DomainException('Formulario vencido. Recarga la página e inténtalo nuevamente.');
        }
        $mensaje = $controlador->procesar(
            is_string($_POST['accion'] ?? null) ? $_POST['accion'] : '',
            $_POST, (int) ($_SESSION['usuario_id'] ?? 0), $admin
        );
        $_SESSION['catequesis_aviso'] = ['ok' => true, 'mensaje' => $mensaje];
    } catch (InvalidArgumentException|DomainException $error) {
        $_SESSION['catequesis_aviso'] = ['ok' => false, 'mensaje' => $error->getMessage()];
    } catch (Throwable $error) {
        error_log('Catequesis página: ' . $error->getMessage());
        $_SESSION['catequesis_aviso'] = ['ok' => false, 'mensaje' => 'No se pudo completar la operación. Revisa el registro de errores.'];
    }
    $params = http_build_query(['tab' => $tab, 'grupo' => $grupoId, 'evaluacion' => $evaluacionId, 'fecha' => $fecha]);
    header('Location: catequesis.php?' . $params);
    exit;
}

$aviso = $_SESSION['catequesis_aviso'] ?? null;
unset($_SESSION['catequesis_aviso']);
$busqueda = is_string($_GET['buscar'] ?? null) ? trim($_GET['buscar']) : '';
if (mb_strlen($busqueda) > 100) { $busqueda = mb_substr($busqueda, 0, 100); }
try {
    $niveles = $modelo->niveles();
    $catequistas = $modelo->catequistas();
    $grupos = $modelo->grupos();
    $alumnos = $modelo->alumnos($tab === 'alumnos' ? $busqueda : '');
    if (!$grupoId && $grupos && in_array($tab, ['evaluaciones', 'notas', 'asistencia'], true)) {
        $grupoId = (int) $grupos[0]['id'];
    }
    $inscripciones = $grupoId > 0 ? $modelo->inscripciones($grupoId) : [];
    $evaluaciones = $grupoId > 0 ? $modelo->evaluaciones($grupoId) : [];
    if (!$evaluacionId && $evaluaciones) { $evaluacionId = (int) $evaluaciones[0]['id']; }
    if ($evaluacionId && !in_array($evaluacionId, array_map(static fn ($r) => (int) $r['id'], $evaluaciones), true)) {
        $evaluacionId = 0;
    }
    $notasActuales = $evaluacionId > 0 && $tab === 'notas' ? $modelo->notasEvaluacion($evaluacionId) : [];
    $promedios = $grupoId > 0 && $tab === 'notas' ? $modelo->promediosGrupo($grupoId) : [];
    $asistencias = $grupoId > 0 && $tab === 'asistencia' ? $modelo->asistencias($grupoId, $fecha) : [];
    $editarAlumno = $tab === 'alumnos' && (int) ($_GET['editar'] ?? 0) > 0
        ? $modelo->alumno((int) $_GET['editar']) : null;
    $editarCatequista = $admin && $tab === 'catequistas' && (int) ($_GET['editar'] ?? 0) > 0
        ? $modelo->catequista((int) $_GET['editar']) : null;
    $editarGrupo = $admin && $tab === 'grupos' && (int) ($_GET['editar'] ?? 0) > 0
        ? $modelo->grupo((int) $_GET['editar']) : null;
} catch (Throwable $error) {
    error_log('Catequesis consulta: ' . $error->getMessage());
    http_response_code(503);
    exit('No se pudo cargar Catequesis. Comprueba que importaste database/catequesis.sql en manos_que_sirven.');
}
$paginaActiva = 'catequesis';
$csrf = generarTokenCSRF();
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Catequesis | Manos que Sirven</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/sidebar.css">
    <link rel="stylesheet" href="assets/css/catequesis.css">
</head>
<body>
<div class="app-layout">
    <?php require __DIR__ . '/../app/views/layout/sidebar.php'; ?>
    <main class="main-content catequesis-contenido">
        <header class="catequesis-encabezado">
            <div><span class="catequesis-subtitulo">FORMACIÓN PARROQUIAL</span><h1>Catequesis</h1>
                <p>Alumnos, grupos, evaluaciones, notas y asistencia.</p></div>
            <span class="text-secondary small">Rol: <?= escapar((string) ($_SESSION['rol'] ?? '')) ?></span>
        </header>

        <?php if (is_array($aviso)): ?>
            <div class="alert <?= !empty($aviso['ok']) ? 'alert-success' : 'alert-danger' ?>" role="alert">
                <?= escapar((string) $aviso['mensaje']) ?>
            </div>
        <?php endif; ?>

        <nav class="catequesis-pestanas" aria-label="Secciones de catequesis">
            <?php foreach (['alumnos'=>'Alumnos', 'grupos'=>'Grupos e inscripciones', 'catequistas'=>'Catequistas', 'evaluaciones'=>'Evaluaciones', 'notas'=>'Notas', 'asistencia'=>'Asistencia'] as $clave => $texto): ?>
                <?php if ($clave === 'catequistas' && !$admin) { continue; } ?>
                <a class="<?= $tab === $clave ? 'activa' : '' ?>" href="catequesis.php?tab=<?= escapar($clave) ?>"><?= escapar($texto) ?></a>
            <?php endforeach; ?>
        </nav>

        <?php if ($tab === 'alumnos'): ?>
            <section class="catequesis-panel">
                <h2><?= $editarAlumno ? 'Editar alumno' : 'Registrar alumno' ?></h2>
                <form method="post" class="row g-3">
                    <input type="hidden" name="csrf_token" value="<?= escapar($csrf) ?>">
                    <input type="hidden" name="accion" value="alumno_guardar">
                    <input type="hidden" name="id" value="<?= (int) ($editarAlumno['id'] ?? 0) ?>">
                    <div class="col-lg-6"><label class="form-label">Nombre completo *</label>
                        <input required maxlength="150" class="form-control" name="nombre" value="<?= escapar((string) ($editarAlumno['nombre_completo'] ?? '')) ?>"></div>
                    <div class="col-lg-4"><label class="form-label">Teléfono del responsable (opcional)</label>
                        <input maxlength="25" class="form-control" name="contacto" value="<?= escapar((string) ($editarAlumno['contacto_responsable'] ?? '')) ?>"></div>
                    <div class="col-lg-2 d-flex align-items-end"><button class="btn catequesis-boton w-100">Guardar</button></div>
                    <?php if ($editarAlumno): ?><div class="col-12"><a href="catequesis.php?tab=alumnos">Cancelar edición</a></div><?php endif; ?>
                </form>
            </section>
            <section class="catequesis-panel">
                <div class="catequesis-barra"><h2>Listado de alumnos</h2>
                    <form method="get" class="d-flex gap-2"><input type="hidden" name="tab" value="alumnos">
                        <input aria-label="Buscar alumno" class="form-control" name="buscar" maxlength="100" placeholder="Buscar por nombre" value="<?= escapar($busqueda) ?>">
                        <button class="btn btn-outline-secondary">Buscar</button></form>
                </div>
                <div class="table-responsive"><table class="table table-hover align-middle">
                    <thead><tr><th>Nombre</th><th>Contacto del responsable</th><th>Estado</th><th>Acciones</th></tr></thead>
                    <tbody><?php foreach ($alumnos as $a): ?><tr>
                        <td><?= escapar((string) $a['nombre_completo']) ?></td>
                        <td><?= escapar((string) ($a['contacto_responsable'] ?? '—')) ?></td>
                        <td><?= (int) $a['activo'] === 1 ? 'Activo' : 'Inactivo' ?></td>
                        <td class="catequesis-acciones"><a class="btn btn-sm btn-outline-secondary" href="?tab=alumnos&editar=<?= (int) $a['id'] ?>">Editar</a>
                            <form method="post"><input type="hidden" name="csrf_token" value="<?= escapar($csrf) ?>">
                                <input type="hidden" name="accion" value="alumno_estado"><input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
                                <input type="hidden" name="activo" value="<?= (int) $a['activo'] === 1 ? '0' : '1' ?>">
                                <button class="btn btn-sm btn-outline-secondary"><?= (int) $a['activo'] === 1 ? 'Desactivar' : 'Activar' ?></button></form></td>
                    </tr><?php endforeach; ?></tbody>
                </table></div>
                <?php if (!$alumnos): ?><p class="text-secondary">No hay alumnos registrados con ese criterio.</p><?php endif; ?>
            </section>

        <?php elseif ($tab === 'catequistas' && $admin): ?>
            <section class="catequesis-panel">
                <h2><?= $editarCatequista ? 'Editar catequista' : 'Registrar catequista' ?></h2>
                <form method="post" class="row g-3"><input type="hidden" name="csrf_token" value="<?= escapar($csrf) ?>">
                    <input type="hidden" name="accion" value="catequista_guardar">
                    <input type="hidden" name="id" value="<?= (int) ($editarCatequista['id'] ?? 0) ?>">
                    <div class="col-md-7"><label class="form-label">Nombre completo *</label>
                        <input required maxlength="150" class="form-control" name="nombre" value="<?= escapar((string) ($editarCatequista['nombre_completo'] ?? '')) ?>"></div>
                    <div class="col-md-3"><label class="form-label">Teléfono (opcional)</label>
                        <input maxlength="25" class="form-control" name="telefono" value="<?= escapar((string) ($editarCatequista['telefono'] ?? '')) ?>"></div>
                    <div class="col-md-2 d-flex align-items-end"><button class="btn catequesis-boton w-100">Guardar</button></div>
                </form>
            </section>
            <section class="catequesis-panel"><h2>Listado de catequistas</h2>
                <div class="table-responsive"><table class="table table-hover align-middle"><thead><tr><th>Nombre</th><th>Teléfono</th><th>Estado</th><th>Acciones</th></tr></thead>
                <tbody><?php foreach ($catequistas as $c): ?><tr><td><?= escapar((string) $c['nombre_completo']) ?></td>
                    <td><?= escapar((string) ($c['telefono'] ?? '—')) ?></td><td><?= (int) $c['activo'] === 1 ? 'Activo' : 'Inactivo' ?></td>
                    <td class="catequesis-acciones"><a class="btn btn-sm btn-outline-secondary" href="?tab=catequistas&editar=<?= (int) $c['id'] ?>">Editar</a>
                        <form method="post"><input type="hidden" name="csrf_token" value="<?= escapar($csrf) ?>">
                            <input type="hidden" name="accion" value="catequista_estado"><input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                            <input type="hidden" name="activo" value="<?= (int) $c['activo'] === 1 ? '0' : '1' ?>">
                            <button class="btn btn-sm btn-outline-secondary"><?= (int) $c['activo'] === 1 ? 'Desactivar' : 'Activar' ?></button></form></td></tr><?php endforeach; ?></tbody>
                </table></div></section>

        <?php elseif ($tab === 'grupos'): ?>
            <?php if ($admin): ?>
                <section class="catequesis-panel"><h2><?= $editarGrupo ? 'Editar grupo' : 'Crear grupo' ?></h2>
                    <form method="post" class="row g-3 align-items-end">
                        <input type="hidden" name="csrf_token" value="<?= escapar($csrf) ?>"><input type="hidden" name="accion" value="grupo_guardar">
                        <input type="hidden" name="id" value="<?= (int) ($editarGrupo['id'] ?? 0) ?>">
                        <div class="col-md-3"><label class="form-label">Nombre del grupo</label>
                            <input required maxlength="120" class="form-control" name="nombre" value="<?= escapar((string) ($editarGrupo['nombre'] ?? '')) ?>" placeholder="Ej. Sábado A"></div>
                        <div class="col-md-3"><label class="form-label">Nivel</label><select required class="form-select" name="nivel_id">
                            <?php foreach ($niveles as $n): ?><option value="<?= (int) $n['id'] ?>" <?= (int) ($editarGrupo['nivel_id'] ?? 0) === (int) $n['id'] ? 'selected' : '' ?>><?= escapar((string) $n['nombre']) ?></option><?php endforeach; ?>
                        </select></div>
                        <div class="col-md-3"><label class="form-label">Catequista</label><select class="form-select" name="catequista_id"><option value="">Sin asignar</option>
                            <?php foreach ($catequistas as $c): ?><?php if ((int) $c['activo'] === 1 || (int) ($editarGrupo['catequista_id'] ?? 0) === (int) $c['id']): ?>
                                <option value="<?= (int) $c['id'] ?>" <?= (int) ($editarGrupo['catequista_id'] ?? 0) === (int) $c['id'] ? 'selected' : '' ?>><?= escapar((string) $c['nombre_completo']) ?></option>
                            <?php endif; ?><?php endforeach; ?>
                        </select></div>
                        <div class="col-md-2"><label class="form-label">Año</label><input type="number" class="form-control" name="anio" required min="2020" max="2100" value="<?= (int) ($editarGrupo['anio'] ?? date('Y')) ?>"></div>
                        <div class="col-md-1"><button class="btn catequesis-boton w-100">Guardar</button></div>
                    </form>
                </section>
            <?php endif; ?>
            <section class="catequesis-panel"><h2>Grupos registrados</h2>
                <div class="table-responsive"><table class="table table-hover align-middle"><thead><tr><th>Año</th><th>Grupo</th><th>Nivel</th><th>Catequista</th><th>Estado</th><th>Acciones</th></tr></thead>
                <tbody><?php foreach ($grupos as $g): ?><tr>
                    <td><?= (int) $g['anio'] ?></td><td><?= escapar((string) $g['nombre']) ?></td><td><?= escapar((string) $g['nivel']) ?></td>
                    <td><?= escapar((string) ($g['catequista'] ?? 'Sin asignar')) ?></td><td><?= (int) $g['activo'] === 1 ? 'Activo' : 'Inactivo' ?></td>
                    <td class="catequesis-acciones">
                        <a class="btn btn-sm btn-outline-success" href="?tab=grupos&grupo=<?= (int) $g['id'] ?>">Inscribir / Ver</a>
                        <?php if ($admin): ?><a class="btn btn-sm btn-outline-secondary" href="?tab=grupos&grupo=<?= (int) $g['id'] ?>&editar=<?= (int) $g['id'] ?>">Editar</a>
                            <form method="post"><input type="hidden" name="csrf_token" value="<?= escapar($csrf) ?>">
                                <input type="hidden" name="accion" value="grupo_estado"><input type="hidden" name="id" value="<?= (int) $g['id'] ?>">
                                <input type="hidden" name="activo" value="<?= (int) $g['activo'] === 1 ? '0' : '1' ?>">
                                <button class="btn btn-sm btn-outline-secondary"><?= (int) $g['activo'] === 1 ? 'Desactivar' : 'Activar' ?></button></form>
                        <?php endif; ?>
                    </td></tr><?php endforeach; ?></tbody></table></div>
            </section>
            <?php if ($grupoId > 0): ?>
                <section class="catequesis-panel"><h2>Inscripciones al grupo #<?= $grupoId ?></h2>
                    <form method="post" class="row g-3 align-items-end mb-4">
                        <input type="hidden" name="csrf_token" value="<?= escapar($csrf) ?>"><input type="hidden" name="accion" value="inscribir">
                        <input type="hidden" name="grupo_id" value="<?= $grupoId ?>">
                        <div class="col-md-8"><label class="form-label">Seleccionar alumno</label>
                            <select required name="alumno_id" class="form-select"><option value="">Elige un alumno activo</option>
                                <?php foreach ($alumnos as $a): ?><?php if ((int) $a['activo'] === 1): ?><option value="<?= (int) $a['id'] ?>"><?= escapar((string) $a['nombre_completo']) ?></option><?php endif; ?><?php endforeach; ?>
                            </select></div><div class="col-md-4"><button class="btn catequesis-boton">Inscribir</button></div>
                    </form>
                    <div class="table-responsive"><table class="table table-hover align-middle"><thead><tr><th>Alumno</th><th>Inscripción</th><th>Estado</th><th>Cambiar estado</th></tr></thead>
                        <tbody><?php foreach ($inscripciones as $i): ?><tr><td><?= escapar((string) $i['alumno']) ?></td><td><?= escapar((string) $i['fecha_inscripcion']) ?></td>
                            <td><?= escapar((string) $i['estado']) ?></td><td><form method="post" class="d-flex flex-wrap gap-2">
                                <input type="hidden" name="csrf_token" value="<?= escapar($csrf) ?>"><input type="hidden" name="accion" value="inscripcion_estado">
                                <input type="hidden" name="id" value="<?= (int) $i['id'] ?>">
                                <select class="form-select form-select-sm catequesis-estado" name="estado">
                                <?php foreach (['Activa','Retirada','Finalizada'] as $estado): ?><option <?= $i['estado'] === $estado ? 'selected' : '' ?>><?= $estado ?></option><?php endforeach; ?></select>
                                <button class="btn btn-sm btn-outline-secondary">Guardar</button></form></td></tr><?php endforeach; ?></tbody>
                    </table></div>
                </section>
            <?php endif; ?>

        <?php elseif (in_array($tab, ['evaluaciones', 'notas', 'asistencia'], true)): ?>
            <section class="catequesis-panel">
                <form method="get" class="row g-3 align-items-end">
                    <input type="hidden" name="tab" value="<?= escapar($tab) ?>">
                    <div class="col-md-8"><label class="form-label">Grupo de catequesis</label>
                        <select class="form-select" name="grupo" required><?php foreach ($grupos as $g): ?>
                            <option value="<?= (int) $g['id'] ?>" <?= $grupoId === (int) $g['id'] ? 'selected' : '' ?>><?= escapar($g['nivel'] . ' · ' . $g['nombre'] . ' (' . $g['anio'] . ')') ?></option>
                        <?php endforeach; ?></select></div>
                    <div class="col-md-4"><button class="btn catequesis-boton">Consultar grupo</button></div>
                </form>
            </section>
            <?php if ($grupoId === 0): ?>
                <div class="alert alert-info">Crea un grupo en la pestaña Grupos antes de registrar clases o notas.</div>
            <?php elseif ($tab === 'evaluaciones'): ?>
                <?php if ($admin): ?><section class="catequesis-panel"><h2>Nueva evaluación</h2>
                    <form method="post" class="row g-3 align-items-end">
                        <input type="hidden" name="csrf_token" value="<?= escapar($csrf) ?>"><input type="hidden" name="accion" value="evaluacion_crear">
                        <input type="hidden" name="grupo_id" value="<?= $grupoId ?>">
                        <div class="col-md-6"><label class="form-label">Nombre de la evaluación</label><input class="form-control" maxlength="140" required name="titulo" placeholder="Ej. Evaluación del primer tema"></div>
                        <div class="col-md-3"><label class="form-label">Fecha</label><input class="form-control" required type="date" name="fecha" value="<?= escapar(date('Y-m-d')) ?>"></div>
                        <div class="col-md-3"><button class="btn catequesis-boton">Crear evaluación</button></div>
                    </form></section><?php endif; ?>
                <section class="catequesis-panel"><h2>Evaluaciones del grupo</h2>
                    <div class="table-responsive"><table class="table"><thead><tr><th>Fecha</th><th>Evaluación</th><th>Calificaciones</th></tr></thead><tbody>
                        <?php foreach ($evaluaciones as $ev): ?><tr><td><?= escapar((string) $ev['fecha']) ?></td>
                            <td><?= escapar((string) $ev['titulo']) ?></td><td><a href="?tab=notas&grupo=<?= $grupoId ?>&evaluacion=<?= (int) $ev['id'] ?>">Ingresar notas</a></td></tr><?php endforeach; ?>
                    </tbody></table></div></section>
            <?php elseif ($tab === 'notas'): ?>
                <?php if ($evaluaciones): ?>
                    <section class="catequesis-panel"><h2>Registrar calificaciones</h2>
                        <form method="get" class="row g-3 align-items-end mb-3"><input type="hidden" name="tab" value="notas"><input type="hidden" name="grupo" value="<?= $grupoId ?>">
                            <div class="col-md-8"><label class="form-label">Evaluación</label><select class="form-select" name="evaluacion">
                                <?php foreach ($evaluaciones as $ev): ?><option value="<?= (int) $ev['id'] ?>" <?= $evaluacionId === (int) $ev['id'] ? 'selected' : '' ?>><?= escapar((string) $ev['titulo'] . ' · ' . $ev['fecha']) ?></option><?php endforeach; ?>
                            </select></div><div class="col-md-4"><button class="btn btn-outline-secondary">Seleccionar</button></div>
                        </form>
                        <form method="post"><input type="hidden" name="csrf_token" value="<?= escapar($csrf) ?>"><input type="hidden" name="accion" value="notas_guardar">
                            <input type="hidden" name="grupo_id" value="<?= $grupoId ?>"><input type="hidden" name="evaluacion_id" value="<?= $evaluacionId ?>">
                            <div class="table-responsive"><table class="table align-middle"><thead><tr><th>Alumno activo</th><th>Nota (0 a 10)</th></tr></thead><tbody>
                                <?php foreach ($inscripciones as $i): ?><?php if ($i['estado'] !== 'Activa' || (int) $i['alumno_activo'] !== 1) { continue; } ?>
                                    <tr><td><?= escapar((string) $i['alumno']) ?></td><td><input class="form-control catequesis-nota" aria-label="Nota de <?= escapar((string) $i['alumno']) ?>" type="number" min="0" max="10" step="0.01" name="notas[<?= (int) $i['id'] ?>]" value="<?= escapar((string) ($notasActuales[(int) $i['id']] ?? '')) ?>" placeholder="Sin calificar"></td></tr>
                                <?php endforeach; ?>
                            </tbody></table></div>
                            <p class="small text-secondary">Las notas vacías quedan sin modificar. Escala de 0 a 10, con dos decimales.</p>
                            <button class="btn catequesis-boton">Guardar notas</button>
                        </form>
                    </section>
                    <section class="catequesis-panel"><div class="catequesis-barra"><h2>Promedios del grupo</h2>
                        <a class="btn btn-outline-secondary btn-sm" href="catequesis_exportar.php?grupo=<?= $grupoId ?>">Exportar CSV</a></div>
                        <p class="small text-secondary">El promedio es parcial hasta que el alumno tenga registradas las <?= count($evaluaciones) ?> evaluaciones del grupo.</p>
                        <div class="table-responsive"><table class="table table-hover"><thead><tr><th>Alumno</th><th>Evaluaciones calificadas</th><th>Promedio de notas registradas</th></tr></thead><tbody>
                            <?php foreach ($promedios as $r): ?><tr><td><?= escapar((string) $r['alumno']) ?></td>
                                <td><?= (int) $r['cantidad_notas'] ?> / <?= count($evaluaciones) ?></td>
                                <td><?= $r['promedio'] !== null ? number_format((float) $r['promedio'], 2, '.', '') : 'Sin notas' ?></td></tr><?php endforeach; ?>
                        </tbody></table></div>
                    </section>
                <?php else: ?><div class="alert alert-info">Todavía no se han creado evaluaciones para este grupo.</div><?php endif; ?>
            <?php elseif ($tab === 'asistencia'): ?>
                <section class="catequesis-panel"><h2>Control de asistencia</h2>
                    <form method="get" class="row g-3 align-items-end mb-4"><input type="hidden" name="tab" value="asistencia"><input type="hidden" name="grupo" value="<?= $grupoId ?>">
                        <div class="col-md-5"><label class="form-label">Fecha de la clase</label><input required type="date" class="form-control" name="fecha" value="<?= escapar($fecha) ?>"></div>
                        <div class="col-md-3"><button class="btn btn-outline-secondary">Consultar fecha</button></div>
                    </form>
                    <form method="post"><input type="hidden" name="csrf_token" value="<?= escapar($csrf) ?>"><input type="hidden" name="accion" value="asistencia_guardar">
                        <input type="hidden" name="grupo_id" value="<?= $grupoId ?>"><input type="hidden" name="fecha" value="<?= escapar($fecha) ?>">
                        <div class="table-responsive"><table class="table align-middle"><thead><tr><th>Alumno activo</th><th>Asistencia</th></tr></thead><tbody>
                            <?php foreach ($inscripciones as $i): ?><?php if ($i['estado'] !== 'Activa' || (int) $i['alumno_activo'] !== 1) { continue; } ?>
                                <tr><td><?= escapar((string) $i['alumno']) ?></td><td><select class="form-select catequesis-asistencia" name="asistencias[<?= (int) $i['id'] ?>]">
                                    <option value="">Sin registrar</option>
                                    <?php foreach (['Presente','Ausente','Justificada'] as $opcion): ?>
                                        <option <?= ($asistencias[(int) $i['id']] ?? '') === $opcion ? 'selected' : '' ?>><?= escapar($opcion) ?></option>
                                    <?php endforeach; ?></select></td></tr>
                            <?php endforeach; ?>
                        </tbody></table></div><button class="btn catequesis-boton">Guardar asistencia</button>
                    </form>
                </section>
            <?php endif; ?>
        <?php endif; ?>
        <footer class="footer-note mt-4">Manos que Sirven · Iglesia de San Isidro, Cabañas</footer>
    </main>
</div>
</body>
</html>
