<?php
// File: public/pages/superadmin/payments/partials/add_expense.php (with Logging)
date_default_timezone_set('Asia/Bangkok');

// (1) โหลด bootstrap & helpers
require_once realpath(__DIR__ . '/../../../../../config/bootstrap.php');
require_once ROOT_PATH . '/includes/helpers.php';
if (session_status() === PHP_SESSION_NONE) session_start();
requireRole(['superadmin', 'admin']);

// (2) เชื่อม DB
require_once ROOT_PATH . '/config/db.php';
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// (3) ตั้ง header JSON
header('Content-Type: application/json; charset=utf-8');
$response = ['success' => false, 'error' => ''];

// (4) รับค่า POST
$contractId  = isset($_POST['contract_id'])  ? (int)$_POST['contract_id']      : 0;
$expenseId   = isset($_POST['expense_id'])   ? (int)$_POST['expense_id']       : 0;
$expenseType = isset($_POST['expense_type']) ? trim($_POST['expense_type'])     : '';
$amount      = isset($_POST['amount'])       ? trim($_POST['amount'])           : '';
$note        = isset($_POST['note'])         ? trim($_POST['note'])             : '';

// (5) ตรวจสอบเบื้องต้น
if ($contractId <= 0) {
    $response['error'] = 'รหัสสัญญาไม่ถูกต้อง';
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}
if ($amount === '' || !is_numeric($amount) || (float)$amount <= 0) {
    $response['error'] = 'จำนวนเงินต้องเป็นตัวเลขมากกว่า 0';
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}
$allowedTypes = ['lock_program','disbursement','other'];
if (!in_array($expenseType, $allowedTypes, true)) {
    $response['error'] = 'ประเภทค่าใช้จ่ายไม่ถูกต้อง';
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

// ใช้ Transaction เพื่อความปลอดภัย
$pdo->beginTransaction();

try {
    $cleanAmount = number_format((float)$amount, 2, '.', '');
    $logDesc = "สัญญา #{$contractId} ประเภท: {$expenseType}, จำนวน: {$cleanAmount} บาท";

    if ($expenseId > 0) {
        // ------------------- โหมดอัปเดต -------------------
        $stmt = $pdo->prepare("UPDATE expenses SET expense_type = :type, amount = :amt, note = :note WHERE id = :eid");
        $stmt->execute([':type' => $expenseType, ':amt'  => $cleanAmount, ':note' => $note, ':eid'  => $expenseId]);

        // บันทึก Log การอัปเดต
        logActivity($pdo, $_SESSION['user']['id'], 'edit_expense', 'expense', $expenseId, "แก้ไขค่าใช้จ่าย {$logDesc}");
        
    } else {
        // ------------------- โหมดเพิ่ม -------------------
        $stmt = $pdo->prepare("INSERT INTO expenses (contract_id, expense_type, amount, note, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute([$contractId, $expenseType, $cleanAmount, $note]);
        $newExpenseId = $pdo->lastInsertId(); // ดึง ID ของรายการที่เพิ่งสร้าง

        // บันทึก Log การเพิ่ม
        logActivity($pdo, $_SESSION['user']['id'], 'create_expense', 'expense', $newExpenseId, "เพิ่มค่าใช้จ่าย {$logDesc}");
    }

    $pdo->commit();
    $response['success'] = true;
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;

} catch (PDOException $e) {
    $pdo->rollBack();
    $response['error'] = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}