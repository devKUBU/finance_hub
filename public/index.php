<?php
session_start();
require_once __DIR__ . '/../config/constants.php';

$loggedIn = isset($_SESSION['user']);
$role = $loggedIn ? $_SESSION['user']['role'] : null;

if ($loggedIn && in_array($role, ['admin', 'shop', 'super_admin'])) {
    header("Location: " . BASE_URL . "/pages/{$role}/dashboard.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="utf-8">
    <title>Nano Friend - ระบบจัดการเงินกู้</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/bootstrap/bootstrap.min.css">
</head>

<body class="bg-light">
    <div class="container mt-5">
        <div class="text-center">
            <h1 class="mb-4">💼 Nano Friend</h1>
            <p class="lead">ระบบจัดการสัญญาผ่อนชำระ สำหรับร้านค้าและผู้ดูแล</p>
            <a href="<?= BASE_URL ?>/login.php" class="btn btn-primary btn-lg mt-3">เข้าสู่ระบบ</a>
        </div>
    </div>
</body>

</html>