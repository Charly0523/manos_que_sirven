<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Preparación de variables
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

$productos = isset($productos) && is_array($productos)
    ? $productos
    : [];

$productosActivos = isset($productosActivos) && is_array($productosActivos)
    ? $productosActivos
    : [];

$categorias = isset($categorias) && is_array($categorias)
    ? $categorias
    : [];

$resumen = isset($resumen) && is_array($resumen)
    ? $resumen
    : [];

$movimientos = isset($movimientos) && is_array($movimientos)
    ? $movimientos
    : [];

$productoEditar = isset($productoEditar) && is_array($productoEditar)
    ? $productoEditar
    : null;

$modoEdicion = $productoEditar !== null;

$busqueda = isset($busqueda) && is_string($busqueda)
    ? $busqueda
    : '';

$categoriaFiltro = isset($categoriaFiltro) && is_string($categoriaFiltro)
    ? $categoriaFiltro
    : '';

$estadoFiltro = isset($estadoFiltro) && is_string($estadoFiltro)
    ? $estadoFiltro
    : '';

$busquedaMovimientos = isset($busquedaMovimientos)
    && is_string($busquedaMovimientos)
        ? $busquedaMovimientos
        : '';

$tipoMovimientoFiltro = isset($tipoMovimientoFiltro)
    && is_string($tipoMovimientoFiltro)
        ? $tipoMovimientoFiltro
        : '';

$productoMovimientoFiltro = isset($productoMovimientoFiltro)
    && is_numeric($productoMovimientoFiltro)
        ? (int) $productoMovimientoFiltro
        : 0;

$datosFormulario = $productoEditar ?? [
    'id' => '',
    'nombre' => '',
    'categoria' => 'Alimentos',
    'unidad_medida' => '',
    'stock_minimo' => '0.00',
    'descripcion' => '',
];

$paginaActiva = 'inventario';


/*
|--------------------------------------------------------------------------
| Funciones auxiliares
|--------------------------------------------------------------------------
*/

function inventarioCantidad(mixed $cantidad): string
{
    return number_format(
        is_numeric($cantidad) ? (float) $cantidad : 0,
        2,
        '.',
        ','
    );
}

function inventarioFecha(?string $fecha): string
{
    if ($fecha === null || trim($fecha) === '') {
        return '—';
    }

    try {
        return (new DateTimeImmutable($fecha))->format('d/m/Y H:i');
    } catch (Throwable $e) {
        return $fecha;
    }
}


/*
|--------------------------------------------------------------------------
| Preparación visual
|--------------------------------------------------------------------------
*/

$claseMensaje = $mensaje !== null && !empty($mensaje['exito'])
    ? 'mensaje-exito'
    : 'mensaje-error';

$categoriasSugeridas = [
    'Alimentos',
    'Ropa',
    'Medicamentos',
    'Útiles escolares',
    'Higiene personal',
    'Limpieza',
    'Otros',
];

$categoriasDisponibles = array_values(array_unique(
    array_merge($categoriasSugeridas, $categorias)
));

$categoriaActual = is_string($datosFormulario['categoria'] ?? null)
    ? $datosFormulario['categoria']
    : 'Alimentos';

if (
    $categoriaActual !== ''
    && !in_array($categoriaActual, $categoriasDisponibles, true)
) {
    $categoriasDisponibles[] = $categoriaActual;
}

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#176344">

    <title>Inventario | Manos Que Sirven</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/sidebar.css">

    <style>

        .inventario-contenido {
            width: 100%;
            min-width: 0;
        }

        .inventario-encabezado {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 28px;
        }

        .inventario-etiqueta {
            display: inline-block;
            margin-bottom: 8px;
            color: #176344;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 1.2px;
        }

        .inventario-encabezado h1 {
            margin: 0 0 8px;
            color: #24352c;
            font-size: 28px;
            font-weight: 750;
        }

        .inventario-encabezado p {
            margin: 0;
            color: #718078;
            font-size: 14px;
        }

        .inventario-fecha {
            flex-shrink: 0;
            padding: 12px 16px;
            border: 1px solid #e0e9e3;
            border-radius: 9px;
            background: #ffffff;
            color: #66736d;
            font-size: 13px;
        }

        .inventario-fecha strong {
            display: block;
            margin-top: 4px;
            color: #176344;
            font-size: 16px;
        }

        /*
        |--------------------------------------------------------------------------
        | Estadísticas
        |--------------------------------------------------------------------------
        */

        .inventario-estadisticas {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 25px;
        }

        .inventario-estadistica {
            display: flex;
            align-items: center;
            gap: 14px;
            min-width: 0;
            padding: 20px;
            border: 1px solid #e2eae5;
            border-radius: 10px;
            background: #ffffff;
        }

        .inventario-icono {
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            width: 45px;
            height: 45px;
            border-radius: 10px;
            font-size: 21px;
            font-weight: 700;
        }

        .icono-verde {
            background: #e7f3eb;
            color: #176344;
        }

        .icono-amarillo {
            background: #fff5df;
            color: #a16c13;
        }

        .icono-rojo {
            background: #fceaea;
            color: #a83232;
        }

        .icono-azul {
            background: #eaf1f8;
            color: #355e83;
        }

        .inventario-estadistica p {
            margin: 0 0 4px;
            color: #68776e;
            font-size: 13px;
        }

        .inventario-estadistica strong {
            display: block;
            color: #24352c;
            font-size: 25px;
            font-weight: 750;
        }

        /*
        |--------------------------------------------------------------------------
        | Paneles y formularios
        |--------------------------------------------------------------------------
        */

        .inventario-panel {
            margin-bottom: 25px;
            padding: 23px;
            border: 1px solid #e0e9e3;
            border-radius: 10px;
            background: #ffffff;
        }

        .inventario-panel-encabezado {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            margin-bottom: 20px;
        }

        .inventario-panel-encabezado h2 {
            margin: 0 0 6px;
            color: #24352c;
            font-size: 19px;
            font-weight: 750;
        }

        .inventario-panel-encabezado p {
            margin: 0;
            color: #738078;
            font-size: 13px;
        }

        .inventario-formulario {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 17px;
        }

        .inventario-campo {
            display: flex;
            flex-direction: column;
            gap: 7px;
            min-width: 0;
        }

        .inventario-campo.completo {
            grid-column: 1 / -1;
        }

        .inventario-campo label {
            color: #33463a;
            font-size: 13px;
            font-weight: 650;
        }

        .inventario-campo label span {
            color: #b52d2d;
        }

        .inventario-campo input,
        .inventario-campo select,
        .inventario-campo textarea {
            width: 100%;
            min-height: 43px;
            padding: 10px 12px;
            border: 1px solid #ccd8d1;
            border-radius: 7px;
            outline: none;
            background: #ffffff;
            color: #24352c;
            font: inherit;
            font-size: 14px;
        }

        .inventario-campo textarea {
            min-height: 85px;
            resize: vertical;
        }

        .inventario-campo input:focus,
        .inventario-campo select:focus,
        .inventario-campo textarea:focus {
            border-color: #176344;
            box-shadow: 0 0 0 3px rgba(23, 99, 68, 0.10);
        }

        .inventario-ayuda {
            color: #77847c;
            font-size: 12px;
            line-height: 1.5;
        }

        .inventario-nota {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin-top: 18px;
            padding: 12px 14px;
            border: 1px solid #d8e9dc;
            border-radius: 7px;
            background: #f1f8f2;
            color: #3c654b;
            font-size: 13px;
            line-height: 1.5;
        }

        /*
        |--------------------------------------------------------------------------
        | Botones
        |--------------------------------------------------------------------------
        */

        .inventario-boton {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 39px;
            padding: 9px 15px;
            border: 1px solid #176344;
            border-radius: 7px;
            background: #176344;
            color: #ffffff;
            font-size: 13px;
            font-weight: 650;
            text-decoration: none;
            cursor: pointer;
            transition: background .15s ease;
        }

        .inventario-boton:hover {
            border-color: #124b34;
            background: #124b34;
            color: #ffffff;
        }

        .inventario-boton:disabled {
            opacity: .55;
            cursor: not-allowed;
        }

        .inventario-boton-secundario {
            border-color: #d2ddd5;
            background: #ffffff;
            color: #34483a;
        }

        .inventario-boton-secundario:hover {
            border-color: #b9c9bd;
            background: #f3f7f4;
            color: #24352c;
        }

        .inventario-boton-peligro {
            border-color: #a83232;
            background: #a83232;
        }

        .inventario-boton-peligro:hover {
            border-color: #852626;
            background: #852626;
        }

        .inventario-boton-pequeno {
            min-height: 32px;
            padding: 6px 10px;
            font-size: 12px;
        }

        .inventario-formulario-pie {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 10px;
            margin-top: 5px;
        }

        /*
        |--------------------------------------------------------------------------
        | Mensajes
        |--------------------------------------------------------------------------
        */

        .inventario-mensaje {
            margin-bottom: 22px;
            padding: 14px 16px;
            border: 1px solid;
            border-radius: 8px;
            font-size: 14px;
        }

        .mensaje-exito {
            border-color: #b7d9c0;
            background: #edf7f0;
            color: #205f39;
        }

        .mensaje-error {
            border-color: #e3bcbc;
            background: #fff0f0;
            color: #922d2d;
        }

        /*
        |--------------------------------------------------------------------------
        | Filtros
        |--------------------------------------------------------------------------
        */

        .inventario-filtros {
            display: flex;
            flex-wrap: wrap;
            align-items: end;
            gap: 10px;
            margin-bottom: 20px;
        }

        .inventario-filtros .campo-filtro {
            display: flex;
            flex: 1;
            flex-direction: column;
            gap: 6px;
            min-width: 145px;
        }

        .inventario-filtros label {
            color: #53645a;
            font-size: 12px;
            font-weight: 650;
        }

        .inventario-filtros input,
        .inventario-filtros select {
            width: 100%;
            min-height: 39px;
            padding: 8px 10px;
            border: 1px solid #d1dcd4;
            border-radius: 7px;
            background: #ffffff;
            color: #24352c;
            font: inherit;
            font-size: 13px;
        }

        /*
        |--------------------------------------------------------------------------
        | Tablas
        |--------------------------------------------------------------------------
        */

        .inventario-tabla-contenedor {
            width: 100%;
            overflow-x: auto;
        }

        .inventario-tabla {
            width: 100%;
            border-collapse: collapse;
            background: #ffffff;
        }

        .inventario-tabla th,
        .inventario-tabla td {
            padding: 13px 12px;
            border-bottom: 1px solid #e6ece8;
            text-align: left;
            vertical-align: middle;
        }

        .inventario-tabla th {
            background: #f1f6f2;
            color: #415448;
            font-size: 12px;
            font-weight: 750;
            white-space: nowrap;
        }

        .inventario-tabla td {
            color: #3b4b41;
            font-size: 13px;
        }

        .inventario-tabla tbody tr:hover {
            background: #fafcfb;
        }

        .inventario-producto-nombre {
            color: #24352c;
            font-weight: 700;
        }

        .inventario-subtexto {
            display: block;
            margin-top: 3px;
            color: #7b8880;
            font-size: 11px;
        }

        /*
        |--------------------------------------------------------------------------
        | Etiquetas de estado
        |--------------------------------------------------------------------------
        */

        .inventario-estado {
            display: inline-block;
            padding: 5px 9px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            white-space: nowrap;
        }

        .estado-disponible {
            background: #e9f5ec;
            color: #27633b;
        }

        .estado-bajo {
            background: #fff4dc;
            color: #946416;
        }

        .estado-agotado {
            background: #fdeaea;
            color: #a83232;
        }

        .estado-inactivo {
            background: #edf0ee;
            color: #68736c;
        }

        /*
        |--------------------------------------------------------------------------
        | Acciones
        |--------------------------------------------------------------------------
        */

        .inventario-acciones {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 6px;
            min-width: 150px;
        }

        .inventario-acciones form {
            margin: 0;
        }

        .inventario-vacio {
            padding: 30px !important;
            color: #7b8880 !important;
            text-align: center !important;
        }

        .inventario-conteo {
            padding: 6px 11px;
            border: 1px solid #e1e9e3;
            border-radius: 20px;
            background: #f7faf8;
            color: #53645a;
            font-size: 12px;
            white-space: nowrap;
        }

        .inventario-pie {
            margin: 25px 0 10px;
            color: #87938c;
            font-size: 12px;
            text-align: center;
        }

        /*
        |--------------------------------------------------------------------------
        | Diseño adaptable
        |--------------------------------------------------------------------------
        */

        @media (max-width: 1100px) {
            .inventario-estadisticas {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 650px) {

            .inventario-encabezado {
                align-items: flex-start;
                flex-direction: column;
            }

            .inventario-estadisticas {
                grid-template-columns: 1fr;
            }

            .inventario-formulario {
                grid-template-columns: 1fr;
            }

            .inventario-campo.completo {
                grid-column: auto;
            }

            .inventario-panel {
                padding: 16px;
            }

            .inventario-panel-encabezado {
                align-items: flex-start;
                flex-direction: column;
            }

            .inventario-filtros {
                flex-direction: column;
                align-items: stretch;
            }

            .inventario-filtros .campo-filtro {
                width: 100%;
            }
        }

    </style>

</head>

<body>

<div class="app-layout">

    <?php require __DIR__ . '/../layout/sidebar.php'; ?>

    <main class="main-content">

        <section class="inventario-contenido">

            <!-- ENCABEZADO -->
            <header class="inventario-encabezado">

                <div>

                    <span class="inventario-etiqueta">
                        CONTROL DE RECURSOS
                    </span>

                    <h1>Gestión de inventario</h1>

                    <p>
                        Administra los productos, las existencias y
                        los movimientos de la Iglesia de San Isidro.
                    </p>

                </div>

                <div class="inventario-fecha">

                    Fecha actual

                    <strong>
                        <?= escapar($fechaActual) ?>
                    </strong>

                </div>

            </header>


            <!-- MENSAJES -->
            <?php if ($mensaje !== null): ?>

                <div
                    class="inventario-mensaje <?= $claseMensaje ?>"
                    role="status"
                >
                    <?= escapar(
                        (string) ($mensaje['mensaje'] ?? '')
                    ) ?>
                </div>

            <?php endif; ?>


            <!-- ESTADÍSTICAS -->
            <section
                class="inventario-estadisticas"
                aria-label="Resumen del inventario"
            >

                <article class="inventario-estadistica">

                    <div class="inventario-icono icono-verde">
                        ▦
                    </div>

                    <div>

                        <p>Total de productos</p>

                        <strong>
                            <?= number_format(
                                (int) ($resumen['total_productos'] ?? 0)
                            ) ?>
                        </strong>

                    </div>

                </article>


                <article class="inventario-estadistica">

                    <div class="inventario-icono icono-azul">
                        ✓
                    </div>

                    <div>

                        <p>Productos activos</p>

                        <strong>
                            <?= number_format(
                                (int) ($resumen['productos_activos'] ?? 0)
                            ) ?>
                        </strong>

                    </div>

                </article>


                <article class="inventario-estadistica">

                    <div class="inventario-icono icono-amarillo">
                        !
                    </div>

                    <div>

                        <p>Existencias bajas</p>

                        <strong>
                            <?= number_format(
                                (int) ($resumen['productos_stock_bajo'] ?? 0)
                            ) ?>
                        </strong>

                    </div>

                </article>


                <article class="inventario-estadistica">

                    <div class="inventario-icono icono-rojo">
                        0
                    </div>

                    <div>

                        <p>Productos agotados</p>

                        <strong>
                            <?= number_format(
                                (int) ($resumen['productos_agotados'] ?? 0)
                            ) ?>
                        </strong>

                    </div>

                </article>

            </section>


            <!-- REGISTRO Y EDICIÓN DE PRODUCTOS -->
            <section
                class="inventario-panel"
                id="formulario-producto"
            >

                <div class="inventario-panel-encabezado">

                    <div>

                        <span class="inventario-etiqueta">
                            CATÁLOGO DE PRODUCTOS
                        </span>

                        <h2>
                            <?= $modoEdicion
                                ? 'Editar producto'
                                : 'Registrar nuevo producto' ?>
                        </h2>

                        <p>
                            <?= $modoEdicion
                                ? 'Modificá la información del producto seleccionado.'
                                : 'Ingresá los datos del artículo que formará parte del inventario.' ?>
                        </p>

                    </div>

                    <?php if ($modoEdicion): ?>

                        <a
                            href="inventario.php#formulario-producto"
                            class="inventario-boton inventario-boton-secundario"
                        >
                            Cancelar edición
                        </a>

                    <?php endif; ?>

                </div>


                <form
                    action="inventario.php<?= $modoEdicion
                        ? '?editar=' . (int) ($productoEditar['id'] ?? 0)
                        : '' ?>#formulario-producto"
                    method="POST"
                    class="inventario-formulario"
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
                            name="producto_id"
                            value="<?= (int) ($productoEditar['id'] ?? 0) ?>"
                        >

                    <?php endif; ?>


                    <div class="inventario-campo">

                        <label for="nombre">
                            Nombre del producto <span>*</span>
                        </label>

                        <input
                            type="text"
                            name="nombre"
                            id="nombre"
                            maxlength="150"
                            required
                            value="<?= escapar(
                                (string) ($datosFormulario['nombre'] ?? '')
                            ) ?>"
                            placeholder="Ej. Arroz, frijoles, camisa"
                        >

                    </div>


                    <div class="inventario-campo">

                        <label for="categoria">
                            Categoría <span>*</span>
                        </label>

                        <select
                            name="categoria"
                            id="categoria"
                            required
                        >

                            <option value="">
                                Seleccioná una categoría
                            </option>

                            <?php foreach ($categoriasDisponibles as $categoria): ?>

                                <option
                                    value="<?= escapar($categoria) ?>"
                                    <?= $categoriaActual === $categoria
                                        ? 'selected'
                                        : '' ?>
                                >
                                    <?= escapar($categoria) ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <div class="inventario-campo">

                        <label for="unidad_medida">
                            Unidad de medida <span>*</span>
                        </label>

                        <input
                            type="text"
                            name="unidad_medida"
                            id="unidad_medida"
                            maxlength="30"
                            list="unidades-inventario"
                            required
                            value="<?= escapar(
                                (string) ($datosFormulario['unidad_medida'] ?? '')
                            ) ?>"
                            placeholder="Ej. Libra, Unidad, Litro"
                        >

                        <datalist id="unidades-inventario">
                            <option value="Unidad">
                            <option value="Libra">
                            <option value="Kilogramo">
                            <option value="Gramo">
                            <option value="Litro">
                            <option value="Galón">
                            <option value="Caja">
                            <option value="Paquete">
                            <option value="Bolsa">
                            <option value="Docena">
                        </datalist>

                    </div>


                    <div class="inventario-campo">

                        <label for="stock_minimo">
                            Stock mínimo <span>*</span>
                        </label>

                        <input
                            type="number"
                            name="stock_minimo"
                            id="stock_minimo"
                            min="0"
                            max="9999999999.99"
                            step="0.01"
                            required
                            value="<?= escapar(
                                (string) ($datosFormulario['stock_minimo'] ?? '0.00')
                            ) ?>"
                        >

                        <small class="inventario-ayuda">
                            Cantidad a partir de la cual el sistema indicará
                            que hay pocas existencias.
                        </small>

                    </div>


                    <div class="inventario-campo completo">

                        <label for="descripcion">
                            Descripción del producto
                        </label>

                        <textarea
                            name="descripcion"
                            id="descripcion"
                            maxlength="5000"
                            placeholder="Detalles adicionales del producto (opcional)"
                        ><?= escapar(
                            (string) ($datosFormulario['descripcion'] ?? '')
                        ) ?></textarea>

                    </div>


                    <div class="inventario-campo completo">

                        <div class="inventario-formulario-pie">

                            <button
                                type="submit"
                                class="inventario-boton"
                            >
                                <?= $modoEdicion
                                    ? 'Guardar cambios'
                                    : 'Registrar producto' ?>
                            </button>

                            <?php if ($modoEdicion): ?>

                                <a
                                    href="inventario.php"
                                    class="inventario-boton inventario-boton-secundario"
                                >
                                    Cancelar
                                </a>

                            <?php endif; ?>

                        </div>

                    </div>

                </form>


                <?php if (!$modoEdicion): ?>

                    <div class="inventario-nota">

                        <strong>Importante:</strong>

                        <span>
                            Los productos nuevos comienzan con existencia cero.
                            Para establecer las existencias iniciales utilizá
                            el apartado de ajustes de inventario.
                        </span>

                    </div>

                <?php endif; ?>

            </section>


            <!-- AJUSTES MANUALES -->
            <section
                class="inventario-panel"
                id="ajustes-inventario"
            >

                <div class="inventario-panel-encabezado">

                    <div>

                        <span class="inventario-etiqueta">
                            CONTROL DE EXISTENCIAS
                        </span>

                        <h2>Ajustes de inventario</h2>

                        <p>
                            Registrá correcciones de cantidades con su respectivo motivo.
                        </p>

                    </div>

                </div>


                <form
                    action="inventario.php#ajustes-inventario"
                    method="POST"
                    class="inventario-formulario"
                >

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= escapar(generarTokenCSRF()) ?>"
                    >

                    <input
                        type="hidden"
                        name="accion"
                        value="ajuste"
                    >


                    <div class="inventario-campo">

                        <label for="producto_id_ajuste">
                            Producto <span>*</span>
                        </label>

                        <select
                            name="producto_id"
                            id="producto_id_ajuste"
                            required
                        >

                            <option value="">
                                Seleccioná un producto
                            </option>

                            <?php foreach ($productosActivos as $producto): ?>

                                <?php if (!is_array($producto)) {
                                    continue;
                                } ?>

                                <option value="<?= (int) ($producto['id'] ?? 0) ?>">

                                    <?= escapar(
                                        (string) ($producto['nombre'] ?? '')
                                    ) ?>

                                    —

                                    Stock:

                                    <?= inventarioCantidad(
                                        $producto['stock_actual'] ?? 0
                                    ) ?>

                                    <?= escapar(
                                        (string) ($producto['unidad_medida'] ?? '')
                                    ) ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <div class="inventario-campo">

                        <label for="tipo_movimiento_ajuste">
                            Tipo de ajuste <span>*</span>
                        </label>

                        <select
                            name="tipo_movimiento"
                            id="tipo_movimiento_ajuste"
                            required
                        >

                            <option value="">
                                Seleccioná una opción
                            </option>

                            <option value="Ajuste positivo">
                                Aumentar existencias
                            </option>

                            <option value="Ajuste negativo">
                                Disminuir existencias
                            </option>

                        </select>

                    </div>


                    <div class="inventario-campo">

                        <label for="cantidad_ajuste">
                            Cantidad <span>*</span>
                        </label>

                        <input
                            type="number"
                            name="cantidad"
                            id="cantidad_ajuste"
                            min="0.01"
                            max="9999999999.99"
                            step="0.01"
                            placeholder="Ej. 10.00"
                            required
                        >

                    </div>


                    <div class="inventario-campo">

                        <label for="observaciones_ajuste">
                            Motivo del ajuste <span>*</span>
                        </label>

                        <input
                            type="text"
                            name="observaciones"
                            id="observaciones_ajuste"
                            maxlength="5000"
                            placeholder="Ej. Corrección de conteo físico"
                            required
                        >

                    </div>


                    <div class="inventario-campo completo">

                        <button
                            type="submit"
                            class="inventario-boton"
                            <?= count($productosActivos) === 0 ? 'disabled' : '' ?>
                        >
                            Registrar ajuste
                        </button>

                    </div>

                </form>


                <?php if ($productosActivos === []): ?>

                    <div class="inventario-nota">
                        Primero necesitás registrar al menos un producto activo.
                    </div>

                <?php endif; ?>

            </section>


            <!-- LISTA DE PRODUCTOS -->
            <section
                class="inventario-panel"
                id="lista-productos"
            >

                <div class="inventario-panel-encabezado">

                    <div>

                        <span class="inventario-etiqueta">
                            CATÁLOGO Y EXISTENCIAS
                        </span>

                        <h2>Productos registrados</h2>

                        <p>
                            Consultá las cantidades disponibles y el estado
                            de cada producto.
                        </p>

                    </div>

                    <span class="inventario-conteo">

                        <?= number_format(count($productos)) ?>

                        <?= count($productos) === 1
                            ? 'producto'
                            : 'productos' ?>

                    </span>

                </div>


                <!-- FILTROS -->
                <form
                    action="inventario.php#lista-productos"
                    method="GET"
                    class="inventario-filtros"
                >

                    <div class="campo-filtro">

                        <label for="buscar">
                            Buscar producto
                        </label>

                        <input
                            type="search"
                            name="buscar"
                            id="buscar"
                            value="<?= escapar($busqueda) ?>"
                            placeholder="Nombre o descripción"
                        >

                    </div>


                    <div class="campo-filtro">

                        <label for="filtro-categoria">
                            Categoría
                        </label>

                        <select
                            name="categoria"
                            id="filtro-categoria"
                        >

                            <option value="">
                                Todas las categorías
                            </option>

                            <?php foreach ($categoriasDisponibles as $categoria): ?>

                                <option
                                    value="<?= escapar($categoria) ?>"
                                    <?= $categoriaFiltro === $categoria
                                        ? 'selected'
                                        : '' ?>
                                >
                                    <?= escapar($categoria) ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <div class="campo-filtro">

                        <label for="filtro-estado">
                            Estado
                        </label>

                        <select
                            name="estado"
                            id="filtro-estado"
                        >

                            <option value="">Todos</option>

                            <option
                                value="Activo"
                                <?= $estadoFiltro === 'Activo' ? 'selected' : '' ?>
                            >
                                Activos
                            </option>

                            <option
                                value="Inactivo"
                                <?= $estadoFiltro === 'Inactivo' ? 'selected' : '' ?>
                            >
                                Inactivos
                            </option>

                        </select>

                    </div>


                    <button
                        type="submit"
                        class="inventario-boton"
                    >
                        Buscar
                    </button>


                    <?php if (
                        $busqueda !== ''
                        || $categoriaFiltro !== ''
                        || $estadoFiltro !== ''
                    ): ?>

                        <a
                            href="inventario.php#lista-productos"
                            class="inventario-boton inventario-boton-secundario"
                        >
                            Limpiar
                        </a>

                    <?php endif; ?>

                </form>


                <!-- TABLA DE PRODUCTOS -->
                <div class="inventario-tabla-contenedor">

                    <table class="inventario-tabla">

                        <thead>

                            <tr>
                                <th>Producto</th>
                                <th>Categoría</th>
                                <th>Existencias</th>
                                <th>Stock mínimo</th>
                                <th>Estado del stock</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>

                        </thead>


                        <tbody>

                            <?php if ($productos === []): ?>

                                <tr>

                                    <td colspan="7" class="inventario-vacio">
                                        No hay productos para mostrar.
                                    </td>

                                </tr>

                            <?php else: ?>

                                <?php foreach ($productos as $producto): ?>

                                    <?php
                                    if (!is_array($producto)) {
                                        continue;
                                    }

                                    $productoIdTabla = (int) (
                                        $producto['id'] ?? 0
                                    );

                                    $nombreProducto = is_string($producto['nombre'] ?? null)
                                        ? $producto['nombre']
                                        : 'Producto sin nombre';

                                    $categoriaProducto = is_string($producto['categoria'] ?? null)
                                        ? $producto['categoria']
                                        : 'Otros';

                                    $unidadProducto = is_string($producto['unidad_medida'] ?? null)
                                        ? $producto['unidad_medida']
                                        : '';

                                    $stockActual = is_numeric($producto['stock_actual'] ?? null)
                                        ? (float) $producto['stock_actual']
                                        : 0.0;

                                    $stockMinimo = is_numeric($producto['stock_minimo'] ?? null)
                                        ? (float) $producto['stock_minimo']
                                        : 0.0;

                                    $estadoProducto = is_string($producto['estado'] ?? null)
                                        ? $producto['estado']
                                        : 'Inactivo';

                                    if ($estadoProducto === 'Inactivo') {
                                        $claseStock = 'estado-inactivo';
                                        $textoStock = 'Producto inactivo';
                                    } elseif ($stockActual <= 0) {
                                        $claseStock = 'estado-agotado';
                                        $textoStock = 'Agotado';
                                    } elseif ($stockActual <= $stockMinimo) {
                                        $claseStock = 'estado-bajo';
                                        $textoStock = 'Stock bajo';
                                    } else {
                                        $claseStock = 'estado-disponible';
                                        $textoStock = 'Disponible';
                                    }
                                    ?>

                                    <tr>

                                        <td>

                                            <strong class="inventario-producto-nombre">
                                                <?= escapar($nombreProducto) ?>
                                            </strong>

                                            <small class="inventario-subtexto">
                                                <?= escapar($unidadProducto) ?>
                                            </small>

                                        </td>


                                        <td>
                                            <?= escapar($categoriaProducto) ?>
                                        </td>


                                        <td>

                                            <strong>
                                                <?= inventarioCantidad($stockActual) ?>
                                            </strong>

                                            <?= escapar($unidadProducto) ?>

                                        </td>


                                        <td>
                                            <?= inventarioCantidad($stockMinimo) ?>
                                        </td>


                                        <td>

                                            <span class="inventario-estado <?= $claseStock ?>">
                                                <?= escapar($textoStock) ?>
                                            </span>

                                        </td>


                                        <td>
                                            <?= escapar($estadoProducto) ?>
                                        </td>


                                        <td>

                                            <div class="inventario-acciones">

                                                <a
                                                    href="inventario.php?editar=<?= $productoIdTabla ?>#formulario-producto"
                                                    class="inventario-boton inventario-boton-secundario inventario-boton-pequeno"
                                                >
                                                    Editar
                                                </a>


                                                <form
                                                    action="inventario.php"
                                                    method="POST"
                                                    onsubmit="return confirm('¿Confirmás el cambio de estado de este producto?');"
                                                >

                                                    <input
                                                        type="hidden"
                                                        name="csrf_token"
                                                        value="<?= escapar(generarTokenCSRF()) ?>"
                                                    >

                                                    <input
                                                        type="hidden"
                                                        name="accion"
                                                        value="cambiar_estado"
                                                    >

                                                    <input
                                                        type="hidden"
                                                        name="producto_id"
                                                        value="<?= $productoIdTabla ?>"
                                                    >

                                                    <input
                                                        type="hidden"
                                                        name="estado_nuevo"
                                                        value="<?= $estadoProducto === 'Activo'
                                                            ? 'Inactivo'
                                                            : 'Activo' ?>"
                                                    >

                                                    <button
                                                        type="submit"
                                                        class="inventario-boton inventario-boton-pequeno <?= $estadoProducto === 'Activo'
                                                            ? 'inventario-boton-peligro'
                                                            : '' ?>"
                                                    >
                                                        <?= $estadoProducto === 'Activo'
                                                            ? 'Desactivar'
                                                            : 'Activar' ?>
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

            </section>


            <!-- HISTORIAL DE MOVIMIENTOS -->
            <section
                class="inventario-panel"
                id="historial-movimientos"
            >

                <div class="inventario-panel-encabezado">

                    <div>

                        <span class="inventario-etiqueta">
                            CONTROL Y SEGUIMIENTO
                        </span>

                        <h2>Historial de movimientos</h2>

                        <p>
                            Consultá los últimos 100 movimientos registrados.
                        </p>

                    </div>

                    <span class="inventario-conteo">

                        <?= number_format(count($movimientos)) ?>

                        <?= count($movimientos) === 1
                            ? 'movimiento'
                            : 'movimientos' ?>

                    </span>

                </div>


                <!-- FILTROS DEL HISTORIAL -->
                <form
                    action="inventario.php#historial-movimientos"
                    method="GET"
                    class="inventario-filtros"
                >

                    <div class="campo-filtro">

                        <label for="buscar-movimiento">
                            Buscar
                        </label>

                        <input
                            type="search"
                            name="buscar_movimiento"
                            id="buscar-movimiento"
                            value="<?= escapar($busquedaMovimientos) ?>"
                            placeholder="Producto u observaciones"
                        >

                    </div>


                    <div class="campo-filtro">

                        <label for="filtro-movimiento">
                            Tipo de movimiento
                        </label>

                        <select
                            name="tipo_movimiento"
                            id="filtro-movimiento"
                        >

                            <option value="">
                                Todos los movimientos
                            </option>

                            <?php foreach ([
                                'Entrada',
                                'Salida',
                                'Ajuste positivo',
                                'Ajuste negativo',
                            ] as $tipo): ?>

                                <option
                                    value="<?= escapar($tipo) ?>"
                                    <?= $tipoMovimientoFiltro === $tipo
                                        ? 'selected'
                                        : '' ?>
                                >
                                    <?= escapar($tipo) ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <div class="campo-filtro">

                        <label for="filtro-producto-movimiento">
                            Producto
                        </label>

                        <select
                            name="producto_movimiento"
                            id="filtro-producto-movimiento"
                        >

                            <option value="0">
                                Todos los productos
                            </option>

                            <?php foreach ($productosActivos as $producto): ?>

                                <?php
                                if (!is_array($producto)) {
                                    continue;
                                }

                                $idProductoFiltro = (int) (
                                    $producto['id'] ?? 0
                                );

                                $nombreProductoFiltro = is_string($producto['nombre'] ?? null)
                                    ? $producto['nombre']
                                    : 'Producto sin nombre';
                                ?>

                                <option
                                    value="<?= $idProductoFiltro ?>"
                                    <?= $productoMovimientoFiltro === $idProductoFiltro
                                        ? 'selected'
                                        : '' ?>
                                >
                                    <?= escapar($nombreProductoFiltro) ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <button
                        type="submit"
                        class="inventario-boton"
                    >
                        Buscar
                    </button>


                    <?php if (
                        $busquedaMovimientos !== ''
                        || $tipoMovimientoFiltro !== ''
                        || $productoMovimientoFiltro > 0
                    ): ?>

                        <a
                            href="inventario.php#historial-movimientos"
                            class="inventario-boton inventario-boton-secundario"
                        >
                            Limpiar
                        </a>

                    <?php endif; ?>

                </form>


                <!-- TABLA DE MOVIMIENTOS -->
                <div class="inventario-tabla-contenedor">

                    <table class="inventario-tabla">

                        <thead>

                            <tr>
                                <th>Fecha</th>
                                <th>Producto</th>
                                <th>Movimiento</th>
                                <th>Cantidad</th>
                                <th>Stock anterior</th>
                                <th>Stock nuevo</th>
                                <th>Referencia</th>
                                <th>Registrado por</th>
                                <th>Observaciones</th>
                            </tr>

                        </thead>


                        <tbody>

                            <?php if ($movimientos === []): ?>

                                <tr>

                                    <td colspan="9" class="inventario-vacio">
                                        Todavía no hay movimientos registrados.
                                    </td>

                                </tr>

                            <?php else: ?>

                                <?php foreach ($movimientos as $movimiento): ?>

                                    <?php
                                    /*
                                     * CORRECCIÓN P1006:
                                     * Comprobar que cada movimiento sea un array
                                     * antes de acceder a sus índices.
                                     */

                                    if (!is_array($movimiento)) {
                                        continue;
                                    }

                                    $fechaMovimiento = is_string($movimiento['creado_en'] ?? null)
                                        ? $movimiento['creado_en']
                                        : '';

                                    $nombreProductoMovimiento = is_string($movimiento['producto'] ?? null)
                                        ? $movimiento['producto']
                                        : 'Producto no disponible';

                                    $unidadMovimiento = is_string($movimiento['unidad_medida'] ?? null)
                                        ? $movimiento['unidad_medida']
                                        : '';

                                    $tipoMovimiento = is_string($movimiento['tipo_movimiento'] ?? null)
                                        ? $movimiento['tipo_movimiento']
                                        : 'No especificado';

                                    $cantidadMovimiento = $movimiento['cantidad'] ?? 0;

                                    $stockAnteriorMovimiento = $movimiento['stock_anterior'] ?? 0;

                                    $stockNuevoMovimiento = $movimiento['stock_nuevo'] ?? 0;

                                    $referenciaTipo = is_string($movimiento['referencia_tipo'] ?? null)
                                        ? $movimiento['referencia_tipo']
                                        : '—';

                                    $referenciaId = isset($movimiento['referencia_id'])
                                        && is_numeric($movimiento['referencia_id'])
                                            ? (int) $movimiento['referencia_id']
                                            : 0;

                                    $reversaDe = isset($movimiento['reversa_de'])
                                        && is_numeric($movimiento['reversa_de'])
                                            ? (int) $movimiento['reversa_de']
                                            : 0;

                                    $registradoPor = is_string($movimiento['registrado_por'] ?? null)
                                        ? $movimiento['registrado_por']
                                        : 'No disponible';

                                    $observacionesMovimiento = is_string($movimiento['observaciones'] ?? null)
                                        && $movimiento['observaciones'] !== ''
                                            ? $movimiento['observaciones']
                                            : '—';
                                    ?>

                                    <tr>

                                        <td>
                                            <?= escapar(
                                                inventarioFecha($fechaMovimiento)
                                            ) ?>
                                        </td>


                                        <td>

                                            <strong>
                                                <?= escapar($nombreProductoMovimiento) ?>
                                            </strong>

                                            <small class="inventario-subtexto">
                                                <?= escapar($unidadMovimiento) ?>
                                            </small>

                                        </td>


                                        <td>

                                            <?= escapar($tipoMovimiento) ?>

                                            <?php if ($reversaDe > 0): ?>

                                                <small class="inventario-subtexto">
                                                    Movimiento de reversión
                                                    #<?= $reversaDe ?>
                                                </small>

                                            <?php endif; ?>

                                        </td>


                                        <td>
                                            <?= inventarioCantidad($cantidadMovimiento) ?>
                                        </td>


                                        <td>
                                            <?= inventarioCantidad($stockAnteriorMovimiento) ?>
                                        </td>


                                        <td>
                                            <?= inventarioCantidad($stockNuevoMovimiento) ?>
                                        </td>


                                        <td>

                                            <?= escapar($referenciaTipo) ?>

                                            <?php if ($referenciaId > 0): ?>

                                                <small class="inventario-subtexto">
                                                    #<?= $referenciaId ?>
                                                </small>

                                            <?php endif; ?>

                                        </td>


                                        <td>
                                            <?= escapar($registradoPor) ?>
                                        </td>


                                        <td>
                                            <?= escapar($observacionesMovimiento) ?>
                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            <?php endif; ?>

                        </tbody>

                    </table>

                </div>


                <div class="inventario-nota">

                    El historial permite consultar los movimientos registrados.
                    Los registros anteriores no deben eliminarse para conservar
                    la trazabilidad del inventario.

                </div>

            </section>


            <!-- PIE DE PÁGINA -->
            <footer class="inventario-pie">

                Manos Que Sirven · Iglesia de San Isidro, Cabañas

            </footer>

        </section>

    </main>

</div>

</body>
</html>
