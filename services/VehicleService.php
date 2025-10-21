<?php
/**
 * VehicleService.php
 * Business logic layer for vehicles.
 */
require_once __DIR__ . '/../database/VehicleData.php';

class VehicleService {
    private $vehicleData;
    public function __construct($db) {
        $this->vehicleData = new VehicleData($db);
    }

    public function getCount() {
        return $this->vehicleData->getCount();
    }

    public function getPage($offset, $limit) {
        return $this->vehicleData->getPage($offset, $limit);
    }

    public function findById($id) {
        return $this->vehicleData->findById($id);
    }

    public function create($data) {
        return $this->vehicleData->create($data);
    }

    public function update($id, $data) {
        return $this->vehicleData->update($id, $data);
    }

    public function delete($id) {
        return $this->vehicleData->delete($id);
    }
}
<?php
/**
 * VehicleData.php
 * Data access layer for the vehicle table.
 */
class VehicleData {
    private $db;
    public function __construct($db) {
        $this->db = $db;
    }

    public function getCount() {
        $stmt = $this->db->getConnection()->query('SELECT COUNT(*) FROM vehicle');
        return (int)$stmt->fetchColumn();
    }

    public function getPage($offset, $limit) {
        $stmt = $this->db->getConnection()->prepare('SELECT * FROM vehicle ORDER BY vehicle_id DESC LIMIT :limit OFFSET :offset');
        $stmt->bindValue(':limit', (int)$limit, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function findById($id) {
        $stmt = $this->db->getConnection()->prepare('SELECT * FROM vehicle WHERE vehicle_id = :id');
        $stmt->bindValue(':id', $id, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }

    public function create($data) {
        $sql = 'INSERT INTO vehicle (
            license_plate, vehicle_type, make, model, year_of_manufacture, vin, color, fuel_type, odometer_reading, odometer_unit, service_interval, last_service_date, next_service_date, maintenance_status, assigned_mechanic, tire_condition, battery_health, current_status, registration_expiry, insurance_provider, insurance_policy_number, insurance_expiry, emission_cert_status, inspection_notes, trailer_compatible, refrigeration_unit, notes
        ) VALUES (
            :license_plate, :vehicle_type, :make, :model, :year_of_manufacture, :vin, :color, :fuel_type, :odometer_reading, :odometer_unit, :service_interval, :last_service_date, :next_service_date, :maintenance_status, :assigned_mechanic, :tire_condition, :battery_health, :current_status, :registration_expiry, :insurance_provider, :insurance_policy_number, :insurance_expiry, :emission_cert_status, :inspection_notes, :trailer_compatible, :refrigeration_unit, :notes
        )';
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute($data);
        return $this->db->getConnection()->lastInsertId();
    }

    public function update($id, $data) {
        $sql = 'UPDATE vehicle SET
            license_plate = :license_plate,
            vehicle_type = :vehicle_type,
            make = :make,
            model = :model,
            year_of_manufacture = :year_of_manufacture,
            vin = :vin,
            color = :color,
            fuel_type = :fuel_type,
            odometer_reading = :odometer_reading,
            odometer_unit = :odometer_unit,
            service_interval = :service_interval,
            last_service_date = :last_service_date,
            next_service_date = :next_service_date,
            maintenance_status = :maintenance_status,
            assigned_mechanic = :assigned_mechanic,
            tire_condition = :tire_condition,
            battery_health = :battery_health,
            current_status = :current_status,
            registration_expiry = :registration_expiry,
            insurance_provider = :insurance_provider,
            insurance_policy_number = :insurance_policy_number,
            insurance_expiry = :insurance_expiry,
            emission_cert_status = :emission_cert_status,
            inspection_notes = :inspection_notes,
            trailer_compatible = :trailer_compatible,
            refrigeration_unit = :refrigeration_unit,
            notes = :notes
        WHERE vehicle_id = :vehicle_id';
        $data['vehicle_id'] = $id;
        $stmt = $this->db->getConnection()->prepare($sql);
        return $stmt->execute($data);
    }

    public function delete($id) {
        $stmt = $this->db->getConnection()->prepare('DELETE FROM vehicle WHERE vehicle_id = :id');
        $stmt->bindValue(':id', $id, \PDO::PARAM_INT);
        return $stmt->execute();
    }
}

