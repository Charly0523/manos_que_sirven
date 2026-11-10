<?php
declare(strict_types=1);

require_once __DIR__ . '/../models/Catequesis.php';

final class CatequesisController
{
    public Catequesis $modelo;

    public function __construct()
    {
        $this->modelo = new Catequesis();
    }

    public function procesar(string $accion, array $datos, int $usuarioId, bool $admin): string
    {
        try {
            switch ($accion) {
                case 'alumno_guardar':
                    $nombre = $this->texto($datos['nombre'] ?? null, 150, true);
                    $contacto = $this->texto($datos['contacto'] ?? null, 25);
                    $this->modelo->guardarAlumno($this->idOpcional($datos['id'] ?? null), $nombre, $contacto ?: null);
                    return 'Alumno guardado correctamente.';
                case 'alumno_estado':
                    $this->modelo->cambiarAlumno($this->id($datos['id'] ?? null), $this->binario($datos['activo'] ?? null));
                    return 'Estado del alumno actualizado.';
                case 'catequista_guardar':
                    $this->admin($admin);
                    $nombre = $this->texto($datos['nombre'] ?? null, 150, true);
                    $telefono = $this->texto($datos['telefono'] ?? null, 25);
                    $this->modelo->guardarCatequista($this->idOpcional($datos['id'] ?? null), $nombre, $telefono ?: null);
                    return 'Catequista guardado correctamente.';
                case 'catequista_estado':
                    $this->admin($admin);
                    $this->modelo->cambiarCatequista($this->id($datos['id'] ?? null), $this->binario($datos['activo'] ?? null));
                    return 'Estado del catequista actualizado.';
                case 'grupo_guardar':
                    $this->admin($admin);
                    $anio = $this->id($datos['anio'] ?? null);
                    if ($anio < 2020 || $anio > 2100) { throw new InvalidArgumentException('Año del grupo fuera del rango permitido.'); }
                    $this->modelo->guardarGrupo(
                        $this->idOpcional($datos['id'] ?? null),
                        $this->texto($datos['nombre'] ?? null, 120, true),
                        $this->id($datos['nivel_id'] ?? null),
                        $this->idOpcional($datos['catequista_id'] ?? null) ?: null,
                        $anio
                    );
                    return 'Grupo guardado correctamente.';
                case 'grupo_estado':
                    $this->admin($admin);
                    $this->modelo->cambiarGrupo($this->id($datos['id'] ?? null), $this->binario($datos['activo'] ?? null));
                    return 'Estado del grupo actualizado.';
                case 'inscribir':
                    $this->modelo->inscribir($this->id($datos['alumno_id'] ?? null), $this->id($datos['grupo_id'] ?? null));
                    return 'Alumno inscrito en el grupo.';
                case 'inscripcion_estado':
                    $estado = is_string($datos['estado'] ?? null) ? $datos['estado'] : '';
                    if (!in_array($estado, ['Activa','Retirada','Finalizada'], true)) {
                        throw new InvalidArgumentException('Estado de inscripción no válido.');
                    }
                    $this->modelo->cambiarInscripcion($this->id($datos['id'] ?? null), $estado);
                    return 'Inscripción actualizada.';
                case 'evaluacion_crear':
                    $this->admin($admin);
                    $this->modelo->guardarEvaluacion(
                        $this->id($datos['grupo_id'] ?? null),
                        $this->texto($datos['titulo'] ?? null, 140, true),
                        $this->fecha($datos['fecha'] ?? null)
                    );
                    return 'Evaluación creada correctamente.';
                case 'notas_guardar':
                    if (!is_array($datos['notas'] ?? null)) { throw new InvalidArgumentException('Faltan las notas.'); }
                    $cantidad = $this->modelo->guardarNotas(
                        $this->id($datos['grupo_id'] ?? null),
                        $this->id($datos['evaluacion_id'] ?? null),
                        $datos['notas'], $usuarioId
                    );
                    return "$cantidad nota(s) guardada(s). Los campos vacíos no se modifican.";
                case 'asistencia_guardar':
                    if (!is_array($datos['asistencias'] ?? null)) { throw new InvalidArgumentException('Faltan las asistencias.'); }
                    $cantidad = $this->modelo->guardarAsistencias(
                        $this->id($datos['grupo_id'] ?? null),
                        $this->fecha($datos['fecha'] ?? null),
                        $datos['asistencias'], $usuarioId
                    );
                    return "$cantidad asistencia(s) guardada(s). Los campos sin selección no se modifican.";
                default:
                    throw new InvalidArgumentException('Acción desconocida.');
            }
        } catch (PDOException $error) {
            error_log('Catequesis: ' . $error->getMessage());
            if ($error->getCode() === '23000') {
                throw new DomainException('El registro ya existe o está relacionado con otros registros.');
            }
            throw new RuntimeException('No se pudo completar la operación en la base de datos.');
        }
    }

    private function texto(mixed $valor, int $max, bool $obligatorio = false): string
    {
        if (!is_string($valor)) { throw new InvalidArgumentException('Uno de los campos de texto no es válido.'); }
        $texto = trim($valor);
        if ($obligatorio && $texto === '') { throw new InvalidArgumentException('Completa los campos obligatorios.'); }
        if (mb_strlen($texto) > $max) { throw new InvalidArgumentException('Uno de los textos supera la longitud permitida.'); }
        return $texto;
    }

    private function id(mixed $valor): int
    {
        $id = filter_var($valor, FILTER_VALIDATE_INT);
        if (!is_int($id) || $id <= 0) { throw new InvalidArgumentException('Identificador no válido.'); }
        return $id;
    }

    private function idOpcional(mixed $valor): int
    {
        return $valor === null || $valor === '' || $valor === '0' || $valor === 0 ? 0 : $this->id($valor);
    }

    private function binario(mixed $valor): int
    {
        if (!in_array((string) $valor, ['0', '1'], true)) { throw new InvalidArgumentException('Estado no válido.'); }
        return (int) $valor;
    }

    private function fecha(mixed $valor): string
    {
        if (!is_string($valor) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $valor)) {
            throw new InvalidArgumentException('Selecciona una fecha válida.');
        }
        $fecha = DateTimeImmutable::createFromFormat('!Y-m-d', $valor);
        if (!$fecha || $fecha->format('Y-m-d') !== $valor) {
            throw new InvalidArgumentException('La fecha indicada no existe.');
        }
        return $valor;
    }

    private function admin(bool $admin): void
    {
        if (!$admin) { throw new DomainException('Esta operación requiere permisos de Administrador.'); }
    }
}
