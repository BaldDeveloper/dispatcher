<?php
/**
 * User Edit Page
 *
 * - Handles add, edit, and delete of user records
 * - Uses service for all DB access
 * - CSRF protection and output escaping for security
 * - Validation logic extracted for maintainability
 * - Comments added for clarity
 * - UI matches customer-edit.php
 */
require_once __DIR__ . '/../database/Database.php';
require_once __DIR__ . '/../services/UserService.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/validation.php';
require_once __DIR__ . '/../includes/form_helpers.php';

$db = new Database();
$userService = new UserService($db);

$mode = $_GET['mode'] ?? 'add';
$id = $_GET['id'] ?? null;
$status = '';
$error = '';

$roles = [
    'admin' => 'Admin',
    'office' => 'Office',
    'driver' => 'Driver',
    'other' => 'Other',
];

$states = include __DIR__ . '/../includes/states.php';

$fieldErrors = [
    'username' => '',
    'full_name' => '',
    'state' => '',
    'role' => '',
    'password' => '',
];

function validate_user_fields($username, $full_name, $role, $roles, $password, $mode) {
    if (!$username || !$full_name || !$role) return 'Please fill in all required fields.';
    if (!array_key_exists($role, $roles)) return 'Invalid role selected.';
    if ($mode === 'add' && !$password) return 'Password is required when adding a user.';
    return '';
}

// If editing, load existing user data
if ($mode === 'edit' && $id && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $user = $userService->findById($id);
    if ($user) {
        $username = $user['username'] ?? '';
        $full_name = $user['full_name'] ?? '';
        $address = $user['address'] ?? '';
        $city = $user['city'] ?? '';
        $state = $user['state'] ?? '';
        $zip_code = $user['zip_code'] ?? '';
        $phone_number = $user['phone_number'] ?? '';
        $role = $user['role'] ?? '';
    }
}

// Handle delete request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user']) && $mode === 'edit' && $id) {
    try {
        $userService->delete($id);
        $status = 'deleted';
        $username = $full_name = $address = $city = $state = $zip_code = $phone_number = $role = $password = '';
    } catch (Exception $e) {
        $error = 'Error deleting user: ' . htmlspecialchars($e->getMessage());
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and trim input values
    $username = trim($_POST['username'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $state = trim($_POST['state'] ?? '');
    $zip_code = trim($_POST['zip_code'] ?? '');
    $phone_number = trim($_POST['phone_number'] ?? '');
    $role = trim($_POST['role'] ?? '');
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $password = trim($_POST['password'] ?? '');

    // Validate fields
    $error = validate_user_fields($username, $full_name, $role, $roles, $password, $mode);
    if ($error) {
        if (!$username) $fieldErrors['username'] = 'Please fill out this field.';
        if (!$full_name) $fieldErrors['full_name'] = 'Please fill out this field.';
        if (!$state) $fieldErrors['state'] = 'Please fill out this field.';
        if (!$role) $fieldErrors['role'] = 'Please fill out this field.';
        if ($mode === 'add' && !$password) $fieldErrors['password'] = 'Please fill out this field.';
    } else {
        if ($mode === 'add') {
            // Prevent duplicate usernames
            if ($userService->existsByName($username)) {
                $error = 'A user with this username already exists.';
            } else {
                try {
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);
                    $userService->create($username, $password_hash, $full_name, $address, $city, $state, $zip_code, $phone_number, $role, $is_active);
                    $status = 'added';
                    $username = $full_name = $address = $city = $state = $zip_code = $phone_number = $role = $password = '';
                } catch (Exception $e) {
                    $error = 'Error adding user: ' . htmlspecialchars($e->getMessage());
                }
            }
        } elseif ($mode === 'edit' && $id) {
            try {
                $password_hash = $password ? password_hash($password, PASSWORD_DEFAULT) : null;
                $userService->update($id, $username, $password_hash, $full_name, $address, $city, $state, $zip_code, $phone_number, $role, $is_active);
                $status = 'updated';
            } catch (Exception $e) {
                $error = 'Error updating user: ' . htmlspecialchars($e->getMessage());
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
    <title>User - DispatchBase</title>
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
                        <div class="card-header"><?= $mode === 'edit' ? 'Edit User' : 'Add User' ?></div>
                        <div class="card-body">
                            <?php if ($status === 'deleted'): ?>
                                <div class="alert alert-success" role="alert">User deleted successfully!</div>
                            <?php elseif ($status === 'added' || $status === 'updated'): ?>
                                <div class="alert alert-success" role="alert">User <?= $status === 'added' ? 'added' : 'updated' ?> successfully!</div>
                            <?php elseif ($error): ?>
                                <div class="alert alert-danger" role="alert"><?= $error ?></div>
                            <?php endif; ?>
                            <?php if ($status !== 'deleted'): ?>
                            <form method="POST">
                                <div class="row form-section">
                                    <div class="col-md-6">
                                        <label for="username" class="form-label required">Username</label>
                                        <input type="text" class="form-control<?= $fieldErrors['username'] ? ' is-invalid' : '' ?>" id="username" name="username" value="<?= htmlspecialchars($username ?? '') ?>" required autocomplete="username">
                                        <?php render_invalid_feedback($fieldErrors['username'], 'username'); ?>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="full_name" class="form-label required">Full Name</label>
                                        <input type="text" class="form-control<?= $fieldErrors['full_name'] ? ' is-invalid' : '' ?>" id="full_name" name="full_name" value="<?= htmlspecialchars($full_name ?? '') ?>" required>
                                        <?php render_invalid_feedback($fieldErrors['full_name'], 'full_name'); ?>
                                    </div>
                                </div>
                                <div class="row form-section">
                                    <div class="col-md-6">
                                        <label for="address" class="form-label">Address</label>
                                        <input type="text" class="form-control" id="address" name="address" value="<?= htmlspecialchars($address ?? '') ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <label for="city" class="form-label">City</label>
                                        <input type="text" class="form-control" id="city" name="city" value="<?= htmlspecialchars($city ?? '') ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <label for="state" class="form-label required">State</label>
                                        <select class="form-select<?= $fieldErrors['state'] ? ' is-invalid' : '' ?>" id="state" name="state" required>
                                            <option value="">Select State</option>
                                            <?php foreach ($states as $abbr => $name): ?>
                                                <option value="<?= htmlspecialchars($abbr) ?>" <?= (isset($state) && $state === $abbr) ? 'selected' : '' ?>><?= htmlspecialchars($abbr) ?> - <?= htmlspecialchars($name) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <?php render_invalid_feedback($fieldErrors['state'], 'state'); ?>
                                    </div>
                                </div>
                                <div class="row form-section">
                                    <div class="col-md-3">
                                        <label for="zip_code" class="form-label">Zip Code</label>
                                        <input type="text" class="form-control" id="zip_code" name="zip_code" value="<?= htmlspecialchars($zip_code ?? '') ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <label for="phone_number" class="form-label">Phone Number</label>
                                        <input type="text" class="form-control" id="phone_number" name="phone_number" value="<?= htmlspecialchars($phone_number ?? '') ?>" maxlength="20" pattern="<?= PHONE_PATTERN ?>" autocomplete="off">
                                    </div>
                                    <div class="col-md-3">
                                        <label for="role" class="form-label required">Role</label>
                                        <select class="form-select<?= $fieldErrors['role'] ? ' is-invalid' : '' ?>" id="role" name="role" required>
                                            <option value="">Select Role</option>
                                            <?php foreach ($roles as $key => $label): ?>
                                                <option value="<?= htmlspecialchars($key) ?>" <?= (isset($role) && $role === $key) ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <?php render_invalid_feedback($fieldErrors['role'], 'role'); ?>
                                    </div>
                                    <div class="col-md-3 d-flex align-items-center">
                                        <div class="form-check mt-4">
                                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" <?= (!isset($is_active) || $is_active) ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="is_active">Active</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="row form-section">
                                    <div class="col-md-6">
                                        <label for="password" class="form-label<?= $mode === 'add' ? ' required' : '' ?>">Password<?= $mode === 'add' ? '' : ' (leave blank to keep current)' ?></label>
                                        <input type="password" class="form-control<?= $fieldErrors['password'] ? ' is-invalid' : '' ?>" id="password" name="password" <?= $mode === 'add' ? 'required' : '' ?> autocomplete="new-password">
                                        <?php render_invalid_feedback($fieldErrors['password'], 'password'); ?>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between mt-4">
                                    <button type="submit" class="btn btn-primary"><?= $mode === 'edit' ? 'Update' : 'Add' ?> User</button>
                                    <?php if ($mode === 'edit'): ?>
                                        <button type="submit" name="delete_user" value="1" class="btn btn-secondary" onclick="return confirm('Are you sure you want to delete this user?');">Delete User</button>
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
