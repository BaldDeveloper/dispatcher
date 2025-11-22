<?php
/**
 * Coroner List Page
 *
 * - Displays a paginated list of coroners using CoronerService
 * - Output escaping for XSS prevention
 * - UI and pagination match project standards
 *
 * Last reviewed: 2025-10-09
 */
// Uncomment for production to enforce admin/staff access
// session_start();
// if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['admin', 'staff'])) {
//     header('Location: login.php');
//     exit;
// }

require_once __DIR__ . '/../database/Database.php';
require_once __DIR__ . '/../services/CoronerService.php';

$coronerService = new CoronerService(new Database());

// Pagination and search setup
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$pageSize = isset($_GET['pageSize']) ? max(1, intval($_GET['pageSize'])) : 10;
$offset = ($page - 1) * $pageSize;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

if ($search !== '') {
    $totalCoroners = $coronerService->getCountBySearch($search);
    $coroners = $coronerService->searchPaginated($search, $pageSize, $offset) ?? [];
} else {
    $totalCoroners = $coronerService->getCount();
    $coroners = $coronerService->getPaginated($pageSize, $offset) ?? [];
}

$totalPages = $pageSize > 0 ? (int)ceil($totalCoroners / $pageSize) : 1;

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="" />
    <meta name="author" content="" />
    <title>Coroner List - DispatchBase</title>
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
                        <div class="row align-items-center justify-content-between"></div>
                    </div>
                </div>
            </header>
            <div class="container-xl px-4 mt-n-custom-6">
                <div id="default">
                    <div class="card mb-4 w-100">
                        <div class="card-header">Coroner List</div>
                        <div class="card-body">
                            <!-- Data table for coroners. For large datasets, use AJAX/DataTables for pagination/filtering. -->
                            <div class="dataTables_wrapper dt-bootstrap5">
                                <div class="row mb-3">
                                    <div class="col-sm-12 col-md-6">
                                        <form method="get" class="d-inline">
                                            Show
                                            <select name="pageSize" aria-controls="entries" class="form-select form-select-sm" onchange="this.form.submit()">
                                                <option value="10"<?= $pageSize == 10 ? ' selected' : '' ?>>10</option>
                                                <option value="25"<?= $pageSize == 25 ? ' selected' : '' ?>>25</option>
                                                <option value="50"<?= $pageSize == 50 ? ' selected' : '' ?>>50</option>
                                                <option value="100"<?= $pageSize == 100 ? ' selected' : '' ?>>100</option>
                                            </select>
                                        </form>
                                    </div>
                                    <div class="col-sm-12 col-md-6 text-end">
                                        <form method="get" class="d-inline">
                                            <label>
                                                Search:
                                                <input type="search" name="search" class="form-control form-control-sm" placeholder="Coroner name" aria-controls="entries" value="<?= htmlspecialchars($search) ?>">
                                            </label>
                                            <input type="hidden" name="pageSize" value="<?= $pageSize ?>">
                                            <button type="submit" class="btn btn-sm btn-primary ms-1">Search</button>
                                        </form>
                                    </div>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover mb-0">
                                        <thead class="table-light">
                                        <tr>
                                            <th>Coroner ID</th>
                                            <th>Name</th>
                                            <th>Phone</th>
                                            <th>Email</th>
                                            <th>County</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <?php foreach ($coroners as $coroner): ?>
                                            <tr>
                                                <td><a href="coroner-edit.php?mode=edit&id=<?= htmlspecialchars($coroner['id'] ?? '') ?>"><?= htmlspecialchars($coroner['id'] ?? '') ?></a></td>
                                                <td><?= htmlspecialchars($coronerService->formatDisplayName($coroner)) ?></td> <!-- Use service for display name -->
                                                <td><?= htmlspecialchars($coroner['phone_number'] ?? '') ?></td>
                                                <td><?= htmlspecialchars($coroner['email_address'] ?? '') ?></td>
                                                <td><?= htmlspecialchars($coroner['county'] ?? '') ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                        <?php if (empty($coroners)): ?>
                                            <tr>
                                                <td colspan="5" class="text-danger">No coroners found.</td>
                                            </tr>
                                        <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <!-- Pagination controls -->
                                <nav aria-label="Coroner list pagination" class="mt-3">
                                    <ul class="pagination justify-content-center">
                                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                            <li class="page-item<?= $i == $page ? ' active' : '' ?>">
                                                <a class="page-link" href="?page=<?= $i ?>&pageSize=<?= $pageSize ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
                                            </li>
                                        <?php endfor; ?>
                                    </ul>
                                </nav>
                            </div> <!-- /.dataTables_wrapper -->
                        </div> <!-- /.card-body -->
                    </div> <!-- /.card -->
                </div> <!-- /#default -->
            </div> <!-- /.container-xl -->
        </main>
        <div id="footer"></div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
<script src="js/scripts.js"></script>
<script src="js/phone-format.js"></script>
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
