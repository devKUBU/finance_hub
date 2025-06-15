<?php
session_start();
if (!empty($_SESSION['user'])) {
    header('Location: pages/admin/dashboard.php');
    exit;
}

$pageTitle    = 'สมัครสมาชิก – Nano Friend';
$hideNav      = true;  // ซ่อน Navbar
include __DIR__ . '/includes/header.php';

// ดึง error ที่ถูกเซ็ตมาจาก process
$error = $_SESSION['error'] ?? '';
unset($_SESSION['error']);
?>
<div class="page-flex login-bg">
    <main class="flex-grow-1 d-flex flex-column align-items-center justify-content-start pt-5">
        <!-- หัวข้อ -->
        <h1 class="login-title mb-4">
            <i data-feather="user-plus"></i>
            สมัครสมาชิก
        </h1>

        <div class="glass-card">
            <!-- แสดง error ถ้ามี -->
            <?php if ($error): ?>
            <div class="alert alert-danger small"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form class="login-form" method="post" action="register_process.php">
                <!-- ชื่อ-นามสกุล -->
                <div class="slim-field mb-3">
                    <span class="circle"><i data-feather="user"></i></span>
                    <input type="text" name="fullname" class="slim-input" placeholder="ชื่อ-นามสกุล" required>
                </div>

                <!-- อีเมล -->
                <div class="slim-field mb-3">
                    <span class="circle"><i data-feather="mail"></i></span>
                    <input type="email" name="email" class="slim-input" placeholder="อีเมล" required>
                </div>

                <!-- รหัสผ่าน -->
                <div class="slim-field mb-3 position-relative">
                    <span class="circle"><i data-feather="lock"></i></span>
                    <input type="password" id="password" name="password" class="slim-input" placeholder="รหัสผ่าน"
                        required>
                    <span class="eye" onclick="togglePassword()"><i data-feather="eye"></i></span>
                </div>

                <!-- ยืนยันรหัสผ่าน -->
                <div class="slim-field mb-4 position-relative">
                    <span class="circle"><i data-feather="lock"></i></span>
                    <input type="password" id="confirmPassword" name="confirm_password" class="slim-input"
                        placeholder="ยืนยันรหัสผ่าน" required>
                    <span class="eye" onclick="toggleConfirmPassword()"><i data-feather="eye"></i></span>
                </div>

                <!-- ปุ่มสมัคร -->
                <div class="btn-wrap">
                    <button type="submit" class="btn-gradient">
                        <span class="spinner"></span>
                        <span class="btn-text">สมัครสมาชิก</span>
                    </button>
                </div>

                <!-- ลิงก์กลับ -->
                <div class="d-flex justify-content-between small">
                    <a href="login.php" class="small-link">
                        <i data-feather="arrow-left"></i> กลับไปล็อกอิน
                    </a>
                </div>
            </form>
        </div>
    </main>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

<!-- ฟังก์ชันสำหรับ toggle รหัสผ่าน + spinner -->
<script>
feather.replace();

function togglePassword() {
    const p = document.getElementById('password');
    p.type = p.type === 'password' ? 'text' : 'password';
}

function toggleConfirmPassword() {
    const p = document.getElementById('confirmPassword');
    p.type = p.type === 'password' ? 'text' : 'password';
}
document.querySelector('.login-form').addEventListener('submit', function() {
    const btn = this.querySelector('.btn-gradient');
    btn.classList.add('loading');
});
</script>