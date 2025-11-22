<?php
require_once __DIR__ . '/../database/CoronerData.php';
require_once __DIR__ . '/../database/Database.php';
/**
 * CoronerService
 *
 * Service class for coroner-related business logic.
 *
 * Methods in this class handle business rules, formatting, and validation
 * that are not strictly data access (which remains in CoronerData).
 *
 * Last reviewed: 2025-10-09
 */
class CoronerService {
    private $repo;

    /**
     * CoronerService constructor.
     * @param Database $db
     */
    public function __construct($db = null) {
        if ($db === null) {
            $db = new Database();
        }
        $this->repo = new CoronerData($db);
    }

    /**
     * Format a display name for a coroner.
     * @param array $coroner Coroner data array
     * @return string
     */
    public function formatDisplayName($coroner) {
        $first = $coroner['first_name'] ?? '';
        $last = $coroner['last_name'] ?? '';
        $name = $coroner['coroner_name'] ?? '';
        if ($name) return $name;
        return trim("$first $last");
    }

    /**
     * Find a coroner by ID.
     * Uses the standardized `id` field on the `coroners` table.
     * @param int|string $id
     * @return array|null
     */
    public function findById($id) {
        // Delegate to the repository's findById which queries the `coroners` table by `id`.
        return $this->repo->findById((int)$id);
    }

    /**
     * Delete a coroner by ID.
     * @param int|string $id
     * @return bool
     */
    public function delete($id) {
        return $this->repo->delete($id);
    }

    /**
     * Check if a coroner exists by name.
     * @param string $name
     * @return bool
     */
    public function existsByName($name) {
        return $this->repo->existsByName($name);
    }

    /**
     * Create a new coroner record.
     * @param string $coronerName
     * @param string $phoneNumber
     * @param string $emailAddress
     * @param string $address1
     * @param string $address2
     * @param string $city
     * @param string $state
     * @param string $zip
     * @param string $county
     * @return int|false New coroner ID or false on failure
     */
    public function create($coronerName, $phoneNumber, $emailAddress, $address1, $address2, $city, $state, $zip, $county) {
        return $this->repo->create($coronerName, $phoneNumber, $emailAddress, $address1, $address2, $city, $state, $zip, $county);
    }

    /**
     * Update an existing coroner record.
     * @param int|string $id
     * @param string $coronerName
     * @param string $phoneNumber
     * @param string $emailAddress
     * @param string $address1
     * @param string $address2
     * @param string $city
     * @param string $state
     * @param string $zip
     * @param string $county
     * @return bool
     */
    public function update($id, $coronerName, $phoneNumber, $emailAddress, $address1, $address2, $city, $state, $zip, $county) {
        return $this->repo->update($id, $coronerName, $phoneNumber, $emailAddress, $address1, $address2, $city, $state, $zip, $county);
    }

    /**
     * Get the total number of coroners.
     * @return int
     */
    public function getCount() {
        return $this->repo->getCount();
    }

    /**
     * Get paginated list of coroners.
     * @param int $pageSize
     * @param int $offset
     * @return array
     */
    public function getPaginated($pageSize, $offset) {
        return $this->repo->getPaginated($pageSize, $offset);
    }

    /**
     * Get all coroners.
     * @return array
     */
    public function getAll() {
        return $this->repo->getAll();
    }

    /**
     * Get the total number of coroners matching a search term.
     * @param string $search
     * @return int
     */
    public function getCountBySearch($search) {
        return $this->repo->getCountBySearch($search);
    }

    /**
     * Get paginated list of coroners matching a search term.
     * @param string $search
     * @param int $pageSize
     * @param int $offset
     * @return array
     */
    public function searchPaginated($search, $pageSize, $offset) {
        return $this->repo->searchPaginated($search, $pageSize, $offset);
    }
}
