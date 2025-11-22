<?php
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

require_once __DIR__ . '/../database/Database.php';
require_once __DIR__ . '/../services/PouchService.php';

$db = new Database();
$pouchService = new PouchService($db);

// Pagination setup
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$pageSize = isset($_GET['pageSize']) ? max(1, intval($_GET['pageSize'])) : 10;
$offset = ($page - 1) * $pageSize;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
if ($search !== '') {
    $totalPouches = $pouchService->getCountBySearch($search);
    $pouches = $pouchService->searchPaginated($search, $pageSize, $offset) ?? [];
} else {
    $totalPouches = $pouchService->getCount();
    $pouches = $pouchService->getPaginated($pageSize, $offset) ?? [];
}
$totalPages = $pageSize > 0 ? (int)ceil($totalPouches / $pageSize) : 1;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="" />
    <meta name="author" content="" />
    <title>Pouch Types - DispatchBase</title>
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
                <div id="default">
                    <div class="card mb-4 w-100">
                        <div class="card-header">Pouch Types</div>
                        <div class="card-body">
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
                                                <input type="search" name="search" class="form-control form-control-sm" placeholder="Pouch type" aria-controls="entries" value="<?= htmlspecialchars($search) ?>">
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
                                            <th>ID</th>
                                            <th>Pouch Type</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <?php foreach ($pouches as $pouch): ?>
                                            <tr>
                                                <td><a href="pouch-edit.php?mode=edit&id=<?= urlencode($pouch['id']) ?>"><?= htmlspecialchars($pouch['id']) ?></a></td>
                                                <td><?= htmlspecialchars($pouch['pouch_type'] ?? '') ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                        <?php if (empty($pouches)): ?>
                                            <tr>
                                                <td colspan="2" class="text-danger">No pouch types found.</td>
                                            </tr>
                                        <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <!-- Pagination controls -->
                                <nav aria-label="Pouch list pagination" class="mt-3">
                                    <ul class="pagination justify-content-center">
                                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                            <li class="page-item<?= $i == $page ? ' active' : '' ?>">
                                                <a class="page-link" href="?page=<?= $i ?>&pageSize=<?= $pageSize ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
                                            </li>
                                        <?php endfor; ?>
                                    </ul>
                                </nav>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
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
