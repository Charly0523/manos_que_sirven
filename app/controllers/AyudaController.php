<?php
declare(strict_types=1);

require_once __DIR__ . '/../Models/Ayuda.php';
require_once __DIR__ . '/../Models/Beneficiario.php';
require_once __DIR__ . '/../../config/app.php';

final class AyudaController
{
    private Ayuda $modelo;

    public function __construct()
    {
        $this->modelo = new Ayuda();
    }

    public function listar(string $busqueda = ''): array
    {
        return $this->modelo->obtenerTodas(trim($busqueda));
    }

    public function obtenerPorId(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        return $this->modelo->obtenerPorId($id);
    }

    public function obtenerBeneficiarios(): array
    {
        return $this->modelo->obtenerBeneficiariosActivos();
    }

    public function registrar(array $datos, ?int $usuarioId): array
    {
        $validacion = $this->validarDatos($datos);

        if (!$validacion['exito']) {
            return $validacion;
        }

        $campos = $validacion['datos'];

        if ($usuarioId !== null && $usuarioId <= 0) {
            $usuarioId = null;
        }

        try {
            $beneficiario = (new Beneficiario())->obtenerPorId(
                $campos['beneficiario_id']
            );

            if (
                $beneficiario === null
                || ($beneficiario['estado'] ?? '') !== 'Activo'
            ) {
                return [
                    'exito' => false,
                    'mensaje' => 'El beneficiario no existe o está inactivo.',
                ];
            }

            $this->modelo->crear(
                $campos['beneficiario_id'],
                $campos['tipo_ayuda'],
                $campos['descripcion'],
                $campos['cantidad'],
                $campos['fecha_entrega'],
                $campos['observaciones'],
                $usuarioId
            );

            return [
                'exito' => true,
                'mensaje' => 'La ayuda entregada se registró correctamente.',
            ];
        } catch (Throwable $e) {
            error_log('Error al registrar ayuda: ' . $e->getMessage());

            return [
                'exito' => false,
                'mensaje' => 'No fue posible registrar la ayuda. Verificá los datos e intentá nuevamente.',
            ];
        }
    }

    public function actualizar(array $datos): array
    {
        $id = filter_var(
            $datos['ayuda_id'] ?? null,
            FILTER_VALIDATE_INT
        );

        if ($id === false || $id === null || $id <= 0) {
            return [
                'exito' => false,
                'mensaje' => 'El identificador de la ayuda no es válido.',
            ];
        }

        $validacion = $this->validarDatos($datos);

        if (!$validacion['exito']) {
            return $validacion;
        }

        $campos = $validacion['datos'];

        try {
            $ayudaExistente = $this->modelo->obtenerPorId((int) $id);

            if ($ayudaExistente === null) {
                return [
                    'exito' => false,
                    'mensaje' => 'La ayuda que intentás editar no existe.',
                ];
            }

            $beneficiario = (new Beneficiario())->obtenerPorId(
                $campos['beneficiario_id']
            );

            if (
                $beneficiario === null
                || ($beneficiario['estado'] ?? '') !== 'Activo'
            ) {
                return [
                    'exito' => false,
                    'mensaje' => 'Seleccioná un beneficiario existente y activo.',
                ];
            }

            $actualizado = $this->modelo->actualizar(
                (int) $id,
                $campos['beneficiario_id'],
                $campos['tipo_ayuda'],
                $campos['descripcion'],
                $campos['cantidad'],
                $campos['fecha_entrega'],
                $campos['observaciones']
            );

            if (!$actualizado) {
                return [
                    'exito' => false,
                    'mensaje' => 'No fue posible actualizar la ayuda.',
                ];
            }

            return [
                'exito' => true,
                'mensaje' => 'La ayuda se actualizó correctamente.',
            ];
        } catch (Throwable $e) {
            error_log('Error al actualizar ayuda: ' . $e->getMessage());

            return [
                'exito' => false,
                'mensaje' => 'Ocurrió un error al actualizar la ayuda. Intentá nuevamente.',
            ];
        }
    }

    public function eliminar(int $id): array
    {
        if ($id <= 0) {
            return [
                'exito' => false,
                'mensaje' => 'El identificador de la ayuda no es válido.',
            ];
        }

        try {
            $ayuda = $this->modelo->obtenerPorId($id);

            if ($ayuda === null) {
                return [
                    'exito' => false,
                    'mensaje' => 'La ayuda que intentás eliminar no existe o ya fue eliminada.',
                ];
            }

            $eliminado = $this->modelo->eliminar($id);

            if (!$eliminado) {
                return [
                    'exito' => false,
                    'mensaje' => 'No fue posible eliminar la ayuda.',
                ];
            }

            return [
                'exito' => true,
                'mensaje' => 'La ayuda se eliminó correctamente.',
            ];
        } catch (Throwable $e) {
            error_log('Error al eliminar ayuda: ' . $e->getMessage());

            return [
                'exito' => false,
                'mensaje' => 'Ocurrió un error al eliminar la ayuda. Intentá nuevamente.',
            ];
        }
    }

    private function validarDatos(array $datos): array
    {
        $beneficiarioId = filter_var(
            $datos['beneficiario_id'] ?? null,
            FILTER_VALIDATE_INT
        );

        $tipoAyuda = trim((string) ($datos['tipo_ayuda'] ?? ''));
        $descripcion = trim((string) ($datos['descripcion'] ?? ''));

        $cantidad = filter_var(
            $datos['cantidad'] ?? null,
            FILTER_VALIDATE_INT
        );

        $fechaEntrega = trim((string) ($datos['fecha_entrega'] ?? ''));
        $observaciones = trim((string) ($datos['observaciones'] ?? ''));

        if (
            $beneficiarioId === false
            || $beneficiarioId === null
            || $beneficiarioId <= 0
        ) {
            return [
                'exito' => false,
                'mensaje' => 'Seleccioná un beneficiario válido.',
            ];
        }

        if (
            $tipoAyuda === ''
            || mb_strlen($tipoAyuda) > 100
        ) {
            return [
                'exito' => false,
                'mensaje' => 'Ingresá un tipo de ayuda válido de máximo 100 caracteres.',
            ];
        }

        if ($descripcion === '' || mb_strlen($descripcion) > 5000) {
            return [
                'exito' => false,
                'mensaje' => 'La descripción es obligatoria y no debe superar los 5000 caracteres.',
            ];
        }

        if (
            $cantidad === false
            || $cantidad === null
            || $cantidad <= 0
        ) {
            return [
                'exito' => false,
                'mensaje' => 'La cantidad debe ser un número entero mayor que cero.',
            ];
        }

        $fecha = DateTimeImmutable::createFromFormat(
            '!Y-m-d',
            $fechaEntrega
        );

        $erroresFecha = DateTimeImmutable::getLastErrors();

        if (
            !$fecha
            || (
                $erroresFecha !== false
                && (
                    $erroresFecha['warning_count'] > 0
                    || $erroresFecha['error_count'] > 0
                )
            )
            || $fecha->format('Y-m-d') !== $fechaEntrega
        ) {
            return [
                'exito' => false,
                'mensaje' => 'Ingresá una fecha de entrega válida.',
            ];
        }

        if ($fechaEntrega > date('Y-m-d')) {
            return [
                'exito' => false,
                'mensaje' => 'La fecha de entrega no puede ser futura.',
            ];
        }

        if (mb_strlen($observaciones) > 5000) {
            return [
                'exito' => false,
                'mensaje' => 'Las observaciones no deben superar los 5000 caracteres.',
            ];
        }

        return [
            'exito' => true,
            'datos' => [
                'beneficiario_id' => (int) $beneficiarioId,
                'tipo_ayuda' => $tipoAyuda,
                'descripcion' => $descripcion,
                'cantidad' => (int) $cantidad,
                'fecha_entrega' => $fechaEntrega,
                'observaciones' => $observaciones !== ''
                    ? $observaciones
                    : null,
            ],
        ];
    }

    public function obtenerResumen(): array
    {
        return $this->modelo->obtenerResumen();
    }
}
