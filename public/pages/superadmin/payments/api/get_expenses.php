<?php
// ไฟล์: public/pages/superadmin/payments/api/get_expenses.php
date_default_timezone_set('Asia/Bangkok');

// (1) โหลด bootstrap & helpers, ตรวจสอบ session และสิทธิ์
require_once realpath(__DIR__ . '/../../../../../config/bootstrap.php');
require_once ROOT_PATH . '/includes/helpers.php';
if (session_status() === PHP_SESSION_NONE) session_start();
requireRole(['superadmin', 'admin']);


// (2) เชื่อมฐานข้อมูล
require_once ROOT_PATH . '/config/db.php';
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// (3) ตั้ง header เป็น JSON และรับ contract_id
header('Content-Type: application/json; charset=utf-8');
$contractId = isset($_GET['contract_id']) ? (int)$_GET['contract_id'] : 0;
if ($contractId <= 0) {
    echo json_encode(['error' => 'รหัสสัญญาไม่ถูกต้อง'], JSON_UNESCAPED_UNICODE);
    exit;
}

// (4) คิวรีเดียวดึง commission_amount และ loan_amount จากตาราง contracts
$stmtC = $pdo->prepare("
  SELECT 
    COALESCE(commission_amount,0) AS commission,
    COALESCE(loan_amount,0)     AS loan_amount
  FROM contracts
  WHERE id = ?
  LIMIT 1
");
$stmtC->execute([$contractId]);
$contractRow = $stmtC->fetch(PDO::FETCH_ASSOC);
$commission   = (float)$contractRow['commission'];
$loanAmount   = (float)$contractRow['loan_amount'];

// (5) ดึงยอด “ค่าโปรแกรมล็อค” จากตาราง expenses
$stmtL = $pdo->prepare("
  SELECT COALESCE(SUM(amount),0) AS lock_program
    FROM expenses
   WHERE contract_id = ?
     AND expense_type = 'lock_program'
");
$stmtL->execute([$contractId]);
$lock_program = (float)$stmtL->fetchColumn();

// (6) ดึงยอด “ต้นทุนปล่อยสินเชื่อ (expense)” จาก expenses
$stmtD = $pdo->prepare("
  SELECT COALESCE(SUM(amount),0) AS disbursement
    FROM expenses
   WHERE contract_id = ?
     AND expense_type = 'disbursement'
");
$stmtD->execute([$contractId]);
$disbursement = (float)$stmtD->fetchColumn();

// (7) ดึงยอด “อื่นๆ” จาก expenses
$stmtO = $pdo->prepare("
  SELECT COALESCE(SUM(amount),0) AS other
    FROM expenses
   WHERE contract_id = ?
     AND expense_type = 'other'
");
$stmtO->execute([$contractId]);
$other = (float)$stmtO->fetchColumn();

// (8) คำนวณยอดรวมทั้งหมด
$total = $commission
       + $lock_program
       + $loanAmount    // ค่านี้มาจาก contracts.loan_amount
       + $disbursement
       + $other;

// (9) ดึงรายการรายละเอียดในตาราง “รายการค่าใช้จ่าย”
$stmtList = $pdo->prepare("
  SELECT id, expense_type, amount, note, created_at
    FROM expenses
   WHERE contract_id = ?
   ORDER BY created_at DESC
");
$stmtList->execute([$contractId]);
$rows = $stmtList->fetchAll(PDO::FETCH_ASSOC);

// (10) ส่งข้อมูลกลับเป็น JSON
echo json_encode([
  'commission'    => number_format($commission,  2, '.', ''),   // ยอดค่าคอมมิชชั่น
  'loan_amount'   => number_format($loanAmount,  2, '.', ''),   // ยอดต้นทุนปล่อยสินเชื่อ
  'lock_program'  => number_format($lock_program,2, '.', ''),   // ยอดค่าโปรแกรมล็อค
  'disbursement'  => number_format($disbursement,2, '.', ''),   // ยอดต้นทุนปล่อยสินเชื่อ (expense)
  'other'         => number_format($other,       2, '.', ''),   // ยอดอื่นๆ
  'total'         => number_format($total,       2, '.', ''),   // ยอดรวมทั้งหมด
  'expenses_list' => $rows                              // รายการแถวค่าใช้จ่าย
], JSON_UNESCAPED_UNICODE);
exit;