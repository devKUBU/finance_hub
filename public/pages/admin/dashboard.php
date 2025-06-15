<?php
// File: public/pages/admin/dashboard.php

require_once realpath(__DIR__ . '/../../../config/bootstrap.php');
require_once ROOT_PATH . '/includes/helpers.php';

if (session_status() === PHP_SESSION_NONE) session_start();
requireRole(['admin']);

require_once ROOT_PATH . '/config/db.php';
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// โหลด permission list ถ้าต้องการใช้ label
$permissions = include ROOT_PATH . '/includes/permissions_constants.php';

$pageTitle = 'แดชบอร์ดแอดมิน';
include ROOT_PATH . '/public/includes/header.php';
include ROOT_PATH . '/public/includes/sidebar.php';
?>

<main class="main-content">
    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="mb-0"><i class="fa-solid fa-chart-line me-2"></i><?= htmlspecialchars($pageTitle) ?></h3>
            <div class="header-actions d-flex align-items-center gap-2">
                <button id="sidebarToggle" class="btn-icon" aria-label="Toggle sidebar">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <button id="themeToggle" class="btn-icon" aria-label="Toggle theme">
                    <i id="themeIcon" class="fa-solid fa-moon"></i>
                </button>
            </div>
        </div>
        <hr>

        <!-- Flash message -->
        <?php displayFlash(); ?>

        <div class="row g-4">

            <!-- Box: ดูสัญญา -->
            <?php if (hasPermission($pdo, $_SESSION['user']['id'], 'manage_contracts')): ?>
            <div class="col-md-6 col-xl-4">
                <a href="<?= $baseURL ?>/pages/superadmin/contracts/list-contracts.php" class="dashboard-card">
                    <div class="card border-primary shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title"><i class="fa-solid fa-file-contract me-2"></i> จัดการสัญญา</h5>
                            <p class="card-text text-muted">ดู/แก้ไขสถานะและรายละเอียดของสัญญา</p>
                        </div>
                    </div>
                </a>
            </div>
            <?php endif; ?>

            <!-- Box: ดูรายการชำระ -->
            <?php if (hasPermission($pdo, $_SESSION['user']['id'], 'view_payments')): ?>
            <div class="col-md-6 col-xl-4">
                <a href="<?= $baseURL ?>/pages/superadmin/payments/manage_payments.php" class="dashboard-card">
                    <div class="card border-success shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title"><i class="fa-solid fa-coins me-2"></i> การชำระเงิน</h5>
                            <p class="card-text text-muted">ตรวจสอบ/บันทึกการชำระเงินลูกค้า</p>
                        </div>
                    </div>
                </a>
            </div>
            <?php endif; ?>

            <!-- Box: ปิดยอด/ปิดสัญญา -->
            <?php if (hasPermission($pdo, $_SESSION['user']['id'], 'close_contracts')): ?>
            <div class="col-md-6 col-xl-4">
                <a href="<?= $baseURL ?>/pages/superadmin/contracts/close-contracts.php" class="dashboard-card">
                    <div class="card border-danger shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title"><i class="fa-solid fa-lock me-2"></i> ปิดสัญญา</h5>
                            <p class="card-text text-muted">ปิดยอดลูกค้าที่ชำระครบแล้ว</p>
                        </div>
                    </div>
                </a>
            </div>
            <?php endif; ?>

            <!-- Box: ดูรายงาน -->
            <?php if (hasPermission($pdo, $_SESSION['user']['id'], 'view_reports')): ?>
            <div class="col-md-6 col-xl-4">
                <a href="<?= $baseURL ?>/pages/superadmin/reports/overview.php" class="dashboard-card">
                    <div class="card border-warning shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title"><i class="fa-solid fa-chart-pie me-2"></i> รายงาน</h5>
                            <p class="card-text text-muted">ดูรายงานสรุปรายได้/ยอดชำระ</p>
                        </div>
                    </div>
                </a>
            </div>
            <?php endif; ?>

        </div>
    </div>
</main>

<!-- JS: Sidebar toggle + Theme toggle -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.getElementById('sidebar');
    const toggleBtn = document.getElementById('sidebarToggle');
    const mainContent = document.querySelector('.main-content');

    if (toggleBtn && sidebar && mainContent) {
        toggleBtn.addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('sidebar-collapsed');
        });
    }

    const themeToggle = document.getElementById('themeToggle');
    const html = document.documentElement;

    if (themeToggle) {
        themeToggle.addEventListener('click', () => {
            const currentTheme = html.getAttribute('data-bs-theme') || 'dark';
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            html.setAttribute('data-bs-theme', newTheme);

            // อัปเดตธีมใน session ผ่าน API
            fetch('<?= $baseURL ?>/api/toggle_theme.php', {
                method: 'POST'
            });
        });
    }
});
</script>

<?php include ROOT_PATH . '/public/includes/footer.php'; ?>