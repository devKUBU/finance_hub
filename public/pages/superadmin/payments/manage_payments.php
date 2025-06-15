<?php
// File: public/pages/superadmin/payments/manage_payments.php (Fixed buttons & effects)

require_once realpath(__DIR__ . '/../../../../config/bootstrap.php');
require_once ROOT_PATH . '/includes/helpers.php';
if (session_status() === PHP_SESSION_NONE) session_start();
requireRole(['superadmin', 'admin']);
require_once ROOT_PATH . '/config/db.php';
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if (!hasPermission($pdo, $_SESSION['user']['id'], 'view_payments')) {
    setFlash('error', 'คุณไม่มีสิทธิ์ดูข้อมูลการชำระเงิน');
    header("Location: {$baseURL}/pages/superadmin/dashboard.php");
    exit;
}

$pageTitle = 'จัดการการชำระเงิน';
include ROOT_PATH . '/public/includes/header.php';
include ROOT_PATH . '/public/includes/sidebar.php';
?>
<link rel="stylesheet" href="<?= $baseURL ?>/assets/css/dashboard.css">
<link rel="stylesheet" href="<?= htmlspecialchars($baseURL) ?>/assets/css/manage_payments.css">
<link rel="stylesheet" href="<?= htmlspecialchars($baseURL) ?>/assets/css/custom-pay-modal.css">

<style>
.blink-overdue {
    animation: blink 1.2s steps(2, start) infinite;
}

@keyframes blink {
    to {
        visibility: hidden;
    }
}

.badge[data-bs-toggle="tooltip"] {
    cursor: pointer;
}

/* --- โค้ดที่เพิ่มเข้ามาสำหรับ Status Legend --- */
.status-legend-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 0.75rem;
    padding: 0.5rem;
}

.status-legend-item {
    display: flex;
    align-items: center;
    padding: 0.5rem 0.75rem;
    /* แก้ไข: ใช้สีพื้นหลังระดับสาม ซึ่งจะแตกต่างจากพื้นหลังหลักเสมอ */
    background-color: var(--bs-tertiary-bg);
    border: 1px solid var(--bs-border-color-translucent);
    border-radius: 0.5rem;
    transition: all 0.2s ease-in-out;
    cursor: pointer;
}

.status-legend-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0);
    border-color: var(--bs-primary);
}

.status-legend-icon {
    width: 20px;
    height: 20px;
    border-radius: 50%;
    margin-right: 0.75rem;
    flex-shrink: 0;
}

.status-legend-text {
    font-size: 0.85rem;
    font-weight: 500;
    /* เพิ่ม: กำหนดสีตัวหนังสือให้เป็นสีหลักของธีม ทำให้ชัดเจนขึ้น */
    color: var(--bs-body-color);
}

.p {
    align-items: center;

}
</style>
<main class="main-content">
    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="m-0"><i class="fa-solid fa-list-check me-2"></i><?= htmlspecialchars($pageTitle) ?></h3>
            <div class="header-actions d-flex align-items-center">
                <button id="sidebarToggle" class="btn-icon"><i class="fa-solid fa-bars"></i></button>
                <button id="themeToggle" class="btn-icon ms-2"><i id="themeIcon" class="fa-solid fa-moon"></i></button>
            </div>
        </div>
        <hr>

        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form id="filterForm" class="row g-3 align-items-center">
                    <div class="col-md-7 col-lg-8">
                        <input type="text" id="searchContracts" name="search" class="form-control"
                            placeholder="🔍 ค้นหา: เลขสัญญา หรือ ชื่อลูกค้า...">
                    </div>

                    <div class="col-md-5 col-lg-4">
                        <select id="filterStatus" name="status" class="form-select">
                            <option value="all" selected>สถานะทั้งหมด</option>
                            <option value="overdue">เกินกำหนด</option>
                            <option value="today">ครบกำหนดวันนี้</option>
                            <option value="tomorrow">ครบกำหนดพรุ่งนี้</option>
                            <option value="next7">ครบกำหนด 2–7 วัน</option>
                            <option value="closed">ปิดสัญญาแล้ว</option>
                        </select>
                    </div>
                </form>
            </div>
        </div>
        <div class="d-flex flex-wrap justify-content-center align-items-center small gap-3 mb-4 py-2">

            <span class="fw-bold me-2">คำอธิบายสถานะ:</span>

            <div class="status-legend-item" data-bs-toggle="tooltip" data-bs-placement="bottom"
                data-bs-title="งวดการชำระเงินที่เลยวันครบกำหนดมาแล้ว แต่ยังไม่ได้รับการชำระ">
                <span class="status-legend-icon bg-danger"></span>
                <span class="status-legend-text">เกินกำหนด</span>
            </div>

            <div class="status-legend-item" data-bs-toggle="tooltip" data-bs-placement="bottom"
                data-bs-title="งวดการชำระเงินที่มีกำหนดชำระภายในวันนี้">
                <span class="status-legend-icon bg-warning"></span>
                <span class="status-legend-text">ครบวันนี้</span>
            </div>

            <div class="status-legend-item" data-bs-toggle="tooltip" data-bs-placement="bottom"
                data-bs-title="งวดการชำระเงินในอนาคตที่ยังไม่ถึงวันครบกำหนด">
                <span class="status-legend-icon bg-info"></span>
                <span class="status-legend-text">รอชำระ</span>
            </div>

            <div class="status-legend-item" data-bs-toggle="tooltip" data-bs-placement="bottom"
                data-bs-title="สัญญาที่ไม่มีงวดค้างชำระ โดยจะแสดงถึงงวดล่าสุดที่เพิ่งชำระไป">
                <span class="status-legend-icon bg-success"></span>
                <span class="status-legend-text">ชำระล่าสุด</span>
            </div>

            <div class="status-legend-item" data-bs-toggle="tooltip" data-bs-placement="bottom"
                data-bs-title="สัญญาที่ลูกค้าได้ชำระเงินครบถ้วนทุกงวดแล้ว">
                <span class="status-legend-icon bg-secondary"></span>
                <span class="status-legend-text">ปิดสัญญา</span>
            </div>

        </div>
        <div class="card shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="contractsTable">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center" style="width: 5%;">ลำดับที่</th>
                            <th class="sortable" data-sort="contract_no">เลขสัญญา <i class="fa-solid fa-sort"></i></th>
                            <th class="sortable" data-sort="customer_name">ชื่อลูกค้า <i class="fa-solid fa-sort"></i>
                            </th>
                            <th class="text-center sortable" data-sort="due_date">งวดถัดไป <i
                                    class="fa-solid fa-sort"></i></th>
                            <th class="text-end">ยอดงวด (฿)</th>
                            <th class="text-end">จ่ายแล้ว (฿)</th>
                            <th class="text-end">คงเหลือ (฿)</th>
                            <th class="text-center">สถานะ</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody id="contractsTableBody">
                    </tbody>
                </table>
            </div>
            <div class="card-footer bg-body-tertiary d-flex justify-content-between align-items-center">
                <div id="pagination-summary" class="text-muted small">กำลังโหลด...</div>
                <div id="pagination-controls"></div>
            </div>
        </div>

        <?php include __DIR__ . '/partials/payment_modals.php'; ?>
    </div>
</main>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
window.BASE_URL = "<?= rtrim($baseURL, '/') ?>";
// --- โค้ดที่เพิ่มเข้ามาเพื่อเปิดใช้งาน Tooltip ---
document.addEventListener('DOMContentLoaded', function() {
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(
        tooltipTriggerEl));
});
// --- สิ้นสุดส่วนที่เพิ่ม ---
</script>

<script src="<?= $baseURL ?>/assets/js/manage_payments.js"></script>

<?php include ROOT_PATH . '/public/includes/footer.php'; ?>