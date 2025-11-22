<?php
require_once __DIR__ . '/Database.php';

class DecedentData {
    private Database $db;

    public function __construct(Database $db) {
        $this->db = $db;
    }

    public function getAll(): array {
        return $this->db->query("SELECT * FROM decedents ORDER BY decedent_id DESC");
    }

    public function findById(int $decedent_id): ?array {
        $result = $this->db->query("SELECT * FROM decedents WHERE decedent_id = ?", [$decedent_id]);
        return $result[0] ?? null;
    }

    public function update(
        int $decedent_id,
        string $first_name,
        string $last_name,
        string $ethnicity,
        string $gender
    ): int {
        return $this->db->execute(
            "UPDATE decedents SET first_name = ?, last_name = ?, ethnicity = ?, gender = ? WHERE decedent_id = ?",
            [$first_name, $last_name, $ethnicity, $gender, $decedent_id]
        );
    }

    /**
     * Log database errors and debug info to decedent-errors.log in the same directory
     */
    private function logError(string $message): void {
        $logFile = __DIR__ . '/decedent-errors.log';
        error_log(date('[Y-m-d H:i:s] ') . $message . "\n", 3, $logFile);
    }

    public function updateByTransportId(
        int $transport_id,
        string $first_name,
        string $last_name,
        string $ethnicity,
        string $gender
    ): int {
        $sql = "UPDATE decedents SET first_name = ?, last_name = ?, ethnicity = ?, gender = ? WHERE transport_id = ?";
        $params = [$first_name, $last_name, $ethnicity, $gender, $transport_id];
        // Logging for debugging
        $this->logError("[DecedentData::updateByTransportId] SQL: " . $sql);
        $this->logError("[DecedentData::updateByTransportId] Params: " . print_r($params, true));
        return $this->db->execute($sql, $params);
    }

    public function delete(int $decedent_id): int {
        return $this->db->execute(
            "DELETE FROM decedents WHERE decedent_id = ?",
            [$decedent_id]
        );
    }

    public function deleteByTransportId(int $transport_id): int {
        return $this->db->execute(
            "DELETE FROM decedents WHERE transport_id = ?",
            [$transport_id]
        );
    }

    public function insertByTransportId(
        int $transport_id,
        string $first_name,
        string $last_name,
        string $ethnicity,
        string $gender
    ): int {
        return $this->db->insert(
            "INSERT INTO decedents (transport_id, first_name, last_name, ethnicity, gender) VALUES (?, ?, ?, ?, ?)",
            [$transport_id, $first_name, $last_name, $ethnicity, $gender]
        );
    }
}
