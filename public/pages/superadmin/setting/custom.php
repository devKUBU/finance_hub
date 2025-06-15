<?php
// File: public/pages/superadmin/setting/custom.php
// ต้องมีตัวแปรจาก interest_settings.php:
//   $errorCustom, $successCustom, $rowCustom, $dbCustomEntries, $currentCustomInterval, $currentCustomActive, $formulaLabels
  $currentLabel       = $formulaLabels['custom']     ?? '';
  $currentCustomActive= !empty($formulaActiveFlags['custom']);
?>
<div class="card mb-4 shadow-sm">
    <div class="card-header bg-light">
        <strong>สูตรที่ 3: กำหนดเอง (Custom Formula)</strong>
    </div>

    <div class="mb-3 mt-3">
        <button class="btn btn-sm btn-outline-info" type="button" data-bs-toggle="collapse" data-bs-target="#customDesc"
            aria-expanded="false" aria-controls="customDesc">
            <i class="fa-solid fa-info-circle me-1"></i>คำอธิบายสูตรที่ 3
        </button>
    </div>
    <div class="collapse mb-4" id="customDesc">
        <div class="card card-body bg-light">
            <!-- ... คำอธิบายเหมือนเดิม ... -->
        </div>
    </div>

    <fieldset class="card-body">
        <?php if ($errorCustom): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($errorCustom) ?></div>
        <?php endif; ?>
        <?php if ($successCustom): ?>
        <div class="alert alert-success"><?= htmlspecialchars($successCustom) ?></div>
        <?php endif; ?>

        <form id="customForm" method="post" class="needs-validation" novalidate>
            <input type="hidden" name="form_type" value="custom">

            <!-- ชื่อสูตร + Toggle -->
            <div class="row mb-4">
                <label class="col-sm-2 col-form-label">ชื่อสูตร</label>
                <div class="col-sm-10">
                    <input type="text" name="custom_name" class="form-control" required
                        value="<?= htmlspecialchars($currentLabel) ?>">
                    <div class="invalid-feedback">กรุณาระบุชื่อสูตร</div>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">คำอธิบายสูตร</label>
                <textarea name="custom_desc" class="form-control"
                    rows="2"><?= htmlspecialchars($rowCustom['description'] ?? '') ?></textarea>
            </div>

            <!-- Toggle เปิด/ปิดสูตร -->
            <div class="form-check mb-3">
                <input type="hidden" name="custom_active" value="0">
                <input type="checkbox" id="customActive" name="custom_active" class="form-check-input" value="1"
                    data-bs-toggle="collapse" data-bs-target="#customSettings"
                    aria-expanded="<?= $currentCustomActive?'true':'false'?>" aria-controls="customSettings"
                    <?= $currentCustomActive?'checked':''?>>
                <label for="customActive" class="form-check-label">
                    เปิดใช้งานสูตรที่ 3
                </label>
            </div>

            <fieldset id="customSettings" class="collapse <?= $currentCustomActive?'show':''?>"
                <?= $currentCustomActive?'':'disabled'?>>
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label">ความถี่การชำระ (วัน)</label>
                        <input type="number" name="custom_interval" class="form-control" required min="1"
                            value="<?= htmlspecialchars($currentCustomInterval) ?>">
                        <div class="invalid-feedback">กรุณาระบุจำนวนวัน</div>
                    </div>
                </div>

                <div class="table-responsive mb-3">
                    <table class="table table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center" style="width:80px">✓ ใช้งาน</th>
                                <th style="width:120px">เดือน (n)</th>
                                <th>ค่า (%)</th>
                                <th class="text-center" style="width:80px">ลบ</th>
                            </tr>
                        </thead>
                        <tbody id="customBody">
                            <?php foreach ($dbCustomEntries as $r): ?>
                            <tr>
                                <td class="text-center">
                                    <input type="hidden" name="custom_enabled[<?= $r['months'] ?>]" value="0">
                                    <input type="checkbox" name="custom_enabled[<?= $r['months'] ?>]" value="1" checked>
                                </td>
                                <td>
                                    <input type="number" name="custom_month[]" class="form-control month-input" min="1"
                                        required value="<?= $r['months'] ?>">
                                </td>
                                <td>
                                    <input type="number" name="custom_value[]" class="form-control" step="0.01" required
                                        value="<?= $r['value'] * 100 ?>">
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-outline-danger remove-custom">–</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <button type="button" id="addCustom" class="btn btn-sm btn-outline-primary mb-3">
                    <i class="fa-solid fa-plus me-1"></i>เพิ่มเดือน
                </button>
            </fieldset>

            <div class="text-end">
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-save me-1"></i>บันทึกสูตรที่ 3
                </button>
            </div>
        </form>
    </fieldset>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // ซิงก์ collapse + disable fieldset
    const activeChk = document.getElementById('customActive');
    const settings = document.getElementById('customSettings');

    function toggleSettings() {
        settings.disabled = !activeChk.checked;
    }
    activeChk.addEventListener('change', toggleSettings);
    toggleSettings();

    // ฟังก์ชันซิงก์ชื่อ checkbox ตามค่าเดือน
    function syncNames(tr) {
        const m = tr.querySelector('.month-input').value.trim();
        const hid = tr.querySelector('input[type="hidden"]');
        const chk = tr.querySelector('input[type="checkbox"]');
        if (m) {
            hid.name = `custom_enabled[${m}]`;
            chk.name = `custom_enabled[${m}]`;
        } else {
            hid.removeAttribute('name');
            chk.removeAttribute('name');
        }
    }

    const tbody = document.getElementById('customBody');
    const addBtn = document.getElementById('addCustom');
    const form = document.getElementById('customForm');

    // เพิ่มแถวใหม่
    addBtn.addEventListener('click', () => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
      <td class="text-center">
        <input type="hidden" name="custom_enabled[]" value="0">
        <input type="checkbox" name="custom_enabled[]" value="1">
      </td>
      <td>
        <input type="number" name="custom_month[]" class="form-control month-input" min="1" required>
      </td>
      <td>
        <input type="number" name="custom_value[]" class="form-control" step="0.01" required>
      </td>
      <td class="text-center">
        <button type="button" class="btn btn-sm btn-outline-danger remove-custom">–</button>
      </td>
    `;
        tr.querySelector('.month-input')
            .addEventListener('input', () => syncNames(tr));
        tbody.appendChild(tr);
    });

    // ลบแถว
    tbody.addEventListener('click', e => {
        if (e.target.closest('.remove-custom')) {
            e.target.closest('tr').remove();
        }
    });

    // ก่อน submit: ซิงก์ชื่อทุกแถว
    form.addEventListener('submit', () => {
        tbody.querySelectorAll('tr').forEach(tr => syncNames(tr));
    });
});

document.addEventListener('DOMContentLoaded', () => {
    const chk = document.getElementById('customActive');
    const fs = document.getElementById('customSettings');

    function toggleFs() {
        fs.disabled = !chk.checked;
    }
    chk.addEventListener('change', toggleFs);
    toggleFs();
});
</script>