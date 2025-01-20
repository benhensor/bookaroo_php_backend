<?php
namespace Config;

class Database {
    private static $instance = null;
    private $connection;

    private function __construct() {
        try {
            $host = $_ENV['DB_HOST'] ?? 'localhost';
            $port = $_ENV['DB_PORT'] ?? '3306';
            $database = $_ENV['DB_DATABASE'] ?? 'rebook_db';
            $username = $_ENV['DB_USERNAME'] ?? 'root';
            $password = $_ENV['DB_PASSWORD'] ?? '';

            $dsn = "mysql:host={$host};port={$port};dbname={$database}";
            
            $options = [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_EMULATE_PREPARES => false,
            ];

            try {
                $this->connection = new \PDO($dsn, $username, $password, $options);
            } catch (\PDOException $e) {
                // Log the actual PDO error and connection details (remove in production)
                error_log("DSN: " . $dsn);
                error_log("Username: " . $username);
                error_log("PDO Error: " . $e->getMessage());
                throw $e;
            }
        } catch (\Exception $e) {
            // Convert the PDO error message into the response
            http_response_code(500);
            echo json_encode([
                'error' => 'Database connection failed',
                'details' => $e->getMessage()  // Remove this line in production
            ]);
            exit;
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->connection;
    }

    private function __clone() {}

    public function __wakeup() {
        throw new \Exception("Cannot unserialize singleton");
    }
}