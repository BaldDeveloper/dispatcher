<?php
// decedent-edit.php
// This page will be used for adding/editing decedent records.
$ethnicities = include __DIR__ . '/../includes/ethnicities.php';
$genders = include __DIR__ . '/../includes/genders.php';
require_once __DIR__ . '/../database/Database.php';
require_once __DIR__ . '/../services/DecedentService.php';
?>
<!DOCTYPE html>
<html lang="en">
<div class="container-xl px-1">
    <div class="page-header-content pt-4">
        <div class="row align-items-center justify-content-between">
            <h4>Decedent Information</h4>
        </div>
    </div>
</div>
<div id="decedent-section">
    <table style="width:100%;">
        <tr>
            <td style="padding:10px;">
                <label for="first_name" class="form-label required">First Name</label><br>
                <input type="text" id="first_name" name="first_name" class="form-control" required style="width:95%;"
                       value="<?= htmlspecialchars($decedentFirstName ?? '') ?>">
                <div class="invalid-feedback">Please fill out this field.</div>
            </td>
            <td style="padding:10px;">
                <label for="last_name" class="form-label required">Last Name</label><br>
                <input type="text" id="last_name" name="last_name" class="form-control" required style="width:95%;"
                       value="<?= htmlspecialchars($decedentLastName ?? '') ?>">
                <div class="invalid-feedback">Please fill out this field.</div>
            </td>
        </tr>
        <tr>
            <td style="padding:10px;">
                <label for="ethnicity" class="form-label required">Ethnicity</label><br>
                <select id="ethnicity" name="ethnicity" class="form-control" required style="width:95%;">
                    <option value="" <?= empty($decedentEthnicity) ? 'selected' : '' ?>>Select Ethnicity</option>
                    <?php foreach ($ethnicities as $ethnicity): ?>
                        <option value="<?= htmlspecialchars($ethnicity) ?>" <?= (isset($decedentEthnicity) && $decedentEthnicity === $ethnicity) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($ethnicity) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="invalid-feedback">Please fill out this field.</div>
            </td>
            <td style="padding:10px;">
                <label for="gender" class="form-label required">Gender</label><br>
                <select id="gender" name="gender" class="form-control" required style="width:95%;">
                    <option value="" <?= empty($decedentGender) ? 'selected' : '' ?>>Select Gender</option>
                    <?php foreach ($genders as $gender): ?>
                        <option value="<?= htmlspecialchars($gender) ?>" <?= (isset($decedentGender) && $decedentGender === $gender) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($gender) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="invalid-feedback">Please fill out this field.</div>
            </td>
        </tr>
    </table>
    <!-- Add other fields as needed -->
</div>

</html>
