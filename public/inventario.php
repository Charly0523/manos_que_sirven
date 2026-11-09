<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../app/Controllers/InventarioController.php';

AuthMiddleware::requiereLogin();

$usuarioId = filter_var(
    $_SESSION['usuario_id'] ?? null,
    FILTER_VALIDATE_INT
);

if ($usuarioId === false || $usuarioId === null || $usuarioId <= 0) {
    header('Location: login.php');
    exit;
}

$controlador = new InventarioController();

/*
|--------------------------------------------------------------------------
| Procesamiento de formularios
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? null;

    $accion = isset($_POST['accion']) && is_string($_POST['accion'])
        ? $_POST['accion']
        : '';

    $productoId = filter_var(
        $_POST['producto_id'] ?? null,
        FILTER_VALIDATE_INT
    );

    if (!verificarTokenCSRF(is_string($token) ? $token : null)) {
        $resultado = [
            'exito' => false,
            'mensaje' => 'La sesión del formulario venció. Recargá la página e intentá nuevamente.',
        ];
    } elseif ($accion === 'registrar') {
        $resultado = $controlador->registrar(
            $_POST,
            (int) $usuarioId
        );
    } elseif ($accion === 'actualizar') {
        $resultado = $controlador->actualizar($_POST);
    } elseif ($accion === 'ajuste') {
        $resultado = $controlador->registrarAjuste(
            $_POST,
            (int) $usuarioId
        );
    } elseif ($accion === 'cambiar_estado') {
        $estadoNuevo = isset($_POST['estado_nuevo'])
            && is_string($_POST['estado_nuevo'])
                ? $_POST['estado_nuevo']
                : '';

        if (
            $productoId === false
            || $productoId === null
            || $productoId <= 0
        ) {
            $resultado = [
                'exito' => false,
                'mensaje' => 'El identificador del producto no es válido.',
            ];
        } else {
            $resultado = $controlador->cambiarEstado(
                (int) $productoId,
                $estadoNuevo
            );
        }
    } else {
        $resultado = [
            'exito' => false,
            'mensaje' => 'La acción solicitada no es válida.',
        ];
    }

    $_SESSION['mensaje_inventario'] = $resultado;

    if (
        $accion === 'actualizar'
        && empty($resultado['exito'])
        && $productoId !== false
        && $productoId !== null
        && $productoId > 0
    ) {
        header(
            'Location: inventario.php?editar='
            . (int) $productoId
            . '#formulario-producto'
        );
    } else {
        header('Location: inventario.php');
    }

    exit;
}

/*
|--------------------------------------------------------------------------
| Mensajes
|--------------------------------------------------------------------------
*/

$mensaje = $_SESSION['mensaje_inventario'] ?? null;
unset($_SESSION['mensaje_inventario']);

/*
|--------------------------------------------------------------------------
| Filtros de productos
|--------------------------------------------------------------------------
*/

$busqueda = isset($_GET['buscar']) && is_string($_GET['buscar'])
    ? trim($_GET['buscar'])
    : '';

$categoriaFiltro = isset($_GET['categoria'])
    && is_string($_GET['categoria'])
        ? trim($_GET['categoria'])
        : '';

$estadoFiltro = isset($_GET['estado'])
    && is_string($_GET['estado'])
        ? trim($_GET['estado'])
        : '';

/*
|--------------------------------------------------------------------------
| Filtros de movimientos
|--------------------------------------------------------------------------
*/

$busquedaMovimientos = isset($_GET['buscar_movimiento'])
    && is_string($_GET['buscar_movimiento'])
        ? trim($_GET['buscar_movimiento'])
        : '';

$tipoMovimientoFiltro = isset($_GET['tipo_movimiento'])
    && is_string($_GET['tipo_movimiento'])
        ? trim($_GET['tipo_movimiento'])
        : '';

$productoMovimientoFiltro = filter_var(
    $_GET['producto_movimiento'] ?? 0,
    FILTER_VALIDATE_INT
);

if (
    $productoMovimientoFiltro === false
    || $productoMovimientoFiltro === null
    || $productoMovimientoFiltro < 0
) {
    $productoMovimientoFiltro = 0;
}

/*
|--------------------------------------------------------------------------
| Datos del inventario
|--------------------------------------------------------------------------
*/

$productos = $controlador->listar(
    $busqueda,
    $categoriaFiltro,
    $estadoFiltro
);

// Todos los productos activos para los formularios de ajustes.
$productosActivos = $controlador->listar(
    '',
    '',
    'Activo'
);

$categoriasRegistradas = $controlador->obtenerCategorias();

$categorias = [];

foreach ($categoriasRegistradas as $fila) {
    if (!empty($fila['categoria'])) {
        $categorias[] = (string) $fila['categoria'];
    }
}

$resumen = $controlador->obtenerResumen();

$movimientos = $controlador->listarMovimientos(
    $busquedaMovimientos,
    $tipoMovimientoFiltro,
    (int) $productoMovimientoFiltro,
    100
);

/*
|--------------------------------------------------------------------------
| Producto en edición
|--------------------------------------------------------------------------
*/

$productoEditar = null;

if (isset($_GET['editar'])) {
    $idEdicion = filter_var(
        $_GET['editar'],
        FILTER_VALIDATE_INT
    );

    if (
        $idEdicion === false
        || $idEdicion === null
        || $idEdicion <= 0
    ) {
        $mensaje = [
            'exito' => false,
            'mensaje' => 'El identificador del producto no es válido.',
        ];
    } else {
        $productoEditar = $controlador->obtenerPorId(
            (int) $idEdicion
        );

        if ($productoEditar === null) {
            $mensaje = [
                'exito' => false,
                'mensaje' => 'El producto que intentás editar no existe.',
            ];
        }
    }
}

/*
|--------------------------------------------------------------------------
| Información de sesión y vista
|--------------------------------------------------------------------------
*/

$nombre = (string) ($_SESSION['nombre_usuario'] ?? 'Usuario');
$rol = (string) ($_SESSION['rol'] ?? 'Sin rol');

$esAdministrador = $rol === 'Administrador';

$fechaHoy = date('Y-m-d');
$fechaActual = date('d/m/Y');

$paginaActiva = 'inventario';

require __DIR__ . '/../app/Views/inventario/index.php';
