<?php
// File: public/pages/superadmin/shop_manage/shop_create.php

require_once realpath(__DIR__ . '/../../../../config/bootstrap.php');
require_once ROOT_PATH . '/includes/helpers.php';
if (session_status() === PHP_SESSION_NONE) session_start();
requireRole(['superadmin', 'admin']);


require_once ROOT_PATH . '/config/db.php';

// 1) หาค่า next ID เพื่อสร้างรหัสร้านค้า NNF-xxxx
$stmtMax = $pdo->query("
    SELECT MAX(id) AS max_id
    FROM users
    WHERE role = 'shop'
");
$row          = $stmtMax->fetch(PDO::FETCH_ASSOC);
$nextId       = ($row['max_id'] ?? 0) + 1;
$generatedCode = 'NNF-' . (1000 + $nextId);

// 2) อ่านค่า POST
$latitude   = $_POST['latitude']  ?? '';
$longitude  = $_POST['longitude'] ?? '';
$username   = trim($_POST['username']   ?? '');
$email      = trim($_POST['email']      ?? '');
$shop_name  = trim($_POST['shop_name']  ?? '');
$is_active  = isset($_POST['is_active']) ? 1 : 0;
$errors     = [];

// 3) เมื่อกดบันทึก
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password']        ?? '';
    $confirm  = $_POST['confirm_password']?? '';

    // Validate
    if ($username === '') {
        $errors[] = 'กรุณาระบุ Username';
    }
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'กรุณาระบุอีเมลให้ถูกต้อง';
    }
    if ($shop_name === '') {
        $errors[] = 'กรุณาระบุชื่อร้าน';
    }
    if ($password === '') {
        $errors[] = 'กรุณาระบุรหัสผ่าน';
    } elseif ($password !== $confirm) {
        $errors[] = 'รหัสผ่านไม่ตรงกัน';
    }

    // ตรวจซ้ำ
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = 'Username หรือ อีเมล ถูกใช้งานไปแล้ว';
        }
    }

    // บันทึกถ้าไม่มี error
    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("
            INSERT INTO users
              (username, email, password, role, name, is_active, latitude, longitude)
            VALUES
              (?,        ?,     ?,        'shop', ?,    ?,         ?,        ?)
        ");
        $stmt->execute([
            $username,
            $email,
            $hash,
            $shop_name,
            $is_active,
            $latitude ?: null,
            $longitude ?: null
        ]);
        $newId = $pdo->lastInsertId();

        logActivity(
            $pdo,
            $_SESSION['user']['id'],
            'create_shop',
            'shop',
            $newId,
            "สร้างร้านค้า {$shop_name} ({$generatedCode})"
        );

        header("Location: {$baseURL}/pages/superadmin/shop_manage/shop_manage.php");
        exit;
    }
}

// 4) Metadata สำหรับ header.php
$pageTitle  = 'เพิ่มร้านค้าใหม่';
$pageStyles = [
    '/assets/css/dashboard.css',
    '/assets/css/shop_create.css',
];

include ROOT_PATH . '/public/includes/header.php';
include ROOT_PATH . '/public/includes/sidebar.php';
?>

<!-- Leaflet CSS/JS (โหลดตรงนี้ ไม่มี integrity) -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.3/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.3/dist/leaflet.js"></script>

<main class="main-content">
    <header class="app-header d-flex justify-content-between align-items-center">
        <h2 class="header-title m-0">
            <i class="fa-solid fa-plus me-2"></i><?= htmlspecialchars($pageTitle) ?>
        </h2>
        <div class="header-actions">
            <button id="sidebarToggle" class="btn-icon"><i class="fa-solid fa-bars"></i></button>
            <button id="themeToggle" class="btn-icon ms-2"><i id="themeIcon" class="fa-solid"></i></button>
        </div>
    </header>
    <hr>

    <div class="card mb-4" style="background-color: var(--glass-bg); color: var(--text-main);">
        <div class="card-body">
            <?php if ($errors): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $e): ?>
                    <li><?= htmlspecialchars($e) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <form method="post" novalidate>
                <!-- รหัสร้านค้า -->
                <div class="mb-3">
                    <label class="form-label">รหัสร้านค้า</label>
                    <input type="text" class="form-control text-dark" value="<?= htmlspecialchars($generatedCode) ?>"
                        disabled>
                </div>
                <!-- ฟอร์มข้อมูล -->
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" id="username" name="username" class="form-control"
                        value="<?= htmlspecialchars($username) ?>" required>
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" id="email" name="email" class="form-control"
                        value="<?= htmlspecialchars($email) ?>" required>
                </div>
                <div class="mb-3">
                    <label for="shop_name" class="form-label">ชื่อร้าน</label>
                    <input type="text" id="shop_name" name="shop_name" class="form-control"
                        value="<?= htmlspecialchars($shop_name) ?>" required>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="password" class="form-label">รหัสผ่าน</label>
                        <input type="password" id="password" name="password" class="form-control" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="confirm_password" class="form-label">ยืนยันรหัสผ่าน</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control"
                            required>
                    </div>
                </div>
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active"
                        <?= $is_active ? 'checked' : '' ?>>
                    <label class="form-check-label" for="is_active">เปิดใช้งาน (Active)</label>
                </div>

                <!-- แผนที่เลือกตำแหน่ง -->
                <div class="mb-3">
                    <label class="form-label">ตำแหน่งร้านบนแผนที่</label>
                    <div id="map" style="height: 500px; border:1px solid var(--icon-stroke);"></div>
                    <small class="text-sub">คลิกบนแผนที่เพื่อเลือกตำแหน่งร้าน</small>
                </div>

                <!-- เก็บพิกัด -->
                <input type="hidden" name="latitude" id="latitude" value="<?= htmlspecialchars($latitude) ?>">
                <input type="hidden" name="longitude" id="longitude" value="<?= htmlspecialchars($longitude) ?>">

                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-save me-1"></i>บันทึก
                </button>
                <a href="<?= $baseURL ?>/pages/superadmin/shop_manage/shop_manage.php"
                    class="btn btn-outline-secondary ms-2">ยกเลิก</a>
            </form>
        </div>
    </div>
</main>

<?php include ROOT_PATH . '/public/includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const map = L.map('map').setView([13.736717, 100.523186], 6);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    let marker;
    const latInput = document.getElementById('latitude'),
        lngInput = document.getElementById('longitude');

    if (latInput.value && lngInput.value) {
        marker = L.marker([latInput.value, lngInput.value]).addTo(map);
        map.setView([latInput.value, lngInput.value], 16);
    }

    map.on('click', e => {
        const {
            lat,
            lng
        } = e.latlng;
        if (marker) map.removeLayer(marker);
        marker = L.marker([lat, lng]).addTo(map);
        latInput.value = lat.toFixed(7);
        lngInput.value = lng.toFixed(7);
    });
});
</script>