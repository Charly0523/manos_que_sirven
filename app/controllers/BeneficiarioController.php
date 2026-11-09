<?php
declare(strict_types=1);

require_once __DIR__ . '/../Models/Beneficiario.php';
require_once __DIR__ . '/../../config/app.php';

final class BeneficiarioController
{
    private Beneficiario $modelo;

    public function __construct()
    {
        $this->modelo = new Beneficiario();
    }

    /**
     * Listar o buscar beneficiarios.
     */
    public function listar(string $busqueda = ''): array
    {
        $busqueda = trim($busqueda);

        if ($busqueda !== '') {
            return $this->modelo->buscar($busqueda);
        }

        return $this->modelo->obtenerTodos();
    }

    /**
     * Registrar un beneficiario.
     */
    public function registrar(array $datos, int $usuarioId): array
    {
        $nombre = trim((string) ($datos['nombre_completo'] ?? ''));
        $telefono = trim((string) ($datos['telefono'] ?? ''));
        $direccion = trim((string) ($datos['direccion'] ?? ''));
        $comunidad = trim((string) ($datos['comunidad'] ?? ''));

        if ($nombre === '') {
            return [
                'exito' => false,
                'mensaje' => 'El nombre completo es obligatorio.',
            ];
        }

        if (mb_strlen($nombre) > 150) {
            return [
                'exito' => false,
                'mensaje' => 'El nombre no puede superar los 150 caracteres.',
            ];
        }

        if (mb_strlen($telefono) > 25) {
            return [
                'exito' => false,
                'mensaje' => 'El teléfono no puede superar los 25 caracteres.',
            ];
        }

        if (mb_strlen($direccion) > 255) {
            return [
                'exito' => false,
                'mensaje' => 'La dirección no puede superar los 255 caracteres.',
            ];
        }

        if (mb_strlen($comunidad) > 120) {
            return [
                'exito' => false,
                'mensaje' => 'La comunidad no puede superar los 120 caracteres.',
            ];
        }

        if ($usuarioId <= 0) {
            return [
                'exito' => false,
                'mensaje' => 'No se pudo identificar al usuario que realiza el registro.',
            ];
        }

        $resultado = $this->modelo->crear(
            $nombre,
            $telefono !== '' ? $telefono : null,
            $direccion !== '' ? $direccion : null,
            $comunidad !== '' ? $comunidad : null,
            $usuarioId
        );

        if (!$resultado) {
            return [
                'exito' => false,
                'mensaje' => 'No fue posible registrar al beneficiario.',
            ];
        }

        return [
            'exito' => true,
            'mensaje' => 'Beneficiario registrado correctamente.',
        ];
    }

    /**
     * Actualizar un beneficiario existente.
     */
    public function actualizar(int $id, array $datos): array
    {
        if ($id <= 0) {
            return [
                'exito' => false,
                'mensaje' => 'El beneficiario seleccionado no es válido.',
            ];
        }

        $beneficiario = $this->modelo->obtenerPorId($id);

        if ($beneficiario === null) {
            return [
                'exito' => false,
                'mensaje' => 'El beneficiario no existe.',
            ];
        }

        $nombre = trim((string) ($datos['nombre_completo'] ?? ''));
        $telefono = trim((string) ($datos['telefono'] ?? ''));
        $direccion = trim((string) ($datos['direccion'] ?? ''));
        $comunidad = trim((string) ($datos['comunidad'] ?? ''));

        if ($nombre === '') {
            return [
                'exito' => false,
                'mensaje' => 'El nombre completo es obligatorio.',
            ];
        }

        if (
            mb_strlen($nombre) > 150 ||
            mb_strlen($telefono) > 25 ||
            mb_strlen($direccion) > 255 ||
            mb_strlen($comunidad) > 120
        ) {
            return [
                'exito' => false,
                'mensaje' => 'Uno o más campos superan la longitud permitida.',
            ];
        }

        $this->modelo->actualizar(
            $id,
            $nombre,
            $telefono !== '' ? $telefono : null,
            $direccion !== '' ? $direccion : null,
            $comunidad !== '' ? $comunidad : null
        );

        return [
            'exito' => true,
            'mensaje' => 'Beneficiario actualizado correctamente.',
        ];
    }

    /**
     * Activar o desactivar un beneficiario.
     */
    public function cambiarEstado(int $id, string $estado): array
    {
        if ($id <= 0 || !in_array($estado, ['Activo', 'Inactivo'], true)) {
            return [
                'exito' => false,
                'mensaje' => 'Los datos para cambiar el estado no son válidos.',
            ];
        }

        if ($this->modelo->obtenerPorId($id) === null) {
            return [
                'exito' => false,
                'mensaje' => 'El beneficiario no existe.',
            ];
        }

        $this->modelo->cambiarEstado($id, $estado);

        return [
            'exito' => true,
            'mensaje' => 'El estado del beneficiario se actualizó correctamente.',
        ];
    }

    /**
     * Obtener las cantidades para los indicadores.
     */
    public function obtenerResumen(): array
    {
        return [
            'activos' => $this->modelo->contarPorEstado('Activo'),
            'inactivos' => $this->modelo->contarPorEstado('Inactivo'),
        ];
    }
}
