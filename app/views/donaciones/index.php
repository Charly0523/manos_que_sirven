<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Inicialización y protección de variables
|--------------------------------------------------------------------------
*/

$fechaHoy = isset($fechaHoy) && is_string($fechaHoy)
    ? $fechaHoy
    : date('Y-m-d');

$fechaActual = isset($fechaActual) && is_string($fechaActual)
    ? $fechaActual
    : date('d/m/Y');

$mensaje = isset($mensaje) && is_array($mensaje)
    ? $mensaje
    : null;

$busqueda = isset($busqueda) && is_string($busqueda)
    ? $busqueda
    : '';

$tipoFiltro = isset($tipoFiltro) && is_string($tipoFiltro)
    ? $tipoFiltro
    : '';

$estadoFiltro = isset($estadoFiltro) && is_string($estadoFiltro)
    ? $estadoFiltro
    : '';

$donaciones = isset($donaciones) && is_array($donaciones)
    ? $donaciones
    : [];

$resumen = isset($resumen) && is_array($resumen)
    ? $resumen
    : [];

$productosInventario = isset($productosInventario) && is_array($productosInventario)
    ? $productosInventario
    : [];

$donacionEditar = isset($donacionEditar) && is_array($donacionEditar)
    ? $donacionEditar
    : null;

$modoEdicion = $donacionEditar !== null;

$paginaActiva = 'donaciones';

$datosFormulario = $donacionEditar ?? [
    'id' => '',
    'tipo_donacion' => 'Monetaria',
    'donante' => '',
    'es_anonima' => 0,
    'monto' => '',
    'inventario_producto_id' => '',
    'producto' => '',
    'cantidad' => '',
    'unidad_medida' => '',
    'descripcion' => '',
    'fecha_donacion' => $fechaHoy,
    'estado_recepcion' => 'Recibida',
    'destino' => '',
    'observaciones' => '',
];

$tipoFormulario = (string) ($datosFormulario['tipo_donacion'] ?? 'Monetaria');

$esAnonimaFormulario = (int) ($datosFormulario['es_anonima'] ?? 0) === 1;

$idProductoFormulario = (int) ($datosFormulario['inventario_producto_id'] ?? 0);
$unidadProductoSeleccionado = '';

foreach ($productosInventario as $productoInventario) {
    if (!is_array($productoInventario)) {
        continue;
    }

    if ((int) ($productoInventario['id'] ?? 0) === $idProductoFormulario) {
        $unidadProductoSeleccionado = (string) ($productoInventario['unidad_medida'] ?? '');
        break;
    }
}

$unidadNormalizadaFormulario = mb_strtolower(trim($unidadProductoSeleccionado), 'UTF-8');

$esUnidadEnteraFormulario = in_array(
    $unidadNormalizadaFormulario,
    ['unidad', 'unidades'],
    true
);

/*
|--------------------------------------------------------------------------
| Funciones auxiliares
|--------------------------------------------------------------------------
*/

function donacionesFecha(string $fecha): string
{
    if ($fecha === '') {
        return '—';
    }

    $fechaObjeto = DateTimeImmutable::createFromFormat('!Y-m-d', $fecha);

    return $fechaObjeto
        ? $fechaObjeto->format('d/m/Y')
        : $fecha;
}

function donacionesMonto(mixed $monto): string
{
    return '$' . number_format((float) ($monto ?? 0), 2);
}

/**
 * Mostrar cantidades enteras para productos por unidad.
 * Las unidades de peso, volumen y otras medidas conservan dos decimales.
 */
function donacionesCantidad(mixed $cantidad, ?string $unidadMedida): string
{
    $valor = is_numeric($cantidad) ? (float) $cantidad : 0.0;
    $unidad = mb_strtolower(trim((string) $unidadMedida), 'UTF-8');

    $esUnidadIndividual = in_array(
        $unidad,
        ['unidad', 'unidades'],
        true
    );

    return number_format(
        $valor,
        $esUnidadIndividual ? 0 : 2,
        '.',
        ','
    );
}
?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#176b4d">

    <title>Donaciones | Manos que Sirven</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/sidebar.css">
    <link rel="stylesheet" href="assets/css/donaciones.css">

</head>

<body>

<div class="app-layout">

    <?php require __DIR__ . '/../layout/sidebar.php'; ?>

    <main class="main-content">

        <section class="donaciones-contenido">

            <!-- Encabezado -->
            <header class="donaciones-encabezado">

                <div>

                    <span class="donaciones-etiqueta">
                        CONTROL DE RECURSOS
                    </span>

                    <h1>Gestión de donaciones</h1>

                    <p>
                        Registra, consulta y administra los aportes monetarios
                        y las donaciones en especie recibidas por la iglesia.
                    </p>

                </div>

                <div class="donaciones-fecha">

                    <span>Fecha actual</span>

                    <strong>
                        <?= escapar($fechaActual) ?>
                    </strong>

                </div>

            </header>


            <!-- Mensajes -->
            <?php if ($mensaje !== null): ?>

                <div
                    class="donaciones-mensaje <?= !empty($mensaje['exito']) ? 'mensaje-exito' : 'mensaje-error' ?>"
                    role="status"
                >
                    <?= escapar((string) ($mensaje['mensaje'] ?? '')) ?>
                </div>

            <?php endif; ?>


            <!-- Estadísticas -->
            <section
                class="donaciones-estadisticas"
                aria-label="Resumen de donaciones"
            >

                <article class="donacion-estadistica">

                    <div class="estadistica-icono icono-verde">
                        <span aria-hidden="true">▤</span>
                    </div>

                    <div>

                        <p>Total de donaciones</p>

                        <strong>
                            <?= number_format(
                                (int) ($resumen['total_donaciones'] ?? 0)
                            ) ?>
                        </strong>

                        <small>Todos los registros</small>

                    </div>

                </article>


                <article class="donacion-estadistica">

                    <div class="estadistica-icono icono-monetario">
                        <span aria-hidden="true">$</span>
                    </div>

                    <div>

                        <p>Dinero recibido</p>

                        <strong>
                            <?= escapar(
                                donacionesMonto($resumen['total_monetario'] ?? 0)
                            ) ?>
                        </strong>

                        <small>Donaciones monetarias recibidas</small>

                    </div>

                </article>


                <article class="donacion-estadistica">

                    <div class="estadistica-icono icono-productos">
                        <span aria-hidden="true">▦</span>
                    </div>

                    <div>

                        <p>Donaciones en especie</p>

                        <strong>
                            <?= number_format(
                                (int) ($resumen['donaciones_en_especie'] ?? 0)
                            ) ?>
                        </strong>

                        <small>Registros recibidos</small>

                    </div>

                </article>


                <article class="donacion-estadistica">

                    <div class="estadistica-icono icono-pendiente">
                        <span aria-hidden="true">◷</span>
                    </div>

                    <div>

                        <p>Donaciones pendientes</p>

                        <strong>
                            <?= number_format(
                                (int) ($resumen['donaciones_pendientes'] ?? 0)
                            ) ?>
                        </strong>

                        <small>Por confirmar recepción</small>

                    </div>

                </article>

            </section>


            <!-- Información adicional -->
            <section class="donaciones-dato-extra">

                <div>

                    <span class="dato-extra-titulo">
                        Unidades recibidas en especie
                    </span>

                    <strong>
                        <?= number_format(
                            (float) ($resumen['total_unidades_especie'] ?? 0),
                            2
                        ) ?>
                    </strong>

                </div>

                <div class="dato-extra-separador"></div>

                <div>

                    <span class="dato-extra-titulo">
                        Donaciones recibidas
                    </span>

                    <strong>
                        <?= number_format(
                            (int) ($resumen['donaciones_recibidas'] ?? 0)
                        ) ?>
                    </strong>

                </div>

            </section>


            <!-- Formulario -->
            <section
                class="donaciones-panel"
                id="formulario-donacion"
            >

                <div class="donaciones-panel-encabezado">

                    <div>

                        <span class="donaciones-etiqueta">
                            NUEVO MOVIMIENTO
                        </span>

                        <h2>
                            <?= $modoEdicion
                                ? 'Editar donación'
                                : 'Registrar donación' ?>
                        </h2>

                        <p>
                            Completá la información de la aportación recibida.
                        </p>

                    </div>

                    <?php if ($modoEdicion): ?>

                        <a
                            href="donaciones.php"
                            class="donaciones-boton donaciones-boton-claro"
                        >
                            Cancelar edición
                        </a>

                    <?php endif; ?>

                </div>


                <form
                    action="donaciones.php<?= $modoEdicion ? '?editar=' . (int) ($donacionEditar['id'] ?? 0) : '' ?>#formulario-donacion"
                    method="POST"
                    id="formularioDonacion"
                    class="donaciones-formulario"
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
                            name="donacion_id"
                            value="<?= (int) ($donacionEditar['id'] ?? 0) ?>"
                        >

                    <?php endif; ?>


                    <!-- Tipo -->
                    <div class="donaciones-campo">

                        <label for="tipo_donacion">
                            Tipo de donación <span>*</span>
                        </label>

                        <select
                            name="tipo_donacion"
                            id="tipo_donacion"
                            required
                        >

                            <option
                                value="Monetaria"
                                <?= $tipoFormulario === 'Monetaria' ? 'selected' : '' ?>
                            >
                                Monetaria
                            </option>

                            <option
                                value="En especie"
                                <?= $tipoFormulario === 'En especie' ? 'selected' : '' ?>
                            >
                                En especie
                            </option>

                        </select>

                    </div>


                    <!-- Donante -->
                    <div class="donaciones-campo donaciones-campo-donante">

                        <label for="donante">
                            Nombre del donante
                        </label>

                        <input
                            type="text"
                            name="donante"
                            id="donante"
                            maxlength="150"
                            value="<?= escapar((string) ($datosFormulario['donante'] ?? '')) ?>"
                            placeholder="Nombre de la persona o institución"
                        >

                        <label
                            class="donaciones-checkbox"
                            for="es_anonima"
                        >

                            <input
                                type="checkbox"
                                name="es_anonima"
                                id="es_anonima"
                                value="1"
                                <?= $esAnonimaFormulario ? 'checked' : '' ?>
                            >

                            <span>
                                Registrar como donación anónima
                            </span>

                        </label>

                    </div>


                    <!-- Monto -->
                    <div
                        class="donaciones-campo campo-monetario"
                        id="campo-monetario"
                    >

                        <label for="monto">
                            Monto recibido (USD) <span>*</span>
                        </label>

                        <div class="donaciones-input-prefijo">

                            <span>$</span>

                            <input
                                type="number"
                                name="monto"
                                id="monto"
                                min="0.01"
                                max="9999999999.99"
                                step="0.01"
                                value="<?= escapar((string) ($datosFormulario['monto'] ?? '')) ?>"
                                placeholder="0.00"
                            >

                        </div>

                    </div>


                    <!-- Producto del inventario -->
                    <div
                        class="donaciones-campo campo-especie"
                        id="campo-producto"
                    >

                        <label for="inventario_producto_id">
                            Producto del inventario <span>*</span>
                        </label>

                        <select
                            name="inventario_producto_id"
                            id="inventario_producto_id"
                            required
                        >

                            <option value="">
                                Seleccioná un producto
                            </option>

                            <?php foreach ($productosInventario as $productoInventario): ?>

                                <?php
                                if (!is_array($productoInventario)) {
                                    continue;
                                }

                                $idOpcionProducto = (int) (
                                    $productoInventario['id'] ?? 0
                                );

                                $nombreOpcionProducto = (string) (
                                    $productoInventario['nombre'] ?? ''
                                );

                                $unidadOpcionProducto = (string) (
                                    $productoInventario['unidad_medida'] ?? ''
                                );

                                $stockOpcionProducto = $productoInventario['stock_actual'] ?? 0;
                                ?>

                                <option
                                    value="<?= $idOpcionProducto ?>"
                                    data-unidad="<?= escapar($unidadOpcionProducto) ?>"
                                    <?= $idProductoFormulario === $idOpcionProducto ? 'selected' : '' ?>
                                >
                                    <?= escapar($nombreOpcionProducto) ?>

                                    — <?= escapar($unidadOpcionProducto) ?>

                                    — Stock actual:
                                    <?= escapar(
                                        donacionesCantidad(
                                            $stockOpcionProducto,
                                            $unidadOpcionProducto
                                        )
                                    ) ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                        <?php if ($productosInventario === []): ?>

                            <small class="text-danger">
                                No hay productos activos en el inventario.
                                Primero registrá un producto en
                                <a href="inventario.php">
                                    Gestión de inventario
                                </a>.
                            </small>

                        <?php else: ?>

                            <small class="donaciones-subtexto">
                                Seleccioná el producto que recibiste.
                                El sistema utilizará su unidad de medida registrada.
                            </small>

                        <?php endif; ?>

                    </div>


                    <!-- Cantidad -->
                    <div
                        class="donaciones-campo campo-especie"
                        id="campo-cantidad"
                    >

                        <label for="cantidad">
                            Cantidad recibida <span>*</span>
                        </label>

                        <input
                            type="number"
                            name="cantidad"
                            id="cantidad"
                            min="<?= $esUnidadEnteraFormulario ? '1' : '0.01' ?>"
                            max="99999999.99"
                            step="<?= $esUnidadEnteraFormulario ? '1' : '0.01' ?>"
                            value="<?= escapar((string) ($datosFormulario['cantidad'] ?? '')) ?>"
                            placeholder="<?= $esUnidadEnteraFormulario ? 'Ej. 5' : 'Ej. 25 o 2.50' ?>"
                            inputmode="decimal"
                        >

                    </div>


                    <!-- Unidad de medida del producto seleccionado -->
                    <div
                        class="donaciones-campo campo-especie"
                        id="campo-unidad"
                    >

                        <label for="unidad_medida">
                            Unidad de medida
                        </label>

                        <input
                            type="text"
                            id="unidad_medida"
                            value="<?= escapar($unidadProductoSeleccionado) ?>"
                            placeholder="Se mostrará según el producto seleccionado"
                            readonly
                        >

                        <small class="donaciones-subtexto">
                            Esta unidad proviene del catálogo de inventario
                            y no se puede modificar desde Donaciones.
                        </small>

                    </div>


                    <!-- Fecha -->
                    <div class="donaciones-campo">

                        <label for="fecha_donacion">
                            Fecha de donación <span>*</span>
                        </label>

                        <input
                            type="date"
                            name="fecha_donacion"
                            id="fecha_donacion"
                            value="<?= escapar((string) ($datosFormulario['fecha_donacion'] ?? $fechaHoy)) ?>"
                            required
                        >

                        <small>
                            Para donaciones pendientes se puede indicar una fecha futura.
                        </small>

                    </div>


                    <!-- Estado -->
                    <div class="donaciones-campo">

                        <label for="estado_recepcion">
                            Estado de recepción <span>*</span>
                        </label>

                        <select
                            name="estado_recepcion"
                            id="estado_recepcion"
                            required
                        >

                            <option
                                value="Recibida"
                                <?= ($datosFormulario['estado_recepcion'] ?? '') === 'Recibida' ? 'selected' : '' ?>
                            >
                                Recibida
                            </option>

                            <option
                                value="Pendiente"
                                <?= ($datosFormulario['estado_recepcion'] ?? '') === 'Pendiente' ? 'selected' : '' ?>
                            >
                                Pendiente
                            </option>

                        </select>

                    </div>


                    <!-- Destino -->
                    <div class="donaciones-campo donaciones-campo-completo">

                        <label for="destino">
                            Destino de la donación
                        </label>

                        <input
                            type="text"
                            name="destino"
                            id="destino"
                            maxlength="180"
                            value="<?= escapar((string) ($datosFormulario['destino'] ?? '')) ?>"
                            placeholder="Ej. Programa de ayuda alimentaria"
                        >

                    </div>


                    <!-- Descripción -->
                    <div class="donaciones-campo donaciones-campo-completo">

                        <label for="descripcion">
                            Descripción
                        </label>

                        <textarea
                            name="descripcion"
                            id="descripcion"
                            rows="3"
                            maxlength="5000"
                            placeholder="Detalles adicionales de la donación"
                        ><?= escapar((string) ($datosFormulario['descripcion'] ?? '')) ?></textarea>

                    </div>


                    <!-- Observaciones -->
                    <div class="donaciones-campo donaciones-campo-completo">

                        <label for="observaciones">
                            Observaciones
                        </label>

                        <textarea
                            name="observaciones"
                            id="observaciones"
                            rows="3"
                            maxlength="5000"
                            placeholder="Información adicional (opcional)"
                        ><?= escapar((string) ($datosFormulario['observaciones'] ?? '')) ?></textarea>

                    </div>


                    <!-- Botones -->
                    <div class="donaciones-formulario-pie donaciones-campo-completo">

                        <button
                            type="submit"
                            class="donaciones-boton donaciones-boton-principal"
                        >
                            <?= $modoEdicion
                                ? 'Guardar cambios'
                                : 'Registrar donación' ?>
                        </button>

                        <?php if ($modoEdicion): ?>

                            <a
                                href="donaciones.php"
                                class="donaciones-boton donaciones-boton-claro"
                            >
                                Cancelar
                            </a>

                        <?php endif; ?>

                    </div>

                </form>

            </section>


            <!-- Historial -->
            <section
                class="donaciones-panel"
                aria-labelledby="historial-donaciones-titulo"
            >

                <div class="donaciones-panel-encabezado historial-encabezado">

                    <div>

                        <span class="donaciones-etiqueta">
                            CONTROL Y SEGUIMIENTO
                        </span>

                        <h2 id="historial-donaciones-titulo">
                            Historial de donaciones
                        </h2>

                        <p>
                            Consultá los aportes registrados y revisá su estado.
                        </p>

                    </div>

                    <span class="donaciones-contador">

                        <?= number_format(count($donaciones)) ?>

                        <?= count($donaciones) === 1
                            ? 'registro'
                            : 'registros' ?>

                    </span>

                </div>


                <!-- Filtros -->
                <form
                    action="donaciones.php"
                    method="GET"
                    class="donaciones-filtros"
                >

                    <div class="donaciones-busqueda">

                        <label
                            class="visually-hidden"
                            for="buscar"
                        >
                            Buscar donaciones
                        </label>

                        <input
                            type="search"
                            name="buscar"
                            id="buscar"
                            value="<?= escapar($busqueda) ?>"
                            placeholder="Buscar donante, producto o descripción"
                        >

                    </div>


                    <div>

                        <label
                            class="visually-hidden"
                            for="filtro-tipo"
                        >
                            Tipo de donación
                        </label>

                        <select
                            name="tipo"
                            id="filtro-tipo"
                        >

                            <option value="">
                                Todos los tipos
                            </option>

                            <option
                                value="Monetaria"
                                <?= $tipoFiltro === 'Monetaria' ? 'selected' : '' ?>
                            >
                                Monetarias
                            </option>

                            <option
                                value="En especie"
                                <?= $tipoFiltro === 'En especie' ? 'selected' : '' ?>
                            >
                                En especie
                            </option>

                        </select>

                    </div>


                    <div>

                        <label
                            class="visually-hidden"
                            for="filtro-estado"
                        >
                            Estado
                        </label>

                        <select
                            name="estado"
                            id="filtro-estado"
                        >

                            <option value="">
                                Todos los estados
                            </option>

                            <option
                                value="Recibida"
                                <?= $estadoFiltro === 'Recibida' ? 'selected' : '' ?>
                            >
                                Recibida
                            </option>

                            <option
                                value="Pendiente"
                                <?= $estadoFiltro === 'Pendiente' ? 'selected' : '' ?>
                            >
                                Pendiente
                            </option>

                            <option
                                value="Anulada"
                                <?= $estadoFiltro === 'Anulada' ? 'selected' : '' ?>
                            >
                                Anulada
                            </option>

                        </select>

                    </div>


                    <button
                        type="submit"
                        class="donaciones-boton donaciones-boton-principal"
                    >
                        Buscar
                    </button>


                    <?php if (
                        $busqueda !== ''
                        || $tipoFiltro !== ''
                        || $estadoFiltro !== ''
                    ): ?>

                        <a
                            href="donaciones.php"
                            class="donaciones-boton donaciones-boton-claro"
                        >
                            Limpiar
                        </a>

                    <?php endif; ?>

                </form>


                <!-- Tabla -->
                <div class="donaciones-tabla-contenedor">

                    <table class="donaciones-tabla">

                        <thead>

                            <tr>
                                <th>Fecha</th>
                                <th>Donante</th>
                                <th>Tipo</th>
                                <th>Detalle / valor</th>
                                <th>Estado</th>
                                <th>Destino</th>
                                <th>Registrado por</th>
                                <th>Acciones</th>
                            </tr>

                        </thead>


                        <tbody>

                            <?php if ($donaciones === []): ?>

                                <tr>

                                    <td colspan="8" class="donaciones-vacio">

                                        <strong>
                                            No hay donaciones para mostrar.
                                        </strong>

                                        <p>
                                            <?= (
                                                $busqueda !== ''
                                                || $tipoFiltro !== ''
                                                || $estadoFiltro !== ''
                                            )
                                                ? 'Probá modificar los filtros de búsqueda.'
                                                : 'Cuando registres una donación, aparecerá en este historial.' ?>
                                        </p>

                                    </td>

                                </tr>

                            <?php else: ?>

                                <?php foreach ($donaciones as $donacion): ?>

                                    <?php
                                    if (!is_array($donacion)) {
                                        continue;
                                    }

                                    $idDonacionTabla = (int) (
                                        $donacion['id'] ?? 0
                                    );

                                    $esMonetaria = (
                                        $donacion['tipo_donacion'] ?? ''
                                    ) === 'Monetaria';

                                    $estado = (string) (
                                        $donacion['estado_recepcion'] ?? ''
                                    );

                                    $esAnonimaTabla = (int) (
                                        $donacion['es_anonima'] ?? 0
                                    ) === 1;

                                    $donanteTabla = $esAnonimaTabla
                                        ? 'Donación anónima'
                                        : (string) (
                                            $donacion['donante'] ?? 'No especificado'
                                        );

                                    $destinoTabla = (string) (
                                        $donacion['destino'] ?? ''
                                    );

                                    $registradoPorTabla = (string) (
                                        $donacion['registrado_por'] ?? ''
                                    );

                                    $productoTabla = (string) (
                                        $donacion['producto'] ?? ''
                                    );

                                    $unidadTabla = (string) (
                                        $donacion['unidad_medida'] ?? ''
                                    );

                                    $claseEstado = strtolower($estado);
                                    ?>

                                    <tr>

                                        <td>
                                            <?= escapar(
                                                donacionesFecha(
                                                    (string) (
                                                        $donacion['fecha_donacion'] ?? ''
                                                    )
                                                )
                                            ) ?>
                                        </td>


                                        <td>

                                            <strong>
                                                <?= escapar($donanteTabla) ?>
                                            </strong>

                                            <?php if ($esAnonimaTabla): ?>

                                                <small class="donaciones-subtexto">
                                                    Identidad reservada
                                                </small>

                                            <?php endif; ?>

                                        </td>


                                        <td>

                                            <span class="donaciones-tipo <?= $esMonetaria
                                                ? 'tipo-monetaria'
                                                : 'tipo-especie' ?>">

                                                <?= escapar(
                                                    (string) (
                                                        $donacion['tipo_donacion'] ?? ''
                                                    )
                                                ) ?>

                                            </span>

                                        </td>


                                        <td>

                                            <?php if ($esMonetaria): ?>

                                                <strong class="donaciones-valor">

                                                    <?= escapar(
                                                        donacionesMonto(
                                                            $donacion['monto'] ?? 0
                                                        )
                                                    ) ?>

                                                </strong>

                                            <?php else: ?>

                                                <strong>
                                                    <?= escapar($productoTabla) ?>
                                                </strong>

                                                <small class="donaciones-subtexto">

                                                    <?= escapar(
                                                        donacionesCantidad(
                                                            $donacion['cantidad'] ?? 0,
                                                            $unidadTabla
                                                        )
                                                    ) ?>

                                                    <?= escapar($unidadTabla) ?>

                                                </small>

                                            <?php endif; ?>

                                        </td>


                                        <td>

                                            <span class="donaciones-estado estado-<?= escapar($claseEstado) ?>">
                                                <?= escapar($estado) ?>
                                            </span>

                                        </td>


                                        <td>
                                            <?= escapar(
                                                $destinoTabla !== ''
                                                    ? $destinoTabla
                                                    : '—'
                                            ) ?>
                                        </td>


                                        <td>
                                            <?= escapar(
                                                $registradoPorTabla !== ''
                                                    ? $registradoPorTabla
                                                    : '—'
                                            ) ?>
                                        </td>


                                        <td>

                                            <div class="donaciones-acciones">

                                                <?php if (
                                                    $estado !== 'Anulada'
                                                    && $idDonacionTabla > 0
                                                ): ?>

                                                    <a
                                                        href="donaciones.php?editar=<?= $idDonacionTabla ?>#formulario-donacion"
                                                        class="donaciones-accion-editar"
                                                    >
                                                        Editar
                                                    </a>

                                                    <form
                                                        action="donaciones.php"
                                                        method="POST"
                                                        onsubmit="return confirm('¿Deseás anular esta donación? El registro se conservará en el historial.');"
                                                    >

                                                        <input
                                                            type="hidden"
                                                            name="csrf_token"
                                                            value="<?= escapar(generarTokenCSRF()) ?>"
                                                        >

                                                        <input
                                                            type="hidden"
                                                            name="accion"
                                                            value="anular"
                                                        >

                                                        <input
                                                            type="hidden"
                                                            name="donacion_id"
                                                            value="<?= $idDonacionTabla ?>"
                                                        >

                                                        <button
                                                            type="submit"
                                                            class="donaciones-accion-anular"
                                                        >
                                                            Anular
                                                        </button>

                                                    </form>

                                                <?php else: ?>

                                                    <span class="donaciones-accion-inactiva">
                                                        Sin acciones
                                                    </span>

                                                <?php endif; ?>

                                            </div>

                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            <?php endif; ?>

                        </tbody>

                    </table>

                </div>


                <p class="donaciones-nota-historial">
                    Las donaciones anuladas se conservan en el historial
                    y no se incluyen en los totales de recursos recibidos.
                </p>

            </section>


            <footer class="donaciones-pie-pagina">
                Manos que Sirven · Iglesia de San Isidro, Cabañas
            </footer>

        </section>

    </main>

</div>


<!-- JavaScript -->
<script>
(function () {
    'use strict';

    const tipoDonacion = document.getElementById('tipo_donacion');

    const campoMonetario = document.getElementById('campo-monetario');
    const campoProducto = document.getElementById('campo-producto');
    const campoCantidad = document.getElementById('campo-cantidad');
    const campoUnidad = document.getElementById('campo-unidad');

    const monto = document.getElementById('monto');
    const productoInventario = document.getElementById('inventario_producto_id');
    const cantidad = document.getElementById('cantidad');
    const unidadMedida = document.getElementById('unidad_medida');

    const donante = document.getElementById('donante');
    const anonima = document.getElementById('es_anonima');
    const formulario = document.getElementById('formularioDonacion');

    function esUnidadIndividual(unidad) {
        const valor = String(unidad || '').trim().toLocaleLowerCase('es');

        return valor === 'unidad' || valor === 'unidades';
    }

    function actualizarUnidadProducto() {
        if (!productoInventario || !cantidad || !unidadMedida) {
            return;
        }

        const opcion = productoInventario.selectedOptions[0];

        const unidad = opcion
            ? (opcion.dataset.unidad || '')
            : '';

        const esUnidad = esUnidadIndividual(unidad);

        unidadMedida.value = unidad;

        if (esUnidad) {

            cantidad.min = '1';
            cantidad.step = '1';
            cantidad.placeholder = 'Ej. 5';

            cantidad.setAttribute('step', '1');

            if (
                cantidad.value !== ''
                && !Number.isInteger(Number(cantidad.value))
            ) {
                cantidad.setCustomValidity(
                    'Este producto se registra por unidades completas. Ingresá un número entero.'
                );
            } else {
                cantidad.setCustomValidity('');
            }

        } else {

            cantidad.min = '0.01';
            cantidad.step = '0.01';
            cantidad.placeholder = 'Ej. 25 o 2.50';

            cantidad.setAttribute('step', '0.01');

            cantidad.setCustomValidity('');
        }
    }

    function actualizarTipoDonacion() {

        const esMonetaria = tipoDonacion.value === 'Monetaria';

        campoMonetario.hidden = !esMonetaria;
        campoProducto.hidden = esMonetaria;
        campoCantidad.hidden = esMonetaria;
        campoUnidad.hidden = esMonetaria;

        monto.required = esMonetaria;
        productoInventario.required = !esMonetaria;
        cantidad.required = !esMonetaria;

        monto.disabled = !esMonetaria;
        productoInventario.disabled = esMonetaria;
        cantidad.disabled = esMonetaria;
        unidadMedida.disabled = esMonetaria;

        if (!esMonetaria) {
            actualizarUnidadProducto();
        } else {
            cantidad.setCustomValidity('');
        }
    }

    function actualizarDonante() {

        donante.required = !anonima.checked;
        donante.disabled = anonima.checked;

        if (anonima.checked) {

            donante.value = '';
            donante.placeholder = 'La identidad del donante será reservada';

        } else {

            donante.placeholder = 'Nombre de la persona o institución';

        }
    }

    tipoDonacion.addEventListener('change', actualizarTipoDonacion);

    productoInventario.addEventListener(
        'change',
        actualizarUnidadProducto
    );

    cantidad.addEventListener(
        'input',
        actualizarUnidadProducto
    );

    anonima.addEventListener('change', actualizarDonante);

    formulario.addEventListener('submit', function (evento) {

        if (tipoDonacion.value !== 'En especie') {
            return;
        }

        const opcion = productoInventario.selectedOptions[0];

        const unidad = opcion
            ? (opcion.dataset.unidad || '')
            : '';

        if (
            esUnidadIndividual(unidad)
            && !Number.isInteger(Number(cantidad.value))
        ) {
            evento.preventDefault();

            cantidad.setCustomValidity(
                'Este producto se registra por unidades completas. Ingresá un número entero.'
            );

            cantidad.reportValidity();
        }
    });

    actualizarTipoDonacion();
    actualizarDonante();

})();
</script>

</body>
</html>
