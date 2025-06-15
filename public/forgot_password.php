<?php
// File: public/forgot_password.php
session_start();

// 1) โหลดค่าคงที่ (ถ้ามี) และเชื่อมต่อฐานข้อมูล
require __DIR__ . '/../config/constants.php';
require __DIR__ . '/../config/db.php';

// 2) โหลด Composer autoload เพื่อใช้ App\Mail\Mailer
require __DIR__ . '/../vendor/autoload.php';
use App\Mail\Mailer;

// เตรียมตัวแปรสำหรับข้อความ
$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    // ตรวจสอบรูปแบบอีเมล
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'กรุณากรอกอีเมลให้ถูกต้อง';
    } else {
        // 3) ตรวจสอบว่าอีเมลนี้มีในตาราง users หรือไม่
        $stmt = $pdo->prepare("SELECT id, name FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            // ไม่บอกผู้ใช้ว่ามีหรือไม่มีอีเมล เพื่อความปลอดภัย
            $success = 'หากอีเมลนี้มีอยู่ในระบบ คุณจะได้รับลิงก์รีเซ็ตรหัสผ่านภายในไม่กี่นาที';
        } else {
            // 4) สร้างโทเค็นรีเซ็ต
            $token = bin2hex(random_bytes(16));
            $now   = date('Y-m-d H:i:s');

            // 5) บันทึก token ลงตาราง password_resets
            $ins = $pdo->prepare("
                INSERT INTO password_resets (email, token, created_at)
                VALUES (?, ?, ?)
            ");
            $ins->execute([$email, $token, $now]);

            // 6) ส่งอีเมลรีเซ็ตผ่าน Mailer
            $sent = Mailer::sendResetPassword(
                $email,
                $user['name'],
                $token
            );

            if ($sent) {
                $success = 'ส่งลิงก์รีเซ็ตรหัสผ่านไปที่อีเมลของคุณแล้ว';
            } else {
                $error = 'เกิดข้อผิดพลาดในการส่งอีเมล กรุณาลองใหม่';
            }
        }
    }
}

$pageTitle = 'ลืมรหัสผ่าน – Nano Friend';
include __DIR__ . '/includes/header.php';
?>

<div class="login-wrapper">
    <div class="login-card shadow-sm">
        <h2 class="text-center mb-4">ลืมรหัสผ่าน</h2>

        <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php elseif ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <?php if (!$success): ?>
        <form method="post" action="">
            <div class="mb-3">
                <label for="email" class="form-label">กรอกอีเมลของคุณ</label>
                <input type="email" class="form-control form-control-lg" id="email" name="email"
                    placeholder="you@example.com" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>
            <button type="submit" class="btn btn-primary btn-lg w-100">ส่งลิงก์รีเซ็ตรหัสผ่าน</button>
            <div class="mt-3 text-center">
                <a href="login.php" class="text-decoration-none">กลับไปหน้าล็อกอิน</a>
            </div>
        </form>
        <?php endif; ?>
    </div>
</div>

<!-- Bootstrap JS Bundle -->
<script src="assets/js/bootstrap.bundle.min.js"></script>
</body>

</html>