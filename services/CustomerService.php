<?php
require_once __DIR__ . '/../database/CustomerData.php';
require_once __DIR__ . '/../database/Database.php';
/**
 * CustomerService
 *
 * Service class for customer-related business logic.
 *
 * Methods in this class handle business rules, formatting, and validation
 * that are not strictly data access (which remains in CustomerData).
 *
 * Last reviewed: 2025-10-09
 */
class CustomerService {
    private $repo;

    /**
     * CustomerService constructor.
     * @param Database|null $db
     */
    public function __construct($db = null) {
        if ($db === null) {
            $db = new Database();
        }
        $this->repo = new CustomerData($db);
    }

    /**
     * Format a display name for a customer.
     * @param array $customer Customer data array
     * @return string
     */
    public function formatDisplayName($customer) {
        $company = $customer['company_name'] ?? '';
        return $company;
    }

    /**
     * Find a customer by id.
     * @param int|string $id
     * @return array|null
     */
    public function findById($id) {
        return $this->repo->findById($id);
    }

    /**
     * Delete a customer by id.
     * @param int|string $id
     * @return bool|int
     */
    public function delete($id) {
        return $this->repo->delete($id);
    }

    /**
     * Check if a customer exists by company name.
     * @param string $company_name
     * @return bool
     */
    public function existsByName($company_name) {
        return $this->repo->existsByName($company_name);
    }

    /**
     * Create a new customer record.
     * @param string $company_name
     * @param string $phone_number
     * @param string $address_1
     * @param string $address_2
     * @param string $city
     * @param string $state
     * @param string $zip
     * @param string $email_address
     * @return int|false New customer ID or false on failure
     */
    public function create($company_name, $phone_number, $address_1, $address_2, $city, $state, $zip, $email_address) {
        return $this->repo->create($company_name, $phone_number, $address_1, $address_2, $city, $state, $zip, $email_address);
    }

    /**
     * Update an existing customer record.
     * @param int|string $id
     * @param string $company_name
     * @param string $phone_number
     * @param string $address_1
     * @param string $address_2
     * @param string $city
     * @param string $state
     * @param string $zip
     * @param string $email_address
     * @return bool|int
     */
    public function update($id, $company_name, $phone_number, $address_1, $address_2, $city, $state, $zip, $email_address) {
        return $this->repo->update($id, $company_name, $phone_number, $address_1, $address_2, $city, $state, $zip, $email_address);
    }

    /**
     * Get the total number of customers.
     * @return int
     */
    public function getCount() {
        return $this->repo->getCount();
    }

    /**
     * Get paginated list of customers.
     * @param int $pageSize
     * @param int $offset
     * @return array
     */
    public function getPaginated($pageSize, $offset) {
        return $this->repo->getPaginated($pageSize, $offset);
    }

    /**
     * Get the total number of customers matching a search term.
     * @param string $search
     * @return int
     */
    public function getCountBySearch($search) {
        return $this->repo->getCountBySearch($search);
    }

    /**
     * Get paginated list of customers matching a search term.
     * @param string $search
     * @param int $pageSize
     * @param int $offset
     * @return array
     */
    public function searchPaginated($search, $pageSize, $offset) {
        return $this->repo->searchPaginated($search, $pageSize, $offset);
    }
}
