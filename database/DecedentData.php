<?php
require_once __DIR__ . '/Database.php';

class DecedentData {
    private Database $db;
    // column name that references transport; detected at runtime
    // after renaming transport table -> dispatches, prefer `id` as foreign key column name
    private string $transportColumn = 'id';

    public function __construct(Database $db) {
        $this->db = $db;
        $this->detectTransportColumn();
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

    /**
     * Detect which column in the decedents table links to transport records.
     * This prevents fatal SQL errors when the schema uses a different column name.
     */
    private function detectTransportColumn(): void {
        try {
            $cols = $this->db->query("SHOW COLUMNS FROM decedents");
            $found = null;
            // prefer `id` as the linking column; keep legacy variants as fallbacks
            // Include common variants including `transport_id` (underscore) which is used in many schemas
            $candidates = ['id', 'transport', 'transport_id', 'transportid', 'transport_number', 'transportId'];
            foreach ($cols as $col) {
                // MySQL SHOW COLUMNS returns a 'Field' key
                $field = $col['Field'] ?? $col['field'] ?? null;
                if (!$field) continue;
                $lf = strtolower($field);
                foreach ($candidates as $cand) {
                    if ($lf === strtolower($cand)) {
                        $found = $field;
                        break 2;
                    }
                }
            }
            if ($found) {
                $this->transportColumn = $found;
            } else {
                // keep default transport_id if not found but log a warning
                $this->logError("[DecedentData::detectTransportColumn] No transport column found in decedents table. Defaulting to 'id'. Columns: " . json_encode($cols));
            }
        } catch (Exception $e) {
            // Could not inspect table structure; keep default and log error
            $this->logError('[DecedentData::detectTransportColumn] Exception: ' . $e->getMessage());
        }
    }

    /**
     * Find a decedent row linked to a transport using the detected transport column.
     */
    public function findByTransportId(int $id): ?array {
        // Ensure column name is safe (contains only letters, numbers, underscore)
        if (!preg_match('/^[A-Za-z0-9_]+$/', $this->transportColumn)) {
            $this->logError('[DecedentData::findByTransportId] Invalid transport column name: ' . $this->transportColumn);
            return null;
        }
        $col = $this->transportColumn;
        $sql = "SELECT * FROM decedents WHERE `$col` = ? LIMIT 1";
        try {
            $result = $this->db->query($sql, [$id]);
            return $result[0] ?? null;
        } catch (Exception $e) {
            $this->logError('[DecedentData::findByTransportId] SQL Error: ' . $e->getMessage() . ' SQL: ' . $sql . ' Param: ' . $id);
            return null;
        }
    }

    public function updateByTransportId(
        int $id,
        string $first_name,
        string $last_name,
        string $ethnicity,
        string $gender
    ): int {
        if (!preg_match('/^[A-Za-z0-9_]+$/', $this->transportColumn)) {
            $this->logError('[DecedentData::updateByTransportId] Invalid transport column name: ' . $this->transportColumn);
            return 0;
        }
        $col = $this->transportColumn;
        $sql = "UPDATE decedents SET first_name = ?, last_name = ?, ethnicity = ?, gender = ? WHERE `$col` = ?";
        $params = [$first_name, $last_name, $ethnicity, $gender, $id];
        // Logging for debugging
        $this->logError("[DecedentData::updateByTransportId] SQL: " . $sql);
        $this->logError("[DecedentData::updateByTransportId] Params: " . print_r($params, true));
        try {
            return $this->db->execute($sql, $params);
        } catch (Exception $e) {
            $this->logError('[DecedentData::updateByTransportId] Exception: ' . $e->getMessage());
            return 0;
        }
    }

    public function delete(int $decedent_id): int {
        return $this->db->execute(
            "DELETE FROM decedents WHERE decedent_id = ?",
            [$decedent_id]
        );
    }

    public function deleteByTransportId(int $id): int {
        if (!preg_match('/^[A-Za-z0-9_]+$/', $this->transportColumn)) {
            $this->logError('[DecedentData::deleteByTransportId] Invalid transport column name: ' . $this->transportColumn);
            return 0;
        }
        $col = $this->transportColumn;
        $sql = "DELETE FROM decedents WHERE `$col` = ?";
        try {
            return $this->db->execute($sql, [$id]);
        } catch (Exception $e) {
            $this->logError('[DecedentData::deleteByTransportId] Exception: ' . $e->getMessage());
            return 0;
        }
    }

    public function insertByTransportId(
        int $id,
        string $first_name,
        string $last_name,
        string $ethnicity,
        string $gender
    ): int {
        if (!preg_match('/^[A-Za-z0-9_]+$/', $this->transportColumn)) {
            $this->logError('[DecedentData::insertByTransportId] Invalid transport column name: ' . $this->transportColumn);
            return 0;
        }
        $col = $this->transportColumn;
        $sql = "INSERT INTO decedents (`$col`, first_name, last_name, ethnicity, gender) VALUES (?, ?, ?, ?, ?)";
        $params = [$id, $first_name, $last_name, $ethnicity, $gender];
        // Logging for debugging
        $this->logError("[DecedentData::insertByTransportId] SQL: " . $sql);
        $this->logError("[DecedentData::insertByTransportId] Params: " . print_r($params, true));
        try {
            return $this->db->insert($sql, $params);
        } catch (Exception $e) {
            $this->logError('[DecedentData::insertByTransportId] Exception: ' . $e->getMessage());
            return 0;
        }
    }
}
