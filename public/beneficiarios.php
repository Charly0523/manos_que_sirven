<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../app/Controllers/BeneficiarioController.php';

AuthMiddleware::requiereLogin();

$controlador = new BeneficiarioController();

$mensaje = '';
$tipoMensaje = 'success';
$busqueda = trim((string) ($_GET['buscar'] ?? ''));
$accion = (string) ($_GET['accion'] ?? '');

$beneficiarioEditar = null;

/*
 * Procesar formularios.
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verificarTokenCSRF($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        exit('La solicitud ha caducado o no es válida. Recarga la página e inténtalo de nuevo.');
    }

    $accionPost = (string) ($_POST['accion'] ?? '');

    try {
        switch ($accionPost) {
            case 'registrar':
                $resultado = $controlador->registrar(
                    $_POST,
                    (int) $_SESSION['usuario_id']
                );
                break;

            case 'actualizar':
                $resultado = $controlador->actualizar(
                    (int) ($_POST['id'] ?? 0),
                    $_POST
                );
                break;

            case 'cambiar_estado':
                $resultado = $controlador->cambiarEstado(
                    (int) ($_POST['id'] ?? 0),
                    (string) ($_POST['estado'] ?? '')
                );
                break;

            default:
                $resultado = [
                    'exito' => false,
                    'mensaje' => 'La acción solicitada no es válida.',
                ];
        }

        $mensaje = $resultado['mensaje'];
        $tipoMensaje = $resultado['exito'] ? 'success' : 'danger';

        if ($resultado['exito']) {
            $_SESSION['mensaje_beneficiarios'] = $mensaje;
            $_SESSION['tipo_mensaje_beneficiarios'] = $tipoMensaje;

            header('Location: beneficiarios.php');
            exit;
        }
    } catch (Throwable $e) {
        error_log('Error en beneficiarios: ' . $e->getMessage());

        $mensaje = 'Ocurrió un error al procesar la solicitud. Inténtalo nuevamente.';
        $tipoMensaje = 'danger';
    }
}

/*
 * Recuperar mensajes después de una redirección.
 */
if (isset($_SESSION['mensaje_beneficiarios'])) {
    $mensaje = (string) $_SESSION['mensaje_beneficiarios'];
    $tipoMensaje = (string) ($_SESSION['tipo_mensaje_beneficiarios'] ?? 'success');

    unset(
        $_SESSION['mensaje_beneficiarios'],
        $_SESSION['tipo_mensaje_beneficiarios']
    );
}

/*
 * Cargar el beneficiario seleccionado para editar.
 */
if ($accion === 'editar') {
    $idEditar = (int) ($_GET['id'] ?? 0);

    if ($idEditar > 0) {
        $modelo = new Beneficiario();
        $beneficiarioEditar = $modelo->obtenerPorId($idEditar);
    }

    if ($beneficiarioEditar === null) {
        $mensaje = 'No se encontró el beneficiario seleccionado.';
        $tipoMensaje = 'warning';
        $accion = '';
    }
}

/*
 * Consultar los registros y los indicadores.
 */
try {
    $beneficiarios = $controlador->listar($busqueda);
    $resumen = $controlador->obtenerResumen();
} catch (Throwable $e) {
    error_log('Error al consultar beneficiarios: ' . $e->getMessage());

    $beneficiarios = [];
    $resumen = ['activos' => 0, 'inactivos' => 0];
    $mensaje = 'No se pudieron cargar los beneficiarios. Revisa la conexión con la base de datos.';
    $tipoMensaje = 'danger';
}

/*
 * Mostrar la vista.
 */
$nombreUsuario = (string) ($_SESSION['nombre_usuario'] ?? 'Usuario');
$rolUsuario = (string) ($_SESSION['rol'] ?? '');

require_once __DIR__ . '/../app/Views/beneficiarios/index.php';
