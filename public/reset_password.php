<?php
// File: public/reset_password.php
session_start();

// โหลดคอนฟิก และเชื่อม DB
require __DIR__ . '/../config/constants.php';
require __DIR__ . '/../config/db.php';

// 1) รับ token จาก URL
$token = $_GET['token'] ?? '';
if (!$token) {
    die('ลิงก์ไม่ถูกต้อง');
}

// 2) ดึง record จาก password_resets
$stmt = $pdo->prepare("SELECT email, created_at FROM password_resets WHERE token = ?");
$stmt->execute([$token]);
$reset = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$reset) {
    die('ลิงก์ไม่ถูกต้องหรือหมดอายุ');
}

// 3) เช็คอายุโทเค็น (1 ชั่วโมง)
$created = new DateTime($reset['created_at']);
if ((new DateTime())->getTimestamp() - $created->getTimestamp() > 3600) {
    die('ลิงก์หมดอายุแล้ว');
}

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pw1 = $_POST['password']         ?? '';
    $pw2 = $_POST['confirm_password'] ?? '';
    if ($pw1 === '' || $pw1 !== $pw2) {
        $error = 'รหัสผ่านไม่ตรงกันหรือว่างเปล่า';
    } else {
        // อัปเดตรหัสผ่าน
        $hash = password_hash($pw1, PASSWORD_DEFAULT);
        $upd  = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
        $upd->execute([$hash, $reset['email']]);

        // ลบทิ้ง token
        $del = $pdo->prepare("DELETE FROM password_resets WHERE token = ?");
        $del->execute([$token]);

        $success = 'เปลี่ยนรหัสผ่านเรียบร้อยแล้ว สามารถ <a href="login.php">ล็อกอิน</a> ได้ทันที';
    }
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>ตั้งรหัสผ่านใหม่ – Nano Friend</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/login.css" rel="stylesheet">
    <style>
    body,
    html {
        font-family: 'Prompt', sans-serif;
    }
    </style>
</head>

<body>
    <div class="login-card">
        <h2 class="text-center mb-4">ตั้งรหัสผ่านใหม่</h2>

        <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php elseif ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>

        <?php if (!$success): ?>
        <form method="post">
            <div class="mb-3">
                <label for="password" class="form-label">รหัสผ่านใหม่</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <div class="mb-3">
                <label for="confirm_password" class="form-label">ยืนยันรหัสผ่านใหม่</label>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">บันทึก</button>
        </form>
        <?php endif; ?>
    </div>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
</body>

</html>