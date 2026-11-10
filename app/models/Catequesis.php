<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';

final class Catequesis
{
    private PDO $db;

    public function __construct()
    {
        $this->db = obtenerConexion();
    }

    private function consultar(string $sql, array $params = []): array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    private function uno(string $sql, array $params = []): ?array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch() ?: null;
    }

    private function ejecutar(string $sql, array $params): void
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
    }

    public function niveles(): array
    {
        return $this->consultar('SELECT id, nombre FROM catequesis_niveles ORDER BY orden, nombre');
    }

    public function alumnos(string $busqueda = ''): array
    {
        return $this->consultar(
            'SELECT * FROM catequesis_alumnos WHERE nombre_completo LIKE :busqueda ORDER BY nombre_completo LIMIT 500',
            ['busqueda' => '%' . $busqueda . '%']
        );
    }

    public function alumno(int $id): ?array
    {
        return $this->uno('SELECT * FROM catequesis_alumnos WHERE id = ?', [$id]);
    }

    public function guardarAlumno(int $id, string $nombre, ?string $contacto): void
    {
        if ($id > 0) {
            if (!$this->alumno($id)) { throw new DomainException('Alumno no encontrado.'); }
            $this->ejecutar('UPDATE catequesis_alumnos SET nombre_completo=?, contacto_responsable=? WHERE id=?', [$nombre, $contacto, $id]);
        } else {
            $this->ejecutar('INSERT INTO catequesis_alumnos (nombre_completo, contacto_responsable) VALUES (?, ?)', [$nombre, $contacto]);
        }
    }

    public function cambiarAlumno(int $id, int $activo): void
    {
        if (!$this->alumno($id)) { throw new DomainException('Alumno no encontrado.'); }
        $this->ejecutar('UPDATE catequesis_alumnos SET activo=? WHERE id=?', [$activo, $id]);
    }

    public function catequistas(): array
    {
        return $this->consultar('SELECT * FROM catequesis_catequistas ORDER BY nombre_completo');
    }

    public function catequista(int $id): ?array
    {
        return $this->uno('SELECT * FROM catequesis_catequistas WHERE id=?', [$id]);
    }

    public function guardarCatequista(int $id, string $nombre, ?string $telefono): void
    {
        if ($id > 0) {
            if (!$this->catequista($id)) { throw new DomainException('Catequista no encontrado.'); }
            $this->ejecutar('UPDATE catequesis_catequistas SET nombre_completo=?, telefono=? WHERE id=?', [$nombre, $telefono, $id]);
        } else {
            $this->ejecutar('INSERT INTO catequesis_catequistas (nombre_completo, telefono) VALUES (?, ?)', [$nombre, $telefono]);
        }
    }

    public function cambiarCatequista(int $id, int $activo): void
    {
        if (!$this->catequista($id)) { throw new DomainException('Catequista no encontrado.'); }
        $this->ejecutar('UPDATE catequesis_catequistas SET activo=? WHERE id=?', [$activo, $id]);
    }

    public function grupos(): array
    {
        return $this->consultar('SELECT g.*, n.nombre AS nivel, c.nombre_completo AS catequista
            FROM catequesis_grupos g
            JOIN catequesis_niveles n ON n.id=g.nivel_id
            LEFT JOIN catequesis_catequistas c ON c.id=g.catequista_id
            ORDER BY g.anio DESC, n.orden, g.nombre');
    }

    public function grupo(int $id): ?array
    {
        return $this->uno('SELECT * FROM catequesis_grupos WHERE id=?', [$id]);
    }

    public function guardarGrupo(int $id, string $nombre, int $nivelId, ?int $catequistaId, int $anio): void
    {
        if (!$this->uno('SELECT id FROM catequesis_niveles WHERE id=?', [$nivelId])) {
            throw new DomainException('Selecciona un nivel válido.');
        }
        if ($catequistaId !== null && !$this->uno('SELECT id FROM catequesis_catequistas WHERE id=? AND activo=1', [$catequistaId])) {
            throw new DomainException('Selecciona un catequista activo.');
        }
        if ($id > 0) {
            if (!$this->grupo($id)) { throw new DomainException('Grupo no encontrado.'); }
            $this->ejecutar('UPDATE catequesis_grupos SET nombre=?, nivel_id=?, catequista_id=?, anio=? WHERE id=?',
                [$nombre, $nivelId, $catequistaId, $anio, $id]);
        } else {
            $this->ejecutar('INSERT INTO catequesis_grupos (nombre, nivel_id, catequista_id, anio) VALUES (?,?,?,?)',
                [$nombre, $nivelId, $catequistaId, $anio]);
        }
    }

    public function cambiarGrupo(int $id, int $activo): void
    {
        if (!$this->grupo($id)) { throw new DomainException('Grupo no encontrado.'); }
        $this->ejecutar('UPDATE catequesis_grupos SET activo=? WHERE id=?', [$activo, $id]);
    }

    public function inscripciones(int $grupoId): array
    {
        return $this->consultar('SELECT i.*, a.nombre_completo AS alumno, a.activo AS alumno_activo
            FROM catequesis_inscripciones i
            JOIN catequesis_alumnos a ON a.id=i.alumno_id
            WHERE i.grupo_id=? ORDER BY a.nombre_completo', [$grupoId]);
    }

    public function inscribir(int $alumnoId, int $grupoId): void
    {
        if (!$this->uno('SELECT id FROM catequesis_alumnos WHERE id=? AND activo=1', [$alumnoId])) {
            throw new DomainException('Selecciona un alumno activo.');
        }
        if (!$this->uno('SELECT id FROM catequesis_grupos WHERE id=? AND activo=1', [$grupoId])) {
            throw new DomainException('Selecciona un grupo activo.');
        }
        $this->ejecutar('INSERT INTO catequesis_inscripciones (alumno_id, grupo_id, fecha_inscripcion) VALUES (?,?,CURDATE())',
            [$alumnoId, $grupoId]);
    }

    public function cambiarInscripcion(int $id, string $estado): void
    {
        $this->ejecutar('UPDATE catequesis_inscripciones SET estado=? WHERE id=?', [$estado, $id]);
        if (!$this->uno('SELECT id FROM catequesis_inscripciones WHERE id=?', [$id])) {
            throw new DomainException('Inscripción no encontrada.');
        }
    }

    public function evaluaciones(int $grupoId): array
    {
        return $this->consultar('SELECT * FROM catequesis_evaluaciones WHERE grupo_id=? ORDER BY fecha, id', [$grupoId]);
    }

    public function guardarEvaluacion(int $grupoId, string $titulo, string $fecha): void
    {
        if (!$this->uno('SELECT id FROM catequesis_grupos WHERE id=? AND activo=1', [$grupoId])) {
            throw new DomainException('Selecciona un grupo activo.');
        }
        $this->ejecutar('INSERT INTO catequesis_evaluaciones (grupo_id,titulo,fecha) VALUES (?,?,?)', [$grupoId, $titulo, $fecha]);
    }

    public function notasEvaluacion(int $evaluacionId): array
    {
        $filas = $this->consultar('SELECT inscripcion_id, nota FROM catequesis_notas WHERE evaluacion_id=?', [$evaluacionId]);
        $resultado = [];
        foreach ($filas as $fila) { $resultado[(int) $fila['inscripcion_id']] = (string) $fila['nota']; }
        return $resultado;
    }

    public function promediosGrupo(int $grupoId): array
    {
        return $this->consultar('SELECT i.id, a.nombre_completo AS alumno, i.estado,
                COUNT(nt.id) AS cantidad_notas, ROUND(AVG(nt.nota),2) AS promedio
            FROM catequesis_inscripciones i
            JOIN catequesis_alumnos a ON a.id=i.alumno_id
            LEFT JOIN catequesis_notas nt ON nt.inscripcion_id=i.id
                AND nt.evaluacion_id IN (SELECT id FROM catequesis_evaluaciones WHERE grupo_id=?)
            WHERE i.grupo_id=?
            GROUP BY i.id, a.nombre_completo, i.estado
            ORDER BY a.nombre_completo', [$grupoId, $grupoId]);
    }

    public function guardarNotas(int $grupoId, int $evaluacionId, array $notas, int $usuarioId): int
    {
        $cantidad = 0;
        $this->db->beginTransaction();
        try {
            $evaluacion = $this->uno('SELECT id FROM catequesis_evaluaciones WHERE id=? AND grupo_id=?', [$evaluacionId, $grupoId]);
            if (!$evaluacion) { throw new DomainException('La evaluación no corresponde al grupo.'); }
            $permitidas = $this->consultar("SELECT i.id FROM catequesis_inscripciones i
                JOIN catequesis_alumnos a ON a.id=i.alumno_id
                WHERE i.grupo_id=? AND i.estado='Activa' AND a.activo=1", [$grupoId]);
            $ids = array_fill_keys(array_map(static fn ($r) => (int) $r['id'], $permitidas), true);
            $stmt = $this->db->prepare('INSERT INTO catequesis_notas (inscripcion_id, evaluacion_id, nota, registrado_por)
                VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE nota=VALUES(nota), registrado_por=VALUES(registrado_por)');
            foreach ($notas as $id => $valor) {
                if (!is_scalar($valor)) { throw new InvalidArgumentException('Nota inválida.'); }
                $texto = trim(str_replace(',', '.', (string) $valor));
                if ($texto === '') { continue; }
                $inscripcionId = filter_var($id, FILTER_VALIDATE_INT);
                if (!is_int($inscripcionId) || !isset($ids[$inscripcionId])) {
                    throw new DomainException('Hay una inscripción no autorizada para este grupo.');
                }
                if (!preg_match('/^(?:10(?:\.0{1,2})?|[0-9](?:\.[0-9]{1,2})?)$/', $texto)) {
                    throw new InvalidArgumentException('Las notas deben estar entre 0 y 10, con máximo dos decimales.');
                }
                $stmt->execute([$inscripcionId, $evaluacionId, $texto, $usuarioId]);
                $cantidad++;
            }
            $this->db->commit();
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) { $this->db->rollBack(); }
            throw $e;
        }
        return $cantidad;
    }

    public function asistencias(int $grupoId, string $fecha): array
    {
        $filas = $this->consultar('SELECT a.inscripcion_id, a.estado
            FROM catequesis_asistencias a JOIN catequesis_inscripciones i ON i.id=a.inscripcion_id
            WHERE i.grupo_id=? AND a.fecha=?', [$grupoId, $fecha]);
        $res = [];
        foreach ($filas as $fila) { $res[(int) $fila['inscripcion_id']] = (string) $fila['estado']; }
        return $res;
    }

    public function guardarAsistencias(int $grupoId, string $fecha, array $asistencias, int $usuarioId): int
    {
        $cantidad = 0;
        $this->db->beginTransaction();
        try {
            if (!$this->uno('SELECT id FROM catequesis_grupos WHERE id=?', [$grupoId])) {
                throw new DomainException('Grupo no encontrado.');
            }
            $permitidas = $this->consultar("SELECT i.id FROM catequesis_inscripciones i
                JOIN catequesis_alumnos a ON a.id=i.alumno_id
                WHERE i.grupo_id=? AND i.estado='Activa' AND a.activo=1", [$grupoId]);
            $ids = array_fill_keys(array_map(static fn ($r) => (int) $r['id'], $permitidas), true);
            $stmt = $this->db->prepare('INSERT INTO catequesis_asistencias (inscripcion_id, fecha, estado, registrado_por)
                VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE estado=VALUES(estado), registrado_por=VALUES(registrado_por)');
            foreach ($asistencias as $id => $estado) {
                if (!is_string($estado)) { throw new InvalidArgumentException('Asistencia no válida.'); }
                if ($estado === '') { continue; }
                $inscripcionId = filter_var($id, FILTER_VALIDATE_INT);
                if (!is_int($inscripcionId) || !isset($ids[$inscripcionId])) {
                    throw new DomainException('Hay una inscripción no autorizada para este grupo.');
                }
                if (!in_array($estado, ['Presente', 'Ausente', 'Justificada'], true)) {
                    throw new InvalidArgumentException('Estado de asistencia no válido.');
                }
                $stmt->execute([$inscripcionId, $fecha, $estado, $usuarioId]);
                $cantidad++;
            }
            $this->db->commit();
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) { $this->db->rollBack(); }
            throw $e;
        }
        return $cantidad;
    }
}
