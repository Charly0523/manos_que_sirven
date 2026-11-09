<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../app/Controllers/DonacionController.php';

AuthMiddleware::requiereLogin();

/*
|--------------------------------------------------------------------------
| Identificación del usuario
|--------------------------------------------------------------------------
*/

$usuarioId = filter_var(
    $_SESSION['usuario_id'] ?? null,
    FILTER_VALIDATE_INT
);

if (
    $usuarioId === false
    || $usuarioId === null
    || $usuarioId <= 0
) {
    header('Location: login.php');
    exit;
}

$controlador = new DonacionController();

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

    $idDonacion = filter_var(
        $_POST['donacion_id'] ?? null,
        FILTER_VALIDATE_INT
    );

    /*
    |--------------------------------------------------------------------------
    | Verificación CSRF
    |--------------------------------------------------------------------------
    */

    if (!verificarTokenCSRF(is_string($token) ? $token : null)) {

        $resultado = [
            'exito' => false,
            'mensaje' => 'La sesión del formulario venció. Recargá la página e intentá nuevamente.',
        ];

    } elseif ($accion === 'anular') {

        /*
        |--------------------------------------------------------------------------
        | Anular donación
        |--------------------------------------------------------------------------
        */

        if (
            $idDonacion === false
            || $idDonacion === null
            || $idDonacion <= 0
        ) {
            $resultado = [
                'exito' => false,
                'mensaje' => 'El identificador de la donación no es válido.',
            ];
        } else {
            $resultado = $controlador->anular(
                (int) $idDonacion,
                (int) $usuarioId
            );
        }

    } elseif ($accion === 'actualizar') {

        /*
        |--------------------------------------------------------------------------
        | Actualizar donación
        |--------------------------------------------------------------------------
        */

        $resultado = $controlador->actualizar(
            $_POST,
            (int) $usuarioId
        );

    } elseif ($accion === 'registrar') {

        /*
        |--------------------------------------------------------------------------
        | Registrar donación
        |--------------------------------------------------------------------------
        */

        $resultado = $controlador->registrar(
            $_POST,
            (int) $usuarioId
        );

    } else {

        $resultado = [
            'exito' => false,
            'mensaje' => 'La acción solicitada no es válida.',
        ];

    }

    /*
    |--------------------------------------------------------------------------
    | Guardar mensaje y redireccionar
    |--------------------------------------------------------------------------
    */

    $_SESSION['mensaje_donaciones'] = $resultado;

    if (
        $accion === 'actualizar'
        && empty($resultado['exito'])
        && $idDonacion !== false
        && $idDonacion !== null
        && $idDonacion > 0
    ) {
        header(
            'Location: donaciones.php?editar='
            . (int) $idDonacion
            . '#formulario-donacion'
        );
    } else {
        header('Location: donaciones.php');
    }

    exit;
}

/*
|--------------------------------------------------------------------------
| Recuperar mensajes
|--------------------------------------------------------------------------
*/

$mensaje = $_SESSION['mensaje_donaciones'] ?? null;

unset($_SESSION['mensaje_donaciones']);

/*
|--------------------------------------------------------------------------
| Filtros de búsqueda
|--------------------------------------------------------------------------
*/

$busqueda = isset($_GET['buscar']) && is_string($_GET['buscar'])
    ? trim($_GET['buscar'])
    : '';

$tipoFiltro = isset($_GET['tipo']) && is_string($_GET['tipo'])
    ? trim($_GET['tipo'])
    : '';

$estadoFiltro = isset($_GET['estado']) && is_string($_GET['estado'])
    ? trim($_GET['estado'])
    : '';

/*
|--------------------------------------------------------------------------
| Obtener donaciones
|--------------------------------------------------------------------------
*/

$donaciones = $controlador->listar(
    $busqueda,
    $tipoFiltro,
    $estadoFiltro
);

/*
|--------------------------------------------------------------------------
| Obtener resumen
|--------------------------------------------------------------------------
*/

$resumen = $controlador->obtenerResumen();

/*
|--------------------------------------------------------------------------
| Obtener productos disponibles del inventario
|--------------------------------------------------------------------------
| Estos productos se mostrarán en el formulario de donaciones
| en especie para evitar registrar nombres manualmente.
|--------------------------------------------------------------------------
*/

$productosInventario = $controlador->obtenerProductosInventario();

/*
|--------------------------------------------------------------------------
| Obtener donación en edición
|--------------------------------------------------------------------------
*/

$donacionEditar = null;

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
            'mensaje' => 'El identificador de la donación no es válido.',
        ];

    } else {

        $donacionEditar = $controlador->obtenerPorId(
            (int) $idEdicion
        );

        if ($donacionEditar === null) {

            $mensaje = [
                'exito' => false,
                'mensaje' => 'La donación que intentás editar no existe.',
            ];

        } elseif (
            ($donacionEditar['estado_recepcion'] ?? '') === 'Anulada'
        ) {

            $mensaje = [
                'exito' => false,
                'mensaje' => 'Las donaciones anuladas no se pueden modificar.',
            ];

            $donacionEditar = null;
        }
    }
}

/*
|--------------------------------------------------------------------------
| Información del usuario
|--------------------------------------------------------------------------
*/

$nombre = (string) (
    $_SESSION['nombre_usuario'] ?? 'Usuario'
);

$rol = (string) (
    $_SESSION['rol'] ?? 'Sin rol'
);

/*
|--------------------------------------------------------------------------
| Fechas
|--------------------------------------------------------------------------
*/

$fechaActual = date('d/m/Y');

$fechaHoy = date('Y-m-d');

/*
|--------------------------------------------------------------------------
| Módulo activo
|--------------------------------------------------------------------------
*/

$paginaActiva = 'donaciones';

/*
|--------------------------------------------------------------------------
| Cargar vista
|--------------------------------------------------------------------------
*/

require __DIR__ . '/../app/Views/donaciones/index.php';
