<?php
/**
 * Vehicle List Page
 *
 * - Lists all vehicles with pagination, page size selector, and Bootstrap table
 * - Vehicle ID is a hyperlink to vehicle-edit.php
 * - Columns: Vehicle ID, Year, Make, Model, Color
 * - UI and logic matches customer-list.php and other list pages
 */

require_once __DIR__ . '/../database/Database.php';
require_once __DIR__ . '/../database/VehicleData.php';
require_once __DIR__ . '/../includes/table_helpers.php';

$db = new Database();
$vehicleData = new VehicleData($db);

// Pagination logic (matches customer-list.php)
$page = isset($_GET['page']) && ctype_digit($_GET['page']) && $_GET['page'] > 0 ? (int)$_GET['page'] : 1;
$pageSize = isset($_GET['pageSize']) && ctype_digit($_GET['pageSize']) && $_GET['pageSize'] > 0 ? (int)$_GET['pageSize'] : 10;
$offset = ($page - 1) * $pageSize;

// Fetch total count and vehicles for current page
$totalVehicles = $vehicleData->getCount();
$vehicles = $vehicleData->getAll($offset, $pageSize);
$totalPages = $totalVehicles > 0 ? (int)ceil($totalVehicles / $pageSize) : 1;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="" />
    <meta name="author" content="" />
    <title>Vehicles - DispatchBase</title>
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
                        <div class="row align-items-center justify-content-between">
                        </div>
                    </div>
                </div>
            </header>
            <!-- Main page content-->
            <div class="container-xl px-4 mt-n-custom-6">
                <div class="card mb-4 w-100">
                    <div class="card-header">Vehicles</div>
                    <div class="card-body">
                        <form method="get" class="mb-3 d-flex align-items-center">
                            <label for="pageSize" class="me-2 mb-0">Page size:</label>
                            <select name="pageSize" id="pageSize" class="form-select w-auto me-2" onchange="this.form.submit()">
                                <option value="10"<?= $pageSize == 10 ? ' selected' : '' ?>>10</option>
                                <option value="25"<?= $pageSize == 25 ? ' selected' : '' ?>>25</option>
                                <option value="50"<?= $pageSize == 50 ? ' selected' : '' ?>>50</option>
                                <option value="100"<?= $pageSize == 100 ? ' selected' : '' ?>>100</option>
                            </select>
                        </form>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Vehicle ID</th>
                                        <th>Year</th>
                                        <th>Make</th>
                                        <th>Model</th>
                                        <th>Color</th>
                                        <th>Insurance Expiry</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php if (empty($vehicles)): ?>
                                    <tr><td colspan="6" class="text-center">No vehicles found.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($vehicles as $vehicle): ?>
                                        <tr>
                                            <td><a href="vehicle-edit.php?id=<?= urlencode($vehicle['id']) ?>&mode=edit"><?= htmlspecialchars($vehicle['id']) ?></a></td>
                                            <td><?= htmlspecialchars($vehicle['year']) ?></td>
                                            <td><?= htmlspecialchars($vehicle['make']) ?></td>
                                            <td><?= htmlspecialchars($vehicle['model']) ?></td>
                                            <td><?= htmlspecialchars($vehicle['color']) ?></td>
                                            <td><?= isset($vehicle['insurance_expiry']) ? htmlspecialchars($vehicle['insurance_expiry']) : '' ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php
                        // Use the correct pagination helper function
                        $baseUrl = strtok($_SERVER['REQUEST_URI'], '?');
                        $query = $_GET;
                        unset($query['page'], $query['pageSize']);
                        $baseUrl .= (count($query) ? '?' . http_build_query($query) . '&' : '?');
                        render_pagination($page, $totalPages, $pageSize, $baseUrl);
                        ?>
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
</body>
</html>
