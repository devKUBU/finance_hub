<?php
// File: public/pages/superadmin/apple_ids/apple_id_form.php
// -------------------------------------------------
// Superadmin: Add / Edit Apple ID (plaintext password, random generator, AJAX‐save)
// -------------------------------------------------

date_default_timezone_set('Asia/Bangkok');

// 1) LOAD BOOTSTRAP & HELPERS
require_once realpath(__DIR__ . '/../../../../config/bootstrap.php');
require_once ROOT_PATH . '/includes/helpers.php';
if (session_status() === PHP_SESSION_NONE) session_start();
requireRole(['superadmin', 'admin']);


require_once ROOT_PATH . '/config/db.php';
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$appleId   = '';
$password  = '';
$pincode   = '';
$isActive  = 1;        // ให้ active เป็นค่า default
$errors    = [];
$isAjax    = false;

$errors = [];
$id     = isset($_POST['id']) ? (int)$_POST['id']
        : (isset($_GET['id']) ? (int)$_GET['id'] : 0);

/* ---------- ดึงแถวเดิม (ถ้ามี) เพื่อเทียบค่าเก่า ---------- */
$row     = $id ? loadApple($pdo,$id) : [];
$history = json_decode($row['history'] ?? '[]', true);   // <–- history เก่า (array)

/* ---------- POST ---------- */
if ($_SERVER['REQUEST_METHOD']==='POST') {

    $appleId  = trim($_POST['apple_id'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $pincode  = trim($_POST['pincode']  ?? '');
    $isActive = isset($_POST['is_active']) ? 1 : 0;

    /* validate + unique (เหมือนเดิม) … ------------------------------------------------ */

    /* ----------------------------------------------------- */
    /* 1) ถ้า “มีการแก้” รหัสผ่านหรือพิน → บันทึกลง history */
    /* ----------------------------------------------------- */
    if ($id && empty($errors)) {          // ต้องเป็นโหมดแก้ไข และไม่มี error validate
        if ($row['password'] !== $password || $row['pincode'] !== $pincode) {
            $history[] = [
                'changed_at'   => date('c'),          // ISO-8601
                'old_password' => $row['password'],
                'old_pincode'  => $row['pincode'],
                'changed_by'   => $_SESSION['user']['id'] ?? 0   // id ผู้ใช้
            ];
        }
    }

    /* ----------------------------------------------------- */
    /* 2) บันทึกฐานข้อมูล                                        */
    /* ----------------------------------------------------- */
    $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
              $_SERVER['HTTP_X_REQUESTED_WITH']==='XMLHttpRequest';
    if ($isAjax)  header('Content-Type: application/json; charset=utf-8');

    if ($errors){
        if ($isAjax){ echo json_encode(['success'=>false,'errors'=>$errors]); exit; }
    }else{
        if ($id){      /* UPDATE */
            $stmt = $pdo->prepare("
              UPDATE apple_ids
                 SET apple_id  = ?,
                     password  = ?,
                     pincode   = ?,
                     is_active = ?,
                     history   = ?,                 -- ⭐ เก็บประวัติ
                     updated_at= CURRENT_TIMESTAMP
               WHERE id = ?
            ");
            $stmt->execute([
                $appleId,$password,$pincode,$isActive,
                json_encode($history,JSON_UNESCAPED_UNICODE),
                $id
            ]);
        }else{         /* INSERT */
            $stmt = $pdo->prepare("
              INSERT INTO apple_ids
                    (apple_id,password,pincode,is_active,
                     contracts,history,created_at,updated_at)
              VALUES (?,?,?,?, '[]', ?, CURRENT_TIMESTAMP,CURRENT_TIMESTAMP)
            ");
            $stmt->execute([
                $appleId,$password,$pincode,$isActive,
                json_encode([],JSON_UNESCAPED_UNICODE)   // history ใหม่ = []
            ]);
        }

        if ($isAjax){ echo json_encode(['success'=>true]); exit; }
        header('Location: apple_ids.php'); exit;
    }
}

// HELPERS
function loadApple(PDO $pdo, int $id): array {
    $stmt = $pdo->prepare("SELECT * FROM apple_ids WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    return (array)$stmt->fetch(PDO::FETCH_ASSOC);
}
function randomPassword(int $len = 12): string {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()';
    $pw = '';
    for ($i = 0; $i < $len; $i++) {
        $pw .= $chars[random_int(0, strlen($chars)-1)];
    }
    return $pw;
}


// PAGE METADATA
$pageTitle  = $id ? '🖋️ แก้ไข Apple ID' : '➕ เพิ่ม Apple ID';
$pageStyles = ['/assets/css/dashboard.css'];
include ROOT_PATH . '/public/includes/header.php';
include ROOT_PATH . '/public/includes/sidebar.php';
?>

<link rel="stylesheet" href="<?=$baseURL?>/assets/css/dashboard.css">

<main class="main-content">
    <div class="container-fluid py-4">
        <!-- HEADER -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="header-title">
                <i class="fa-solid fa-user-apple me-2"></i><?= htmlspecialchars($pageTitle) ?>
            </h2>
            <div class="header-actions">
                <a href="apple_ids.php" class="btn btn-outline-secondary me-2">
                    <i class="fa-solid fa-arrow-left me-1"></i>กลับ
                </a>
                <button id="themeToggle" class="btn btn-outline-secondary">
                    <i id="themeIcon" class="fa-solid fa-moon"></i>
                </button>
            </div>
        </div>

        <!-- ERRORS -->
        <?php if ($errors && !$isAjax): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach($errors as $e): ?>
                <li><?= htmlspecialchars($e) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <!-- FORM -->
        <div class="card shadow-sm">
            <div class="card-body">
                <form id="appleForm" method="post" class="row g-3">
                    <!-- Apple ID -->
                    <div class="col-md-6">
                        <label class="form-label">Apple ID <span class="text-danger">*</span></label>
                        <input id="appleIdInput" name="apple_id" type="text" class="form-control" required
                            value="<?= htmlspecialchars($appleId) ?>">
                        <div id="appleIdFeedback" class="form-text text-danger d-none">
                            Apple ID นี้ถูกใช้งานแล้ว
                        </div>
                    </div>
                    <!-- Password -->
                    <div class="col-md-4">
                        <label class="form-label">Password <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input id="pwInput" name="password" type="text" class="form-control" required
                                value="<?= htmlspecialchars($password) ?>">
                            <button type="button" id="rndPwBtn" class="btn btn-outline-secondary" title="สุ่มรหัสผ่าน">
                                <i class="fa-solid fa-rotate"></i>
                            </button>
                        </div>
                    </div>
                    <!-- Pincode -->
                    <div class="col-md-2">
                        <label class="form-label">Pincode <span class="text-danger">*</span></label>
                        <input name="pincode" type="text" pattern="\d{4}" maxlength="4" class="form-control" required
                            value="<?= htmlspecialchars($pincode) ?>">
                    </div>
                    <!-- Active -->
                    <div class="col-12">
                        <div class="form-check form-switch">
                            <input name="is_active" class="form-check-input" type="checkbox" id="chkActive"
                                <?= $isActive ? 'checked' : '' ?>>
                            <label class="form-check-label" for="chkActive">Active</label>
                        </div>
                    </div>
                    <!-- Submit -->
                    <div class="col-12 text-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-save me-1"></i>บันทึก
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Randomize password locally
document.getElementById('rndPwBtn').addEventListener('click', () => {
    const chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()';
    let pw = '';
    for (let i = 0; i < 12; i++) {
        pw += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    document.getElementById('pwInput').value = pw;
});

// AJAX save handler
document.getElementById('appleForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const data = new URLSearchParams(new FormData(this));
    const res = await fetch(window.location.href, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: data
    });
    const json = await res.json();
    if (json.success) {
        location.reload();
    } else {
        alert(json.errors.join('\n'));
    }
});
</script>
<?php include ROOT_PATH . '/public/includes/footer.php'; ?>