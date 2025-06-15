<?php
// File: public/pages/superadmin/admin_manage/admins.php

require_once realpath(__DIR__ . '/../../../../config/bootstrap.php');
require_once ROOT_PATH . '/includes/helpers.php';
if (session_status() === PHP_SESSION_NONE) session_start();
requireRole(['superadmin', 'admin']);


require_once ROOT_PATH . '/config/db.php';

$pageTitle = 'จัดการแอดมิน';

// ลบแอดมิน
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $deleteId = (int) $_GET['delete'];
    $stmtInfo = $pdo->prepare("
        SELECT username, email
        FROM users
        WHERE id = ? AND role = 'admin'
    ");
    $stmtInfo->execute([$deleteId]);
    $adminInfo = $stmtInfo->fetch(PDO::FETCH_ASSOC);

    if ($adminInfo) {
        $stmtDel = $pdo->prepare("
            DELETE FROM users
            WHERE id = ? AND role = 'admin'
        ");
        $stmtDel->execute([$deleteId]);
        logActivity(
            $pdo,
            $_SESSION['user']['id'],
            'delete_admin',
            'admin',
            $deleteId,
            "ลบแอดมิน username={$adminInfo['username']}, email={$adminInfo['email']}"
        );
    }

    header("Location: {$baseURL}/pages/superadmin/admin_manage/admins.php");
    exit;
}

// ดึงข้อมูลแอดมิน
$stmt   = $pdo->query("
    SELECT id, username, email, is_active, created_at
    FROM users
    WHERE role = 'admin'
    ORDER BY created_at DESC
");
$admins = $stmt->fetchAll(PDO::FETCH_ASSOC);

// โหลด Layout หลัก + CSS
include ROOT_PATH . '/public/includes/header.php';
include ROOT_PATH . '/public/includes/sidebar.php';
echo '<link rel="stylesheet" href="'. htmlspecialchars($baseURL) .'/assets/css/dashboard.css">';
// ไม่ต้องเรียก admins.css ถ้าไม่มีการปรับเพิ่มเติม
?>

<main class="main-content">
    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="m-0">
                <i class="fa-solid fa-list-check me-2"></i>
                <?= htmlspecialchars($pageTitle) ?>
            </h3>
            <div class="header-actions d-flex align-items-center">
                <button id="sidebarToggle" class="btn-icon" aria-label="Toggle sidebar">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <button id="themeToggle" class="btn-icon ms-2" aria-label="Toggle theme">
                    <i id="themeIcon" class="fa-solid fa-moon"></i>
                </button>
            </div>
        </div>
        <hr>
        <div class="header-actions d-flex align-items-center">
            <a href="<?= $baseURL ?>/pages/superadmin/admin_manage/admins_add.php"
                class="header-link btn btn-sm btn-primary me-2">
                <i class="fa-solid fa-plus me-1"></i>เพิ่มแอดมิน
            </a>
        </div>
        </header>
        <hr>

        <!-- Table of Admins -->
        <div class="table-responsive">
            <table class="table-shop-manage align-middle mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>สถานะ</th>
                        <th>วันที่สร้าง</th>
                        <th>จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($admins as $index => $a): ?>
                    <tr>
                        <td><?= $index + 1 ?></td>
                        <td><?= htmlspecialchars($a['username']) ?></td>
                        <td><?= htmlspecialchars($a['email']) ?></td>
                        <td>
                            <span class="badge <?= $a['is_active'] ? 'bg-success' : 'bg-secondary' ?>">
                                <?= $a['is_active'] ? '<i class="fa-solid fa-check"></i> Active' : '<i class="fa-solid fa-ban"></i> Inactive' ?>
                            </span>
                        </td>
                        <td><?= date('Y-m-d', strtotime($a['created_at'])) ?></td>
                        <td>
                            <button class="action-btn me-1 btn-delete-admin" data-id="<?= $a['id'] ?>"
                                data-username="<?= htmlspecialchars($a['username']) ?>">
                                <i class="fa-solid fa-trash"></i>
                            </button>

                            <button class="action-btn"
                                onclick="location.href='<?= $baseURL ?>/pages/superadmin/admin_manage/admins_edit.php?id=<?= $a['id'] ?>'">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<?php include ROOT_PATH . '/public/includes/footer.php'; ?>