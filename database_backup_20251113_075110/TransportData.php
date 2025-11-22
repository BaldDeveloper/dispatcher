<?php
require_once __DIR__ . '/Database.php';

class TransportData {
    private Database $db;

    public function __construct(Database $db) {
        $this->db = $db;
    }

    // Insert a new record into transport
    public function create(
        int $customerId,
        string $firmDate,
        string $accountType,
        string $originLocation,
        string $destinationLocation,
        string $coronerName,
        string $pouchType,
        string $transitPermitNumber,
        string $tagNumber,
        string $callTime,
        string $arrivalTime,
        string $departureTime,
        string $deliveryTime,
        ?int $primaryTransporter = null,
        ?int $assistantTransporter = null,
        ?float $mileage = null,
        ?float $mileage_rate = null,
        ?float $mileage_total_charge = null
    ): int {
        $sql = "INSERT INTO transport (
            customer_id, firm_date, account_type, origin_location, destination_location, coroner_name, pouch_type, transit_permit_number, tag_number, call_time, arrival_time, departure_time, delivery_time, primary_transporter, assistant_transporter, mileage, mileage_rate, mileage_total_charge
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
        )";
        $params = [
            $customerId,
            $firmDate,
            $accountType,
            $originLocation,
            $destinationLocation,
            $coronerName,
            $pouchType,
            $transitPermitNumber,
            $tagNumber,
            $callTime,
            $arrivalTime,
            $departureTime,
            $deliveryTime,
            $primaryTransporter,
            $assistantTransporter,
            $mileage,
            $mileage_rate,
            $mileage_total_charge
        ];
        // Clear the error log before each run
        @file_put_contents(__DIR__ . '/../../database/db_error.log', '');
        error_log('SQL: ' . $sql);
        error_log('PARAM COUNT: ' . count($params));
        foreach ($params as $i => $param) {
            error_log("Param $i type: " . gettype($param) . ", value: " . var_export($param, true));
        }
        error_log('PARAMS: ' . json_encode($params));
        return $this->db->insert($sql, $params);
    }

    public function getAll(): array {
        // Join transport with decedents to get first and last name
        $sql = "SELECT t.*, d.first_name AS decedent_first_name, d.last_name AS decedent_last_name
                FROM transport t
                LEFT JOIN decedents d ON t.transport_id = d.transport_id
                ORDER BY t.transport_id DESC";
        return $this->db->query($sql);
    }

    public function findById(int $id): ?array {
        $result = $this->db->query("SELECT * FROM transport WHERE transport_id = ?", [$id]);
        return $result[0] ?? null;
    }

    // Update an existing record in transport
    public function update(
        int $transportId,
        int $customerId,
        string $firmDate,
        string $accountType,
        string $originLocation,
        string $destinationLocation,
        string $coronerName,
        string $pouchType,
        string $transitPermitNumber,
        string $tagNumber,
        string $callTime,
        string $arrivalTime,
        string $departureTime,
        string $deliveryTime,
        ?int $primaryTransporter = null,
        ?int $assistantTransporter = null,
        ?float $mileage = null,
        ?float $mileage_rate = null,
        ?float $mileage_total_charge = null
    ): int {
        $sql = "UPDATE transport SET
            customer_id = ?,
            firm_date = ?,
            account_type = ?,
            origin_location = ?,
            destination_location = ?,
            coroner_name = ?,
            pouch_type = ?,
            transit_permit_number = ?,
            tag_number = ?,
            call_time = ?,
            arrival_time = ?,
            departure_time = ?,
            delivery_time = ?,
            primary_transporter = ?,
            assistant_transporter = ?,
            mileage = ?,
            mileage_rate = ?,
            mileage_total_charge = ?
            WHERE transport_id = ?";
        $params = [
            $customerId,
            $firmDate,
            $accountType,
            $originLocation,
            $destinationLocation,
            $coronerName,
            $pouchType,
            $transitPermitNumber,
            $tagNumber,
            $callTime,
            $arrivalTime,
            $departureTime,
            $deliveryTime,
            $primaryTransporter,
            $assistantTransporter,
            $mileage,
            $mileage_rate,
            $mileage_total_charge,
            $transportId
        ];
        error_log('updateTransport called for id ' . $transportId . ' with tag_number: ' . (isset($params[8]) ? $params[8] : 'NOT SET'));
        error_log('updateTransport SQL: ' . $sql);
        error_log('updateTransport params: ' . json_encode($params));
        try {
            $result = $this->db->execute($sql, $params);
            if (!$result) {
                error_log('SQL updateTransport failed: ' . $sql . ' Params: ' . json_encode($params));
            }
            return $result;
        } catch (Exception $e) {
            error_log('Exception in updateTransport: ' . $e->getMessage());
            return false;
        }
    }

    public function delete(int $transport_id): int {
        $sql = "DELETE FROM transport WHERE transport_id = ?";
        return $this->db->execute($sql, [$transport_id]);
    }

    public function getCount(): int {
        $sql = "SELECT COUNT(*) AS cnt FROM transport";
        $result = $this->db->query($sql);
        return isset($result[0]['cnt']) ? (int)$result[0]['cnt'] : 0;
    }

    public function getPaginated(int $pageSize, int $offset): array {
        $pageSize = max(1, (int)$pageSize);
        $offset = max(0, (int)$offset);
        $sql = "SELECT t.*, d.first_name AS decedent_first_name, d.last_name AS decedent_last_name
                FROM transport t
                LEFT JOIN decedents d ON t.transport_id = d.transport_id
                ORDER BY t.transport_id DESC
                LIMIT $pageSize OFFSET $offset";
        return $this->db->query($sql);
    }

    // Returns the total number of transports matching a search term
    public function getCountBySearch(string $search): int {
        $like = '%' . strtolower($search) . '%';
        $sql = "SELECT COUNT(*) as cnt FROM transport t LEFT JOIN decedents d ON t.transport_id = d.transport_id WHERE (LOWER(t.origin_location) LIKE ? OR LOWER(t.destination_location) LIKE ?) OR (LOWER(d.first_name) LIKE ? OR LOWER(d.last_name) LIKE ?)";
        error_log('[getCountBySearch] SQL: ' . $sql);
        error_log('[getCountBySearch] PARAMS: ' . json_encode([$like, $like, $like, $like]));
        $result = $this->db->query(
            $sql,
            [$like, $like, $like, $like]
        );
        return isset($result[0]['cnt']) ? (int)$result[0]['cnt'] : 0;
    }

    // Returns a paginated list of transports matching a search term
    public function searchPaginated(string $search, int $limit, int $offset): array {
        $limit = max(1, (int)$limit);
        $offset = max(0, (int)$offset);
        $like = '%' . strtolower($search) . '%';
        $sql = "SELECT t.*, d.first_name AS decedent_first_name, d.last_name AS decedent_last_name FROM transport t LEFT JOIN decedents d ON t.transport_id = d.transport_id WHERE (LOWER(t.origin_location) LIKE ? OR LOWER(t.destination_location) LIKE ?) OR (LOWER(d.first_name) LIKE ? OR LOWER(d.last_name) LIKE ?) ORDER BY t.transport_id DESC LIMIT $limit OFFSET $offset";
        error_log('[searchPaginated] SQL: ' . $sql);
        error_log('[searchPaginated] PARAMS: ' . json_encode([$like, $like, $like, $like]));
        return $this->db->query($sql, [$like, $like, $like, $like]);
    }
}
