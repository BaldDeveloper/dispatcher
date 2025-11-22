<?php
require_once __DIR__ . '/Database.php';

class PouchData {
    private Database $db;

    public function __construct(Database $db) {
        $this->db = $db;
    }

    public function getAll(): array {
        return $this->db->query("SELECT * FROM pouches ORDER BY id DESC");
    }

    public function findById(int $pouch_id): ?array {
        $result = $this->db->query("SELECT * FROM pouches WHERE id = ?", [$pouch_id]);
        return $result[0] ?? null;
    }

    public function findByType(string $pouchType): ?array {
        $result = $this->db->query("SELECT * FROM pouches WHERE pouch_type = ?", [$pouchType]);
        return $result[0] ?? null;
    }

    public function create(string $pouchType): int {
        return $this->db->insert(
            "INSERT INTO pouches (pouch_type) VALUES (?)",
            [$pouchType]
        );
    }

    public function update(int $pouch_id, string $pouchType): int {
        return $this->db->execute(
            "UPDATE pouches SET pouch_type = ? WHERE id = ?",
            [$pouchType, $pouch_id]
        );
    }

    public function delete(int $pouch_id): int {
        return $this->db->execute(
            "DELETE FROM pouches WHERE id = ?",
            [$pouch_id]
        );
    }

    public function getPaginated(int $limit, int $offset): array {
        $limit = max(1, (int)$limit);
        $offset = max(0, (int)$offset);
        $sql = "SELECT * FROM pouches ORDER BY id DESC LIMIT $limit OFFSET $offset";
        return $this->db->query($sql);
    }

    public function getCount(): int {
        $result = $this->db->query("SELECT COUNT(*) as cnt FROM pouches");
        return isset($result[0]['cnt']) ? (int)$result[0]['cnt'] : 0;
    }

    // Returns the total number of pouches matching a search term
    public function getCountBySearch(string $search): int {
        $like = '%' . $search . '%';
        $result = $this->db->query("SELECT COUNT(*) as cnt FROM pouches WHERE pouch_type LIKE ?", [$like]);
        return isset($result[0]['cnt']) ? (int)$result[0]['cnt'] : 0;
    }

    // Returns a paginated list of pouches matching a search term
    public function searchPaginated(string $search, int $limit, int $offset): array {
        $limit = max(1, (int)$limit);
        $offset = max(0, (int)$offset);
        $like = '%' . $search . '%';
        $sql = "SELECT * FROM pouches WHERE pouch_type LIKE ? ORDER BY id DESC LIMIT $limit OFFSET $offset";
        return $this->db->query($sql, [$like]);
    }
}
