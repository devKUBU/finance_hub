<?php
session_start();

// ถ้าล็อกอินแล้ว ให้ไปหน้าแดชบอร์ด
if (!empty($_SESSION['user'])) {
    // สมมติว่า $baseURL ถูกกำหนดไว้ใน header.php หรือไฟล์ config ที่ header.php โหลดมา
    // หาก $baseURL ไม่ได้ถูกกำหนดใน header.php ก่อนเรียกใช้ที่นี่ อาจจะต้อง include ไฟล์ config ที่มี $baseURL ก่อน
    // หรือถ้า header.php กำหนด $baseURL อยู่แล้ว ก็ไม่จำเป็นต้องทำอะไรเพิ่มเติม
    if (empty($baseURL) && file_exists(__DIR__ . '/config.php')) { // ตัวอย่างการโหลด config หาก $baseURL ไม่ได้มาจาก header
         require_once __DIR__ . '/config.php';
    } else if (empty($baseURL) && file_exists(__DIR__ . '/includes/config.php')) {
         require_once __DIR__ . '/includes/config.php';
    }
    // ตรวจสอบอีกครั้งว่า $baseURL ถูกกำหนดค่าแล้ว
    if (empty($baseURL)) {
        // ตั้งค่า default หากยังไม่ได้ตั้ง (ควรตั้งค่าให้ถูกต้องใน config ของคุณ)
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        $host = $_SERVER['HTTP_HOST'];
        $baseURL = $protocol . $host . str_replace(basename($_SERVER['SCRIPT_NAME']), '', $_SERVER['SCRIPT_NAME']);
        // ลบ / ท้ายสุดถ้ามี เพื่อให้สอดคล้องกับการใช้งาน $baseURL . '/path'
        if (substr($baseURL, -1) == '/') {
            $baseURL = substr($baseURL, 0, -1);
        }
    }
    header('Location: ' . $baseURL . '/pages/admin/dashboard.php');
    exit;
}

// บอก header.php ว่าเป็นหน้า Login
$hideNav   = true;
$pageTitle = 'เข้าสู่ระบบ – Nano Friend';

// โหลด header (จะได้ $baseURL, CSS ธีม และ login.css มาอัตโนมัติ)
require_once __DIR__ . '/includes/header.php';

// ดึง error message (ถ้ามี)
$error = $_SESSION['error'] ?? '';
unset($_SESSION['error']);
?>

<div class="login-bg login-wrapper">

    <div class="system-header text-center mb-4">
        <span class="h5">ระบบจัดการ Nano Friend Technology</span>
    </div>

    <div class="glass-card login-card">
        <img src="<?= $baseURL ?>/assets/images/logo.png" class="brand mb-3" alt="Nano Friend">
        <h2 class="fw-light mb-4">เข้าสู่ระบบ</h2>

        <?php if ($error): ?>
        <div class="alert alert-danger small"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form class="login-form" method="POST" action="<?= $baseURL ?>/authenticate.php">
            <div class="slim-field mb-3">
                <span class="circle"><i data-feather="user"></i></span>
                <input type="text" name="login" class="slim-input" placeholder="ชื่อผู้ใช้" required>
            </div>
            <div class="slim-field mb-4 position-relative">
                <span class="circle"><i data-feather="lock"></i></span>
                <input type="password" id="password" name="password" class="slim-input" placeholder="รหัสผ่าน" required>
                <span class="eye" onclick="togglePassword()"><i data-feather="eye"></i></span>
            </div>
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" id="rememberMe" name="remember_me">
                <label class="form-check-label" for="rememberMe">จดจำฉันไว้</label>
            </div>
            <div class="btn-wrap">
                <button type="submit" class="btn-gradient">
                    <span class="spinner"></span>
                    <span class="btn-text">เข้าสู่ระบบ</span>
                </button>
            </div>

            <div class="partner-link-section text-center mt-4">
                <a href="https://line.me/R/ti/p/@387mkssg" class="btn-partner-line">
                    <img src="<?= $baseURL ?>/assets/images/line_icon.png" alt="LINE" style="width: 2rem;"
                        class="line-icon">
                    สมัครพาร์ทเนอร์ที่นี่
                </a>
            </div>
        </form>
    </div>
</div>

<?php
// โหลด footer (login.js จะถูกโหลดอัตโนมัติเพราะ $hideNav = true)
require_once __DIR__ . '/includes/footer.php';
?>

<script src="https://unpkg.com/feather-icons"></script>
<script>
feather.replace();

function togglePassword() {
    const p = document.getElementById('password');
    p.type = p.type === 'password' ? 'text' : 'password';
}

// ตรวจสอบว่า element มีจริงก่อนเพิ่ม event listener
const loginForm = document.querySelector('.login-form');
if (loginForm) {
    loginForm.addEventListener('submit', function() {
        const button = this.querySelector('.btn-gradient');
        if (button) {
            button.classList.add('loading');
        }
    });
}
</script>