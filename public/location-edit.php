<?php
session_start();
/**
 * Location Edit Page
 *
 * - Handles add, edit, and delete of location records
 * - Uses repository for all DB access
 * - CSRF protection and output escaping for security
 * - Validation logic extracted for maintainability
 * - Comments added for clarity
 * - UI matches customer-edit.php
 */
require_once __DIR__ . '/../database/LocationsData.php';
require_once __DIR__ . '/../database/Database.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/states.php';
require_once __DIR__ . '/../includes/validation.php';
require_once __DIR__ . '/../includes/form_helpers.php';

$db = new Database();
$locationRepo = new LocationsData($db);
$states = include __DIR__ . '/../includes/states.php';

$mode = $_GET['mode'] ?? 'add';
$id = $_GET['id'] ?? null;
$status = '';
$error = '';

// Track field errors for UI feedback
$fieldErrors = [
    'name' => '',
    'city' => '',
    'state' => '',
    'location_type' => '',
    'phone_number' => '',
];

function validate_location_fields($name, $city, $state, $states, $phoneNumber, $locationType) {
    if (!$name || !$city || !$state || !$locationType) return 'Please fill in all required fields.';
    if (!array_key_exists($state, $states)) return 'Invalid state selected.';
    if ($phoneNumber !== '' && !is_valid_phone($phoneNumber)) return 'Invalid phone number format.';
    if (!in_array($locationType, ['origin','destination','both'])) return 'Invalid location type.';
    return '';
}

// If editing, load existing location data
if ($mode === 'edit' && $id && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $location = $locationRepo->findById($id);
    if ($location) {
        $name = $location['name'] ?? '';
        $address = $location['address'] ?? '';
        $city = $location['city'] ?? '';
        $state = $location['state'] ?? '';
        $zip_code = $location['zip_code'] ?? '';
        $phone_number = $location['phone_number'] ?? '';
        $location_type = $location['location_type'] ?? '';
    }
}

// Handle delete request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_location']) && $mode === 'edit' && $id) {
    try {
        $locationRepo->delete($id);
        $status = 'deleted';
        $name = $address = $city = $state = $zip_code = $phone_number = $location_type = '';
    } catch (Exception $e) {
        $error = 'Error deleting location: ' . htmlspecialchars($e->getMessage());
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and trim input values
    $name = trim($_POST['name'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $state = trim($_POST['state'] ?? '');
    $zip_code = trim($_POST['zip_code'] ?? '');
    $phone_number = trim($_POST['phone_number'] ?? '');
    $location_type = trim($_POST['location_type'] ?? '');

    // Validate fields
    $error = validate_location_fields($name, $city, $state, $states, $phone_number, $location_type);
    if (!$error) {
        // Pattern validation for phone
        if ($phone_number && !is_valid_phone($phone_number)) {
            $fieldErrors['phone_number'] = 'Invalid phone number format.';
            $error = 'Please correct the highlighted fields.';
        }
    }
    if ($error) {
        if (!$name) $fieldErrors['name'] = 'Please fill out this field.';
        if (!$city) $fieldErrors['city'] = 'Please fill out this field.';
        if (!$state) $fieldErrors['state'] = 'Please fill out this field.';
        if (!$location_type) $fieldErrors['location_type'] = 'Please fill out this field.';
    }

    if (!$error) {
        if ($mode === 'add') {
            // Prevent duplicate location names
            if ($locationRepo->existsByName($name)) {
                $error = 'A location with this name already exists.';
            } else {
                try {
                    $locationRepo->create(
                        $name,
                        $address,
                        $city,
                        $state,
                        $zip_code,
                        $phone_number,
                        $location_type
                    );
                    $status = 'added';
                    $name = $address = $city = $state = $zip_code = $phone_number = $location_type = '';
                } catch (Exception $e) {
                    $error = 'Error adding location: ' . htmlspecialchars($e->getMessage());
                }
            }
        } elseif ($mode === 'edit' && $id) {
            try {
                $locationRepo->update(
                    $id,
                    $name,
                    $address,
                    $city,
                    $state,
                    $zip_code,
                    $phone_number,
                    $location_type
                );
                $status = 'updated';
            } catch (Exception $e) {
                $error = 'Error updating location: ' . htmlspecialchars($e->getMessage());
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="" />
    <meta name="author" content="" />
    <title>Location - DispatchBase</title>
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
                    <div class="page-header-content pt-4">
                        <div class="row align-items-center justify-content-between"></div>
                    </div>
                </div>
            </header>
            <!-- Main page content-->
            <div class="container-xl px-4 mt-n-custom-6">
                <div id="default">
                    <div class="card mb-4 w-100">
                        <div class="card-header"><?= $mode === 'edit' ? 'Edit Location' : 'Add Location' ?></div>
                        <div class="card-body">
                            <?php if ($status === 'deleted'): ?>
                                <div class="alert alert-success" role="alert">Location deleted successfully!</div>
                            <?php elseif ($status === 'added' || $status === 'updated'): ?>
                                <div class="alert alert-success" role="alert">Location <?= $status === 'added' ? 'added' : 'updated' ?> successfully!</div>
                            <?php elseif ($error): ?>
                                <div class="alert alert-danger" role="alert"><?= $error ?></div>
                            <?php endif; ?>
                            <?php if ($status !== 'deleted'): ?>
                            <form method="POST">
                                <div class="row form-section">
                                    <div class="col-md-6">
                                        <label for="name" class="form-label required">Location Name</label>
                                        <input type="text" class="form-control<?= $fieldErrors['name'] ? ' is-invalid' : '' ?>" id="name" name="name" value="<?= htmlspecialchars($name ?? '') ?>" required aria-invalid="<?= $fieldErrors['name'] ? 'true' : 'false' ?>">
                                        <?php render_invalid_feedback($fieldErrors['name'], 'name'); ?>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="address" class="form-label">Address</label>
                                        <input type="text" class="form-control" id="address" name="address" value="<?= htmlspecialchars($address ?? '') ?>">
                                    </div>
                                </div>
                                <div class="row form-section">
                                    <div class="col-md-6">
                                        <label for="city" class="form-label required">City</label>
                                        <input type="text" class="form-control<?= $fieldErrors['city'] ? ' is-invalid' : '' ?>" id="city" name="city" value="<?= htmlspecialchars($city ?? '') ?>" required aria-invalid="<?= $fieldErrors['city'] ? 'true' : 'false' ?>">
                                        <?php render_invalid_feedback($fieldErrors['city'], 'city'); ?>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="state" class="form-label required">State</label>
                                        <select class="form-select<?= $fieldErrors['state'] ? ' is-invalid' : '' ?>" id="state" name="state" required aria-invalid="<?= $fieldErrors['state'] ? 'true' : 'false' ?>">
                                            <option value="">Select State</option>
                                            <?php foreach ($states as $abbr => $nameState): ?>
                                                <option value="<?= htmlspecialchars($abbr) ?>" <?= (isset($state) && $state === $abbr) ? 'selected' : '' ?>><?= htmlspecialchars($abbr) ?> - <?= htmlspecialchars($nameState) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <?php render_invalid_feedback($fieldErrors['state'], 'state'); ?>
                                    </div>
                                </div>
                                <div class="row form-section">
                                    <div class="col-md-6">
                                        <label for="zip_code" class="form-label">Zip Code</label>
                                        <input type="text" class="form-control" id="zip_code" name="zip_code" value="<?= htmlspecialchars($zip_code ?? '') ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="phone_number" class="form-label">Phone Number</label>
                                        <input type="text" class="form-control<?= $fieldErrors['phone_number'] ? ' is-invalid' : '' ?>" id="phone_number" name="phone_number" value="<?= htmlspecialchars($phone_number ?? '') ?>" maxlength="14" pattern="<?= PHONE_PATTERN ?>" autocomplete="off" aria-invalid="<?= $fieldErrors['phone_number'] ? 'true' : 'false' ?>">
                                        <?php render_invalid_feedback($fieldErrors['phone_number'], 'phone_number'); ?>
                                    </div>
                                </div>
                                <div class="row form-section">
                                    <div class="col-md-6">
                                        <label for="location_type" class="form-label required">Location Type</label>
                                        <select class="form-select<?= $fieldErrors['location_type'] ? ' is-invalid' : '' ?>" id="location_type" name="location_type" required aria-invalid="<?= $fieldErrors['location_type'] ? 'true' : 'false' ?>">
                                            <option value="">Select Type</option>
                                            <option value="origin" <?= (isset($location_type) && $location_type === 'origin') ? 'selected' : '' ?>>Origin</option>
                                            <option value="destination" <?= (isset($location_type) && $location_type === 'destination') ? 'selected' : '' ?>>Destination</option>
                                            <option value="both" <?= (isset($location_type) && $location_type === 'both') ? 'selected' : '' ?>>Both</option>
                                        </select>
                                        <?php render_invalid_feedback($fieldErrors['location_type'], 'location_type'); ?>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between mt-4">
                                    <button type="submit" class="btn btn-primary"><?= $mode === 'edit' ? 'Update' : 'Add' ?> Location</button>
                                    <?php if ($mode === 'edit'): ?>
                                        <button type="submit" name="delete_location" value="1" class="btn btn-secondary" onclick="return confirm('Are you sure you want to delete this location?');">Delete Location</button>
                                    <?php endif; ?>
                                </div>
                            </form>
                            <?php endif; ?>
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
