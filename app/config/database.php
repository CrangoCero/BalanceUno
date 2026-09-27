<?php

namespace App\Config;

use PDO;
use PDOException;

/**
 * Clase Database
 *
 * Administra la conexión a la base de datos MySQL mediante el patrón Singleton,
 * garantizando una única instancia de PDO reutilizable en toda la aplicación.
 */
class Database
{
    /**
     * Instancia única de la conexión PDO.
     *
     * @var PDO|null
     */
    private static ?PDO $conn = null;

    /**
     * Constructor privado para prevenir la instanciación directa (Singleton).
     */
    private function __construct() {}

    /**
     * Obtiene o inicializa la conexión PDO con la base de datos.
     *
     * Lee las credenciales de las variables de entorno ($_ENV) con valores
     * de respaldo por defecto para entorno local de desarrollo (XAMPP).
     *
     * @return PDO Instancia activa de conexión a MySQL.
     * @throws PDOException Si la conexión no puede ser establecida.
     */
    public static function getConnection(): PDO
    {
        if (self::$conn === null) {
            $host     = $_ENV['DB_HOST'] ?? 'localhost';
            $dbName   = $_ENV['DB_NAME'] ?? 'balanceuno';
            $username = $_ENV['DB_USER'] ?? 'root';
            $password = $_ENV['DB_PASS'] ?? '';
            $charset  = $_ENV['DB_CHARSET'] ?? 'utf8mb4';

            $dsn = "mysql:host={$host};dbname={$dbName};charset={$charset}";

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ];

            try {
                self::$conn = new PDO($dsn, $username, $password, $options);
            } catch (PDOException $e) {
                error_log("Error crítico de conexión a la base de datos: " . $e->getMessage());
                die("Error de conexión a la base de datos. Por favor, contacte al administrador.");
            }
        }

        return self::$conn;
    }
}
