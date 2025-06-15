<?php
session_start();
session_destroy();

// ให้ BASE_URL เป็น root ตรงนี้ (กรณี logout อยู่ root)
$basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
$redirectTo = $basePath . '/login.php';

header("Location: $redirectTo");
exit;