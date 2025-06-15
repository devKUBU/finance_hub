<?php
// File: public/pages/superadmin/setting/penalty_settings.php (with SweetAlert2 Confirm & Toast)

require_once realpath(__DIR__ . '/../../../../config/bootstrap.php');
require_once ROOT_PATH . '/includes/helpers.php';
if (session_status() === PHP_SESSION_NONE) session_start();
requireRole(['superadmin', 'admin']);
require_once ROOT_PATH . '/config/db.php';
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// --- ส่วนบันทึกข้อมูล (ทำงานเมื่อมีการ POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hasPermission($pdo, $_SESSION['user']['id'], 'manage_settings')) {
        setFlash('error', 'คุณไม่มีสิทธิ์แก้ไขการตั้งค่า');
    } else {
        $penaltyType = $_POST['penalty_type'] ?? 'none';
        $penaltyRate = (float)($_POST['penalty_rate'] ?? 20.00);
        try {
            $stmt = $pdo->prepare("UPDATE contracts SET penalty_type = ?, penalty_rate = ? WHERE approval_status = 'approved'");
            $stmt->execute([$penaltyType, $penaltyRate]);
            $affectedRows = $stmt->rowCount();

            logActivity($pdo, $_SESSION['user']['id'], 'bulk_update_penalty', 'system', null, "อัปเดตค่าปรับของสัญญา {$affectedRows} รายการเป็น: {$penaltyType}, อัตรา: {$penaltyRate}");
            setFlash('success', "อัปเดตค่าปรับสำหรับสัญญาที่อนุมัติแล้ว {$affectedRows} รายการเรียบร้อยแล้ว");
        } catch (PDOException $e) {
            setFlash('error', 'เกิดข้อผิดพลาดในการอัปเดตฐานข้อมูล: ' . $e->getMessage());
        }
    }
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit;
}

// --- ดึงข้อมูลค่าปรับล่าสุดจากสัญญาที่สร้างล่าสุด ---
$currentSettings = $pdo->query("SELECT id, penalty_type, penalty_rate FROM contracts ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
if (!$currentSettings) {
    $currentSettings = ['id' => null, 'penalty_type' => 'none', 'penalty_rate' => '20.00'];
}

$pageTitle = 'ตั้งค่าและอัปเดตค่าปรับ';
include ROOT_PATH . '/public/includes/header.php';
include ROOT_PATH . '/public/includes/sidebar.php';
?>
<link rel="stylesheet" href="<?= htmlspecialchars($baseURL) ?>/assets/css/dashboard.css">

<main class="main-content">
    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="m-0"><i class="fa-solid fa-gavel me-2"></i><?= htmlspecialchars($pageTitle) ?></h3>
            <div class="header-actions d-flex align-items-center">
                <button id="sidebarToggle" class="btn-icon"><i class="fa-solid fa-bars"></i></button>
                <button id="themeToggle" class="btn-icon ms-2"><i id="themeIcon" class="fa-solid"></i></button>
            </div>
        </div>
        <hr>

        <?php 
            // ดึงข้อความ Flash Message มาเตรียมไว้ให้ JavaScript
            $flashSuccess = getFlash('success');
            $flashError = getFlash('error');
        ?>

        <div class="row g-4">
            <div class="col-lg-5">
                <div class="card shadow-sm h-100">
                    <div class="card-header">
                        <h6 class="m-0"><i class="fa-solid fa-eye me-2"></i>การตั้งค่าปัจจุบัน (ตัวอย่างจากสัญญา
                            #<?= htmlspecialchars($currentSettings['id'] ?? 'N/A') ?>)</h6>
                    </div>
                    <div class="card-body d-flex flex-column justify-content-center text-center">
                        <?php
                            $typeText = 'ไม่คิดค่าปรับ'; $icon = 'fa-solid fa-shield-halved'; $color = 'text-secondary';
                            if ($currentSettings['penalty_type'] == 'daily') {
                                $typeText = 'คิดค่าปรับรายวัน'; $icon = 'fa-solid fa-calendar-day'; $color = 'text-warning';
                            } elseif ($currentSettings['penalty_type'] == 'fixed') {
                                $typeText = 'คิดค่าปรับครั้งเดียว'; $icon = 'fa-solid fa-tag'; $color = 'text-info';
                            }
                        ?>
                        <div class="display-1 <?= $color ?> my-3"><i class="<?= $icon ?>"></i></div>
                        <h5 class="card-title"><?= $typeText ?></h5>
                        <?php if ($currentSettings['penalty_type'] !== 'none'): ?>
                        <p class="card-text display-5 fw-bold"><?= number_format($currentSettings['penalty_rate'], 2) ?>
                            <span class="h5">บาท</span>
                        </p>
                        <?php endif; ?>
                        <p class="text-muted small mt-3">นี่คือการตั้งค่าจากสัญญาที่สร้างล่าสุดในระบบ</p>
                    </div>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="card shadow-sm border-danger h-100">
                    <div class="card-header bg-danger-subtle">
                        <h6 class="m-0 text-warning-emphasis"><i
                                class="fa-solid fa-triangle-exclamation me-2"></i>เครื่องมืออัปเดตค่าปรับอัตโนมัติ</h6>
                    </div>
                    <div class="card-body">
                        <p class="text-danger"><strong>คำเตือน:</strong> การบันทึกหน้านี้จะทำการ
                            **อัปเดตเงื่อนไขค่าปรับให้กับสัญญาทุกฉบับที่ "อนุมัติแล้ว"** ในระบบ</p>
                        <hr>
                        <form id="bulkUpdateForm" method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>">
                            <div class="mb-3">
                                <label for="penalty_type" class="form-label fw-bold">รูปแบบค่าปรับ</label>
                                <select name="penalty_type" id="penalty_type" class="form-select">
                                    <option value="none"
                                        <?= ($currentSettings['penalty_type'] == 'none') ? 'selected' : '' ?>>
                                        ไม่คิดค่าปรับ</option>
                                    <option value="daily"
                                        <?= ($currentSettings['penalty_type'] == 'daily') ? 'selected' : '' ?>>
                                        คิดค่าปรับรายวัน</option>
                                    <option value="fixed"
                                        <?= ($currentSettings['penalty_type'] == 'fixed') ? 'selected' : '' ?>>
                                        คิดค่าปรับครั้งเดียว</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="penalty_rate" class="form-label fw-bold">อัตราค่าปรับ (บาท)</label>
                                <input type="number" step="0.01" name="penalty_rate" id="penalty_rate"
                                    class="form-control"
                                    value="<?= htmlspecialchars($currentSettings['penalty_rate']) ?>">
                            </div>
                            <button type="submit" class="btn btn-danger w-100"><i
                                    class="fa-solid fa-arrows-rotate me-1"></i> อัปเดตสัญญาทั้งหมด</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<div class="toast-container position-fixed top-0 end-0 p-3">
    <div id="notificationToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header">
            <i id="toastIcon" class="fa-solid rounded me-2"></i>
            <strong class="me-auto" id="toastTitle"></strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body" id="toastBody"></div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<script>
$(function() {
    // --- ส่วนควบคุม UI (Sidebar & Theme) ---
    // (โค้ดส่วนนี้เหมือนเดิม)

    // --- ส่วนยืนยันการ Submit Form (เปลี่ยนมาใช้ SweetAlert2) ---
    $('#bulkUpdateForm').on('submit', function(event) {
        event.preventDefault(); // หยุดการส่งฟอร์มแบบปกติ
        const form = this;

        Swal.fire({
            title: 'ยืนยันการอัปเดต',
            text: "คุณแน่ใจหรือไม่ว่าต้องการอัปเดตเงื่อนไขค่าปรับให้กับสัญญาทุกฉบับที่อนุมัติแล้ว? การกระทำนี้ไม่สามารถย้อนกลับได้!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'ใช่, อัปเดตเลย!',
            cancelButtonText: 'ยกเลิก'
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit(); // ถ้าผู้ใช้ยืนยัน ให้ส่งฟอร์ม
            }
        });
    });

    // --- ส่วนแสดง Toast Notification ---
    const successMessage = "<?= addslashes($flashSuccess) ?>";
    const errorMessage = "<?= addslashes($flashError) ?>";
    const toastEl = document.getElementById('notificationToast');

    if (toastEl) {
        const toastBody = document.getElementById('toastBody');
        const toastIcon = document.getElementById('toastIcon');
        const toastTitle = document.getElementById('toastTitle');
        const toast = new bootstrap.Toast(toastEl);

        if (successMessage) {
            toastTitle.innerText = 'สำเร็จ!';
            toastBody.innerText = successMessage;
            toastIcon.className = 'fa-solid fa-circle-check text-success me-2';
            toast.show();
        } else if (errorMessage) {
            toastTitle.innerText = 'เกิดข้อผิดพลาด!';
            toastBody.innerText = errorMessage;
            toastIcon.className = 'fa-solid fa-circle-exclamation text-danger me-2';
            toast.show();
        }
    }
});
</script>

<?php include ROOT_PATH . '/public/includes/footer.php'; ?>