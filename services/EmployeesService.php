<?php
require_once __DIR__ . '/../database/EmployeesData.php';
require_once __DIR__ . '/../database/Database.php';

/**
 * EmployeesService
 *
 * Service layer for employees business logic.
 */
class EmployeesService {
    private $repo;

    public function __construct($db = null) {
        if ($db === null) {
            $db = new Database();
        }
        $this->repo = new EmployeesData($db);
    }

    public function getAll() {
        return $this->repo->getAll();
    }

    public function findById($id) {
        return $this->repo->findById((int)$id);
    }

    public function create($user_id, $job_title, $salary = null, $employment_type = 'permanent', $start_date = null, $end_date = null, $status = 'active') {
        return $this->repo->create((int)$user_id, $job_title, $salary !== null ? (float)$salary : null, $employment_type, $start_date, $end_date, $status);
    }

    public function update($id, $user_id, $job_title, $salary = null, $employment_type = 'permanent', $start_date = null, $end_date = null, $status = 'active') {
        return $this->repo->update((int)$id, (int)$user_id, $job_title, $salary !== null ? (float)$salary : null, $employment_type, $start_date, $end_date, $status);
    }

    public function delete($id) {
        return $this->repo->delete((int)$id);
    }

    public function getCount() {
        return $this->repo->getCount();
    }

    public function getPaginated($pageSize, $offset) {
        return $this->repo->getPaginated((int)$pageSize, (int)$offset);
    }

    public function existsByUserId($user_id) {
        return $this->repo->existsByUserId((int)$user_id);
    }

    public function getCountBySearch($search) {
        return $this->repo->getCountBySearch($search);
    }

    public function searchPaginated($search, $pageSize, $offset) {
        return $this->repo->searchPaginated($search, (int)$pageSize, (int)$offset);
    }
}

