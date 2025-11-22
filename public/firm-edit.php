<?php
require_once __DIR__ . '/../database/Database.php';
require_once __DIR__ . '/../includes/account_types.php';

// firm-edit.php
// This page will be used for adding/editing firm records. Currently empty, but includes a label for testing inclusion.
?>
<div id="firm-section">
    <div class="container-xl px-1">
        <div class="page-header-content pt-4">
            <div class="row align-items-center justify-content-between">
                <h4>Firm Information</h4>
            </div>
        </div>
    </div>
    <table style="width:100%;">
        <tr>
            <td>
                <div class="mb-3">
                    <label for="firm_date" class="form-label required">Firm Date</label>
                    <input type="date" class="form-control" id="firm_date" name="firm_date"
                           value="<?= htmlspecialchars($firmDate ?? '') ?>" required />
                </div>
            </td>
            <td>
                <div class="mb-3">
                    <label for="customer_id" class="form-label required">Firm</label>
                    <select class="form-control" id="customer_id" name="customer_id" required>
                        <option value="">Select Firm</option>
                        <?php foreach ($customers as $customer): ?>
                            <option value="<?= htmlspecialchars($customer['id']) ?>" <?= ($customerId == $customer['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($customer['company_name']) ?>
                                (<?= htmlspecialchars($customer['id']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="invalid-feedback">Please fill out this field.</div>
                </div>
            </td>
        </tr>
        <tr>
            <td>
                <div class="mb-3">
                    <label for="account_type" class="form-label required">Account Type</label>
                    <select class="form-control" id="account_type" name="account_type" required>
                        <option value="">Select Account Type</option>
                        <?php foreach ($ACCOUNT_TYPES as $key => $label): ?>
                            <option value="<?= htmlspecialchars($key) ?>" <?= ($accountType == $key) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="invalid-feedback">Please fill out this field.</div>
                </div>
            </td>
            <td>
                <!-- Placeholder for future field -->
            </td>
        </tr>
    </table>
    <!-- Add other fields as needed -->
</div>
