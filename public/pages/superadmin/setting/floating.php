<?php
// File: public/pages/superadmin/setting/floating.php
// Partial สำหรับ “สูตรที่ 2: ดอกลอย (Floating Interest)”
// ต้องมีตัวแปรจาก interest_settings.php:
//   $errorFloat, $successFloat, $dbFloat, $currentFloatActive, $currentFloatInterval, $formulaLabels

$currentLabel = $formulaLabels['floating_interest'] ?? '';
?>
<div class="card mb-4 shadow-sm">
    <div class="card-header bg-light">
        <strong>สูตรที่ 2: ดอกลอย (Floating Interest)</strong>
    </div>

    <!-- ปุ่มสลับแสดง/ซ่อนคำอธิบายสูตรที่ 2 -->
    <div class="mb-3 mt-3">
        <button class="btn btn-sm btn-outline-info" type="button" data-bs-toggle="collapse" data-bs-target="#floatDesc"
            aria-expanded="false" aria-controls="floatDesc">
            <i class="fa-solid fa-info-circle text-info me-1"></i>คำอธิบายสูตรที่ 2
        </button>
    </div>

    <!-- คำอธิบาย -->
    <div class="collapse mb-4" id="floatDesc">
        <div class="card card-body bg-light">
            <p>
                <i class="fa-solid fa-water text-primary me-2"></i>
                ดอกเบี้ยแบบ <strong>Floating Interest</strong> คิดตามอัตรา (%) ต่อเดือน
                และตัดรอบชำระตามจำนวนวันที่กำหนด
            </p>
            <ul class="mb-0">
                <li>
                    <i class="fa-solid fa-calendar-days text-success me-2"></i>
                    <strong>ดอกเบี้ยต่อเดือน</strong> = เงินต้น × Rate
                </li>
                <li>
                    <i class="fa-solid fa-clock text-warning me-2"></i>
                    <strong>ดอกเบี้ยต่อรอบ</strong> = (เงินต้น × Rate) × (interval_days / 30)
                </li>
                <li>
                    <i class="fa-solid fa-percent text-danger me-2"></i>
                    <strong>ตัวอย่าง:</strong>
                    <ul>
                        <li><i class="fa-solid fa-circle text-secondary me-1"></i>
                            เงินต้น 5,000 บาท, Rate 20% → ดอกเบี้ยเดือน = 1,000 บาท
                        </li>
                        <li><i class="fa-solid fa-circle text-secondary me-1"></i>
                            interval_days = 15 → ดอกเบี้ยต่อรอบ = 1,000 × (15/30) = 500 บาท
                        </li>
                    </ul>
                </li>
                <li>
                    <i class="fa-solid fa-toggle-on text-info me-2"></i>
                    สามารถเลือกเปิด/ปิดสูตร และปรับจำนวนวันตัดรอบได้ตามต้องการ
                </li>
            </ul>
        </div>
    </div>

    <fieldset class="card-body">
        <?php if ($errorFloat): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($errorFloat) ?></div>
        <?php endif; ?>
        <?php if ($successFloat): ?>
        <div class="alert alert-success"><?= htmlspecialchars($successFloat) ?></div>
        <?php endif; ?>

        <form id="floatForm" method="post" class="needs-validation" novalidate>
            <input type="hidden" name="form_type" value="floating">

            <!-- ชื่อสูตร -->
            <div class="mb-4 row">
                <label class="col-sm-2 col-form-label">ชื่อสูตรค่าบริการ</label>
                <div class="col-sm-10">
                    <input type="text" name="float_label" class="form-control" required
                        value="<?= htmlspecialchars($currentLabel) ?>">
                    <div class="invalid-feedback">กรุณาระบุชื่อสูตร</div>
                </div>
            </div>

            <!-- Toggle เปิด/ปิดสูตร -->
            <div class="form-check mb-3">
                <input type="hidden" name="float_active" value="0">
                <input type="checkbox" id="floatActive" name="float_active" class="form-check-input" value="1"
                    data-bs-toggle="collapse" data-bs-target="#floatSettings"
                    aria-expanded="<?= $currentFloatActive ? 'true' : 'false' ?>" aria-controls="floatSettings"
                    <?= $currentFloatActive ? 'checked' : '' ?>>
                <label for="floatActive" class="form-check-label">
                    เปิดใช้งานสูตรที่ 2
                </label>
            </div>

            <!-- Settings ที่ถูกพับ/disabled เมื่อปิดสูตร -->
            <fieldset id="floatSettings" disabled class="collapse <?= $currentFloatActive ? 'show' : '' ?>">
                <!-- ความถี่การชำระ -->
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label">ความถี่การชำระ (วัน)</label>
                        <input type="number" name="float_interval" class="form-control" min="1" required
                            value="<?= $currentFloatInterval ?>">
                        <div class="invalid-feedback">กรุณาระบุจำนวนวัน</div>
                    </div>
                </div>

                <!-- ตารางเดือน ↔ Rate -->
                <div class="table-responsive mb-3">
                    <table class="table table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center" style="width:80px">✓ ใช้งาน</th>
                                <th style="width:120px">เดือน (n)</th>
                                <th>Rate (%)</th>
                                <th class="text-center" style="width:80px">ลบ</th>
                            </tr>
                        </thead>
                        <tbody id="floatBody">
                            <?php foreach ($dbFloat as $row): ?>
                            <tr>
                                <td class="text-center">
                                    <input type="hidden" name="float_enabled[<?= $row['months'] ?>]" value="0">
                                    <input type="checkbox" name="float_enabled[<?= $row['months'] ?>]" value="1"
                                        <?= $row['is_active'] ? 'checked' : '' ?>>
                                </td>
                                <td>
                                    <input type="number" name="float_month[]" class="form-control month-input" required
                                        min="1" value="<?= $row['months'] ?>">
                                </td>
                                <td>
                                    <input type="number" name="float_rate[]" class="form-control" required step="0.01"
                                        value="<?= $row['rate'] * 100 ?>">
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-outline-danger remove-float">–</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- ปุ่มเพิ่มแถว -->
                <button type="button" id="addFloat" class="btn btn-sm btn-outline-primary mb-3">
                    <i class="fa-solid fa-plus me-1"></i>เพิ่มเดือน
                </button>
            </fieldset>

            <!-- ปุ่มบันทึก -->
            <div class="text-end">
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-save me-1"></i>บันทึกสูตรที่ 2
                </button>
            </div>
        </form>
    </fieldset>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // เก็บ/คืนค่าแท็บ
    const params = new URLSearchParams(window.location.search);
    if (!params.has('tab')) {
        const saved = localStorage.getItem('interestTab');
        if (saved) return window.location.search = 'tab=' + saved;
    }
    document.querySelectorAll('.nav-link').forEach(a => {
        a.addEventListener('click', () => {
            const m = a.href.match(/tab=(\w+)/);
            if (m) localStorage.setItem('interestTab', m[1]);
        });
    });

    // Sync checkbox name ตามเดือน
    function syncNames(tr) {
        const m = tr.querySelector('.month-input').value.trim();
        const hid = tr.querySelector('input[type="hidden"]');
        const chk = tr.querySelector('input[type="checkbox"]');
        if (m) {
            hid.name = `float_enabled[${m}]`;
            chk.name = `float_enabled[${m}]`;
        } else {
            hid.removeAttribute('name');
            chk.removeAttribute('name');
        }
    }

    const tbody = document.getElementById('floatBody');
    const addBtn = document.getElementById('addFloat');
    const form = document.getElementById('floatForm');

    // เพิ่มแถวใหม่
    addBtn.addEventListener('click', () => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
          <td class="text-center">
            <input type="hidden" name="float_enabled[]" value="0">
            <input type="checkbox" name="float_enabled[]" value="1">
          </td>
          <td>
            <input type="number" name="float_month[]" class="form-control month-input" min="1" required>
          </td>
          <td>
            <input type="number" name="float_rate[]" class="form-control" step="0.01" required>
          </td>
          <td class="text-center">
            <button type="button" class="btn btn-sm btn-outline-danger remove-float">–</button>
          </td>
        `;
        tr.querySelector('.month-input').addEventListener('input', () => syncNames(tr));
        tbody.appendChild(tr);
    });

    // ลบแถว
    tbody.addEventListener('click', e => {
        if (e.target.closest('.remove-float')) {
            e.target.closest('tr').remove();
        }
    });

    // ก่อน submit ให้ sync ชื่อทุกแถว
    form.addEventListener('submit', () => {
        tbody.querySelectorAll('tr').forEach(tr => syncNames(tr));
    });

    // ปรับ enable/disable settings เมื่อ toggle สูตร
    const floatActive = document.getElementById('floatActive');
    const floatSettings = document.getElementById('floatSettings');

    function updateFloatSettings() {
        floatSettings.disabled = !floatActive.checked;
    }
    floatActive.addEventListener('change', updateFloatSettings);
    updateFloatSettings();
});
</script>