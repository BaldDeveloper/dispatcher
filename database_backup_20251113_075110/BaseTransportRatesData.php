<?php
/**
 * Backwards-compatible shim for BaseTransportRatesData
 * This file preserves the old class name and delegates to BaseRatesData.
 */
require_once __DIR__ . '/BaseRatesData.php';

class BaseTransportRatesData extends BaseRatesData {
    // intentionally empty - exists for backward compatibility
}
