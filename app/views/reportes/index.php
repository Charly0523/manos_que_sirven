<?php
declare(strict_types=1);

/**
 * Esta vista recibe sus variables desde public/reportes.php.
 * Se establecen valores predeterminados para evitar variables indefinidas.
 */

$periodo = isset($periodo) && is_string($periodo)
    ? $periodo
    : 'mes';

$fechaReferencia = isset($fechaReferencia) && is_string($fechaReferencia)
    ? $fechaReferencia
    : date('Y-m-d');

$tipoAyuda = isset($tipoAyuda) && is_string($tipoAyuda)
    ? $tipoAyuda
    : '';

$fechaActual = isset($fechaActual) && is_string($fechaActual)
    ? $fechaActual
    : date('Y-m-d');

$tiposAyuda = isset($tiposAyuda) && is_array($tiposAyuda)
    ? $tiposAyuda
    : [];

$nombrePeriodo = isset($nombrePeriodo) && is_string($nombrePeriodo)
    ? $nombrePeriodo
    : 'Mensual';

$errorReporte = isset($errorReporte) && is_string($errorReporte)
    ? $errorReporte
    : '';

$resumenGeneral = isset($resumenGeneral) && is_array($resumenGeneral)
    ? $resumenGeneral
    : [];

$reporte = isset($reporte) && is_array($reporte)
    ? $reporte
    : null;

$datosReporte = is_array($reporte) ? $reporte : null;
$datosPeriodo = $datosReporte['periodo'] ?? [];

$fechaDesde = $datosPeriodo['desde'] ?? '';
$fechaHasta = $datosPeriodo['hasta'] ?? '';

$parametrosDescarga = [
    'periodo' => $periodo,
    'fecha' => $fechaReferencia,
    'tipo_ayuda' => $tipoAyuda,
];

$urlCSV = 'descargar_reporte.php?' . http_build_query(
    array_merge($parametrosDescarga, ['formato' => 'csv'])
);

$urlPDF = 'descargar_reporte.php?' . http_build_query(
    array_merge($parametrosDescarga, ['formato' => 'pdf'])
);

$nombreUsuario = (string) ($_SESSION['nombre_usuario'] ?? 'Usuario');
$rolUsuario = (string) ($_SESSION['rol'] ?? 'Usuario');

// Identifica la opción activa del menú lateral independiente.
$paginaActiva = 'reportes';

function reportesFecha(string $fecha): string
{
    if ($fecha === '') {
        return 'No disponible';
    }

    $fechaObjeto = DateTimeImmutable::createFromFormat('!Y-m-d', $fecha);

    return $fechaObjeto
        ? $fechaObjeto->format('d/m/Y')
        : $fecha;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#176b4d">

    <title>Reportes | Manos que Sirven</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">

    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/beneficiarios.css">
    <link rel="stylesheet" href="assets/css/reportes.css">
    <link rel="stylesheet" href="assets/css/sidebar.css">
</head>
<body>

<div class="app-layout">

    <!-- Menú lateral independiente y reutilizable -->
    <?php require_once __DIR__ . '/../layout/sidebar.php'; ?>

    <main class="main-content">

        <section class="contenido-modulo">

            <div class="encabezado-modulo">
                <div>
                    <div class="etiqueta-modulo">INFORMES Y ESTADÍSTICAS</div>

                    <h2>Reportes de ayudas entregadas</h2>

                    <p>
                        Consultá los registros por día, semana, mes o año
                        y descargá los informes del período seleccionado.
                    </p>
                </div>
            </div>

            <?php if ($errorReporte !== ''): ?>
                <div class="alert alert-danger" role="alert">
                    <?= escapar($errorReporte) ?>
                </div>
            <?php endif; ?>

            <div class="indicadores reportes-indicadores">
                <article class="indicador">
                    <div class="indicador-cabecera">
                        <span>Total de beneficiarios</span>
                        <span class="indicador-icono verde">B</span>
                    </div>

                    <div class="indicador-numero">
                        <?= number_format(
                            (int) ($resumenGeneral['total_beneficiarios'] ?? 0)
                        ) ?>
                    </div>

                    <div class="indicador-pie">
                        Personas registradas en el sistema
                    </div>
                </article>

                <article class="indicador">
                    <div class="indicador-cabecera">
                        <span>Total de ayudas</span>
                        <span class="indicador-icono gris">A</span>
                    </div>

                    <div class="indicador-numero">
                        <?= number_format(
                            (int) ($resumenGeneral['total_ayudas'] ?? 0)
                        ) ?>
                    </div>

                    <div class="indicador-pie">
                        Registros de ayudas entregadas
                    </div>
                </article>

                <article class="indicador indicador-total">
                    <div class="indicador-cabecera">
                        <span>Unidades entregadas</span>
                        <span class="indicador-icono claro">U</span>
                    </div>

                    <div class="indicador-numero">
                        <?= number_format(
                            (int) ($resumenGeneral['total_unidades'] ?? 0)
                        ) ?>
                    </div>

                    <div class="indicador-pie">
                        Cantidades registradas en el sistema
                    </div>
                </article>
            </div>

            <section class="panel">
                <div class="panel-encabezado">
                    <div>
                        <h2>Generar reporte</h2>
                        <p>Seleccioná el período y el tipo de ayuda.</p>
                    </div>
                </div>

                <form action="reportes.php"
                      method="GET"
                      class="formulario-contenido">

                    <div class="formulario-grid">

                        <div class="campo">
                            <label for="periodo">Período del reporte</label>

                            <select id="periodo"
                                    name="periodo"
                                    class="form-select"
                                    required>
                                <option value="dia"
                                    <?= $periodo === 'dia' ? 'selected' : '' ?>>
                                    Diario
                                </option>

                                <option value="semana"
                                    <?= $periodo === 'semana' ? 'selected' : '' ?>>
                                    Semanal
                                </option>

                                <option value="mes"
                                    <?= $periodo === 'mes' ? 'selected' : '' ?>>
                                    Mensual
                                </option>

                                <option value="anio"
                                    <?= $periodo === 'anio' ? 'selected' : '' ?>>
                                    Anual
                                </option>
                            </select>
                        </div>

                        <div class="campo">
                            <label for="fecha">Fecha de referencia</label>

                            <input type="date"
                                   id="fecha"
                                   name="fecha"
                                   value="<?= escapar($fechaReferencia) ?>"
                                   max="<?= escapar($fechaActual) ?>"
                                   required>

                            <small>
                                El sistema calculará automáticamente
                                el rango correspondiente.
                            </small>
                        </div>

                        <div class="campo">
                            <label for="tipo_ayuda">Tipo de ayuda</label>

                            <select id="tipo_ayuda"
                                    name="tipo_ayuda"
                                    class="form-select">
                                <option value="">Todos los tipos</option>

                                <?php foreach ($tiposAyuda as $tipo): ?>
                                    <option
                                        value="<?= escapar((string) $tipo) ?>"
                                        <?= $tipoAyuda === $tipo ? 'selected' : '' ?>>
                                        <?= escapar((string) $tipo) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                    </div>

                    <div class="formulario-pie">
                        <a href="reportes.php" class="boton-secundario">
                            Limpiar filtros
                        </a>

                        <button type="submit" class="boton-principal">
                            Consultar reporte
                        </button>
                    </div>
                </form>
            </section>

            <?php if ($datosReporte !== null): ?>

                <section class="panel">

                    <div class="panel-encabezado">
                        <div>
                            <h2>
                                Resultado del reporte
                                <?= escapar($nombrePeriodo) ?>
                            </h2>

                            <p>
                                Del
                                <strong><?= escapar(reportesFecha((string) $fechaDesde)) ?></strong>
                                al
                                <strong><?= escapar(reportesFecha((string) $fechaHasta)) ?></strong>
                            </p>

                            <?php if ($tipoAyuda !== ''): ?>
                                <p>
                                    Tipo de ayuda:
                                    <strong><?= escapar($tipoAyuda) ?></strong>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="indicadores reportes-indicadores">
                        <article class="indicador">
                            <div class="indicador-cabecera">
                                <span>Registros de ayudas</span>
                                <span class="indicador-icono verde">R</span>
                            </div>

                            <div class="indicador-numero">
                                <?= number_format(
                                    (int) ($datosReporte['total_registros'] ?? 0)
                                ) ?>
                            </div>
                        </article>

                        <article class="indicador">
                            <div class="indicador-cabecera">
                                <span>Unidades entregadas</span>
                                <span class="indicador-icono gris">U</span>
                            </div>

                            <div class="indicador-numero">
                                <?= number_format(
                                    (int) ($datosReporte['total_unidades'] ?? 0)
                                ) ?>
                            </div>
                        </article>

                        <article class="indicador indicador-total">
                            <div class="indicador-cabecera">
                                <span>Beneficiarios atendidos</span>
                                <span class="indicador-icono claro">B</span>
                            </div>

                            <div class="indicador-numero">
                                <?= number_format(
                                    (int) ($datosReporte['beneficiarios_atendidos'] ?? 0)
                                ) ?>
                            </div>
                        </article>
                    </div>

                    <div class="panel-encabezado">
                        <div>
                            <h2>Descargar informe</h2>
                            <p>Exportá los resultados del período seleccionado.</p>
                        </div>
                    </div>

                    <div class="formulario-contenido">
                        <div class="acciones-tabla">
                            <a href="<?= escapar($urlCSV) ?>"
                               class="boton-principal">
                                Descargar CSV
                            </a>

                            <a href="<?= escapar($urlPDF) ?>"
                               class="boton-secundario">
                                Descargar PDF
                            </a>
                        </div>
                    </div>

                    <div class="panel-encabezado">
                        <div>
                            <h2>Detalle de ayudas entregadas</h2>
                            <p>Registros incluidos en el informe.</p>
                        </div>
                    </div>

                    <div class="tabla-contenedor">
                        <table class="tabla-beneficiarios">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Beneficiario</th>
                                    <th>Comunidad</th>
                                    <th>Tipo de ayuda</th>
                                    <th>Descripción</th>
                                    <th>Cantidad</th>
                                </tr>
                            </thead>

                            <tbody>
                            <?php if (empty($datosReporte['ayudas'])): ?>
                                <tr>
                                    <td colspan="6">
                                        <div class="estado-vacio">
                                            <strong>No hay ayudas registradas</strong>
                                            <p>
                                                No se encontraron resultados
                                                para el período seleccionado.
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($datosReporte['ayudas'] as $ayuda): ?>
                                    <tr>
                                        <td>
                                            <?= escapar(
                                                reportesFecha(
                                                    (string) ($ayuda['fecha_entrega'] ?? '')
                                                )
                                            ) ?>
                                        </td>

                                        <td>
                                            <?= escapar(
                                                (string) ($ayuda['beneficiario'] ?? '')
                                            ) ?>
                                        </td>

                                        <td>
                                            <?= escapar(
                                                (string) ($ayuda['comunidad'] ?? '—')
                                            ) ?>
                                        </td>

                                        <td>
                                            <?= escapar(
                                                (string) ($ayuda['tipo_ayuda'] ?? '')
                                            ) ?>
                                        </td>

                                        <td>
                                            <?= escapar(
                                                (string) ($ayuda['descripcion'] ?? '')
                                            ) ?>
                                        </td>

                                        <td>
                                            <?= number_format(
                                                (int) ($ayuda['cantidad'] ?? 0)
                                            ) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="panel-encabezado">
                        <div>
                            <h2>Resumen por tipo de ayuda</h2>
                            <p>Totales agrupados según la categoría de ayuda.</p>
                        </div>
                    </div>

                    <div class="tabla-contenedor">
                        <table class="tabla-beneficiarios">
                            <thead>
                                <tr>
                                    <th>Tipo de ayuda</th>
                                    <th>Registros</th>
                                    <th>Unidades entregadas</th>
                                    <th>Beneficiarios atendidos</th>
                                </tr>
                            </thead>

                            <tbody>
                            <?php if (empty($datosReporte['resumen_por_tipo'])): ?>
                                <tr>
                                    <td colspan="4">
                                        <div class="estado-vacio">
                                            <strong>No hay datos para resumir</strong>
                                            <p>
                                                No existen resultados para los
                                                filtros seleccionados.
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($datosReporte['resumen_por_tipo'] as $fila): ?>
                                    <tr>
                                        <td>
                                            <?= escapar(
                                                (string) ($fila['tipo_ayuda'] ?? '')
                                            ) ?>
                                        </td>
                                        <td>
                                            <?= number_format(
                                                (int) ($fila['total_registros'] ?? 0)
                                            ) ?>
                                        </td>
                                        <td>
                                            <?= number_format(
                                                (int) ($fila['total_unidades'] ?? 0)
                                            ) ?>
                                        </td>
                                        <td>
                                            <?= number_format(
                                                (int) ($fila['beneficiarios_atendidos'] ?? 0)
                                            ) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="pie-tabla">
                        Los resultados se calculan a partir de las ayudas
                        registradas en el sistema.
                    </div>

                </section>
            <?php endif; ?>

            <footer class="pie-pagina">
                Manos que Sirven · Iglesia de San Isidro, Cabañas
            </footer>

        </section>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>