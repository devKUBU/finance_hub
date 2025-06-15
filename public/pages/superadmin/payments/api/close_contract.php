<?php
// File: public/pages/superadmin/payments/api/close_contract.php

require_once realpath(__DIR__ . '/../../../../../config/bootstrap.php');
require_once ROOT_PATH . '/includes/helpers.php';
if (session_status() === PHP_SESSION_NONE) session_start();
requireRole(['superadmin', 'admin']);


// คืนค่า JSON
header('Content-Type: application/json; charset=utf-8');

// รับ contract_id จาก AJAX
$cid = isset($_POST['contract_id']) ? (int) $_POST['contract_id'] : 0;
if ($cid <= 0) {
    echo json_encode(['success' => false, 'error' => 'รหัสสัญญาไม่ถูกต้อง']);
    exit;
}

try {
    // เชื่อมต่อ DB
    require_once ROOT_PATH . '/config/db.php';
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // อัปเดตสถานะงวดผ่อนทั้งหมดของสัญญานี้ให้เป็น "closed"
    // (เว้นงวดที่จ่ายแล้วไว้ไม่เปลี่ยน)
    $stmt = $pdo->prepare("
        UPDATE payments
           SET status = 'closed'
         WHERE contract_id = :cid
           AND status != 'paid'
    ");
    $stmt->execute([':cid' => $cid]);
    $count = $stmt->rowCount();

    echo json_encode([
        'success' => true,
        'closed_count' => $count
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error'   => 'DB Error: ' . $e->getMessage()
    ]);
}