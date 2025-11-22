<?php
require_once __DIR__ . '/Database.php';

class UserData {
    private Database $db;

    public function __construct(Database $db) {
        $this->db = $db;
    }

    // Get all users
    public function getAll(): array {
        return $this->db->query("SELECT * FROM users ORDER BY id DESC");
    }

    // Get user by ID
    public function findById(int $id): ?array {
        $result = $this->db->query("SELECT * FROM users WHERE id = ?", [$id]);
        return $result[0] ?? null;
    }

    // Create a new user
    public function create(string $username, string $password_hash, string $full_name, ?string $address = null, ?string $city = null, ?string $state = null, ?string $zip_code = null, ?string $phone_number = null, string $role = 'other', int $is_active = 1): int {
        return $this->db->insert(
            "INSERT INTO users (username, password_hash, full_name, address, city, state, zip_code, phone_number, role, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [$username, $password_hash, $full_name, $address, $city, $state, $zip_code, $phone_number, $role, $is_active]
        );
    }

    // Update an existing user
    public function update(int $id, string $username, ?string $password_hash, string $full_name, ?string $address = null, ?string $city = null, ?string $state = null, ?string $zip_code = null, ?string $phone_number = null, string $role = 'other', int $is_active = 1): int {
        $setPassword = $password_hash ? ", password_hash = ?" : "";
        $params = [$username, $full_name, $address, $city, $state, $zip_code, $phone_number, $role, $is_active];
        $sql = "UPDATE users SET username = ?, full_name = ?, address = ?, city = ?, state = ?, zip_code = ?, phone_number = ?, role = ?, is_active = ?";
        if ($password_hash) {
            $sql .= ", password_hash = ?";
            $params[] = $password_hash;
        }
        $sql .= " WHERE id = ?";
        $params[] = $id;
        return $this->db->execute($sql, $params);
    }

    // Delete a user
    public function delete(int $id): int {
        return $this->db->execute("DELETE FROM users WHERE id = ?", [$id]);
    }

    // Get all drivers (users with role = 'driver')
    public function getDrivers(): array {
        return $this->db->query("SELECT * FROM users WHERE role = 'driver' ORDER BY username ASC");
    }

    // Returns the total number of users in the table
    public function getCount(): int {
        $result = $this->db->query("SELECT COUNT(*) AS cnt FROM users");
        return isset($result[0]['cnt']) ? (int)$result[0]['cnt'] : 0;
    }

    // Returns a paginated list of users
    public function getPaginated(int $limit, int $offset): array {
        $limit = max(1, (int)$limit);
        $offset = max(0, (int)$offset);
        return $this->db->query(
            "SELECT * FROM users ORDER BY id DESC LIMIT $limit OFFSET $offset"
        );
    }

    // Checks if a user with the given username exists
    public function existsByName(string $username): bool {
        $result = $this->db->query("SELECT id FROM users WHERE username = ? LIMIT 1", [$username]);
        return !empty($result);
    }

    // Get count of users matching search
    public function getCountBySearch(string $search): int {
        $like = '%' . $search . '%';
        $sql = "SELECT COUNT(*) as cnt FROM users WHERE 
            username LIKE ? OR 
            full_name LIKE ? OR 
            city LIKE ? OR 
            state LIKE ? OR 
            role LIKE ? OR 
            (is_active = 1 AND ? IN ('yes','active','1')) OR
            (is_active = 0 AND ? IN ('no','inactive','0'))
        ";
        $params = [$like, $like, $like, $like, $like, strtolower($search), strtolower($search)];
        $result = $this->db->query($sql, $params);
        return $result[0]['cnt'] ?? 0;
    }

    // Get paginated users matching search
    public function searchPaginated(string $search, int $pageSize, int $offset): array {
        $like = '%' . $search . '%';
        $pageSize = max(1, (int)$pageSize);
        $offset = max(0, (int)$offset);
        $sql = "SELECT * FROM users WHERE 
            username LIKE ? OR 
            full_name LIKE ? OR 
            city LIKE ? OR 
            state LIKE ? OR 
            role LIKE ? OR 
            (is_active = 1 AND ? IN ('yes','active','1')) OR
            (is_active = 0 AND ? IN ('no','inactive','0'))
            ORDER BY id DESC LIMIT $pageSize OFFSET $offset";
        $params = [$like, $like, $like, $like, $like, strtolower($search), strtolower($search)];
        return $this->db->query($sql, $params);
    }
}
