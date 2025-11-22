<?php
require_once __DIR__ . '/../database/TransportData.php';
require_once __DIR__ . '/../database/Database.php';
/**
 * TransportService
 *
 * Service class for dispatch-related business logic (table: dispatches).
 *
 * Methods in this class handle business rules, formatting, and validation
 * that are not strictly data access (which remains in TransportData).
 *
 * Last reviewed: 2025-10-09
 */
class TransportService {
    private $repo;

    /**
     * TransportService constructor.
     * @param Database|null $db
     */
    public function __construct($db = null) {
        if ($db === null) {
            $db = new Database();
        }
        $this->repo = new TransportData($db);
    }

    /**
     * Find a dispatch by ID.
     * @param int|string $id
     * @return array|null
     */
    public function findById($id) {
        return $this->repo->findById($id);
    }

    /**
     * Delete a dispatch by ID.
     * @param int|string $id
     * @return bool|int
     */
    public function delete($id) {
        return $this->repo->delete($id);
    }

    /**
     * Create a new dispatch record.
     * @param ... (see TransportData for full param list)
     * @return int|false New transport ID or false on failure
     */
    public function create(...$args) {
        return $this->repo->create(...$args);
    }

    /**
     * Update an existing dispatch record.
     * @param ... (see TransportData for full param list)
     * @return bool|int
     */
    public function update(...$args) {
        return $this->repo->update(...$args);
    }

    /**
     * Get the total number of dispatches.
     * @return int
     */
    public function getCount() {
        return $this->repo->getCount();
    }

    /**
     * Get paginated list of dispatches.
     * @param int $pageSize
     * @param int $offset
     * @return array
     */
    public function getPaginated($pageSize, $offset) {
        return $this->repo->getPaginated($pageSize, $offset);
    }

    /**
     * Get all dispatches.
     * @return array
     */
    public function getAll() {
        return $this->repo->getAll();
    }

    /**
     * Get the total number of dispatches matching a search term.
     * @param string $search
     * @return int
     */
    public function getCountBySearch($search) {
        return $this->repo->getCountBySearch($search);
    }

    /**
     * Get paginated list of dispatches matching a search term.
     * @param string $search
     * @param int $pageSize
     * @param int $offset
     * @return array
     */
    public function searchPaginated($search, $pageSize, $offset) {
        return $this->repo->searchPaginated($search, $pageSize, $offset);
    }
}
