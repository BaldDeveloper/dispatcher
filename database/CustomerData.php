<?php
require_once __DIR__ . '/Database.php';

class CustomerData {
    private Database $db;

    public function __construct(Database $db) {
        $this->db = $db;
    }

    public function getAll(): array {
        return $this->db->query("SELECT * FROM customer ORDER BY customer_number DESC");
    }

    public function findByCustomerNumber(int $customer_number): ?array {
        $result = $this->db->query("SELECT * FROM customer WHERE customer_number = ?", [$customer_number]);
        return $result[0] ?? null;
    }

    public function create(
        string $company_name,
        string $phone_number,
        string $address_1,
        string $address_2,
        string $city,
        string $state,
        string $zip,
        string $email
    ): int {
        return $this->db->insert(
            "INSERT INTO customer (company_name, phone_number, address_1, address_2, city, state, zip, email_address) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            [$company_name, $phone_number, $address_1, $address_2, $city, $state, $zip, $email]
        );
    }

    public function update(
        int $customer_number,
        string $company_name,
        string $phone_number,
        string $address_1,
        string $address_2,
        string $city,
        string $state,
        string $zip,
        string $email
    ): int {
        return $this->db->execute(
            "UPDATE customer SET company_name = ?, phone_number = ?, address_1 = ?, address_2 = ?, city = ?, state = ?, zip = ?, email_address = ? WHERE customer_number = ?",
            [$company_name, $phone_number, $address_1, $address_2, $city, $state, $zip, $email, $customer_number]
        );
    }

    public function delete(int $customer_number): int {
        return $this->db->execute("DELETE FROM customer WHERE customer_number = ?", [$customer_number]);
    }

    // Returns the total number of customers in the table
    public function getCount(): int {
        $result = $this->db->query("SELECT COUNT(*) AS cnt FROM customer");
        return isset($result[0]['cnt']) ? (int)$result[0]['cnt'] : 0;
    }

    // Returns a paginated list of customers
    public function getPaginated(int $limit, int $offset): array {
        // MySQL does not allow LIMIT/OFFSET as bound parameters, so inject as integers
        $limit = max(1, (int)$limit);
        $offset = max(0, (int)$offset);
        return $this->db->query(
            "SELECT * FROM customer ORDER BY customer_number DESC LIMIT $limit OFFSET $offset"
        );
    }

    public function existsByName(string $company_name): bool {
        $result = $this->db->query("SELECT customer_number FROM customer WHERE company_name = ? LIMIT 1", [$company_name]);
        return !empty($result);
    }

    // Returns the total number of customers matching a search term
    public function getCountBySearch(string $search): int {
        $like = '%' . $search . '%';
        $result = $this->db->query("SELECT COUNT(*) AS cnt FROM customer WHERE company_name LIKE ?", [$like]);
        return isset($result[0]['cnt']) ? (int)$result[0]['cnt'] : 0;
    }

    // Returns a paginated list of customers matching a search term
    public function searchPaginated(string $search, int $limit, int $offset): array {
        $limit = max(1, (int)$limit);
        $offset = max(0, (int)$offset);
        $like = '%' . $search . '%';
        return $this->db->query(
            "SELECT * FROM customer WHERE company_name LIKE ? ORDER BY customer_number DESC LIMIT $limit OFFSET $offset",
            [$like]
        );
    }
}
