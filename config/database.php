<?php
/**
 * Conexión a base de datos usando PDO
 * Incluir este archivo en cada API/página que necesite BD
 */

require_once __DIR__ . '/config.php';

class Database {
    private static $instance = null;
    private $pdo = null;

    private function __construct() {
        try {
            $dsn = sprintf(
                'mysql:host=%s;port=3306;dbname=%s;charset=utf8mb4',
                DB_HOST, DB_NAME
            );

            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            $isProduction = getenv('APP_ENV') === 'production';
            die(json_encode([
                'error' => 'Error de conexión a base de datos',
                'detalle' => $isProduction ? 'No se puede conectar a la base de datos' : $e->getMessage(),
                'debug' => !$isProduction ? [
                    'host' => DB_HOST,
                    'database' => DB_NAME,
                    'usuario' => DB_USER
                ] : null
            ]));
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->pdo;
    }
}

// Helper para obtener conexión
function getPDO() {
    return Database::getInstance()->getConnection();
}
