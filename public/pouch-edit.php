<?php
session_start();

require_once __DIR__ . '/../database/Database.php';
require_once __DIR__ . '/../services/PouchService.php';
require_once __DIR__ . '/../includes/validation.php';
require_once __DIR__ . '/../includes/form_helpers.php';
require_once __DIR__ . '/../includes/csrf.php';

$db = new Database();
$pouchService = new PouchService($db);

$mode = $_GET['mode'] ?? 'add';
$id = $_GET['id'] ?? null;
$pouch_type = '';
$success = false;
$error = '';

$fieldErrors = [
    'pouch_type' => ''
];

function validate_pouch_fields($pouch_type) {
    if (!$pouch_type) return 'Please fill in all required fields.';
    if (strlen($pouch_type) > 100) return 'Pouch type must be 100 characters or less.';
    return '';
}

if ($mode === 'edit' && $id && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $pouch = $pouchService->findById((int)$id);
    if ($pouch) {
        $pouch_type = $pouch['pouch_type'] ?? '';
    } else {
        $error = 'Pouch not found.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_pouch']) && $mode === 'edit' && $id) {
    try {
        $pouchService->delete((int)$id);
        $success = 'deleted';
        $pouch_type = '';
    } catch (Exception $e) {
        $error = 'Error deleting pouch: ' . htmlspecialchars($e->getMessage());
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pouch_type = trim($_POST['pouch_type'] ?? '');
    // CSRF check
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid CSRF token.';
    } else {
        $error = validate_pouch_fields($pouch_type);
        if (!$error) {
            // Prevent duplicate pouch_type on add
            $existing = $pouchService->findByType($pouch_type);
            if (($mode === 'add' && $existing) ||
                ($mode === 'edit' && $existing && ($existing['id'] != $id))) {
                $error = 'A pouch with this type already exists.';
            } else {
                try {
                    if ($mode === 'edit' && $id) {
                        $pouchService->update((int)$id, $pouch_type);
                        $success = true;
                    } else {
                        $pouchService->create($pouch_type);
                        $success = true;
                        $pouch_type = '';
                    }
                } catch (Exception $e) {
                    $error = 'Error saving pouch: ' . htmlspecialchars($e->getMessage());
                }
            }
        }
        if ($error) {
            if (!$pouch_type) $fieldErrors['pouch_type'] = 'Please fill out this field.';
            elseif (strlen($pouch_type) > 100) $fieldErrors['pouch_type'] = 'Pouch type must be 100 characters or less.';
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
    <title>Pouch Type - DispatchBase</title>
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
                            <h4><?= $mode === 'edit' ? 'Edit' : 'Add' ?> Pouch Type</h4>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Main page content-->
            <div class="container-xl px-4 mt-n-custom-6">
                <div class="card mb-4 w-100">
                    <div class="card-header"><?= $mode === 'edit' ? 'Edit' : 'Add' ?> Pouch Type</div>
                    <div class="card-body">
                        <?php if ($success === 'deleted'): ?>
                            <div class="alert alert-success" role="alert">
                                Pouch deleted successfully!
                            </div>
                        <?php elseif ($success): ?>
                            <div class="alert alert-success" role="alert">
                                Pouch saved successfully!
                            </div>
                        <?php elseif ($error): ?>
                            <div class="alert alert-danger" role="alert">
                                <?= $error ?>
                            </div>
                        <?php endif; ?>
                        <?php if ($success !== 'deleted'): ?>
                            <form method="POST">
                                <?php csrf_token_field(); ?>
                                <div class="mb-3">
                                    <label for="pouch_type" class="form-label required">Pouch Type</label>
                                    <input type="text" class="form-control<?= $fieldErrors['pouch_type'] ? ' is-invalid' : '' ?>" id="pouch_type" name="pouch_type" value="<?= htmlspecialchars($pouch_type ?? '') ?>" required maxlength="100" aria-invalid="<?= $fieldErrors['pouch_type'] ? 'true' : 'false' ?>">
                                    <?php render_invalid_feedback($fieldErrors['pouch_type'], 'Please fill out this field.'); ?>
                                </div>
                                <div class="d-flex justify-content-between mt-4">
                                    <button type="submit" class="btn btn-primary"><?= $mode === 'edit' ? 'Update' : 'Add' ?> Pouch Type</button>
                                    <?php if ($mode === 'edit'): ?>
                                        <button type="submit" name="delete_pouch" value="1" class="btn btn-secondary" onclick="return confirm('Are you sure you want to delete this pouch type?');">Delete Pouch Type</button>
                                    <?php endif; ?>
                                </div>
                            </form>
                        <?php endif; ?>
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
