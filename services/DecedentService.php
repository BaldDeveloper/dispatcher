<?php
// DecedentService.php
// Service class for decedents-related business logic

require_once __DIR__ . '/../database/DecedentData.php';
require_once __DIR__ . '/../database/Database.php';
require_once __DIR__ . '/../includes/validation.php';

/**
 * DecedentService
 *
 * Service class for decedents-related business logic.
 *
 * Methods in this class handle business rules, formatting, and validation
 * that are not strictly data access (which remains in DecedentData).
 *
 * Last reviewed: 2025-10-09
 */
class DecedentService {
    private $repo;

    /**
     * DecedentService constructor.
     * @param Database|null $db
     */
    public function __construct($db = null) {
        if ($db === null) {
            $db = new Database();
        }
        $this->repo = new DecedentData($db);
    }

    /**
     * Get all decedents (table: decedents).
     * @return array
     */
    public function getAll() {
        return $this->repo->getAll();
    }

    /**
     * Find a decedent by ID (table: decedents).
     * @param int|string $decedent_id
     * @return array|null
     */
    public function findById($decedent_id) {
        return $this->repo->findById($decedent_id);
    }

    /**
     * Update a decedent by ID, with validation (table: decedents).
     * @param int $decedent_id
     * @param string $first_name
     * @param string $last_name
     * @param string $ethnicity
     * @param string $gender
     * @return int
     */
    public function update($decedent_id, $first_name, $last_name, $ethnicity, $gender) {
        if (!is_valid_name($first_name) || !is_valid_name($last_name)) {
            throw new InvalidArgumentException('Invalid name.');
        }
        return $this->repo->update($decedent_id, $first_name, $last_name, $ethnicity, $gender);
    }

    /**
     * Update a decedent by dispatch ID (table: decedents).
     * @param int $id
     * @param string $first_name
     * @param string $last_name
     * @param string $ethnicity
     * @param string $gender
     * @return int
     */
    public function updateByTransportId($id, $first_name, $last_name, $ethnicity, $gender) {
        return $this->repo->updateByTransportId($id, $first_name, $last_name, $ethnicity, $gender);
    }

    /**
     * Delete a decedent by ID (table: decedents).
     * @param int $decedent_id
     * @return int
     */
    public function delete($decedent_id) {
        return $this->repo->delete($decedent_id);
    }

    /**
     * Delete a decedent by dispatch ID (table: decedents).
     * @param int $id
     * @return int
     */
    public function deleteByTransportId($id) {
        return $this->repo->deleteByTransportId($id);
    }

    /**
     * Insert a decedent by dispatch ID (table: decedents).
     * @param int $id
     * @param string $first_name
     * @param string $last_name
     * @param string $ethnicity
     * @param string $gender
     * @return int
     */
    public function insertByTransportId($id, $first_name, $last_name, $ethnicity, $gender) {
        return $this->repo->insertByTransportId($id, $first_name, $last_name, $ethnicity, $gender);
    }
}
