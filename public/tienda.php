<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/middleware/AuthMiddleware.php';
require_once __DIR__ . '/../app/controllers/TiendaController.php';

AuthMiddleware::requiereRol(['Administrador', 'Empleado']);
$admin = AuthMiddleware::tieneRol('Administrador');
$usuarioId = (int) $_SESSION['usuario_id'];
$controlador = new TiendaController();
$tabs = ['pos', 'productos', 'caja', 'ventas'];
$tab = is_string($_GET['tab'] ?? null) ? $_GET['tab'] : 'pos';
if (!in_array($tab, $tabs, true)) { $tab = 'pos'; }

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $accion = is_string($_POST['accion'] ?? null) ? $_POST['accion'] : '';
    if (!verificarTokenCSRF(is_string($_POST['csrf_token'] ?? null) ? $_POST['csrf_token'] : null)) {
        $_SESSION['tienda_aviso'] = ['ok' => false, 'texto' => 'Formulario vencido. Recarga la página.'];
        header('Location: tienda.php?tab=' . urlencode($tab));
        exit;
    }
    if ($accion === 'venta_registrar') {
        $nonce = (string) ($_POST['nonce_venta'] ?? '');
        if ($nonce === '' || !hash_equals((string) ($_SESSION['tienda_nonce'] ?? ''), $nonce)) {
            $_SESSION['tienda_aviso'] = ['ok' => false, 'texto' => 'Venta repetida o formulario vencido. Actualiza la página.'];
            header('Location: tienda.php?tab=pos');
            exit;
        }
        unset($_SESSION['tienda_nonce']);
    }
    try {
        $r = $controlador->ejecutar($accion, $_POST, $usuarioId, $admin);
        $_SESSION['tienda_aviso'] = ['ok' => true, 'texto' => $r['mensaje']];
        $destino = 'tienda.php?tab=' . urlencode($r['tab'] ?? $tab);
        if (!empty($r['recibo'])) { $destino .= '&recibo=' . (int) $r['recibo']; }
        header('Location: ' . $destino);
        exit;
    } catch (InvalidArgumentException|DomainException $e) {
        $mensaje = $e->getMessage();
    } catch (Throwable $e) {
        error_log('POS: ' . $e->getMessage());
        $mensaje = 'No se completó la operación. Consulta el registro de errores de PHP.';
    }
    $_SESSION['tienda_aviso'] = ['ok' => false, 'texto' => $mensaje];
    header('Location: tienda.php?tab=' . urlencode($tab));
    exit;
}

$_SESSION['tienda_nonce'] = bin2hex(random_bytes(16));
$aviso = $_SESSION['tienda_aviso'] ?? null;
unset($_SESSION['tienda_aviso']);
$buscar = is_string($_GET['buscar'] ?? null) ? trim($_GET['buscar']) : '';
if (mb_strlen($buscar) > 100) { $buscar = mb_substr($buscar, 0, 100); }

try {
    $productos = $tab === 'productos' ? $controlador->productos->listar($buscar) : [];
    $activos = $tab === 'pos' ? $controlador->productos->activos() : [];
    $categorias = $tab === 'productos' ? $controlador->productos->categorias() : [];
    $caja = in_array($tab, ['pos', 'caja'], true) ? $controlador->cajas->abierta() : null;
    $resumen = $tab === 'caja' && $caja ? $controlador->cajas->resumen((int) $caja['id']) : null;
    $movimientosCaja = $tab === 'caja' && $caja ? $controlador->cajas->movimientos((int) $caja['id']) : [];
    $cajasAnteriores = $tab === 'caja' ? $controlador->cajas->ultimas() : [];
    $historial = $tab === 'ventas' ? $controlador->ventas->listar() : [];
    $reciboId = filter_var($_GET['recibo'] ?? null, FILTER_VALIDATE_INT);
    $recibo = $tab === 'ventas' && $reciboId && $reciboId > 0
        ? $controlador->ventas->comprobante((int) $reciboId) : null;
    $editarId = filter_var($_GET['editar'] ?? null, FILTER_VALIDATE_INT);
    $editar = $admin && $tab === 'productos' && $editarId && $editarId > 0
        ? $controlador->productos->uno((int) $editarId) : null;
    $movimientosStock = $admin && $tab === 'productos' ? $controlador->productos->movimientos() : [];
} catch (Throwable $e) {
    error_log('POS consulta: ' . $e->getMessage());
    http_response_code(503);
    exit('No se pudo consultar el módulo Tienda. Revisa la importación del SQL y la conexión MySQL.');
}

function dineroTienda(mixed $monto): string
{
    return '$' . number_format((float) $monto, 2, '.', ',');
}
$paginaActiva = 'tienda';
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tienda parroquial | Manos que Sirven</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/sidebar.css">
    <link rel="stylesheet" href="assets/css/tienda.css">
</head>
<body>
<div class="app-layout">
    <?php require __DIR__ . '/../app/views/layout/sidebar.php'; ?>
    <main class="main-content tienda-contenido">
        <header class="tienda-header no-print">
            <div><span class="tienda-etiqueta">GESTIÓN PARROQUIAL</span><h1>Tienda parroquial</h1>
                <p>Artículos religiosos, inventario comercial y caja.</p></div>
            <span class="tienda-rol">Sesión: <?= escapar((string) ($_SESSION['rol'] ?? '')) ?></span>
        </header>

        <?php if (is_array($aviso)): ?>
            <div class="alert <?= !empty($aviso['ok']) ? 'alert-success' : 'alert-danger' ?> no-print" role="alert">
                <?= escapar((string) $aviso['texto']) ?>
            </div>
        <?php endif; ?>

        <nav class="tienda-tabs no-print" aria-label="Secciones de la tienda">
            <?php foreach (['pos' => 'Registrar venta', 'productos' => 'Productos', 'caja' => 'Caja', 'ventas' => 'Historial de ventas'] as $clave => $titulo): ?>
                <a href="tienda.php?tab=<?= $clave ?>" class="<?= $tab === $clave ? 'seleccionado' : '' ?>"><?= escapar($titulo) ?></a>
            <?php endforeach; ?>
        </nav>

        <?php if ($tab === 'pos'): ?>
            <div class="tienda-panel no-print">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <div><h2 class="h5 m-0">Nueva venta</h2><small class="text-secondary">Los precios y existencias se validan al guardar.</small></div>
                    <?php if ($caja): ?><span class="badge text-bg-success">Caja #<?= (int) $caja['id'] ?> abierta</span>
                    <?php else: ?><span class="badge text-bg-warning">No hay caja abierta</span><?php endif; ?>
                </div>
                <?php if (!$caja): ?>
                    <p>Antes de vender, <a href="tienda.php?tab=caja">abre una caja</a>.</p>
                <?php else: ?>
                    <div class="row g-3 mb-3">
                        <div class="col-lg-8"><label for="selectorProducto" class="form-label">Producto</label>
                            <select class="form-select" id="selectorProducto">
                                <option value="">Selecciona un producto</option>
                                <?php foreach ($activos as $p): ?>
                                    <option value="<?= (int) $p['id'] ?>" <?= (int) $p['stock_actual'] === 0 ? 'disabled' : '' ?>>
                                        <?= escapar((string) $p['nombre']) ?> · <?= dineroTienda($p['precio_venta']) ?> · Stock <?= (int) $p['stock_actual'] ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-5 col-lg-2"><label for="selectorCantidad" class="form-label">Cantidad</label>
                            <input id="selectorCantidad" class="form-control" type="number" min="1" max="10000" value="1" step="1"></div>
                        <div class="col-7 col-lg-2 d-flex align-items-end"><button type="button" id="btnAgregar" class="btn btn-mqs w-100">Agregar</button></div>
                    </div>
                    <form method="post" action="tienda.php?tab=pos" id="formVenta">
                        <input type="hidden" name="csrf_token" value="<?= escapar(generarTokenCSRF()) ?>">
                        <input type="hidden" name="nonce_venta" value="<?= escapar($_SESSION['tienda_nonce']) ?>">
                        <input type="hidden" name="accion" value="venta_registrar">
                        <input type="hidden" id="carritoPayload" name="carrito" value="[]">
                        <div class="table-responsive"><table class="table align-middle">
                            <thead><tr><th>Producto</th><th>Cant.</th><th>Precio</th><th>Importe</th><th></th></tr></thead>
                            <tbody id="filasCarrito"><tr><td colspan="5" class="text-secondary">Agrega productos para comenzar.</td></tr></tbody>
                        </table></div>
                        <div class="row g-3 align-items-end">
                            <div class="col-md-3"><label class="form-label" for="descuento">Descuento ($)</label>
                                <input class="form-control" name="descuento" id="descuento" type="number" min="0" step="0.01" value="0.00"></div>
                            <div class="col-md-3"><label class="form-label" for="metodoPago">Pago</label>
                                <select class="form-select" id="metodoPago" name="metodo_pago"><option>Efectivo</option><option>Transferencia</option><option>Otro</option></select></div>
                            <div class="col-md-3" id="bloqueRecibido"><label class="form-label" for="montoRecibido">Efectivo recibido ($)</label>
                                <input class="form-control" name="monto_recibido" id="montoRecibido" type="number" min="0" step="0.01" value="0.00" required></div>
                            <div class="col-md-3 text-md-end"><span class="text-secondary d-block">Total estimado</span><strong class="tienda-total" id="totalVenta">$0.00</strong>
                                <small class="text-secondary d-block" id="textoCambio"></small></div>
                        </div>
                        <div class="mt-3"><button type="submit" class="btn btn-mqs" id="confirmarVenta">Confirmar venta</button></div>
                    </form>
                    <?php $productosJS = array_map(static fn(array $p): array => [
                        'id' => (int) $p['id'], 'nombre' => (string) $p['nombre'],
                        'centavos' => TiendaProducto::centavos((string) $p['precio_venta']),
                        'stock' => (int) $p['stock_actual'],
                    ], $activos); ?>
                    <script type="application/json" id="productosTiendaJSON"><?= json_encode($productosJS, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_APOS | JSON_THROW_ON_ERROR) ?></script>
                <?php endif; ?>
            </div>
        <?php elseif ($tab === 'productos'): ?>
            <?php if ($admin): ?>
                <section class="tienda-panel no-print">
                    <h2 class="h5"><?= $editar ? 'Editar producto' : 'Registrar producto' ?></h2>
                    <form method="post" action="tienda.php?tab=productos" class="row g-3">
                        <input type="hidden" name="csrf_token" value="<?= escapar(generarTokenCSRF()) ?>">
                        <input type="hidden" name="accion" value="producto_guardar">
                        <input type="hidden" name="id" value="<?= (int) ($editar['id'] ?? 0) ?>">
                        <div class="col-md-4"><label class="form-label">Nombre *</label><input required maxlength="160" class="form-control" name="nombre" value="<?= escapar((string) ($editar['nombre'] ?? '')) ?>"></div>
                        <div class="col-md-3"><label class="form-label">Código (opcional)</label><input maxlength="50" class="form-control" name="codigo" value="<?= escapar((string) ($editar['codigo'] ?? '')) ?>"></div>
                        <div class="col-md-3"><label class="form-label">Categoría</label><select class="form-select" name="categoria_id"><option value="">Sin categoría</option>
                            <?php foreach ($categorias as $categoria): ?><option value="<?= (int) $categoria['id'] ?>" <?= (int) ($editar['categoria_id'] ?? 0) === (int) $categoria['id'] ? 'selected' : '' ?>><?= escapar((string) $categoria['nombre']) ?></option><?php endforeach; ?>
                        </select></div>
                        <div class="col-md-2"><label class="form-label">Estado</label><select class="form-select" name="activo"><option value="1" <?= (int) ($editar['activo'] ?? 1) === 1 ? 'selected' : '' ?>>Activo</option><option value="0" <?= isset($editar['activo']) && (int) $editar['activo'] === 0 ? 'selected' : '' ?>>Inactivo</option></select></div>
                        <div class="col-md-3"><label class="form-label">Precio venta ($) *</label><input required class="form-control" type="number" min="0" max="1000000" step="0.01" name="precio_venta" value="<?= escapar((string) ($editar['precio_venta'] ?? '0.00')) ?>"></div>
                        <div class="col-md-3"><label class="form-label">Costo ($, opcional)</label><input class="form-control" type="number" min="0" max="1000000" step="0.01" name="precio_costo" value="<?= escapar((string) ($editar['precio_costo'] ?? '')) ?>"></div>
                        <div class="col-md-3"><label class="form-label">Stock mínimo</label><input class="form-control" type="number" min="0" max="1000000" step="1" name="stock_minimo" value="<?= (int) ($editar['stock_minimo'] ?? 0) ?>"></div>
                        <div class="col-md-12"><label class="form-label">Descripción</label><textarea class="form-control" rows="2" maxlength="10000" name="descripcion"><?= escapar((string) ($editar['descripcion'] ?? '')) ?></textarea></div>
                        <div class="col-12 d-flex gap-2"><button type="submit" class="btn btn-mqs"><?= $editar ? 'Guardar cambios' : 'Crear producto' ?></button><?php if ($editar): ?><a href="tienda.php?tab=productos" class="btn btn-outline-secondary">Cancelar</a><?php endif; ?></div>
                    </form>
                </section>
                <section class="tienda-panel no-print">
                    <h2 class="h6">Nueva categoría</h2>
                    <form method="post" action="tienda.php?tab=productos" class="d-flex flex-wrap gap-2">
                        <input type="hidden" name="csrf_token" value="<?= escapar(generarTokenCSRF()) ?>"><input type="hidden" name="accion" value="categoria_crear">
                        <input class="form-control tienda-input-mediano" maxlength="100" name="nombre_categoria" placeholder="Ej. Biblias" required>
                        <button class="btn btn-outline-success" type="submit">Agregar categoría</button>
                    </form>
                </section>
            <?php endif; ?>
            <section class="tienda-panel no-print">
                <div class="d-flex justify-content-between gap-2 flex-wrap mb-3"><h2 class="h5 mb-0">Catálogo de productos</h2>
                    <form method="get" class="d-flex gap-2"><input type="hidden" name="tab" value="productos"><input class="form-control" type="search" name="buscar" maxlength="100" placeholder="Nombre o código" value="<?= escapar($buscar) ?>"><button class="btn btn-outline-success">Buscar</button></form>
                </div>
                <div class="table-responsive"><table class="table table-hover align-middle"><thead><tr><th>Código</th><th>Producto</th><th>Categoría</th><th>Precio</th><th>Stock</th><th>Estado</th><?php if ($admin): ?><th>Editar</th><?php endif; ?></tr></thead>
                    <tbody><?php foreach ($productos as $p): ?><tr>
                        <td><?= escapar((string) ($p['codigo'] ?? '—')) ?></td><td><?= escapar((string) $p['nombre']) ?></td><td><?= escapar((string) ($p['categoria_nombre'] ?? '—')) ?></td>
                        <td><?= dineroTienda($p['precio_venta']) ?></td><td><strong class="<?= (int) $p['stock_actual'] <= (int) $p['stock_minimo'] ? 'text-danger' : '' ?>"><?= (int) $p['stock_actual'] ?></strong></td><td><?= (int) $p['activo'] === 1 ? 'Activo' : 'Inactivo' ?></td>
                        <?php if ($admin): ?><td><a href="tienda.php?tab=productos&amp;editar=<?= (int) $p['id'] ?>">Editar</a></td><?php endif; ?></tr><?php endforeach; ?>
                    <?php if (!$productos): ?><tr><td colspan="7">No hay productos registrados.</td></tr><?php endif; ?></tbody>
                </table></div>
            </section>
            <?php if ($admin): ?>
                <section class="tienda-panel no-print"><h2 class="h5">Reponer o ajustar existencias</h2>
                    <form method="post" action="tienda.php?tab=productos" class="row g-3 align-items-end">
                        <input type="hidden" name="csrf_token" value="<?= escapar(generarTokenCSRF()) ?>"><input type="hidden" name="accion" value="stock_ajustar">
                        <div class="col-md-4"><label class="form-label">Producto</label><select class="form-select" required name="producto_id"><option value="">Selecciona</option><?php foreach ($productos as $p): ?><option value="<?= (int) $p['id'] ?>"><?= escapar((string) $p['nombre']) ?> (<?= (int) $p['stock_actual'] ?>)</option><?php endforeach; ?></select></div>
                        <div class="col-md-2"><label class="form-label">Movimiento</label><select class="form-select" name="tipo"><option>Entrada</option><option>Salida</option></select></div>
                        <div class="col-md-2"><label class="form-label">Cantidad</label><input required class="form-control" name="cantidad" type="number" min="1" step="1" max="1000000"></div>
                        <div class="col-md-4"><label class="form-label">Motivo</label><input class="form-control" name="observaciones" maxlength="255" placeholder="Reposición o ajuste manual"></div>
                        <div class="col-12"><button class="btn btn-mqs">Registrar movimiento</button></div>
                    </form>
                </section>
                <section class="tienda-panel no-print"><h2 class="h5">Últimos movimientos de inventario</h2><div class="table-responsive"><table class="table table-sm"><thead><tr><th>Fecha</th><th>Producto</th><th>Tipo</th><th>Cantidad</th><th>Nuevo stock</th><th>Responsable</th></tr></thead><tbody>
                    <?php foreach ($movimientosStock as $m): ?><tr><td><?= escapar((string) $m['creado_en']) ?></td><td><?= escapar((string) $m['producto']) ?></td><td><?= escapar((string) $m['tipo']) ?></td><td><?= (int) $m['cantidad'] ?></td><td><?= (int) $m['stock_nuevo'] ?></td><td><?= escapar((string) $m['usuario']) ?></td></tr><?php endforeach; ?>
                </tbody></table></div></section>
            <?php endif; ?>
        <?php elseif ($tab === 'caja'): ?>
            <?php if (!$caja): ?>
                <section class="tienda-panel no-print"><h2 class="h5">Abrir caja</h2>
                    <form method="post" action="tienda.php?tab=caja" class="row g-3 align-items-end">
                        <input type="hidden" name="csrf_token" value="<?= escapar(generarTokenCSRF()) ?>"><input type="hidden" name="accion" value="caja_abrir">
                        <div class="col-md-4"><label class="form-label">Fondo inicial ($)</label><input required type="number" class="form-control" min="0" max="1000000" step="0.01" value="0.00" name="fondo_inicial"></div>
                        <div class="col-md-4"><button class="btn btn-mqs">Abrir caja</button></div>
                    </form>
                </section>
            <?php else: ?>
                <?php $efectivoEstimado = (float) $caja['fondo_inicial'] + (float) $resumen['efectivo'] + (float) $resumen['ingresos'] - (float) $resumen['egresos']; ?>
                <section class="tienda-panel no-print">
                    <h2 class="h5">Caja #<?= (int) $caja['id'] ?> · Abierta</h2><p class="text-secondary">Responsable: <?= escapar((string) $caja['responsable']) ?> · <?= escapar((string) $caja['fecha_apertura']) ?></p>
                    <div class="tienda-resumen">
                        <div><span>Fondo inicial</span><strong><?= dineroTienda($caja['fondo_inicial']) ?></strong></div>
                        <div><span>Ventas efectivo</span><strong><?= dineroTienda($resumen['efectivo']) ?></strong></div>
                        <div><span>Otros pagos</span><strong><?= dineroTienda($resumen['otros']) ?></strong></div>
                        <div><span>Efectivo esperado</span><strong><?= dineroTienda($efectivoEstimado) ?></strong></div>
                    </div>
                    <small class="text-secondary">El efectivo esperado considera ingresos y egresos manuales. Transferencias no se suman al efectivo físico.</small>
                </section>
                <section class="tienda-panel no-print"><h2 class="h5">Ingreso o egreso de caja</h2>
                    <form method="post" action="tienda.php?tab=caja" class="row g-3 align-items-end">
                        <input type="hidden" name="csrf_token" value="<?= escapar(generarTokenCSRF()) ?>"><input type="hidden" name="accion" value="caja_movimiento"><input type="hidden" name="caja_id" value="<?= (int) $caja['id'] ?>">
                        <div class="col-md-2"><label class="form-label">Tipo</label><select class="form-select" name="tipo"><option>Ingreso</option><option>Egreso</option></select></div>
                        <div class="col-md-3"><label class="form-label">Monto ($)</label><input required class="form-control" type="number" min="0.01" max="1000000" step="0.01" name="monto"></div>
                        <div class="col-md-5"><label class="form-label">Concepto</label><input class="form-control" required maxlength="180" name="concepto" placeholder="Ej. Pago de limpieza"></div>
                        <div class="col-md-2"><button class="btn btn-outline-success w-100">Registrar</button></div>
                    </form>
                    <div class="table-responsive mt-3"><table class="table table-sm"><thead><tr><th>Fecha</th><th>Tipo</th><th>Concepto</th><th>Monto</th></tr></thead><tbody>
                        <?php foreach ($movimientosCaja as $m): ?><tr><td><?= escapar((string) $m['creado_en']) ?></td><td><?= escapar((string) $m['tipo']) ?></td><td><?= escapar((string) $m['concepto']) ?></td><td><?= dineroTienda($m['monto']) ?></td></tr><?php endforeach; ?></tbody></table></div>
                </section>
                <?php if ($admin): ?>
                    <section class="tienda-panel no-print"><h2 class="h5">Cerrar caja (solo administrador)</h2>
                        <form method="post" action="tienda.php?tab=caja" class="row g-3 align-items-end" onsubmit="return confirm('¿Cerrar caja? Una caja cerrada no permite nuevas ventas ni anulaciones de sus ventas.');">
                            <input type="hidden" name="csrf_token" value="<?= escapar(generarTokenCSRF()) ?>"><input type="hidden" name="accion" value="caja_cerrar"><input type="hidden" name="caja_id" value="<?= (int) $caja['id'] ?>">
                            <div class="col-md-3"><label class="form-label">Efectivo contado ($)</label><input required class="form-control" type="number" min="0" max="10000000" step="0.01" name="efectivo_contado"></div>
                            <div class="col-md-6"><label class="form-label">Observaciones</label><input class="form-control" name="observaciones" maxlength="5000"></div>
                            <div class="col-md-3"><button class="btn btn-outline-danger w-100">Cerrar y calcular diferencia</button></div>
                        </form>
                    </section>
                <?php endif; ?>
            <?php endif; ?>
            <section class="tienda-panel no-print"><h2 class="h5">Últimas cajas</h2><div class="table-responsive"><table class="table table-hover table-sm"><thead><tr><th>#</th><th>Apertura</th><th>Cierre</th><th>Estado</th><th>Contado</th><th>Diferencia</th></tr></thead><tbody>
                <?php foreach ($cajasAnteriores as $r): ?><tr><td><?= (int) $r['id'] ?></td><td><?= escapar((string) $r['fecha_apertura']) ?></td><td><?= escapar((string) ($r['fecha_cierre'] ?? '—')) ?></td><td><?= escapar((string) $r['estado']) ?></td><td><?= $r['efectivo_contado'] !== null ? dineroTienda($r['efectivo_contado']) : '—' ?></td><td><?= $r['diferencia'] !== null ? dineroTienda($r['diferencia']) : '—' ?></td></tr><?php endforeach; ?>
            </tbody></table></div></section>
        <?php elseif ($tab === 'ventas'): ?>
            <?php if ($recibo): ?>
                <section class="tienda-panel tienda-recibo" id="tiendaRecibo">
                    <div class="text-center"><strong>IGLESIA DE SAN ISIDRO</strong><br>Manos que Sirven · Tienda parroquial<br><small>Comprobante interno, no es documento tributario</small></div>
                    <hr><h2 class="h6">Venta <?= escapar((string) $recibo['codigo_venta']) ?></h2>
                    <p>Fecha: <?= escapar((string) $recibo['fecha_venta']) ?><br>Vendedor: <?= escapar((string) $recibo['vendedor']) ?><br>Estado: <?= escapar((string) $recibo['estado']) ?></p>
                    <table class="table table-sm"><thead><tr><th>Producto</th><th>Cantidad</th><th>Precio</th><th>Total</th></tr></thead><tbody>
                        <?php foreach ($recibo['lineas'] as $linea): ?><tr><td><?= escapar((string) $linea['nombre_producto']) ?></td><td><?= (int) $linea['cantidad'] ?></td><td><?= dineroTienda($linea['precio_unitario']) ?></td><td><?= dineroTienda($linea['subtotal']) ?></td></tr><?php endforeach; ?></tbody></table>
                    <p>Subtotal: <strong><?= dineroTienda($recibo['subtotal']) ?></strong><br>Descuento: <strong><?= dineroTienda($recibo['descuento']) ?></strong><br>Total: <strong><?= dineroTienda($recibo['total']) ?></strong><br>Pago: <?= escapar((string) $recibo['metodo_pago']) ?>
                        <?php if ($recibo['metodo_pago'] === 'Efectivo'): ?><br>Recibido: <?= dineroTienda($recibo['monto_recibido']) ?><br>Cambio: <?= dineroTienda($recibo['cambio']) ?><?php endif; ?></p>
                    <?php if ($recibo['estado'] === 'Anulada'): ?><p class="text-danger">Venta anulada: <?= escapar((string) ($recibo['motivo_anulacion'] ?? '')) ?></p><?php endif; ?>
                    <button type="button" class="btn btn-outline-secondary no-print" onclick="window.print()">Imprimir comprobante</button>
                    <a href="tienda.php?tab=ventas" class="btn btn-outline-secondary no-print">Cerrar comprobante</a>
                </section>
            <?php endif; ?>
            <section class="tienda-panel no-print"><h2 class="h5">Historial de ventas</h2>
                <p class="text-secondary">Se muestran las últimas 80 ventas. Solo un administrador puede anular ventas de una caja todavía abierta.</p>
                <div class="table-responsive"><table class="table table-hover align-middle"><thead><tr><th>Venta</th><th>Fecha</th><th>Vendedor</th><th>Pago</th><th>Total</th><th>Estado</th><th>Acción</th></tr></thead><tbody>
                    <?php foreach ($historial as $v): ?><tr><td><?= escapar((string) $v['codigo_venta']) ?></td><td><?= escapar((string) $v['fecha_venta']) ?></td><td><?= escapar((string) $v['vendedor']) ?></td><td><?= escapar((string) $v['metodo_pago']) ?></td><td><?= dineroTienda($v['total']) ?></td><td><?= escapar((string) $v['estado']) ?></td>
                        <td><a href="tienda.php?tab=ventas&amp;recibo=<?= (int) $v['id'] ?>">Ver</a>
                            <?php if ($admin && $v['estado'] === 'Completada'): ?>
                                <form method="post" action="tienda.php?tab=ventas" class="tienda-form-inline" onsubmit="return confirm('¿Anular esta venta? Si la caja está abierta, las existencias volverán al inventario.');">
                                    <input type="hidden" name="csrf_token" value="<?= escapar(generarTokenCSRF()) ?>"><input type="hidden" name="accion" value="venta_anular"><input type="hidden" name="venta_id" value="<?= (int) $v['id'] ?>">
                                    <input required name="motivo_anulacion" maxlength="255" placeholder="Motivo" aria-label="Motivo de anulación"><button type="submit" class="btn btn-sm btn-outline-danger">Anular</button>
                                </form>
                            <?php endif; ?>
                        </td></tr><?php endforeach; ?>
                </tbody></table></div>
            </section>
        <?php endif; ?>
    </main>
</div>
<?php if ($tab === 'pos' && $caja): ?>
<script>
(() => {
    'use strict';
    const disponibles = JSON.parse(document.getElementById('productosTiendaJSON').textContent);
    const catalogo = new Map(disponibles.map(p => [String(p.id), p]));
    const carrito = new Map();
    const $ = id => document.getElementById(id);
    const formato = c => '$' + (c / 100).toFixed(2);
    function dibujar() {
        const cuerpo = $('filasCarrito');
        cuerpo.replaceChildren();
        let subtotal = 0;
        carrito.forEach((cantidad, id) => {
            const p = catalogo.get(id);
            subtotal += p.centavos * cantidad;
            const tr = document.createElement('tr');
            for (const valor of [p.nombre, String(cantidad), formato(p.centavos), formato(p.centavos * cantidad)]) {
                const td = document.createElement('td'); td.textContent = valor; tr.append(td);
            }
            const td = document.createElement('td');
            const borrar = document.createElement('button'); borrar.type = 'button';
            borrar.className = 'btn btn-sm btn-outline-danger'; borrar.textContent = 'Quitar';
            borrar.addEventListener('click', () => { carrito.delete(id); dibujar(); });
            td.append(borrar); tr.append(td); cuerpo.append(tr);
        });
        if (!carrito.size) {
            const tr = document.createElement('tr'); const td = document.createElement('td');
            td.colSpan = 5; td.className = 'text-secondary'; td.textContent = 'Agrega productos para comenzar.';
            tr.append(td); cuerpo.append(tr);
        }
        const descuento = Math.round(Number($('descuento').value || 0) * 100);
        const total = Math.max(subtotal - descuento, 0);
        $('totalVenta').textContent = formato(total);
        const recibido = Math.round(Number($('montoRecibido').value || 0) * 100);
        $('textoCambio').textContent = $('metodoPago').value === 'Efectivo'
            ? 'Cambio estimado: ' + formato(Math.max(0, recibido - total)) : '';
        $('carritoPayload').value = JSON.stringify([...carrito].map(([id,cantidad]) => ({id:Number(id),cantidad})));
    }
    $('btnAgregar').addEventListener('click', () => {
        const id = $('selectorProducto').value;
        const p = catalogo.get(id);
        const cantidad = Number($('selectorCantidad').value);
        if (!p || !Number.isInteger(cantidad) || cantidad < 1 || cantidad > 10000) {
            alert('Selecciona un producto y una cantidad válida.'); return;
        }
        const nuevo = (carrito.get(id) || 0) + cantidad;
        if (nuevo > p.stock) { alert('Cantidad superior a las existencias disponibles.'); return; }
        carrito.set(id, nuevo); dibujar();
    });
    $('metodoPago').addEventListener('change', () => {
        const efectivo = $('metodoPago').value === 'Efectivo';
        $('bloqueRecibido').hidden = !efectivo;
        $('montoRecibido').required = efectivo;
        dibujar();
    });
    $('descuento').addEventListener('input', dibujar);
    $('montoRecibido').addEventListener('input', dibujar);
    $('formVenta').addEventListener('submit', event => {
        if (!carrito.size) { event.preventDefault(); alert('Agrega al menos un producto.'); return; }
        const subtotal = [...carrito].reduce((sum,[id,c]) => sum + catalogo.get(id).centavos * c, 0);
        const descuento = Math.round(Number($('descuento').value || 0) * 100);
        if (!Number.isFinite(descuento) || descuento < 0 || descuento > subtotal) {
            event.preventDefault(); alert('Descuento inválido.'); return;
        }
        $('confirmarVenta').disabled = true;
    });
    dibujar();
})();
</script>
<?php endif; ?>
</body>
</html>
