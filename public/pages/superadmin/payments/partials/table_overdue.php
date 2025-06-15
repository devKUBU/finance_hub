<?php
/* ------------------------------------------------------------------
   partials/table_overdue.php
   - ใช้ตัวแปร $rowsOver  = array ข้อมูลสรุปค้างชำระต่อสัญญา
   - ใช้ตัวแปร $baseURL   = base URL ของเว็บ (ส่งมาจากไฟล์แม่)
------------------------------------------------------------------ */
if (empty($rowsOver)) {
    echo '<div class="text-center text-success py-4">ยังไม่มีสัญญาใดที่เกินกำหนด</div>';
    return;
}
?>
<div class="table-responsive">
    <table id="table-overdue-contracts" class="table table-striped table-hover align-middle">
        <thead class="table-light">
            <tr>
                <th>#</th>
                <th>เลขสัญญา</th>
                <th>ลูกค้า</th>
                <th class="text-end">ยอดเงินค้างชำระทั้งหมด (฿)</th>
                <th class="text-center">จัดการ</th>
            </tr>
        </thead>

        <tbody>
            <?php foreach ($rowsOver as $idx => $row): ?>
            <tr>
                <td><?= $idx + 1 ?></td>
                <td><?= htmlspecialchars($row['contract_no']) ?></td>
                <td><?= htmlspecialchars($row['customer_firstname'].' '.$row['customer_lastname']) ?></td>
                <td class="text-end"><?= number_format($row['total_outstanding'], 2) ?></td>
                <td class="text-center">
                    <a href="<?= $baseURL ?>/pages/superadmin/contracts/view.php?id=<?= $row['contract_id'] ?>"
                        class="btn btn-sm btn-outline-primary">
                        ดูรายละเอียด
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>