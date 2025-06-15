<?php
header('Content-Type: application/json; charset=UTF-8');
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/../../logs/php-error.log');

require_once realpath(__DIR__ . '/../../config/bootstrap.php');
require_once ROOT_PATH . '/includes/helpers.php';
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// ตรวจสอบ session
if (empty($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$userId = (int) $_SESSION['user']['id'];
$collapsed = isset($_POST['collapsed']) ? (int) $_POST['collapsed'] : 0;

// แค่ require ไฟล์ db.php เพื่อให้เกิดตัวแปร $pdo 
require_once ROOT_PATH . '/config/db.php';  // assume db.php กำหนด $pdo ไว้แล้ว
// ไม่ต้อง assign $pdo = require … เพราะ config/db.php ไม่ return ค่า

try {
    // $pdo ควรเป็น PDO object ที่ถูกสร้างใน config/db.php
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->prepare("UPDATE users SET sidebar_collapsed = ? WHERE id = ?");
    $stmt->execute([$collapsed, $userId]);

    echo json_encode(['success' => true, 'collapsed' => $collapsed]);
    exit;
} catch (Exception $e) {
    http_response_code(500);
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error']);
    exit;
}