<?php
// File: public/pages/superadmin/admin_manage/admins_edit.php

require_once realpath(__DIR__ . '/../../../../config/bootstrap.php');
require_once ROOT_PATH . '/includes/helpers.php';
if (session_status() === PHP_SESSION_NONE) session_start();
requireRole(['superadmin', 'admin']);

require_once ROOT_PATH . '/config/db.php';

// 0) ดึง id ของแอดมิน
$id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'admin' LIMIT 1");
$stmt->execute([$id]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$admin) {
    $_SESSION['toast'] = ['type' => 'danger', 'msg' => 'ไม่พบแอดมินที่ต้องการแก้ไข'];
    header("Location: {$baseURL}/pages/superadmin/admin_manage/admins.php");
    exit;
}

// 1) เตรียมตัวแปรเริ่มต้น
$pageTitle  = 'แก้ไขแอดมิน – Nano Friend';
$username   = $admin['username'];
$email      = $admin['email'];
$is_active  = $admin['is_active'];
$perms      = json_decode($admin['permissions'] ?: '[]', true);
$errors     = [];
$toastHere  = null;

// 2) ดึงรายการ Permissions จากไฟล์กลาง
$allPermissions = include ROOT_PATH . '/includes/permissions_constants.php';

// 3) ประมวลผล POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email     = trim($_POST['email'] ?? '');
    $password  = $_POST['password'] ?? '';
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $perms     = $_POST['permissions'] ?? [];

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'รูปแบบอีเมลไม่ถูกต้อง';
    }
    if ($password && strlen($password) < 6) {
        $errors[] = 'รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร';
    }

    if (empty($errors)) {
        $dup = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ? AND id <> ?");
        $dup->execute([$email, $id]);
        if ($dup->fetchColumn() > 0) {
            $errors[] = 'อีเมลนี้ถูกใช้งานแล้ว';
        }
    }

    if ($errors) {
        $toastHere = [
            'type' => 'danger',
            'msg'  => implode('<br>', $errors)
        ];
    } else {
        $set    = "email = ?, is_active = ?, permissions = ?";
        $params = [$email, $is_active, json_encode(array_values($perms)), $id];

        if ($password) {
            $set .= ", password = ?";
            array_splice($params, 3, 0, password_hash($password, PASSWORD_DEFAULT));
        }

        $sql = "UPDATE users SET $set WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        logActivity($pdo, $_SESSION['user']['id'], 'edit_admin', 'admin', $id, "แก้ไขแอดมิน id={$id}, email={$email}, is_active={$is_active}");

        $_SESSION['toast'] = ['type' => 'success', 'msg' => 'บันทึกการแก้ไขเรียบร้อยแล้ว!'];
        header("Location: {$baseURL}/pages/superadmin/admin_manage/admins.php");
        exit;
    }
}

include ROOT_PATH . '/public/includes/header.php';
echo '<link rel="stylesheet" href="'.$baseURL.'/assets/css/dashboard.css">';
echo '<link rel="stylesheet" href="'.$baseURL.'/assets/css/admins.css">';
include ROOT_PATH . '/public/includes/sidebar.php';
?>

<main class="main-content">
    <header class="app-header d-flex justify-content-between align-items-center">
        <h2 class="header-title">
            <i class="fa-solid fa-user-pen me-2"></i> แก้ไขแอดมิน
        </h2>
        <div class="header-actions d-flex align-items-center gap-2">
            <button id="sidebarToggle" class="btn-icon"><i class="fa-solid fa-bars"></i></button>
            <button id="themeToggle" class="btn-icon"><i id="themeIcon" class="fa-solid"></i></button>
        </div>
    </header>
    <hr>

    <?php if ($toastHere): ?>
    <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index:1100">
        <div id="editToast" class="toast toast-solid text-<?= $toastHere['type'] ?> border-0" data-bs-autohide="false">
            <div class="d-flex">
                <div class="toast-leftbar bg-<?= $toastHere['type'] ?>"></div>
                <div class="toast-icon"><i class="fa-solid fa-circle-xmark"></i></div>
                <div class="toast-body flex-grow-1"><?= $toastHere['msg'] ?></div>
                <button type="button" class="btn-close ms-2 me-2" data-bs-dismiss="toast"></button>
            </div>
        </div>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const t = bootstrap.Toast.getOrCreateInstance(document.getElementById('editToast'));
        t.show();
    });
    </script>
    <?php endif; ?>

    <div class="admin-card mx-auto">
        <form method="post" class="row g-4">
            <div class="col-md-6 position-relative">
                <i class="fa-solid fa-user input-icon"></i>
                <div class="form-floating">
                    <input type="text" class="form-control" id="username" name="username"
                        value="<?= htmlspecialchars($username) ?>" readonly>
                    <label for="username">ชื่อผู้ใช้</label>
                </div>
            </div>

            <div class="col-md-6 position-relative">
                <i class="fa-solid fa-envelope input-icon"></i>
                <div class="form-floating">
                    <input type="email" class="form-control" id="email" name="email"
                        value="<?= htmlspecialchars($email) ?>" required>
                    <label for="email">อีเมล</label>
                </div>
            </div>

            <div class="col-md-6 position-relative">
                <i class="fa-solid fa-lock input-icon"></i>
                <div class="form-floating">
                    <input type="password" class="form-control" id="password" name="password">
                    <label for="password">รหัสผ่านใหม่ (เว้นว่างไม่เปลี่ยน)</label>
                </div>
            </div>

            <div class="col-md-6 d-flex align-items-center">
                <div class="form-check form-switch ms-2">
                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active"
                        <?= $is_active ? 'checked' : '' ?>>
                    <label class="form-check-label" for="is_active">เปิดใช้งาน</label>
                </div>
            </div>

            <div class="col-12">
                <h5 class="mb-2"><i class="fa-solid fa-shield-halved me-1"></i> สิทธิ์การใช้งาน</h5>
                <div class="permissions-grid">
                    <?php foreach($allPermissions as $key => $labelPerm): ?>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="perm_<?= $key ?>" name="permissions[]"
                            value="<?= $key ?>" <?= in_array($key, $perms) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="perm_<?= $key ?>">
                            <?= $labelPerm ?>
                        </label>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="col-12 text-center pt-2">
                <button type="submit" class="btn btn-success px-4">
                    <i class="fa-solid fa-floppy-disk me-1"></i>บันทึก
                </button>
                <a href="<?= $baseURL ?>/pages/superadmin/admin_manage/admins.php" class="btn btn-link ms-3">ยกเลิก</a>
            </div>
        </form>
    </div>
</main>

<?php include ROOT_PATH . '/public/includes/footer.php'; ?>