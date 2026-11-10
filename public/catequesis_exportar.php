<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/middleware/AuthMiddleware.php';
require_once __DIR__ . '/../app/models/Catequesis.php';

AuthMiddleware::requiereRol(['Administrador', 'Empleado']);
$grupoId = filter_var($_GET['grupo'] ?? null, FILTER_VALIDATE_INT);
if (!is_int($grupoId) || $grupoId <= 0) { http_response_code(400); exit('Grupo inválido.'); }
$modelo = new Catequesis();
$grupo = $modelo->grupo($grupoId);
if (!$grupo) { http_response_code(404); exit('Grupo no encontrado.'); }
$evaluaciones = $modelo->evaluaciones($grupoId);
$promedios = $modelo->promediosGrupo($grupoId);

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="catequesis_grupo_' . $grupoId . '.csv"');
header('Cache-Control: no-store');
$salida = fopen('php://output', 'wb');
if ($salida === false) { exit; }
fwrite($salida, "\xEF\xBB\xBF");
fputcsv($salida, ['Alumno', 'Estado de inscripción', 'Evaluaciones calificadas', 'Total evaluaciones', 'Promedio parcial (0 a 10)'], ';', '"', '');
foreach ($promedios as $fila) {
    $nombre = (string) $fila['alumno'];
    if (preg_match('/^[\s]*[=+\-@]/u', $nombre)) { $nombre = "'" . $nombre; }
    fputcsv($salida, [
        $nombre, (string) $fila['estado'], (int) $fila['cantidad_notas'], count($evaluaciones),
        $fila['promedio'] === null ? '' : number_format((float) $fila['promedio'], 2, '.', '')
    ], ';', '"', '');
}
fclose($salida);
exit;
