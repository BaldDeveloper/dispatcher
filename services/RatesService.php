<?php
/**
 * RatesService.php
 * Service layer for rates. Wraps RatesData for simple business operations.
 */
require_once __DIR__ . '/../database/RatesData.php';
require_once __DIR__ . '/../database/Database.php';

class RatesService {
    private $repo;

    public function __construct($db = null) {
        if ($db === null) {
            $db = new Database();
        }
        $this->repo = new RatesData($db);
    }

    /**
     * Find the rates configuration (singleton row) or for a specific customer
     * @param int|null $customerNumber
     * @return array|null
     */
    public function find(?int $customerNumber = null): ?array {
        return $this->repo->find($customerNumber);
    }

    /**
     * Save rates data (create or update)
     * @param array $data
     * @return int|false id on success or false on failure
     */
    public function saveRates(array $data) {
        return $this->repo->save($data);
    }

    /**
     * Delete the existing rates row by id
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool {
        return $this->repo->delete($id);
    }
}
