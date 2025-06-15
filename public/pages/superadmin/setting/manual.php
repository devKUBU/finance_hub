<?php
// File: public/pages/superadmin/setting/manual.php
// รับตัวแปรจาก interest_settings.php: $errorManual, $manualRow['is_active'], $groupedData, $currentManualInterval
?>
<div class="card mb-4 shadow-sm">
    <div class="card-header bg-light">
        <strong>สูตรที่ 4: กำหนดเอง (Manual)</strong>
    </div>

    <div class="card-body">
        <?php if (!empty($errorManual)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($errorManual) ?></div>
        <?php endif; ?>

        <form id="manualForm" method="post" class="needs-validation" novalidate>
            <input type="hidden" name="form_type" value="manual">

            <!-- เปิด/ปิดสูตรทั้งหมด -->
            <div class="form-check mb-3">
                <input type="hidden" name="manual_active" value="0">
                <input type="checkbox" id="manualActive" name="manual_active" class="form-check-input" value="1"
                    <?= $manualRow['is_active'] ? 'checked' : '' ?>>
                <label for="manualActive" class="form-check-label">
                    เปิดใช้งานสูตรที่ 4
                </label>
            </div>

            <!-- ความถี่การชำระ (วัน) -->
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label class="form-label">
                        <i class="fa-solid fa-calendar-days me-1 text-primary"></i>
                        ความถี่การชำระ (วัน)
                    </label>
                    <input type="number" name="manual_interval" class="form-control" min="1" required
                        value="<?= htmlspecialchars($currentManualInterval) ?>">
                    <div class="invalid-feedback">กรุณาระบุจำนวนวัน</div>
                </div>
            </div>

            <!-- Container สำหรับแต่ละกลุ่ม -->
            <div id="manualGroupsContainer">
                <?php foreach ($groupedData as $gi => $group): ?>
                <div class="manual-group card mb-3" style="border-left:4px solid #007bff;">
                    <div class="card-body">
                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label class="form-label">ยอดเงินต้น (บาท)</label>
                                <input type="number" name="manual_principal[]" class="form-control" step="0.01" required
                                    value="<?= htmlspecialchars($group['principal']) ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">ชื่อกลุ่ม (ไม่บังคับ)</label>
                                <input type="text" name="manual_group_name[]" class="form-control"
                                    value="<?= htmlspecialchars($group['group_name']) ?>">
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <button type="button" class="btn btn-outline-danger remove-group">
                                    <i class="fa-solid fa-trash me-1"></i>ลบกลุ่มนี้
                                </button>
                            </div>
                        </div>

                        <div class="table-responsive mb-3">
                            <table class="table table-sm table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width:120px">เดือน (n)</th>
                                        <th>ยอดผ่อนคืน (บาท)</th>
                                        <th style="width:80px">ลบ</th>
                                        <th style="width:80px">ใช้งาน</th>
                                    </tr>
                                </thead>
                                <tbody class="group-entries">
                                    <?php foreach ($group['entries'] as $ri => $entry): ?>
                                    <tr>
                                        <td>
                                            <!-- class="entry-month" ชั่วคราว -->
                                            <input type="number" class="form-control entry-month" required
                                                value="<?= $entry['month_idx'] ?>">
                                        </td>
                                        <td>
                                            <!-- class="entry-repayment" ชั่วคราว -->
                                            <input type="number" class="form-control entry-repayment" step="0.01"
                                                required value="<?= $entry['repayment'] ?>">
                                        </td>
                                        <td class="text-center">
                                            <button type="button"
                                                class="btn btn-sm btn-outline-danger remove-entry">–</button>
                                        </td>
                                        <td class="text-center">
                                            <!-- เปลี่ยนชื่อ hidden+checkbox ให้เป็น manual_active_entry[gi][ri] -->
                                            <input type="hidden" class="entry-active-hidden" value="0">
                                            <input type="checkbox" class="form-check-input mt-1 entry-active-checkbox"
                                                value="1" <?= !empty($entry['is_active']) ? 'checked' : '' ?>>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <button type="button" class="btn btn-sm btn-outline-primary add-entry">
                            <i class="fa-solid fa-plus me-1"></i>เพิ่มเดือน
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>

                <?php if (empty($groupedData)): ?>
                <!-- กรณียังไม่มีข้อมูล ให้สร้างกลุ่มเปล่า 1 กลุ่ม (เหมือนตัวอย่างด้านบน) -->
                <div class="manual-group card mb-3" style="border-left:4px solid #007bff;">
                    <div class="card-body">
                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label class="form-label">ยอดเงินต้น (บาท)</label>
                                <input type="number" name="manual_principal[]" class="form-control" step="0.01"
                                    required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">ชื่อกลุ่ม (ไม่บังคับ)</label>
                                <input type="text" name="manual_group_name[]" class="form-control">
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <button type="button" class="btn btn-outline-danger remove-group">
                                    <i class="fa-solid fa-trash me-1"></i>ลบกลุ่มนี้
                                </button>
                            </div>
                        </div>
                        <div class="table-responsive mb-3">
                            <table class="table table-sm table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width:120px">เดือน (n)</th>
                                        <th>ยอดผ่อนคืน (บาท)</th>
                                        <th style="width:80px">ลบ</th>
                                        <th style="width:80px">ใช้งาน</th>
                                    </tr>
                                </thead>
                                <tbody class="group-entries">
                                    <?php foreach ($group['entries'] as $ri => $entry): ?>
                                    <tr>
                                        <td>
                                            <!-- class="entry-month" ชั่วคราว -->
                                            <input type="number" class="form-control entry-month" required
                                                value="<?= $entry['month_idx'] ?>">
                                        </td>
                                        <td>
                                            <!-- class="entry-repayment" ชั่วคราว -->
                                            <input type="number" class="form-control entry-repayment" step="0.01"
                                                required value="<?= $entry['repayment'] ?>">
                                        </td>
                                        <td class="text-center">
                                            <button type="button"
                                                class="btn btn-sm btn-outline-danger remove-entry">–</button>
                                        </td>
                                        <td class="text-center">
                                            <!-- เปลี่ยนชื่อ hidden+checkbox ให้เป็น manual_active_entry[gi][ri] -->
                                            <input type="hidden" class="entry-active-hidden" value="0">
                                            <input type="checkbox" class="form-check-input mt-1 entry-active-checkbox"
                                                value="1" <?= !empty($entry['is_active']) ? 'checked' : '' ?>>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary add-entry">
                            <i class="fa-solid fa-plus me-1"></i>เพิ่มเดือน
                        </button>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- ปุ่มเพิ่มกลุ่มใหม่ -->
            <div class="mb-3">
                <button type="button" id="addManualGroup" class="btn btn-outline-success">
                    <i class="fa-solid fa-plus me-1"></i>เพิ่มกลุ่มเงินต้นใหม่
                </button>
            </div>

            <!-- ปุ่มบันทึก -->
            <div class="text-end">
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-save me-1"></i>บันทึกสูตรที่ 4
                </button>
            </div>
        </form>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('manualGroupsContainer');
    const addGroupBtn = document.getElementById('addManualGroup');
    const form = document.getElementById('manualForm');

    // ฟังก์ชันช่วยสร้างแถวใหม่ พร้อมช่อง Toggle (ชื่อชั่วคราวก่อน; JS จะกำหนด name จริงก่อนส่ง)
    function createEntryRow(monthIndex = 1, repaymentValue = '', isActive = true) {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>
                <input type="number" class="form-control entry-month" required value="${monthIndex}">
            </td>
            <td>
                <input type="number" class="form-control entry-repayment" step="0.01" required value="${repaymentValue}">
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-outline-danger remove-entry">–</button>
            </td>
            <td class="text-center">
                <!-- hidden + checkbox ชั่วคราว; JS จะกำหนด name ให้เป็น manual_active_entry[gi][ri] -->
                <input type="hidden" class="entry-active-hidden" value="0">
                <input type="checkbox" class="form-check-input mt-1 entry-active-checkbox" value="1" ${isActive ? 'checked' : ''}>
            </td>
        `;
        return tr;
    }

    // เมื่อคลิก “เพิ่มกลุ่ม” → clone กลุ่มแรก แล้วรีเซ็ตค่าต่างๆ
    addGroupBtn.addEventListener('click', () => {
        const firstGroup = container.querySelector('.manual-group');
        const clone = firstGroup.cloneNode(true);

        // รีเซ็ตค่าหลักของกลุ่ม
        clone.querySelector('input[name="manual_principal[]"]').value = '';
        clone.querySelector('input[name="manual_group_name[]"]').value = '';

        // รีเซ็ตตารางงวด: ลบบรรทัดเก่า แล้วใส่แถวเดียว (เดือน=1, active=checked)
        const tbody = clone.querySelector('.group-entries');
        tbody.innerHTML = '';
        tbody.appendChild(createEntryRow(1, '', true));

        container.appendChild(clone);
    });

    // จับ event ภายใน container (add-entry, remove-entry, remove-group)
    container.addEventListener('click', e => {
        // ลบกลุ่มทั้งกล่อง
        if (e.target.closest('.remove-group')) {
            const groups = container.querySelectorAll('.manual-group');
            if (groups.length > 1) {
                e.target.closest('.manual-group').remove();
            }
            return;
        }
        // เพิ่มแถวใหม่ภายในกลุ่ม
        if (e.target.closest('.add-entry')) {
            const groupCard = e.target.closest('.manual-group');
            const tbody = groupCard.querySelector('.group-entries');
            const nextIndex = tbody.children.length + 1;
            tbody.appendChild(createEntryRow(nextIndex, '', true));
            return;
        }
        // ลบแถวภายในกลุ่ม (แต่ต้องเหลือ 1 แถวขึ้นไปเสมอ)
        if (e.target.closest('.remove-entry')) {
            const tbody = e.target.closest('tbody.group-entries');
            if (tbody.children.length > 1) {
                e.target.closest('tr').remove();
            }
            return;
        }
    });

    // ก่อน submit ให้ re-index ตั้งชื่อ field ให้ถูกต้อง (principal, group_name, month[][], repayment[][], active[][])
    form.addEventListener('submit', (evt) => {
        // แต่ละกลุ่ม (manual-group) index = gi
        container.querySelectorAll('.manual-group').forEach((groupEl, gi) => {
            // ตั้งชื่อยอดเงินต้น & ชื่อกลุ่ม
            groupEl.querySelector('input[name="manual_principal[]"]').name =
                'manual_principal[]';
            groupEl.querySelector('input[name="manual_group_name[]"]').name =
                'manual_group_name[]';

            // ไล่ทุกแถวในตารางงวด
            const rows = groupEl.querySelectorAll('tbody.group-entries tr');
            rows.forEach((tr, ri) => {
                // เดือน (input.entry-month)
                const monthInput = tr.querySelector('.entry-month');
                monthInput.name = `manual_month[${gi}][${ri}]`;

                // ยอดผ่อนคืน (input.entry-repayment)
                const repayInput = tr.querySelector('.entry-repayment');
                repayInput.name = `manual_repayment[${gi}][${ri}]`;

                // Hidden + Checkbox สำหรับสถานะ active
                const hiddenActive = tr.querySelector('.entry-active-hidden');
                const checkActive = tr.querySelector('.entry-active-checkbox');
                hiddenActive.name = `manual_active_entry[${gi}][${ri}]`;
                checkActive.name = `manual_active_entry[${gi}][${ri}]`;
            });
        });
        // หลังตั้งชื่อครบแล้ว ให้ submit ต่องาน PHP
    });
});
</script>