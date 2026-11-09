<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';

final class Reporte
{
    /**
     * Obtiene las estadísticas generales del sistema.
     */
    public function obtenerResumenGeneral(): array
    {
        $conexion = obtenerConexion();

        $sqlBeneficiarios = "
            SELECT
                COUNT(*) AS total_beneficiarios,
                COALESCE(
                    SUM(CASE WHEN estado = 'Activo' THEN 1 ELSE 0 END),
                    0
                ) AS beneficiarios_activos,
                COALESCE(
                    SUM(CASE WHEN estado = 'Inactivo' THEN 1 ELSE 0 END),
                    0
                ) AS beneficiarios_inactivos
            FROM beneficiarios
        ";

        $consulta = $conexion->query($sqlBeneficiarios);
        $beneficiarios = $consulta->fetch(PDO::FETCH_ASSOC);

        $sqlAyudas = "
            SELECT
                COUNT(*) AS total_ayudas,
                COALESCE(SUM(cantidad), 0) AS total_unidades,
                COUNT(DISTINCT beneficiario_id)
                    AS beneficiarios_atendidos
            FROM ayudas
        ";

        $consulta = $conexion->query($sqlAyudas);
        $ayudas = $consulta->fetch(PDO::FETCH_ASSOC);

        return [
            'total_beneficiarios' =>
                (int) ($beneficiarios['total_beneficiarios'] ?? 0),

            'beneficiarios_activos' =>
                (int) ($beneficiarios['beneficiarios_activos'] ?? 0),

            'beneficiarios_inactivos' =>
                (int) ($beneficiarios['beneficiarios_inactivos'] ?? 0),

            'total_ayudas' =>
                (int) ($ayudas['total_ayudas'] ?? 0),

            'total_unidades' =>
                (int) ($ayudas['total_unidades'] ?? 0),

            'beneficiarios_atendidos' =>
                (int) ($ayudas['beneficiarios_atendidos'] ?? 0),
        ];
    }

    /**
     * Obtiene las ayudas entregadas dentro de un período.
     */
    public function obtenerAyudasPorPeriodo(
        string $desde,
        string $hasta,
        string $tipoAyuda = ''
    ): array {
        $sql = "
            SELECT
                a.id,
                a.beneficiario_id,
                b.nombre_completo AS beneficiario,
                b.comunidad,
                a.tipo_ayuda,
                a.descripcion,
                a.cantidad,
                a.fecha_entrega,
                a.observaciones
            FROM ayudas a
            INNER JOIN beneficiarios b
                ON b.id = a.beneficiario_id
            WHERE a.fecha_entrega BETWEEN :desde AND :hasta
        ";

        $parametros = [
            'desde' => $desde,
            'hasta' => $hasta,
        ];

        if ($tipoAyuda !== '') {
            $sql .= " AND a.tipo_ayuda = :tipo_ayuda";
            $parametros['tipo_ayuda'] = $tipoAyuda;
        }

        $sql .= "
            ORDER BY a.fecha_entrega DESC, a.id DESC
        ";

        $consulta = obtenerConexion()->prepare($sql);
        $consulta->execute($parametros);

        return $consulta->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Resume las ayudas agrupadas por tipo durante un período.
     */
    public function obtenerResumenPorTipo(
        string $desde,
        string $hasta
    ): array {
        $sql = "
            SELECT
                tipo_ayuda,
                COUNT(*) AS total_registros,
                COALESCE(SUM(cantidad), 0) AS total_unidades,
                COUNT(DISTINCT beneficiario_id)
                    AS beneficiarios_atendidos
            FROM ayudas
            WHERE fecha_entrega BETWEEN :desde AND :hasta
            GROUP BY tipo_ayuda
            ORDER BY total_unidades DESC, tipo_ayuda ASC
        ";

        $consulta = obtenerConexion()->prepare($sql);

        $consulta->execute([
            'desde' => $desde,
            'hasta' => $hasta,
        ]);

        return $consulta->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene los beneficiarios atendidos en un período.
     */
    public function obtenerBeneficiariosAtendidos(
        string $desde,
        string $hasta
    ): array {
        $sql = "
            SELECT
                b.id,
                b.nombre_completo,
                b.comunidad,
                b.estado,
                COUNT(a.id) AS total_ayudas,
                COALESCE(SUM(a.cantidad), 0) AS total_unidades,
                MAX(a.fecha_entrega) AS ultima_ayuda
            FROM beneficiarios b
            INNER JOIN ayudas a
                ON a.beneficiario_id = b.id
            WHERE a.fecha_entrega BETWEEN :desde AND :hasta
            GROUP BY
                b.id,
                b.nombre_completo,
                b.comunidad,
                b.estado
            ORDER BY b.nombre_completo ASC
        ";

        $consulta = obtenerConexion()->prepare($sql);

        $consulta->execute([
            'desde' => $desde,
            'hasta' => $hasta,
        ]);

        return $consulta->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene los tipos de ayuda registrados para los filtros.
     */
    public function obtenerTiposAyuda(): array
    {
        $sql = "
            SELECT DISTINCT tipo_ayuda
            FROM ayudas
            WHERE tipo_ayuda IS NOT NULL
              AND TRIM(tipo_ayuda) <> ''
            ORDER BY tipo_ayuda ASC
        ";

        $consulta = obtenerConexion()->query($sql);

        return $consulta->fetchAll(PDO::FETCH_COLUMN);
    }
}
