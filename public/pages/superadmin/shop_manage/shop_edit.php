<?php
// File: public/pages/superadmin/shop_manage/shop_edit.php

require_once realpath(__DIR__ . '/../../../../config/bootstrap.php');
require_once ROOT_PATH . '/includes/helpers.php';
if (session_status() === PHP_SESSION_NONE) session_start();
requireRole(['superadmin', 'admin']);

require_once ROOT_PATH . '/config/db.php';

// — LOAD & VALIDATE —
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id < 1) {
    header("Location: {$baseURL}/pages/superadmin/shop_manage/shop_manage.php");
    exit;
}
$error = '';

// — HANDLE POST —
// — HANDLE POST —
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 0) ให้ PDO โยน exception เมื่อ error
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1) รับค่าจากฟอร์ม
    $username  = trim($_POST['username']);
    $email     = trim($_POST['email']);
    $shopName  = trim($_POST['shop_name']);
    $address   = trim($_POST['address']);
    $phone     = trim($_POST['phone']);
    $taxId     = trim($_POST['tax_id']);
    $website   = trim($_POST['website']);
    $lineId    = trim($_POST['line_id']);
    $isActive  = isset($_POST['is_active']) ? 1 : 0;
    $lat       = trim($_POST['latitude']);
    $lng       = trim($_POST['longitude']);

    // 2) รหัสผ่านใหม่ (ถ้ามี)
    $newPass   = $_POST['new_password']     ?? '';
    $confirm   = $_POST['confirm_password'] ?? '';
    $hash      = '';
    if ($newPass !== '') {
        if ($newPass !== $confirm) {
            $error = 'รหัสผ่านทั้งสองช่องไม่ตรงกัน';
        } else {
            $hash = password_hash($newPass, PASSWORD_DEFAULT);
        }
    }

    // 3) ถ้าไม่มี error ให้รันอัพเดต
    if (empty($error)) {
        try {
            // สร้าง SET clause & params
            $fields = [
                'username = ?', 'email = ?', 'name = ?', 'address = ?',
                'phone = ?', 'tax_id = ?', 'website = ?', 'line_id = ?',
                'is_active = ?', 'latitude = ?', 'longitude = ?'
            ];
            $params = [
                $username, $email, $shopName, $address,
                $phone, $taxId, $website, $lineId,
                $isActive, $lat, $lng
            ];
            if ($hash) {
                $fields[]  = 'password = ?';
                $params[] = $hash;
            }
            $params[] = $id;

            $sql  = 'UPDATE users SET '. implode(', ', $fields) ." WHERE id = ? AND role = 'shop'";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            // สำเร็จ → กลับหน้า manage
            header("Location: {$baseURL}/pages/superadmin/shop_manage/shop_manage.php");
            exit;

        } catch (Exception $e) {
            // เก็บข้อความ error ไว้แสดงบนฟอร์ม
            $error = 'Error: ' . $e->getMessage();
        }
    }
}


// — FETCH EXISTING —
$stmt = $pdo->prepare("
    SELECT username,email,name AS shop_name,address,phone,tax_id,website,line_id,
           is_active,latitude,longitude,created_at
      FROM users
     WHERE id = ? AND role = 'shop'
");
$stmt->execute([$id]);
$shop = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$shop) {
    echo "<div class='alert alert-danger m-4'>ไม่พบข้อมูลร้านค้า</div>";
    exit;
}
$dt      = new DateTime($shop['created_at'], new DateTimeZone('Asia/Bangkok'));
$created = $dt->format('d/m/') . ($dt->format('Y')+543) . $dt->format(' H:i:s');
$theme   = $_SESSION['user']['theme'] ?? 'light';

$pageTitle = 'แก้ไขร้านค้า';
include ROOT_PATH . '/public/includes/header.php';
include ROOT_PATH . '/public/includes/sidebar.php';
echo '<link rel="stylesheet" href="'. htmlspecialchars($baseURL) .'/assets/css/dashboard.css">';
?>

<!— Leaflet CSS —>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.3/dist/leaflet.css">

    <style>
    /* Section headers */
    .section-header {
        font-size: 1rem;
        margin: 2rem 0 1rem;
        padding-bottom: .25rem;
        border-bottom: 2px solid var(--bs-primary);
        color: var(--bs-primary);
    }

    /* Input group icons */
    .input-group-text {
        background: var(--bs-light);
    }

    /* Map styling */
    #map {
        height: 500px;
        border-radius: .25rem;
    }

    /* Card padding */
    .card-body {
        padding: 2rem;
    }

    /* Dark‐theme headers */
    [data-theme="dark"] .form-label,
    [data-theme="dark"] .form-check,
    [data-theme="dark"] .form-control-plaintext {
        color: #f1f1f1 !important;
    }

    [data-theme="dark"] .card-header {
        background-color: #343a40 !important;
        color: #f8f9fa !important;
        /* ตัวอักษรสีขาวอ่อน */
        border-bottom: 1px solid #495057;
    }

    [data-theme="dark"] .section-header {
        color: #f8f9fa !important;
        /* หัวข้อแต่ละ Section ให้สีอ่อน */
        border-bottom-color: #6c757d;
        /* เส้นกั้นหัวข้อให้สว่างขึ้น */
    }

    [data-theme="dark"] .main-content h3,
    [data-theme="dark"] .main-content h2 {
        color: #f8f9fa !important;
        /* ปรับหัวข้อหลักบนหน้า */
    }
    </style>

    <main class="main-content">
        <div class="container-fluid py-4">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white d-flex align-items-center">
                    <i class="fa-solid fa-pen-to-square me-2"></i>
                    <h5 class="mb-0">แก้ไขร้านค้า NNF-<?=1000+$id?></h5>
                </div>
                <div class="card-body">

                    <?php if ($error): ?>
                    <div class="alert alert-warning"><?=htmlspecialchars($error)?></div>
                    <?php endif ?>

                    <form method="post" class="needs-validation" novalidate>

                        <!-- SECTION: Basic Info -->
                        <div class="section-header">ข้อมูลพื้นฐาน</div>
                        <div class="row gx-3 gy-4">
                            <div class="col-md-4">
                                <label class="form-label">รหัสร้านค้า</label>
                                <input type="text" readonly class="form-control-plaintext" value="NNF-<?=1000+$id?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">วันที่สร้าง</label>
                                <input type="text" readonly class="form-control-plaintext" value="<?=$created?>">
                            </div>
                            <div class="col-md-4 text-end d-flex align-items-center justify-content-end">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active"
                                        <?=$shop['is_active']?'checked':''?>>
                                    <label class="form-check-label" for="is_active">Active</label>
                                </div>
                            </div>
                        </div>

                        <!-- SECTION: Credentials -->
                        <div class="section-header">บัญชีเข้าใช้งาน</div>
                        <div class="row gx-3 gy-4">
                            <div class="col-md-4">
                                <label for="username" class="form-label">Username*</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fa-solid fa-user"></i></span>
                                    <input id="username" name="username" class="form-control" required
                                        value="<?=htmlspecialchars($shop['username'])?>">
                                    <div class="invalid-feedback">กรุณากรอก Username</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label for="email" class="form-label">Email*</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fa-solid fa-envelope"></i></span>
                                    <input id="email" type="email" name="email" class="form-control" required
                                        value="<?=htmlspecialchars($shop['email'])?>">
                                    <div class="invalid-feedback">กรุณากรอก Email ให้ถูกต้อง</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label for="new_password" class="form-label">รหัสผ่านใหม่</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
                                    <input id="new_password" type="password" name="new_password" class="form-control"
                                        placeholder="เว้นว่างถ้าไม่เปลี่ยน">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label for="confirm_password" class="form-label">ยืนยันรหัสผ่าน</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
                                    <input id="confirm_password" type="password" name="confirm_password"
                                        class="form-control" placeholder="ยืนยันอีกครั้ง">
                                </div>
                            </div>
                        </div>

                        <!-- SECTION: Shop Details -->
                        <div class="section-header">รายละเอียดร้านค้า</div>
                        <div class="row gx-3 gy-4">
                            <div class="col-md-6">
                                <label for="shop_name" class="form-label">ชื่อร้าน*</label>
                                <input id="shop_name" name="shop_name" class="form-control" required
                                    value="<?=htmlspecialchars($shop['shop_name'])?>">
                                <div class="invalid-feedback">กรุณากรอก ชื่อร้าน</div>
                            </div>
                            <div class="col-md-6">
                                <label for="address" class="form-label">ที่อยู่</label>
                                <input id="address" name="address" class="form-control"
                                    value="<?=htmlspecialchars($shop['address'])?>">
                            </div>
                            <div class="col-md-3">
                                <label for="phone" class="form-label">โทรศัพท์</label>
                                <input id="phone" name="phone" class="form-control"
                                    value="<?=htmlspecialchars($shop['phone'])?>">
                            </div>
                            <div class="col-md-3">
                                <label for="tax_id" class="form-label">Tax ID</label>
                                <input id="tax_id" name="tax_id" class="form-control"
                                    value="<?=htmlspecialchars($shop['tax_id'])?>">
                            </div>
                            <div class="col-md-3">
                                <label for="line_id" class="form-label">Line ID</label>
                                <input id="line_id" name="line_id" class="form-control"
                                    value="<?=htmlspecialchars($shop['line_id'])?>">
                            </div>
                            <div class="col-md-3">
                                <label for="website" class="form-label">Website</label>
                                <div class="input-group has-validation">
                                    <span class="input-group-text"><i class="fa-solid fa-link"></i></span>
                                    <input id="website" type="text" name="website" class="form-control"
                                        placeholder="https://example.com"
                                        value="<?=htmlspecialchars($shop['website'])?>">
                                    <div class="invalid-feedback">
                                        กรุณากรอก Website ให้ถูกต้อง (รวม http:// หรือ https://)
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- SECTION: Location -->
                        <div class="section-header">ตำแหน่งร้านค้า</div>
                        <div class="row gx-3 gy-4">
                            <div class="col-12">
                                <div id="map"></div>
                            </div>
                            <div class="col-md-6">
                                <label for="latitude" class="form-label">Latitude</label>
                                <input id="latitude" name="latitude" class="form-control"
                                    value="<?=htmlspecialchars($shop['latitude'])?>">
                            </div>
                            <div class="col-md-6">
                                <label for="longitude" class="form-label">Longitude</label>
                                <input id="longitude" name="longitude" class="form-control"
                                    value="<?=htmlspecialchars($shop['longitude'])?>">
                            </div>
                        </div>

                        <!-- ACTIONS -->
                        <div class="mt-5 text-end">
                            <a href="<?= $baseURL ?>/pages/superadmin/shop_manage/shop_manage.php"
                                class="btn btn-outline-secondary me-2">
                                <i class="fa-solid fa-arrow-left me-1"></i> ยกเลิก
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fa-solid fa-check me-1"></i> บันทึก
                            </button>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </main>

    <?php include ROOT_PATH . '/public/includes/footer.php'; ?>

    <!-- JS: Bootstrap Bundle + Leaflet -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.3/dist/leaflet.js"></script>

    <script>
    // Bootstrap validation
    (() => {
        'use strict';
        document.querySelectorAll('.needs-validation').forEach(f => {
            f.addEventListener('submit', e => {
                if (!f.checkValidity()) {
                    e.preventDefault();
                    e.stopPropagation();
                }
                f.classList.add('was-validated');
            });
        });
    })();

    // Leaflet map init
    const lat0 = parseFloat('<?= $shop['latitude'] ?: 13.736717 ?>');
    const lng0 = parseFloat('<?= $shop['longitude'] ?: 100.523186 ?>');
    const map = L.map('map').setView([lat0, lng0], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);
    const marker = L.marker([lat0, lng0], {
        draggable: true
    }).addTo(map);
    marker.on('dragend', () => {
        const p = marker.getLatLng();
        document.getElementById('latitude').value = p.lat.toFixed(6);
        document.getElementById('longitude').value = p.lng.toFixed(6);
    });
    </script>