<?php
// File: public/pages/superadmin/payments/api/save_payment.php (Final Version with Enhanced Logging)

ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

// 1) โหลดระบบ Bootstrap, Helpers, DB
require_once realpath(__DIR__ . '/../../../../../config/bootstrap.php');
require_once realpath(__DIR__ . '/../../../../../includes/helpers.php');
require_once realpath(__DIR__ . '/../../../../../config/db.php');

if (session_status() === PHP_SESSION_NONE) session_start();
requireRole(['superadmin', 'admin']);

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->beginTransaction();

try {
    // --- ฟังก์ชันสำหรับทำความสะอาดตัวเลข ---
    function clean_float($str) {
        return (float)str_replace(',', '', $str);
    }

    // 2) อ่านค่าจาก POST และทำความสะอาดข้อมูล
    $mode           = trim($_POST['mode']           ?? 'new');
    $paymentId      = (int)($_POST['payment_id']     ?? 0);
    $amountPaid     = clean_float($_POST['amount_paid']    ?? 0);
    $penalty        = clean_float($_POST['penalty_amount'] ?? 0);
    $feeUnlock      = clean_float($_POST['fee_unlock']     ?? 0);
    $feeDocument    = clean_float($_POST['fee_document']   ?? 0);
    $feeOther       = clean_float($_POST['fee_other']      ?? 0);
    $note           = trim($_POST['note']           ?? '');
    $closeBalance   = (int)($_POST['close_balance']  ?? 0);

    // 3) ตรวจสอบข้อมูล
    if ($paymentId <= 0) {
        throw new Exception('ข้อมูลไม่ครบ: ไม่พบรหัสการชำระเงิน');
    }
    if (!$closeBalance && $amountPaid <= 0) {
        throw new Exception('กรุณาระบุจำนวนเงินที่จ่ายจริง');
    }

    // 4) หาก closeBalance ให้ปรับจำนวนจ่ายเป็นยอดที่เหลือทั้งหมด
    if ($closeBalance) {
        $stmtRem = $pdo->prepare("SELECT amount_due - IFNULL(amount_paid,0) FROM payments WHERE id = ?");
        $stmtRem->execute([$paymentId]);
        $amountPaid = (float)$stmtRem->fetchColumn();
    }
    $status = ($amountPaid > 0) ? 'paid' : 'pending';
    if ($closeBalance) { $status = 'closed'; }

    // 5) อัปเดตตาราง payments
    if ($mode === 'edit') {
        $stmt = $pdo->prepare("UPDATE payments SET amount_paid = :amount_paid, penalty_amount = :penalty_amount, fee_unlock = :fee_unlock, fee_document = :fee_document, fee_other = :fee_other, note = :note, status = :status, paid_at = NOW() WHERE id = :id");
    } else {
        $stmt = $pdo->prepare("UPDATE payments SET amount_paid = COALESCE(amount_paid,0) + :amount_paid, penalty_amount = COALESCE(penalty_amount,0) + :penalty_amount, fee_unlock = COALESCE(fee_unlock,0) + :fee_unlock, fee_document = COALESCE(fee_document,0) + :fee_document, fee_other = COALESCE(fee_other,0) + :fee_other, note = :note, status = :status, paid_at = NOW() WHERE id = :id");
    }
    $stmt->execute([
        ':amount_paid'    => $amountPaid,
        ':penalty_amount' => $penalty,
        ':fee_unlock'     => $feeUnlock,
        ':fee_document'   => $feeDocument,
        ':fee_other'      => $feeOther,
        ':note'           => $note,
        ':status'         => $status,
        ':id'             => $paymentId
    ]);

    // 6) อัปโหลดสลิป (ถ้ามี)
    if (!empty($_FILES['slip_file']['name'][0])) {
        $uploadDir = ROOT_PATH . "/public/uploads/slips/{$paymentId}";
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        $saved = [];
        foreach ($_FILES['slip_file']['tmp_name'] as $i => $tmp) {
            if ($_FILES['slip_file']['error'][$i] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['slip_file']['name'][$i], PATHINFO_EXTENSION));
                $fn  = uniqid('slip_', true) . ".$ext";
                if (move_uploaded_file($tmp, "$uploadDir/$fn")) {
                    $saved[] = "/uploads/slips/{$paymentId}/{$fn}";
                }
            }
        }
        if ($saved) {
            $jsonPaths = json_encode($saved, JSON_UNESCAPED_SLASHES);
            $upd = $pdo->prepare("UPDATE payments SET slip_paths = ? WHERE id = ?");
            $upd->execute([$jsonPaths, $paymentId]);
        }
    }

    // --- 7) รวบรวมข้อมูลสรุปสำหรับ JS และ Log (Optimized Version) ---
    $summarySql = "
        SELECT
            p.contract_id, p.pay_no,
            c.contract_no_shop, 
            CONCAT(c.customer_firstname, ' ', c.customer_lastname) as customer_name,
            (SELECT COUNT(*) FROM payments WHERE contract_id = p.contract_id) AS total_installments,
            (SELECT SUM(amount_paid) FROM payments WHERE contract_id = p.contract_id) AS paid,
            (SELECT SUM(amount_due - IFNULL(amount_paid, 0)) FROM payments WHERE contract_id = p.contract_id) AS remaining
        FROM payments p
        JOIN contracts c ON p.contract_id = c.id
        WHERE p.id = ? LIMIT 1
    ";
    $stmtSummary = $pdo->prepare($summarySql);
    $stmtSummary->execute([$paymentId]);
    $summaryData = $stmtSummary->fetch(PDO::FETCH_ASSOC);

    // --- บันทึก Log การทำรายการ ---
    if ($summaryData) {
        $logAction = ($mode === 'edit') ? 'edit_payment' : 'save_payment';
        
        // สร้างข้อความ Log แบบ HTML
        $logDesc = "
            <div style='font-size: 0.9rem;'>
                <div><i class='fa-solid fa-file-invoice-dollar text-primary me-2'></i><strong>สัญญา:</strong> " . htmlspecialchars($summaryData['contract_no_shop']) . "</div>
                <div><i class='fa-solid fa-user text-secondary me-2'></i><strong>ลูกค้า:</strong> " . htmlspecialchars($summaryData['customer_name']) . "</div>
                <hr class='my-1'>
                <div><i class='fa-solid fa-money-bill-wave text-success me-2'></i><strong>ชำระงวดที่ " . $summaryData['pay_no'] . ":</strong> " . number_format($amountPaid, 2) . " บาท</div>
                <div><i class='fa-solid fa-wallet text-danger me-2'></i><strong>ยอดคงเหลือทั้งสัญญา:</strong> " . number_format($summaryData['remaining'], 2) . " บาท</div>
            </div>
        ";

        logActivity($pdo, $_SESSION['user']['id'], $logAction, 'payment', $paymentId, $logDesc);
    }

    $pdo->commit();

    // 8) ส่งกลับ JSON
    echo json_encode([
        'success'            => true,
        'contract_id'        => (int)$summaryData['contract_id'],
        'paid_no'            => (int)$summaryData['pay_no'],
        'total_installments' => (int)$summaryData['total_installments'],
        'new_paid'           => (float)$summaryData['paid'],
        'new_remaining'      => (float)$summaryData['remaining']
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    $pdo->rollBack();
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => 'Server error: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}