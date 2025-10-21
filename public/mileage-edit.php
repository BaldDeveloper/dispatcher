<?php
// mileage-edit.php
// Section for editing mileage information.
require_once __DIR__ . '/../database/TransportData.php';
require_once __DIR__ . '/../database/Database.php';
?>
<div id="mileage-section">
    <div class="container-xl px-1">
        <div class="page-header-content pt-4">
            <div class="row align-items-center justify-content-between">
                <h4>Mileage Information</h4>
            </div>
        </div>
    </div>
    <table style="width:100%;">
        <tr>
            <td style="padding:10px;">
                <label for="mileage" class="form-label required">Mileage</label><br>
                <input type="number" name="mileage" id="mileage" class="form-control" style="width:95%;" step="0.01" min="0" required value="<?= htmlspecialchars($mileage ?? '') ?>">
                <div class="invalid-feedback">Please fill out this field.</div>
            </td>
            <td style="padding:10px;">
                <label for="mileage_rate">Mileage Rate</label><br>
                <input type="number" name="mileage_rate" id="mileage_rate" class="form-control" style="width:95%;" step="0.01" min="0" value="<?= htmlspecialchars($mileage_rate ?? '') ?>">
                <div class="invalid-feedback" style="display:none;">Please fill out this field.</div>
            </td>
            <td style="padding:10px;">
                <label for="mileage_total_charge">Mileage Total Charge</label><br>
                <input type="number" name="mileage_total_charge" id="mileage_total_charge" class="form-control" style="width:95%;" step="0.01" min="0" value="<?= htmlspecialchars($mileage_total_charge ?? '') ?>">
                <div class="invalid-feedback" style="display:none;">Please fill out this field.</div>
            </td>
        </tr>
    </table>
    <div id="mileage-required-message" class="text-danger" style="display:none;">
        Please fill in all required fields.
    </div>
    <script>
        function calculateMileageTotalCharge() {
            const mileage = parseFloat(document.getElementById('mileage').value) || 0;
            const rate = parseFloat(document.getElementById('mileage_rate').value) || 0;
            const total = (mileage * rate).toFixed(2);
            document.getElementById('mileage_total_charge').value = total;
        }
        document.getElementById('mileage').addEventListener('input', calculateMileageTotalCharge);
        document.getElementById('mileage_rate').addEventListener('input', calculateMileageTotalCharge);
        // Initial calculation on page load
        calculateMileageTotalCharge();
        // Simple required field validation
        function validateMileageFields() {
            const mileage = document.getElementById('mileage').value;
            const rate = document.getElementById('mileage_rate').value;
            const total = document.getElementById('mileage_total_charge').value;
            const msg = document.getElementById('mileage-required-message');
            if (!mileage || !rate || !total) {
                msg.style.display = 'block';
                return false;
            }
            msg.style.display = 'none';
            return true;
        }
        // Attach validation to form submit if needed
    </script>
</div>
