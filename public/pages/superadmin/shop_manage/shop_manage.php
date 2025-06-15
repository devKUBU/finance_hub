<?php
// File: public/pages/superadmin/shop_manage/shop_manage.php

require_once realpath(__DIR__ . '/../../../../config/bootstrap.php');
require_once ROOT_PATH    . '/includes/helpers.php';
if (session_status() === PHP_SESSION_NONE) session_start();
requireRole(['superadmin', 'admin']);


require_once ROOT_PATH . '/config/db.php';
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// — Handle delete action —
if (isset($_GET['action'], $_GET['id']) && $_GET['action'] === 'delete') {
    $id = (int)$_GET['id'];
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'shop'");
    $stmt->execute([$id]);
    header("Location: {$baseURL}/pages/superadmin/shop_manage/shop_manage.php");
    exit;
}

// — Fetch all shops —
$stmt  = $pdo->query("
    SELECT id,
           username,
           email,
           name       AS shop_name,
           is_active,
           created_at
      FROM users
     WHERE role = 'shop'
     ORDER BY created_at DESC
");
$shops = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'จัดการร้านค้า';
?>

<?php include ROOT_PATH . '/public/includes/header.php'; ?>
<?php include ROOT_PATH . '/public/includes/sidebar.php'; ?>
<link rel="stylesheet" href="<?= htmlspecialchars($baseURL) ?>/assets/css/dashboard.css">

<main class="main-content">
    <!-- Header -->
    <header class="app-header d-flex align-items-center justify-content-between">
        <h2 class="header-title m-0">
            <i class="fa-solid fa-store me-2"></i><?= htmlspecialchars($pageTitle) ?>
        </h2>
        <div class="header-actions d-flex align-items-center">
            <a href="<?= $baseURL ?>/pages/superadmin/shop_manage/shop_create.php" class="btn btn-sm btn-primary me-2">
                <i class="fa-solid fa-plus me-1"></i>เพิ่มร้านค้า
            </a>
            <button id="sidebarToggle" class="btn-icon" aria-label="Toggle sidebar">
                <i class="fa-solid fa-bars"></i>
            </button>
            <button id="themeToggle" class="btn-icon ms-2" aria-label="Toggle theme">
                <i id="themeIcon" class="fa-solid"></i>
            </button>
        </div>
    </header>
    <hr>

    <!-- Summary -->
    <div class="mb-3">
        <h5>
            <i class="fa-solid fa-store me-2"></i>
            จำนวนร้านค้า <?= count($shops) ?> รายการ
        </h5>
    </div>

    <!-- Search -->
    <div class="mb-3">
        <input type="text" id="searchInput" class="form-control" placeholder="ค้นหา ชื่อร้าน, ชื่อผู้ใช้, อีเมล์...">
    </div>

    <!-- Shops Table -->
    <div class="table-responsive">
        <table class="table-shop-manage align-middle mb-0">
            <thead>
                <tr>
                    <th>ลำดับ</th>
                    <th>รหัสร้านค้า</th>
                    <th>Username</th>
                    <th>ชื่อร้าน</th>
                    <th>สถานะ</th>
                    <th>วันที่สร้าง</th>
                    <th>จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($shops as $i => $shop): 
          // Format date to DD/MM/YYYY+543 HH:MM:SS
          $dt = new DateTime($shop['created_at'], new DateTimeZone('Asia/Bangkok'));
          $day   = $dt->format('d');
          $month = $dt->format('m');
          $year  = $dt->format('Y') + 543;
          $time  = $dt->format(' H:i:s');
          $code  = 'NNF-' . (1000 + $shop['id']);
        ?>
                <tr data-username="<?= htmlspecialchars(strtolower($shop['username'])) ?>"
                    data-shop-name="<?= htmlspecialchars(strtolower($shop['shop_name'])) ?>"
                    data-email="<?= htmlspecialchars(strtolower($shop['email'])) ?>">
                    <td></td>
                    <td><?= $code ?></td>
                    <td><?= htmlspecialchars($shop['username']) ?></td>
                    <td><?= htmlspecialchars($shop['shop_name']) ?></td>
                    <td>
                        <span class="badge <?= $shop['is_active'] ? 'bg-success' : 'bg-secondary' ?>">
                            <i class="fa-solid <?= $shop['is_active'] ? 'fa-check' : 'fa-ban' ?> me-1"></i>
                            <?= $shop['is_active'] ? 'Active' : 'Inactive' ?>
                        </span>
                    </td>
                    <td><?= "{$day}/{$month}/{$year}{$time}" ?></td>
                    <td>
                        <!-- Detail -->
                        <button class="btn btn-sm btn-outline-secondary me-1" data-bs-toggle="modal"
                            data-bs-target="#shopDetailModal" data-code="<?= $code ?>"
                            data-username="<?= htmlspecialchars($shop['username']) ?>"
                            data-email="<?= htmlspecialchars($shop['email']) ?>"
                            data-shopname="<?= htmlspecialchars($shop['shop_name']) ?>"
                            data-status="<?= $shop['is_active'] ? 'Active' : 'Inactive' ?>"
                            data-created="<?= "{$day}/{$month}/{$year}{$time}" ?>" title="ดูรายละเอียด">
                            <i class="fa-solid fa-eye"></i>
                        </button>

                        <!-- Edit -->
                        <button class="btn btn-sm btn-outline-primary me-1"
                            onclick="location.href='<?= $baseURL ?>/pages/superadmin/shop_manage/shop_edit.php?id=<?= $shop['id'] ?>'"
                            title="แก้ไข">
                            <i class="fa-solid fa-pen-to-square"></i>
                        </button>

                        <!-- Delete -->
                        <button class="btn btn-sm btn-outline-danger"
                            onclick="if(confirm('ยืนยันการลบร้านค้านี้?')) location.href='?action=delete&id=<?= $shop['id'] ?>'"
                            title="ลบ">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Detail Modal -->
    <div class="modal fade" id="shopDetailModal" tabindex="-1" aria-labelledby="shopDetailModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="shopDetailModalLabel">
                        <i class="fa-solid fa-store me-2"></i>รายละเอียดร้านค้า
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="ปิด"></button>
                </div>
                <div class="modal-body">
                    <dl class="row">
                        <dt class="col-sm-4">รหัสร้านค้า</dt>
                        <dd class="col-sm-8" id="modal-code"></dd>
                        <dt class="col-sm-4">Username</dt>
                        <dd class="col-sm-8" id="modal-username"></dd>
                        <dt class="col-sm-4">Email</dt>
                        <dd class="col-sm-8" id="modal-email"></dd>
                        <dt class="col-sm-4">ชื่อร้าน</dt>
                        <dd class="col-sm-8" id="modal-shopname"></dd>
                        <dt class="col-sm-4">สถานะ</dt>
                        <dd class="col-sm-8" id="modal-status"></dd>
                        <dt class="col-sm-4">วันที่สร้าง</dt>
                        <dd class="col-sm-8" id="modal-created"></dd>
                    </dl>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Pagination -->
    <nav id="pagination" class="mt-3"></nav>
</main>

<?php include ROOT_PATH . '/public/includes/footer.php'; ?>

<script>
// Sidebar & Theme toggle
document.getElementById('sidebarToggle').onclick = () =>
    document.body.classList.toggle('collapsed');

(function() {
    const btn = document.getElementById('themeToggle'),
        icon = document.getElementById('themeIcon'),
        root = document.documentElement;

    function updateIcon() {
        icon.className = root.getAttribute('data-theme') === 'dark' ?
            'fa-solid fa-sun' :
            'fa-solid fa-moon';
    }
    btn.onclick = () => {
        root.setAttribute('data-theme',
            root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark');
        updateIcon();
    };
    updateIcon();
})();

// Search & Pagination
document.addEventListener('DOMContentLoaded', () => {
    const tableBody = document.querySelector('.table-shop-manage tbody'),
        allRows = Array.from(tableBody.querySelectorAll('tr')),
        searchInput = document.getElementById('searchInput'),
        pagination = document.getElementById('pagination');
    const rowsPerPage = 20;
    let filteredRows = allRows.slice(),
        currentPage = 1;

    function renderTable() {
        tableBody.innerHTML = '';
        const start = (currentPage - 1) * rowsPerPage,
            slice = filteredRows.slice(start, start + rowsPerPage);
        slice.forEach((row, idx) => {
            row.children[0].textContent = start + idx + 1;
            tableBody.appendChild(row);
        });
        renderPagination();
    }

    function renderPagination() {
        pagination.innerHTML = '';
        const pageCount = Math.ceil(filteredRows.length / rowsPerPage);
        if (pageCount < 2) return;
        const ul = document.createElement('ul');
        ul.className = 'pagination';

        function makeItem(label, page, disabled = false, active = false) {
            const li = document.createElement('li');
            li.className = 'page-item' +
                (disabled ? ' disabled' : '') +
                (active ? ' active' : '');
            const a = document.createElement('a');
            a.className = 'page-link';
            a.href = '#';
            a.textContent = label;
            a.addEventListener('click', e => {
                e.preventDefault();
                if (!disabled && !active) {
                    currentPage = page;
                    renderTable();
                }
            });
            li.appendChild(a);
            return li;
        }

        ul.appendChild(makeItem('«', currentPage - 1, currentPage === 1));
        for (let i = 1; i <= pageCount; i++) {
            ul.appendChild(makeItem(i, i, false, i === currentPage));
        }
        ul.appendChild(makeItem('»', currentPage + 1, currentPage === pageCount));
        pagination.appendChild(ul);
    }

    function filterRows() {
        const q = searchInput.value.trim().toLowerCase();
        filteredRows = allRows.filter(row =>
            row.dataset.username.includes(q) ||
            row.dataset.shopName.includes(q) ||
            row.dataset.email.includes(q)
        );
        currentPage = 1;
        renderTable();
    }

    searchInput.addEventListener('input', filterRows);
    renderTable();
});

// Modal detail population
const detailModal = document.getElementById('shopDetailModal');
detailModal.addEventListener('show.bs.modal', event => {
    const btn = event.relatedTarget;
    document.getElementById('modal-code').textContent = btn.getAttribute('data-code');
    document.getElementById('modal-username').textContent = btn.getAttribute('data-username');
    document.getElementById('modal-email').textContent = btn.getAttribute('data-email');
    document.getElementById('modal-shopname').textContent = btn.getAttribute('data-shopname');
    document.getElementById('modal-status').textContent = btn.getAttribute('data-status');
    document.getElementById('modal-created').textContent = btn.getAttribute('data-created');
});
</script>