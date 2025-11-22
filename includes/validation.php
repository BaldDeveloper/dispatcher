<?php
// Common validation utilities

// Email regex pattern for HTML5 pattern attribute
const EMAIL_PATTERN = '[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}';
// Phone regex pattern for HTML5 pattern attribute
const PHONE_PATTERN = '\(\d{3}\)\d{3}-\d{4}';
// License plate regex pattern for HTML5 pattern attribute (alphanumeric, 2-15 chars)
const LICENSE_PLATE_PATTERN = '[A-Za-z0-9]{2,15}';

/**
 * Validate an email address (server-side)
 * @param string $email
 * @return bool
 */
function is_valid_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate a phone number (server-side)
 * @param string $phone
 * @return bool
 */
function is_valid_phone($phone) {
    return preg_match('/^\(\d{3}\)\d{3}-\d{4}$/', $phone);
}

/**
 * Validate a name (letters, spaces, hyphens, apostrophes; 1-50 chars)
 * @param string $name
 * @return bool
 */
function is_valid_name($name) {
    return is_string($name) && preg_match("/^[a-zA-Z'\-\s]{1,50}$/u", $name);
}

/**
 * Validate a license plate (alphanumeric, 2-15 chars)
 * @param string $plate
 * @return bool
 */
function is_valid_license_plate($plate) {
    return is_string($plate) && preg_match('/^[A-Za-z0-9]{2,15}$/', $plate);
}

/**
 * Validate chronological order of transport times.
 * Returns an array of error messages for any invalid order.
 * @param string $callTime
 * @param string $departureTime
 * @param string $arrivalTime
 * @param string $deliveryTime
 * @return array
 */
function validate_transport_times($callTime, $departureTime, $arrivalTime, $deliveryTime) {
    $errors = [];
    // Convert to timestamps for comparison
    $call = strtotime(trim($callTime));
    $depart = strtotime(trim($departureTime));
    $arrive = strtotime(trim($arrivalTime));
    $deliver = strtotime(trim($deliveryTime));
    if ($call && $depart && $depart <= $call) {
        $errors['departure_time'] = 'Departure time must be after Call time.';
    }
    if ($depart && $arrive && $arrive <= $depart) {
        $errors['arrival_time'] = 'Arrival time must be after Departure time.';
    }
    if ($arrive && $deliver && $deliver <= $arrive) {
        $errors['delivery_time'] = 'Delivery time must be after Arrival time.';
    }
    return $errors;
}

/**
 * Validate vehicle fields (required and pattern checks)
 * @param array $data
 * @return array Associative array of errors (field => message)
 */
function validate_vehicle_fields($data) {
    $errors = [];
    // Required fields
    if (empty($data['vehicle_type'])) {
        $errors['vehicle_type'] = 'Please fill out this field.';
    }
    if (empty($data['color'])) {
        $errors['color'] = 'Please fill out this field.';
    }
    if (empty($data['license_plate'])) {
        $errors['license_plate'] = 'Please fill out this field.';
    } elseif (!is_valid_license_plate($data['license_plate'])) {
        $errors['license_plate'] = 'License plate must be 2-15 letters or numbers (no spaces or symbols).';
    }
    if (empty($data['year'])) {
        $errors['year'] = 'Please fill out this field.';
    } elseif (!preg_match('/^(19|20)\\d{2}$/', $data['year'])) {
        $errors['year'] = 'Enter a valid year (e.g., 2020).';
    }
    if (empty($data['make'])) {
        $errors['make'] = 'Please fill out this field.';
    }
    if (empty($data['model'])) {
        $errors['model'] = 'Please fill out this field.';
    }
    if (empty($data['vin'])) {
        $errors['vin'] = 'Please fill out this field.';
    } elseif (!preg_match('/^[A-HJ-NPR-Z0-9]{17}$/i', $data['vin'])) {
        $errors['vin'] = 'VIN must be 17 characters (letters and numbers, no I/O/Q).';
    }
    return $errors;
}

/**
 * Validate transport charges fields.
 * Accepts an associative array (e.g., $_POST) and returns an array of errors keyed by field name.
 * Empty values are treated as zero (business rule can be changed to require fields if needed).
 *
 * @param array $input
 * @return array<string,string>
 */
function validate_transport_charges_fields(array $input): array {
    $errors = [];
    $numericFields = [
        'removal_charge',
        'pouch_charge',
        'transport_fees',
        'wait_charge',
        'mileage_fees',
        'other_charge_1',
        'other_charge_2',
        'other_charge_3',
        'other_charge_4'
    ];

    foreach ($numericFields as $field) {
        $raw = $input[$field] ?? '';
        $v = trim((string)$raw);
        if ($v === '') {
            // Treat empty as zero; no error. Change if business requires field to be present.
            continue;
        }
        // Normalize comma as decimal separator
        $v = str_replace(',', '.', $v);
        // Validate float format
        $validated = filter_var($v, FILTER_VALIDATE_FLOAT);
        if ($validated === false) {
            $errors[$field] = 'Invalid number format.';
            continue;
        }
        $f = (float)$validated;
        if ($f < 0) {
            $errors[$field] = 'Value must be zero or greater.';
        }
    }

    // Optional: verify total if provided. This function only validates components; caller may recalculate total server-side.
    return $errors;
}

/**
 * Validate transport form fields (required checks and time ordering).
 * Sets per-field error flags in $fieldErrors and per-time messages in $timeErrors.
 * Returns an empty string on success or a global error message when required fields are missing.
 *
 * @param array $fields
 * @param array $fieldErrors (by reference) associative array of field => bool
 * @param array $timeErrors (by reference) associative array of timeField => message
 * @return string global error message or empty string
 */
function validate_transport_fields(array $fields, array & $fieldErrors, array & $timeErrors): string {
    $missing = [];
    $required = [
        'customer_id',
        'firm_date',
        'account_type',
        'origin_location',
        'destination_location',
        'coroner',
        'pouch_type',
        'primary_transporter'
    ];

    foreach ($required as $req) {
        if (!isset($fields[$req]) || trim((string)$fields[$req]) === '') {
            $fieldErrors[$req] = true;
            $missing[] = $req;
        }
    }

    // Time fields required
    $timeFields = ['call_time', 'arrival_time', 'departure_time', 'delivery_time'];
    foreach ($timeFields as $t) {
        if (!isset($fields[$t]) || trim((string)$fields[$t]) === '') {
            $fieldErrors[$t] = true;
            $missing[] = $t;
        }
    }

    // Chronological order validation (only if values present)
    $timeErrors = validate_transport_times(
        $fields['call_time'] ?? '',
        $fields['departure_time'] ?? '',
        $fields['arrival_time'] ?? '',
        $fields['delivery_time'] ?? ''
    );

    // Mark any time-related fields as invalid if they have messages
    foreach ($timeErrors as $key => $msg) {
        $fieldErrors[$key] = true;
    }

    return empty($missing) ? '' : 'Please fill in all required fields.';
}
