<?php
// -------------------------------------------------------------
//  pay_modal_stub.php   (อยู่ใน partials/)
//  แสดงฟอร์มยืนยันการชำระสำหรับงวดที่ยัง pending
// -------------------------------------------------------------
$modalId = 'payModal-'.$row['id'];
$outstanding = ($row['amount_due'] ?? 0) - ($row['amount_paid'] ?? 0);
?>
<div class="modal fade" id="<?= $modalId ?>" tabindex="-1" aria-labelledby="<?= $modalId ?>Label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <form method="post" action="<?= $_SERVER['PHP_SELF'].'?group='.urlencode($activeTab) ?>"
                enctype="multipart/form-data">

                <input type="hidden" name="payment_id" value="<?= $row['id'] ?>">

                <div class="modal-header">
                    <h5 class="modal-title" id="<?= $modalId ?>Label">
                        ชำระสัญญา <?= htmlspecialchars($row['contract_no']) ?>
                        (งวดที่ <?= $row['pay_no'] ?>)
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">ยอดที่ต้องชำระ</label>
                        <input type="text" class="form-control" value="<?= number_format($row['amount_due'],2) ?> ฿"
                            readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">ยอดที่เคยชำระแล้ว</label>
                        <input type="text" class="form-control"
                            value="<?= number_format($row['amount_paid'] ?? 0,2) ?> ฿" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">ยอดที่จะชำระครั้งนี้</label>
                        <input type="number" step="0.01" name="amount_paid" class="form-control"
                            value="<?= $outstanding ?>" max="<?= $outstanding ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">วันที่ชำระ</label>
                        <input type="datetime-local" name="paid_date" class="form-control"
                            value="<?= date('Y-m-d\TH:i') ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">วิธีชำระเงิน</label>
                        <select name="payment_method" class="form-select" required>
                            <option value="cash">เงินสด</option>
                            <option value="transfer">โอนเงิน</option>
                            <option value="credit_card">บัตรเครดิต</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">อัปโหลดสลิป (ถ้ามี)</label>
                        <input type="file" name="slip_file" class="form-control" accept="image/*,application/pdf">
                        <div class="form-text">ขนาดไฟล์สูงสุด 2 MB</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">หมายเหตุ (ถ้ามี)</label>
                        <textarea name="note" class="form-control" rows="2"></textarea>
                    </div>
                </div><!-- /.modal-body -->

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fa-solid fa-check me-1"></i> ยืนยันชำระ
                    </button>
                </div>

            </form>

        </div>
    </div>
</div>