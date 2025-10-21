<?php
/**
 * TransportChargesData.php
 * Data access for transport_charges table
 */
require_once __DIR__ . '/Database.php';

class TransportChargesData {
    private Database $db;

    public function __construct(Database $db) {
        $this->db = $db;
    }

    /**
     * Create a new transport_charges record
     */
    public function create(
        int $transport_id,
        float $removal_charge,
        float $pouch_charge,
        float $transport_fees,
        float $wait_charge,
        float $mileage_fees,
        float $other_charge_1,
        ?string $other_charge_1_description,
        float $other_charge_2,
        ?string $other_charge_2_description,
        float $other_charge_3,
        ?string $other_charge_3_description,
        float $other_charge_4,
        ?string $other_charge_4_description,
        float $total_charge
    ): int {
        $sql = "INSERT INTO transport_charges (
            transport_id, removal_charge, pouch_charge, transport_fees, wait_charge, mileage_fees,
            other_charge_1, other_charge_1_description, other_charge_2, other_charge_2_description, other_charge_3, other_charge_3_description,
            other_charge_4, other_charge_4_description, total_charge
        ) VALUES (
            ?,?,?,?,?,?,?,?,?,?,?,?,?,?,?
        )";
        $params = [
            $transport_id,
            $removal_charge,
            $pouch_charge,
            $transport_fees,
            $wait_charge,
            $mileage_fees,
            $other_charge_1,
            $other_charge_1_description,
            $other_charge_2,
            $other_charge_2_description,
            $other_charge_3,
            $other_charge_3_description,
            $other_charge_4,
            $other_charge_4_description,
            $total_charge
        ];
        return $this->db->insert($sql, $params);
    }

    /**
     * Update an existing transport_charges record
     */
    public function update(
        int $id,
        int $transport_id,
        float $removal_charge,
        float $pouch_charge,
        float $transport_fees,
        float $wait_charge,
        float $mileage_fees,
        float $other_charge_1,
        ?string $other_charge_1_description,
        float $other_charge_2,
        ?string $other_charge_2_description,
        float $other_charge_3,
        ?string $other_charge_3_description,
        float $other_charge_4,
        ?string $other_charge_4_description,
        float $total_charge
    ): bool {
        $sql = "UPDATE transport_charges SET
            transport_id = ?,
            removal_charge = ?,
            pouch_charge = ?,
            transport_fees = ?,
            wait_charge = ?,
            mileage_fees = ?,
            other_charge_1 = ?,
            other_charge_1_description = ?,
            other_charge_2 = ?,
            other_charge_2_description = ?,
            other_charge_3 = ?,
            other_charge_3_description = ?,
            other_charge_4 = ?,
            other_charge_4_description = ?,
            total_charge = ?
        WHERE id = ?";
        $params = [
            $transport_id,
            $removal_charge,
            $pouch_charge,
            $transport_fees,
            $wait_charge,
            $mileage_fees,
            $other_charge_1,
            $other_charge_1_description,
            $other_charge_2,
            $other_charge_2_description,
            $other_charge_3,
            $other_charge_3_description,
            $other_charge_4,
            $other_charge_4_description,
            $total_charge,
            $id
        ];
        return (bool)$this->db->execute($sql, $params);
    }

    /**
     * Delete a transport_charges record by id
     */
    public function delete(int $id): bool {
        $sql = "DELETE FROM transport_charges WHERE id = ?";
        return (bool)$this->db->execute($sql, [$id]);
    }

    /**
     * Delete by transport_id
     */
    public function deleteByTransportId(int $transport_id): bool {
        $sql = "DELETE FROM transport_charges WHERE transport_id = ?";
        return (bool)$this->db->execute($sql, [$transport_id]);
    }

    /**
     * Find by transport_id
     */
    public function findByTransportId(int $transport_id): ?array {
        $sql = "SELECT * FROM transport_charges WHERE transport_id = ?";
        $result = $this->db->query($sql, [$transport_id]);
        return $result[0] ?? null;
    }

    /**
     * Find by id
     */
    public function findById(int $id): ?array {
        $sql = "SELECT * FROM transport_charges WHERE id = ?";
        $result = $this->db->query($sql, [$id]);
        return $result[0] ?? null;
    }
}
