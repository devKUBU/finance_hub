<?php
// public/pages/superadmin/admin_manage/admins_add.php
require_once realpath(__DIR__ . '/../../../../config/bootstrap.php');
require_once ROOT_PATH . '/includes/helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
requireRole(['superadmin', 'admin']);


require_once ROOT_PATH . '/config/db.php';

$pageTitle = 'เพิ่มแอดมิน – Nano Friend';

/* -----------------------------------------------------------------
   1) ค่าเริ่มต้น
------------------------------------------------------------------*/
$errors    = [];
$username  = '';
$email     = '';
$password  = '';
$is_active = 1;            // ใช้งานทันที
$perms     = [];           // permission ที่ติ๊กมา
$toastHere = null;         // Toast สำหรับแสดงบนหน้านี้ (error)

/* รายการสิทธิ์ที่รองรับ */
$allPermissions = include ROOT_PATH . '/includes/permissions_constants.php';


/* -----------------------------------------------------------------
   2) รับค่าจากฟอร์ม  (POST)
------------------------------------------------------------------*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* --- เก็บค่า --- */
    $username  = trim($_POST['username'] ?? '');
    $email     = trim($_POST['email']    ?? '');
    $password  = $_POST['password']      ?? '';
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $perms     = $_POST['permissions'] ?? [];   // array

    /* --- Validation --- */
    if ($username === '')                       $errors[] = 'กรุณากรอกชื่อผู้ใช้';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))
                                               $errors[] = 'รูปแบบอีเมลไม่ถูกต้อง';
    if (strlen($password) < 6)                  $errors[] = 'รหัสผ่านต้องอย่างน้อย 6 ตัว';

    /* --- ตรวจซ้ำ Username / Email --- */
    if (empty($errors)) {
        $dup = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
        $dup->execute([$username, $email]);
        if ($dup->fetchColumn() > 0) {
            $errors[] = 'ชื่อผู้ใช้หรืออีเมลนี้ถูกใช้งานแล้ว';
        }
    }

    /* -----------------------------------------------------------------
       3) ผลลัพธ์
       ----------------------------------------------------------------*/
    if ($errors) {
        /* ❌ ผิดพลาด – แสดง Toast สีแดงบนหน้านี้ (ไม่ redirect) */
        $toastHere = [
            'type' => 'danger',
            'msg'  => implode('<br>', $errors)
        ];

    } else {
        /* ✅ ผ่านทุกเงื่อนไข – INSERT ลงฐานข้อมูล */
        $hash       = password_hash($password, PASSWORD_DEFAULT);
        $jsonPerms  = json_encode(array_values($perms));

        $ins = $pdo->prepare("
            INSERT INTO users
              (username, email, password, role, is_active, permissions, created_at)
            VALUES
              (?, ?, ?, 'admin', ?, ?, NOW())
        ");
        $ins->execute([$username, $email, $hash, $is_active, $jsonPerms]);

        $newAdminId = $pdo->lastInsertId();
        logActivity($pdo, $_SESSION['user']['id'],
            'create_admin', 'admin', $newAdminId,
            "สร้างแอดมิน username={$username}, email={$email}");


        /* Toast สำเร็จ สำหรับหน้า list */
        $_SESSION['toast'] = [
            'type' => 'success',
            'msg'  => 'เพิ่มแอดมินเรียบร้อยแล้ว!'
        ];

        header("Location: {$baseURL}/pages/superadmin/admin_manage/admins.php");
        exit;
    }
}

/* -----------------------------------------------------------------
   4) โหลดส่วนหัว / CSS / Sidebar (ต่อด้วย HTML ด้านล่าง)
------------------------------------------------------------------*/
include ROOT_PATH . '/public/includes/header.php';
echo '<link rel="stylesheet" href="'.$baseURL.'/assets/css/dashboard.css">';
echo '<link rel="stylesheet" href="'.$baseURL.'/assets/css/admins.css">'; // ถ้ามีไฟล์เฉพาะหน้า
include ROOT_PATH . '/public/includes/sidebar.php';
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
        <!-- Card Container --------------------------------------------->
        <div class="admin-card">
            <?php if($errors): ?>
            <div class="alert alert-danger mb-4">
                <ul class="mb-0"><?php foreach($errors as $e): ?><li><?= htmlspecialchars($e) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <form method="post" class="row g-4">
                <!-- Username -->
                <div class="col-md-6 position-relative">
                    <i class="fa-solid fa-user input-icon"></i>
                    <div class="form-floating">
                        <input type="text" class="form-control" id="username" name="username"
                            value="<?= htmlspecialchars($username) ?>" required>
                        <label for="username">ชื่อผู้ใช้</label>
                    </div>
                </div>

                <!-- Email -->
                <div class="col-md-6 position-relative">
                    <i class="fa-solid fa-envelope input-icon"></i>
                    <div class="form-floating">
                        <input type="email" class="form-control" id="email" name="email"
                            value="<?= htmlspecialchars($email) ?>" required>
                        <label for="email">อีเมล</label>
                    </div>
                </div>

                <!-- Password -->
                <div class="col-md-6 position-relative">
                    <i class="fa-solid fa-lock input-icon"></i>
                    <div class="form-floating">
                        <input type="password" class="form-control" id="password" name="password" required>
                        <label for="password">รหัสผ่าน (≥ 6 ตัว)</label>
                    </div>
                </div>

                <!-- Active Switch -->
                <div class="col-md-6 d-flex align-items-center">
                    <div class="form-check form-switch ms-2">
                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active"
                            <?= $is_active?'checked':'' ?>>
                        <label class="form-check-label" for="is_active">เปิดใช้งานทันที</label>
                    </div>
                </div>

                <!-- Permissions Checklist -->
                <div class="col-12">
                    <h5 class="mb-2"><i class="fa-solid fa-shield-halved me-1"></i> สิทธิ์การใช้งาน</h5>
                    <div class="permissions-grid">
                        <?php foreach($allPermissions as $key=>$labelPerm): ?>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="perm_<?= $key ?>" name="permissions[]"
                                value="<?= $key ?>" <?= in_array($key,$perms)?'checked':'' ?>>
                            <label class="form-check-label" for="perm_<?= $key ?>">
                                <?= $labelPerm ?>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Buttons -->
                <div class="col-12 text-center pt-2">
                    <button class="btn btn-success px-4"><i class="fa-solid fa-floppy-disk me-1"></i>บันทึก</button>
                    <a href="<?= $baseURL ?>/pages/superadmin/admin_manage/admins.php"
                        class="btn btn-link ms-3">ยกเลิก</a>
                </div>
            </form>
        </div>
    </div>
</main>

<?php include ROOT_PATH . '/public/includes/footer.php'; ?>