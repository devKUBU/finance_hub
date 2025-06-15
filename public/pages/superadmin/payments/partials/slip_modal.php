<?php
$modalId = 'slipModal-'.$row['id'];
$fullPath = $baseURL.'/'.ltrim($row['slip_path'],'/');  // กันกรณี path ไม่มี /
$ext = strtolower(pathinfo($row['slip_path'], PATHINFO_EXTENSION));
?>
<div class="modal fade" id="<?= $modalId ?>" tabindex="-1" aria-labelledby="<?= $modalId ?>Label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="<?= $modalId ?>Label">
                    สลิปการชำระ – <?= htmlspecialchars($row['contract_no']) ?> (งวดที่ <?= $row['pay_no'] ?>)
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <?php if (in_array($ext,['jpg','jpeg','png','gif','webp'])): ?>
                <img src="<?= $fullPath ?>" class="img-fluid rounded shadow-sm">
                <?php elseif ($ext === 'pdf'): ?>
                <iframe src="<?= $fullPath ?>" width="100%" height="600" style="border:none;"></iframe>
                <?php else: ?>
                <a href="<?= $fullPath ?>" target="_blank" class="btn btn-outline-primary">เปิดไฟล์สลิป</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>