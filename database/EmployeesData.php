<?php
require_once __DIR__ . '/Database.php';

class EmployeesData {
    private Database $db;

    public function __construct(Database $db) {
        $this->db = $db;
    }

    // Get all employees with optional user full name
    public function getAll(): array {
        $sql = "SELECT e.*, u.full_name AS user_full_name FROM employees e LEFT JOIN users u ON e.user_id = u.id ORDER BY e.id DESC";
        return $this->db->query($sql);
    }

    public function findById(int $id): ?array {
        $result = $this->db->query("SELECT * FROM employees WHERE id = ?", [$id]);
        return $result[0] ?? null;
    }

    public function create(
        int $user_id,
        string $job_title,
        ?float $salary = null,
        string $employment_type = 'permanent',
        ?string $start_date = null,
        ?string $end_date = null,
        string $status = 'active'
    ): int {
        $sql = "INSERT INTO employees (user_id, job_title, salary, employment_type, start_date, end_date, status) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $params = [$user_id, $job_title, $salary, $employment_type, $start_date, $end_date, $status];
        return $this->db->insert($sql, $params);
    }

    public function update(
        int $id,
        int $user_id,
        string $job_title,
        ?float $salary = null,
        string $employment_type = 'permanent',
        ?string $start_date = null,
        ?string $end_date = null,
        string $status = 'active'
    ): int {
        $sql = "UPDATE employees SET user_id = ?, job_title = ?, salary = ?, employment_type = ?, start_date = ?, end_date = ?, status = ? WHERE id = ?";
        $params = [$user_id, $job_title, $salary, $employment_type, $start_date, $end_date, $status, $id];
        return $this->db->execute($sql, $params);
    }

    public function delete(int $id): int {
        return $this->db->execute("DELETE FROM employees WHERE id = ?", [$id]);
    }

    public function getCount(): int {
        $result = $this->db->query("SELECT COUNT(*) AS cnt FROM employees");
        return isset($result[0]['cnt']) ? (int)$result[0]['cnt'] : 0;
    }

    public function getPaginated(int $limit, int $offset): array {
        $limit = max(1, (int)$limit);
        $offset = max(0, (int)$offset);
        $sql = "SELECT e.*, u.full_name AS user_full_name FROM employees e LEFT JOIN users u ON e.user_id = u.id ORDER BY e.id DESC LIMIT $limit OFFSET $offset";
        return $this->db->query($sql);
    }

    public function existsByUserId(int $user_id): bool {
        $result = $this->db->query("SELECT id FROM employees WHERE user_id = ? LIMIT 1", [$user_id]);
        return !empty($result);
    }

    public function getCountBySearch(string $search): int {
        $like = '%' . $search . '%';
        $sql = "SELECT COUNT(*) AS cnt FROM employees e LEFT JOIN users u ON e.user_id = u.id WHERE e.job_title LIKE ? OR u.full_name LIKE ?";
        $result = $this->db->query($sql, [$like, $like]);
        return isset($result[0]['cnt']) ? (int)$result[0]['cnt'] : 0;
    }

    public function searchPaginated(string $search, int $limit, int $offset): array {
        $limit = max(1, (int)$limit);
        $offset = max(0, (int)$offset);
        $like = '%' . $search . '%';
        $sql = "SELECT e.*, u.full_name AS user_full_name FROM employees e LEFT JOIN users u ON e.user_id = u.id WHERE e.job_title LIKE ? OR u.full_name LIKE ? ORDER BY e.id DESC LIMIT $limit OFFSET $offset";
        return $this->db->query($sql, [$like, $like]);
    }
}

