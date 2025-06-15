<?php
// File: public/pages/superadmin/payments/partials/payment_list_table.php

if (empty($contracts)): ?>
<div class="text-center text-muted py-4">
    ไม่พบสัญญาที่มีงวดกำลังจะถึง
</div>
<?php else:

  // 1) รวบรวม contract_id
  $contractIds = array_column($contracts, 'contract_id');
  $ph = implode(',', array_fill(0, count($contractIds), '?'));

  // 2) ดึงยอดจ่ายแล้ว, คงเหลือ, จำนวนงวดทั้งหมด
  $sqlStats = "
    SELECT
      p.contract_id,
      COALESCE(SUM(p.amount_paid),0)                       AS paid,
      COALESCE(SUM(p.amount_due-IFNULL(p.amount_paid,0)),0) AS remaining,
      COUNT(*)                                             AS total_installments
    FROM payments p
    WHERE p.contract_id IN ($ph)
    GROUP BY p.contract_id
  ";
  $stmtStats = $pdo->prepare($sqlStats);
  $stmtStats->execute($contractIds);
  $statRows = [];
  foreach ($stmtStats->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $statRows[$row['contract_id']] = $row;
  }

  // 3) ดึงเฉพาะ contract ที่มีงวด “เกินกำหนด”
  $sqlOv = "
    SELECT DISTINCT p.contract_id
    FROM payments p
    WHERE p.contract_id IN ($ph)
      AND p.status = 'pending'
      AND p.due_date < CURDATE()
  ";
  $stmtOv = $pdo->prepare($sqlOv);
  $stmtOv->execute($contractIds);
  $overdueIds = $stmtOv->fetchAll(PDO::FETCH_COLUMN);
?>
<style>
/* กระพริบเฉพาะข้อความสถานะ overdue */
.blink-overdue {
    animation: blink 1s steps(2, start) infinite;
}

@keyframes blink {
    to {
        visibility: hidden;
    }
}
</style>

<div class="table-responsive">
    <table class="table table-striped table-hover align-middle" id="contractsTable">
        <thead class="table-light">
            <tr>
                <th>#</th>
                <th>เลขสัญญา</th>
                <th>ชื่อลูกค้า</th>
                <th class="text-center">งวดถัดไป</th>
                <th class="text-center">วันที่ครบกำหนด</th>
                <th class="text-end">ยอดงวด (฿)</th>
                <th class="text-end">จ่ายแล้ว (฿)</th>
                <th class="text-end">คงเหลือ (฿)</th>
                <th class="text-center">สถานะ</th>
                <th class="text-center">ค่าใช้จ่าย</th>
                <th class="text-center">Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($contracts as $idx => $r):
        $cid        = $r['contract_id'];
        $dueRaw     = $r['next_due_date'];
        $dueDsp     = date('d/m/Y', strtotime($dueRaw));
        $amtDue     = number_format($r['next_amount_due'],2);

        $stats      = $statRows[$cid] ?? ['paid'=>0,'remaining'=>$r['next_amount_due'],'total_installments'=>1];
        $paidAmt    = number_format($stats['paid'],2);
        $remAmt     = number_format($stats['remaining'],2);
        $totalIns   = (int)$stats['total_installments'];
        $nextNo     = (int)$r['next_pay_no'];

        $isOverdue  = in_array($cid, $overdueIds, true);
        $status     = $r['next_status']; // จาก SQL manage_payments.php

        // 4) กำหนด label & icon & blink
        if ($status === 'paid') {
          // จ่ายแล้ว X/Y
          $label = "จ่ายงวดนี้แล้ว {$nextNo}/{$totalIns}";
          $icon  = 'fa-check-circle text-success';
          $blink = '';
        }
        elseif ($stats['remaining'] <= 0) {
          // ปิดสัญญา
          $label = 'ปิดสัญญา';
          $icon  = 'fa-check-circle text-success';
          $blink = '';
        }
        elseif ($isOverdue) {
          // เกินกำหนด
          $label = 'เกินกำหนด';
          $icon  = 'fa-exclamation-triangle text-danger';
          $blink = 'blink-overdue';
        }
        elseif ($dueRaw === date('Y-m-d')) {
          // ครบกำหนด
          $label = 'ครบกำหนด';
          $icon  = 'fa-calendar-day text-warning';
          $blink = '';
        }
        else {
          // กำลังผ่อน
          $label = 'กำลังผ่อน';
          $icon  = 'fa-spinner text-primary fa-spin';
          $blink = '';
        }
      ?>
            <tr data-contract-id="<?= $cid ?>" data-next-pay="<?= $nextNo ?>" data-total-installments="<?= $totalIns ?>"
                data-due-date="<?= $r['next_due_date'] ?>" data-remaining="<?= $statRows[$cid]['remaining'] ?>">

                <td><?= $idx+1 ?></td>
                <td><?= htmlspecialchars($r['contract_no'],ENT_QUOTES) ?></td>
                <td><?= htmlspecialchars($r['customer_name'],ENT_QUOTES) ?></td>
                <td class="text-center"><?= $nextNo ?>/<?= $totalIns ?></td>
                <td class="text-center"><?= $dueDsp ?></td>
                <td class="text-end"><?= $amtDue ?></td>
                <td class="text-end"><?= $paidAmt ?></td>
                <td class="text-end"><?= $remAmt ?></td>
                <td class="text-center">
                    <span class="status-text <?= $blink ?>">
                        <i class="fa-solid <?= $icon ?> me-1"></i><?= htmlspecialchars($label) ?>
                    </span>
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-outline-warning btn-add-expense"
                        data-contract-id="<?= $cid ?>" data-bs-toggle="modal" data-bs-target="#expenseModal">
                        <i class="fa-solid fa-wallet me-1"></i>ค่าใช้จ่าย
                    </button>
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-outline-primary btn-details"
                        data-contract-id="<?= $cid ?>"
                        data-contract-no="<?= htmlspecialchars($r['contract_no'],ENT_QUOTES) ?>"
                        data-customer-name="<?= htmlspecialchars($r['customer_name'],ENT_QUOTES) ?>"
                        data-bs-toggle="modal" data-bs-target="#contractModal">
                        <i class="fa-solid fa-circle-info me-1"></i>รายละเอียด
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>