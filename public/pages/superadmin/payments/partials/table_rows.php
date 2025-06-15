<?php
/* ------------------------------------------------------------------
   File: public/pages/superadmin/payments/partials/table_rows.php
   - ใช้ $rows   : array   ข้อมูลงวด
   - ใช้ $group  : string  ชื่อแท็บ (all|today|…)
   - ใช้ $baseURL: string  ตามที่ไฟล์แม่ส่งมา
------------------------------------------------------------------ */
if (empty($rows)) {
    echo '<div class="text-center text-success py-4">ไม่มีรายการในกลุ่มนี้</div>';
    return;
}
?>
<div class="table-responsive">
    <table class="table table-bordered table-hover" id="paymentsTable-<?= htmlspecialchars($group) ?>">
        <thead class="table-light">
            <tr>
                <th>
                    <i class="fa-solid fa-file-contract me-1"></i>
                    สัญญา
                </th>
                <th>
                    <i class="fa-solid fa-user me-1"></i>
                    ชื่อลูกค้า
                </th>
                <th class="text-center">
                    <i class="fa-solid fa-hashtag me-1"></i>
                    งวด (No.)
                </th>
                <th class="text-center">
                    <i class="fa-solid fa-calendar-day me-1"></i>
                    วันครบกำหนด
                </th>
                <th class="text-end">
                    <i class="fa-solid fa-money-bill-wave me-1"></i>
                    ยอดสุทธิ (฿)
                </th>
                <th class="text-end">
                    <i class="fa-solid fa-coins me-1"></i>
                    จ่าย / คงเหลือ (฿)
                </th>
                <th class="text-center">
                    <i class="fa-solid fa-circle-info me-1"></i>
                    สถานะ
                </th>
                <th class="text-start">
                    <i class="fa-solid fa-list-check me-1"></i>
                    Action
                </th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $r): ?>
            <?php
                // ดึงค่าจากแต่ละแถว
                $id            = (int)$r['id'];
                $contractNo    = htmlspecialchars($r['contract_no']);
                $custName      = htmlspecialchars($r['customer_firstname'] . ' ' . $r['customer_lastname']);
                $custPhone     = htmlspecialchars($r['customer_phone'] ?? '');
                $payNo         = (int)$r['pay_no'];
                $dueDateRaw    = substr($r['due_date'], 0, 10);     // YYYY-MM-DD
                $amtDue        = (float)$r['amount_due'];
                $amtPaid       = (float)$r['amount_paid'];
                $penalty       = (float)($r['penalty_amount'] ?? 0);
                $feeUnlock     = (float)($r['fee_unlock'] ?? 0);
                $feeDocument   = (float)($r['fee_document'] ?? 0);
                $feeOther      = (float)($r['fee_other'] ?? 0);
                $note          = htmlspecialchars($r['note'] ?? '');
                $slipPathsJson = json_decode($r['slip_paths'] ?? '[]', true) ?: [];
                $slipPaths     = htmlspecialchars(json_encode($slipPathsJson), ENT_QUOTES);

                // คำนวณยอดรวมค่าธรรมเนียม
                $totalFees        = $penalty + $feeUnlock + $feeDocument + $feeOther;
                // คำนวณยอดรวมที่จ่ายจริง (รวมค่าธรรมเนียม เพื่อแสดงใน modal)
                $totalCollected   = $amtPaid + $totalFees;

                // คำนวณยอดคงเหลือ (เฉพาะยอดงวด)
                $remainingBal     = $amtDue - $amtPaid;
                $remainingBalFmt  = number_format(max($remainingBal, 0), 2);

                // ฟอร์แมตตัวเลข
                $amtDueFmt         = number_format($amtDue, 2);
                $amtPaidFmt        = number_format($amtPaid, 2);
                $totalCollectedFmt = number_format($totalCollected, 2);

                // กำหนดสถานะ 4 กรณี (เทียบวันที่กับ “today”)
                $today = date('Y-m-d');
                if ($amtPaid >= $amtDue) {
                    // จ่ายครบแล้ว (ไม่รวมค่าธรรมเนียม)
                    $badgeClass = 'badge bg-success';
                    $badgeLabel = 'ครบแล้ว';
                } else {
                    if ($dueDateRaw > $today) {
                        // ยังไม่ถึงกำหนดจ่าย
                        $badgeClass = 'badge bg-secondary text-dark';
                        $badgeLabel = 'ยังไม่ถึงกำหนดจ่าย';
                    }
                    elseif ($dueDateRaw === $today) {
                        // วันครบกำหนดวันนี้ แต่จ่ายไม่ครบ
                        $badgeClass = 'badge bg-warning text-dark';
                        $badgeLabel = 'จ่ายยังไม่ครบ';
                    }
                    else {
                        // เกินกำหนดจ่าย (dueDateRaw < today และยังจ่ายไม่ครบ)
                        $badgeClass = 'badge bg-danger';
                        $badgeLabel = 'เกินกำหนดจ่าย';
                    }
                }

                // ฟอร์แมตวันที่
                $dueDateDisplay = date('d/m/Y', strtotime($dueDateRaw));
            ?>
            <tr data-payment-id="<?= $id ?>" data-contract-no="<?= $contractNo ?>" data-cust-name="<?= $custName ?>"
                data-cust-phone="<?= $custPhone ?>" data-pay-no="<?= $payNo ?>" data-due-date="<?= $dueDateRaw ?>"
                data-amount-due="<?= $amtDue ?>" data-amount-paid="<?= $amtPaid ?>" data-penalty="<?= $penalty ?>"
                data-fee-unlock="<?= $feeUnlock ?>" data-fee-document="<?= $feeDocument ?>"
                data-fee-other="<?= $feeOther ?>" data-note="<?= $note ?>" data-slip-paths="<?= $slipPaths ?>"
                data-principal="<?= htmlspecialchars($r['principal_amount'] ?? 0) ?>"
                data-remaining-balance="<?= $remainingBal ?>" data-not-collected="<?= $remainingBal ?>">
                <!-- สัญญา -->
                <td><?= $contractNo ?></td>

                <!-- ชื่อลูกค้า -->
                <td><?= $custName ?></td>

                <!-- งวด (No.) -->
                <td class="text-center"><?= $payNo ?></td>

                <!-- วันที่ครบกำหนด -->
                <td class="text-center"><?= $dueDateDisplay ?></td>

                <!-- ยอดสุทธิ (amount_due) -->
                <td class="text-end"><?= $amtDueFmt ?></td>

                <!-- จ่าย / คงเหลือ -->
                <td class="text-end">
                    <?= $amtPaidFmt ?>
                    <?php if ($remainingBal > 0): ?>
                    &nbsp;(<span class="text-danger">คงเหลือ <?= $remainingBalFmt ?></span>)
                    <?php endif; ?>
                </td>

                <!-- สถานะ -->
                <td class="text-center">
                    <span class="<?= $badgeClass ?>"><?= $badgeLabel ?></span>
                </td>

                <!-- Action -->
                <td class="text-start">
                    <?php if ($amtPaid >= $amtDue): ?>
                    <!-- 1) จ่ายครบแล้ว: แสดงปุ่ม ดูรายละเอียด + แก้ไข -->
                    <button type="button" class="btn btn-sm btn-outline-info btn-view-payment me-1"
                        data-bs-toggle="modal" data-bs-target="#paymentDetailModal">
                        <i class="fa-solid fa-circle-info me-1"></i>ดูรายละเอียด
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary btn-open-pay-modal"
                        data-bs-toggle="modal" data-bs-target="#paymentModal">
                        <i class="fa-solid fa-pen-to-square me-1"></i>แก้ไข
                    </button>

                    <?php elseif ($dueDateRaw > $today): ?>
                    <!-- 2) ยังไม่ถึงกำหนดจ่าย: ปุ่ม disabled -->
                    <button type="button" class="btn btn-sm btn-outline-secondary" disabled>
                        <i class="fa-solid fa-clock me-1"></i>ยังไม่ถึงกำหนดจ่าย
                    </button>

                    <?php else: ?>
                    <!-- 3 & 4) จ่ายยังไม่ครบ หรือ เกินกำหนดจ่าย: ดูรายละเอียด + แก้ไข + ชำระเงิน -->
                    <button type="button" class="btn btn-sm btn-outline-info btn-view-payment me-1"
                        data-bs-toggle="modal" data-bs-target="#paymentDetailModal">
                        <i class="fa-solid fa-circle-info me-1"></i>ดูรายละเอียด
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary btn-open-pay-modal me-1"
                        data-bs-toggle="modal" data-bs-target="#paymentModal">
                        <i class="fa-solid fa-pen-to-square me-1"></i>แก้ไข
                    </button>
                    <button type="button" class="btn btn-sm btn-primary btn-open-pay-modal" data-bs-toggle="modal"
                        data-bs-target="#paymentModal">
                        <i class="fa-solid fa-credit-card me-1"></i>ชำระเงิน
                    </button>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>