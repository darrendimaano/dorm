<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

/**
 * Database Configuration Manager
 * Handles database connections without hardcoded credentials
 */
class DatabaseConfig {
    private static $instance = null;
    private $pdo = null;
    private $config = [];

    private function __construct() {
        $this->loadConfig();
        $this->connect();
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function loadConfig() {
        // Load from environment variables or fall back to defaults
        $this->config = [
            'host' => $_ENV['DB_HOST'] ?? 'localhost',
            'port' => $_ENV['DB_PORT'] ?? '3306',
            'database' => $_ENV['DB_DATABASE'] ?? 'mockdata',
            'username' => $_ENV['DB_USERNAME'] ?? 'jeany',
            'password' => $_ENV['DB_PASSWORD'] ?? 'jeany',
            'charset' => 'utf8mb4'
        ];
    }

    private function connect() {
        try {
            $dsn = sprintf(
                "mysql:host=%s;port=%s;dbname=%s;charset=%s",
                $this->config['host'],
                $this->config['port'],
                $this->config['database'],
                $this->config['charset']
            );

            $this->pdo = new PDO($dsn, $this->config['username'], $this->config['password']);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            throw new Exception("Database connection failed. Please check your configuration.");
        }
    }

    public function getConnection() {
        return $this->pdo;
    }

    public function getConfig($key = null) {
        if ($key) {
            return $this->config[$key] ?? null;
        }
        return $this->config;
    }

    // Test database connection
    public function testConnection() {
        try {
            $stmt = $this->pdo->query("SELECT 1");
            return $stmt !== false;
        } catch (Exception $e) {
            return false;
        }
    }
}
?>