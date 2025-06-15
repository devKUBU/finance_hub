<?php
// File: public/pages/shop/income/profit.php

// 1) โหลด bootstrap & helpers ให้ถูก path
require_once __DIR__ . '/../../../../config/bootstrap.php';
require_once ROOT_PATH    . '/includes/helpers.php';

if (session_status() === PHP_SESSION_NONE) session_start();
requireRole(['shop']);

// 2) โหลด DB
require_once ROOT_PATH . '/config/db.php';
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$shopId = (int)$_SESSION['user']['id'];

// 3.1) ยอดรวมคอมมิชชั่น
$totalStmt = $pdo->prepare("
  SELECT COALESCE(SUM(commission_amount),0)
    FROM contracts
   WHERE shop_id = ?
     AND commission_amount IS NOT NULL
");
$totalStmt->execute([$shopId]);
$totalCommission = (float)$totalStmt->fetchColumn();

// 3.2) รายการคอมมิชชั่น
$stmt = $pdo->prepare("
  SELECT
    id               AS contract_id,
    contract_no_shop AS contract_no,
    customer_firstname,
    customer_lastname,
    commission_amount,
    commission_transferred_at,
    commission_slip_path
  FROM contracts
  WHERE shop_id = ?
    AND commission_amount IS NOT NULL
  ORDER BY commission_transferred_at DESC
");
$stmt->execute([$shopId]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'รายได้คอมมิชชั่นของฉัน';
?>

<?php include ROOT_PATH . '/public/includes/header.php'; ?>
<?php include ROOT_PATH . '/public/includes/sidebar.php'; ?>

<!-- 4) ดึง CSS ที่ใช้กับ dashboard ทุกหน้า (toggle, table, cards) -->
<link rel="stylesheet" href="<?= htmlspecialchars($baseURL) ?>/assets/css/dashboard.css">


<main class="main-content">
    <!-- 5) Header รองรับ toggle sidebar & theme -->
    <header class="app-header d-flex align-items-center justify-content-between">
        <h2 class="header-title m-0">
            <i class="fa-solid fa-coins me-2"></i><?= htmlspecialchars($pageTitle) ?>
        </h2>
        <div class="header-actions d-flex align-items-center">
            <button id="sidebarToggle" class="btn-icon" aria-label="Toggle sidebar">
                <i class="fa-solid fa-bars"></i>
            </button>
            <button id="themeToggle" class="btn-icon ms-2" aria-label="Toggle theme">
                <i id="themeIcon" class="fa-solid"></i>
            </button>
        </div>
    </header>
    <hr>

    <div class="container-fluid">
        <div class="row mb-4">
            <div class="col-12 col-md-12">
                <div class="card shadow-sm summary-card">
                    <div class="card-body d-flex align-items-center">
                        <i class="fa-solid fa-baht-sign fa-3x text-success me-3"></i>
                        <div>
                            <h6 class="mb-1">ยอดรวมคอมมิชชั่นที่โอนแล้ว</h6>
                            <h2 class="m-0"><?= number_format($totalCommission,2) ?> ฿</h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- ตารางประวัติคอมมิชชั่น -->
        <div class="card">
            <div class="card-header">ประวัติคอมมิชชั่น</div>
            <div class="card-body table-responsive">
                <table class="table mb-0 text-center align-middle" id="profitsTable">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>เลขที่สัญญา</th>
                            <th>ชื่อลูกค้า</th>
                            <th class="text-end">คอมมิชชั่น (฿)</th>
                            <th>โอนเมื่อ</th>
                            <th>สลิป</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($rows as $i => $r): ?>
                        <tr>
                            <td><?= $i+1 ?></td>
                            <td><?= htmlspecialchars($r['contract_no']) ?></td>
                            <td><?= htmlspecialchars($r['customer_firstname'].' '.$r['customer_lastname']) ?></td>
                            <td class="text-end"><?= number_format($r['commission_amount'],2) ?></td>
                            <td><?= date('d/m/'.(date('Y',strtotime($r['commission_transferred_at']))+543).' H:i:s',
                          strtotime($r['commission_transferred_at'])) ?></td>
                            <td>
                                <?php if ($r['commission_slip_path']): ?>
                                <img src="<?= htmlspecialchars($baseURL . '/' . ltrim($r['commission_slip_path'],'/')) ?>"
                                    class="thumb-img"
                                    data-full="<?= htmlspecialchars($baseURL . '/' . ltrim($r['commission_slip_path'],'/')) ?>"
                                    title="คลิกดูสลิป">
                                <?php else: ?>
                                –
                                <?php endif; ?>
                            </td>

                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>
<style>
/* ปรับ thumbnail */
.thumb-img {
    width: 48px;
    height: 48px;
    object-fit: cover;
    border-radius: .25rem;
    cursor: pointer;
}

/* Lightbox */
#lightboxOverlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.8);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 2000;
}

#lightboxOverlay img {
    max-width: 90%;
    max-height: 85%;
    border-radius: .5rem;
}
</style>

<!-- Lightbox Overlay -->
<div id="lightboxOverlay" onclick="hideLightbox()">
    <img id="lightboxImg" src="" alt="Slip Preview">
</div>

<script>
// Lightbox functions
function hideLightbox() {
    document.getElementById('lightboxOverlay').style.display = 'none';
}
document.querySelectorAll('.thumb-img').forEach(img => {
    img.addEventListener('click', e => {
        const url = e.target.dataset.full;
        const lb = document.getElementById('lightboxOverlay');
        document.getElementById('lightboxImg').src = url;
        lb.style.display = 'flex';
    });
});
</script>
<?php include ROOT_PATH . '/public/includes/footer.php'; ?>
<!-- 6) JS Toggle & DataTables -->
<script>
// sidebar & theme toggle (copy จาก list-contracts)
document.getElementById('sidebarToggle').onclick = () =>
    document.body.classList.toggle('collapsed');

(function() {
    const btn = document.getElementById('themeToggle'),
        icon = document.getElementById('themeIcon'),
        root = document.documentElement;

    function updateIcon() {
        icon.className = root.getAttribute('data-theme') === 'dark' ?
            'fa-solid fa-sun' : 'fa-solid fa-moon';
    }
    btn.onclick = () => {
        root.setAttribute('data-theme',
            root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark');
        updateIcon();
    };
    updateIcon();
})();

// DataTables init
document.addEventListener('DOMContentLoaded', () => {
    if (window.jQuery && $.fn.DataTable) {
        $('#profitsTable').DataTable({
            order: [
                [4, 'desc']
            ],
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/th.json'
            }
        });
    }
});
</script>