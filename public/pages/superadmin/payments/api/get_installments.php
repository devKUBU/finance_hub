<?php
// File: public/pages/superadmin/payments/api/get_installments.php (Original structure with Penalty Logic)

// 1) โหลดระบบ Bootstrap และ Helpers
require_once realpath(__DIR__ . '/../../../../../config/bootstrap.php');
require_once ROOT_PATH . '/includes/helpers.php';
if (session_status() === PHP_SESSION_NONE) session_start();
requireRole(['superadmin', 'admin']);

// 2) เชื่อมต่อฐานข้อมูล
require_once ROOT_PATH . '/config/db.php';
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
date_default_timezone_set('Asia/Bangkok');
// 3) รับพารามิเตอร์
$contractId   = isset($_GET['contract_id'])   ? (int) $_GET['contract_id'] : 0;
$contractNo   = isset($_GET['contract_no'])   ? trim($_GET['contract_no'])   : '';
$customerName = isset($_GET['customer_name']) ? trim($_GET['customer_name']) : '';

if ($contractId <= 0) {
    http_response_code(400);
    echo '<div class="text-danger text-center p-4">ข้อมูลไม่ถูกต้อง</div>';
    exit;
}

// --- (เพิ่มเข้ามา) ดึงข้อมูลสัญญาและเงื่อนไขค่าปรับ ---
$contractStmt = $pdo->prepare("SELECT penalty_type, penalty_rate FROM contracts WHERE id = ?");
$contractStmt->execute([$contractId]);
$penaltySettings = $contractStmt->fetch(PDO::FETCH_ASSOC);


// 4) ดึงข้อมูลงวดผ่อน (installments) ของสัญญานั้น
$sql = "
    SELECT 
      p.id, p.pay_no, DATE(p.due_date) AS due_date,
      p.amount_due, COALESCE(p.amount_paid, 0) AS amount_paid,
      p.penalty_amount, p.fee_unlock, p.fee_document, p.fee_other,
      p.note, p.slip_paths, p.status
    FROM payments p
    WHERE p.contract_id = ?
    ORDER BY p.pay_no ASC
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$contractId]);
$installments = $stmt->fetchAll(PDO::FETCH_ASSOC);


// 5) แสดงเลขสัญญา & ชื่อลูกค้า
echo '<div class="mb-3"><strong>เลขสัญญา:</strong> ' . htmlspecialchars($contractNo, ENT_QUOTES) . ' &nbsp;&nbsp; <strong>ชื่อลูกค้า:</strong> ' . htmlspecialchars($customerName, ENT_QUOTES) . '</div>';

// 6) แสดงตารางงวดผ่อน
if (empty($installments)) {
    echo '<div class="text-center text-muted py-4">ไม่พบข้อมูลงวดผ่อน</div>';
    exit;
}
?>

<div class="table-responsive">
    <table class="table table-bordered table-sm align-middle">
        <thead class="table-light">
            <tr>
                <th class="text-center">งวดที่</th>
                <th class="text-center">วันที่ครบกำหนด</th>
                <th class="text-end">ยอดต้องจ่าย (฿)</th>
                <th class="text-end">จ่ายแล้ว (฿)</th>
                <th class="text-end">ค่าปรับ (฿)</th>
                <th class="text-end">ค่าปลดล็อค (฿)</th>
                <th class="text-end">ค่าเอกสาร (฿)</th>
                <th class="text-end">ค่าบริการอื่น (฿)</th>
                <th class="text-center">สถานะ</th>
                <th class="text-center">ดู</th>
                <th class="text-center">Action</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $today = date('Y-m-d');
            foreach ($installments as $row):
                
                // --- (เพิ่มเข้ามา) ส่วนคำนวณค่าปรับอัตโนมัติ ---
                $calculatedPenalty = 0; // ประกาศค่าเริ่มต้น
                if ($penaltySettings && $penaltySettings['penalty_type'] !== 'none' && $row['status'] === 'pending' && $row['due_date'] < $today) {
                    if ($penaltySettings['penalty_type'] === 'daily') {
                        $dueDateObj = new DateTime($row['due_date']);
                        $todayObj = new DateTime($today);
                        $daysLate = $todayObj->diff($dueDateObj)->days;
                        $calculatedPenalty = $daysLate * (float)$penaltySettings['penalty_rate'];
                    } elseif ($penaltySettings['penalty_type'] === 'fixed') {
                        $calculatedPenalty = (float)$penaltySettings['penalty_rate'];
                    }
                }
                // ใช้ค่าปรับที่เคยบันทึกไว้ ถ้ามี, หรือใช้ค่าที่คำนวณใหม่ถ้ายังไม่เคยบันทึก
                $finalPenalty = ($row['penalty_amount'] > 0) ? $row['penalty_amount'] : $calculatedPenalty;

                // --- ส่วนแสดงผล (เหมือนโค้ดเดิมของคุณ) ---
                $dueDisplay = date('d/m/Y', strtotime($row['due_date']));
                $note = htmlspecialchars($row['note'] ?? '', ENT_QUOTES);
                $status = $row['status'];

                if ($status === 'pending') {
                    if ($row['due_date'] < $today) { $statusLabel = '<span class="badge bg-danger">เกินกำหนด</span>'; } 
                    elseif ($row['due_date'] === $today) { $statusLabel = '<span class="badge bg-warning text-dark">ครบกำหนดวันนี้</span>'; } 
                    else { $statusLabel = '<span class="badge bg-info">รอชำระ</span>'; }
                } else {
                    $statusLabel = '<span class="badge bg-success">จ่ายแล้ว</span>';
                }

                $slips = [];
                if (!empty($row['slip_paths'])) {
                    $decoded = json_decode($row['slip_paths'], true);
                    if (is_array($decoded)) { $slips = $decoded; }
                }
                $slipsAttr = implode(',', $slips);
            ?>
            <tr>
                <td class="text-center"><?= (int)$row['pay_no'] ?></td>
                <td class="text-center"><?= $dueDisplay ?></td>
                <td class="text-end"><?= number_format((float)$row['amount_due'], 2) ?></td>
                <td class="text-end"><?= number_format((float)$row['amount_paid'], 2) ?></td>
                <td class="text-end text-danger"><?= number_format((float)$finalPenalty, 2) ?></td>
                <td class="text-end"><?= number_format((float)$row['fee_unlock'], 2) ?></td>
                <td class="text-end"><?= number_format((float)$row['fee_document'], 2) ?></td>
                <td class="text-end"><?= number_format((float)$row['fee_other'], 2) ?></td>
                <td class="text-center"><?= $statusLabel ?></td>
                <td class="text-center">
                    <button class="btn btn-sm btn-info btn-view-payment" data-payment-id="<?= $row['id'] ?>"
                        data-amount-paid="<?= $row['amount_paid'] ?>" data-penalty="<?= $row['penalty_amount'] ?>"
                        data-fee-unlock="<?= $row['fee_unlock'] ?>" data-fee-document="<?= $row['fee_document'] ?>"
                        data-fee-other="<?= $row['fee_other'] ?>" data-note="<?= $note ?>"
                        data-slips="<?= htmlspecialchars($slipsAttr, ENT_QUOTES) ?>" title="ดูรายละเอียด">
                        <i class="fa fa-eye"></i>
                    </button>
                </td>
                <td class="text-center">
                    <?php if ($row['status'] === 'pending'): ?>
                    <button class="btn btn-sm btn-primary btn-pay-installment" data-payment-id="<?= $row['id'] ?>"
                        data-pay-no="<?= $row['pay_no'] ?>" data-amount-due="<?= $row['amount_due'] ?>"
                        data-amount-paid="<?= $row['amount_paid'] ?>" data-penalty="<?= $finalPenalty ?>"
                        data-due-date="<?= $row['due_date'] ?>"
                        data-contract-no="<?= htmlspecialchars($contractNo,ENT_QUOTES) ?>"
                        data-customer-name="<?= htmlspecialchars($customerName,ENT_QUOTES) ?>">
                        ชำระ
                    </button>
                    <?php else: ?>
                    <button class="btn btn-sm btn-outline-warning btn-edit-payment" data-payment-id="<?= $row['id'] ?>"
                        data-pay-no="<?= $row['pay_no'] ?>" data-amount-due="<?= $row['amount_due'] ?>"
                        data-amount-paid="<?= $row['amount_paid'] ?>" data-penalty="<?= $finalPenalty ?>"
                        data-fee-unlock="<?= $row['fee_unlock'] ?>" data-fee-document="<?= $row['fee_document'] ?>"
                        data-fee-other="<?= $row['fee_other'] ?>" data-due-date="<?= $row['due_date'] ?>"
                        data-note="<?= $note ?>" data-contract-no="<?= htmlspecialchars($contractNo,ENT_QUOTES) ?>"
                        data-customer-name="<?= htmlspecialchars($customerName,ENT_QUOTES) ?>">
                        <i class="fa fa-pen"></i>
                    </button>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>