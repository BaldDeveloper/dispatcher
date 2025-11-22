<?php
/**
 * Vehicle Edit Page
 *
 * - Handles add, edit, and delete of vehicle records (fields to be added)
 * - Uses service for all DB access (to be implemented)
 * - CSRF protection and output escaping for security
 * - Validation logic extracted for maintainability
 * - Comments added for clarity
 * - Role-based access control can be added if needed
 */

session_start();

require_once __DIR__ . '/../database/Database.php';
require_once __DIR__ . '/../database/VehicleData.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/validation.php';
require_once __DIR__ . '/../includes/form_helpers.php';

$mode = $_GET['mode'] ?? 'add';
$id = $_GET['id'] ?? null;

$db = new Database();
$vehicleData = new VehicleData($db);

$status = '';
$error = '';
$errors = [];

$showRequiredHeader = false;
$formSubmitted = false;
$firstMissingField = null;

/**
 * Returns an associative array of all vehicle fields initialized to default values
 */
function reset_vehicle_fields() {
    return [
        'id' => '',
        'vehicle_type' => '',
        'color' => '',
        'license_plate' => '',
        'year' => '',
        'make' => '',
        'model' => '',
        'vin' => '',
        'refrigeration_unit' => '',
        'fuel_type' => '',
        'odometer_reading' => '',
        'trailer_compatible' => '',
        'emission_cert_status' => '',
        'inspection_notes' => '',
        'assigned_mechanic' => '',
        'last_service_date' => '',
        'next_service_date' => '',
        'service_interval' => '',
        'maintenance_status' => '',
        'current_status' => '',
        'tire_condition' => '',
        'battery_health' => '',
        'registration_expiry' => '',
        'insurance_provider' => '',
        'insurance_policy_number' => '',
        'insurance_expiry' => '',
        'notes' => ''
    ];
}

/**
 * Populates and normalizes vehicle fields from DB for form usage
 */
function populate_vehicle_fields_from_db($vehicle) {
    return [
        'id' => $vehicle['id'] ?? '',
        'vehicle_type' => $vehicle['vehicle_type'] ?? '',
        'color' => $vehicle['color'] ?? '',
        'license_plate' => $vehicle['license_plate'] ?? '',
        'year' => $vehicle['year_of_manufacture'] ?? '',
        'make' => $vehicle['make'] ?? '',
        'model' => $vehicle['model'] ?? '',
        'vin' => $vehicle['vin'] ?? '',
        'refrigeration_unit' => isset($vehicle['refrigeration_unit']) ? ($vehicle['refrigeration_unit'] === 1 ? 'Yes' : ($vehicle['refrigeration_unit'] === 0 ? 'No' : '')) : '',
        'fuel_type' => $vehicle['fuel_type'] ?? '',
        'odometer_reading' => $vehicle['odometer_reading'] ?? '',
        'trailer_compatible' => isset($vehicle['trailer_compatible']) ? ($vehicle['trailer_compatible'] === 1 ? 'Yes' : ($vehicle['trailer_compatible'] === 0 ? 'No' : '')) : '',
        'emission_cert_status' => $vehicle['emission_cert_status'] ?? '',
        'inspection_notes' => $vehicle['inspection_notes'] ?? '',
        'assigned_mechanic' => $vehicle['assigned_mechanic'] ?? '',
        'last_service_date' => $vehicle['last_service_date'] ?? '',
        'next_service_date' => $vehicle['next_service_date'] ?? '',
        'service_interval' => $vehicle['service_interval'] ?? '',
        'maintenance_status' => $vehicle['maintenance_status'] ?? '',
        'current_status' => $vehicle['current_status'] ?? '',
        'tire_condition' => $vehicle['tire_condition'] ?? '',
        'battery_health' => $vehicle['battery_health'] ?? '',
        'registration_expiry' => $vehicle['registration_expiry'] ?? '',
        'insurance_provider' => $vehicle['insurance_provider'] ?? '',
        'insurance_policy_number' => $vehicle['insurance_policy_number'] ?? '',
        'insurance_expiry' => $vehicle['insurance_expiry'] ?? '',
        'notes' => $vehicle['notes'] ?? ''
    ];
}

/**
 * Loads and populates vehicle fields for the form by vehicle ID
 */
function load_vehicle_fields($id) {
    global $vehicleData, $error;
    $vehicle = $vehicleData->getById((int)$id);
    if ($vehicle) {
        $fields = populate_vehicle_fields_from_db($vehicle);
        extract($fields);
        return $fields;
    } else {
        $error = 'Vehicle not found.';
        return reset_vehicle_fields();
    }
}

// Use the function to initialize fields
$fields = reset_vehicle_fields();
// Preserve requested id across extract
$__requestedId = $id;
extract($fields);
$id = $__requestedId;

// After POST, validate using centralized function only
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and trim all input values using helper
    $cleanFields = sanitize_and_trim_vehicle_fields($_POST);
    extract($cleanFields);
    $last_service_date_db = ($last_service_date === '') ? null : $last_service_date;
    $next_service_date_db = ($next_service_date === '') ? null : $next_service_date;
    $registration_expiry_db = ($registration_expiry === '') ? null : $registration_expiry;
    $insurance_expiry_db = ($insurance_expiry === '') ? null : $insurance_expiry;

    // Centralized validation
    $errors = validate_vehicle_fields([
        'vehicle_type' => $vehicle_type,
        'color' => $color,
        'license_plate' => $license_plate,
        'year' => $year,
        'make' => $make,
        'model' => $model,
        'vin' => $vin
    ]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $mode === 'add') {
    // Use sanitized and trimmed fields from above
    // Centralized validation already done above
    if (empty($errors)) {
        try {
            error_log('DEBUG: Attempting to add vehicle record at ' . date('Y-m-d H:i:s'));
            // Normalize for DB using helper
            $dbFields = normalize_vehicle_fields_for_db($cleanFields);
            $newId = $vehicleData->addVehicle($dbFields);
            $status = 'added';
            // Clear all form fields after successful add
            $fields = reset_vehicle_fields();
            extract($fields);
            // Prevent validation from firing after successful add
            $formSubmitted = false;
            $missingRequired = false;
            $firstMissingField = null;
        } catch (Exception $e) {
            error_log('ERROR: Exception in add block: ' . $e->getMessage());
            $error = 'Error adding vehicle: ' . htmlspecialchars($e->getMessage());
        }
    }
}
// Populate fields if editing and not submitting form
if ($mode === 'edit' && $id && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Preserve requested id across extract
    $__requestedId = $id;
    $fields = load_vehicle_fields($id);
    extract($fields);
    $id = $__requestedId;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $mode === 'edit' && $id) {
    // Use sanitized and trimmed fields from above
    // Centralized validation already done above
    if (empty($errors) && empty($requiredError)) {
        try {
            // Normalize for DB using helper
            $dbFields = normalize_vehicle_fields_for_db($cleanFields);
            $vehicleData->updateVehicle((int)$id, $dbFields);
            $status = 'updated';
            // Reload updated data from DB to repopulate form
            $fields = load_vehicle_fields($id);
            extract($fields);
        } catch (Exception $e) {
            $error = 'Error updating vehicle: ' . htmlspecialchars($e->getMessage());
        }
    }
}

// Handle vehicle deletion
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    $mode === 'edit' &&
    $id &&
    isset($_POST['delete_vehicle'])
) {
    try {
        $vehicleData->deleteVehicle((int)$id);
        $status = 'deleted';
        // Clear all fields after deletion
        $fields = reset_vehicle_fields();
        extract($fields);
    } catch (Exception $e) {
        $error = 'Error deleting vehicle: ' . htmlspecialchars($e->getMessage());
    }
}

// Ensure this block is immediately before the HTML output (right before )
// Track if the form was submitted
$formSubmitted = ($status === 'added') ? false : ($_SERVER['REQUEST_METHOD'] === 'POST');

// Identify required fields and the first missing one
$requiredFields = [
    'vehicle_type' => $vehicle_type,
    'color' => $color,
    'license_plate' => $license_plate,
    'year' => $year,
    'make' => $make,
    'model' => $model,
    'vin' => $vin
];
$firstMissingField = null;
$missingRequired = false;
if ($formSubmitted) {
    error_log('DEBUG: Required fields check running at POST time on ' . date('Y-m-d H:i:s'));
    foreach ($requiredFields as $field => $value) {
        if (empty($value)) {
            if ($firstMissingField === null) {
                $firstMissingField = $field;
            }
            $missingRequired = true;
        }
    }
}
// Always show the required header if the form was submitted and any required field is missing
// Ensure it does NOT show after a successful add/update/delete
$showRequiredHeader = ($formSubmitted && $missingRequired && $status !== 'added' && $status !== 'updated' && $status !== 'deleted');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="" />
    <meta name="author" content="" />
    <title>Vehicle - DispatchBase</title>
    <link href="css/styles.css" rel="stylesheet" />
    <link rel="icon" type="image/x-icon" href="assets/img/favicon.png" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <script data-search-pseudo-elements defer src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.3.0/js/all.min.js" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/feather-icons/4.29.0/feather.min.js" crossorigin="anonymous"></script>
</head>
<body class="nav-fixed">
<div id="topnav"></div>
<div id="layoutSidenav">
    <div id="layoutSidenav_nav"></div>
    <div id="layoutSidenav_content">
        <main>
            <header class="page-header page-header-dark bg-gradient-primary-to-secondary pb-5" style="padding-bottom: 9%;">
                <div class="container-xl px-4">
                    <?php if ($showRequiredHeader): ?>
                        <div class="alert alert-danger mt-4 mb-0" role="alert" style="font-size:1.15rem; font-weight:500;">
                            Please fill in all required fields.
                        </div>
                    <?php endif; ?>
                    <div class="page-header-content pt-4">
                        <div class="row align-items-center justify-content-between">
                        </div>
                    </div>
                </div>
            </header>
            <!-- Main page content-->
            <div class="container-xl px-4 mt-n-custom-6">
                <div id="default">
                    <div class="card mb-4 w-100">
                        <div class="card-header">Vehicle</div>
                        <div class="card-body">
                            <?php if ($status === 'deleted'): ?>
                                <div class="alert alert-success" role="alert">
                                    Vehicle deleted successfully!
                                </div>
                            <?php elseif ($status === 'added' || $status === 'updated'): ?>
                                <div class="alert alert-success" role="alert">
                                    Vehicle <?= $status === 'added' ? 'added' : 'updated' ?> successfully!
                                </div>
                            <?php elseif ($error): ?>
                                <div class="alert alert-danger" role="alert">
                                    <?= $error ?>
                                </div>
                            <?php endif; ?>
                            <?php if (isset($errors['_required'])): ?>
                                <div class="alert alert-danger" role="alert">
                                    <?= htmlspecialchars($errors['_required']) ?>
                                </div>
                            <?php endif; ?>
                            <form method="POST">
                                <?php csrf_token_field(); ?>
                                <div class="row form-section mb-3">
                                    <div class="col-md-4 mb-3">
                                        <label for="vehicle_type" class="form-label required">Vehicle Type</label>
                                        <select class="form-select<?= ($formSubmitted && $firstMissingField === 'vehicle_type') ? ' is-invalid' : '' ?>" id="vehicle_type" name="vehicle_type" required>
                                            <option value="">Select Vehicle Type</option>
                                            <option value="Truck" <?= (isset($vehicle_type) && $vehicle_type === 'Truck') ? 'selected' : '' ?>>Truck</option>
                                            <option value="Van" <?= (isset($vehicle_type) && $vehicle_type === 'Van') ? 'selected' : '' ?>>Van</option>
                                            <option value="Bus" <?= (isset($vehicle_type) && $vehicle_type === 'Bus') ? 'selected' : '' ?>>Bus</option>
                                            <option value="Car" <?= (isset($vehicle_type) && $vehicle_type === 'Car') ? 'selected' : '' ?>>Car</option>
                                            <option value="Trailer" <?= (isset($vehicle_type) && $vehicle_type === 'Trailer') ? 'selected' : '' ?>>Trailer</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="color" class="form-label required">Color</label>
                                        <input type="text" class="form-control<?= ($errors && isset($errors['color'])) ? ' is-invalid' : '' ?>" id="color" name="color" value="<?= htmlspecialchars($color ?? '') ?>" required>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="license_plate" class="form-label required">License Plate Number</label>
                                        <input type="text" class="form-control<?= (isset($errors['license_plate']) ? ' is-invalid' : '') ?>" id="license_plate" name="license_plate" value="<?= htmlspecialchars($license_plate ?? '') ?>" required pattern="<?= LICENSE_PLATE_PATTERN ?>" title="2-15 letters or numbers, no spaces or symbols">
                                    </div>
                                </div>
                                <div class="row form-section mb-3">
                                    <div class="col-md-4 mb-3">
                                        <label for="year" class="form-label required">Year</label>
                                        <input type="text" class="form-control<?= ($errors && isset($errors['year'])) ? ' is-invalid' : '' ?>" id="year" name="year" value="<?= htmlspecialchars($year ?? '') ?>" required pattern="^(19|20)\d{2}$">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="make" class="form-label required">Make</label>
                                        <input type="text" class="form-control<?= ($errors && isset($errors['make'])) ? ' is-invalid' : '' ?>" id="make" name="make" value="<?= htmlspecialchars($make ?? '') ?>" required>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="model" class="form-label required">Model</label>
                                        <input type="text" class="form-control<?= ($errors && isset($errors['model'])) ? ' is-invalid' : '' ?>" id="model" name="model" value="<?= htmlspecialchars($model ?? '') ?>" required>
                                    </div>
                                </div>
                                <div class="row form-section mb-3">
                                    <div class="col-md-4 mb-3">
                                        <label for="vin" class="form-label required">VIN</label>
                                        <input type="text" class="form-control<?= ($errors && isset($errors['vin'])) ? ' is-invalid' : '' ?>" id="vin" name="vin" value="<?= htmlspecialchars($vin ?? '') ?>" required pattern="^[A-HJ-NPR-Za-hj-npr-z0-9]{17}$">
                                        <?php if ($errors && isset($errors['vin'])): ?>
                                            <div class="invalid-feedback d-block">
                                                VIN must be exactly 17 characters (letters and numbers only).
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="refrigeration_unit" class="form-label">Refrigeration Unit</label>
                                        <select class="form-select" id="refrigeration_unit" name="refrigeration_unit">
                                            <option value="">Select Option</option>
                                            <option value="Yes" <?= (isset($refrigeration_unit) && $refrigeration_unit === 'Yes') ? 'selected' : '' ?>>Yes</option>
                                            <option value="No" <?= (isset($refrigeration_unit) && $refrigeration_unit === 'No') ? 'selected' : '' ?>>No</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="fuel_type" class="form-label">Fuel Type</label>
                                        <select class="form-select" id="fuel_type" name="fuel_type">
                                            <option value="">Select Fuel Type</option>
                                            <option value="Diesel" <?= (isset($fuel_type) && $fuel_type === 'Diesel') ? 'selected' : '' ?>>Diesel</option>
                                            <option value="Petrol" <?= (isset($fuel_type) && $fuel_type === 'Petrol') ? 'selected' : '' ?>>Petrol</option>
                                            <option value="Electric" <?= (isset($fuel_type) && $fuel_type === 'Electric') ? 'selected' : '' ?>>Electric</option>
                                            <option value="Hybrid" <?= (isset($fuel_type) && $fuel_type === 'Hybrid') ? 'selected' : '' ?>>Hybrid</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row form-section mb-3">
                                    <div class="col-md-4 mb-3">
                                        <label for="odometer_reading" class="form-label">Odometer Reading</label>
                                        <input type="text" class="form-control" id="odometer_reading" name="odometer_reading" value="<?= htmlspecialchars($odometer_reading ?? '') ?>">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="trailer_compatible" class="form-label">Trailer Compatible</label>
                                        <select class="form-select" id="trailer_compatible" name="trailer_compatible">
                                            <option value="">Select Option</option>
                                            <option value="Yes" <?= (isset($trailer_compatible) && $trailer_compatible === 'Yes') ? 'selected' : '' ?>>Yes</option>
                                            <option value="No" <?= (isset($trailer_compatible) && $trailer_compatible === 'No') ? 'selected' : '' ?>>No</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row form-section mb-3">
                                    <div class="col-md-6 mb-3">
                                        <label for="emission_cert_status" class="form-label">Emission Certification Status</label>
                                        <input type="text" class="form-control" id="emission_cert_status" name="emission_cert_status" value="<?= htmlspecialchars($emission_cert_status ?? '') ?>">
                                    </div>
                                </div>
                                <div class="row form-section mb-3">
                                    <div class="col-md-6 mb-3">
                                        <label for="inspection_notes" class="form-label">Inspection Notes</label>
                                        <textarea class="form-control" id="inspection_notes" name="inspection_notes" rows="2"><?= htmlspecialchars($inspection_notes ?? '') ?></textarea>
                                    </div>
                                </div>

                                <!-- Service Information Section -->
                                <hr class="my-4">
                                <h5 class="mb-3">Service Information</h5>
                                <div class="row form-section mb-3">
                                    <div class="col-md-6 mb-3">
                                        <label for="assigned_mechanic" class="form-label">Assigned Mechanic or Service Provider</label>
                                        <input type="text" class="form-control" id="assigned_mechanic" name="assigned_mechanic" value="<?= htmlspecialchars($assigned_mechanic ?? '') ?>">
                                    </div>
                                </div>
                                <div class="row form-section mb-3">
                                    <div class="col-md-4 mb-3">
                                        <label for="last_service_date" class="form-label">Last Service Date</label>
                                        <input type="date" class="form-control" id="last_service_date" name="last_service_date" value="<?= htmlspecialchars($last_service_date ?? '') ?>">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="next_service_date" class="form-label">Next Scheduled Service Date</label>
                                        <input type="date" class="form-control" id="next_service_date" name="next_service_date" value="<?= htmlspecialchars($next_service_date ?? '') ?>">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="service_interval" class="form-label">Service Interval</label>
                                        <input type="text" class="form-control" id="service_interval" name="service_interval" value="<?= htmlspecialchars($service_interval ?? '') ?>">
                                    </div>
                                </div>
                                <div class="row form-section mb-3">
                                    <div class="col-md-6 mb-3">
                                        <label for="maintenance_status" class="form-label">Maintenance Status</label>
                                        <select class="form-select<?= isset($errors['maintenance_status']) ? ' is-invalid' : '' ?>" id="maintenance_status" name="maintenance_status" required>
                                            <option value=""<?= ($maintenance_status === '' ? ' selected' : '') ?>>Select Maintenance Status</option>
                                            <option value="Up-To-Date"<?= ($maintenance_status === 'Up-To-Date' ? ' selected' : '') ?>>Up-To-Date</option>
                                            <option value="Overdue"<?= ($maintenance_status === 'Overdue' ? ' selected' : '') ?>>Overdue</option>
                                            <option value="In Repair"<?= ($maintenance_status === 'In Repair' ? ' selected' : '') ?>>In Repair</option>
                                        </select>
                                        <?php if (isset($errors['maintenance_status'])): ?>
                                            <div class="invalid-feedback d-block">
                                                <?= htmlspecialchars($errors['maintenance_status']) ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="current_status" class="form-label">Current Status</label>
                                        <select class="form-select<?= isset($errors['current_status']) ? ' is-invalid' : '' ?>" id="current_status" name="current_status" required>
                                            <option value=""<?= ($current_status === '' ? ' selected' : '') ?>>Select Status</option>
                                            <option value="Active"<?= ($current_status === 'Active' ? ' selected' : '') ?>>Active</option>
                                            <option value="Idle"<?= ($current_status === 'Idle' ? ' selected' : '') ?>>Idle</option>
                                            <option value="Under Maintenance"<?= ($current_status === 'Under Maintenance' ? ' selected' : '') ?>>Under Maintenance</option>
                                        </select>
                                        <?php if (isset($errors['current_status'])): ?>
                                            <div class="invalid-feedback d-block">
                                                <?= htmlspecialchars($errors['current_status']) ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="row form-section mb-3">
                                    <div class="col-md-6 mb-3">
                                        <label for="tire_condition" class="form-label">Tire Condition</label>
                                        <input type="text" class="form-control" id="tire_condition" name="tire_condition" value="<?= htmlspecialchars($tire_condition ?? '') ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="battery_health" class="form-label">Battery Health (for electric vehicles)</label>
                                        <input type="text" class="form-control" id="battery_health" name="battery_health" value="<?= htmlspecialchars($battery_health ?? '') ?>">
                                    </div>
                                </div>
                                <div class="row form-section mb-3">
                                    <div class="col-md-6 mb-3">
                                        <label for="registration_expiry" class="form-label">Registration Expiry Date</label>
                                        <input type="date" class="form-control" id="registration_expiry" name="registration_expiry" value="<?= htmlspecialchars($registration_expiry ?? '') ?>">
                                    </div>
                                </div>
                                <!-- Insurance Section -->
                                <hr class="my-4">
                                <h5 class="mb-3">Insurance</h5>
                                <div class="row form-section mb-3">
                                    <div class="col-md-4 mb-3">
                                        <label for="insurance_provider" class="form-label">Insurance Provider</label>
                                        <input type="text" class="form-control" id="insurance_provider" name="insurance_provider" value="<?= htmlspecialchars($insurance_provider ?? '') ?>">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="insurance_policy_number" class="form-label">Insurance Policy Number</label>
                                        <input type="text" class="form-control" id="insurance_policy_number" name="insurance_policy_number" value="<?= htmlspecialchars($insurance_policy_number ?? '') ?>">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="insurance_expiry" class="form-label">Insurance Expiry</label>
                                        <input type="date" class="form-control" id="insurance_expiry" name="insurance_expiry" value="<?= htmlspecialchars($insurance_expiry ?? '') ?>">
                                    </div>
                                </div>
                                <!-- Notes Section -->
                                <hr class="my-4">
                                <h5 class="mb-3">Notes</h5>
                                <div class="row form-section mb-3">
                                    <div class="col-12 mb-3">
                                        <label for="notes" class="form-label">Notes</label>
                                        <textarea class="form-control" id="notes" name="notes" rows="3"><?= htmlspecialchars($notes ?? '') ?></textarea>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between mt-4">
                                    <button type="submit" class="btn btn-primary"><?= $mode === 'edit' ? 'Update' : 'Add' ?> Vehicle</button>
                                    <?php if ($mode === 'edit'): ?>
                                        <button type="submit" name="delete_vehicle" value="1" class="btn btn-secondary" onclick="return confirm('Are you sure you want to delete this vehicle?');">Delete Vehicle</button>
                                    <?php endif; ?>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </main>
        <div id="footer"></div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
<script src="js/scripts.js"></script>
<script src="js/phone-format.js"></script>
<script>
    // Dynamically load topnav.html into #topnav
    fetch('topnav.html')
        .then(response => response.text())
        .then(html => {
            var topnav = document.getElementById('topnav');
            if (topnav) {
                topnav.innerHTML = html;
                feather.replace();
                if (typeof initSidebarToggle === 'function') initSidebarToggle();
            }
        });
    // Dynamically load sidebar.html into #layoutSidenav_nav
    fetch('sidebar.html')
        .then(response => response.text())
        .then(html => {
            var sidenav = document.getElementById('layoutSidenav_nav');
            if (sidenav) {
                sidenav.innerHTML = html;
                feather.replace();
            }
        });
    // Dynamically load footer.html into #footer
    fetch('footer.html')
        .then(response => response.text())
        .then(html => {
            var footer = document.getElementById('footer');
            if (footer) {
                footer.innerHTML = html;
            }
        });
</script>
</body>
</html>
