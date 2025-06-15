<?php
// -----------------------------------------------------------------------------
// File: public/pages/superadmin/dashboard.php (Theme-Aware Final Version)
// -----------------------------------------------------------------------------
require_once realpath(__DIR__.'/../../../config/bootstrap.php');
require_once ROOT_PATH.'/includes/helpers.php';
if (session_status()===PHP_SESSION_NONE) session_start();
requireRole(['superadmin', 'admin']);
require_once ROOT_PATH.'/config/db.php';
$pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
date_default_timezone_set('Asia/Bangkok');
$today=date('Y-m-d');

/*━━━━━━━━━━ 1) ดึงข้อมูลสรุปการเงินแบบละเอียด ━━━━━━━━━━*/
$stmt = $pdo->prepare("SELECT COALESCE(SUM(p.amount_due - IFNULL(p.amount_paid,0)),0) FROM payments p WHERE p.status='pending' AND DATE(p.due_date) < ?");
$stmt->execute([$today]);
$overdueAmount = (float)$stmt->fetchColumn();
$stmt = $pdo->prepare("SELECT COALESCE(SUM(p.amount_due - IFNULL(p.amount_paid,0)),0) FROM payments p WHERE p.status = 'pending' AND DATE(p.due_date) = ?");
$stmt->execute([$today]);
$dueTodayAmount = (float)$stmt->fetchColumn();
$stmt = $pdo->prepare("SELECT COALESCE(SUM(p.amount_due - IFNULL(p.amount_paid,0)),0) FROM payments p WHERE p.status='pending' AND DATE(p.due_date) BETWEEN DATE_ADD(?, INTERVAL 1 DAY) AND DATE_ADD(?, INTERVAL 7 DAY)");
$stmt->execute([$today, $today]);
$dueNext7Amount = (float)$stmt->fetchColumn();
$totalPrincipal = (float)$pdo->query("SELECT COALESCE(SUM(c.loan_amount),0) FROM contracts c WHERE c.approval_status='approved'")->fetchColumn();
$totalDue = (float)$pdo->query("SELECT COALESCE(SUM(p.amount_due),0) FROM payments p JOIN contracts c ON c.id=p.contract_id WHERE c.approval_status='approved'")->fetchColumn();
$totalIncome = (float)$pdo->query("SELECT COALESCE(SUM(IFNULL(p.amount_paid,0) + IFNULL(p.penalty_amount,0) + IFNULL(p.fee_unlock,0) + IFNULL(p.fee_document,0) + IFNULL(p.fee_other,0)),0) FROM payments p JOIN contracts c ON c.id=p.contract_id WHERE c.approval_status='approved'")->fetchColumn();
$totalCommission = (float)$pdo->query("SELECT COALESCE(SUM(c.commission_amount),0) FROM contracts c WHERE c.approval_status='approved'")->fetchColumn();
$totalOtherCost = (float)$pdo->query("SELECT COALESCE(SUM(e.amount),0) FROM expenses e")->fetchColumn();
$totalCost = $totalPrincipal + $totalCommission + $totalOtherCost;
$profit = $totalIncome - $totalCost;
$isNegative   = $profit < 0;
$alertClass   = $isNegative ? 'loss' : 'profit';
$messageLabel = $isNegative ? 'พอร์ตติดลบ' : 'พอร์ตเติบโต';
$iconClass    = $isNegative ? 'fa-arrow-down' : 'fa-arrow-up';
$messageMain  = $isNegative ? 'ขยันตามเก็บเงินนะคะ พอร์ตติดลบอยู่' : 'ยอดเยี่ยม! รักษามาตรฐานไว้นะคะ';

/*━━━━━━━━━━ 2) กราฟสัญญารายเดือน ━━━━━━━━━━*/
$chart = $pdo->query("SELECT DATE_FORMAT(created_at,'%Y-%m') ym,COUNT(*) cnt FROM contracts WHERE YEAR(created_at)=YEAR(CURDATE()) GROUP BY ym ORDER BY ym")->fetchAll(PDO::FETCH_ASSOC);
$chartLabels = json_encode(array_column($chart,'ym'),JSON_UNESCAPED_UNICODE);
$chartData   = json_encode(array_column($chart,'cnt'),JSON_NUMERIC_CHECK);

/*━━━━━━━━━━ 3) ตารางงวดถัดไป ━━━━━━━━━━*/
$nextSql=<<<SQL
SELECT c.id contract_id, c.contract_no_shop contract_no,
       CONCAT(c.customer_firstname,' ',c.customer_lastname) customer_name,
       COALESCE(pn.pay_no,pp.pay_no)           next_pay_no,
       DATE(COALESCE(pn.due_date,pp.due_date)) next_due_date,
       COALESCE(pn.amount_due,pp.amount_due)   next_amount_due,
       CASE WHEN pn.id IS NOT NULL THEN pn.status ELSE pp.status END next_status
FROM contracts c
LEFT JOIN payments pn ON pn.id=(SELECT p.id FROM payments p WHERE p.contract_id=c.id AND p.status='pending' ORDER BY (DATE(p.due_date)<CURDATE()) DESC,p.due_date ASC LIMIT 1)
LEFT JOIN payments pp ON pp.id=(SELECT p2.id FROM payments p2 WHERE p2.contract_id=c.id AND p2.status='paid' ORDER BY p2.pay_no DESC LIMIT 1)
WHERE c.approval_status='approved' AND (pn.id IS NOT NULL OR pp.id IS NOT NULL)
ORDER BY CASE WHEN pn.status='pending' AND DATE(pn.due_date)<CURDATE() THEN 1
              WHEN pn.status='pending' AND DATE(pn.due_date)=CURDATE() THEN 2
              WHEN pn.status='pending' AND DATE(pn.due_date) BETWEEN DATE_ADD(CURDATE(),INTERVAL 1 DAY) AND DATE_ADD(CURDATE(),INTERVAL 7 DAY) THEN 3
              WHEN pn.status='pending' THEN 4 ELSE 5 END,
       COALESCE(pn.due_date,pp.due_date)
SQL;
$contracts=$pdo->query($nextSql)->fetchAll(PDO::FETCH_ASSOC);

/*━━━━━━━━━━ 4) Render ━━━━━━━━━━*/
$pageTitle='แดชบอร์ด';
include ROOT_PATH.'/public/includes/header.php';
include ROOT_PATH.'/public/includes/sidebar.php';
?>

<link rel="stylesheet" href="<?= $baseURL ?>/assets/css/dashboard.css">
<link rel="stylesheet" href="<?= $baseURL ?>/assets/css/manage_payments.css">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">

<style>
.stats-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1rem;
}

.stat-box {
    /* ใช้ Bootstrap CSS Variables เพื่อให้ปรับตามธีม */
    background-color: var(--bs-secondary-bg);
    /* สำหรับพื้นหลังของกล่อง stat-box */
    border: 1px solid var(--bs-border-color-translucent);
    /* สีขอบที่ปรับตามธีม */
    border-radius: .75rem;
    padding: 1.25rem 1rem;
    text-align: center;
    transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out, background-color 0.2s ease-in-out;
    /* เพิ่ม transition สำหรับ background-color */
}

/* ปรับสีพื้นหลังของ stat-box สำหรับ Dark Mode โดยเฉพาะ */
html[data-bs-theme='dark'] .stat-box {
    background-color: var(--bs-body-bg);
    /* ใช้สีพื้นหลังหลักของ body ใน Dark Mode เพื่อความกลมกลืน */
    border-color: rgba(255, 255, 255, 0.15);
    /* ทำให้ขอบดูชัดขึ้นเล็กน้อยใน Dark Mode */
}


.stat-box:hover {
    transform: translateY(-4px);
    box-shadow: 0 .5rem 1rem rgba(0, 0, 0, .07);
    background-color: var(--bs-tertiary-bg);
    /* สีพื้นหลังเมื่อ hover (ปรับตามธีมแล้ว) */
}

.stat-box .icon {
    margin-bottom: 0.75rem;
    /* เพิ่มความสูงคงที่เพื่อจัดตำแหน่งไอคอนให้สวยงาม */
    height: 2rem;
    display: flex;
    align-items: center;
    justify-content: center;
}

.stat-box .icon i {
    font-size: 2rem;
    /* ขนาดไอคอน */
    /* สีไอคอนจะถูกกำหนดจาก class เช่น text-danger-emphasis */
}

.stat-box .label {
    font-size: 0.75rem;
    /* ขนาดป้ายกำกับ */
    font-weight: 600;
    text-transform: uppercase;
    margin-bottom: 0.25rem;
    color: var(--bs-secondary-color);
    /* ใช้สีรองของธีม */
}

.stat-box .value {
    font-size: 1.4rem;
    /* ขนาดตัวเลข */
    font-weight: 700;
    line-height: 1.2;
    color: var(--bs-body-color);
    /* ใช้สีหลักของธีม */
}
</style>
<main class="main-content">
    <div class="container-fluid py-4">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="m-0"><i class="fa-solid fa-gauge-high me-2"></i><?= $pageTitle ?></h3>
            <div class="header-actions d-flex align-items-center">
                <!-- <a href="#" class="btn btn-outline-primary btn-sm d-none d-md-block">
                    <i class="fa-solid fa-file-pdf me-1"></i>Export PDF
                </a> -->
                <button id="showShopsBtn" class="btn-icon text-primary ms-3" data-bs-toggle="modal"
                    data-bs-target="#shopsModal" title="แสดงแผนที่ร้านค้า">
                    <i class="fa-solid fa-map-location-dot"></i>
                </button>
                <button id="sidebarToggle" class="btn-icon ms-3"><i class="fa-solid fa-bars"></i></button>
                <button id="themeToggle" class="btn-icon ms-2"><i id="themeIcon" class="fa-solid"></i></button>
            </div>
        </div>
        <hr class="mb-4">

        <div class="profit-banner <?= $alertClass ?> mb-4">
            <div class="banner-content">
                <i class="fa-solid <?= $iconClass ?> banner-icon"></i>
                <div class="banner-text">
                    <div class="banner-label"><?= $messageLabel ?></div>
                    <div class="banner-value"><?= number_format($profit,2) ?> ฿</div>
                    <div class="banner-msg"><?= $messageMain ?></div>
                </div>
                <img src="<?= $baseURL ?>/assets/images/<?= $isNegative ? 'cartoon-sad.png' : 'cartoon-happy.png' ?>"
                    alt="cartoon" class="banner-cartoon">
            </div>
        </div>

        <div class="stats-container mb-4">
            <div class="stat-box">
                <div class="icon text-danger-emphasis"><i class="fa-solid fa-exclamation-circle"></i></div>
                <div class="label">เกินกำหนด</div>
                <div class="value"><?= number_format($overdueAmount,2) ?> ฿</div>
            </div>
            <div class="stat-box">
                <div class="icon text-warning-emphasis"><i class="fa-solid fa-clock"></i></div>
                <div class="label">ครบกำหนดวันนี้</div>
                <div class="value"><?= number_format($dueTodayAmount,2) ?> ฿</div>
            </div>
            <div class="stat-box">
                <div class="icon text-info-emphasis"><i class="fa-solid fa-calendar-days"></i></div>
                <div class="label">ครบกำหนด 1-7 วัน</div>
                <div class="value"><?= number_format($dueNext7Amount,2) ?> ฿</div>
            </div>
            <div class="stat-box">
                <div class="icon text-primary-emphasis"><i class="fa-solid fa-receipt"></i></div>
                <div class="label">ยอดต้องเก็บ</div>
                <div class="value"><?= number_format($totalDue,2) ?> ฿</div>
            </div>
            <div class="stat-box">
                <div class="icon text-secondary"><i class="fa-solid fa-piggy-bank"></i></div>
                <div class="label">ทุนปล่อยสินเชื่อ</div>
                <div class="value"><?= number_format($totalPrincipal,2) ?> ฿</div>
            </div>
            <div class="stat-box">
                <div class="icon text-warning-emphasis"><i class="fa-solid fa-percent"></i></div>
                <div class="label">ค่าคอมมิชชั่น</div>
                <div class="value"><?= number_format($totalCommission,2) ?> ฿</div>
            </div>
            <div class="stat-box">
                <div class="icon text-secondary"><i class="fa-solid fa-wallet"></i></div>
                <div class="label">ค่าใช้จ่ายอื่นๆ</div>
                <div class="value"><?= number_format($totalOtherCost,2) ?> ฿</div>
            </div>
            <div class="stat-box">
                <div class="icon text-success-emphasis"><i class="fa-solid fa-hand-holding-dollar"></i></div>
                <div class="label">รายได้รวม</div>
                <div class="value"><?= number_format($totalIncome,2) ?> ฿</div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-7">
                <div class="card shadow-sm h-100">
                    <div class="card-body d-flex flex-column">
                        <h6 class="card-title mb-3">จำนวนสัญญาใหม่รายเดือน</h6>
                        <div class="flex-grow-1" style="position: relative; min-height: 300px;">
                            <canvas id="contractsChart"
                                style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="card shadow-sm h-100 d-flex flex-column">
                    <div class="card-header bg-transparent border-0 pt-3">
                        <h6 class="m-0 card-title"><i class="fa-solid fa-list-check me-2"></i>รายการที่ต้องติดตาม</h6>
                    </div>
                    <div class="card-body p-0 flex-grow-1" style="overflow-y: auto;">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <tbody>
                                    <?php if (empty($contracts)): ?>
                                    <tr>
                                        <td class="text-center p-4 text-muted">ไม่มีข้อมูลสัญญาที่ต้องติดตาม</td>
                                    </tr>
                                    <?php else: ?>
                                    <?php foreach (array_slice($contracts, 0, 10) as $contract): ?>
                                    <?php
                                                $statusClass = '';
                                                if ($contract['next_status'] === 'paid') $statusClass = 'text-success-emphasis';
                                                elseif (strtotime($contract['next_due_date']) < strtotime($today)) $statusClass = 'text-danger-emphasis';
                                                else $statusClass = 'text-warning-emphasis';
                                            ?>
                                    <tr>
                                        <td class="ps-3 py-3">
                                            <div class="fw-bold"><?= htmlspecialchars($contract['customer_name']) ?>
                                            </div>
                                            <div class="small text-muted">
                                                <?= htmlspecialchars($contract['contract_no']) ?></div>
                                        </td>
                                        <td class="text-end pe-3 py-3">
                                            <div class="fw-bold <?= $statusClass ?>">
                                                <?= number_format($contract['next_amount_due'], 2) ?> ฿</div>
                                            <div class="small text-muted">
                                                <?= date('d M Y', strtotime($contract['next_due_date'])) ?></div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php if(count($contracts) > 10): ?>
                    <div class="card-footer text-center bg-body-tertiary">
                        <a href="<?= $baseURL ?>/public/pages/superadmin/payments/manage_payments.php"
                            class="text-decoration-none small fw-bold">จัดการการชำระเงินทั้งหมด &raquo;</a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>
</main>

<div class="modal fade" id="shopsModal" tabindex="-1">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa-solid fa-map-location-dot me-1"></i>แผนที่ร้านค้าทั้งหมด</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div id="shopsMapModal" style="width:100%;height:100vh"></div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const BASE_URL = "<?= $baseURL ?>";
    const chartEl = document.getElementById('contractsChart');
    if (chartEl) {
        new Chart(chartEl, {
            type: 'bar',
            data: {
                labels: <?= $chartLabels ?>,
                datasets: [{
                    label: 'จำนวนสัญญา',
                    data: <?= $chartData ?>,
                    backgroundColor: 'rgba(54, 162, 235, 0.6)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    }
    const shopsModal = document.getElementById('shopsModal');
    if (shopsModal) {
        let map;
        shopsModal.addEventListener('shown.bs.modal', () => {
            setTimeout(() => {
                if (!map) {
                    map = L.map('shopsMapModal').setView([15.87, 100.99], 6);
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '© OpenStreetMap'
                    }).addTo(map);
                    const shops =
                        <?= json_encode($pdo->query("SELECT name,latitude lat,longitude lng FROM users WHERE role='shop' AND latitude IS NOT NULL AND longitude IS NOT NULL")->fetchAll(PDO::FETCH_ASSOC),JSON_UNESCAPED_UNICODE) ?>;
                    shops.forEach(s => L.marker([s.lat, s.lng]).addTo(map).bindPopup(s.name));
                }
                map.invalidateSize();
            }, 10);
        });
    }
});
</script>

<?php include ROOT_PATH.'/public/includes/footer.php'; ?>