<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/Middleware/AuthMiddleware.php';

AuthMiddleware::requiereLogin();

require_once __DIR__ . '/../app/Controllers/ReporteController.php';

$controlador = new ReporteController();

$periodo = isset($_GET['periodo']) && is_string($_GET['periodo'])
    ? $_GET['periodo']
    : 'mes';

if (!$controlador->validarPeriodo($periodo)) {
    $periodo = 'mes';
}

$fechaReferencia = isset($_GET['fecha']) && is_string($_GET['fecha'])
    ? trim($_GET['fecha'])
    : date('Y-m-d');

$tipoAyuda = isset($_GET['tipo_ayuda']) && is_string($_GET['tipo_ayuda'])
    ? trim($_GET['tipo_ayuda'])
    : '';

$errorReporte = '';
$reporte = null;
$tiposAyuda = [];
$resumenGeneral = [];

try {
    $tiposAyuda = $controlador->obtenerTiposAyuda();
    $resumenGeneral = $controlador->obtenerResumenGeneral();

    $reporte = $controlador->generarReporte(
        $periodo,
        $fechaReferencia,
        $tipoAyuda
    );
} catch (Throwable $e) {
    error_log('Error al cargar reportes: ' . $e->getMessage());
    $errorReporte = 'No fue posible generar el reporte. Revisá los filtros e intentá nuevamente.';
}

$nombrePeriodo = [
    'dia' => 'Diario',
    'semana' => 'Semanal',
    'mes' => 'Mensual',
    'anio' => 'Anual',
][$periodo];

$fechaActual = date('Y-m-d');

require_once __DIR__ . '/../app/Views/reportes/index.php';
