<?php
// File: C:\xampp\htdocs\nano-friend\public\pages\superadmin\apple_ids\check_apple_usage.php

// (1) Bootstrap + DB
require_once realpath(__DIR__ . '/../../../../config/bootstrap.php');
require_once ROOT_PATH . '/config/db.php';
header('Content-Type: application/json; charset=utf-8');

// รับ ID มา
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    echo json_encode(['error'=>'Invalid ID'], JSON_UNESCAPED_UNICODE);
    exit;
}

// ดึงค่า apple_id (string) จากตาราง
$stmt0 = $pdo->prepare("SELECT apple_id FROM apple_ids WHERE id = ?");
$stmt0->execute([$id]);
$appleId = $stmt0->fetchColumn();
if (!$appleId) {
    echo json_encode(['error'=>'Not found'], JSON_UNESCAPED_UNICODE);
    exit;
}

// ดึงสัญญาที่ใช้งาน Apple ID นี้ (ยังไม่หมดอายุ)
$stmt = $pdo->prepare("
    SELECT contract_no_shop
      FROM contracts
     WHERE icloud_email = ?
       AND end_date >= CURDATE()
");
$stmt->execute([$appleId]);
$contracts = $stmt->fetchAll(PDO::FETCH_COLUMN);

// ตอบ JSON
echo json_encode([
    'used'      => !empty($contracts),
    'contracts' => array_map(fn($no)=>['contract_no'=>$no], $contracts)
], JSON_UNESCAPED_UNICODE);