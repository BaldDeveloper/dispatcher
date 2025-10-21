<?php
require_once __DIR__ . '/../database/TransportData.php';
require_once __DIR__ . '/../database/Database.php';

// times-edit.php
// This page will be used as a section in transport-edit.php for editing/adding transport times.
// Expects: $callTime, $arrivalTime, $departureTime, $deliveryTime (all datetime strings or null)
?>
<!DOCTYPE html>
<html lang="en">
<div class="container-xl px-1">
    <div class="page-header-content pt-4">
        <div class="row align-items-center justify-content-between">
            <h4>Transport Times</h4>
        </div>
    </div>
</div>
<div id="times-section">
    <table style="width:100%;">
        <tr>
            <td style="padding:10px;">
                <label for="call_time" class="form-label required">Call Time</label><br>
                <input type="datetime-local" id="call_time" name="call_time" class="form-control<?= isset($timeErrors['call_time']) ? ' is-invalid' : '' ?>" style="width:95%;" value="<?= htmlspecialchars($callTime ?? '') ?>" required>
                <?php if (isset($timeErrors['call_time'])): ?>
                    <div class="invalid-feedback"> <?= htmlspecialchars($timeErrors['call_time']) ?> </div>
                <?php else: ?>
                    <div class="invalid-feedback">Please fill out this field.</div>
                <?php endif; ?>
            </td>
            <td style="padding:10px;">
                <label for="arrival_time" class="form-label required">Arrival Time</label><br>
                <input type="datetime-local" id="arrival_time" name="arrival_time" class="form-control<?= isset($timeErrors['arrival_time']) ? ' is-invalid' : '' ?>" style="width:95%;" value="<?= htmlspecialchars($arrivalTime ?? '') ?>" required>
                <?php if (isset($timeErrors['arrival_time'])): ?>
                    <div class="invalid-feedback"> <?= htmlspecialchars($timeErrors['arrival_time']) ?> </div>
                <?php else: ?>
                    <div class="invalid-feedback">Please fill out this field.</div>
                <?php endif; ?>
            </td>
            <td style="padding:10px;">
                <label for="departure_time" class="form-label required">Departure Time</label><br>
                <input type="datetime-local" id="departure_time" name="departure_time" class="form-control<?= isset($timeErrors['departure_time']) ? ' is-invalid' : '' ?>" style="width:95%;" value="<?= htmlspecialchars($departureTime ?? '') ?>" required>
                <?php if (isset($timeErrors['departure_time'])): ?>
                    <div class="invalid-feedback"> <?= htmlspecialchars($timeErrors['departure_time']) ?> </div>
                <?php else: ?>
                    <div class="invalid-feedback">Please fill out this field.</div>
                <?php endif; ?>
            </td>
            <td style="padding:10px;">
                <label for="delivery_time" class="form-label required">Delivery Time</label><br>
                <input type="datetime-local" id="delivery_time" name="delivery_time" class="form-control<?= isset($timeErrors['delivery_time']) ? ' is-invalid' : '' ?>" style="width:95%;" value="<?= htmlspecialchars($deliveryTime ?? '') ?>" required>
                <?php if (isset($timeErrors['delivery_time'])): ?>
                    <div class="invalid-feedback"> <?= htmlspecialchars($timeErrors['delivery_time']) ?> </div>
                <?php else: ?>
                    <div class="invalid-feedback">Please fill out this field.</div>
                <?php endif; ?>
            </td>
        </tr>
    </table>
    <div id="times-error" style="color:red; display:none; margin-top:10px;"></div>
</div>
<script>
// Required field validation for times section, matching customer-edit.php style
function validateTimesRequiredFields(form) {
    let firstInvalid = null;
    // Remove previous error highlighting
    form.querySelectorAll('.field-error').forEach(function(field) {
        field.classList.remove('field-error');
    });
    // Validate required fields
    ['call_time','arrival_time','departure_time','delivery_time'].forEach(function(id) {
        var field = form.querySelector('#' + id);
        if (!field || !field.value || field.value.trim() === '') {
            if (field) field.classList.add('field-error');
            if (!firstInvalid && field) firstInvalid = field;
        }
    });
    if (firstInvalid) {
        firstInvalid.focus();
        return false;
    }
    return true;
}
document.addEventListener('DOMContentLoaded', function() {
    var timesSection = document.getElementById('times-section');
    var form = timesSection.closest('form');
    if (!form) return;
    form.addEventListener('submit', function(e) {
        if (!validateTimesRequiredFields(form)) {
            e.preventDefault();
            document.getElementById('times-error').textContent = 'Please fill in all required time fields.';
            document.getElementById('times-error').style.display = 'block';
        } else {
            document.getElementById('times-error').textContent = '';
            document.getElementById('times-error').style.display = 'none';
        }
    });
});
</script>
</html>
