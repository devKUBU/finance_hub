<?php
session_start();

// ดึง BASE_URL ให้รู้ว่าอยู่ใน /nano-friend
$scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
$projectBase = str_replace('/index.php', '', $scriptName);
define('BASE_URL', $projectBase);

// ตรวจสอบว่าล็อกอินหรือยัง
if (isset($_SESSION['user'])) {
    $role = $_SESSION['user']['role'] ?? 'shop';
    header("Location: " . BASE_URL . "/public/pages/{$role}/dashboard.php");
    exit;
}

// ยังไม่ล็อกอิน → ไปหน้า login
header("Location: " . BASE_URL . "/public/login.php");
exit;