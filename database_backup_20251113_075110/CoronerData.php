<?php
require_once __DIR__ . '/Database.php';

class CoronerData {
    private Database $db;

    public function __construct(Database $db) {
        $this->db = $db;
    }

    public function getAll(): array {
        return $this->db->query("SELECT * FROM coroners ORDER BY id DESC");
    }

    public function findById(int $id): ?array {
        // Keep method name for backward compatibility but query the new table/column
        $result = $this->db->query("SELECT * FROM coroners WHERE id = ?", [$id]);
        return $result[0] ?? null;
    }

    public function create(
        string $coronerName,
        ?string $phoneNumber,
        ?string $emailAddress,
        ?string $address1,
        ?string $address2,
        ?string $city,
        ?string $state,
        ?string $zip,
        string $county
    ): int {
        return $this->db->insert(
            "INSERT INTO coroners (coroner_name, phone_number, email_address, address_1, address_2, city, state, zip, county) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [$coronerName, $phoneNumber, $emailAddress, $address1, $address2, $city, $state, $zip, $county]
        );
    }

    public function update(
        int $id,
        string $coronerName,
        ?string $phoneNumber,
        ?string $emailAddress,
        ?string $address1,
        ?string $address2,
        ?string $city,
        ?string $state,
        ?string $zip,
        string $county
    ): int {
        return $this->db->execute(
            "UPDATE coroners SET coroner_name = ?, phone_number = ?, email_address = ?, address_1 = ?, address_2 = ?, city = ?, state = ?, zip = ?, county = ? WHERE id = ?",
            [$coronerName, $phoneNumber, $emailAddress, $address1, $address2, $city, $state, $zip, $county, $id]
        );
    }

    public function delete(int $id): int {
        return $this->db->execute("DELETE FROM coroners WHERE id = ?", [$id]);
    }

    public function getPaginated(int $limit, int $offset): array {
        $limit = max(1, (int)$limit);
        $offset = max(0, (int)$offset);
        $sql = "SELECT * FROM coroners ORDER BY id DESC LIMIT $limit OFFSET $offset";
        return $this->db->query($sql);
    }

    public function getCount(): int {
        $result = $this->db->query("SELECT COUNT(*) as cnt FROM coroners");
        return isset($result[0]['cnt']) ? (int)$result[0]['cnt'] : 0;
    }

    public function existsByName(string $coronerName): bool {
        $result = $this->db->query("SELECT id FROM coroners WHERE coroner_name = ? LIMIT 1", [$coronerName]);
        return !empty($result);
    }

    // Returns the total number of coroners matching a search term
    public function getCountBySearch(string $search): int {
        $like = '%' . $search . '%';
        $result = $this->db->query("SELECT COUNT(*) as cnt FROM coroners WHERE coroner_name LIKE ?", [$like]);
        return isset($result[0]['cnt']) ? (int)$result[0]['cnt'] : 0;
    }

    // Returns a paginated list of coroners matching a search term
    public function searchPaginated(string $search, int $limit, int $offset): array {
        $limit = max(1, (int)$limit);
        $offset = max(0, (int)$offset);
        $like = '%' . $search . '%';
        $sql = "SELECT * FROM coroners WHERE coroner_name LIKE ? ORDER BY id DESC LIMIT $limit OFFSET $offset";
        return $this->db->query($sql, [$like]);
    }
}
