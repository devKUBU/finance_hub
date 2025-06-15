<?php
// public/authenticate.php

require_once realpath(__DIR__ . '/../config/bootstrap.php');
require_once realpath(__DIR__ . '/../includes/helpers.php');
require_once realpath(__DIR__ . '/../config/db.php');

session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

$login    = trim($_POST['login']    ?? '');
$password = trim($_POST['password'] ?? '');

if ($login === '' || $password === '') {
    $_SESSION['error'] = 'กรุณากรอกชื่อผู้ใช้/อีเมล และรหัสผ่านให้ครบ';
    header('Location: login.php');
    exit;
}

$stmt = $pdo->prepare("
    SELECT id, username, name, email, password, role, theme, permissions
    FROM users
    WHERE username = ? OR email = ?
    LIMIT 1
");
$stmt->execute([$login, $login]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || !password_verify($password, $user['password'])) {
    $_SESSION['error'] = 'ชื่อผู้ใช้/อีเมล หรือรหัสผ่านไม่ถูกต้อง';
    header('Location: login.php');
    exit;
}

// สร้าง session
unset($user['password']);
$_SESSION['user'] = $user;
$_SESSION['user']['theme'] = $user['theme'] ?? 'dark';

// Remember me
if (!empty($_POST['remember_me'])) {
    setcookie('remember_me', session_id(), time() + 60 * 60 * 24 * 7, '/');
}

// Log กิจกรรม
logActivity($pdo, $user['id'], 'login', 'system', null, 'ล็อกอินสำเร็จ');

// ✅ Redirect ตาม role
switch (strtolower($user['role'])) {
    case 'superadmin':
    case 'super_admin':
    case 'admin':
        // ✅ ทั้ง superadmin และ admin ไปหน้าเดียวกัน
        header("Location: {$baseURL}/pages/superadmin/dashboard.php");
        break;

    case 'shop':
        header("Location: {$baseURL}/pages/shop/dashboard.php");
        break;

    default:
        $_SESSION['error'] = 'บทบาทผู้ใช้ไม่ถูกต้อง';
        header("Location: login.php");
        break;
}
exit;