<?php
// Database.php — Reusable PDO-based Data Access Layer for MySQL

class Database {
    private PDO $pdo;

    public function __construct() {
        // Load DB config from includes/config.php
        $config = require(__DIR__ . '/../includes/config.php');
        $db_host = $config['DB_HOST'] ?? '127.0.0.1';
        $db_user = $config['DB_USER'] ?? 'root';
        $db_pass = $config['DB_PASS'] ?? '';
        $db_name = $config['DB_NAME'] ?? 'test';
        $log_file = __DIR__ . '/db_error.log';

        $dsn = "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4";
        try {
            $this->pdo = new PDO($dsn, $db_user, $db_pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_TIMEOUT => 5,
            ]);
        } catch (PDOException $e) {
            $error = "Database connection failed: " . $e->getMessage();
            file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . "] $error\n", FILE_APPEND);
            throw new RuntimeException($error);
        }
    }

    /**
     * Get the PDO instance
     * @return PDO
     */
    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    // Generic query executor
    public function query(string $sql, array $params = []): array {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // Insert with auto-increment ID return
    public function insert(string $sql, array $params): int {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int)$this->pdo->lastInsertId();
    }

    // Update or delete
    public function execute(string $sql, array $params): int {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    // Transaction support
    public function beginTransaction(): void {
        $this->pdo->beginTransaction();
    }

    public function commit(): void {
        $this->pdo->commit();
    }

    public function rollback(): void {
        $this->pdo->rollBack();
    }
}
