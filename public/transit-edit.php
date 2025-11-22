<?php
require_once __DIR__ . '/../database/TransportData.php';
require_once __DIR__ . '/../database/Database.php';

// transit-edit_old.php
// This page will be used for adding/editing transit records. Currently empty, but includes a label for testing inclusion.
?>
<!DOCTYPE html>
<html lang="en">
<div class="container-xl px-1">
    <div class="page-header-content pt-4">
        <div class="row align-items-center justify-content-between">
            <h4>Transit Information</h4>
        </div>
    </div>
</div>
<!-- transit-edit_old.php: Pure view, expects $originLocations, $destinationLocations, $coroners, $pouchTypes, $originLocation, $destinationLocation, $coronerName, $transitPermitNumber, $tagNumber, $pouchType -->
<div id="transit-section">
    <?php if (isset($error) && $error === 'Please fill in all required fields.'): ?>
        <div class="alert alert-danger" role="alert">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>
    <table style="width:100%;">
        <tr>
            <td style="padding:10px;">
                <label for="origin_location" class="form-label required">Origin Location</label><br>
                <select id="origin_location" name="origin_location" class="form-control<?= isset($error) && strpos($error, 'origin_location') !== false ? ' is-invalid' : '' ?>" style="width:95%;" required>
                    <option value="">Select Origin Location</option>
                    <?php foreach ($originLocations as $origin): ?>
                        <option value="<?= htmlspecialchars($origin['id']) ?>" <?= (isset($originLocation) && $originLocation == $origin['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($origin['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (function_exists('render_invalid_feedback')) { render_invalid_feedback('Please fill out this field.', isset($error) && strpos($error, 'origin_location') !== false); } else { ?>
                    <div class="invalid-feedback">Please fill out this field.</div>
                <?php } ?>
            </td>
            <td style="padding:10px;">
                <label for="destination_location" class="form-label required">Destination Location</label><br>
                <select id="destination_location" name="destination_location" class="form-control<?= isset($error) && strpos($error, 'destination_location') !== false ? ' is-invalid' : '' ?>" style="width:95%;" required>
                    <option value="">Select Destination Location</option>
                    <?php foreach ($destinationLocations as $destination): ?>
                        <option value="<?= htmlspecialchars($destination['id']) ?>" <?= (isset($destinationLocation) && $destinationLocation == $destination['id']) ? 'selected' : '' ?> >
                            <?= htmlspecialchars($destination['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (function_exists('render_invalid_feedback')) { render_invalid_feedback('Please fill out this field.', isset($error) && strpos($error, 'destination_location') !== false); } else { ?>
                    <div class="invalid-feedback">Please fill out this field.</div>
                <?php } ?>
            </td>
            <td style="padding:10px;">
                <label for="coroner" class="form-label required">Coroner</label><br>
                <select id="coroner" name="coroner" class="form-control<?= isset($error) && strpos($error, 'coroner') !== false ? ' is-invalid' : '' ?>" style="width:95%;" required>
                    <option value="" <?= empty($coronerName) ? 'selected' : '' ?>>Select Coroner</option>
                    <?php foreach ($coroners as $coroner): ?>
                        <option value="<?= htmlspecialchars($coroner['id'] ?? '') ?>" <?= (isset($coronerName) && ($coronerName == ($coroner['id'] ?? '') || $coronerName === ($coroner['coroner_name'] ?? ''))) ? 'selected' : '' ?> >
                            <?= htmlspecialchars($coroner['coroner_name'] ?? '') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (function_exists('render_invalid_feedback')) { render_invalid_feedback('Please fill out this field.', isset($error) && strpos($error, 'coroner') !== false); } else { ?>
                    <div class="invalid-feedback">Please fill out this field.</div>
                <?php } ?>
            </td>
        </tr>
        <tr>
            <td style="padding:10px;">
                <label for="transit_permit_number" class="form-label">Transit Permit Number</label><br>
                <input type="text" id="transit_permit_number" name="transit_permit_number" class="form-control" style="width:95%;" value="<?= htmlspecialchars($transitPermitNumber ?? '') ?>">
            </td>
            <td style="padding:10px;">
                <label for="tag_number" class="form-label required">Tag Number</label><br>
                <input type="text" id="tag_number" name="tag_number" class="form-control<?= isset($error) && strpos($error, 'tag_number') !== false ? ' is-invalid' : '' ?>" style="width:95%;" value="<?= htmlspecialchars($tagNumber ?? '') ?>" required>
                <?php if (function_exists('render_invalid_feedback')) { render_invalid_feedback('Please fill out this field.', isset($error) && strpos($error, 'tag_number') !== false); } else { ?>
                    <div class="invalid-feedback">Please fill out this field.</div>
                <?php } ?>
            </td>
            <td style="padding:10px;">
                <label for="pouch_type" class="form-label required">Pouch Type</label><br>
                <select id="pouch_type" name="pouch_type" class="form-control<?= isset($error) && strpos($error, 'pouch_type') !== false ? ' is-invalid' : '' ?>" style="width:95%;" required>
                    <option value="" <?= empty($pouchType) ? 'selected' : '' ?>>Select Pouch Type</option>
                    <?php foreach ($pouchTypes as $pouch): ?>
                        <?php $type = $pouch['pouch_type'] ?? $pouch['type'] ?? $pouch; ?>
                        <option value="<?= htmlspecialchars($type) ?>" <?= (isset($pouchType) && $pouchType === $type) ? 'selected' : '' ?> >
                            <?= htmlspecialchars($type) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (function_exists('render_invalid_feedback')) { render_invalid_feedback('Please fill out this field.', isset($error) && strpos($error, 'pouch_type') !== false); } else { ?>
                    <div class="invalid-feedback">Please fill out this field.</div>
                <?php } ?>
            </td>
        </tr>
        <tr>
            <td style="padding:10px;">
                <label for="primary_transporter" class="form-label required">Primary Transporter</label><br>
                <select id="primary_transporter" name="primary_transporter" class="form-control<?= isset($error) && strpos($error, 'primary_transporter') !== false ? ' is-invalid' : '' ?>" style="width:95%;" required>
                    <option value="" <?= empty($primaryTransporter) ? 'selected' : '' ?>>Select Primary Transporter</option>
                    <?php foreach ($drivers as $driver): ?>
                        <option value="<?= htmlspecialchars($driver['id']) ?>" <?= (isset($primaryTransporter) && $primaryTransporter == $driver['id']) ? 'selected' : '' ?> >
                            <?= htmlspecialchars($driver['username']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (function_exists('render_invalid_feedback')) { render_invalid_feedback('Please fill out this field.', isset($error) && strpos($error, 'primary_transporter') !== false); } else { ?>
                    <div class="invalid-feedback">Please fill out this field.</div>
                <?php } ?>
            </td>
            <td style="padding:10px;">
                <label for="assistant_transporter" class="form-label">Assistant Transporter</label><br>
                <select id="assistant_transporter" name="assistant_transporter" class="form-control" style="width:95%;">
                    <option value="" <?= empty($assistantTransporter) ? 'selected' : '' ?>>None</option>
                    <?php foreach ($drivers as $driver): ?>
                        <option value="<?= htmlspecialchars($driver['id']) ?>" <?= (isset($assistantTransporter) && $assistantTransporter == $driver['id']) ? 'selected' : '' ?> >
                            <?= htmlspecialchars($driver['username']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </td>
            <td></td>
        </tr>
    </table>
    <div id="transporter-error" style="color:red; display:none; margin-top:10px;"></div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Find the parent form
    var transitSection = document.getElementById('transit-section');
    var form = transitSection.closest('form');
    if (!form) return;
    var primary = document.getElementById('primary_transporter');
    var assistant = document.getElementById('assistant_transporter');
    var errorDiv = document.getElementById('transporter-error');

    function validateTransporters(e) {
        if (primary.value && assistant.value && primary.value === assistant.value) {
            errorDiv.textContent = 'Primary Transporter and Assistant Transporter cannot be the same.';
            errorDiv.style.display = 'block';
            primary.classList.add('is-invalid');
            assistant.classList.add('is-invalid');
            if (e) e.preventDefault();
            return false;
        } else {
            errorDiv.textContent = '';
            errorDiv.style.display = 'none';
            primary.classList.remove('is-invalid');
            assistant.classList.remove('is-invalid');
            return true;
        }
    }

    // Validate on form submit
    form.addEventListener('submit', validateTransporters);
    // Validate on change
    primary.addEventListener('change', validateTransporters);
    assistant.addEventListener('change', validateTransporters);
});
</script>


</html>
