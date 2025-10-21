<?php
require_once __DIR__ . '/Database.php';

/**
 * VehicleData - Data access for vehicles
 */
class VehicleData {
    private Database $db;

    public function __construct(Database $db) {
        $this->db = $db;
    }

    /**
     * Add a new vehicle record
     * @param array $data Associative array of vehicle fields
     * @return int New vehicle ID
     * @throws Exception on failure
     */
    public function addVehicle(array $data): int {
        $sql = "INSERT INTO vehicle (
            vehicle_type, color, license_plate, year_of_manufacture, make, model, vin, refrigeration_unit, fuel_type, odometer_reading, trailer_compatible, emission_cert_status, inspection_notes, assigned_mechanic, last_service_date, next_service_date, service_interval, maintenance_status, current_status, tire_condition, battery_health, registration_expiry, insurance_provider, insurance_policy_number, insurance_expiry, notes
        ) VALUES (
            :vehicle_type, :color, :license_plate, :year_of_manufacture, :make, :model, :vin, :refrigeration_unit, :fuel_type, :odometer_reading, :trailer_compatible, :emission_cert_status, :inspection_notes, :assigned_mechanic, :last_service_date, :next_service_date, :service_interval, :maintenance_status, :current_status, :tire_condition, :battery_health, :registration_expiry, :insurance_provider, :insurance_policy_number, :insurance_expiry, :notes
        )";
        return $this->db->insert($sql, [
            ':vehicle_type' => $data['vehicle_type'] ?? null,
            ':color' => $data['color'] ?? null,
            ':license_plate' => $data['license_plate'] ?? null,
            ':year_of_manufacture' => $data['year'] ?? null,
            ':make' => $data['make'] ?? null,
            ':model' => $data['model'] ?? null,
            ':vin' => $data['vin'] ?? null,
            ':refrigeration_unit' => $data['refrigeration_unit'] ?? null,
            ':fuel_type' => $data['fuel_type'] ?? null,
            ':odometer_reading' => $data['odometer_reading'] ?? null,
            ':trailer_compatible' => $data['trailer_compatible'] ?? null,
            ':emission_cert_status' => $data['emission_cert_status'] ?? null,
            ':inspection_notes' => $data['inspection_notes'] ?? null,
            ':assigned_mechanic' => $data['assigned_mechanic'] ?? null,
            ':last_service_date' => $data['last_service_date'] ?? null,
            ':next_service_date' => $data['next_service_date'] ?? null,
            ':service_interval' => $data['service_interval'] ?? null,
            ':maintenance_status' => $data['maintenance_status'] ?? null,
            ':current_status' => $data['current_status'] ?? null,
            ':tire_condition' => $data['tire_condition'] ?? null,
            ':battery_health' => $data['battery_health'] ?? null,
            ':registration_expiry' => $data['registration_expiry'] ?? null,
            ':insurance_provider' => $data['insurance_provider'] ?? null,
            ':insurance_policy_number' => $data['insurance_policy_number'] ?? null,
            ':insurance_expiry' => $data['insurance_expiry'] ?? null,
            ':notes' => $data['notes'] ?? null,
        ]);
    }

    /**
     * Get all vehicles (paginated)
     * @param int $offset
     * @param int $limit
     * @return array
     */
    public function getAll(int $offset = 0, int $limit = 10): array {
        $sql = "SELECT vehicle_id, year_of_manufacture AS year, make, model, color FROM vehicle ORDER BY vehicle_id DESC LIMIT :limit OFFSET :offset";
        $stmt = $this->db->getPdo()->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Get total vehicle count
     * @return int
     */
    public function getCount(): int {
        $sql = "SELECT COUNT(*) FROM vehicle";
        return (int)$this->db->getPdo()->query($sql)->fetchColumn();
    }

    /**
     * Get a single vehicle by ID
     * @param int $id
     * @return array|null
     */
    public function getById(int $id): ?array {
        $sql = "SELECT * FROM vehicle WHERE vehicle_id = :id LIMIT 1";
        $stmt = $this->db->getPdo()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $vehicle = $stmt->fetch();
        return $vehicle !== false ? $vehicle : null;
    }

    /**
     * Update a vehicle record by ID
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function updateVehicle(int $id, array $data): bool {
        $sql = "UPDATE vehicle SET
            vehicle_type = :vehicle_type,
            color = :color,
            license_plate = :license_plate,
            year_of_manufacture = :year_of_manufacture,
            make = :make,
            model = :model,
            vin = :vin,
            refrigeration_unit = :refrigeration_unit,
            fuel_type = :fuel_type,
            odometer_reading = :odometer_reading,
            trailer_compatible = :trailer_compatible,
            emission_cert_status = :emission_cert_status,
            inspection_notes = :inspection_notes,
            assigned_mechanic = :assigned_mechanic,
            last_service_date = :last_service_date,
            next_service_date = :next_service_date,
            service_interval = :service_interval,
            maintenance_status = :maintenance_status,
            current_status = :current_status,
            tire_condition = :tire_condition,
            battery_health = :battery_health,
            registration_expiry = :registration_expiry,
            insurance_provider = :insurance_provider,
            insurance_policy_number = :insurance_policy_number,
            insurance_expiry = :insurance_expiry,
            notes = :notes
        WHERE vehicle_id = :id";
        $params = [
            ':vehicle_type' => $data['vehicle_type'] ?? null,
            ':color' => $data['color'] ?? null,
            ':license_plate' => $data['license_plate'] ?? null,
            ':year_of_manufacture' => $data['year'] ?? null,
            ':make' => $data['make'] ?? null,
            ':model' => $data['model'] ?? null,
            ':vin' => $data['vin'] ?? null,
            ':refrigeration_unit' => $data['refrigeration_unit'] ?? null,
            ':fuel_type' => $data['fuel_type'] ?? null,
            ':odometer_reading' => $data['odometer_reading'] ?? null,
            ':trailer_compatible' => $data['trailer_compatible'] ?? null,
            ':emission_cert_status' => $data['emission_cert_status'] ?? null,
            ':inspection_notes' => $data['inspection_notes'] ?? null,
            ':assigned_mechanic' => $data['assigned_mechanic'] ?? null,
            ':last_service_date' => $data['last_service_date'] ?? null,
            ':next_service_date' => $data['next_service_date'] ?? null,
            ':service_interval' => $data['service_interval'] ?? null,
            ':maintenance_status' => $data['maintenance_status'] ?? null,
            ':current_status' => $data['current_status'] ?? null,
            ':tire_condition' => $data['tire_condition'] ?? null,
            ':battery_health' => $data['battery_health'] ?? null,
            ':registration_expiry' => $data['registration_expiry'] ?? null,
            ':insurance_provider' => $data['insurance_provider'] ?? null,
            ':insurance_policy_number' => $data['insurance_policy_number'] ?? null,
            ':insurance_expiry' => $data['insurance_expiry'] ?? null,
            ':notes' => $data['notes'] ?? null,
            ':id' => $id,
        ];
        $stmt = $this->db->getPdo()->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Delete a vehicle by ID
     * @param int $id
     * @return void
     * @throws Exception on failure
     */
    public function deleteVehicle(int $id): void {
        $sql = "DELETE FROM vehicle WHERE vehicle_id = :id";
        $this->db->execute($sql, [':id' => $id]);
    }
}
