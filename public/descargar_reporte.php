<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/Middleware/AuthMiddleware.php';

AuthMiddleware::requiereLogin();

require_once __DIR__ . '/../app/Controllers/ReporteController.php';

/**
 * Protege los datos exportados contra fórmulas de Excel.
 */
function limpiarValorCSV(mixed $valor): string
{
    $valor = (string) ($valor ?? '');

    if (preg_match('/^[\s]*[=+\-@]/u', $valor)) {
        $valor = "'" . $valor;
    }

    return $valor;
}

/**
 * Convierte texto UTF-8 a Windows-1252 para las fuentes PDF estándar.
 */
function normalizarTextoPDF(string $texto): string
{
    $texto = str_replace(["\r", "\t"], [' ', ' '], $texto);

    $texto = preg_replace(
        '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u',
        '',
        $texto
    ) ?? '';

    if (function_exists('iconv')) {
        $convertido = iconv(
            'UTF-8',
            'Windows-1252//TRANSLIT//IGNORE',
            $texto
        );

        if ($convertido !== false) {
            return $convertido;
        }
    }

    return preg_replace('/[^\x20-\x7E]/', '', $texto) ?? '';
}

/**
 * Escapa cadenas para incluirlas en el PDF.
 */
function escaparCadenaPDF(string $texto): string
{
    return str_replace(
        ['\\', '(', ')'],
        ['\\\\', '\\(', '\\)'],
        normalizarTextoPDF($texto)
    );
}

/**
 * Ajusta los textos para que no se salgan de las celdas.
 */
function envolverTextoPDF(
    string $texto,
    int $maxCaracteres,
    int $maxLineas = 4
): array {

    $texto = trim(str_replace(["\r", "\t"], [' ', ' '], $texto));

    if ($texto === '') {
        return ['—'];
    }

    $parrafos = preg_split('/\n+/u', $texto) ?: [$texto];

    $lineas = [];

    foreach ($parrafos as $parrafo) {
        $palabras = preg_split('/\s+/u', trim($parrafo)) ?: [];

        $linea = '';

        foreach ($palabras as $palabra) {

            if ($palabra === '') {
                continue;
            }

            $prueba = $linea === ''
                ? $palabra
                : $linea . ' ' . $palabra;

            $longitud = function_exists('mb_strwidth')
                ? mb_strwidth($prueba, 'UTF-8')
                : strlen($prueba);

            if ($longitud > $maxCaracteres && $linea !== '') {
                $lineas[] = $linea;
                $linea = $palabra;
            } else {
                $linea = $prueba;
            }
        }

        if ($linea !== '') {
            $lineas[] = $linea;
        }
    }

    if ($lineas === []) {
        $lineas = ['—'];
    }

    if (count($lineas) > $maxLineas) {
        $lineas = array_slice($lineas, 0, $maxLineas);

        $ultima = array_pop($lineas);

        $lineas[] = rtrim($ultima, " .") . '...';
    }

    return $lineas;
}

/**
 * Generador PDF institucional.
 *
 * Formato A4 horizontal.
 * Incluye encabezados, estadísticas, gráfica, tablas
 * y numeración automática de páginas.
 */
final class ReportePDF
{
    private const ANCHO = 842;
    private const ALTO = 595;

    private const VERDE_OSCURO = '#124B34';
    private const VERDE = '#176344';
    private const VERDE_CLARO = '#EAF4EE';
    private const FONDO = '#F5F7F6';
    private const TEXTO = '#24352C';
    private const GRIS = '#68766F';
    private const BORDE = '#D9E5DE';
    private const BLANCO = '#FFFFFF';

    private array $paginas = [];

    private string $contenidoActual = '';

    private string $fechaGeneracion;

    public function __construct()
    {
        $this->fechaGeneracion = date('d/m/Y H:i');
    }

    /**
     * Convierte hexadecimal a RGB para PDF.
     */
    private function rgb(string $hex): string
    {
        $hex = ltrim($hex, '#');

        $r = hexdec(substr($hex, 0, 2)) / 255;
        $g = hexdec(substr($hex, 2, 2)) / 255;
        $b = hexdec(substr($hex, 4, 2)) / 255;

        return sprintf('%.4F %.4F %.4F', $r, $g, $b);
    }

    /**
     * Dibuja rectángulos.
     */
    private function rect(
        float $x,
        float $y,
        float $ancho,
        float $alto,
        string $color
    ): void {

        $this->contenidoActual .= sprintf(
            "q %s rg %.2F %.2F %.2F %.2F re f Q\n",
            $this->rgb($color),
            $x,
            $y,
            $ancho,
            $alto
        );
    }

    /**
     * Dibuja tarjetas con borde.
     */
    private function rectBorde(
        float $x,
        float $y,
        float $ancho,
        float $alto,
        string $relleno,
        string $borde = self::BORDE
    ): void {

        $this->contenidoActual .= sprintf(
            "q %s rg %s RG 0.7 w %.2F %.2F %.2F %.2F re B Q\n",
            $this->rgb($relleno),
            $this->rgb($borde),
            $x,
            $y,
            $ancho,
            $alto
        );
    }

    /**
     * Dibuja líneas.
     */
    private function linea(
        float $x1,
        float $y1,
        float $x2,
        float $y2,
        string $color,
        float $grosor = 0.6
    ): void {

        $this->contenidoActual .= sprintf(
            "q %s RG %.2F w %.2F %.2F m %.2F %.2F l S Q\n",
            $this->rgb($color),
            $grosor,
            $x1,
            $y1,
            $x2,
            $y2
        );
    }

    /**
     * Escribe texto dentro del PDF.
     */
    public function texto(
        float $x,
        float $y,
        float $tamano,
        string $texto,
        string $color = self::TEXTO,
        bool $negrita = false
    ): void {

        $fuente = $negrita ? 'F2' : 'F1';

        $cadena = escaparCadenaPDF($texto);

        $this->contenidoActual .= sprintf(
            "BT /%s %.2F Tf %s rg 1 0 0 1 %.2F %.2F Tm (%s) Tj ET\n",
            $fuente,
            $tamano,
            $this->rgb($color),
            $x,
            $y,
            $cadena
        );
    }

    /**
     * Encabezado institucional de cada página.
     */
    private function encabezado(
        string $titulo = 'REPORTE DE AYUDAS ENTREGADAS'
    ): void {

        $this->rect(
            0,
            0,
            self::ANCHO,
            self::ALTO,
            self::BLANCO
        );

        $this->rect(
            0,
            505,
            self::ANCHO,
            90,
            self::VERDE_OSCURO
        );

        $this->rect(
            0,
            501,
            self::ANCHO,
            4,
            self::VERDE
        );

        // Logotipo textual MS.
        $this->rect(
            36,
            540,
            38,
            38,
            self::VERDE_CLARO
        );

        $this->texto(
            45,
            553,
            15,
            'MS',
            self::VERDE_OSCURO,
            true
        );

        // Nombre institucional.
        $this->texto(
            86,
            568,
            14,
            'MANOS QUE SIRVEN',
            self::BLANCO,
            true
        );

        $this->texto(
            86,
            553,
            8,
            'IGLESIA DE SAN ISIDRO · CABAÑAS',
            '#D8E9DF'
        );

        $this->texto(
            36,
            521,
            12,
            $titulo,
            self::BLANCO,
            true
        );

        $this->texto(
            665,
            568,
            7,
            'INFORME ADMINISTRATIVO',
            self::BLANCO,
            true
        );

        $this->texto(
            665,
            553,
            7,
            'GENERADO: ' . $this->fechaGeneracion,
            '#D8E9DF'
        );
    }

    /**
     * Inicia una nueva página.
     */
    public function nuevaPagina(
        string $titulo = 'REPORTE DE AYUDAS ENTREGADAS'
    ): void {

        if ($this->contenidoActual !== '') {
            $this->paginas[] = $this->contenidoActual;
        }

        $this->contenidoActual = '';

        $this->encabezado($titulo);
    }

    /**
     * Información del período consultado.
     */
    public function bandaPeriodo(
        array $rango,
        string $tipoAyuda
    ): void {

        $this->rectBorde(
            36,
            461,
            770,
            29,
            self::VERDE_CLARO,
            '#C9DED1'
        );

        $nombre = (string) ($rango['nombre'] ?? 'Período seleccionado');

        $desde = (string) ($rango['desde'] ?? '');

        $hasta = (string) ($rango['hasta'] ?? '');

        $tipo = $tipoAyuda !== ''
            ? $tipoAyuda
            : 'Todos los tipos';

        $this->texto(
            47,
            478,
            7,
            'PERÍODO',
            self::VERDE_OSCURO,
            true
        );

        $this->texto(
            47,
            467,
            8,
            $nombre,
            self::TEXTO,
            true
        );

        $this->texto(
            270,
            478,
            7,
            'RANGO DE FECHAS',
            self::VERDE_OSCURO,
            true
        );

        $this->texto(
            270,
            467,
            8,
            $desde . ' al ' . $hasta,
            self::TEXTO,
            true
        );

        $this->texto(
            590,
            478,
            7,
            'TIPO DE AYUDA',
            self::VERDE_OSCURO,
            true
        );

        $this->texto(
            590,
            467,
            8,
            $tipo,
            self::TEXTO,
            true
        );
    }

    /**
     * Encabezados de secciones.
     */
    public function tituloSeccion(
        float $y,
        string $titulo,
        string $subtitulo = ''
    ): void {

        $this->rect(
            36,
            $y - 4,
            4,
            15,
            self::VERDE
        );

        $this->texto(
            47,
            $y,
            10,
            $titulo,
            self::VERDE_OSCURO,
            true
        );

        if ($subtitulo !== '') {
            $this->texto(
                47,
                $y - 13,
                7,
                $subtitulo,
                self::GRIS
            );
        }
    }

    /**
     * Tarjetas estadísticas.
     */
    public function tarjetasResumen(array $reporte): void
    {
        $elementos = [
            [
                'titulo' => 'AYUDAS REGISTRADAS',
                'valor' => (int) ($reporte['total_registros'] ?? 0),
                'nota' => 'Registros dentro del período'
            ],
            [
                'titulo' => 'UNIDADES ENTREGADAS',
                'valor' => (int) ($reporte['total_unidades'] ?? 0),
                'nota' => 'Cantidad total reportada'
            ],
            [
                'titulo' => 'BENEFICIARIOS ATENDIDOS',
                'valor' => (int) ($reporte['beneficiarios_atendidos'] ?? 0),
                'nota' => 'Personas distintas atendidas'
            ],
        ];

        $ancho = 246;
        $separacion = 16;
        $xInicial = 36;

        foreach ($elementos as $i => $elemento) {

            $x = $xInicial + ($i * ($ancho + $separacion));

            $this->rectBorde(
                $x,
                374,
                $ancho,
                54,
                self::BLANCO
            );

            $this->rect(
                $x,
                374,
                4,
                54,
                self::VERDE
            );

            $this->texto(
                $x + 14,
                414,
                7,
                $elemento['titulo'],
                self::GRIS,
                true
            );

            $this->texto(
                $x + 14,
                389,
                19,
                number_format($elemento['valor']),
                self::VERDE_OSCURO,
                true
            );

            $this->texto(
                $x + 105,
                390,
                7,
                $elemento['nota'],
                self::GRIS
            );
        }
    }

    /**
     * Gráfica de barras por categoría.
     */
    public function graficaPorTipo(array $resumenPorTipo): void
    {
        $this->tituloSeccion(
            351,
            'UNIDADES ENTREGADAS POR TIPO',
            'Comparación de unidades registradas en cada categoría.'
        );

        if ($resumenPorTipo === []) {

            $this->texto(
                48,
                315,
                8,
                'No hay desglose por tipo de ayuda para los filtros seleccionados.',
                self::GRIS
            );

            return;
        }

        $maximo = 1;

        foreach ($resumenPorTipo as $fila) {
            $maximo = max(
                $maximo,
                (int) ($fila['total_unidades'] ?? 0)
            );
        }

        $filas = array_slice($resumenPorTipo, 0, 6);

        $y = 324;
        $altoBarra = 8;
        $anchoBarra = 475;

        foreach ($filas as $fila) {

            $categoria = (string) (
                $fila['tipo_ayuda'] ?? 'Sin categoría'
            );

            if (function_exists('mb_strimwidth')) {
                $categoria = mb_strimwidth(
                    $categoria,
                    0,
                    26,
                    '...',
                    'UTF-8'
                );
            }

            $valor = (int) (
                $fila['total_unidades'] ?? 0
            );

            $this->texto(
                47,
                $y + 1,
                7.5,
                $categoria,
                self::TEXTO,
                true
            );

            // Fondo de la barra.
            $this->rect(
                220,
                $y - 1,
                $anchoBarra,
                $altoBarra,
                self::VERDE_CLARO
            );

            // Barra proporcional al resultado.
            $anchoRelleno = ($valor / $maximo) * $anchoBarra;

            if ($anchoRelleno > 0) {
                $this->rect(
                    220,
                    $y - 1,
                    $anchoRelleno,
                    $altoBarra,
                    self::VERDE
                );
            }

            $this->texto(
                710,
                $y,
                7.5,
                number_format($valor) . ' unidades',
                self::VERDE_OSCURO,
                true
            );

            $y -= 20;
        }

        if (count($resumenPorTipo) > 6) {
            $this->texto(
                47,
                $y + 3,
                6.5,
                'La gráfica muestra las primeras seis categorías; la tabla incluye el desglose completo.',
                self::GRIS
            );
        }
    }

    /**
     * Tabla estadística agrupada por categoría.
     */
    public function tablaResumenPorTipo(array $resumenPorTipo): void
    {
        $this->tituloSeccion(
            190,
            'RESUMEN POR TIPO DE AYUDA',
            'Cantidad de registros y personas atendidas por categoría.'
        );

        $x = 36;
        $yTop = 169;
        $alto = 19;

        $anchos = [330, 130, 155, 155];

        $encabezados = [
            'Tipo de ayuda',
            'Registros',
            'Unidades',
            'Beneficiarios'
        ];

        $this->rect(
            $x,
            $yTop - $alto,
            array_sum($anchos),
            $alto,
            self::VERDE_OSCURO
        );

        $cursor = $x;

        foreach ($encabezados as $i => $encabezado) {

            $this->texto(
                $cursor + 7,
                $yTop - 13,
                7,
                $encabezado,
                self::BLANCO,
                true
            );

            $cursor += $anchos[$i];
        }

        if ($resumenPorTipo === []) {

            $this->rect(
                36,
                $yTop - 39,
                770,
                20,
                self::FONDO
            );

            $this->texto(
                44,
                $yTop - 33,
                7,
                'No hay categorías para mostrar en este período.',
                self::GRIS
            );

            return;
        }

        $y = $yTop - $alto;

        foreach ($resumenPorTipo as $indice => $fila) {

            $y -= $alto;

            $this->rect(
                36,
                $y,
                770,
                $alto,
                $indice % 2 === 0
                    ? self::FONDO
                    : self::BLANCO
            );

            $valores = [
                (string) ($fila['tipo_ayuda'] ?? 'Sin categoría'),
                number_format((int) ($fila['total_registros'] ?? 0)),
                number_format((int) ($fila['total_unidades'] ?? 0)),
                number_format((int) ($fila['beneficiarios_atendidos'] ?? 0)),
            ];

            $cursor = 36;

            foreach ($valores as $i => $valor) {

                $this->texto(
                    $cursor + 7,
                    $y + 6,
                    7,
                    $valor,
                    self::TEXTO,
                    $i === 0
                );

                $cursor += $anchos[$i];
            }

            $this->linea(
                36,
                $y,
                806,
                $y,
                self::BORDE,
                0.35
            );
        }
    }

    /**
     * Encabezado de la tabla de entregas.
     */
    private function encabezadoTablaDetalle(): void
    {
        $x = 36;
        $yTop = 448;
        $alto = 23;

        $anchos = [
            35,
            65,
            105,
            90,
            100,
            320,
            55
        ];

        $encabezados = [
            'ID',
            'Fecha',
            'Beneficiario',
            'Comunidad',
            'Tipo',
            'Descripción / observaciones',
            'Cant.'
        ];

        $this->rect(
            $x,
            $yTop - $alto,
            array_sum($anchos),
            $alto,
            self::VERDE_OSCURO
        );

        $cursor = $x;

        foreach ($encabezados as $i => $encabezado) {

            $this->texto(
                $cursor + 4,
                $yTop - 15,
                6.6,
                $encabezado,
                self::BLANCO,
                true
            );

            $cursor += $anchos[$i];
        }
    }

    /**
     * Tabla completa de ayudas con paginación automática.
     */
    public function tablaDetalle(array $ayudas): void
    {
        $this->nuevaPagina('DETALLE DE AYUDAS ENTREGADAS');

        $this->texto(
            36,
            480,
            10,
            'Detalle de entregas registradas',
            self::VERDE_OSCURO,
            true
        );

        $this->texto(
            36,
            465,
            7,
            'Cada fila corresponde a una ayuda registrada en el período consultado.',
            self::GRIS
        );

        $this->encabezadoTablaDetalle();

        $y = 425;

        if ($ayudas === []) {

            $this->rectBorde(
                36,
                382,
                770,
                34,
                self::FONDO
            );

            $this->texto(
                48,
                395,
                8,
                'No se encontraron ayudas para los filtros seleccionados.',
                self::GRIS
            );

            return;
        }

        $anchos = [
            35,
            65,
            105,
            90,
            100,
            320,
            55
        ];

        foreach ($ayudas as $indice => $ayuda) {

            $fecha = (string) (
                $ayuda['fecha_entrega'] ?? ''
            );

            if ($fecha !== '') {

                $fechaObj = DateTimeImmutable::createFromFormat(
                    '!Y-m-d',
                    $fecha
                );

                if ($fechaObj) {
                    $fecha = $fechaObj->format('d/m/Y');
                }
            }

            $descripcion = trim(
                (string) ($ayuda['descripcion'] ?? '')
            );

            $observaciones = trim(
                (string) ($ayuda['observaciones'] ?? '')
            );

            if ($observaciones !== '') {
                $descripcion .= "\nObservación: " . $observaciones;
            }

            $celdas = [

                envolverTextoPDF(
                    (string) ($ayuda['id'] ?? ''),
                    5,
                    2
                ),

                envolverTextoPDF(
                    $fecha,
                    11,
                    2
                ),

                envolverTextoPDF(
                    (string) ($ayuda['beneficiario'] ?? ''),
                    18,
                    3
                ),

                envolverTextoPDF(
                    (string) ($ayuda['comunidad'] ?? '—'),
                    15,
                    3
                ),

                envolverTextoPDF(
                    (string) ($ayuda['tipo_ayuda'] ?? ''),
                    17,
                    3
                ),

                envolverTextoPDF(
                    $descripcion,
                    61,
                    5
                ),

                envolverTextoPDF(
                    number_format((int) ($ayuda['cantidad'] ?? 0)),
                    7,
                    2
                ),
            ];

            $maxLineas = 1;

            foreach ($celdas as $lineas) {
                $maxLineas = max(
                    $maxLineas,
                    count($lineas)
                );
            }

            $altoFila = max(
                22,
                ($maxLineas * 8) + 8
            );

            // Cuando no hay espacio, continúa en otra página.
            if ($y - $altoFila < 43) {

                $this->nuevaPagina(
                    'CONTINUACIÓN DEL DETALLE DE AYUDAS'
                );

                $this->texto(
                    36,
                    480,
                    10,
                    'Detalle de entregas registradas',
                    self::VERDE_OSCURO,
                    true
                );

                $this->texto(
                    36,
                    465,
                    7,
                    'Continuación del listado de ayudas del período consultado.',
                    self::GRIS
                );

                $this->encabezadoTablaDetalle();

                $y = 425;
            }

            $y -= $altoFila;

            $this->rect(
                36,
                $y,
                770,
                $altoFila,
                $indice % 2 === 0
                    ? self::FONDO
                    : self::BLANCO
            );

            $this->linea(
                36,
                $y,
                806,
                $y,
                self::BORDE,
                0.35
            );

            $cursor = 36;

            foreach ($celdas as $columna => $lineas) {

                foreach ($lineas as $linea => $texto) {

                    $this->texto(
                        $cursor + 4,
                        $y + $altoFila - 11 - ($linea * 8),
                        6.6,
                        $texto,
                        self::TEXTO,
                        $columna === 0
                    );
                }

                $cursor += $anchos[$columna];
            }

            $this->linea(
                36,
                $y,
                36,
                $y + $altoFila,
                self::BORDE,
                0.35
            );

            $this->linea(
                806,
                $y,
                806,
                $y + $altoFila,
                self::BORDE,
                0.35
            );
        }
    }

    /**
     * Construye el documento PDF y sus páginas.
     */
    public function generar(): string
    {
        if ($this->contenidoActual !== '') {
            $this->paginas[] = $this->contenidoActual;
            $this->contenidoActual = '';
        }

        $totalPaginas = count($this->paginas);

        foreach ($this->paginas as $indice => &$contenido) {

            $numero = $indice + 1;

            $contenido .=
                "q " . $this->rgb(self::BORDE) .
                " RG 0.6 w 36 32 m 806 32 l S Q\n";

            $contenido .=
                "BT /F1 7 Tf " .
                $this->rgb(self::GRIS) .
                " rg 1 0 0 1 36 18 Tm " .
                "(Manos que Sirven | Iglesia de San Isidro, Cabañas) Tj ET\n";

            $contenido .=
                "BT /F2 7 Tf " .
                $this->rgb(self::VERDE_OSCURO) .
                " rg 1 0 0 1 744 18 Tm " .
                "(Página {$numero} de {$totalPaginas}) Tj ET\n";
        }

        unset($contenido);

        $objetos = [];

        $objetos[1] =
            '<< /Type /Catalog /Pages 2 0 R >>';

        $objetos[3] =
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';

        $objetos[4] =
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>';

        $referencias = [];

        foreach ($this->paginas as $indice => $contenido) {

            $idPagina = 5 + ($indice * 2);
            $idContenido = $idPagina + 1;

            $referencias[] = $idPagina . ' 0 R';

            $objetos[$idContenido] =
                '<< /Length ' . strlen($contenido) . " >>\nstream\n" .
                $contenido . "\nendstream";

            $objetos[$idPagina] =
                '<< /Type /Page /Parent 2 0 R ' .
                '/MediaBox [0 0 842 595] ' .
                '/Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> ' .
                '/Contents ' . $idContenido . ' 0 R >>';
        }

        $objetos[2] =
            '<< /Type /Pages /Kids [' .
            implode(' ', $referencias) .
            '] /Count ' . $totalPaginas . ' >>';

        ksort($objetos);

        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";

        $desplazamientos = [];

        $cantidadObjetos = max(array_keys($objetos)) + 1;

        for ($id = 1; $id < $cantidadObjetos; $id++) {

            if (!isset($objetos[$id])) {
                continue;
            }

            $desplazamientos[$id] = strlen($pdf);

            $pdf .= $id . " 0 obj\n";
            $pdf .= $objetos[$id] . "\n";
            $pdf .= "endobj\n";
        }

        $inicioXref = strlen($pdf);

        $pdf .= "xref\n";
        $pdf .= "0 {$cantidadObjetos}\n";
        $pdf .= "0000000000 65535 f \n";

        for ($id = 1; $id < $cantidadObjetos; $id++) {

            if (isset($desplazamientos[$id])) {

                $pdf .= sprintf(
                    "%010d 00000 n \n",
                    $desplazamientos[$id]
                );

            } else {
                $pdf .= "0000000000 00000 f \n";
            }
        }

        $pdf .= "trailer\n";

        $pdf .=
            '<< /Size ' . $cantidadObjetos .
            ' /Root 1 0 R >>' . "\n";

        $pdf .= "startxref\n";
        $pdf .= $inicioXref . "\n";
        $pdf .= "%%EOF";

        return $pdf;
    }
}

/*
|--------------------------------------------------------------------------
| GENERACIÓN DEL REPORTE
|--------------------------------------------------------------------------
*/

try {

    $controlador = new ReporteController();

    $periodo = isset($_GET['periodo']) && is_string($_GET['periodo'])
        ? $_GET['periodo']
        : 'mes';

    if (!$controlador->validarPeriodo($periodo)) {

        throw new InvalidArgumentException(
            'El período seleccionado no es válido.'
        );
    }

    $fecha = isset($_GET['fecha']) && is_string($_GET['fecha'])
        ? trim($_GET['fecha'])
        : date('Y-m-d');

    $tipoAyuda = isset($_GET['tipo_ayuda']) && is_string($_GET['tipo_ayuda'])
        ? trim($_GET['tipo_ayuda'])
        : '';

    $formato = isset($_GET['formato']) && is_string($_GET['formato'])
        ? strtolower(trim($_GET['formato']))
        : '';

    if (!in_array($formato, ['csv', 'pdf'], true)) {

        throw new InvalidArgumentException(
            'El formato solicitado no es válido.'
        );
    }

    // Obtener los datos reales del controlador.
    $reporte = $controlador->generarReporte(
        $periodo,
        $fecha,
        $tipoAyuda
    );

    $rango = $reporte['periodo'] ?? [];

    $ayudas = is_array($reporte['ayudas'] ?? null)
        ? $reporte['ayudas']
        : [];

    $resumenPorTipo = is_array($reporte['resumen_por_tipo'] ?? null)
        ? $reporte['resumen_por_tipo']
        : [];

    $fechaNombre = preg_replace(
        '/[^0-9-]/',
        '',
        (string) ($rango['desde'] ?? date('Y-m-d'))
    );

    $nombreArchivo = 'reporte_ayudas_' .
        $periodo . '_' .
        $fechaNombre;

    /*
    |--------------------------------------------------------------------------
    | EXPORTACIÓN CSV
    |--------------------------------------------------------------------------
    */

    if ($formato === 'csv') {

        header('Content-Type: text/csv; charset=UTF-8');

        header(
            'Content-Disposition: attachment; filename="' .
            $nombreArchivo . '.csv"'
        );

        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Pragma: no-cache');

        $salida = fopen('php://output', 'wb');

        if ($salida === false) {

            throw new RuntimeException(
                'No fue posible generar el archivo CSV.'
            );
        }

        // Compatibilidad con caracteres especiales en Excel.
        fwrite($salida, "\xEF\xBB\xBF");

        fputcsv($salida, ['MANOS QUE SIRVEN']);
        fputcsv($salida, ['Iglesia de San Isidro, Cabañas']);
        fputcsv($salida, ['REPORTE DE AYUDAS ENTREGADAS']);
        fputcsv($salida, ['Fecha de generación', date('d/m/Y H:i')]);

        fputcsv($salida, [
            'Período',
            $rango['nombre'] ?? $periodo
        ]);

        fputcsv($salida, [
            'Desde',
            $rango['desde'] ?? ''
        ]);

        fputcsv($salida, [
            'Hasta',
            $rango['hasta'] ?? ''
        ]);

        fputcsv($salida, [
            'Tipo de ayuda',
            limpiarValorCSV(
                $tipoAyuda !== '' ? $tipoAyuda : 'Todos'
            )
        ]);

        fputcsv($salida, []);

        // Resumen general.
        fputcsv($salida, ['RESUMEN GENERAL']);
        fputcsv($salida, ['Indicador', 'Resultado']);

        fputcsv($salida, [
            'Total de registros',
            (int) ($reporte['total_registros'] ?? 0)
        ]);

        fputcsv($salida, [
            'Total de unidades entregadas',
            (int) ($reporte['total_unidades'] ?? 0)
        ]);

        fputcsv($salida, [
            'Beneficiarios atendidos',
            (int) ($reporte['beneficiarios_atendidos'] ?? 0)
        ]);

        fputcsv($salida, []);

        // Resumen por categoría.
        fputcsv($salida, ['RESUMEN POR TIPO DE AYUDA']);

        fputcsv($salida, [
            'Tipo de ayuda',
            'Registros',
            'Unidades entregadas',
            'Beneficiarios atendidos'
        ]);

        foreach ($resumenPorTipo as $fila) {

            fputcsv($salida, [
                limpiarValorCSV($fila['tipo_ayuda'] ?? ''),
                (int) ($fila['total_registros'] ?? 0),
                (int) ($fila['total_unidades'] ?? 0),
                (int) ($fila['beneficiarios_atendidos'] ?? 0)
            ]);
        }

        fputcsv($salida, []);

        // Detalle de entregas.
        fputcsv($salida, ['DETALLE DE AYUDAS ENTREGADAS']);

        fputcsv($salida, [
            'ID',
            'Beneficiario',
            'Comunidad',
            'Tipo de ayuda',
            'Descripción',
            'Cantidad',
            'Fecha de entrega',
            'Observaciones'
        ]);

        foreach ($ayudas as $ayuda) {

            fputcsv($salida, [
                limpiarValorCSV($ayuda['id'] ?? ''),
                limpiarValorCSV($ayuda['beneficiario'] ?? ''),
                limpiarValorCSV($ayuda['comunidad'] ?? ''),
                limpiarValorCSV($ayuda['tipo_ayuda'] ?? ''),
                limpiarValorCSV($ayuda['descripcion'] ?? ''),
                (int) ($ayuda['cantidad'] ?? 0),
                limpiarValorCSV($ayuda['fecha_entrega'] ?? ''),
                limpiarValorCSV($ayuda['observaciones'] ?? '')
            ]);
        }

        fclose($salida);
        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | EXPORTACIÓN PDF PROFESIONAL
    |--------------------------------------------------------------------------
    */

    $pdf = new ReportePDF();

    // Primera página: información general.
    $pdf->nuevaPagina();

    $pdf->bandaPeriodo(
        $rango,
        $tipoAyuda
    );

    $pdf->tituloSeccion(
        442,
        'RESUMEN EJECUTIVO',
        'Indicadores principales del período seleccionado.'
    );

    $pdf->tarjetasResumen($reporte);

    $pdf->graficaPorTipo($resumenPorTipo);

    $pdf->tablaResumenPorTipo($resumenPorTipo);

    // Páginas siguientes: detalle de las ayudas.
    $pdf->tablaDetalle($ayudas);

    // Construir documento.
    $contenidoPDF = $pdf->generar();

    header('Content-Type: application/pdf');

    header(
        'Content-Disposition: attachment; filename="' .
        $nombreArchivo . '.pdf"'
    );

    header('Content-Length: ' . strlen($contenidoPDF));

    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('Pragma: no-cache');

    echo $contenidoPDF;
    exit;

} catch (Throwable $e) {

    error_log(
        'Error al descargar reporte: ' . $e->getMessage()
    );

    http_response_code(400);

    header('Content-Type: text/plain; charset=UTF-8');

    echo 'No fue posible descargar el reporte. ';
    echo 'Verificá los filtros e intentá nuevamente.';

    exit;
}