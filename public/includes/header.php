<?php
// public/includes/header.php

// ตรวจสอบและโหลดไฟล์ bootstrap.php ซึ่งเป็นไฟล์กำหนดค่าเริ่มต้นของระบบ
$bootstrapFile = realpath(__DIR__ . '/../../config/bootstrap.php');
require_once $bootstrapFile;

// ตรวจสอบสถานะเซสชัน ถ้ายังไม่มีให้เริ่มเซสชันใหม่
if (session_status() === PHP_SESSION_NONE) session_start();

// ดึงค่าธีมของผู้ใช้จากเซสชัน หรือใช้ 'dark' เป็นค่าเริ่มต้น
$userTheme = $_SESSION['user']['theme'] ?? 'dark';
?>
<!DOCTYPE html>
<html lang="th" data-theme="<?= htmlspecialchars($userTheme) ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? '') ?></title>
    <link rel="icon" href="<?= $baseURL ?>/assets/images/favicon.ico">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">


    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet" />

    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">


    <?php if (!empty($hideNav)): ?>
    <link href="<?= $baseURL ?>/assets/css/login.css" rel="stylesheet">
    <?php else: ?>
    <link href="<?= $baseURL ?>/assets/css/main.css" rel="stylesheet">
    <link href="<?= $baseURL ?>/assets/css/footer.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= $baseURL ?>/assets/css/sidebar.css">
    <?php if (!empty($pageStyles) && is_array($pageStyles)): ?>
    <?php foreach ($pageStyles as $cssPath): ?>
    <link href="<?= $baseURL . $cssPath ?>" rel="stylesheet">
    <?php endforeach; ?>
    <?php endif; ?>
    <?php endif; ?>
</head>

<body class="<?= !empty($hideNav) ? 'login-body' : '' ?>">

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"
        integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

    <script>
    $(function() {
        // ตรวจสอบว่ามี element ID 'activityLogTable' อยู่บนหน้าก่อนที่จะทำการ initialize DataTables
        if ($('#activityLogTable').length) {
            $('#activityLogTable').DataTable({
                paging: false,
                info: false,
                ordering: false, // ปิด client-side sort
                searching: false
            });
        }
    });
    </script>