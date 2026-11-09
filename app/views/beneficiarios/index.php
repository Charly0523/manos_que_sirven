<?php
$beneficiarios = $beneficiarios ?? [];
$busqueda = $busqueda ?? '';
$mensaje = $mensaje ?? '';

$tipoMensaje = in_array(
    ($tipoMensaje ?? 'success'),
    ['success', 'danger', 'warning', 'info'],
    true
) ? ($tipoMensaje ?? 'success') : 'success';

$resumen = $resumen ?? ['activos' => 0, 'inactivos' => 0];
$accion = $accion ?? '';
$beneficiarioEditar = $beneficiarioEditar ?? null;

$mostrarFormulario = in_array($accion, ['nuevo', 'editar'], true);
$esEdicion = $accion === 'editar' && $beneficiarioEditar !== null;

$datosFormulario = $beneficiarioEditar ?? [
    'id' => '',
    'nombre_completo' => '',
    'telefono' => '',
    'direccion' => '',
    'comunidad' => '',
];

$nombreUsuario = $nombreUsuario ?? (string) ($_SESSION['nombre_usuario'] ?? 'Usuario');
$rolUsuario = $rolUsuario ?? (string) ($_SESSION['rol'] ?? 'Usuario');

$totalBeneficiarios = count($beneficiarios);

// Identifica la opción que debe aparecer seleccionada en el menú lateral.
$paginaActiva = 'beneficiarios';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#176b4d">

    <title>Beneficiarios | Manos que Sirven</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">

    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/beneficiarios.css">
    <link rel="stylesheet" href="assets/css/sidebar.css">
</head>
<body>

<div class="app-layout">

    <!-- Menú lateral independiente y reutilizable -->
    <?php require_once __DIR__ . '/../layout/sidebar.php'; ?>

    <!-- Contenido principal -->
    <main class="main-content">

        <section class="contenido-modulo">

            <div class="encabezado-modulo">
                <div>
                    <div class="etiqueta-modulo">
                        REGISTRO SOCIAL
                    </div>

                    <h2>Gestión de beneficiarios</h2>

                    <p>
                        Administra la información de las personas que reciben
                        apoyo de la iglesia.
                    </p>
                </div>

                <a href="beneficiarios.php?accion=nuevo"
                   class="boton-principal">
                    <span aria-hidden="true">+</span>
                    Nuevo beneficiario
                </a>
            </div>

            <?php if ($mensaje !== ''): ?>
                <div class="alert alert-<?= escapar($tipoMensaje) ?> alert-dismissible fade show"
                     role="alert">
                    <?= escapar($mensaje) ?>

                    <button type="button"
                            class="btn-close"
                            data-bs-dismiss="alert"
                            aria-label="Cerrar"></button>
                </div>
            <?php endif; ?>

            <!-- Indicadores -->
            <div class="indicadores">
                <article class="indicador">
                    <div class="indicador-cabecera">
                        <span>Beneficiarios activos</span>
                        <span class="indicador-icono verde">✓</span>
                    </div>

                    <div class="indicador-numero">
                        <?= (int) $resumen['activos'] ?>
                    </div>

                    <div class="indicador-pie">
                        Personas habilitadas en el registro
                    </div>
                </article>

                <article class="indicador">
                    <div class="indicador-cabecera">
                        <span>Beneficiarios inactivos</span>
                        <span class="indicador-icono gris">−</span>
                    </div>

                    <div class="indicador-numero">
                        <?= (int) $resumen['inactivos'] ?>
                    </div>

                    <div class="indicador-pie">
                        Personas actualmente inactivas
                    </div>
                </article>

                <article class="indicador indicador-total">
                    <div class="indicador-cabecera">
                        <span>Resultados de esta consulta</span>
                        <span class="indicador-icono claro">#</span>
                    </div>

                    <div class="indicador-numero">
                        <?= $totalBeneficiarios ?>
                    </div>

                    <div class="indicador-pie">
                        Registros mostrados en la tabla
                    </div>
                </article>
            </div>

            <!-- Formulario de registro o edición -->
            <?php if ($mostrarFormulario): ?>
                <section class="panel formulario-panel" id="formulario">
                    <div class="panel-encabezado">
                        <div>
                            <h2>
                                <?= $esEdicion
                                    ? 'Editar beneficiario'
                                    : 'Registrar beneficiario' ?>
                            </h2>
                            <p>Completa los datos de la persona beneficiaria.</p>
                        </div>

                        <a href="beneficiarios.php"
                           class="boton-icono"
                           aria-label="Cerrar formulario">×</a>
                    </div>

                    <form action="beneficiarios.php"
                          method="POST"
                          class="formulario-contenido">

                        <input type="hidden"
                               name="csrf_token"
                               value="<?= escapar(generarTokenCSRF()) ?>">

                        <input type="hidden"
                               name="accion"
                               value="<?= $esEdicion ? 'actualizar' : 'registrar' ?>">

                        <?php if ($esEdicion): ?>
                            <input type="hidden"
                                   name="id"
                                   value="<?= (int) $datosFormulario['id'] ?>">
                        <?php endif; ?>

                        <div class="formulario-grid">
                            <div class="campo campo-completo">
                                <label for="nombre_completo">
                                    Nombre completo <span class="obligatorio">*</span>
                                </label>

                                <input type="text"
                                       id="nombre_completo"
                                       name="nombre_completo"
                                       maxlength="150"
                                       autocomplete="name"
                                       required
                                       value="<?= escapar((string) $datosFormulario['nombre_completo']) ?>"
                                       placeholder="Ej. María del Carmen López">
                            </div>

                            <div class="campo">
                                <label for="telefono">Teléfono</label>

                                <input type="tel"
                                       id="telefono"
                                       name="telefono"
                                       maxlength="25"
                                       autocomplete="tel"
                                       value="<?= escapar((string) ($datosFormulario['telefono'] ?? '')) ?>"
                                       placeholder="Ej. 7000-0000">
                            </div>

                            <div class="campo">
                                <label for="comunidad">Comunidad o cantón</label>

                                <input type="text"
                                       id="comunidad"
                                       name="comunidad"
                                       maxlength="120"
                                       value="<?= escapar((string) ($datosFormulario['comunidad'] ?? '')) ?>"
                                       placeholder="Nombre de la comunidad">
                            </div>

                            <div class="campo campo-completo">
                                <label for="direccion">Dirección</label>

                                <textarea id="direccion"
                                          name="direccion"
                                          rows="3"
                                          maxlength="255"
                                          placeholder="Dirección o referencia de residencia"><?= escapar((string) ($datosFormulario['direccion'] ?? '')) ?></textarea>
                            </div>
                        </div>

                        <div class="formulario-pie">
                            <a href="beneficiarios.php" class="boton-secundario">
                                Cancelar
                            </a>

                            <button type="submit" class="boton-principal">
                                <?= $esEdicion ? 'Guardar cambios' : 'Registrar beneficiario' ?>
                            </button>
                        </div>
                    </form>
                </section>
            <?php endif; ?>

            <!-- Listado de beneficiarios -->
            <section class="panel listado-panel">
                <div class="panel-encabezado encabezado-listado">
                    <div>
                        <h2>Listado de beneficiarios</h2>
                        <p>Consulta y administra los registros existentes.</p>
                    </div>

                    <span class="contador-registros">
                        <?= $totalBeneficiarios ?>
                        <?= $totalBeneficiarios === 1 ? 'registro' : 'registros' ?>
                    </span>
                </div>

                <div class="busqueda-contenedor">
                    <form action="beneficiarios.php"
                          method="GET"
                          class="formulario-busqueda">

                        <div class="busqueda-campo">
                            <span aria-hidden="true">⌕</span>

                            <input type="search"
                                   name="buscar"
                                   value="<?= escapar($busqueda) ?>"
                                   placeholder="Buscar por nombre, teléfono o comunidad"
                                   aria-label="Buscar beneficiarios">
                        </div>

                        <button type="submit" class="boton-buscar">Buscar</button>

                        <?php if ($busqueda !== ''): ?>
                            <a href="beneficiarios.php" class="boton-limpiar">
                                Limpiar
                            </a>
                        <?php endif; ?>
                    </form>
                </div>

                <div class="tabla-contenedor">
                    <table class="tabla-beneficiarios">
                        <thead>
                            <tr>
                                <th>Beneficiario</th>
                                <th>Teléfono</th>
                                <th>Comunidad</th>
                                <th>Estado</th>
                                <th>Registrado por</th>
                                <th class="columna-acciones">Acciones</th>
                            </tr>
                        </thead>

                        <tbody>
                        <?php if (empty($beneficiarios)): ?>
                            <tr>
                                <td colspan="6">
                                    <div class="estado-vacio">
                                        <strong>No hay beneficiarios para mostrar</strong>

                                        <p>
                                            <?= $busqueda !== ''
                                                ? 'Prueba con otro término de búsqueda.'
                                                : 'Puedes comenzar registrando a la primera persona.' ?>
                                        </p>

                                        <?php if ($busqueda === ''): ?>
                                            <a href="beneficiarios.php?accion=nuevo"
                                               class="boton-principal">
                                                Registrar beneficiario
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($beneficiarios as $beneficiario): ?>
                                <tr>
                                    <td>
                                        <div class="persona-tabla">
                                            <span class="persona-avatar">
                                                <?= escapar(mb_substr(
                                                    (string) $beneficiario['nombre_completo'],
                                                    0,
                                                    1,
                                                    'UTF-8'
                                                )) ?>
                                            </span>

                                            <div class="persona-texto">
                                                <strong>
                                                    <?= escapar((string) $beneficiario['nombre_completo']) ?>
                                                </strong>

                                                <?php if (!empty($beneficiario['direccion'])): ?>
                                                    <small>
                                                        <?= escapar((string) $beneficiario['direccion']) ?>
                                                    </small>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>

                                    <td>
                                        <?= escapar((string) ($beneficiario['telefono'] ?: '—')) ?>
                                    </td>

                                    <td>
                                        <?= escapar((string) ($beneficiario['comunidad'] ?: '—')) ?>
                                    </td>

                                    <td>
                                        <?php if ($beneficiario['estado'] === 'Activo'): ?>
                                            <span class="estado estado-activo">
                                                <span></span> Activo
                                            </span>
                                        <?php else: ?>
                                            <span class="estado estado-inactivo">
                                                <span></span> Inactivo
                                            </span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <?= escapar((string) ($beneficiario['registrado_por'] ?: '—')) ?>
                                    </td>

                                    <td>
                                        <div class="acciones-tabla">
                                            <a href="beneficiarios.php?accion=editar&id=<?= (int) $beneficiario['id'] ?>"
                                               class="accion-boton accion-editar"
                                               title="Editar beneficiario"
                                               aria-label="Editar beneficiario">
                                                Editar
                                            </a>

                                            <form action="beneficiarios.php"
                                                  method="POST"
                                                  onsubmit="return confirm('¿Deseas cambiar el estado de este beneficiario?');">

                                                <input type="hidden"
                                                       name="csrf_token"
                                                       value="<?= escapar(generarTokenCSRF()) ?>">

                                                <input type="hidden"
                                                       name="accion"
                                                       value="cambiar_estado">

                                                <input type="hidden"
                                                       name="id"
                                                       value="<?= (int) $beneficiario['id'] ?>">

                                                <input type="hidden"
                                                       name="estado"
                                                       value="<?= $beneficiario['estado'] === 'Activo' ? 'Inactivo' : 'Activo' ?>">

                                                <button type="submit"
                                                        class="accion-boton <?= $beneficiario['estado'] === 'Activo' ? 'accion-desactivar' : 'accion-activar' ?>">
                                                    <?= $beneficiario['estado'] === 'Activo' ? 'Desactivar' : 'Activar' ?>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="pie-tabla">
                    Los registros se conservan aunque el beneficiario pase a estado inactivo.
                </div>
            </section>

            <footer class="pie-pagina">
                Manos que Sirven · Iglesia de San Isidro, Cabañas
            </footer>

        </section>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>