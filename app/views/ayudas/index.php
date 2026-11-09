<?php
declare(strict_types=1);

require_once __DIR__ . '/../../Controllers/AyudaController.php';
require_once __DIR__ . '/../../../config/app.php';

if (!isset($_SESSION['usuario_id']) || !is_numeric($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

$controlador = new AyudaController();

$nombre = (string) ($_SESSION['nombre_usuario'] ?? 'Usuario');
$rol = (string) ($_SESSION['rol'] ?? 'Sin rol');
$esAdministrador = $rol === 'Administrador';
$usuarioId = (int) $_SESSION['usuario_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? null;
    $accion = (string) ($_POST['accion'] ?? 'registrar');

    $idAyuda = filter_var(
        $_POST['ayuda_id'] ?? null,
        FILTER_VALIDATE_INT
    );

    if (!verificarTokenCSRF(is_string($token) ? $token : null)) {
        $resultado = [
            'exito' => false,
            'mensaje' => 'La sesión del formulario venció. Recargá la página e intentá nuevamente.',
        ];
    } elseif ($accion === 'eliminar') {
        if ($idAyuda === false || $idAyuda === null || $idAyuda <= 0) {
            $resultado = [
                'exito' => false,
                'mensaje' => 'El identificador de la ayuda no es válido.',
            ];
        } else {
            $resultado = $controlador->eliminar((int) $idAyuda);
        }
    } elseif ($accion === 'actualizar') {
        $resultado = $controlador->actualizar($_POST);
    } else {
        $resultado = $controlador->registrar($_POST, $usuarioId);
    }

    $_SESSION['mensaje_ayudas'] = $resultado;

    if (
        $accion === 'actualizar'
        && empty($resultado['exito'])
        && $idAyuda !== false
        && $idAyuda !== null
        && $idAyuda > 0
    ) {
        header('Location: ayudas.php?editar=' . (int) $idAyuda . '#formulario-ayuda');
    } else {
        header('Location: ayudas.php');
    }

    exit;
}

$mensaje = $_SESSION['mensaje_ayudas'] ?? null;
unset($_SESSION['mensaje_ayudas']);

$busqueda = trim((string) ($_GET['buscar'] ?? ''));

$beneficiarios = $controlador->obtenerBeneficiarios();
$ayudas = $controlador->listar($busqueda);
$resumen = $controlador->obtenerResumen();

$tiposAyuda = [
    'Alimentos',
    'Ropa',
    'Medicamentos',
    'Apoyo económico',
    'Útiles escolares',
    'Otros',
];

$fechaHoy = date('Y-m-d');
$fechaActual = date('d/m/Y');

$ayudaEditar = null;
$idEdicion = null;

if (isset($_GET['editar'])) {
    $idEdicion = filter_var($_GET['editar'], FILTER_VALIDATE_INT);

    if ($idEdicion === false || $idEdicion === null || $idEdicion <= 0) {
        $mensaje = [
            'exito' => false,
            'mensaje' => 'El identificador de la ayuda no es válido.',
        ];
    } else {
        $ayudaEditar = $controlador->obtenerPorId((int) $idEdicion);

        if ($ayudaEditar === null) {
            $mensaje = [
                'exito' => false,
                'mensaje' => 'La ayuda que intentás editar no existe.',
            ];
        }
    }
}

$modoEdicion = is_array($ayudaEditar);
$beneficiarioActualActivo = false;

// Indica al menú lateral independiente qué módulo está activo.
$paginaActiva = 'ayudas';

if ($modoEdicion) {
    foreach ($beneficiarios as $beneficiario) {
        if ((int) $beneficiario['id'] === (int) $ayudaEditar['beneficiario_id']) {
            $beneficiarioActualActivo = true;
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Ayudas entregadas | Manos que Sirven</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/sidebar.css">

    <style>
        .ayudas-contenido {
            width: 100%;
            min-width: 0;
        }

        .ayudas-resumen {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 18px;
            margin-bottom: 26px;
        }

        .ayudas-tarjeta,
        .ayudas-panel {
            padding: 22px;
            background: #ffffff;
            border: 1px solid #dfe8e3;
            border-radius: 10px;
        }

        .ayudas-tarjeta .etiqueta {
            display: block;
            margin-bottom: 10px;
            color: #66736d;
            font-size: 14px;
        }

        .ayudas-tarjeta .valor {
            display: block;
            margin: 0;
            color: #245b48;
            font-size: 28px;
            font-weight: 700;
        }

        .ayudas-panel {
            margin-bottom: 25px;
        }

        .ayudas-panel h2 {
            margin: 0 0 20px;
            color: #263238;
            font-size: 20px;
            font-weight: 700;
        }

        .ayudas-formulario {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
        }

        .ayudas-campo {
            display: flex;
            flex-direction: column;
            gap: 7px;
            min-width: 0;
        }

        .ayudas-campo.completo {
            grid-column: 1 / -1;
        }

        .ayudas-campo label {
            color: #263238;
            font-size: 14px;
            font-weight: 600;
        }

        .ayudas-campo input,
        .ayudas-campo select,
        .ayudas-campo textarea {
            width: 100%;
            padding: 11px 12px;
            border: 1px solid #ccd8d1;
            border-radius: 6px;
            background: #ffffff;
            color: #263238;
            font: inherit;
        }

        .ayudas-campo textarea {
            min-height: 90px;
            resize: vertical;
        }

        .ayudas-campo input:focus,
        .ayudas-campo select:focus,
        .ayudas-campo textarea:focus,
        .ayudas-busqueda input:focus {
            border-color: #245b48;
            outline: 2px solid rgba(36, 91, 72, 0.12);
        }

        .ayudas-boton {
            display: inline-block;
            padding: 11px 18px;
            border: 1px solid #245b48;
            border-radius: 6px;
            background: #245b48;
            color: #ffffff;
            font: inherit;
            text-align: center;
            text-decoration: none;
            cursor: pointer;
        }

        .ayudas-boton:hover {
            border-color: #194735;
            background: #194735;
            color: #ffffff;
        }

        .ayudas-boton-secundario {
            border-color: #ccd8d1;
            background: #ffffff;
            color: #263238;
        }

        .ayudas-boton-secundario:hover {
            border-color: #aabbb1;
            background: #f4f7f6;
            color: #263238;
        }

        .ayudas-boton-pequeno {
            padding: 7px 12px;
            font-size: 13px;
            white-space: nowrap;
        }

        .ayudas-boton-eliminar {
            border-color: #a83232;
            background: #a83232;
            color: #ffffff;
        }

        .ayudas-boton-eliminar:hover {
            border-color: #852626;
            background: #852626;
            color: #ffffff;
        }

        .ayudas-acciones {
            white-space: nowrap;
        }

        .ayudas-form-eliminar {
            display: inline-block;
            margin: 4px 0 0;
        }

        .ayudas-mensaje {
            margin-bottom: 20px;
            padding: 14px 16px;
            border: 1px solid #b8d8c5;
            border-radius: 6px;
            background: #edf7f0;
            color: #245b48;
        }

        .ayudas-mensaje.error {
            border-color: #e2b7b7;
            background: #fff0f0;
            color: #8a2424;
        }

        .ayudas-busqueda {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .ayudas-busqueda input {
            flex: 1;
            min-width: 0;
            padding: 11px 12px;
            border: 1px solid #ccd8d1;
            border-radius: 6px;
            font: inherit;
        }

        .ayudas-tabla-contenedor {
            width: 100%;
            overflow-x: auto;
        }

        .ayudas-tabla {
            width: 100%;
            border-collapse: collapse;
            background: #ffffff;
        }

        .ayudas-tabla th,
        .ayudas-tabla td {
            padding: 12px;
            border: 1px solid #dfe5e1;
            text-align: left;
            vertical-align: top;
            overflow-wrap: anywhere;
        }

        .ayudas-tabla th {
            background: #edf3ef;
            color: #263238;
            font-size: 13px;
        }

        .ayudas-tabla td {
            font-size: 14px;
        }

        .ayudas-vacio {
            padding: 24px !important;
            color: #66736d;
            text-align: center !important;
        }

        .ayudas-nota {
            margin: 0 0 18px;
            color: #66736d;
            font-size: 14px;
        }

        .ayudas-botones-formulario {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        @media (max-width: 900px) {
            .ayudas-resumen {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 600px) {
            .ayudas-formulario {
                grid-template-columns: 1fr;
            }

            .ayudas-campo.completo {
                grid-column: auto;
            }

            .ayudas-busqueda {
                flex-direction: column;
            }

            .ayudas-panel {
                padding: 16px;
            }
        }
    </style>
</head>
<body>

<div class="app-layout">

    <!-- Menú lateral independiente -->
    <?php require_once __DIR__ . '/../layout/sidebar.php'; ?>

    <main class="main-content">

        <header class="topbar">
            <div>
                <h1 class="page-heading">Ayudas entregadas</h1>
                <p class="page-subtitle">
                    Registro y seguimiento de las ayudas proporcionadas
                    a los beneficiarios.
                </p>
            </div>

            <div class="date-label">
                <?= escapar($fechaActual) ?>
            </div>
        </header>

        <div class="ayudas-contenido">

            <?php if (is_array($mensaje)): ?>
                <div
                    class="ayudas-mensaje <?= !empty($mensaje['exito']) ? '' : 'error' ?>"
                    role="status"
                >
                    <?= escapar((string) ($mensaje['mensaje'] ?? '')) ?>
                </div>
            <?php endif; ?>

            <section aria-labelledby="resumen-ayudas-titulo">
                <h2 class="section-heading" id="resumen-ayudas-titulo">
                    Resumen de ayudas
                </h2>

                <div class="ayudas-resumen">
                    <article class="ayudas-tarjeta">
                        <span class="etiqueta">Total de ayudas registradas</span>
                        <p class="valor">
                            <?= (int) $resumen['total_ayudas'] ?>
                        </p>
                    </article>

                    <article class="ayudas-tarjeta">
                        <span class="etiqueta">Total de unidades entregadas</span>
                        <p class="valor">
                            <?= (int) $resumen['total_unidades'] ?>
                        </p>
                    </article>

                    <article class="ayudas-tarjeta">
                        <span class="etiqueta">Beneficiarios atendidos</span>
                        <p class="valor">
                            <?= (int) $resumen['beneficiarios_atendidos'] ?>
                        </p>
                    </article>
                </div>
            </section>

            <section
                class="ayudas-panel"
                id="formulario-ayuda"
                aria-labelledby="registrar-ayuda-titulo"
            >
                <h2 id="registrar-ayuda-titulo">
                    <?= $modoEdicion
                        ? 'Editar ayuda entregada'
                        : 'Registrar ayuda entregada' ?>
                </h2>

                <p class="ayudas-nota">
                    <?php if ($modoEdicion): ?>
                        Modificá los datos necesarios y guardá los cambios.
                    <?php else: ?>
                        Registrá únicamente las ayudas que ya fueron entregadas.
                    <?php endif; ?>
                </p>

                <?php if (count($beneficiarios) > 0 || $modoEdicion): ?>
                    <form
                        method="POST"
                        action="<?= $modoEdicion
                            ? 'ayudas.php?editar=' . (int) $ayudaEditar['id'] . '#formulario-ayuda'
                            : 'ayudas.php#formulario-ayuda' ?>"
                        class="ayudas-formulario"
                    >
                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= escapar(generarTokenCSRF()) ?>"
                        >

                        <input
                            type="hidden"
                            name="accion"
                            value="<?= $modoEdicion ? 'actualizar' : 'registrar' ?>"
                        >

                        <?php if ($modoEdicion): ?>
                            <input
                                type="hidden"
                                name="ayuda_id"
                                value="<?= (int) $ayudaEditar['id'] ?>"
                            >
                        <?php endif; ?>

                        <div class="ayudas-campo">
                            <label for="beneficiario_id">Beneficiario *</label>

                            <select
                                id="beneficiario_id"
                                name="beneficiario_id"
                                required
                            >
                                <option value="">Seleccioná un beneficiario</option>

                                <?php if ($modoEdicion && !$beneficiarioActualActivo): ?>
                                    <option
                                        value="<?= (int) $ayudaEditar['beneficiario_id'] ?>"
                                        selected
                                    >
                                        <?= escapar((string) $ayudaEditar['beneficiario']) ?>
                                        — Beneficiario actual inactivo
                                    </option>
                                <?php endif; ?>

                                <?php foreach ($beneficiarios as $beneficiario): ?>
                                    <option
                                        value="<?= (int) $beneficiario['id'] ?>"
                                        <?= $modoEdicion
                                            && (int) $beneficiario['id'] === (int) $ayudaEditar['beneficiario_id']
                                            ? 'selected'
                                            : '' ?>
                                    >
                                        <?= escapar($beneficiario['nombre_completo']) ?>
                                        <?php if (!empty($beneficiario['comunidad'])): ?>
                                            — <?= escapar($beneficiario['comunidad']) ?>
                                        <?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <?php if ($modoEdicion && !$beneficiarioActualActivo): ?>
                                <small>
                                    El beneficiario actual está inactivo.
                                    Para guardar los cambios, seleccioná uno activo.
                                </small>
                            <?php endif; ?>
                        </div>

                        <div class="ayudas-campo">
                            <label for="tipo_ayuda">Tipo de ayuda *</label>

                            <select
                                id="tipo_ayuda"
                                name="tipo_ayuda"
                                required
                            >
                                <option value="">Seleccioná el tipo</option>

                                <?php foreach ($tiposAyuda as $tipo): ?>
                                    <option
                                        value="<?= escapar($tipo) ?>"
                                        <?= $modoEdicion
                                            && $ayudaEditar['tipo_ayuda'] === $tipo
                                            ? 'selected'
                                            : '' ?>
                                    >
                                        <?= escapar($tipo) ?>
                                    </option>
                                <?php endforeach; ?>

                                <?php
                                $tipoActual = $modoEdicion
                                    ? (string) $ayudaEditar['tipo_ayuda']
                                    : '';
                                ?>

                                <?php if (
                                    $modoEdicion
                                    && $tipoActual !== ''
                                    && !in_array($tipoActual, $tiposAyuda, true)
                                ): ?>
                                    <option
                                        value="<?= escapar($tipoActual) ?>"
                                        selected
                                    >
                                        <?= escapar($tipoActual) ?>
                                    </option>
                                <?php endif; ?>
                            </select>
                        </div>

                        <div class="ayudas-campo">
                            <label for="cantidad">Cantidad *</label>

                            <input
                                type="number"
                                id="cantidad"
                                name="cantidad"
                                min="1"
                                step="1"
                                value="<?= $modoEdicion
                                    ? (int) $ayudaEditar['cantidad']
                                    : 1 ?>"
                                required
                            >
                        </div>

                        <div class="ayudas-campo">
                            <label for="fecha_entrega">Fecha de entrega *</label>

                            <input
                                type="date"
                                id="fecha_entrega"
                                name="fecha_entrega"
                                max="<?= escapar($fechaHoy) ?>"
                                value="<?= escapar(
                                    $modoEdicion
                                        ? (string) $ayudaEditar['fecha_entrega']
                                        : $fechaHoy
                                ) ?>"
                                required
                            >
                        </div>

                        <div class="ayudas-campo completo">
                            <label for="descripcion">
                                Descripción de la ayuda *
                            </label>

                            <textarea
                                id="descripcion"
                                name="descripcion"
                                maxlength="5000"
                                placeholder="Describí los productos, artículos o apoyo entregado."
                                required
                            ><?= $modoEdicion
                                ? escapar((string) $ayudaEditar['descripcion'])
                                : '' ?></textarea>
                        </div>

                        <div class="ayudas-campo completo">
                            <label for="observaciones">Observaciones</label>

                            <textarea
                                id="observaciones"
                                name="observaciones"
                                maxlength="5000"
                                placeholder="Información adicional (opcional)."
                            ><?= $modoEdicion
                                ? escapar((string) ($ayudaEditar['observaciones'] ?? ''))
                                : '' ?></textarea>
                        </div>

                        <div class="ayudas-campo completo">
                            <div class="ayudas-botones-formulario">
                                <button type="submit" class="ayudas-boton">
                                    <?= $modoEdicion
                                        ? 'Guardar cambios'
                                        : 'Registrar ayuda' ?>
                                </button>

                                <?php if ($modoEdicion): ?>
                                    <a
                                        href="ayudas.php"
                                        class="ayudas-boton ayudas-boton-secundario"
                                    >
                                        Cancelar edición
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </form>
                <?php else: ?>
                    <p>
                        No hay beneficiarios activos disponibles.
                        Registrá o activá un beneficiario antes de entregar una ayuda.
                    </p>
                <?php endif; ?>
            </section>

            <section
                class="ayudas-panel"
                aria-labelledby="historial-ayudas-titulo"
            >
                <h2 id="historial-ayudas-titulo">
                    Historial de ayudas
                </h2>

                <form method="GET" action="ayudas.php" class="ayudas-busqueda">
                    <input
                        type="search"
                        name="buscar"
                        value="<?= escapar($busqueda) ?>"
                        placeholder="Buscar por beneficiario, comunidad o tipo de ayuda"
                        aria-label="Buscar ayudas"
                    >

                    <button type="submit" class="ayudas-boton">
                        Buscar
                    </button>

                    <a
                        class="ayudas-boton ayudas-boton-secundario"
                        href="ayudas.php"
                    >
                        Limpiar
                    </a>
                </form>

                <div class="ayudas-tabla-contenedor">
                    <table class="ayudas-tabla">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Beneficiario</th>
                                <th>Comunidad</th>
                                <th>Tipo</th>
                                <th>Descripción</th>
                                <th>Cantidad</th>
                                <th>Observaciones</th>
                                <th>Registrado por</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php if (count($ayudas) > 0): ?>
                                <?php foreach ($ayudas as $ayuda): ?>
                                    <tr>
                                        <td>
                                            <?= escapar((string) $ayuda['fecha_entrega']) ?>
                                        </td>

                                        <td>
                                            <?= escapar((string) $ayuda['beneficiario']) ?>
                                        </td>

                                        <td>
                                            <?= escapar((string) ($ayuda['comunidad'] ?? '')) ?>
                                        </td>

                                        <td>
                                            <?= escapar((string) $ayuda['tipo_ayuda']) ?>
                                        </td>

                                        <td>
                                            <?= nl2br(escapar((string) $ayuda['descripcion'])) ?>
                                        </td>

                                        <td>
                                            <?= (int) $ayuda['cantidad'] ?>
                                        </td>

                                        <td>
                                            <?= nl2br(escapar((string) ($ayuda['observaciones'] ?? ''))) ?>
                                        </td>

                                        <td>
                                            <?= escapar((string) ($ayuda['registrado_por'] ?? 'No disponible')) ?>
                                        </td>

                                        <td class="ayudas-acciones">
                                            <a
                                                href="ayudas.php?editar=<?= (int) $ayuda['id'] ?>#formulario-ayuda"
                                                class="ayudas-boton ayudas-boton-pequeno"
                                            >
                                                Editar
                                            </a>

                                            <form
                                                method="POST"
                                                action="ayudas.php"
                                                class="ayudas-form-eliminar"
                                                onsubmit="return confirm('¿Estás seguro de que deseas eliminar esta ayuda? Esta acción no se puede deshacer.');"
                                            >
                                                <input
                                                    type="hidden"
                                                    name="csrf_token"
                                                    value="<?= escapar(generarTokenCSRF()) ?>"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="accion"
                                                    value="eliminar"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="ayuda_id"
                                                    value="<?= (int) $ayuda['id'] ?>"
                                                >

                                                <button
                                                    type="submit"
                                                    class="ayudas-boton ayudas-boton-pequeno ayudas-boton-eliminar"
                                                >
                                                    Eliminar
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" class="ayudas-vacio">
                                        No se encontraron ayudas registradas.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

        </div>

        <footer class="footer-note">
            Manos que Sirven · Iglesia de San Isidro, Cabañas
        </footer>

    </main>
</div>

</body>
</html>