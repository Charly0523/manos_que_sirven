<?php
declare(strict_types=1);

require_once __DIR__ . '/../Models/Reporte.php';

final class ReporteController
{
    private Reporte $modelo;

    public function __construct()
    {
        $this->modelo = new Reporte();
    }

    /**
     * Calcula las fechas correspondientes al período seleccionado.
     */
    public function obtenerPeriodo(
        string $periodo,
        string $fechaReferencia = ''
    ): array {
        $zonahoraria = new DateTimeZone('America/El_Salvador');

        if ($fechaReferencia === '') {
            $fecha = new DateTimeImmutable('today', $zonahoraria);
        } else {
            $fechaValidada = DateTimeImmutable::createFromFormat(
                '!Y-m-d',
                $fechaReferencia,
                $zonahoraria
            );

            $errores = DateTimeImmutable::getLastErrors();

            if (
                $fechaValidada === false ||
                (
                    $errores !== false &&
                    (
                        $errores['warning_count'] > 0 ||
                        $errores['error_count'] > 0
                    )
                ) ||
                $fechaValidada->format('Y-m-d') !== $fechaReferencia
            ) {
                throw new InvalidArgumentException(
                    'La fecha seleccionada no es válida.'
                );
            }

            $fecha = $fechaValidada;
        }

        switch ($periodo) {
            case 'dia':
                $desde = $fecha;
                $hasta = $fecha;
                $nombre = 'Diario';
                break;

            case 'semana':
                $desde = $fecha->modify('monday this week');
                $hasta = $desde->modify('+6 days');
                $nombre = 'Semanal';
                break;

            case 'mes':
                $desde = $fecha->modify('first day of this month');
                $hasta = $fecha->modify('last day of this month');
                $nombre = 'Mensual';
                break;

            case 'anio':
                $desde = $fecha->setDate(
                    (int) $fecha->format('Y'),
                    1,
                    1
                );
                $hasta = $fecha->setDate(
                    (int) $fecha->format('Y'),
                    12,
                    31
                );
                $nombre = 'Anual';
                break;

            default:
                throw new InvalidArgumentException(
                    'Seleccioná un período válido.'
                );
        }

        return [
            'periodo' => $periodo,
            'nombre' => $nombre,
            'fecha_referencia' => $fecha->format('Y-m-d'),
            'desde' => $desde->format('Y-m-d'),
            'hasta' => $hasta->format('Y-m-d'),
        ];
    }

    /**
     * Obtiene el reporte detallado y sus estadísticas.
     */
    public function generarReporte(
        string $periodo,
        string $fechaReferencia = '',
        string $tipoAyuda = ''
    ): array {
        $rango = $this->obtenerPeriodo(
            $periodo,
            $fechaReferencia
        );

        $ayudas = $this->modelo->obtenerAyudasPorPeriodo(
            $rango['desde'],
            $rango['hasta'],
            $tipoAyuda
        );

        $totalRegistros = count($ayudas);
        $totalUnidades = 0;
        $beneficiarios = [];

        foreach ($ayudas as $ayuda) {
            $totalUnidades += (int) $ayuda['cantidad'];
            $beneficiarios[(int) $ayuda['beneficiario_id']] = true;
        }

        $resumenPorTipo = $this->modelo->obtenerResumenPorTipo(
            $rango['desde'],
            $rango['hasta']
        );

        if ($tipoAyuda !== '') {
            $resumenPorTipo = array_values(array_filter(
                $resumenPorTipo,
                static fn(array $fila): bool =>
                    $fila['tipo_ayuda'] === $tipoAyuda
            ));
        }

        return [
            'periodo' => $rango,
            'tipo_ayuda' => $tipoAyuda,
            'total_registros' => $totalRegistros,
            'total_unidades' => $totalUnidades,
            'beneficiarios_atendidos' => count($beneficiarios),
            'resumen_por_tipo' => $resumenPorTipo,
            'ayudas' => $ayudas,
        ];
    }

    /**
     * Obtiene los datos para los filtros de la vista.
     */
    public function obtenerTiposAyuda(): array
    {
        return $this->modelo->obtenerTiposAyuda();
    }

    /**
     * Obtiene el resumen general del sistema.
     */
    public function obtenerResumenGeneral(): array
    {
        return $this->modelo->obtenerResumenGeneral();
    }

    /**
     * Valida el formato de un período recibido por la URL o formulario.
     */
    public function validarPeriodo(string $periodo): bool
    {
        return in_array(
            $periodo,
            ['dia', 'semana', 'mes', 'anio'],
            true
        );
    }
}
