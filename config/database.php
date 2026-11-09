<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Configuración de la base de datos
|--------------------------------------------------------------------------
| Configuración temporal para desarrollo local con WampServer.
| Antes de publicar el sistema, utiliza un usuario exclusivo de MySQL
| con una contraseña segura y permisos limitados.
*/

const DB_HOST = '127.0.0.1';
const DB_NAME = 'manos_que_sirven';
const DB_USER = 'root';
const DB_PASS = '';

/**
 * Crea y devuelve una conexión PDO con la base de datos.
 */
function obtenerConexion(): PDO
{
    static $conexion = null;

    if ($conexion instanceof PDO) {
        return $conexion;
    }

    $dsn = 'mysql:host=' . DB_HOST
        . ';dbname=' . DB_NAME
        . ';charset=utf8mb4';

    $opciones = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    $conexion = new PDO(
        $dsn,
        DB_USER,
        DB_PASS,
        $opciones
    );

    return $conexion;
}
