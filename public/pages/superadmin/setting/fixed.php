<?php
// File: public/pages/superadmin/setting/fixed.php
// ต้องมีตัวแปรจาก interest_settings.php:
//   $errorFixed, $successFixed, $dbFixed, $currentFixedActive, $currentFixedInterval
$currentLabel = $formulaLabels['fixed_interest'] ?? '';
?>

<div class="card mb-4 shadow-sm">
    <!-- ปุ่มสลับแสดง/ซ่อนคำอธิบายสูตรที่ 1 -->
    <div class="mb-3 mt-3">
        <button class="btn btn-sm btn-outline-info" type="button" data-bs-toggle="collapse" data-bs-target="#fixedDesc"
            aria-expanded="false" aria-controls="fixedDesc">
            <i class="fa-solid fa-info-circle text-info me-1"></i>คำอธิบายสูตรที่ 1
        </button>
    </div>

    <!-- คำอธิบายสูตรที่ 1 พับได้ พร้อมไอค่อนสี -->
    <div class="collapse mb-4" id="fixedDesc">
        <div class="card card-body bg-light">
            <p>
                <i class="fa-solid fa-calculator text-primary me-2"></i>
                สูตร <strong>Fixed Interest</strong> เป็นการคำนวณดอกเบี้ยแบบง่าย (Simple Interest) โดยใช้ multiplier
                คงที่:
            </p>
            <ul class="mb-0">
                <li>
                    <i class="fa-solid fa-coins text-success me-2"></i>
                    <strong>ดอกเบี้ยรวม</strong> = เงินต้น × Multiplier
                </li>
                <li>
                    <i class="fa-solid fa-arrows-spin text-warning me-2"></i>
                    เช่น ถ้า Multiplier = 125% และเงินต้น = 5,000 → ดอกเบี้ยรวม = 5,000 × 125% = 6,250 บาท
                </li>
                <li>
                    <i class="fa-solid fa-receipt text-danger me-2"></i>
                    <strong>ยอดผ่อนคืนต่อรอบ</strong> = ดอกเบี้ยรวม ÷ (30 / interval_days)
                </li>
                <li>
                    <i class="fa-solid fa-clock text-secondary me-2"></i>
                    ตัวอย่าง: interval_days = 15 → จำนวนงวดต่อเดือน = 30/15 = 2 งวด → งวดละ 6,250 ÷ 2 = 3,125 บาท
                </li>
                <li>
                    <i class="fa-solid fa-toggle-on text-info me-2"></i>
                    สามารถเปิด/ปิดสูตร และปรับจำนวนวันตัดรอบ (interval_days) ได้ตามต้องการ
                </li>
            </ul>
        </div>
    </div>

    <fieldset class="card-body">
        <?php if ($errorFixed): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($errorFixed) ?></div>
        <?php endif; ?>
        <?php if ($successFixed): ?>
        <div class="alert alert-success"><?= htmlspecialchars($successFixed) ?></div>
        <?php endif; ?>

        <form id="fixedForm" method="post" class="needs-validation" novalidate>
            <input type="hidden" name="form_type" value="fixed">
            <!-- ชื่อสูตร + Toggle -->
            <div class="mb-4 row">
                <label class="col-sm-2 col-form-label">ชื่อสูตรค่าบริการ</label>
                <div class="col-sm-10">
                    <input type="text" name="fixed_label" class="form-control" required
                        value="<?= htmlspecialchars($currentLabel) ?>">
                    <div class="invalid-feedback">กรุณาระบุชื่อสูตร</div>
                </div>
            </div>

            <!-- Toggle เปิด/ปิดสูตร: เพิ่ม data-bs-* -->
            <div class="form-check mb-3">
                <input type="hidden" name="fixed_active" value="0">
                <input type="checkbox" id="fixedActive" name="fixed_active" class="form-check-input" value="1"
                    data-bs-toggle="collapse" data-bs-target="#fixedSettings"
                    aria-expanded="<?= $currentFixedActive ? 'true' : 'false' ?>" aria-controls="fixedSettings"
                    <?= $currentFixedActive ? 'checked' : '' ?>>
                <label for="fixedActive" class="form-check-label">
                    เปิดใช้งานสูตรที่ 1
                </label>
            </div>

            <fieldset id="fixedSettings" disabled class="collapse <?= $currentFixedActive ? 'show' : '' ?>">
                <!-- ความถี่การชำระ -->
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label">ความถี่การชำระ (วัน)</label>
                        <input type="number" name="fixed_interval" class="form-control" min="1" required
                            value="<?= $currentFixedInterval ?>">
                        <div class="invalid-feedback">กรุณาระบุจำนวนวัน</div>
                    </div>
                </div>

                <!-- ตารางเดือน ↔ Multiplier -->
                <div class="table-responsive mb-3">
                    <table class="table table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center" style="width:80px">✓ ใช้งาน</th>
                                <th style="width:120px">เดือน (n)</th>
                                <th>Multiplier (%)</th>
                                <th class="text-center" style="width:80px">ลบ</th>
                            </tr>
                        </thead>
                        <tbody id="fixedBody">
                            <?php foreach ($dbFixed as $row): ?>
                            <tr>
                                <td class="text-center">
                                    <input type="hidden" name="fixed_enabled[<?= $row['months'] ?>]" value="0">
                                    <input type="checkbox" name="fixed_enabled[<?= $row['months'] ?>]" value="1"
                                        <?= $row['is_active'] ? 'checked' : '' ?>>
                                </td>
                                <td>
                                    <input type="number" name="fixed_month[]" class="form-control month-input" required
                                        min="1" value="<?= $row['months'] ?>">
                                </td>
                                <td>
                                    <input type="number" name="fixed_multiplier[]" class="form-control" required
                                        step="0.01" value="<?= $row['multiplier'] * 100 ?>">
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-outline-danger remove-fixed">–</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- ปุ่มเพิ่มแถว -->
                <button type="button" id="addFixed" class="btn btn-sm btn-outline-primary mb-3">
                    <i class="fa-solid fa-plus me-1"></i>เพิ่มเดือน
                </button>
            </fieldset>
            <!-- ปุ่มบันทึก -->
            <div class="text-end">
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-save me-1"></i>บันทึกสูตรที่ 1
                </button>
            </div>

        </form>
    </fieldset>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // ——— เก็บ/คืนค่าแท็บ ———
    const params = new URLSearchParams(window.location.search);
    if (!params.has('tab')) {
        const saved = localStorage.getItem('interestTab');
        if (saved) {
            window.location.search = 'tab=' + saved;
            return;
        }
    }
    document.querySelectorAll('.nav-link').forEach(a => {
        a.addEventListener('click', () => {
            const m = a.href.match(/tab=(\w+)/);
            if (m) localStorage.setItem('interestTab', m[1]);
        });
    });

    // ——— helper: ซิงก์ชื่อ checkbox ตามเดือน ———
    function syncNames(tr) {
        const m = tr.querySelector('.month-input').value.trim();
        const hid = tr.querySelector('input[type="hidden"]');
        const chk = tr.querySelector('input[type="checkbox"]');
        if (m) {
            hid.name = `fixed_enabled[${m}]`;
            chk.name = `fixed_enabled[${m}]`;
        } else {
            hid.removeAttribute('name');
            chk.removeAttribute('name');
        }
    }

    const tbody = document.getElementById('fixedBody');
    const addBtn = document.getElementById('addFixed');
    const form = document.getElementById('fixedForm');

    // เพิ่มแถวใหม่
    addBtn.addEventListener('click', () => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
      <td class="text-center">
        <input type="hidden" name="fixed_enabled[]" value="0">
        <input type="checkbox" name="fixed_enabled[]" value="1">
      </td>
      <td>
        <input type="number" name="fixed_month[]" class="form-control month-input" min="1" required>
      </td>
      <td>
        <input type="number" name="fixed_multiplier[]" class="form-control" step="0.01" required>
      </td>
      <td class="text-center">
        <button type="button" class="btn btn-sm btn-outline-danger remove-fixed">–</button>
      </td>
    `;
        // bind change event
        tr.querySelector('.month-input')
            .addEventListener('input', () => syncNames(tr));
        tbody.appendChild(tr);
    });

    // ลบแถว
    tbody.addEventListener('click', e => {
        if (e.target.closest('.remove-fixed')) {
            e.target.closest('tr').remove();
        }
    });

    // ก่อน submit ซิงก์ชื่อทุกแถว
    form.addEventListener('submit', () => {
        tbody.querySelectorAll('tr').forEach(tr => syncNames(tr));
    });
});

document.addEventListener('DOMContentLoaded', () => {
    const fixedActive = document.getElementById('fixedActive');
    const fixedSettings = document.getElementById('fixedSettings');

    function updateFixedSettings() {
        // เมื่อ checked ให้ enable fieldset, ไม่เช็คให้ disable
        fixedSettings.disabled = !fixedActive.checked;
    }

    // ผูก event แล้วก็รันครั้งแรก
    fixedActive.addEventListener('change', updateFixedSettings);
    updateFixedSettings();
});
</script>