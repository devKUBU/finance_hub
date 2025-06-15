<?php
// File: public/pages/shop/contract/delete-evidence.php

// 1) Load bootstrap & helpers
require_once realpath(__DIR__ . '/../../../../config/bootstrap.php');
require_once ROOT_PATH    . '/includes/helpers.php';

// 2) Session & role check
if (session_status() === PHP_SESSION_NONE) session_start();
requireRole(['shop']);

// 3) DB
require_once ROOT_PATH . '/config/db.php';
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// 4) รับ POST
$evidenceId = (int) ($_POST['id'] ?? 0);
$contractId = (int) ($_POST['contract_id'] ?? 0);
$shopId     = (int) $_SESSION['user']['id'];

// 5) เช็คสถานะสัญญาอีกที (block ถ้า approved)
$stmt = $pdo->prepare("
  SELECT approval_status
    FROM contracts
   WHERE id = ? AND shop_id = ?
");
$stmt->execute([$contractId, $shopId]);
$status = $stmt->fetchColumn();
if ($status === 'approved') {
    setFlash('danger', 'สัญญานี้อนุมัติแล้ว ไม่สามารถลบหลักฐานได้');
    header('Location: list-contracts.php');
    exit;
}

// 6) ดึงข้อมูลไฟล์ก่อนลบ (เอา path มาลบไฟล์บนดิสก์ด้วย)
$stmt2 = $pdo->prepare("
  SELECT file_path
    FROM contract_evidence
   WHERE id = ? AND contract_id = ?
");
$stmt2->execute([$evidenceId, $contractId]);
$file = $stmt2->fetchColumn();
if ($file) {
    $fullPath = ROOT_PATH . '/public/' . ltrim($file, '/');
    if (file_exists($fullPath)) {
        @unlink($fullPath);
    }
}

// 7) ลบเรคอร์ด
$del = $pdo->prepare("
  DELETE FROM contract_evidence
   WHERE id = ? AND contract_id = ?
");
$del->execute([$evidenceId, $contractId]);

setFlash('success', 'ลบไฟล์หลักฐานเรียบร้อยแล้ว');
header('Location: list-contracts.php');
exit;