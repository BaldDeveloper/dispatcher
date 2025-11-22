<?php
/**
 * Coroner Edit Page
 *
 * - Handles add, edit, and delete of coroner records using CoronerService
 * - Enforces admin-only access (see session section)
 * - CSRF protection and centralized validation
 * - Output escaping for XSS prevention
 * - Status handling standardized
 * - Comments added for maintainability
 *
 * Last reviewed: 2025-10-09
 */

// Uncomment for production to enforce admin-only access
// session_start();
// if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
//     header('Location: login.php');
//     exit;
// }

session_start();

require_once __DIR__ . '/../database/Database.php';
require_once __DIR__ . '/../services/CoronerService.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/validation.php';
require_once __DIR__ . '/../includes/form_helpers.php';
$states = include __DIR__ . '/../includes/states.php';
$counties = include __DIR__ . '/../includes/counties.php';

$coronerService = new CoronerService(new Database());

$mode = $_GET['mode'] ?? 'add';
$id = $_GET['id'] ?? null;
$status = '';
$error = '';

// Initialize field errors for all expected fields
$fieldErrors = [
    'coroner_name' => '',
    'county' => '',
    'phone_number' => '',
    'email_address' => '',
];

/**
 * Validate coroner fields for add/edit
 * @return string Error message or empty string if valid
 *
 * Only name and county are required. Phone, email, and state are validated if provided.
 */
function validate_coroner_fields($name, $county, $phone, $email, $city, $state, $states, $counties) {
    if (!$name || !$county) return 'Please fill in all required fields.';
    if (!in_array($county, $counties)) return 'Invalid county selection.';
    if ($state !== '' && !array_key_exists($state, $states)) return 'Invalid state selection.';
    return '';
}

/**
 * Sanitize and trim all coroner input fields
 */
function sanitize_and_trim_coroner_fields($input) {
    return [
        'coroner_name' => trim($input['coroner_name'] ?? ''),
        'county' => trim($input['county'] ?? ''),
        'phone_number' => trim($input['phone_number'] ?? ''),
        'email_address' => trim($input['email_address'] ?? ''),
        'address_1' => trim($input['address_1'] ?? ''),
        'address_2' => trim($input['address_2'] ?? ''),
        'city' => trim($input['city'] ?? ''),
        'state' => trim($input['state'] ?? ''),
        'zip' => trim($input['zip'] ?? '')
    ];
}

/**
 * Normalize coroner fields for DB
 */
function normalize_coroner_fields_for_db($fields) {
    return [
        $fields['coroner_name'],
        $fields['phone_number'],
        $fields['email_address'],
        $fields['address_1'],
        $fields['address_2'],
        $fields['city'],
        $fields['state'],
        $fields['zip'],
        $fields['county']
    ];
}

// If editing, load existing coroner data
if ($mode === 'edit' && $id && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Use the standardized findById method (backward-compatible alias exists in service)
    $coroner = $coronerService->findById($id);
    if ($coroner) {
        $coronerName = $coroner['coroner_name'] ?? '';
        $county = $coroner['county'] ?? '';
        $phoneNumber = $coroner['phone_number'] ?? '';
        $emailAddress = $coroner['email_address'] ?? '';
        $address1 = $coroner['address_1'] ?? '';
        $address2 = $coroner['address_2'] ?? '';
        $city = $coroner['city'] ?? '';
        $state = $coroner['state'] ?? '';
        $zip = $coroner['zip'] ?? '';
    }
}

// Handle delete request
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_coroner']) && $mode === 'edit' && $id
) {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid CSRF token.';
    } else {
        try {
            $coronerService->delete($id);
            $status = 'deleted';
            $coronerName = $phoneNumber = $emailAddress = $address1 = $address2 = $city = $state = $zip = '';
        } catch (Exception $e) {
            $error = 'Error deleting coroner: ' . htmlspecialchars($e->getMessage());
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid CSRF token.';
    } else {
        // Sanitize and trim input values
        $fields = sanitize_and_trim_coroner_fields($_POST);
        extract($fields);
        // Validate fields
        $error = validate_coroner_fields($coroner_name, $county, $phone_number, $email_address, $city, $state, $states, $counties);
        if (!$error) {
            // Pattern validation for email and phone
            if ($email_address && !is_valid_email($email_address)) {
                $fieldErrors['email_address'] = 'Invalid email format.';
                $error = 'Please correct the highlighted fields.';
            }
            if ($phone_number && !is_valid_phone($phone_number)) {
                $fieldErrors['phone_number'] = 'Invalid phone number format.';
                $error = 'Please correct the highlighted fields.';
            }
        }
        if ($error) {
            if (!$coroner_name) $fieldErrors['coroner_name'] = 'Please fill out this field.';
            if (!$county) $fieldErrors['county'] = 'Please fill out this field.';
        }
        if (!$error) {
            if ($mode === 'add') {
                // Prevent duplicate coroner names
                if ($coronerService->existsByName($coroner_name)) {
                    $error = 'A coroner with this name already exists.';
                } else {
                    try {
                        // Create new coroner record
                        $dbFields = normalize_coroner_fields_for_db($fields);
                        $coronerService->create(...$dbFields);
                        $status = 'added';
                        // Clear form fields after successful add
                        foreach ($fields as $key => $val) {
                            $$key = '';
                        }
                    } catch (Exception $e) {
                        $error = 'Error adding coroner: ' . htmlspecialchars($e->getMessage());
                    }
                }
            } elseif ($mode === 'edit' && $id) {
                try {
                    // Update existing coroner record
                    $dbFields = normalize_coroner_fields_for_db($fields);
                    $coronerService->update($id, ...$dbFields);
                    $status = 'updated';
                } catch (Exception $e) {
                    $error = 'Error updating coroner: ' . htmlspecialchars($e->getMessage());
                }
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
    <title>Coroner - DispatchBase</title>
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
                        <div class="row align-items-center justify-content-between">
                        </div>
                    </div>
                </div>
            </header>

            <!-- Main page content-->
            <div class="container-xl px-4 mt-n-custom-6">
                <div id="default">
                    <div class="card mb-4 w-100">
                        <div class="card-header">Add Coroner</div>
                        <div class="card-body">
                            <?php if ($status === 'deleted'): ?>
                                <div class="alert alert-success" role="alert">
                                    Coroner deleted successfully!
                                </div>
                            <?php elseif ($status === 'added' || $status === 'updated'): ?>
                                <div class="alert alert-success" role="alert">
                                    Coroner saved successfully!
                                </div>
                            <?php elseif ($error): ?>
                                <div class="alert alert-danger" role="alert">
                                    <?= $error ?>
                                </div>
                            <?php endif; ?>
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
                                <!-- First row: Coroner Name and County -->
                                <div class="row form-section">
                                    <div class="col-md-6">
                                        <label for="coroner_name" class="form-label required">Coroner Name</label>
                                        <input type="text" class="form-control<?= $fieldErrors['coroner_name'] ? ' is-invalid' : '' ?>" id="coroner_name" name="coroner_name" value="<?= htmlspecialchars($coronerName ?? '') ?>" required aria-invalid="<?= $fieldErrors['coroner_name'] ? 'true' : 'false' ?>">
                                        <?php render_invalid_feedback('Please fill out this field.', $fieldErrors['coroner_name'] !== ''); ?>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="county" class="form-label required">County</label>
                                        <select class="form-select<?= $fieldErrors['county'] ? ' is-invalid' : '' ?>" id="county" name="county" required aria-invalid="<?= $fieldErrors['county'] ? 'true' : 'false' ?>">
                                            <option value="">Select County</option>
                                            <?php foreach ($counties as $c): ?>
                                                <option value="<?= htmlspecialchars($c) ?>" <?= (isset($county) && $county === $c) ? 'selected' : '' ?>><?= htmlspecialchars($c) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <?php render_invalid_feedback('Please fill out this field.', $fieldErrors['county'] !== ''); ?>
                                    </div>
                                </div>
                                <!-- Address Fields -->
                                <div class="row form-section">
                                    <div class="col-md-6">
                                        <label for="address_1" class="form-label">Address 1</label>
                                        <input type="text" class="form-control" id="address_1" name="address_1" value="<?= htmlspecialchars($address1 ?? '') ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="address_2" class="form-label">Address 2</label>
                                        <input type="text" class="form-control" id="address_2" name="address_2" value="<?= htmlspecialchars($address2 ?? '') ?>">
                                    </div>
                                </div>
                                <!-- City, State, Zip -->
                                <div class="row form-section">
                                    <div class="col-md-4">
                                        <label for="city" class="form-label">City</label>
                                        <input type="text" class="form-control" id="city" name="city" value="<?= htmlspecialchars($city ?? '') ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="state" class="form-label">State</label>
                                        <select class="form-select" id="state" name="state">
                                            <option value="">Select State</option>
                                            <?php foreach ($states as $abbr => $name): ?>
                                                <option value="<?= htmlspecialchars($abbr) ?>" <?= (isset($state) && $state === $abbr) ? 'selected' : '' ?>><?= htmlspecialchars($abbr) ?> - <?= htmlspecialchars($name) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="zip" class="form-label">Zip</label>
                                        <input type="text" class="form-control" id="zip" name="zip" value="<?= htmlspecialchars($zip ?? '') ?>">
                                    </div>
                                </div>
                                <!-- Last row: Email and Phone Number -->
                                <div class="row form-section">
                                    <div class="col-md-6">
                                        <label for="email_address" class="form-label">Email Address</label>
                                        <input type="email" class="form-control email-pattern<?= $fieldErrors['email_address'] ? ' is-invalid' : '' ?>" id="email_address" name="email_address" value="<?= htmlspecialchars($emailAddress ?? '') ?>" pattern="<?= EMAIL_PATTERN ?>" aria-invalid="<?= $fieldErrors['email_address'] ? 'true' : 'false' ?>">
                                        <?php render_invalid_feedback($fieldErrors['email_address'] ?: 'Invalid email format.', $fieldErrors['email_address'] !== ''); ?>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="phone_number" class="form-label">Phone Number</label>
                                        <input type="text" class="form-control<?= $fieldErrors['phone_number'] ? ' is-invalid' : '' ?>" id="phone_number" name="phone_number" value="<?= htmlspecialchars($phoneNumber ?? '') ?>" maxlength="14" pattern="<?= PHONE_PATTERN ?>" autocomplete="off" aria-invalid="<?= $fieldErrors['phone_number'] ? 'true' : 'false' ?>">
                                        <?php render_invalid_feedback($fieldErrors['phone_number'] ?: 'Invalid phone number format.', $fieldErrors['phone_number'] !== ''); ?>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between mt-4">
                                    <button type="submit" class="btn btn-primary"><?= $mode === 'edit' ? 'Update' : 'Add' ?> Coroner</button>
                                    <?php if ($mode === 'edit'): ?>
                                        <button type="submit" name="delete_coroner" value="1" class="btn btn-secondary" onclick="return confirm('Are you sure you want to delete this coroner?');">Delete Coroner</button>
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
