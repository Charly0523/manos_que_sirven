-- Manos que Sirven | Paso 8: base de datos del mini POS parroquial
-- Importar en phpMyAdmin. No modifica ni elimina las tablas existentes.
USE `manos_que_sirven`;

CREATE TABLE IF NOT EXISTS `tienda_categorias` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `nombre` VARCHAR(100) NOT NULL,
    `descripcion` VARCHAR(255) DEFAULT NULL,
    `activo` TINYINT(1) NOT NULL DEFAULT 1,
    `creado_en` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_tienda_categorias_nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tienda_productos` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `categoria_id` INT UNSIGNED DEFAULT NULL,
    `codigo` VARCHAR(50) DEFAULT NULL,
    `nombre` VARCHAR(160) NOT NULL,
    `descripcion` TEXT DEFAULT NULL,
    `precio_venta` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `precio_costo` DECIMAL(12,2) DEFAULT NULL,
    `stock_actual` INT UNSIGNED NOT NULL DEFAULT 0,
    `stock_minimo` INT UNSIGNED NOT NULL DEFAULT 0,
    `activo` TINYINT(1) NOT NULL DEFAULT 1,
    `creado_en` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `actualizado_en` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_tienda_productos_codigo` (`codigo`),
    KEY `idx_tienda_productos_categoria` (`categoria_id`),
    KEY `idx_tienda_productos_nombre` (`nombre`),
    KEY `idx_tienda_productos_activo` (`activo`),
    CONSTRAINT `fk_tienda_productos_categoria`
        FOREIGN KEY (`categoria_id`) REFERENCES `tienda_categorias` (`id`)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `chk_tienda_productos_precio` CHECK (`precio_venta` >= 0),
    CONSTRAINT `chk_tienda_productos_costo` CHECK (`precio_costo` IS NULL OR `precio_costo` >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tienda_cajas` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `usuario_apertura_id` INT UNSIGNED NOT NULL,
    `usuario_cierre_id` INT UNSIGNED DEFAULT NULL,
    `fecha_apertura` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `fecha_cierre` DATETIME DEFAULT NULL,
    `fondo_inicial` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `efectivo_esperado` DECIMAL(12,2) DEFAULT NULL,
    `efectivo_contado` DECIMAL(12,2) DEFAULT NULL,
    `diferencia` DECIMAL(12,2) DEFAULT NULL,
    `estado` ENUM('Abierta', 'Cerrada') NOT NULL DEFAULT 'Abierta',
    `observaciones` TEXT DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_tienda_cajas_estado` (`estado`),
    KEY `idx_tienda_cajas_fecha` (`fecha_apertura`),
    CONSTRAINT `fk_tienda_cajas_apertura`
        FOREIGN KEY (`usuario_apertura_id`) REFERENCES `usuarios` (`id`)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `fk_tienda_cajas_cierre`
        FOREIGN KEY (`usuario_cierre_id`) REFERENCES `usuarios` (`id`)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `chk_tienda_cajas_fondo` CHECK (`fondo_inicial` >= 0),
    CONSTRAINT `chk_tienda_cajas_contado` CHECK (`efectivo_contado` IS NULL OR `efectivo_contado` >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tienda_ventas` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `caja_id` BIGINT UNSIGNED NOT NULL,
    `usuario_id` INT UNSIGNED NOT NULL,
    `codigo_venta` VARCHAR(40) DEFAULT NULL,
    `fecha_venta` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `subtotal` DECIMAL(12,2) NOT NULL,
    `descuento` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `total` DECIMAL(12,2) NOT NULL,
    `metodo_pago` ENUM('Efectivo', 'Transferencia', 'Otro') NOT NULL,
    `monto_recibido` DECIMAL(12,2) DEFAULT NULL,
    `cambio` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `estado` ENUM('Completada', 'Anulada') NOT NULL DEFAULT 'Completada',
    `usuario_anulacion_id` INT UNSIGNED DEFAULT NULL,
    `fecha_anulacion` DATETIME DEFAULT NULL,
    `motivo_anulacion` VARCHAR(255) DEFAULT NULL,
    `observaciones` TEXT DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_tienda_ventas_codigo` (`codigo_venta`),
    KEY `idx_tienda_ventas_caja` (`caja_id`),
    KEY `idx_tienda_ventas_usuario` (`usuario_id`),
    KEY `idx_tienda_ventas_fecha` (`fecha_venta`),
    KEY `idx_tienda_ventas_estado` (`estado`),
    CONSTRAINT `fk_tienda_ventas_caja`
        FOREIGN KEY (`caja_id`) REFERENCES `tienda_cajas` (`id`)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `fk_tienda_ventas_usuario`
        FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `fk_tienda_ventas_anulacion`
        FOREIGN KEY (`usuario_anulacion_id`) REFERENCES `usuarios` (`id`)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `chk_tienda_ventas_montos` CHECK (
        `subtotal` >= 0 AND `descuento` >= 0 AND `descuento` <= `subtotal`
        AND `total` >= 0 AND (`monto_recibido` IS NULL OR `monto_recibido` >= 0)
    ),
    CONSTRAINT `chk_tienda_ventas_cambio` CHECK (`cambio` >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tienda_venta_detalles` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `venta_id` BIGINT UNSIGNED NOT NULL,
    `producto_id` INT UNSIGNED NOT NULL,
    `nombre_producto` VARCHAR(160) NOT NULL,
    `cantidad` INT UNSIGNED NOT NULL,
    `precio_unitario` DECIMAL(12,2) NOT NULL,
    `subtotal` DECIMAL(12,2) NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_tienda_detalle_producto` (`venta_id`, `producto_id`),
    KEY `idx_tienda_detalle_producto` (`producto_id`),
    CONSTRAINT `fk_tienda_detalle_venta`
        FOREIGN KEY (`venta_id`) REFERENCES `tienda_ventas` (`id`)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `fk_tienda_detalle_producto`
        FOREIGN KEY (`producto_id`) REFERENCES `tienda_productos` (`id`)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `chk_tienda_detalle_cantidad` CHECK (`cantidad` > 0),
    CONSTRAINT `chk_tienda_detalle_precios` CHECK (`precio_unitario` >= 0 AND `subtotal` >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tienda_inventario_movimientos` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `producto_id` INT UNSIGNED NOT NULL,
    `venta_id` BIGINT UNSIGNED DEFAULT NULL,
    `usuario_id` INT UNSIGNED NOT NULL,
    `tipo` ENUM('Entrada', 'Salida', 'Ajuste positivo', 'Ajuste negativo') NOT NULL,
    `origen` ENUM('Reposicion', 'Venta', 'Anulacion', 'Ajuste') NOT NULL,
    `cantidad` INT UNSIGNED NOT NULL,
    `stock_anterior` INT UNSIGNED NOT NULL,
    `stock_nuevo` INT UNSIGNED NOT NULL,
    `observaciones` VARCHAR(255) DEFAULT NULL,
    `creado_en` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_tienda_movimientos_producto` (`producto_id`),
    KEY `idx_tienda_movimientos_venta` (`venta_id`),
    KEY `idx_tienda_movimientos_fecha` (`creado_en`),
    CONSTRAINT `fk_tienda_movimiento_producto`
        FOREIGN KEY (`producto_id`) REFERENCES `tienda_productos` (`id`)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `fk_tienda_movimiento_venta`
        FOREIGN KEY (`venta_id`) REFERENCES `tienda_ventas` (`id`)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `fk_tienda_movimiento_usuario`
        FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `chk_tienda_movimiento_cantidad` CHECK (`cantidad` > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Solo ingresos/egresos manuales: las ventas en efectivo se calculan desde tienda_ventas.
CREATE TABLE IF NOT EXISTS `tienda_caja_movimientos` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `caja_id` BIGINT UNSIGNED NOT NULL,
    `usuario_id` INT UNSIGNED NOT NULL,
    `tipo` ENUM('Ingreso', 'Egreso') NOT NULL,
    `concepto` VARCHAR(180) NOT NULL,
    `monto` DECIMAL(12,2) NOT NULL,
    `creado_en` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_tienda_caja_movimientos_caja` (`caja_id`),
    KEY `idx_tienda_caja_movimientos_fecha` (`creado_en`),
    CONSTRAINT `fk_tienda_caja_movimientos_caja`
        FOREIGN KEY (`caja_id`) REFERENCES `tienda_cajas` (`id`)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `fk_tienda_caja_movimientos_usuario`
        FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `chk_tienda_caja_movimientos_monto` CHECK (`monto` > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
