<?php
// File: public/pages/shop/contract/api/contract-status.php

require_once realpath(__DIR__ . '/../../../../config/bootstrap.php');
require_once ROOT_PATH . '/config/db.php';

if (session_status() === PHP_SESSION_NONE) session_start();
requireRole(['shop']);

$shopId = (int)$_SESSION['user']['id'];
$id     = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("
    SELECT approval_status
      FROM contracts
     WHERE id = ?
       AND shop_id = ?
");
$stmt->execute([$id, $shopId]);
$status = $stmt->fetchColumn() ?: 'pending';

header('Content-Type: application/json; charset=utf-8');
echo json_encode(['approval_status' => $status]);