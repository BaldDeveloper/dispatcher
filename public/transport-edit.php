<?php
// transport-edit.php (refactored to match customer-edit.php formatting and validation)
session_start();
require_once __DIR__ . '/../database/Database.php';
require_once __DIR__ . '/../services/TransportService.php';
require_once __DIR__ . '/../services/LocationService.php';
require_once __DIR__ . '/../services/CoronerService.php';
require_once __DIR__ . '/../services/PouchService.php';
require_once __DIR__ . '/../services/UserService.php';
require_once __DIR__ . '/../services/EmployeesService.php';
require_once __DIR__ . '/../database/TransportData.php';
require_once __DIR__ . '/../database/CustomerData.php';
require_once __DIR__ . '/../database/BaseRatesData.php';
require_once __DIR__ . '/../database/DecedentData.php';
require_once __DIR__ . '/../includes/validation.php';
require_once __DIR__ . '/../includes/form_helpers.php';
require_once __DIR__ . '/../includes/csrf.php';

$db = new Database();
$transportService = new TransportService($db);
$locationService = new LocationService($db);
$coronerService = new CoronerService($db);
$pouchService = new PouchService($db);
$userService = new UserService($db);
$employeesService = new EmployeesService($db);
$transportRepo = new TransportData($db);
$customerRepo = new CustomerData($db);
$chargesData = new BaseRatesData($db);
$decedentRepo = new DecedentData($db);

$mode = $_GET['mode'] ?? 'add';
$id = isset($_GET['id']) ? (int)($_GET['id']) : null;
$status = '';
$error = '';

// Validate transport_id for edit mode
if ($mode === 'edit') {
    if (empty($id) || !is_int($id) || $id <= 0) {
        die('Invalid or missing id.');
    }
}

// Default form field values
$customerId = '';
$firmDate = '';
$accountType = '';
$originLocation = '';
$destinationLocation = '';
$originLocationId = '';
$destinationLocationId = '';
$permitNumber = '';
$tagNumber = '';
$decedentFirstName = '';
$decedentMiddleName = '';
$decedentLastName = '';
$decedentEthnicity = '';
$decedentGender = '';
$coronerName = '';
$pouchType = '';
$primaryTransporter = '';
$assistantTransporter = '';

// Time fields (datetime-local)
$callTime = '';
$arrivalTime = '';
$departureTime = '';
$deliveryTime = '';
$timeErrors = [];

// Charges
$removal_charge = '';
$pouch_charge = '';
$transport_fees = '';
$wait_charge = '';
$mileage_fees = '';
$other_charge_1 = '';
$other_charge_1_description = '';
$other_charge_2 = '';
$other_charge_2_description = '';
$other_charge_3 = '';
$other_charge_3_description = '';
$other_charge_4 = '';
$other_charge_4_description = '';
$total_charge = '';
$chargeErrors = [];

// Additional fields
$transitPermitNumber = '';
$mileage = '';
$mileage_rate = '';
$mileage_total_charge = '';

// Per-field error flags
$fieldErrors = [
    'customer_id' => false,
    'firm_date' => false,
    'account_type' => false,
    'origin_location' => false,
    'destination_location' => false,
    'coroner' => false,
    'pouch_type' => false,
    // time/date
    'call_time' => false,
    'arrival_time' => false,
    'departure_time' => false,
    'delivery_time' => false,
    // transporters
    'primary_transporter' => false,
    'assistant_transporter' => false,
];

// Helper: sanitize and trim input fields
function sanitize_and_trim_transport_fields($input) {
    return [
        'customer_id' => trim($input['customer_id'] ?? ''),
        'firm_date' => trim($input['firm_date'] ?? ''),
        'account_type' => trim($input['account_type'] ?? ''),
        'origin_location' => trim($input['origin_location'] ?? ''),
        'destination_location' => trim($input['destination_location'] ?? ''),
        'permit_number' => trim($input['permit_number'] ?? ''),
        'tag_number' => trim($input['tag_number'] ?? ''),
        'first_name' => trim($input['first_name'] ?? ''),
        'middle_name' => trim($input['middle_name'] ?? ''),
        'last_name' => trim($input['last_name'] ?? ''),
        'ethnicity' => trim($input['ethnicity'] ?? ''),
        'gender' => trim($input['gender'] ?? ''),
        'coroner' => trim($input['coroner'] ?? ''),
        'pouch_type' => trim($input['pouch_type'] ?? ''),
        'transit_permit_number' => trim($input['transit_permit_number'] ?? ''),
        'primary_transporter' => trim($input['primary_transporter'] ?? ''),
        'assistant_transporter' => trim($input['assistant_transporter'] ?? ''),
        'call_time' => trim($input['call_time'] ?? ''),
        'arrival_time' => trim($input['arrival_time'] ?? ''),
        'departure_time' => trim($input['departure_time'] ?? ''),
        'delivery_time' => trim($input['delivery_time'] ?? ''),
        'mileage' => trim($input['mileage'] ?? ''),
        'mileage_rate' => trim($input['mileage_rate'] ?? ''),
        'mileage_total_charge' => trim($input['mileage_total_charge'] ?? ''),
        'removal_charge' => trim($input['removal_charge'] ?? ''),
        'pouch_charge' => trim($input['pouch_charge'] ?? ''),
        'transport_fees' => trim($input['transport_fees'] ?? ''),
        'wait_charge' => trim($input['wait_charge'] ?? ''),
        'mileage_fees' => trim($input['mileage_fees'] ?? ''),
        'other_charge_1' => trim($input['other_charge_1'] ?? ''),
        'other_charge_2' => trim($input['other_charge_2'] ?? ''),
        'other_charge_3' => trim($input['other_charge_3'] ?? ''),
        'other_charge_4' => trim($input['other_charge_4'] ?? ''),
        'other_charge_1_description' => trim($input['other_charge_1_description'] ?? ''),
        'other_charge_2_description' => trim($input['other_charge_2_description'] ?? ''),
        'other_charge_3_description' => trim($input['other_charge_3_description'] ?? ''),
        'other_charge_4_description' => trim($input['other_charge_4_description'] ?? ''),
        'total_charge' => trim($input['total_charge'] ?? ''),
    ];
}

// Convert HTML datetime-local (e.g. 2025-11-22T14:30) to MySQL DATETIME (YYYY-MM-DD HH:MM:SS)
function datetime_local_to_mysql(string $val): string {
    $val = trim($val);
    if ($val === '') return '';
    // Try parsing common formats: 'Y-m-d\TH:i' from datetime-local or full ISO
    $dt = DateTime::createFromFormat('Y-m-d\TH:i', $val);
    if (!$dt) {
        // fallback to generic parse
        try {
            $dt = new DateTime($val);
        } catch (Exception $e) {
            return $val; // return as-is if parsing fails
        }
    }
    return $dt->format('Y-m-d H:i:s');
}

// Convert MySQL DATETIME (YYYY-MM-DD HH:MM:SS) to HTML datetime-local format (Y-m-d\TH:i)
function mysql_to_datetime_local(string $val): string {
    $val = trim($val);
    if ($val === '') return '';
    // Replace T with space and try parse
    $normalized = str_replace('T', ' ', $val);
    try {
        $dt = new DateTime($normalized);
        return $dt->format('Y-m-d\TH:i');
    } catch (Exception $e) {
        return $val;
    }
}

// Data sources for dropdowns
$allLocations = $locationService->getAll();
$originLocations = array_filter($allLocations, function($loc) {
    return ($loc['location_type'] ?? '') === 'origin' || ($loc['location_type'] ?? '') === 'both';
});
$destinationLocations = array_filter($allLocations, function($loc) {
    return ($loc['location_type'] ?? '') === 'destination' || ($loc['location_type'] ?? '') === 'both';
});
$coroners = $coronerService->getAll();
$pouchTypes = $pouchService->getAll();
$allEmployees = $employeesService->getAll();
$drivers = array_values(array_filter($allEmployees, function($e) {
    return isset($e['job_title']) && strtolower(trim($e['job_title'])) === 'driver';
}));

// Load existing transport for edit (non-POST)
if ($mode === 'edit' && $id && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $transport = $transportService->findById($id);
    $charges = $chargesData->findByTransportId($id) ?: [
        'removal_charge' => '',
        'pouch_charge' => '',
        'transport_fees' => '',
        'wait_charge' => '',
        'mileage_fees' => '',
        'other_charge_1' => '',
        'other_charge_1_description' => '',
        'other_charge_2' => '',
        'other_charge_2_description' => '',
        'other_charge_3' => '',
        'other_charge_3_description' => '',
        'other_charge_4' => '',
        'other_charge_4_description' => '',
        'total_charge' => ''
    ];

    if ($transport) {
        $customerId = $transport['customer_id'] ?? '';
        // Convert DB DATETIME to HTML datetime-local for the input value
        $firmDate = mysql_to_datetime_local($transport['firm_date'] ?? '');
        $accountType = $transport['account_type'] ?? '';
        $originLocation = $transport['origin_location'] ?? '';
        $destinationLocation = $transport['destination_location'] ?? '';
        $coronerName = $transport['coroner_name'] ?? '';
        $permitNumber = $transport['permit_number'] ?? '';
        $tagNumber = $transport['tag_number'] ?? '';
        $pouchType = $transport['pouch_type'] ?? '';
        $transitPermitNumber = $transport['transit_permit_number'] ?? '';
        $primaryTransporter = $transport['primary_transporter'] ?? '';
        $assistantTransporter = $transport['assistant_transporter'] ?? '';
        $callTime = $transport['call_time'] ?? '';
        $arrivalTime = $transport['arrival_time'] ?? '';
        $departureTime = $transport['departure_time'] ?? '';
        $deliveryTime = $transport['delivery_time'] ?? '';
        $mileage = $transport['mileage'] ?? '';
        $mileage_rate = $transport['mileage_rate'] ?? '';
        $mileage_total_charge = $transport['mileage_total_charge'] ?? '';
        $originLocationId = $transport['origin_location'] ?? '';
        $destinationLocationId = $transport['destination_location'] ?? '';

        //$decedent = $db->query("SELECT * FROM decedents WHERE transport_id = ?", [$id]);
        // Keep using repository method name for now; map result keys to prefer 'id' in UI
        $decedent = $decedentRepo->findByTransportId((int)$id);
        if (!empty($decedent)) {
            $decedentFirstName = $decedent['first_name'] ?? '';
            $decedentMiddleName = $decedent['middle_name'] ?? '';
            $decedentLastName = $decedent['last_name'] ?? '';
            $decedentEthnicity = $decedent['ethnicity'] ?? '';
            $decedentGender = $decedent['gender'] ?? '';
        } else {
            $decedentFirstName = $transport['decedent_first_name'] ?? '';
            $decedentMiddleName = $transport['decedent_middle_name'] ?? '';
            $decedentLastName = $transport['decedent_last_name'] ?? '';
            $decedentEthnicity = $transport['decedent_ethnicity'] ?? '';
            $decedentGender = $transport['decedent_gender'] ?? '';
        }
    }

    // Charges for form
    $removal_charge = $charges['removal_charge'] ?? '';
    $pouch_charge = $charges['pouch_charge'] ?? '';
    $transport_fees = $charges['transport_fees'] ?? '';
    $wait_charge = $charges['wait_charge'] ?? '';
    $mileage_fees = $charges['mileage_fees'] ?? '';
    $other_charge_1 = $charges['other_charge_1'] ?? '';
    $other_charge_1_description = $charges['other_charge_1_description'] ?? '';
    $other_charge_2 = $charges['other_charge_2'] ?? '';
    $other_charge_2_description = $charges['other_charge_2_description'] ?? '';
    $other_charge_3 = $charges['other_charge_3'] ?? '';
    $other_charge_3_description = $charges['other_charge_3_description'] ?? '';
    $other_charge_4 = $charges['other_charge_4'] ?? '';
    $other_charge_4_description = $charges['other_charge_4_description'] ?? '';
    $total_charge = $charges['total_charge'] ?? '';
}

// CSRF check for POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedToken = $_POST['csrf_token'] ?? '';
    if (!function_exists('validate_csrf_token') || !validate_csrf_token($postedToken)) {
        $error = 'Invalid request (CSRF token mismatch).';
    }
}

// Handle delete (only if CSRF ok)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error && isset($_POST['delete_transport']) && $mode === 'edit' && $id) {
    try {
        $decedentRepo->deleteByTransportId((int)$id);
        $chargesData->deleteByTransportId((int)$id);
        $transportRepo->delete((int)$id);
        $status = 'deleted';
        // Clear fields after delete
        $customerId = $firmDate = $accountType = $originLocation = $destinationLocation = $permitNumber = $tagNumber = '';
        $decedentFirstName = $decedentMiddleName = $decedentLastName = $decedentEthnicity = $decedentGender = '';
        $callTime = $arrivalTime = $departureTime = $deliveryTime = '';
        $mileage = $mileage_rate = $mileage_total_charge = '';
        $originLocationId = $destinationLocationId = '';
    } catch (Exception $e) {
        $error = 'Error deleting dispatch: ' . htmlspecialchars($e->getMessage());
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {
    // Sanitize
    $fields = sanitize_and_trim_transport_fields($_POST);
    // Map sanitized to local vars
    $customerId = $fields['customer_id'] ?: $customerId;
    // Keep firm date as HTML datetime-local string for the form
    $firmDate = $fields['firm_date'] ?: $firmDate;
    $accountType = $fields['account_type'] ?: $accountType;
    $decedentFirstName = $fields['first_name'] ?: $decedentFirstName;
    $decedentMiddleName = $fields['middle_name'] ?: $decedentMiddleName;
    $decedentLastName = $fields['last_name'] ?: $decedentLastName;
    $decedentEthnicity = $fields['ethnicity'] ?: $decedentEthnicity;
    $decedentGender = $fields['gender'] ?: $decedentGender;
    $originLocation = $fields['origin_location'] ?: $originLocation;
    $destinationLocation = $fields['destination_location'] ?: $destinationLocation;
    $originLocationId = $originLocation;
    $destinationLocationId = $destinationLocation;
    $coronerName = $fields['coroner'] ?: $coronerName;
    // Resolve coroner name by id
    if (!empty($coronerName) && is_array($coroners)) {
        foreach ($coroners as $coroner) {
            $coronerId = $coroner['id'] ?? null;
            if ($coronerId == $fields['coroner']) {
                $coronerName = $coroner['coroner_name'];
                break;
            }
        }
    }
    $pouchType = $fields['pouch_type'] ?: $pouchType;
    $transitPermitNumber = $fields['transit_permit_number'] ?: $transitPermitNumber;
    $tagNumber = $fields['tag_number'] ?: $tagNumber;
    $primaryTransporter = $fields['primary_transporter'] ?: $primaryTransporter;
    $assistantTransporter = $fields['assistant_transporter'] ?: $assistantTransporter;
    $callTime = $fields['call_time'] ?: $callTime;
    $arrivalTime = $fields['arrival_time'] ?: $arrivalTime;
    $departureTime = $fields['departure_time'] ?: $departureTime;
    $deliveryTime = $fields['delivery_time'] ?: $deliveryTime;
    $mileage = $fields['mileage'] ?: $mileage;
    $mileage_rate = $fields['mileage_rate'] ?: $mileage_rate;
    $mileage_total_charge = $fields['mileage_total_charge'] ?: $mileage_total_charge;

    // Charges
    $removal_charge = $fields['removal_charge'] ?: $removal_charge;
    $pouch_charge = $fields['pouch_charge'] ?: $pouch_charge;
    $transport_fees = $fields['transport_fees'] ?: $transport_fees;
    $wait_charge = $fields['wait_charge'] ?: $wait_charge;
    $mileage_fees = $fields['mileage_fees'] ?: $mileage_fees;
    $other_charge_1 = $fields['other_charge_1'] ?: $other_charge_1;
    $other_charge_2 = $fields['other_charge_2'] ?: $other_charge_2;
    $other_charge_3 = $fields['other_charge_3'] ?: $other_charge_3;
    $other_charge_4 = $fields['other_charge_4'] ?: $other_charge_4;
    $other_charge_1_description = $fields['other_charge_1_description'] ?: $other_charge_1_description;
    $other_charge_2_description = $fields['other_charge_2_description'] ?: $other_charge_2_description;
    $other_charge_3_description = $fields['other_charge_3_description'] ?: $other_charge_3_description;
    $other_charge_4_description = $fields['other_charge_4_description'] ?: $other_charge_4_description;
    $total_charge = $fields['total_charge'] ?: $total_charge;

    // Validate fields
    $error = validate_transport_fields($fields, $fieldErrors, $timeErrors);
    $chargeErrors = validate_transport_charges_fields($_POST);
    // If any charge errors, set generic error but still show field-specific feedback
    if (!empty($chargeErrors)) {
        $error = $error ?: 'Please correct the highlighted fields.';
    }

    // Server-side validation: assistant cannot be same as primary (assistant optional)
    if ($primaryTransporter !== '' && $assistantTransporter !== '' && $primaryTransporter === $assistantTransporter) {
        $fieldErrors['primary_transporter'] = true;
        $fieldErrors['assistant_transporter'] = true;
        $error = $error ?: 'Assistant transporter cannot be the same as primary transporter.';
    }

    if (!$error) {
        // Ensure strings for time fields (TransportData expects string types)
        $callTimeStr = (string)$callTime;
        $arrivalTimeStr = (string)$arrivalTime;
        $departureTimeStr = (string)$departureTime;
        $deliveryTimeStr = (string)$deliveryTime;

        // Normalize optional transporter IDs
        $primaryTransporterId = is_numeric($primaryTransporter) ? (int)$primaryTransporter : null;
        $assistantTransporterId = is_numeric($assistantTransporter) ? (int)$assistantTransporter : null;

        // Helper to parse float values (empty => 0.0)
        $toFloat = function($v) {
            $s = str_replace(',', '.', trim((string)$v));
            return $s === '' ? 0.0 : (float)$s;
        };

        // Convert firm date from HTML datetime-local to MySQL DATETIME before DB operations
        $firmDateDb = datetime_local_to_mysql((string)$firmDate);

        if ($mode === 'add') {
            try {
                // Create transport using service/repo signature
                $transportId = $transportService->create(
                    (int)$customerId,
                    $firmDateDb,
                    $accountType,
                    (string)$originLocationId,
                    (string)$destinationLocationId,
                    $coronerName,
                    $pouchType,
                    $transitPermitNumber,
                    $tagNumber,
                    $callTimeStr,
                    $arrivalTimeStr,
                    $departureTimeStr,
                    $deliveryTimeStr,
                    $primaryTransporterId,
                    $assistantTransporterId,
                    $mileage !== '' ? (float)$mileage : null,
                    $mileage_rate !== '' ? (float)$mileage_rate : null,
                    $mileage_total_charge !== '' ? (float)$mileage_total_charge : null
                );
                $status = 'added';
                // Insert charges via BaseRatesData
                $chargesData->create(
                    (int)$transportId,
                    $toFloat($removal_charge),
                    $toFloat($pouch_charge),
                    $toFloat($transport_fees),
                    $toFloat($wait_charge),
                    $toFloat($mileage_fees),
                    $toFloat($other_charge_1),
                    $other_charge_1_description !== '' ? $other_charge_1_description : null,
                    $toFloat($other_charge_2),
                    $other_charge_2_description !== '' ? $other_charge_2_description : null,
                    $toFloat($other_charge_3),
                    $other_charge_3_description !== '' ? $other_charge_3_description : null,
                    $toFloat($other_charge_4),
                    $other_charge_4_description !== '' ? $other_charge_4_description : null,
                    $toFloat($total_charge)
                );
                // Insert decedent
                $decedentRepo->insertByTransportId(
                    (int)$transportId,
                    $decedentFirstName,
                    $decedentLastName,
                    $decedentEthnicity,
                    $decedentGender
                );
                // Clear form for next entry
                $customerId = $firmDate = $accountType = $originLocation = $destinationLocation = $permitNumber = $tagNumber = '';
                $decedentFirstName = $decedentMiddleName = $decedentLastName = $decedentEthnicity = $decedentGender = '';
                $callTime = $arrivalTime = $departureTime = $deliveryTime = '';
                $mileage = $mileage_rate = $mileage_total_charge = '';
                $originLocationId = $destinationLocationId = '';
                $removal_charge = $pouch_charge = $transport_fees = $wait_charge = $mileage_fees = '';
                $other_charge_1 = $other_charge_2 = $other_charge_3 = $other_charge_4 = '';
                $other_charge_1_description = $other_charge_2_description = $other_charge_3_description = $other_charge_4_description = '';
                $total_charge = '';
            } catch (Exception $e) {
                $error = 'Error adding transport: ' . htmlspecialchars($e->getMessage());
            }
        } else {
            try {
                // Update transport
                $transportService->update(
                    (int)$id,
                    (int)$customerId,
                    $firmDateDb,
                    $accountType,
                    (string)$originLocationId,
                    (string)$destinationLocationId,
                    $coronerName,
                    $pouchType,
                    $transitPermitNumber,
                    $tagNumber,
                    $callTimeStr,
                    $arrivalTimeStr,
                    $departureTimeStr,
                    $deliveryTimeStr,
                    $primaryTransporterId,
                    $assistantTransporterId,
                    $mileage !== '' ? (float)$mileage : null,
                    $mileage_rate !== '' ? (float)$mileage_rate : null,
                    $mileage_total_charge !== '' ? (float)$mileage_total_charge : null
                );
                $status = 'updated';
                // Update or create charges
                $existingRates = $chargesData->findByTransportId((int)$id);
                if ($existingRates) {
                    $chargesData->update(
                        (int)$existingRates['id'],
                        (int)$id,
                        $toFloat($removal_charge),
                        $toFloat($pouch_charge),
                        $toFloat($transport_fees),
                        $toFloat($wait_charge),
                        $toFloat($mileage_fees),
                        $toFloat($other_charge_1),
                        $other_charge_1_description !== '' ? $other_charge_1_description : null,
                        $toFloat($other_charge_2),
                        $other_charge_2_description !== '' ? $other_charge_2_description : null,
                        $toFloat($other_charge_3),
                        $other_charge_3_description !== '' ? $other_charge_3_description : null,
                        $toFloat($other_charge_4),
                        $other_charge_4_description !== '' ? $other_charge_4_description : null,
                        $toFloat($total_charge)
                    );
                } else {
                    $chargesData->create(
                        (int)$id,
                        $toFloat($removal_charge),
                        $toFloat($pouch_charge),
                        $toFloat($transport_fees),
                        $toFloat($wait_charge),
                        $toFloat($mileage_fees),
                        $toFloat($other_charge_1),
                        $other_charge_1_description !== '' ? $other_charge_1_description : null,
                        $toFloat($other_charge_2),
                        $other_charge_2_description !== '' ? $other_charge_2_description : null,
                        $toFloat($other_charge_3),
                        $other_charge_3_description !== '' ? $other_charge_3_description : null,
                        $toFloat($other_charge_4),
                        $other_charge_4_description !== '' ? $other_charge_4_description : null,
                        $toFloat($total_charge)
                    );
                }
                // Update decedent
                $decedentRepo->updateByTransportId(
                    (int)$id,
                    $decedentFirstName,
                    $decedentLastName,
                    $decedentEthnicity,
                    $decedentGender
                );
            } catch (Exception $e) {
                $error = 'Error updating transport: ' . htmlspecialchars($e->getMessage());
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
    <title>Transport - DispatchBase</title>
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
            <div class="container-xl px-4 mt-n-custom-6">
                <div id="default">
                    <div class="card mb-4 w-100">
                        <div class="card-header"><?php echo ($mode === 'edit') ? 'Edit Transport' : 'Add Transport'; ?></div>
                        <div class="card-body">
                            <?php if ($status === 'deleted'): ?>
                                <div class="alert alert-success" role="alert">Transport deleted successfully!</div>
                            <?php elseif ($status === 'added' || $status === 'updated'): ?>
                                <div class="alert alert-success" role="alert">Transport <?php echo $status === 'added' ? 'added' : 'updated'; ?> successfully!</div>
                            <?php elseif ($error): ?>
                                <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
                            <?php endif; ?>
                            <?php if ($status !== 'deleted'): ?>
                            <form method="POST">
                                <?php csrf_token_field(); ?>
                                <input type="hidden" name="id" value="<?= htmlspecialchars($id ?? '') ?>">

                                <div class="row form-section">
                                    <div class="col-md-6">
                                        <label for="customer_id" class="form-label required">Customer</label>
                                        <select class="form-select<?= $fieldErrors['customer_id'] ? ' is-invalid' : '' ?>" name="customer_id" id="customer_id" required>
                                            <option value="">Select a customer</option>
                                            <?php foreach ($customerRepo->getAll() as $customer): ?>
                                                <option value="<?= htmlspecialchars($customer['id']) ?>" <?= $customerId == $customer['id'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($customer['company_name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <?php render_invalid_feedback('Please fill out this field.', $fieldErrors['customer_id']); ?>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="firm_date" class="form-label required">Firm Date</label>
                                        <input type="datetime-local" class="form-control<?= $fieldErrors['firm_date'] ? ' is-invalid' : '' ?>" name="firm_date" id="firm_date" value="<?= htmlspecialchars($firmDate) ?>" required>
                                        <?php render_invalid_feedback('Please fill out this field.', $fieldErrors['firm_date']); ?>
                                    </div>
                                </div>

                                <div class="row form-section">
                                    <div class="col-md-6">
                                        <label for="account_type" class="form-label required">Account Type</label>
                                        <input type="text" class="form-control<?= $fieldErrors['account_type'] ? ' is-invalid' : '' ?>" name="account_type" id="account_type" value="<?= htmlspecialchars($accountType) ?>" required>
                                        <?php render_invalid_feedback('Please fill out this field.', $fieldErrors['account_type']); ?>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="pouch_type" class="form-label required">Pouch Type</label>
                                        <select class="form-select<?= $fieldErrors['pouch_type'] ? ' is-invalid' : '' ?>" name="pouch_type" id="pouch_type" required>
                                            <option value="">Select a pouch type</option>
                                            <?php if (empty($pouchTypes)): ?>
                                                <option value="" disabled>No pouch types available</option>
                                            <?php else: ?>
                                            <?php foreach ($pouchTypes as $pouch): ?>
                                                 <?php
                                                     // Support rows as arrays or scalar values
                                                     $pouchValue = $pouch['id'] ?? $pouch;
                                                     $pouchLabel = isset($pouchService) ? $pouchService->formatSummary($pouch) : ($pouch['pouch_type'] ?? $pouch['type'] ?? (string)$pouch);
                                                     $isSelected = ((string)$pouchType === (string)$pouchValue) || ((string)$pouchType === (string)($pouch['pouch_type'] ?? $pouch['type'] ?? ''));
                                                 ?>
                                                 <option value="<?= htmlspecialchars($pouchValue) ?>" <?= $isSelected ? 'selected' : '' ?>>
                                                     <?= htmlspecialchars($pouchLabel) ?>
                                                 </option>
                                            <?php endforeach; ?>
                                            <?php endif; ?>
                                         </select>
                                        <?php if (empty($pouchTypes)): ?>
                                            <div class="form-text text-muted">No pouch types found. <a href="pouch-edit.php">Add a pouch type</a>.</div>
                                        <?php endif; ?>
                                        <?php render_invalid_feedback('Please fill out this field.', $fieldErrors['pouch_type']); ?>
                                    </div>
                                </div>

                                <div class="row form-section">
                                    <div class="col-md-6">
                                        <label for="origin_location" class="form-label required">Origin Location</label>
                                        <select class="form-select<?= $fieldErrors['origin_location'] ? ' is-invalid' : '' ?>" name="origin_location" id="origin_location" required>
                                            <option value="">Select an origin location</option>
                                            <?php foreach ($originLocations as $location): ?>
                                                <option value="<?= htmlspecialchars($location['id']) ?>" <?= $originLocation == $location['id'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($location['name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <?php render_invalid_feedback('Please fill out this field.', $fieldErrors['origin_location']); ?>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="destination_location" class="form-label required">Destination Location</label>
                                        <select class="form-select<?= $fieldErrors['destination_location'] ? ' is-invalid' : '' ?>" name="destination_location" id="destination_location" required>
                                            <option value="">Select a destination location</option>
                                            <?php foreach ($destinationLocations as $location): ?>
                                                <option value="<?= htmlspecialchars($location['id']) ?>" <?= $destinationLocation == $location['id'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($location['name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <?php render_invalid_feedback('Please fill out this field.', $fieldErrors['destination_location']); ?>
                                    </div>
                                </div>

                                <div class="row form-section">
                                    <div class="col-md-6">
                                        <label for="coroner" class="form-label required">Coroner</label>
                                        <select class="form-select<?= $fieldErrors['coroner'] ? ' is-invalid' : '' ?>" name="coroner" id="coroner" required>
                                            <option value="">Select a coroner</option>
                                            <?php foreach ($coroners as $coroner): ?>
                                                <option value="<?= htmlspecialchars($coroner['id'] ?? '') ?>" <?= ($coronerName == ($coroner['id'] ?? '') || $coronerName === ($coroner['coroner_name'] ?? '')) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($coroner['coroner_name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <?php render_invalid_feedback('Please fill out this field.', $fieldErrors['coroner']); ?>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="transit_permit_number" class="form-label">Transit Permit Number</label>
                                        <input type="text" class="form-control" name="transit_permit_number" id="transit_permit_number" value="<?= htmlspecialchars($transitPermitNumber) ?>">
                                    </div>
                                </div>

                                <div class="row form-section">
                                    <div class="col-md-6">
                                        <label for="permit_number" class="form-label">Permit Number</label>
                                        <input type="text" class="form-control" name="permit_number" id="permit_number" value="<?= htmlspecialchars($permitNumber) ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="tag_number" class="form-label">Tag Number</label>
                                        <input type="text" class="form-control" name="tag_number" id="tag_number" value="<?= htmlspecialchars($tagNumber) ?>">
                                    </div>
                                </div>

                                <!-- Decedent fields (restored from transport-edit_old / decedent-edit.php) -->
                                <div class="row form-section">
                                    <div class="col-md-6">
                                        <label for="first_name" class="form-label">Decedent First Name</label>
                                        <input type="text" id="first_name" name="first_name" class="form-control" value="<?= htmlspecialchars($decedentFirstName ?? '') ?>">
                                        <div class="invalid-feedback">Please fill out this field.</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="last_name" class="form-label">Decedent Last Name</label>
                                        <input type="text" id="last_name" name="last_name" class="form-control" value="<?= htmlspecialchars($decedentLastName ?? '') ?>">
                                        <div class="invalid-feedback">Please fill out this field.</div>
                                    </div>
                                </div>

                                <div class="row form-section">
                                    <div class="col-md-6">
                                        <label for="ethnicity" class="form-label">Ethnicity</label>
                                        <select id="ethnicity" name="ethnicity" class="form-select">
                                            <option value="" <?= empty($decedentEthnicity) ? 'selected' : '' ?>>Select Ethnicity</option>
                                            <?php $ethnicities = include __DIR__ . '/../includes/ethnicities.php'; ?>
                                            <?php foreach ($ethnicities as $ethnicity): ?>
                                                <option value="<?= htmlspecialchars($ethnicity) ?>" <?= (isset($decedentEthnicity) && $decedentEthnicity === $ethnicity) ? 'selected' : '' ?>><?= htmlspecialchars($ethnicity) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="invalid-feedback">Please fill out this field.</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="gender" class="form-label">Gender</label>
                                        <select id="gender" name="gender" class="form-select">
                                            <option value="" <?= empty($decedentGender) ? 'selected' : '' ?>>Select Gender</option>
                                            <?php $genders = include __DIR__ . '/../includes/genders.php'; ?>
                                            <?php foreach ($genders as $gender): ?>
                                                <option value="<?= htmlspecialchars($gender) ?>" <?= (isset($decedentGender) && $decedentGender === $gender) ? 'selected' : '' ?>><?= htmlspecialchars($gender) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="invalid-feedback">Please fill out this field.</div>
                                    </div>
                                </div>

                                <div class="row form-section">
                                    <div class="col-md-3">
                                        <label for="call_time" class="form-label required">Call Time</label>
                                        <input type="datetime-local" class="form-control<?= ($fieldErrors['call_time'] || isset($timeErrors['call_time'])) ? ' is-invalid' : '' ?>" name="call_time" id="call_time" value="<?= htmlspecialchars($callTime ?? '') ?>" required>
                                        <?php render_invalid_feedback(isset($timeErrors['call_time']) ? $timeErrors['call_time'] : 'Please fill out this field.', ($fieldErrors['call_time'] || isset($timeErrors['call_time']))); ?>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="arrival_time" class="form-label required">Arrival Time</label>
                                        <input type="datetime-local" class="form-control<?= ($fieldErrors['arrival_time'] || isset($timeErrors['arrival_time'])) ? ' is-invalid' : '' ?>" name="arrival_time" id="arrival_time" value="<?= htmlspecialchars($arrivalTime ?? '') ?>" required>
                                        <?php render_invalid_feedback(isset($timeErrors['arrival_time']) ? $timeErrors['arrival_time'] : 'Please fill out this field.', ($fieldErrors['arrival_time'] || isset($timeErrors['arrival_time']))); ?>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="departure_time" class="form-label required">Departure Time</label>
                                        <input type="datetime-local" class="form-control<?= ($fieldErrors['departure_time'] || isset($timeErrors['departure_time'])) ? ' is-invalid' : '' ?>" name="departure_time" id="departure_time" value="<?= htmlspecialchars($departureTime ?? '') ?>" required>
                                        <?php render_invalid_feedback(isset($timeErrors['departure_time']) ? $timeErrors['departure_time'] : 'Please fill out this field.', ($fieldErrors['departure_time'] || isset($timeErrors['departure_time']))); ?>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="delivery_time" class="form-label required">Delivery Time</label>
                                        <input type="datetime-local" class="form-control<?= ($fieldErrors['delivery_time'] || isset($timeErrors['delivery_time'])) ? ' is-invalid' : '' ?>" name="delivery_time" id="delivery_time" value="<?= htmlspecialchars($deliveryTime ?? '') ?>" required>
                                        <?php render_invalid_feedback(isset($timeErrors['delivery_time']) ? $timeErrors['delivery_time'] : 'Please fill out this field.', ($fieldErrors['delivery_time'] || isset($timeErrors['delivery_time']))); ?>
                                    </div>
                                </div>

                                <!-- Transporter selectors: Primary is required -->
                                <div class="row form-section">
                                    <div class="col-md-6">
                                        <label for="primary_transporter" class="form-label required">Primary Transporter</label>
                                        <select class="form-select<?= $fieldErrors['primary_transporter'] ? ' is-invalid' : '' ?>" name="primary_transporter" id="primary_transporter" required>
                                            <option value="">Select a driver</option>
                                            <?php foreach ($drivers as $d): ?>
                                                <?php
                                                    $driverId = $d['id'] ?? $d['user_id'] ?? ($d['employee_id'] ?? null);
                                                    // try several name fields for compatibility
                                                    $driverName = $d['user_full_name'] ?? $d['full_name'] ?? (trim(($d['first_name'] ?? '') . ' ' . ($d['last_name'] ?? '')) ?: (string)($d['name'] ?? ''));
                                                ?>
                                                <option value="<?= htmlspecialchars($driverId) ?>" <?= ($primaryTransporter == $driverId) ? 'selected' : '' ?>><?= htmlspecialchars($driverName) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <?php render_invalid_feedback('Please fill out this field.', $fieldErrors['primary_transporter']); ?>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="assistant_transporter" class="form-label">Assistant Transporter</label>
                                        <select class="form-select<?= $fieldErrors['assistant_transporter'] ? ' is-invalid' : '' ?>" name="assistant_transporter" id="assistant_transporter">
                                            <option value="">Select a driver</option>
                                            <?php foreach ($drivers as $d): ?>
                                                <?php
                                                    $driverId = $d['id'] ?? $d['user_id'] ?? ($d['employee_id'] ?? null);
                                                    $driverName = $d['user_full_name'] ?? $d['full_name'] ?? (trim(($d['first_name'] ?? '') . ' ' . ($d['last_name'] ?? '')) ?: (string)($d['name'] ?? ''));
                                                ?>
                                                <option value="<?= htmlspecialchars($driverId) ?>" <?= ($assistantTransporter == $driverId) ? 'selected' : '' ?>><?= htmlspecialchars($driverName) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <?php render_invalid_feedback('Please fill out this field.', $fieldErrors['assistant_transporter']); ?>
                                    </div>
                                </div>

                                <div class="row form-section">
                                    <div class="col-md-4">
                                        <label for="mileage" class="form-label">Mileage</label>
                                        <input type="number" step="0.01" class="form-control" name="mileage" id="mileage" value="<?= htmlspecialchars($mileage) ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="mileage_rate" class="form-label">Mileage Rate</label>
                                        <input type="number" step="0.01" class="form-control" name="mileage_rate" id="mileage_rate" value="<?= htmlspecialchars($mileage_rate) ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="mileage_total_charge" class="form-label">Mileage Total Charge</label>
                                        <input type="number" step="0.01" class="form-control" name="mileage_total_charge" id="mileage_total_charge" value="<?= htmlspecialchars($mileage_total_charge) ?>">
                                    </div>
                                </div>

                                <h5 class="mt-4">Charges</h5>
                                <div class="row form-section">
                                    <div class="col-md-4">
                                        <label for="removal_charge" class="form-label">Removal Charge</label>
                                        <input type="number" step="0.01" class="form-control<?= isset($chargeErrors['removal_charge']) ? ' is-invalid' : '' ?>" name="removal_charge" id="removal_charge" value="<?= htmlspecialchars($removal_charge) ?>">
                                        <?php if (isset($chargeErrors['removal_charge'])) render_invalid_feedback($chargeErrors['removal_charge'], true); ?>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="pouch_charge" class="form-label">Pouch Charge</label>
                                        <input type="number" step="0.01" class="form-control<?= isset($chargeErrors['pouch_charge']) ? ' is-invalid' : '' ?>" name="pouch_charge" id="pouch_charge" value="<?= htmlspecialchars($pouch_charge) ?>">
                                        <?php if (isset($chargeErrors['pouch_charge'])) render_invalid_feedback($chargeErrors['pouch_charge'], true); ?>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="transport_fees" class="form-label">Transport Fees</label>
                                        <input type="number" step="0.01" class="form-control<?= isset($chargeErrors['transport_fees']) ? ' is-invalid' : '' ?>" name="transport_fees" id="transport_fees" value="<?= htmlspecialchars($transport_fees) ?>">
                                        <?php if (isset($chargeErrors['transport_fees'])) render_invalid_feedback($chargeErrors['transport_fees'], true); ?>
                                    </div>
                                </div>

                                <div class="row form-section">
                                    <div class="col-md-4">
                                        <label for="wait_charge" class="form-label">Wait Charge</label>
                                        <input type="number" step="0.01" class="form-control<?= isset($chargeErrors['wait_charge']) ? ' is-invalid' : '' ?>" name="wait_charge" id="wait_charge" value="<?= htmlspecialchars($wait_charge) ?>">
                                        <?php if (isset($chargeErrors['wait_charge'])) render_invalid_feedback($chargeErrors['wait_charge'], true); ?>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="mileage_fees" class="form-label">Mileage Fees</label>
                                        <input type="number" step="0.01" class="form-control<?= isset($chargeErrors['mileage_fees']) ? ' is-invalid' : '' ?>" name="mileage_fees" id="mileage_fees" value="<?= htmlspecialchars($mileage_fees) ?>">
                                        <?php if (isset($chargeErrors['mileage_fees'])) render_invalid_feedback($chargeErrors['mileage_fees'], true); ?>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="other_charge_1" class="form-label">Other Charge 1</label>
                                        <input type="number" step="0.01" class="form-control<?= isset($chargeErrors['other_charge_1']) ? ' is-invalid' : '' ?>" name="other_charge_1" id="other_charge_1" value="<?= htmlspecialchars($other_charge_1) ?>">
                                        <?php if (isset($chargeErrors['other_charge_1'])) render_invalid_feedback($chargeErrors['other_charge_1'], true); ?>
                                    </div>
                                </div>

                                <div class="row form-section">
                                    <div class="col-md-6">
                                        <label for="other_charge_1_description" class="form-label">Other Charge 1 Description</label>
                                        <input type="text" class="form-control" name="other_charge_1_description" id="other_charge_1_description" value="<?= htmlspecialchars($other_charge_1_description) ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="other_charge_2" class="form-label">Other Charge 2</label>
                                        <input type="number" step="0.01" class="form-control<?= isset($chargeErrors['other_charge_2']) ? ' is-invalid' : '' ?>" name="other_charge_2" id="other_charge_2" value="<?= htmlspecialchars($other_charge_2) ?>">
                                        <?php if (isset($chargeErrors['other_charge_2'])) render_invalid_feedback($chargeErrors['other_charge_2'], true); ?>
                                    </div>
                                </div>

                                <div class="row form-section">
                                    <div class="col-md-6">
                                        <label for="other_charge_2_description" class="form-label">Other Charge 2 Description</label>
                                        <input type="text" class="form-control" name="other_charge_2_description" id="other_charge_2_description" value="<?= htmlspecialchars($other_charge_2_description) ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="other_charge_3" class="form-label">Other Charge 3</label>
                                        <input type="number" step="0.01" class="form-control<?= isset($chargeErrors['other_charge_3']) ? ' is-invalid' : '' ?>" name="other_charge_3" id="other_charge_3" value="<?= htmlspecialchars($other_charge_3) ?>">
                                        <?php if (isset($chargeErrors['other_charge_3'])) render_invalid_feedback($chargeErrors['other_charge_3'], true); ?>
                                    </div>
                                </div>

                                <div class="row form-section">
                                    <div class="col-md-6">
                                        <label for="other_charge_3_description" class="form-label">Other Charge 3 Description</label>
                                        <input type="text" class="form-control" name="other_charge_3_description" id="other_charge_3_description" value="<?= htmlspecialchars($other_charge_3_description) ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="other_charge_4" class="form-label">Other Charge 4</label>
                                        <input type="number" step="0.01" class="form-control<?= isset($chargeErrors['other_charge_4']) ? ' is-invalid' : '' ?>" name="other_charge_4" id="other_charge_4" value="<?= htmlspecialchars($other_charge_4) ?>">
                                        <?php if (isset($chargeErrors['other_charge_4'])) render_invalid_feedback($chargeErrors['other_charge_4'], true); ?>
                                    </div>
                                </div>

                                <div class="row form-section">
                                    <div class="col-md-6">
                                        <label for="other_charge_4_description" class="form-label">Other Charge 4 Description</label>
                                        <input type="text" class="form-control" name="other_charge_4_description" id="other_charge_4_description" value="<?= htmlspecialchars($other_charge_4_description) ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="total_charge" class="form-label">Total Charge</label>
                                        <input type="number" step="0.01" class="form-control" name="total_charge" id="total_charge" value="<?= htmlspecialchars($total_charge) ?>" readonly>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-between mt-4">
                                    <button type="submit" class="btn btn-primary"><?= $mode === 'edit' ? 'Update' : 'Add' ?> Transport</button>
                                    <?php if ($mode === 'edit'): ?>
                                        <button type="submit" name="delete_transport" value="1" class="btn btn-secondary" onclick="return confirm('Are you sure you want to delete this transport?');">Delete Transport</button>
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
<script>
    // Client-side validation: assistant cannot equal primary
    (function() {
        var form = document.querySelector('form');
        if (!form) return;
        var primary = document.getElementById('primary_transporter');
        var assistant = document.getElementById('assistant_transporter');

        // Helper to show a top-level required-fields alert (matches server message)
        function ensureTopError(msg) {
            var container = document.querySelector('.card-body');
            if (!container) return;
            var existing = container.querySelector('.transport-required-alert');
            if (!existing) {
                var div = document.createElement('div');
                div.className = 'alert alert-danger transport-required-alert';
                div.setAttribute('role', 'alert');
                div.textContent = msg;
                container.insertBefore(div, container.firstChild);
            } else {
                existing.textContent = msg;
            }
        }

        function setAssistantValidity() {
            if (!primary || !assistant) return true;
            var p = primary.value;
            var a = assistant.value;
            // Reset
            assistant.classList.remove('is-invalid');
            primary.classList.remove('is-invalid');
            var existingFeedback = assistant.nextElementSibling;
            if (existingFeedback && existingFeedback.classList && existingFeedback.classList.contains('invalid-feedback')) {
                existingFeedback.remove();
            }
            if (p !== '' && a !== '' && p === a) {
                assistant.classList.add('is-invalid');
                primary.classList.add('is-invalid');
                var div = document.createElement('div');
                div.className = 'invalid-feedback d-block';
                div.textContent = 'Assistant cannot be the same as primary transporter.';
                assistant.parentNode.appendChild(div);
                ensureTopError('Please correct the highlighted fields.');
                return false;
            }
            return true;
        }

        if (primary && assistant) {
            primary.addEventListener('change', setAssistantValidity);
            assistant.addEventListener('change', setAssistantValidity);
        }

        form.addEventListener('submit', function(e) {
            // First, check HTML5 validity for the form. If invalid, prevent submission and show top error
            if (!form.checkValidity()) {
                e.preventDefault();
                // Add 'is-invalid' to any required fields that are empty so user sees red
                var requiredEls = form.querySelectorAll('[required]');
                requiredEls.forEach(function(el) {
                    // If element has no value, mark invalid
                    if (!el.value) {
                        el.classList.add('is-invalid');
                        // insert invalid-feedback if not present
                        var next = el.nextElementSibling;
                        if (!(next && next.classList && next.classList.contains('invalid-feedback'))) {
                            var div = document.createElement('div');
                            div.className = 'invalid-feedback d-block';
                            div.textContent = 'Please fill out this field.';
                            el.parentNode.appendChild(div);
                        }
                    }
                });
                ensureTopError('Please fill in all required fields.');
                window.scrollTo({ top: 0, behavior: 'smooth' });
                return;
            }

            var ok = setAssistantValidity();
            if (!ok) {
                e.preventDefault();
                // scroll to top so the global error is visible if present
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        });
    })();
</script>
</body>
</html>
