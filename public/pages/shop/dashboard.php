<?php
// File: public/pages/shop/dashboard.php

require_once realpath(__DIR__ . '/../../../config/bootstrap.php');
require_once ROOT_PATH . '/includes/helpers.php';
if (session_status() === PHP_SESSION_NONE) session_start();
requireRole(['shop']);

require_once ROOT_PATH . '/config/db.php';
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Helper กัน SQL ผิดพลาด
function safeCount(PDO $pdo, string $sql, array $params) {
    try {
        $st = $pdo->prepare($sql);
        $st->execute($params);
        return (int)$st->fetchColumn();
    } catch (PDOException $e) {
        return 0;
    }
}

// รหัสร้าน
$shopId = $_SESSION['user']['id'];

// 1) สรุปตัวเลข
$totalContracts        = safeCount($pdo, "SELECT COUNT(*) FROM contracts WHERE shop_id = ?", [$shopId]);
$pendingContracts      = safeCount($pdo, "SELECT COUNT(*) FROM contracts WHERE shop_id = ? AND approval_status = 'pending'", [$shopId]);
$pendingCommission     = safeCount($pdo, "SELECT COUNT(*) FROM contracts WHERE shop_id = ? AND approval_status = 'approved' AND commission_status = 'commission_pending'", [$shopId]);
$transferredCommission = safeCount($pdo, "SELECT COUNT(*) FROM contracts WHERE shop_id = ? AND commission_status = 'commission_transferred'", [$shopId]);

// — รายได้เดือนนี้ (จาก commission_amount) —
$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(commission_amount), 0)
      FROM contracts
     WHERE shop_id = ?
       AND commission_status = 'commission_transferred'
       AND MONTH(commission_transferred_at) = MONTH(CURDATE())
       AND YEAR(commission_transferred_at)  = YEAR(CURDATE())
");
$stmt->execute([$shopId]);
$thisMonthCommissionRev = (float)$stmt->fetchColumn();


// 2) แนวโน้ม 6 เดือนล่าสุด (จำนวนสัญญา)
$months = $counts = [];
for ($i = 5; $i >= 0; $i--) {
    $dt = (new DateTime("first day of -{$i} months"))->setTimezone(new DateTimeZone('Asia/Bangkok'));
    $mNum = (int)$dt->format('n');
    $thaiM = ['','ม.ค.','ก.พ.','มี.ค.','เม.ย.','พ.ค.','มิ.ย.','ก.ค.','ส.ค.','ก.ย.','ต.ค.','พ.ย.','ธ.ค.'][$mNum];
    $months[] = $thaiM;
    $counts[] = safeCount(
        $pdo,
        "SELECT COUNT(*) FROM contracts
           WHERE shop_id = ?
             AND MONTH(created_at) = ?
             AND YEAR(created_at)  = ?",
        [$shopId, $mNum, (int)$dt->format('Y')]
    );
}

// 3) สถิติสถานะอนุมัติ
$statusMap = [
    'pending'  => 'รอการอนุมัติ',
    'approved' => 'อนุมัติแล้ว',
    'rejected' => 'ไม่อนุมัติ',
];

// ดึง labels เป็นลิสต์ข้อความ
$statusLabels = array_values($statusMap);

// ดึง data เป็นลิสต์ค่า count ตามลำดับ key ใน $statusMap
$statusData = [];
foreach (array_keys($statusMap) as $status) {
    $statusData[] = safeCount(
        $pdo,
        "SELECT COUNT(*) FROM contracts WHERE shop_id = ? AND approval_status = ?",
        [$shopId, $status]
    );
}

// — รายได้วันนี้ —
$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(commission_amount),0)
      FROM contracts
     WHERE shop_id = ?
       AND commission_status = 'commission_transferred'
       AND DATE(commission_transferred_at) = CURDATE()
");
$stmt->execute([$shopId]);
$dailyCommissionRev = (float)$stmt->fetchColumn();

// — รายได้รายสัปดาห์ —
$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(commission_amount),0)
      FROM contracts
     WHERE shop_id = ?
       AND commission_status = 'commission_transferred'
       AND YEAR(commission_transferred_at)  = YEAR(CURDATE())
       AND WEEK(commission_transferred_at,1) = WEEK(CURDATE(),1)
");
$stmt->execute([$shopId]);
$weeklyCommissionRev = (float)$stmt->fetchColumn();

// — รายได้รายเดือน —
$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(commission_amount),0)
      FROM contracts
     WHERE shop_id = ?
       AND commission_status = 'commission_transferred'
       AND MONTH(commission_transferred_at) = MONTH(CURDATE())
       AND YEAR(commission_transferred_at)  = YEAR(CURDATE())
");
$stmt->execute([$shopId]);
$monthlyCommissionRev = (float)$stmt->fetchColumn();

// — รายได้รายปี —
$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(commission_amount),0)
      FROM contracts
     WHERE shop_id = ?
       AND commission_status = 'commission_transferred'
       AND YEAR(commission_transferred_at) = YEAR(CURDATE())
");
$stmt->execute([$shopId]);
$yearlyCommissionRev = (float)$stmt->fetchColumn();

// — ยอดรวมค่าคอมมิชชั่นทั้งหมด —
$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(commission_amount),0)
      FROM contracts
     WHERE shop_id = ?
       AND commission_status = 'commission_transferred'
");
$stmt->execute([$shopId]);
$totalCommissionRev = (float)$stmt->fetchColumn();

// 4) รายการสัญญา 5 ล่าสุด
$stRecent = $pdo->prepare("
  SELECT contract_no_shop, approval_status, created_at
    FROM contracts
   WHERE shop_id = ?
   ORDER BY created_at DESC
   LIMIT 5
");
$stRecent->execute([$shopId]);
$recentContracts = $stRecent->fetchAll(PDO::FETCH_ASSOC);

// 5) งวดผ่อนกำลังจะครบใน 7 วันข้างหน้า
$stUpcoming = $pdo->prepare("
  SELECT p.pay_no, p.due_date, c.contract_no_shop
    FROM payments p
    JOIN contracts c ON p.contract_id = c.id
   WHERE c.shop_id = ?
     AND p.due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
   ORDER BY p.due_date ASC
   LIMIT 5
");
$stUpcoming->execute([$shopId]);
$upcoming = $stUpcoming->fetchAll(PDO::FETCH_ASSOC);



// ชื่อหน้า
$pageTitle = 'แดชบอร์ดร้านค้า';
include ROOT_PATH . '/public/includes/header.php';
include ROOT_PATH . '/public/includes/sidebar.php';
echo '<link rel="stylesheet" href="'.htmlspecialchars($baseURL).'/assets/css/dashboard.css">';
?>
<style>
/* ย่อขนาดฟอนต์ในการ์ดสรุป */
.summary-card .card-body h5 {
    font-size: 1.25rem;
}

.summary-card .card-body small {
    font-size: 0.85rem;
}

/* Dark Theme Text Overrides */
[data-theme="dark"] .main-content,
[data-theme="dark"] .main-content h2,
[data-theme="dark"] .main-content h5,
[data-theme="dark"] .card,
[data-theme="dark"] .card-header,
[data-theme="dark"] .card-body,
[data-theme="dark"] .table,
[data-theme="dark"] .table th,
[data-theme="dark"] .table td {
    color: #e0e0e0 !important;
}

[data-theme="dark"] .table-light {
    background-color: #343a40 !important;
}

/* เปลี่ยน background ของ thead.table-light ใน Dark Mode */
[data-theme="dark"] .table-light {
    background-color: rgba(52, 58, 64, 0) !important;
    /* หรือสีตามตัวแปรธีมของคุณ */
}

/* เปลี่ยนสีตัวอักษรใน th ให้เด่นขึ้น */
[data-theme="dark"] .table-light th {
    color: #f8f9fa !important;
}

/* ถ้าอยาก override ทุก th ใน table */
[data-theme="dark"] .table thead th {
    background-color: rgba(52, 58, 64, 0) !important;
    color: #f8f9fa !important;
}

/* นอกจากนี้ ถ้ามี td ที่ยังขาว ให้ override เพิ่ม */
[data-theme="dark"] .table tbody td {
    background-color: transparent !important;
}

.summary-card .card-body h5 {
    font-size: 1.5rem;
    font-weight: 600;
}

/* ปรับสีข้อความเล็ก */
.summary-card .card-body small {
    font-size: 0.9rem;
    color: #6c757d;
}

.value {
    font-size: medium;
    color: #e0e0e0;
}
</style>


<main class="main-content">
    <div class="container-fluid py-4">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="m-0"><i class="fa-solid fa-chart-line me-2"></i><?=htmlspecialchars($pageTitle)?></h2>
            <div>
                <button id="sidebarToggle" class="btn btn-outline-secondary btn-sm me-1"><i
                        class="fa-solid fa-bars"></i></button>
                <button id="themeToggle" class="btn btn-outline-secondary btn-sm"><i id="themeIcon"
                        class="fa-solid"></i></button>
            </div>
        </div>

        <!-- Summary Cards -->

        <div class="row justify-content-center row-cols-2 row-cols-md-4 g-4 mb-5">
            <?php 
    $statusSummaries = [
      ['icon'=>'fa-file-contract',  'value'=>$totalContracts,      'label'=>'สัญญาทั้งหมด',        'color'=>'primary'],
      ['icon'=>'fa-hourglass-start','value'=>$pendingContracts,    'label'=>'รอการอนุมัติ',        'color'=>'warning'],
      ['icon'=>'fa-handshake',      'value'=>$pendingCommission,   'label'=>'รอโอนคอมมิชชั่น',     'color'=>'info'],
      ['icon'=>'fa-money-bill-wave','value'=>$transferredCommission,'label'=>'โอนคอมมิชชั่นแล้ว',   'color'=>'success'],
    ];
    foreach ($statusSummaries as $s):
  ?>
            <div class="col">
                <div class="card h-100 shadow-sm summary-card">
                    <div class="card-body d-flex flex-column justify-content-center align-items-center text-center">
                        <i class="fa-solid <?= $s['icon'] ?> fa-2x text-<?= $s['color'] ?> mb-2"></i>
                        <h5 class="mb-1"><?= $s['value'] ?></h5>
                        <small><?= $s['label'] ?></small>

                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Summary Row 2: รายได้ -->
        <div class="row justify-content-center row-cols-2 row-cols-sm-3 row-cols-md-5 g-4 mb-5">
            <?php 
    $revSummaries = [
      ['icon'=>'fa-sun',           'value'=>number_format($dailyCommissionRev,2),   'label'=>'รายได้วันนี้',      'color'=>'warning'],
      ['icon'=>'fa-calendar-week', 'value'=>number_format($weeklyCommissionRev,2),  'label'=>'สัปดาห์นี้',        'color'=>'info'],
      ['icon'=>'fa-calendar-alt',  'value'=>number_format($monthlyCommissionRev,2), 'label'=>'เดือนนี้',          'color'=>'primary'],
      ['icon'=>'fa-calendar',      'value'=>number_format($yearlyCommissionRev,2),  'label'=>'ปีนี้',             'color'=>'success'],
      ['icon'=>'fa-coins',         'value'=>number_format($totalCommissionRev,2),   'label'=>'รวมค่าคอมมิชชั่น', 'color'=>'danger'],
    ];
    foreach ($revSummaries as $s):
  ?>
            <div class="col">
                <div class="card h-100 shadow-sm summary-card">
                    <div class="card-body d-flex flex-column justify-content-center align-items-center text-center">
                        <i class="fa-solid <?= $s['icon'] ?> fa-2x text-<?= $s['color'] ?> mb-2"></i>
                        <h5 class="mb-1"><?= $s['value'] ?></h5>
                        <small><?= $s['label'] ?></small>

                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>



        <!-- Charts -->
        <div class="row mb-4">
            <div class="col-lg-6 mb-3">
                <div class="card shadow-sm">
                    <div class="card-header"><i class="fa-solid fa-chart-line me-2"></i>สัญญา 6 เดือนล่าสุด</div>
                    <div class="card-body">
                        <canvas id="contractTrendChart" height="120"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 mb-3">
                <div class="card shadow-sm">
                    <div class="card-header"><i class="fa-solid fa-chart-pie me-2"></i>สถานะการอนุมัติ</div>
                    <div class="card-body d-flex justify-content-center">
                        <!-- กำหนดขนาดสูง–กว้างให้เล็กลง -->
                        <canvas id="statusPieChart" width="290" height="290"
                            style="max-width:290px; max-height:290px;"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Contracts -->
        <div class="card shadow-sm mb-4">
            <div class="card-header"><i class="fa-solid fa-clock-rotate-left me-2"></i>สัญญาล่าสุด</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>เลขที่สัญญา</th>
                                <th>สถานะ</th>
                                <th>วันที่</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($recentContracts as $idx => $r):
                $dt = (new DateTime($r['created_at'],new DateTimeZone('Asia/Bangkok')))
                         ->format('d/m/Y H:i');
                $cls = $r['approval_status']==='pending'  ? 'badge bg-warning'
                     : ($r['approval_status']==='approved'? 'badge bg-success'
                     : 'badge bg-danger');
                $txt = $r['approval_status']==='pending'  ? 'รออนุมัติ'
                     : ($r['approval_status']==='approved'? 'อนุมัติแล้ว'
                     : 'ไม่อนุมัติ');
              ?>
                            <tr>
                                <td><?= $idx+1 ?></td>
                                <td><?= htmlspecialchars($r['contract_no_shop']) ?></td>
                                <td><span class="<?= $cls ?>"><?= $txt ?></span></td>
                                <td><?= $dt ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</main>

<?php include ROOT_PATH . '/public/includes/footer.php'; ?>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Sidebar & Theme toggle
document.getElementById('sidebarToggle').onclick = () =>
    document.body.classList.toggle('collapsed');
(function() {
    const btn = document.getElementById('themeToggle'),
        icon = document.getElementById('themeIcon'),
        root = document.documentElement;

    function upd() {
        icon.className = root.getAttribute('data-theme') === 'dark' ?
            'fa-solid fa-sun' :
            'fa-solid fa-moon';
    }
    btn.onclick = () => {
        root.setAttribute('data-theme',
            root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark');
        upd();
    };
    upd();
})();

// ข้อมูลจาก PHP
const months = <?= json_encode($months, JSON_UNESCAPED_UNICODE) ?>;
const contractData = <?= json_encode($counts) ?>;
const statusLabels = <?= json_encode($statusLabels, JSON_UNESCAPED_UNICODE) ?>;
const statusData = <?= json_encode($statusData) ?>;

// Line Chart: แนวโน้มสัญญา
new Chart(document.getElementById('contractTrendChart'), {
    type: 'line',
    data: {
        labels: months,
        datasets: [{
            label: 'จำนวนสัญญา',
            data: contractData,
            fill: false
        }]
    },
    options: {
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

// Pie Chart: สถานะอนุมัติ
new Chart(document.getElementById('statusPieChart'), {
    type: 'pie',
    data: {
        labels: statusLabels,
        datasets: [{
            data: statusData
        }]
    }
});
</script>