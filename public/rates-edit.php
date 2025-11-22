<?php
/**
 * Rates Edit Page (Transport Fees)
 *
 * Allows entering transport fee settings:
 * - Basic removal amount (covers first X miles)
 * - Miles covered by basic removal
 * - Per-mile overage charge (for miles over the covered miles)
 * - Assistant charge (per assistant, in addition to driver)
 *
 * This page implements the UI and server-side validation. Saving will call
 * RatesService if that class/file is added later; otherwise form behaves
 * locally (shows success and clears fields) so the UI can be tested.
 */

session_start();

require_once __DIR__ . '/../database/Database.php';
// Require the rates service and data files (now present in the project)
require_once __DIR__ . '/../services/RatesService.php';
require_once __DIR__ . '/../database/RatesData.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/validation.php';
require_once __DIR__ . '/../includes/form_helpers.php';
require_once __DIR__ . '/../database/CustomerData.php';

$db = new Database();
// Only instantiate if the class exists to avoid fatal errors until service is added
$ratesService = class_exists('RatesService') ? new RatesService($db) : null;
$customerRepo = new CustomerData($db);
$customers = $customerRepo->getAll();

// Initialize commonly-used variables to prevent undefined variable warnings
$status = '';
$error = '';
$missingRequired = false;
$existing_id = null;
$basic_fee = $included_miles = $extra_mile_rate = $assistant_fee = $effective_date = $notes = '';
$customer_id = ''; // track selected customer for save
$override_checked = isset($_POST['override_rates']) ? true : false;

// Simple GET endpoint to return rates for a specific customer id as JSON
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_rates') {
    $customer_id = $_GET['customer_id'] ?? '';
    header('Content-Type: application/json');
    if ($customer_id === '' || !ctype_digit(strval($customer_id))) {
        echo json_encode(['error' => 'Invalid customer_id']);
        exit;
    }
    $custNum = (int)$customer_id;

    // Prefer using the service layer if available
    $ratesRow = null;
    if ($ratesService && method_exists($ratesService, 'find')) {
        try {
            $ratesRow = $ratesService->find($custNum);
        } catch (Exception $e) {
            $ratesRow = null;
        }
    }

    // Fallback: direct DB query if service not available or failed
    if (empty($ratesRow)) {
        try {
            $stmt = $db->getPdo()->prepare("SELECT * FROM rates WHERE customer_id = ? ORDER BY id DESC LIMIT 1");
            $stmt->execute([$custNum]);
            $ratesRow = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $ratesRow = null;
        }
    }

    if (empty($ratesRow)) {
        echo json_encode(['error' => 'No rates found']);
    } else {
        echo json_encode(['data' => $ratesRow]);
    }
    exit;
}

// If not posting, attempt to load existing rates to pre-fill the form
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if ($ratesService && method_exists($ratesService, 'find')) {
        $existing = $ratesService->find();
        if ($existing) {
            $existing_id = isset($existing['id']) ? (int)$existing['id'] : null;
            $basic_fee = isset($existing['basic_fee']) ? (string)$existing['basic_fee'] : '';
            $included_miles = isset($existing['included_miles']) ? (string)$existing['included_miles'] : '';
            $extra_mile_rate = isset($existing['extra_mile_rate']) ? (string)$existing['extra_mile_rate'] : '';
            $assistant_fee = isset($existing['assistant_fee']) ? (string)$existing['assistant_fee'] : '';
            $effective_date = isset($existing['effective_date']) ? (string)$existing['effective_date'] : '';
            $notes = isset($existing['notes']) ? (string)$existing['notes'] : '';
            $customer_id = isset($existing['customer_id']) ? (string)$existing['customer_id'] : '';
        }
    }
}

/**
 * Validate rates form fields.
 * Must return an empty string on success, or an error message on failure.
 * Per project rules, if any required field is missing, return the generic
 * message 'Please fill in all required fields.' so it can be displayed above the form.
 *
 * @return string
 */
function validate_rates_fields($basicFee, $includedMiles, $extraMileRate, $assistantFee, $effectiveDate) {
    $basicFee = trim($basicFee);
    $includedMiles = trim($includedMiles);
    $extraMileRate = trim($extraMileRate);
    $assistantFee = trim($assistantFee);
    $effectiveDate = trim($effectiveDate);

    if ($basicFee === '' || $includedMiles === '' || $extraMileRate === '' || $assistantFee === '' || $effectiveDate === '') {
        return 'Please fill in all required fields.';
    }

    if (!is_numeric($basicFee) || floatval($basicFee) < 0) {
        return 'Basic fee must be a non-negative number.';
    }
    if (!ctype_digit((string)$includedMiles) || intval($includedMiles) < 0) {
        return 'Included miles must be a non-negative integer.';
    }
    if (!is_numeric($extraMileRate) || floatval($extraMileRate) < 0) {
        return 'Extra mile rate must be a non-negative number.';
    }
    if (!is_numeric($assistantFee) || floatval($assistantFee) < 0) {
        return 'Assistant fee must be a non-negative number.';
    }
    // Validate date format YYYY-MM-DD
    $d = DateTime::createFromFormat('Y-m-d', $effectiveDate);
    if (!($d && $d->format('Y-m-d') === $effectiveDate)) {
        return 'Effective date must be a valid date (Y-m-d).';
    }

    return '';
}

/**
 * Sanitize and trim rates fields from input array
 */
function sanitize_and_trim_rates_fields($input) {
    return [
        'basic_fee' => trim($input['basic_fee'] ?? ''),
        'included_miles' => trim($input['included_miles'] ?? ''),
        'extra_mile_rate' => trim($input['extra_mile_rate'] ?? ''),
        'assistant_fee' => trim($input['assistant_fee'] ?? ''),
        'effective_date' => trim($input['effective_date'] ?? ''),
        'notes' => trim($input['notes'] ?? ''),
        'customer_id' => trim($input['customer_id'] ?? '')
    ];
}

// Handle POST - delete, then save
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Load existing to know id for delete/update
    $existing = ($ratesService && method_exists($ratesService, 'find')) ? $ratesService->find() : null;
    $existing_id = $existing['id'] ?? null;

    // Handle delete action
    if (isset($_POST['delete_rates']) && $existing_id && $ratesService && method_exists($ratesService, 'delete')) {
        try {
            $ratesService->delete((int)$existing_id);
            $status = 'deleted';
            // clear fields
            $basic_fee = $included_miles = $extra_mile_rate = $assistant_fee = $effective_date = $notes = '';
            $customer_id = '';
            $existing_id = null;
        } catch (Exception $e) {
            $error = 'Error deleting rates: ' . htmlspecialchars($e->getMessage());
        }
    } else {
        // Sanitize and validate input for save
        $fields = sanitize_and_trim_rates_fields($_POST);
        extract($fields);

        // Ensure $customer_id variable exists for template usage
        $customer_id = $customer_id ?? '';

        $error = validate_rates_fields($basic_fee, $included_miles, $extra_mile_rate, $assistant_fee, $effective_date);
        if ($error === 'Please fill in all required fields.') {
            $missingRequired = true;
            // Avoid duplicate top alerts: the generic required-fields message must always be shown via $missingRequired;
            // clear $error so the same message doesn't show again in the generic $error banner.
            $error = '';
        }

        if (!$error && !$missingRequired) {
            $normalized = [
                'basic_fee' => floatval($basic_fee),
                'included_miles' => intval($included_miles),
                'extra_mile_rate' => floatval($extra_mile_rate),
                'assistant_fee' => floatval($assistant_fee),
                'effective_date' => $effective_date,
                'notes' => $notes,
                // Persist customer_id if provided and numeric; otherwise null
                'customer_id' => ($customer_id !== '' && ctype_digit((string)$customer_id)) ? (int)$customer_id : null
            ];

            try {
                if ($ratesService && method_exists($ratesService, 'saveRates')) {
                    $savedId = $ratesService->saveRates($normalized);
                    if ($savedId) {
                        $status = $existing_id ? 'updated' : 'added';
                        $existing_id = $savedId;
                    }
                } else {
                    // If service not present, behave as UI-only save
                    $status = 'added';
                }
                // Clear form fields after successful add/update per project rules
                $basic_fee = $included_miles = $extra_mile_rate = $assistant_fee = $effective_date = $notes = '';
                $customer_id = '';
            } catch (Exception $e) {
                $error = 'Error saving rates: ' . htmlspecialchars($e->getMessage());
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
    <title>Transport Fees - DispatchBase</title>
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
                        <div class="card-header">Manage Transport Fees</div>
                        <div class="card-body">
                            <?php if ($status === 'added'): ?>
                                <div class="alert alert-success" role="alert">Rates saved successfully!</div>
                            <?php elseif ($status === 'updated'): ?>
                                <div class="alert alert-success" role="alert">Rates updated successfully!</div>
                            <?php elseif ($status === 'deleted'): ?>
                                <div class="alert alert-success" role="alert">Rates deleted successfully!</div>
                            <?php elseif ($error && $error !== 'Please fill in all required fields.'): ?>
                                <div class="alert alert-danger" role="alert"><?= htmlspecialchars($error) ?></div>
                            <?php endif; ?>

                            <?php if ($missingRequired): ?>
                                <div class="alert alert-danger" role="alert">Please fill in all required fields.</div>
                            <?php endif; ?>

                            <form method="POST" novalidate>
                                <?php csrf_token_field(); ?>

                                <!-- Customer selector row: placed before other fields -->
                                <div class="row form-section mb-3">
                                    <div class="col-md-10">
                                        <label for="customer_id" class="form-label">Select Customer</label>
                                        <select id="customer_id" name="customer_id" class="form-select">
                                            <option value="" selected>Select</option>
                                            <?php foreach ($customers as $c): ?>
                                                <option value="<?= htmlspecialchars($c['id'] ?? '') ?>" <?= ($customer_id !== '' && $customer_id == ($c['id'] ?? '')) ? 'selected' : '' ?>><?= htmlspecialchars($c['company_name'] ?? '') ?> (<?= htmlspecialchars($c['id'] ?? '') ?>)</option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-2 d-flex align-items-end justify-content-end">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value="1" id="override_rates" name="override_rates" <?php if ($override_checked) echo 'checked'; ?>>
                                            <label class="form-check-label" for="override_rates">Allow override base rates</label>
                                        </div>
                                    </div>
                                </div>

                                <div class="row form-section">
                                    <div class="col-md-6">
                                        <label for="basic_fee" class="form-label required">Basic Fee (covers first X miles)</label>
                                        <input type="number" step="0.01" min="0" class="form-control<?= ($error && ($basic_fee === '' || !is_numeric($basic_fee))) ? ' is-invalid' : '' ?>" id="basic_fee" name="basic_fee" value="<?= htmlspecialchars($basic_fee ?? '') ?>" required>
                                        <?php render_invalid_feedback('Please fill out this field.', $missingRequired && $basic_fee === ''); ?>
                                        <?php render_invalid_feedback('Enter a non-negative number for basic fee.', $error && $basic_fee !== '' && !is_numeric($basic_fee)); ?>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="included_miles" class="form-label required">Miles Covered by Basic Fee</label>
                                        <input type="number" step="1" min="0" class="form-control<?= ($error && ($included_miles === '' || !ctype_digit($included_miles))) ? ' is-invalid' : '' ?>" id="included_miles" name="included_miles" value="<?= htmlspecialchars($included_miles ?? '') ?>" required>
                                        <?php render_invalid_feedback('Please fill out this field.', $missingRequired && $included_miles === ''); ?>
                                        <?php render_invalid_feedback('Enter a non-negative integer for included miles.', $error && $included_miles !== '' && !ctype_digit($included_miles)); ?>
                                    </div>
                                </div>

                                <div class="row form-section">
                                    <div class="col-md-6">
                                        <label for="extra_mile_rate" class="form-label required">Extra Mile Rate (for miles over basic coverage)</label>
                                        <input type="number" step="0.01" min="0" class="form-control<?= ($error && ($extra_mile_rate === '' || !is_numeric($extra_mile_rate))) ? ' is-invalid' : '' ?>" id="extra_mile_rate" name="extra_mile_rate" value="<?= htmlspecialchars($extra_mile_rate ?? '') ?>" required>
                                        <?php render_invalid_feedback('Please fill out this field.', $missingRequired && $extra_mile_rate === ''); ?>
                                        <?php render_invalid_feedback('Enter a non-negative number for extra mile rate.', $error && $extra_mile_rate !== '' && !is_numeric($extra_mile_rate)); ?>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="assistant_fee" class="form-label required">Assistant Fee (per assistant, in addition to driver)</label>
                                        <input type="number" step="0.01" min="0" class="form-control<?= ($error && ($assistant_fee === '' || !is_numeric($assistant_fee))) ? ' is-invalid' : '' ?>" id="assistant_fee" name="assistant_fee" value="<?= htmlspecialchars($assistant_fee ?? '') ?>" required>
                                        <?php render_invalid_feedback('Please fill out this field.', $missingRequired && $assistant_fee === ''); ?>
                                        <?php render_invalid_feedback('Enter a non-negative number for assistant fee.', $error && $assistant_fee !== '' && !is_numeric($assistant_fee)); ?>
                                    </div>
                                </div>

                                <div class="row form-section">
                                    <div class="col-md-6">
                                        <label for="effective_date" class="form-label required">Effective Date</label>
                                        <input type="date" class="form-control<?= ($error && ($effective_date === '' || !DateTime::createFromFormat('Y-m-d', $effective_date))) ? ' is-invalid' : '' ?>" id="effective_date" name="effective_date" value="<?= htmlspecialchars($effective_date ?? '') ?>" required>
                                        <?php render_invalid_feedback('Please fill out this field.', $missingRequired && $effective_date === ''); ?>
                                        <?php render_invalid_feedback('Effective date must be a valid date (Y-m-d).', $error && $effective_date !== '' && !DateTime::createFromFormat('Y-m-d', $effective_date)); ?>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="notes" class="form-label">Notes</label>
                                        <textarea class="form-control" id="notes" name="notes" rows="3"><?= htmlspecialchars($notes ?? '') ?></textarea>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-between mt-4">
                                    <button type="submit" class="btn btn-primary">Save Rates</button>
                                    <?php if ($existing_id): ?>
                                        <button type="submit" name="delete_rates" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete these rates?');">Delete Rates</button>
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

<!-- Get Rates button handler: fetch rates JSON and populate fields -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    var sel = document.getElementById('customer_id');
    var overrideCheckbox = document.getElementById('override_rates');

    function setFieldsReadOnly(makeReadOnly) {
        var ids = ['basic_fee','included_miles','extra_mile_rate','assistant_fee','effective_date','notes'];
        ids.forEach(function(id){
            var el = document.getElementById(id);
            if (!el) return;
            if (makeReadOnly) {
                el.setAttribute('readonly', 'readonly');
                el.classList.add('bg-light');
            } else {
                el.removeAttribute('readonly');
                el.classList.remove('bg-light');
            }
        });
    }

    // Toggle readonly when the checkbox is changed
    if (overrideCheckbox) {
        overrideCheckbox.addEventListener('change', function() {
            if (overrideCheckbox.checked) {
                setFieldsReadOnly(false);
            } else {
                var first = document.getElementById('basic_fee');
                if (first && first.value !== '') {
                    setFieldsReadOnly(true);
                }
            }
        });
    }

    function clearRateFields() {
        var ids = ['basic_fee','included_miles','extra_mile_rate','assistant_fee','effective_date','notes'];
        ids.forEach(function(id){
            var el = document.getElementById(id);
            if (!el) return;
            if (el.tagName === 'INPUT' || el.tagName === 'TEXTAREA' || el.tagName === 'SELECT') el.value = '';
            el.classList && el.classList.remove('is-invalid');
        });
    }

    function showInfoMessage(msg) {
        var cardBody = document.querySelector('.card-body');
        if (!cardBody) return;
        var prev = document.getElementById('rates_info');
        if (prev) prev.remove();
        var info = document.createElement('div');
        info.id = 'rates_info';
        info.className = 'alert alert-info';
        info.role = 'alert';
        info.textContent = msg;
        cardBody.insertBefore(info, cardBody.firstChild);
        setTimeout(function(){ var el = document.getElementById('rates_info'); if (el) el.remove(); }, 6000);
    }

    function fetchRatesForCustomer(cust) {
        if (!cust) {
            // Match previous Get Rates button behavior: if no customer selected, show validation and don't fetch.
            if (sel) sel.classList.add('is-invalid');
            return;
        }

        if (sel) sel.classList.remove('is-invalid');
        fetch('rates-edit.php?action=get_rates&customer_id=' + encodeURIComponent(cust))
            .then(function(resp) { return resp.json(); })
            .then(function(json) {
                if (json.error) {
                    if (json.error === 'No rates found') {
                        clearRateFields();
                        setFieldsReadOnly(false);
                        var first = document.getElementById('basic_fee');
                        if (first) first.focus();
                        showInfoMessage('No existing rates found for the selected customer. Fields have been cleared so you may add new rates.');
                        return;
                    }
                    alert('Error: ' + json.error);
                    return;
                }

                var data = json.data || {};
                var safeSet = function(id, val) { var el = document.getElementById(id); if (el) el.value = (val !== undefined && val !== null) ? val : ''; };
                safeSet('basic_fee', data.basic_fee ?? '');
                safeSet('included_miles', data.included_miles ?? '');
                safeSet('extra_mile_rate', data.extra_mile_rate ?? '');
                safeSet('assistant_fee', data.assistant_fee ?? '');
                safeSet('effective_date', data.effective_date ?? '');
                safeSet('notes', data.notes ?? '');

                ['basic_fee','included_miles','extra_mile_rate','assistant_fee','effective_date','notes'].forEach(function(id){
                    var el = document.getElementById(id);
                    if (el && el.classList) el.classList.remove('is-invalid');
                });

                if (overrideCheckbox && !overrideCheckbox.checked) {
                    setFieldsReadOnly(true);
                } else {
                    setFieldsReadOnly(false);
                }
            }).catch(function(err){
                console.error(err);
                alert('Failed to retrieve rates for the selected customer.');
            });
    }

    // Load rates on customer change
    if (sel) {
        sel.addEventListener('change', function() { fetchRatesForCustomer(sel.value); });
        // Initial load if a customer is pre-selected
        if (sel.value) fetchRatesForCustomer(sel.value);
    }

    // On initial load, if override checkbox is unchecked and fields already have values, lock them
    try {
        if (overrideCheckbox && !overrideCheckbox.checked) {
            var first = document.getElementById('basic_fee');
            if (first && first.value !== '') setFieldsReadOnly(true);
        }
    } catch (e) {}
});
</script>

</body>
</html>
