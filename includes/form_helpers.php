<?php
/**
 * Render a Bootstrap invalid-feedback element for form validation
 *
 * @param string $message The error message to display
 * @param bool $show Whether to show the feedback (field is invalid)
 */
function render_invalid_feedback($message, $show) {
    if ($show) {
        echo '<div class="invalid-feedback d-block">' . htmlspecialchars($message) . '</div>';
    }
}

/**
 * Sanitize and trim all vehicle fields from input array
 * @param array $input Typically $_POST
 * @return array Cleaned vehicle fields
 */
function sanitize_and_trim_vehicle_fields($input) {
    return [
        'vehicle_type' => trim($input['vehicle_type'] ?? ''),
        'color' => trim($input['color'] ?? ''),
        'license_plate' => trim($input['license_plate'] ?? ''),
        'year' => trim($input['year'] ?? ''),
        'make' => trim($input['make'] ?? ''),
        'model' => trim($input['model'] ?? ''),
        'vin' => trim($input['vin'] ?? ''),
        'refrigeration_unit' => trim($input['refrigeration_unit'] ?? ''),
        'fuel_type' => trim($input['fuel_type'] ?? ''),
        'odometer_reading' => trim($input['odometer_reading'] ?? ''),
        'trailer_compatible' => trim($input['trailer_compatible'] ?? ''),
        'emission_cert_status' => trim($input['emission_cert_status'] ?? ''),
        'inspection_notes' => trim($input['inspection_notes'] ?? ''),
        'assigned_mechanic' => trim($input['assigned_mechanic'] ?? ''),
        'last_service_date' => trim($input['last_service_date'] ?? ''),
        'next_service_date' => trim($input['next_service_date'] ?? ''),
        'service_interval' => trim($input['service_interval'] ?? ''),
        'maintenance_status' => trim($input['maintenance_status'] ?? ''),
        'current_status' => trim($input['current_status'] ?? ''),
        'tire_condition' => trim($input['tire_condition'] ?? ''),
        'battery_health' => trim($input['battery_health'] ?? ''),
        'registration_expiry' => trim($input['registration_expiry'] ?? ''),
        'insurance_provider' => trim($input['insurance_provider'] ?? ''),
        'insurance_policy_number' => trim($input['insurance_policy_number'] ?? ''),
        'insurance_expiry' => trim($input['insurance_expiry'] ?? ''),
        'notes' => trim($input['notes'] ?? '')
    ];
}

/**
 * Normalize vehicle fields for DB storage
 * @param array $fields Sanitized vehicle fields
 * @return array Normalized fields for DB
 */
function normalize_vehicle_fields_for_db($fields) {
    return [
        'vehicle_type' => $fields['vehicle_type'],
        'color' => $fields['color'],
        'license_plate' => $fields['license_plate'],
        'year' => $fields['year'],
        'make' => $fields['make'],
        'model' => $fields['model'],
        'vin' => $fields['vin'],
        'refrigeration_unit' => ($fields['refrigeration_unit'] === 'Yes') ? 1 : (($fields['refrigeration_unit'] === 'No') ? 0 : null),
        'fuel_type' => $fields['fuel_type'] !== '' ? $fields['fuel_type'] : null,
        'odometer_reading' => ($fields['odometer_reading'] === '' || !is_numeric($fields['odometer_reading'])) ? null : (int)$fields['odometer_reading'],
        'trailer_compatible' => ($fields['trailer_compatible'] === 'Yes') ? 1 : (($fields['trailer_compatible'] === 'No') ? 0 : null),
        'emission_cert_status' => $fields['emission_cert_status'],
        'inspection_notes' => $fields['inspection_notes'],
        'assigned_mechanic' => $fields['assigned_mechanic'],
        'last_service_date' => ($fields['last_service_date'] === '') ? null : $fields['last_service_date'],
        'next_service_date' => ($fields['next_service_date'] === '') ? null : $fields['next_service_date'],
        'service_interval' => $fields['service_interval'],
        'maintenance_status' => $fields['maintenance_status'],
        'current_status' => $fields['current_status'],
        'tire_condition' => $fields['tire_condition'],
        'battery_health' => $fields['battery_health'],
        'registration_expiry' => ($fields['registration_expiry'] === '') ? null : $fields['registration_expiry'],
        'insurance_provider' => $fields['insurance_provider'],
        'insurance_policy_number' => $fields['insurance_policy_number'],
        'insurance_expiry' => ($fields['insurance_expiry'] === '') ? null : $fields['insurance_expiry'],
        'notes' => $fields['notes']
    ];
}
