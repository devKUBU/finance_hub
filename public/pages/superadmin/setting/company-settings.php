<?php
// File: C:\xampp\htdocs\nano-friend\public\pages\superadmin\setting\company-settings.php

require_once realpath(__DIR__ . '/../../../../config/bootstrap.php');
require_once ROOT_PATH . '/includes/helpers.php';
if (session_status() === PHP_SESSION_NONE) session_start();
requireRole(['superadmin', 'admin']);


require_once ROOT_PATH . '/config/db.php';
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// –– Ensure upload directory exists ––
$uploadDir = ROOT_PATH . '/public/uploads/settings/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// ฟังก์ชันสำหรับปรับขนาดรูปภาพ
function resizeImage($sourcePath, $targetPath, $maxWidth, $maxHeight, $quality = 85) {
    $imageInfo = getimagesize($sourcePath);
    if (!$imageInfo) return false;

    $sourceWidth = $imageInfo[0];
    $sourceHeight = $imageInfo[1];
    $mimeType = $imageInfo['mime'];

    // คำนวณขนาดใหม่โดยรักษาอัตราส่วน
    $ratio = min($maxWidth / $sourceWidth, $maxHeight / $sourceHeight);
    if ($ratio > 1) $ratio = 1; // ไม่ขยายภาพ

    $newWidth = round($sourceWidth * $ratio);
    $newHeight = round($sourceHeight * $ratio);

    // สร้าง resource ตามประเภทไฟล์
    switch ($mimeType) {
        case 'image/jpeg':
            $sourceImage = imagecreatefromjpeg($sourcePath);
            break;
        case 'image/png':
            $sourceImage = imagecreatefrompng($sourcePath);
            break;
        case 'image/gif':
            $sourceImage = imagecreatefromgif($sourcePath);
            break;
        case 'image/webp':
            $sourceImage = imagecreatefromwebp($sourcePath);
            break;
        default:
            return false;
    }

    if (!$sourceImage) return false;

    // สร้างภาพใหม่
    $targetImage = imagecreatetruecolor($newWidth, $newHeight);

    // รักษาความโปร่งใสสำหรับ PNG และ GIF
    if ($mimeType === 'image/png' || $mimeType === 'image/gif') {
        imagealphablending($targetImage, false);
        imagesavealpha($targetImage, true);
        $transparent = imagecolorallocatealpha($targetImage, 255, 255, 255, 127);
        imagefill($targetImage, 0, 0, $transparent);
    }

    imagecopyresampled($targetImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $sourceWidth, $sourceHeight);

    // บันทึกไฟล์ (แปลงเป็น JPEG สำหรับลดขนาด ยกเว้น PNG ที่ต้องการความโปร่งใส)
    if ($mimeType === 'image/png') {
        $success = imagepng($targetImage, $targetPath, 9);
    } else {
        $success = imagejpeg($targetImage, $targetPath, $quality);
    }

    imagedestroy($sourceImage);
    imagedestroy($targetImage);

    return $success;
}

// ฟังก์ชันตรวจสอบไฟล์รูปภาพ
function validateImageFile($file, $type = 'general') {
    $errors = [];

    // ตรวจสอบขนาดไฟล์
    $maxFileSize = 5 * 1024 * 1024; // 5MB
    if ($file['size'] > $maxFileSize) {
        $errors[] = 'ขนาดไฟล์ใหญ่เกินไป (สูงสุด 5MB)';
    }

    // ตรวจสอบประเภทไฟล์
    $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, $allowedMimes)) {
        $errors[] = 'ประเภทไฟล์ไม่ถูกต้อง (รองรับเฉพาะ JPG, PNG, GIF, WebP)';
    }

    // ตรวจสอบขนาดภาพ
    $imageInfo = getimagesize($file['tmp_name']);
    if (!$imageInfo) {
        $errors[] = 'ไฟล์ไม่ใช่รูปภาพที่ถูกต้อง';
    } else {
        $width = $imageInfo[0];
        $height = $imageInfo[1];

        // กำหนดขนาดสูงสุดตามประเภท
        if ($type === 'logo') {
            $maxWidth = 800;
            $maxHeight = 600;
            if ($width > $maxWidth || $height > $maxHeight) {
                $errors[] = "ขนาดโลโก้ใหญ่เกินไป (สูงสุด {$maxWidth}x{$maxHeight} pixels)";
            }
        } elseif ($type === 'qr') {
            $maxWidth = 500;
            $maxHeight = 500;
            if ($width > $maxWidth || $height > $maxHeight) {
                $errors[] = "ขนาด QR Code ใหญ่เกินไป (สูงสุด {$maxWidth}x{$maxHeight} pixels)";
            }
        }
    }

    return $errors;
}

// 1) Fetch existing settings (assume id=1)
$stmt = $pdo->prepare("SELECT * FROM company_settings WHERE id = 1 LIMIT 1");
$stmt->execute();
$settings = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$settings) {
    // default empty values
    $settings = [
        'id'               => null,
        'company_name'     => '',
        'company_address'  => '',
        'branch_name'      => '',
        'payment_methods'  => '',
        'line_qr_path'     => '',
        'logo_path'        => '',
        'line_id'          => '',
        'theme'            => 'light'  // สมมติมีคอลัมน์ theme
    ];
}

// ดึงค่าธีมจากฐานข้อมูล (ใส่ default ถ้าไม่มี)
$currentTheme = (isset($settings['theme']) && in_array($settings['theme'], ['light','dark']))
    ? $settings['theme']
    : 'light';

// 2) Handle form submit
$errors  = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // รับค่าจากฟอร์ม
    $companyName    = trim($_POST['company_name'] ?? '');
    $companyAddress = trim($_POST['company_address'] ?? '');
    $branchName     = trim($_POST['branch_name'] ?? '');
    $paymentMethods = trim($_POST['payment_methods'] ?? '');
    $lineIdInput    = trim($_POST['line_id'] ?? '');
    // ถ้า user เลือก checkbox จะได้ 'dark' ถ้าไม่เช็คให้เป็น 'light'
    $themeInput     = isset($_POST['theme']) && $_POST['theme'] === 'dark' ? 'dark' : 'light';

    // validate required fields
    if ($companyName === '')    $errors[] = 'กรุณาใส่ชื่อบริษัท';
    if ($companyAddress === '') $errors[] = 'กรุณาใส่ที่อยู่บริษัท';
    if ($branchName === '')     $errors[] = 'กรุณาใส่ชื่อสาขา';
    if ($paymentMethods === '') $errors[] = 'กรุณาใส่ช่องทางการชำระเงิน';

    // เตรียม theme ใหม่
    $newTheme = $currentTheme;
    if ($themeInput !== $currentTheme) {
        $newTheme = $themeInput;
    }

    // handle logo upload/deletion
    $newLogoPath = $settings['logo_path'];
    if (isset($_POST['clear_logo']) && $_POST['clear_logo'] === '1') {
        if (!empty($settings['logo_path']) && file_exists(ROOT_PATH . '/public/' . $settings['logo_path'])) {
            @unlink(ROOT_PATH . '/public/' . $settings['logo_path']);
        }
        $newLogoPath = '';
    } elseif (isset($_FILES['logo']) && !empty($_FILES['logo']['name'])) {
        if ($_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            // ตรวจสอบไฟล์โลโก้
            $logoErrors = validateImageFile($_FILES['logo'], 'logo');
            if (empty($logoErrors)) {
                // ลบไฟล์เดิม
                if (!empty($settings['logo_path']) && file_exists(ROOT_PATH . '/public/' . $settings['logo_path'])) {
                    @unlink(ROOT_PATH . '/public/' . $settings['logo_path']);
                }

                $tmp      = $_FILES['logo']['tmp_name'];
                $origName = basename($_FILES['logo']['name']);
                $ext      = pathinfo($origName, PATHINFO_EXTENSION);
                $filename = 'company_logo_' . time() . '.' . $ext;
                $dest     = $uploadDir . $filename;
                $destResized = $uploadDir . 'resized_' . $filename;

                // อัปโหลดไฟล์ต้นฉบับ
                if (move_uploaded_file($tmp, $dest)) {
                    // ปรับขนาดภาพ (โลโก้: สูงสุด 400x300)
                    if (resizeImage($dest, $destResized, 400, 300, 90)) {
                        @unlink($dest);
                        rename($destResized, $dest);
                    }
                    $newLogoPath = 'uploads/settings/' . $filename;
                } else {
                    $errors[] = 'ไม่สามารถอัปโหลดไฟล์โลโก้ได้';
                }
            } else {
                $errors = array_merge($errors, $logoErrors);
            }
        } else {
            $errors[] = 'เกิดข้อผิดพลาดในการอัปโหลดโลโก้ (รหัส ' . $_FILES['logo']['error'] . ')';
        }
    }

    // handle LINE QR upload/deletion
    $newLineQrPath = $settings['line_qr_path'];
    if (isset($_POST['clear_line_qr']) && $_POST['clear_line_qr'] === '1') {
        if (!empty($settings['line_qr_path']) && file_exists(ROOT_PATH . '/public/' . $settings['line_qr_path'])) {
            @unlink(ROOT_PATH . '/public/' . $settings['line_qr_path']);
        }
        $newLineQrPath = '';
    } elseif (isset($_FILES['line_qr']) && !empty($_FILES['line_qr']['name'])) {
        if ($_FILES['line_qr']['error'] === UPLOAD_ERR_OK) {
            // ตรวจสอบไฟล์ QR Code
            $qrErrors = validateImageFile($_FILES['line_qr'], 'qr');
            if (empty($qrErrors)) {
                // ลบไฟล์เดิม
                if (!empty($settings['line_qr_path']) && file_exists(ROOT_PATH . '/public/' . $settings['line_qr_path'])) {
                    @unlink(ROOT_PATH . '/public/' . $settings['line_qr_path']);
                }

                $tmp      = $_FILES['line_qr']['tmp_name'];
                $origName = basename($_FILES['line_qr']['name']);
                $ext      = pathinfo($origName, PATHINFO_EXTENSION);
                $filename = 'line_qr_' . time() . '.' . $ext;
                $dest     = $uploadDir . $filename;
                $destResized = $uploadDir . 'resized_' . $filename;

                // อัปโหลดไฟล์ต้นฉบับ
                if (move_uploaded_file($tmp, $dest)) {
                    // ปรับขนาดภาพ (QR Code: สูงสุด 300x300)
                    if (resizeImage($dest, $destResized, 300, 300, 95)) {
                        @unlink($dest);
                        rename($destResized, $dest);
                    }
                    $newLineQrPath = 'uploads/settings/' . $filename;
                } else {
                    $errors[] = 'ไม่สามารถอัปโหลดไฟล์ QR Code ได้';
                }
            } else {
                $errors = array_merge($errors, $qrErrors);
            }
        } else {
            $errors[] = 'เกิดข้อผิดพลาดในการอัปโหลด QR Code (รหัส ' . $_FILES['line_qr']['error'] . ')';
        }
    }

    if (empty($errors)) {
        if (!empty($settings['id'])) {
            // UPDATE
            $sql = "
                UPDATE company_settings
                   SET company_name     = :company_name,
                       company_address  = :company_address,
                       branch_name      = :branch_name,
                       payment_methods  = :payment_methods,
                       line_qr_path     = :line_qr_path,
                       logo_path        = :logo_path,
                       line_id          = :line_id
                 WHERE id = 1
            ";
            $stmtUp = $pdo->prepare($sql);
            $stmtUp->execute([
                ':company_name'     => $companyName,
                ':company_address'  => $companyAddress,
                ':branch_name'      => $branchName,
                ':payment_methods'  => $paymentMethods,
                ':line_qr_path'     => $newLineQrPath,
                ':logo_path'        => $newLogoPath,
                ':line_id'          => $lineIdInput

            ]);
        } else {
            // INSERT
            $sql = "
                INSERT INTO company_settings
                    (id, company_name, company_address, branch_name, payment_methods, line_qr_path, logo_path, line_id, theme)
                VALUES
                    (1, :company_name, :company_address, :branch_name, :payment_methods, :line_qr_path, :logo_path, :line_id, :theme)
            ";
            $stmtIn = $pdo->prepare($sql);
            $stmtIn->execute([
                ':company_name'     => $companyName,
                ':company_address'  => $companyAddress,
                ':branch_name'      => $branchName,
                ':payment_methods'  => $paymentMethods,
                ':line_qr_path'     => $newLineQrPath,
                ':logo_path'        => $newLogoPath,
                ':line_id'          => $lineIdInput

            ]);
        }

        // reload settings into array
        $settings['company_name']     = $companyName;
        $settings['company_address']  = $companyAddress;
        $settings['branch_name']      = $branchName;
        $settings['payment_methods']  = $paymentMethods;
        $settings['line_qr_path']     = $newLineQrPath;
        $settings['logo_path']        = $newLogoPath;
        $settings['line_id']          = $lineIdInput;
        $settings['theme']            = $newTheme;

        $_SESSION['flash_company'] = 'บันทึกการตั้งค่าบริษัทเรียบร้อยแล้ว';
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit;
    }
}

$flash = $_SESSION['flash_company'] ?? '';
unset($_SESSION['flash_company']);

$pageTitle = 'ตั้งค่าข้อมูลบริษัท';
include ROOT_PATH . '/public/includes/header.php';
include ROOT_PATH . '/public/includes/sidebar.php';
echo '<link rel="stylesheet" href="'. htmlspecialchars($baseURL) .'/assets/css/dashboard.css">';
?>

<style>
/* .css และ override ของ dark theme เหมือนเดิม */
:root[data-theme="dark"] {
    --bg-card: #1f2937;
    --border-color: #374151;
    --text-primary: #f9fafb;
    --text-secondary: #d1d5db;
    --bg-input: #374151;
}

/* 1) ปรับ .card ให้เป็นสีเข้ม และขอบอ่อน */
:root[data-theme="dark"] .card,
:root[data-theme="dark"] .settings-card {
    background-color: var(--bg-card) !important;
    border-color: var(--border-color) !important;
    color: var(--text-primary);
}

/* 2) ปรับ .card-header ของที่ใช้ .bg-light ให้เป็นสีเข้มด้วย */
:root[data-theme="dark"] .card-header.bg-light {
    background-color: var(--bg-card) !important;
    color: var(--text-primary) !important;
    border-bottom: 1px solid var(--border-color) !important;
}

/* 3) ปรับส่วน form-control, textarea, select ให้พื้นหลังเข้ม ขอบอ่อน และข้อความสีอ่อน */
:root[data-theme="dark"] .form-control,
:root[data-theme="dark"] .form-select,
:root[data-theme="dark"] textarea.form-control {
    background-color: var(--bg-input) !important;
    border: 2px solid var(--border-color) !important;
    color: var(--text-primary) !important;
}

/* 4) ปรับ .bg-light ในส่วนอื่นๆ (ถ้ามี) ให้เป็นโทนเข้ม */
:root[data-theme="dark"] .bg-light {
    background-color: var(--bg-card) !important;
    color: var(--text-primary) !important;
}

/* 5) ปรับ .alert.bg-light ให้เป็นโทนเข้ม */
:root[data-theme="dark"] .alert.bg-light {
    background-color: var(--bg-card) !important;
    color: var(--text-primary) !important;
    border: 1px solid var(--border-color) !important;
}

/* 6) ถ้ามี element ที่ใช้ .bg-white หรือ .bg-light ซ้อนกันในบางจุด ให้ override อีกที */
:root[data-theme="dark"] .bg-white {
    background-color: var(--bg-card) !important;
    color: var(--text-primary) !important;
}

/* 7) ปรับ dropdown, modal, table หรือ component อื่นๆ ถ้าจำเป็น
   ยกตัวอย่างการปรับ <table> ให้พื้นหลังเข้ม: */
:root[data-theme="dark"] table {
    background-color: var(--bg-card) !important;
    color: var(--text-primary) !important;
}

:root[data-theme="dark"] table th,
:root[data-theme="dark"] table td {
    border-color: var(--border-color) !important;
}

/* 8) หากใช้ .card-body หรือ .modal-body ที่มีพื้นหลังขาว ให้ปรับเป็นโทนเข้ม */
:root[data-theme="dark"] .card-body,
:root[data-theme="dark"] .modal-body {
    background-color: var(--bg-card) !important;
    color: var(--text-primary) !important;
}

/* 9) กรณีมีปุ่มที่ใช้คลาส .btn-light ให้เปลี่ยนสีข้อความและพื้นหลังให้เหมาะสม */
:root[data-theme="dark"] .btn-light {
    background-color: var(--border-color) !important;
    color: var(--text-primary) !important;
    border: 1px solid var(--border-color) !important;
}

/* 10) ปรับ scrollbar (ถ้าต้องการให้เข้ากับธีม) */
:root[data-theme="dark"] ::-webkit-scrollbar {
    width: 8px;
}

:root[data-theme="dark"] ::-webkit-scrollbar-track {
    background: var(--bg-card);
}

:root[data-theme="dark"] ::-webkit-scrollbar-thumb {
    background-color: var(--border-color);
    border-radius: 4px;
}

/* CSS สำหรับควบคุมขนาดรูปภาพ */
.current-image {
    max-width: 100%;
    max-height: 200px;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    object-fit: contain;
    background-color: #f8f9fa;
}

.logo-preview {
    max-width: 300px;
    max-height: 200px;
    background-color: rgba(248, 249, 250, 0.38);
    border: none;
}

.qr-preview {
    max-width: 200px;
    max-height: 200px;
}

.image-info {
    font-size: 0.8em;
    color: #6c757d;
    margin-top: 0.5rem;
}

.upload-area {
    border: 2px dashed #dee2e6;
    border-radius: 8px;
    padding: 1rem;
    text-align: center;
    transition: all 0.3s ease;
}

.upload-area:hover {
    border-color: #0d6efd;
    background-color: #f8f9fa;
}

.upload-area.dragover {
    border-color: #0d6efd;
    background-color: #e7f3ff;
}

.file-requirements {
    background-color: rgba(248, 249, 250, 0.38);
    border-left: 4px solid rgba(13, 109, 253, 0);
    padding: 0.75rem;
    margin-top: 0.5rem;
    border-radius: 10px;
}

.example-text {
    background-color: rgba(248, 249, 250, 0.38);
    padding: 0.5rem;
    border-radius: 4px;
    font-size: 0.9em;
}

.form-text {
    font-style: italic;
    color: rgb(151, 156, 165);
}
</style>

<main class="main-content">
    <!-- Header with toggles -->
    <header class="app-header d-flex align-items-center justify-content-between mb-3">
        <h2 class="header-title m-0">
            <i class="fa-solid fa-building-columns me-2"></i><?= htmlspecialchars($pageTitle) ?>
        </h2>
        <div class="header-actions d-flex align-items-center">
            <button id="sidebarToggle" class="btn-icon me-2" aria-label="Toggle sidebar">
                <i class="fa-solid fa-bars"></i>
            </button>
            <button id="themeToggle" class="btn-icon ms-2" aria-label="Toggle theme">
                <i id="themeIcon" class="fa-solid"></i>
            </button>
        </div>
    </header>
    <hr>

    <div class="container-fluid py-4">
        <?php if ($flash): ?>
        <div class="alert alert-success d-flex align-items-center">
            <i class="fa-solid fa-circle-check me-2"></i>
            <?= htmlspecialchars($flash) ?>
        </div>
        <?php endif; ?>

        <?php if ($errors): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $e): ?>
                <li><?= htmlspecialchars($e) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data" class="row g-4">
            <!-- ข้อมูลพื้นฐานบริษัท -->
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-light"><i class="fa-solid fa-building"></i>
                        <strong>ข้อมูลพื้นฐานบริษัท</strong>
                    </div>
                    <div class="card-body settings-card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="company_name" class="form-label">ชื่อบริษัท <span
                                        class="text-danger">*</span></label>
                                <input type="text" id="company_name" name="company_name" class="form-control" required
                                    placeholder="เช่น บริษัท ดิจิทัล โซลูชั่น จำกัด"
                                    value="<?= htmlspecialchars($settings['company_name']) ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="branch_name" class="form-label">ชื่อสาขา <span
                                        class="text-danger">*</span></label>
                                <input type="text" id="branch_name" name="branch_name" class="form-control" required
                                    placeholder="เช่น สาขาอรุณอมรินทร์"
                                    value="<?= htmlspecialchars($settings['branch_name']) ?>">
                            </div>
                            <div class="col-12">
                                <label for="company_address" class="form-label">
                                    ที่อยู่บริษัท (หลายบรรทัด) <span class="text-danger">*</span>
                                </label>
                                <textarea id="company_address" name="company_address" class="form-control" rows="8"
                                    required
                                    placeholder="กรอกที่อยู่สำนักงานของบริษัท..."><?= htmlspecialchars($settings['company_address']) ?></textarea>

                                <!-- เพิ่มตัวอย่างวิธีกรอกที่อยู่พร้อมการขึ้นบรรทัดใหม่ -->
                                <div class="form-text mt-2">
                                    <i class="fa-solid fa-lightbulb me-1"></i>
                                    ตัวอย่าง: ใช้ <code>&lt;br&gt;</code> คือการขึ้นบรรทัดใหม่
                                    <div class="example-text ms-3">
                                        61 ถนนอรุณอมรินทร์<br>
                                        แขวงอรุณอมรินทร์ เขตบางกอกน้อย<br>
                                        กรุงเทพมหานคร 10700
                                    </div>
                                </div>
                            </div>

                            <div class="col-12">
                                <label for="payment_methods" class="form-label">ช่องทางการชำระเงิน <span
                                        class="text-danger">*</span></label>
                                <textarea id="payment_methods" name="payment_methods" class="form-control" rows="8"
                                    required
                                    placeholder="กรอกข้อมูลการชำระเงิน เช่น ธนาคาร เลขบัญชี หรือช่องทางอื่นๆ..."><?= htmlspecialchars($settings['payment_methods']) ?></textarea>
                                <small class="form-text text-muted mt-1">
                                    <i class="fa-solid fa-lightbulb me-1"></i>
                                    ตัวอย่าง:
                                    <div class="example-text ms-3">
                                        🏦 ธนาคารทหารไทย สาขาอรุณอมรินทร์<br>
                                        👤 ชื่อบัญชี: ธนวรรณณ์ ฯ<br>
                                        💳 เลขที่บัญชี: 922-9-6337-x<br>
                                        📞 โทรศัพท์: 080-559-343xx<br>
                                        💬 Line: @nanopay (ไลน์ไอดีของร้านค้า)
                                    </div>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- โลโก้บริษัท -->
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-light">
                        <strong>โลโก้บริษัท</strong>
                    </div>
                    <div class="card-body settings-card-body">
                        <div class="row align-items-center mb-3">
                            <!-- พรีวิวโลโก้และ upload-area รูป -->
                            <div class="col-md-4 text-center">
                                <div class="upload-area">
                                    <?php if (!empty($settings['logo_path'])): ?>
                                    <img src="<?= htmlspecialchars($baseURL . '/' . $settings['logo_path']) ?>"
                                        alt="Company Logo" class="current-image logo-preview mb-2">
                                    <?php 
                                            $logoFile = ROOT_PATH . '/public/' . $settings['logo_path'];
                                            if (file_exists($logoFile)) {
                                                $logoInfo = getimagesize($logoFile);
                                                $logoSize = filesize($logoFile);
                                                echo '<div class="image-info">';
                                                echo 'ขนาด: ' . $logoInfo[0] . 'x' . $logoInfo[1] . ' พิกเซล<br>';
                                                echo 'ขนาดไฟล์: ' . number_format($logoSize / 1024, 1) . ' KB';
                                                echo '</div>';
                                            }
                                        ?>
                                    <?php else: ?>
                                    <i class="fa-solid fa-image text-muted" style="font-size: 3rem;"></i>
                                    <p class="text-muted mb-1 mt-2">ยังไม่มีโลโก้บริษัท</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-md-8">
                                <!-- ลบโลโก้เดิมออก -->
                                <?php if (!empty($settings['logo_path'])): ?>
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" name="clear_logo" id="clear_logo"
                                        value="1">
                                    <label class="form-check-label fw-semibold" for="clear_logo">
                                        <i class="fa-solid fa-trash-can me-1"></i>ลบโลโก้เดิมออก
                                    </label>
                                </div>
                                <small class="form-text mb-3">
                                    <i class="fa-solid fa-exclamation-triangle me-1"></i>
                                    ถ้าลบโลโก้ เอกสารต่างๆ จะไม่มีโลโก้บริษัท
                                </small>
                                <?php endif; ?>

                                <!-- ปุ่มอัปโหลดโลโก้ใหม่ -->
                                <label for="logo" class="form-label mb-1">
                                    <i class="fa-solid fa-cloud-arrow-up me-1"></i>
                                    <?= !empty($settings['logo_path']) ? 'อัปโหลดโลโก้ใหม่ (แทนที่เดิม)' : 'อัปโหลดโลโก้บริษัท' ?>
                                </label>
                                <input type="file" id="logo" name="logo" accept="image/*"
                                    class="form-control form-control-lg mb-2">
                                <div class="file-requirements small text-muted">
                                    <strong>ข้อกำหนดโลโก้:</strong><br>
                                    • ประเภทไฟล์: JPG, PNG, GIF, WebP<br>
                                    • ขนาดไฟล์: ไม่เกิน 5MB<br>
                                    • ขนาดภาพ: สูงสุด 800x600 พิกเซล<br>
                                    • ระบบจะปรับขนาดเป็น 400x300 พิกเซล อัตโนมัติ
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- LINE และช่องทางการติดต่อ -->
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-light">
                        <strong>LINE และช่องทางการติดต่อ</strong>
                    </div>
                    <div class="card-body settings-card-body">
                        <div class="row g-3">
                            <!-- คอลัมน์ซ้าย: พรีวิว QR Code -->
                            <div class="col-md-4 text-center">
                                <div class="upload-area">
                                    <?php if (!empty($settings['line_qr_path'])): ?>
                                    <img src="<?= htmlspecialchars($baseURL . '/' . $settings['line_qr_path']) ?>"
                                        alt="LINE QR Code" class="current-image qr-preview mb-2">
                                    <?php 
                                            $qrFile = ROOT_PATH . '/public/' . $settings['line_qr_path'];
                                            if (file_exists($qrFile)) {
                                                $qrInfo = getimagesize($qrFile);
                                                $qrSize = filesize($qrFile);
                                                echo '<div class="image-info">';
                                                echo 'ขนาด: ' . $qrInfo[0] . 'x' . $qrInfo[1] . ' พิกเซล<br>';
                                                echo 'ขนาดไฟล์: ' . number_format($qrSize / 1024, 1) . ' KB';
                                                echo '</div>';
                                            }
                                        ?>
                                    <?php else: ?>
                                    <i class="fa-solid fa-qrcode text-muted" style="font-size: 3rem;"></i>
                                    <p class="text-muted mb-1 mt-2">ยังไม่มี QR Code LINE</p>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- คอลัมน์ขวา: ลบ QR Code และอัปโหลดใหม่ -->
                            <div class="col-md-8">
                                <?php if (!empty($settings['line_qr_path'])): ?>
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" name="clear_line_qr"
                                        id="clear_line_qr" value="1">
                                    <label class="form-check-label fw-semibold" for="clear_line_qr">
                                        <i class="fa-solid fa-trash-can me-1"></i>ลบ QR Code เดิมออก
                                    </label>
                                </div>
                                <small class="form-text mb-4">
                                    ถ้าลบ QR Code ลูกค้าจะไม่เห็น QR Code ในเอกสาร
                                </small>
                                <?php endif; ?>

                                <label for="line_qr" class="form-label mb-1">
                                    <i class="fa-solid fa-cloud-arrow-up me-1"></i>
                                    <?= !empty($settings['line_qr_path']) ? 'อัปโหลด QR Code ใหม่ (แทนที่เดิม)' : 'อัปโหลด QR Code LINE' ?>
                                </label>
                                <input type="file" id="line_qr" name="line_qr" accept="image/*"
                                    class="form-control form-control-lg mb-2">
                                <div class="file-requirements small text-muted">
                                    <strong>ข้อกำหนด QR Code:</strong><br>
                                    • ประเภทไฟล์: JPG, PNG, GIF, WebP<br>
                                    • ขนาดไฟล์: ไม่เกิน 5MB<br>
                                    • ขนาดภาพ: สูงสุด 500x500 พิกเซล<br>
                                    • ระบบจะปรับขนาดเป็น 300x300 พิกเซล อัตโนมัติ
                                </div>
                                <small class="form-text text-muted mt-3">
                                    <strong>วิธีสร้าง QR Code LINE:</strong>
                                    <div class="example-text ms-3">
                                        1. เปิดแอป LINE → ไปที่โปรไฟล์<br>
                                        2. แตะ "QR Code" → "QR Code ของฉัน"<br>
                                        3. แตะ "บันทึก" เพื่อดาวน์โหลดภาพ<br>
                                        4. นำภาพที่ได้มาอัปโหลดที่นี่
                                    </div>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- เลือกธีม (light/dark)
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-light">
                        <strong>ตั้งค่าธีม</strong>
                    </div>
                    <div class="card-body settings-card-body">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="themeSwitch" name="theme" value="dark"
                                <?= $currentTheme === 'dark' ? 'checked' : '' ?>>
                            <label class="form-check-label" for="themeSwitch">
                                เปิดโหมด Dark Theme
                            </label>
                        </div>
                    </div>
                </div>
            </div> -->

            <!-- ปุ่มบันทึก -->
            <div class="col-12 text-end">
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-floppy-disk me-1"></i>บันทึกการตั้งค่า
                </button>
            </div>
        </form>
    </div>
</main>

<!-- Toast แสดงผลเมื่อบันทึกสำเร็จ -->
<?php if ($flash): ?>
<div class="position-fixed top-0 end-0 p-3" style="z-index:2000">
    <div id="toastCompany" class="toast align-items-center text-white bg-success border-0" role="alert"
        aria-live="assertive" aria-atomic="true" data-bs-delay="3000">
        <div class="d-flex">
            <div class="toast-body">
                <i class="fa-solid fa-circle-check me-2"></i><?= htmlspecialchars($flash) ?>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"
                aria-label="ปิด"></button>
        </div>
    </div>
</div>
<?php endif; ?>

<?php include ROOT_PATH . '/public/includes/footer.php'; ?>

<script>
// Sidebar Toggle
document.getElementById('sidebarToggle').onclick = () => {
    document.body.classList.toggle('collapsed');
};

// Theme Toggle (ใช้ data-theme ที่ header.php ตั้งไว้เป็นค่าตั้งต้น)
(function() {
    const btn = document.getElementById('themeToggle'),
        ico = document.getElementById('themeIcon'),
        root = document.documentElement,
        themeSwitch = document.getElementById('themeSwitch');

    function updateIcon(theme) {
        ico.className = (theme === 'dark') ? 'fa-solid fa-sun' : 'fa-solid fa-moon';
    }

    // อ่านค่า data-theme ปัจจุบันจาก <html> (header.php เซ็ตไว้แล้ว)
    let currentTheme = root.getAttribute('data-theme') || 'light';
    updateIcon(currentTheme);

    // เมื่อคลิกปุ่มที่มุม จะสลับ ทั้ง data-theme และ switch
    btn.onclick = () => {
        currentTheme = (currentTheme === 'dark') ? 'light' : 'dark';
        root.setAttribute('data-theme', currentTheme);
        updateIcon(currentTheme);
        themeSwitch.checked = (currentTheme === 'dark');
    };

    // เมื่อเปลี่ยน switch ในฟอร์ม จะสลับ data-theme ให้ตรง
    themeSwitch.onchange = () => {
        currentTheme = themeSwitch.checked ? 'dark' : 'light';
        root.setAttribute('data-theme', currentTheme);
        updateIcon(currentTheme);
    };
})();

// Toast Notification
document.addEventListener('DOMContentLoaded', () => {
    const toastEl = document.getElementById('toastCompany');
    if (toastEl) {
        new bootstrap.Toast(toastEl).show();
    }
});

// Drag and Drop สำหรับไฟล์อัปโหลด
document.addEventListener('DOMContentLoaded', function() {
    const uploadAreas = document.querySelectorAll('.upload-area');
    const fileInputs = document.querySelectorAll('input[type="file"]');

    // เพิ่ม drag and drop functionality
    uploadAreas.forEach(area => {
        area.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.classList.add('dragover');
        });

        area.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');
        });

        area.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');

            const files = e.dataTransfer.files;
            if (files.length > 0) {
                // หา input file ที่เกี่ยวข้อง
                const fileInput = this.closest('.card-body').querySelector(
                    'input[type="file"]');
                if (fileInput) {
                    fileInput.files = files;
                    // แสดงชื่อไฟล์
                    const fileName = files[0].name;
                    this.innerHTML = `<i class="fa-solid fa-file-image text-success" style="font-size: 3rem;"></i>
                                       <p class="text-success mb-0 mt-2">เลือกไฟล์: ${fileName}</p>
                                       <small class="text-muted">คลิกหรือลากไฟล์ใหม่เพื่อเปลี่ยน</small>`;
                }
            }
        });
    });

    // แสดงชื่อไฟล์เมื่อเลือกผ่าน input
    fileInputs.forEach(input => {
        input.addEventListener('change', function() {
            if (this.files.length > 0) {
                const fileName = this.files[0].name;
                const fileSize = (this.files[0].size / 1024 / 1024).toFixed(2); // MB
                const uploadArea = this.closest('.card-body').querySelector('.upload-area');
                if (uploadArea) {
                    uploadArea.innerHTML = `<i class="fa-solid fa-file-image text-success" style="font-size: 3rem;"></i>
                                           <p class="text-success mb-0 mt-2">เลือกไฟล์: ${fileName}</p>
                                           <small class="text-muted">ขนาด: ${fileSize} MB</small>`;
                }
            }
        });
    });
});
</script>