<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';

final class TiendaProducto
{
    private PDO $db;

    public function __construct()
    {
        $this->db = obtenerConexion();
    }

    public function categorias(): array
    {
        return $this->db->query('SELECT id, nombre, activo FROM tienda_categorias ORDER BY nombre')->fetchAll();
    }

    public function crearCategoria(string $nombre): void
    {
        $nombre = trim($nombre);
        if ($nombre === '' || mb_strlen($nombre) > 100) {
            throw new InvalidArgumentException('El nombre de la categoría debe tener entre 1 y 100 caracteres.');
        }
        $stmt = $this->db->prepare('INSERT INTO tienda_categorias (nombre) VALUES (?)');
        $stmt->execute([$nombre]);
    }

    public function listar(string $buscar = ''): array
    {
        $stmt = $this->db->prepare('SELECT p.*, c.nombre AS categoria_nombre
            FROM tienda_productos p LEFT JOIN tienda_categorias c ON c.id = p.categoria_id
            WHERE (:vacio = 1 OR p.nombre LIKE :nombre OR p.codigo LIKE :codigo)
            ORDER BY p.activo DESC, p.nombre ASC');
        $stmt->execute([
            'vacio' => $buscar === '' ? 1 : 0,
            'nombre' => '%' . $buscar . '%',
            'codigo' => '%' . $buscar . '%',
        ]);
        return $stmt->fetchAll();
    }

    public function activos(): array
    {
        return $this->db->query('SELECT id, codigo, nombre, precio_venta, stock_actual
            FROM tienda_productos WHERE activo = 1 ORDER BY nombre')->fetchAll();
    }

    public function uno(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM tienda_productos WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function guardar(array $datos): void
    {
        $id = filter_var($datos['id'] ?? 0, FILTER_VALIDATE_INT) ?: 0;
        $nombre = trim((string) ($datos['nombre'] ?? ''));
        $codigo = trim((string) ($datos['codigo'] ?? ''));
        $descripcion = trim((string) ($datos['descripcion'] ?? ''));
        $cat = filter_var($datos['categoria_id'] ?? null, FILTER_VALIDATE_INT);
        $categoriaId = $cat && $cat > 0 ? $cat : null;
        $precio = self::centavos((string) ($datos['precio_venta'] ?? ''));
        $costoTexto = trim((string) ($datos['precio_costo'] ?? ''));
        $costo = $costoTexto !== '' ? self::centavos($costoTexto) : null;
        $minimo = filter_var($datos['stock_minimo'] ?? null, FILTER_VALIDATE_INT);
        $activo = (string) ($datos['activo'] ?? '1') === '1' ? 1 : 0;

        if ($nombre === '' || mb_strlen($nombre) > 160 || mb_strlen($codigo) > 50 || mb_strlen($descripcion) > 10000) {
            throw new InvalidArgumentException('Revisa nombre, código y descripción del producto.');
        }
        if ($minimo === false || $minimo === null || $minimo < 0 || $minimo > 1000000) {
            throw new InvalidArgumentException('El stock mínimo debe ser un entero entre 0 y 1000000.');
        }
        if ($precio > 100000000 || ($costo !== null && $costo > 100000000)) {
            throw new InvalidArgumentException('El precio máximo permitido es $1,000,000.00.');
        }
        $parametros = [
            'categoria_id' => $categoriaId,
            'codigo' => $codigo !== '' ? $codigo : null,
            'nombre' => $nombre,
            'descripcion' => $descripcion !== '' ? $descripcion : null,
            'precio_venta' => self::decimal($precio),
            'precio_costo' => $costo === null ? null : self::decimal($costo),
            'stock_minimo' => $minimo,
            'activo' => $activo,
        ];
        if ($id > 0) {
            if ($this->uno($id) === null) {
                throw new InvalidArgumentException('El producto ya no existe.');
            }
            $parametros['id'] = $id;
            $stmt = $this->db->prepare('UPDATE tienda_productos SET categoria_id=:categoria_id,
                codigo=:codigo,nombre=:nombre,descripcion=:descripcion,precio_venta=:precio_venta,
                precio_costo=:precio_costo,stock_minimo=:stock_minimo,activo=:activo WHERE id=:id');
        } else {
            $stmt = $this->db->prepare('INSERT INTO tienda_productos
                (categoria_id,codigo,nombre,descripcion,precio_venta,precio_costo,stock_minimo,activo,stock_actual)
                VALUES (:categoria_id,:codigo,:nombre,:descripcion,:precio_venta,:precio_costo,:stock_minimo,:activo,0)');
        }
        $stmt->execute($parametros);
    }

    public function ajustarExistencias(int $id, string $tipo, int $cantidad, int $usuarioId, string $motivo): void
    {
        if (!in_array($tipo, ['Entrada','Salida'], true) || $id < 1 || $cantidad < 1 || $cantidad > 1000000) {
            throw new InvalidArgumentException('Datos del movimiento de inventario no válidos.');
        }
        if (mb_strlen($motivo) > 255) {
            throw new InvalidArgumentException('La observación es demasiado extensa.');
        }
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare('SELECT stock_actual FROM tienda_productos WHERE id = ? FOR UPDATE');
            $stmt->execute([$id]);
            $anterior = $stmt->fetchColumn();
            if ($anterior === false) {
                throw new InvalidArgumentException('Producto no encontrado.');
            }
            $anterior = (int) $anterior;
            $nuevo = $tipo === 'Entrada' ? $anterior + $cantidad : $anterior - $cantidad;
            if ($nuevo < 0 || $nuevo > 4294967295) {
                throw new InvalidArgumentException('El movimiento dejaría existencias inválidas.');
            }
            $stmt = $this->db->prepare('UPDATE tienda_productos SET stock_actual = ? WHERE id = ?');
            $stmt->execute([$nuevo, $id]);
            $stmt = $this->db->prepare('INSERT INTO tienda_inventario_movimientos
                (producto_id,usuario_id,tipo,origen,cantidad,stock_anterior,stock_nuevo,observaciones)
                VALUES (?,?,?, ?,?,?,?,?)');
            $stmt->execute([
                $id, $usuarioId, $tipo, $tipo === 'Entrada' ? 'Reposicion' : 'Ajuste',
                $cantidad, $anterior, $nuevo, $motivo !== '' ? $motivo : null,
            ]);
            $this->db->commit();
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) { $this->db->rollBack(); }
            throw $e;
        }
    }

    public function movimientos(): array
    {
        return $this->db->query('SELECT m.*, p.nombre AS producto, u.usuario
            FROM tienda_inventario_movimientos m
            JOIN tienda_productos p ON p.id=m.producto_id
            JOIN usuarios u ON u.id=m.usuario_id
            ORDER BY m.id DESC LIMIT 40')->fetchAll();
    }

    public static function centavos(string $entrada): int
    {
        $entrada = trim($entrada);
        if (!preg_match('/^\d{1,10}(?:\.\d{1,2})?$/D', $entrada)) {
            throw new InvalidArgumentException('Utiliza un importe válido, por ejemplo 12.50.');
        }
        [$entero, $decimal] = array_pad(explode('.', $entrada, 2), 2, '');
        $centavos = ((int) $entero) * 100 + (int) str_pad($decimal, 2, '0');
        if ($centavos > 999999999999) {
            throw new InvalidArgumentException('Importe fuera de rango.');
        }
        return $centavos;
    }

    public static function decimal(int $centavos): string
    {
        return intdiv($centavos, 100) . '.' . str_pad((string) ($centavos % 100), 2, '0', STR_PAD_LEFT);
    }
}
