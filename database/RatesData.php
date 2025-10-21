<?php
/**
 * RatesData.php
 * Data access for a simple `rates` table storing transport fee defaults.
 * Table columns: id, basic_fee, included_miles, extra_mile_rate, assistant_fee, effective_date, notes[, customer_number]
 */
require_once __DIR__ . '/Database.php';

class RatesData {
    private Database $db;

    public function __construct(Database $db) {
        $this->db = $db;
    }

    /**
     * Return a rates row. If $customerNumber is provided, try to return the most
     * recent row for that customer; otherwise return the canonical singleton row.
     * @param int|null $customerNumber
     * @return array|null
     */
    public function find(?int $customerNumber = null): ?array {
        if ($customerNumber !== null) {
            $sql = "SELECT * FROM rates WHERE customer_number = ? ORDER BY id DESC LIMIT 1";
            $result = $this->db->query($sql, [$customerNumber]);
            return $result[0] ?? null;
        }

        $sql = "SELECT * FROM rates ORDER BY id LIMIT 1";
        $result = $this->db->query($sql, []);
        return $result[0] ?? null;
    }

    /**
     * Create a new rates row
     * @return int Inserted ID
     */
    public function create(float $basicFee, int $includedMiles, float $extraMileRate, float $assistantFee, string $effectiveDate, ?string $notes, ?int $customerNumber = null): int {
        // Include customer_number column so client code can persist per-customer rates.
        $sql = "INSERT INTO rates (basic_fee, included_miles, extra_mile_rate, assistant_fee, effective_date, notes, customer_number) VALUES (?,?,?,?,?,?,?)";
        $params = [ $basicFee, $includedMiles, $extraMileRate, $assistantFee, $effectiveDate, $notes, $customerNumber ];
        return $this->db->insert($sql, $params);
    }

    /**
     * Update an existing rates row by id
     */
    public function update(int $id, float $basicFee, int $includedMiles, float $extraMileRate, float $assistantFee, string $effectiveDate, ?string $notes, ?int $customerNumber = null): bool {
        $sql = "UPDATE rates SET basic_fee = ?, included_miles = ?, extra_mile_rate = ?, assistant_fee = ?, effective_date = ?, notes = ?, customer_number = ? WHERE id = ?";
        $params = [ $basicFee, $includedMiles, $extraMileRate, $assistantFee, $effectiveDate, $notes, $customerNumber, $id ];
        return (bool)$this->db->execute($sql, $params);
    }

    /**
     * Delete a rates row by id
     */
    public function delete(int $id): bool {
        $sql = "DELETE FROM rates WHERE id = ?";
        return (bool)$this->db->execute($sql, [$id]);
    }

    /**
     * Save rates: create if none exists, otherwise update the existing row.
     * If $data contains a customer_number, prefer to find an existing row for
     * that customer and update/create accordingly.
     * Returns the id of the row created/updated (int) or false on failure
     */
    public function save(array $data) {
        $cust = isset($data['customer_number']) && $data['customer_number'] !== null ? (int)$data['customer_number'] : null;

        // If customer_number provided, try to find a matching row first
        if ($cust !== null) {
            $existingCust = $this->find($cust);
            if ($existingCust && isset($existingCust['id'])) {
                $ok = $this->update(
                    (int)$existingCust['id'],
                    (float)$data['basic_fee'],
                    (int)$data['included_miles'],
                    (float)$data['extra_mile_rate'],
                    (float)$data['assistant_fee'],
                    $data['effective_date'],
                    $data['notes'] ?? null,
                    $cust
                );
                return $ok ? (int)$existingCust['id'] : false;
            } else {
                return $this->create(
                    (float)$data['basic_fee'],
                    (int)$data['included_miles'],
                    (float)$data['extra_mile_rate'],
                    (float)$data['assistant_fee'],
                    $data['effective_date'],
                    $data['notes'] ?? null,
                    $cust
                );
            }
        }

        // No customer specified: fall back to singleton behavior
        $existing = $this->find();
        if ($existing && isset($existing['id'])) {
            $ok = $this->update(
                (int)$existing['id'],
                (float)$data['basic_fee'],
                (int)$data['included_miles'],
                (float)$data['extra_mile_rate'],
                (float)$data['assistant_fee'],
                $data['effective_date'],
                $data['notes'] ?? null,
                null
            );
            return $ok ? (int)$existing['id'] : false;
        } else {
            return $this->create(
                (float)$data['basic_fee'],
                (int)$data['included_miles'],
                (float)$data['extra_mile_rate'],
                (float)$data['assistant_fee'],
                $data['effective_date'],
                $data['notes'] ?? null,
                null
            );
        }
    }
}
