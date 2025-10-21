<?php
// charges-edit.php
// This file is a fragment included by transport-edit.php for editing/adding transport charges.

// Ensure variables are defined to avoid undefined variable notices when included
$removal_charge = $removal_charge ?? '';
$pouch_charge = $pouch_charge ?? '';
$transport_fees = $transport_fees ?? '';
$wait_charge = $wait_charge ?? '';
$mileage_fees = $mileage_fees ?? '';
$other_charge_1 = $other_charge_1 ?? '';
$other_charge_2 = $other_charge_2 ?? '';
$other_charge_3 = $other_charge_3 ?? '';
$other_charge_4 = $other_charge_4 ?? '';
$other_charge_1_description = $other_charge_1_description ?? '';
$other_charge_2_description = $other_charge_2_description ?? '';
$other_charge_3_description = $other_charge_3_description ?? '';
$other_charge_4_description = $other_charge_4_description ?? '';
$total_charge = $total_charge ?? '';
?>

<div id="charges-section" class="mb-3">
  <div class="container-xl px-1">
    <div class="page-header-content pt-4">
      <div class="row align-items-center justify-content-between">
        <h4>Transport Charges</h4>
      </div>
    </div>

    <div class="table-responsive">
      <div class="row gx-3 gy-2">
        <div class="col-md-4">
          <label for="removal_charge" class="form-label">Removal Charge</label>
          <input type="number" inputmode="decimal" name="removal_charge" id="removal_charge"
            class="form-control charge-input" step="0.01" min="0"
            value="<?= htmlspecialchars(number_format((float)$removal_charge, 2, '.', ''), ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div class="col-md-4">
          <label for="pouch_charge" class="form-label">Pouch Charge</label>
          <input type="number" inputmode="decimal" name="pouch_charge" id="pouch_charge"
            class="form-control charge-input" step="0.01" min="0"
            value="<?= htmlspecialchars(number_format((float)$pouch_charge, 2, '.', ''), ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div class="col-md-4">
          <label for="transport_fees" class="form-label">Transport Fees</label>
          <input type="number" inputmode="decimal" name="transport_fees" id="transport_fees"
            class="form-control charge-input" step="0.01" min="0"
            value="<?= htmlspecialchars(number_format((float)$transport_fees, 2, '.', ''), ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div class="col-md-4">
          <label for="wait_charge" class="form-label">Wait Charge</label>
          <input type="number" inputmode="decimal" name="wait_charge" id="wait_charge"
            class="form-control charge-input" step="0.01" min="0"
            value="<?= htmlspecialchars(number_format((float)$wait_charge, 2, '.', ''), ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div class="col-md-4">
          <label for="mileage_fees" class="form-label">Mileage Fees</label>
          <input type="number" inputmode="decimal" name="mileage_fees" id="mileage_fees"
            class="form-control charge-input" step="0.01" min="0"
            value="<?= htmlspecialchars(number_format((float)$mileage_fees, 2, '.', ''), ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div class="col-md-4"></div>

        <div class="col-md-4">
          <label for="other_charge_1" class="form-label">Other Charge 1</label>
          <input type="number" inputmode="decimal" name="other_charge_1" id="other_charge_1"
            class="form-control charge-input" step="0.01" min="0"
            value="<?= htmlspecialchars(number_format((float)$other_charge_1, 2, '.', ''), ENT_QUOTES, 'UTF-8') ?>">
          <input type="text" name="other_charge_1_description" id="other_charge_1_description"
            class="form-control mt-1" placeholder="Description"
            value="<?= htmlspecialchars($other_charge_1_description, ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div class="col-md-4">
          <label for="other_charge_2" class="form-label">Other Charge 2</label>
          <input type="number" inputmode="decimal" name="other_charge_2" id="other_charge_2"
            class="form-control charge-input" step="0.01" min="0"
            value="<?= htmlspecialchars(number_format((float)$other_charge_2, 2, '.', ''), ENT_QUOTES, 'UTF-8') ?>">
          <input type="text" name="other_charge_2_description" id="other_charge_2_description"
            class="form-control mt-1" placeholder="Description"
            value="<?= htmlspecialchars($other_charge_2_description, ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div class="col-md-4">
          <label for="other_charge_3" class="form-label">Other Charge 3</label>
          <input type="number" inputmode="decimal" name="other_charge_3" id="other_charge_3"
            class="form-control charge-input" step="0.01" min="0"
            value="<?= htmlspecialchars(number_format((float)$other_charge_3, 2, '.', ''), ENT_QUOTES, 'UTF-8') ?>">
          <input type="text" name="other_charge_3_description" id="other_charge_3_description"
            class="form-control mt-1" placeholder="Description"
            value="<?= htmlspecialchars($other_charge_3_description, ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div class="col-md-4">
          <label for="other_charge_4" class="form-label">Other Charge 4</label>
          <input type="number" inputmode="decimal" name="other_charge_4" id="other_charge_4"
            class="form-control charge-input" step="0.01" min="0"
            value="<?= htmlspecialchars(number_format((float)$other_charge_4, 2, '.', ''), ENT_QUOTES, 'UTF-8') ?>">
          <input type="text" name="other_charge_4_description" id="other_charge_4_description"
            class="form-control mt-1" placeholder="Description"
            value="<?= htmlspecialchars($other_charge_4_description, ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div class="col-md-4">
          <label for="total_charge" class="form-label">Total Charge</label>
          <input type="number" inputmode="decimal" name="total_charge" id="total_charge"
            class="form-control" step="0.01" min="0" readonly
            value="<?= htmlspecialchars(number_format((float)$total_charge, 2, '.', ''), ENT_QUOTES, 'UTF-8') ?>">
        </div>
      </div>
    </div>
  </div>
</div>
