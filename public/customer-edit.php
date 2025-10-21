<?php
/**
 * Customer Edit Page
 *
 * - Handles add, edit, and delete of customer records
 * - Uses service for all DB access
 * - CSRF protection and output escaping for security
 * - Validation logic extracted for maintainability
 * - Comments added for clarity
 * - Role-based access control can be added if needed
 *
 * NOTE: Role-based access control is currently commented out for development/testing purposes.
 * Uncomment when ready for production.
 */
session_start();
// // Example role check - uncomment and adapt as needed
// // if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
// //     header('Location: login.php');
// //     exit;
// // }

require_once __DIR__ . '/../database/Database.php';
require_once __DIR__ . '/../services/CustomerService.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/validation.php';
require_once __DIR__ . '/../includes/form_helpers.php';
$states = include __DIR__ . '/../includes/states.php';

$db = new Database();
$customerService = new CustomerService($db);

$mode = $_GET['mode'] ?? 'add';
$id = isset($_GET['id']) ? (int)$_GET['id'] : null; // cast to int for safety
$status = '';
$error = '';

// Initialize form variables to avoid undefined variable notices in the view
$company_name = $phone_number = $email_address = $address_1 = $address_2 = $city = $state = $zip = '';

// Server-side CSRF validation for all POST requests. If token is invalid, set $error
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedToken = $_POST['csrf_token'] ?? '';
    if (!function_exists('validate_csrf_token') || !validate_csrf_token($postedToken)) {
        $error = 'Invalid request (CSRF token mismatch).';
    }
}

/**
 * Validate customer fields for add/edit
 *
 * @param string $company_name
 * @param string $email_address
 * @param string $city
 * @param string $state
 * @param array $states
 * @param string $phone_number
 * @return string Error message or empty string if valid
 */
function validate_customer_fields($company_name, $email_address, $city, $state, $states, $phone_number) {
    if (!$company_name || !$state || !$email_address || !$city) return 'Please fill in all required fields.';
    if (!array_key_exists($state, $states)) return 'Invalid state selected.';
    if (!is_valid_email($email_address)) return 'Invalid email address.';
    if ($phone_number !== '' && !is_valid_phone($phone_number)) return 'Invalid phone number format.';
    return '';
}

/**
 * Sanitize and trim all customer input fields
 */
function sanitize_and_trim_customer_fields($input) {
    return [
        'company_name' => trim($input['company_name'] ?? ''),
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
 * Normalize customer fields for DB
 */
function normalize_customer_fields_for_db($fields) {
    return [
        $fields['company_name'],
        $fields['phone_number'],
        $fields['address_1'],
        $fields['address_2'],
        $fields['city'],
        $fields['state'],
        $fields['zip'],
        $fields['email_address']
    ];
}

// If editing, load existing customer data
if ($mode === 'edit' && $id && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $customer = $customerService->findByCustomerNumber($id);
    if ($customer) {
        $company_name = $customer['company_name'] ?? '';
        $phone_number = $customer['phone_number'] ?? '';
        $email_address = $customer['email_address'] ?? '';
        $address_1 = $customer['address_1'] ?? '';
        $address_2 = $customer['address_2'] ?? '';
        $city = $customer['city'] ?? '';
        $state = $customer['state'] ?? '';
        $zip = $customer['zip'] ?? '';
    }
}

// Handle delete request (only proceed if CSRF validation passed)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error && isset($_POST['delete_customer']) && $mode === 'edit' && $id) {
    try {
        $customerService->delete($id);
        $status = 'deleted';
        // Clear form values so form does not show after deletion
        $company_name = $phone_number = $email_address = $address_1 = $address_2 = $city = $state = $zip = '';
    } catch (Exception $e) {
        // Log full error, show generic message
        error_log($e->getMessage());
        $error = 'An internal error occurred while deleting the customer.';
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {
    // Sanitize and trim input values
    $fields = sanitize_and_trim_customer_fields($_POST);
    extract($fields);
    // Validate fields
    $error = validate_customer_fields($company_name, $email_address, $city, $state, $states, $phone_number);
    if (!$error) {
        if ($mode === 'add') {
            // Prevent duplicate customer names
            if ($customerService->existsByName($company_name)) {
                $error = 'A customer with this company name already exists.';
            } else {
                try {
                    $dbFields = normalize_customer_fields_for_db($fields);
                    $customerService->create(...$dbFields);
                    $status = 'added';
                    // Clear form fields after successful add
                    foreach ($fields as $key => $val) {
                        $$key = '';
                    }
                } catch (Exception $e) {
                    error_log($e->getMessage());
                    $error = 'An internal error occurred while adding the customer.';
                }
            }
        } elseif ($mode === 'edit' && $id) {
            try {
                $dbFields = normalize_customer_fields_for_db($fields);
                $customerService->update($id, ...$dbFields);
                $status = 'updated';
            } catch (Exception $e) {
                error_log($e->getMessage());
                $error = 'An internal error occurred while updating the customer.';
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
    <title>Customer - DispatchBase</title>
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
                        <div class="card-header"><?php echo ($mode === 'edit') ? 'Edit Customer' : 'Add Customer'; ?></div>
                        <div class="card-body">
                            <?php if ($status === 'deleted'): ?>
                                <div class="alert alert-success" role="alert">
                                    Customer deleted successfully!
                                </div>
                            <?php elseif ($status === 'added' || $status === 'updated'): ?>
                                <div class="alert alert-success" role="alert">
                                    Customer <?php echo $status === 'added' ? 'added' : 'updated' ?> successfully!
                                </div>
                            <?php elseif ($error): ?>
                                <div class="alert alert-danger" role="alert">
                                    <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
                                </div>
                            <?php endif; ?>
                            <?php if ($status !== 'deleted'): ?>
                            <form method="POST">
                                <?php
                                // csrf_token_field() is defined in includes/csrf.php
                                csrf_token_field();
                                ?>
                                <div class="row form-section">
                                    <div class="col-md-6">
                                        <label for="company_name" class="form-label required">Company Name</label>
                                        <input type="text" class="form-control<?= ($error && !$company_name) ? ' is-invalid' : '' ?>" id="company_name" name="company_name" value="<?= htmlspecialchars($company_name ?? '') ?>" required>
                                        <?php render_invalid_feedback('Please fill out this field.', $error && !$company_name); ?>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="phone_number" class="form-label">Phone Number</label>
                                        <input type="text" class="form-control<?= ($error && $phone_number !== '' && !is_valid_phone($phone_number)) ? ' is-invalid' : '' ?>" id="phone_number" name="phone_number" value="<?= htmlspecialchars($phone_number ?? '') ?>" maxlength="14" pattern="<?= PHONE_PATTERN ?>" autocomplete="off">
                                        <?php render_invalid_feedback('Invalid phone number format.', $error && $phone_number !== '' && !is_valid_phone($phone_number)); ?>
                                    </div>
                                </div>
                                <div class="row form-section">
                                    <div class="col-md-6">
                                        <label for="address_1" class="form-label required">Address 1</label>
                                        <input type="text" class="form-control<?= ($error && !$address_1) ? ' is-invalid' : '' ?>" id="address_1" name="address_1" value="<?= htmlspecialchars($address_1 ?? '') ?>" required>
                                        <?php render_invalid_feedback('Please fill out this field.', $error && !$address_1); ?>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="address_2" class="form-label">Address 2</label>
                                        <input type="text" class="form-control" id="address_2" name="address_2" value="<?= htmlspecialchars($address_2 ?? '') ?>">
                                    </div>
                                </div>
                                <div class="row form-section">
                                    <div class="col-md-4">
                                        <label for="city" class="form-label required">City</label>
                                        <input type="text" class="form-control<?= ($error && !$city) ? ' is-invalid' : '' ?>" id="city" name="city" value="<?= htmlspecialchars($city ?? '') ?>" required>
                                        <?php render_invalid_feedback('Please fill out this field.', $error && !$city); ?>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="state" class="form-label required">State</label>
                                        <select class="form-select<?= ($error && !$state) ? ' is-invalid' : '' ?>" id="state" name="state" required>
                                            <option value="">Select State</option>
                                            <?php foreach ($states as $abbr => $name): ?>
                                                <option value="<?= htmlspecialchars($abbr) ?>" <?= (isset($state) && $state === $abbr) ? 'selected' : '' ?>><?= htmlspecialchars($abbr) ?> - <?= htmlspecialchars($name) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <?php render_invalid_feedback('Please fill out this field.', $error && !$state); ?>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="zip" class="form-label">Zip</label>
                                        <input type="text" class="form-control" id="zip" name="zip" value="<?= htmlspecialchars($zip ?? '') ?>">
                                    </div>
                                </div>
                                <div class="row form-section">
                                    <div class="col-md-6">
                                        <label for="email_address" class="form-label required">Email Address</label>
                                        <input type="email" class="form-control email-pattern<?= ($error && !is_valid_email($email_address)) ? ' is-invalid' : '' ?>" id="email_address" name="email_address" value="<?= htmlspecialchars($email_address ?? '') ?>" required pattern="<?= EMAIL_PATTERN ?>">
                                        <?php render_invalid_feedback('Please fill out this field.', $error && !is_valid_email($email_address)); ?>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between mt-4">
                                    <button type="submit" class="btn btn-primary"><?= $mode === 'edit' ? 'Update' : 'Add' ?> Customer</button>
                                    <?php if ($mode === 'edit'): ?>
                                        <button type="submit" name="delete_customer" value="1" class="btn btn-secondary" onclick="return confirm('Are you sure you want to delete this customer?');">Delete Customer</button>
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
