<?php
// File: public/pages/superadmin/contracts/view-contract-admin.php
date_default_timezone_set('Asia/Bangkok');

/* ------------------------------------------------------------------
   1) LOAD BOOTSTRAP & HELPERS
------------------------------------------------------------------ */
require_once realpath(__DIR__ . '/../../../../config/bootstrap.php');
require_once ROOT_PATH . '/includes/helpers.php';
if (session_status() === PHP_SESSION_NONE) session_start();
requireRole(['superadmin', 'admin']);


/* ------------------------------------------------------------------
   2) CONNECT DB
------------------------------------------------------------------ */
require_once ROOT_PATH . '/config/db.php';
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

/* ------------------------------------------------------------------
   FETCH MANUAL INTEREST FORMULAS
------------------------------------------------------------------ */
$intervalManual = (int)$pdo->query("
    SELECT interval_days
      FROM payment_frequency
     WHERE formula='manual' AND is_active=1
")->fetchColumn() ?: 15;

$rawManual = $pdo->query("
    SELECT g.group_id, g.group_name, g.principal,
           e.month_idx, e.repayment
      FROM manual_interest_formulas f
      JOIN manual_interest_groups   g ON f.formula_id = g.formula_id
 LEFT JOIN manual_interest_entries e ON g.group_id = e.group_id
     WHERE f.is_active = 1
  ORDER BY g.group_id, e.month_idx
")->fetchAll(PDO::FETCH_ASSOC);

$manualGroups = [];
foreach ($rawManual as $row) {
    $gid = $row['group_id'];
    if (!isset($manualGroups[$gid])) {
        $manualGroups[$gid] = [
            'group_id'   => $gid,
            'group_name' => $row['group_name'],
            'principal'  => $row['principal'],
            'entries'    => []
        ];
    }
    if ($row['month_idx'] !== null) {
        $manualGroups[$gid]['entries'][] = [
            'month_idx' => $row['month_idx'],
            'repayment' => $row['repayment']
        ];
    }
}
$manualGroups = array_values($manualGroups);

/* ------------------------------------------------------------------
   3) FETCH CONTRACT TO VIEW / EDIT (SUPERADMIN)
------------------------------------------------------------------ */
$contractId = (int)($_GET['id'] ?? 0);
if ($contractId <= 0) {
    setFlash('invalid_contract', 'ผิดพลาด: ไม่พบรหัสสัญญา');
    header('Location: list-contracts-admin.php');
    exit;
}

$stmt = $pdo->prepare("
    SELECT c.*, u.name AS shop_name
      FROM contracts c
      JOIN users u ON u.id = c.shop_id
     WHERE c.id = ?
    LIMIT 1
");
$stmt->execute([$contractId]);
$contract = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$contract) {
    setFlash('not_found', 'ไม่พบสัญญานี้');
    header('Location: list-contracts-admin.php');
    exit;
}

// หากสัญญาอนุมัติแล้ว ก็ Lock ไม่ให้แก้ไข
$isApproved = ($contract['approval_status'] === 'approved');

// เตรียมค่า reject_reason เพื่อแสดงถ้ามี
$rejectReason = $contract['reject_reason'] ?? '';

/* ------------------------------------------------------------------
   4) LOAD INTEREST FORMULAS (เหมือน new-contract.php)
------------------------------------------------------------------ */
// Fixed
$fixedMultipliers = $pdo->query("
    SELECT months, multiplier
      FROM fixed_interest_multipliers
     WHERE is_active = 1
  ORDER BY months
")->fetchAll(PDO::FETCH_KEY_PAIR);
$intervalFixed = (int)$pdo->query("
    SELECT interval_days
      FROM payment_frequency
     WHERE formula = 'fixed' AND is_active = 1
")->fetchColumn() ?: 15;

// Floating
$floatingSettings = $pdo->query("
    SELECT months, rate
      FROM floating_interest_settings
     WHERE is_active = 1
  ORDER BY months
")->fetchAll(PDO::FETCH_KEY_PAIR);
$intervalFloating = (int)$pdo->query("
    SELECT interval_days
      FROM payment_frequency
     WHERE formula = 'floating' AND is_active = 1
")->fetchColumn() ?: 15;

// Custom
$rowCustom      = $pdo->query("
    SELECT interval_days
      FROM payment_frequency
     WHERE formula = 'custom' AND is_active = 1
")->fetch(PDO::FETCH_ASSOC);
$intervalCustom = (int)($rowCustom['interval_days'] ?? 15);
$customEntries  = $pdo->query("
    SELECT months, value
      FROM custom_interest_entries
     WHERE formula_id = 1
  ORDER BY months
")->fetchAll(PDO::FETCH_ASSOC);

// Manual
$rowManual      = $pdo->query("
    SELECT interval_days
      FROM payment_frequency
     WHERE formula = 'manual' AND is_active = 1
")->fetch(PDO::FETCH_ASSOC);
$intervalManual = (int)($rowManual['interval_days'] ?? 15);
$rawManual      = $pdo->query("
    SELECT g.group_id, g.group_name, g.principal,
           e.month_idx, e.repayment
      FROM manual_interest_formulas f
      JOIN manual_interest_groups   g ON f.formula_id = g.formula_id
 LEFT JOIN manual_interest_entries e ON g.group_id = e.group_id
     WHERE f.is_active = 1
  ORDER BY g.group_id, e.month_idx
")->fetchAll(PDO::FETCH_ASSOC);
// reshape
$manualGroups = [];
foreach ($rawManual as $row) {
    $gid = $row['group_id'];
    if (!isset($manualGroups[$gid])) {
        $manualGroups[$gid] = [
            'group_id'   => $gid,
            'group_name' => $row['group_name'],
            'principal'  => $row['principal'],
            'entries'    => []
        ];
    }
    if ($row['month_idx'] !== null) {
        $manualGroups[$gid]['entries'][] = [
            'month_idx' => $row['month_idx'],
            'repayment' => $row['repayment']
        ];
    }
}
$manualGroups = array_values($manualGroups);

/* ------------------------------------------------------------------
   5) INITIALISE FORM VARIABLES (POST or DB)
------------------------------------------------------------------ */
$errors = [];

// Lock settings
$lockType       = $_POST['lock_type']      ?? $contract['lock_type'];
$mdmReference   = $_POST['mdm_reference']  ?? $contract['mdm_reference'];
$icloudEmail    = $_POST['icloud_email']   ?? $contract['icloud_email'];
$icloudPassword = $_POST['icloud_password']?? $contract['icloud_password'];
$icloudPin      = $_POST['icloud_pin']     ?? $contract['icloud_pin'];

// Start date → HTML5 datetime-local
$startDateRaw = $_POST['start_date'] ?? $contract['start_date'];
$startDate    = date('Y-m-d\TH:i', strtotime($startDateRaw));

// Birth date (dd/mm/YYYY พ.ศ.)
$birthDateInput = trim($_POST['birth_date'] ?? '');
if ($birthDateInput === '' && !empty($contract['customer_birth_date'])) {
    $d  = new DateTime($contract['customer_birth_date']);
    $gy = (int)$d->format('Y');
    $by = $gy + 543;
    $birthDateInput = $d->format('d/m/') . $by;
}

// Interest, amount, period
$interestType  = $_POST['interest_type']   ?? $contract['contract_type'];
$loanAmount    = isset($_POST['loan_amount'])   ? (float)$_POST['loan_amount']   : (float)$contract['loan_amount'];
$periodMonths  = isset($_POST['period_months']) ? (int)$_POST['period_months']   : (int)$contract['period_months'];
$manualGroupId = isset($_POST['manual_group'])  ? (int)$_POST['manual_group']    : (int)$contract['manual_group_id'];

// Customer & device fields
$firstName      = $_POST['customer_firstname'] ?? $contract['customer_firstname'];
$lastName       = $_POST['customer_lastname']  ?? $contract['customer_lastname'];
$phone          = $_POST['customer_phone']     ?? $contract['customer_phone'];
$lineId         = $_POST['customer_line']      ?? $contract['customer_line'];
$facebook       = $_POST['customer_facebook']  ?? $contract['customer_facebook'];
$provinceId     = isset($_POST['province'])    ? (int)$_POST['province']   : (int)$contract['province_id'];
$amphurId       = isset($_POST['amphur'])      ? (int)$_POST['amphur']     : (int)$contract['amphur_id'];
$districtId     = isset($_POST['district'])    ? (int)$_POST['district']   : (int)$contract['district_id'];
$postalCode     = $_POST['postal_code']        ?? $contract['postal_code'];
$houseNumber    = $_POST['house_number']       ?? $contract['house_number'];
$moo            = $_POST['moo']                ?? $contract['moo'];
$soi            = $_POST['soi']                ?? $contract['soi'];
$otherAddress   = $_POST['other_address']      ?? $contract['other_address'];
$deviceBrand    = $_POST['device_brand']       ?? $contract['device_brand'];
$deviceModel    = $_POST['device_model']       ?? $contract['device_model'];
$deviceCapacity = $_POST['device_capacity']    ?? $contract['device_capacity'];
$deviceColor    = $_POST['device_color']       ?? $contract['device_color'];
$deviceIMEI     = $_POST['device_imei']        ?? $contract['device_imei'];
$deviceSerial   = $_POST['device_serial']      ?? $contract['device_serial_no'];

/* ------------------------------------------------------------------
   FETCH DEVICE MODELS FOR DROPDOWN
------------------------------------------------------------------ */
$allModels = $pdo->query("
    SELECT brand, model_name
      FROM device_models
  ORDER BY brand, model_name
")->fetchAll(PDO::FETCH_ASSOC);
$modelsByBrand = [];
foreach ($allModels as $r) {
    $modelsByBrand[$r['brand']][] = $r['model_name'];
}
$brands = array_keys($modelsByBrand);

/* ------------------------------------------------------------------
   6) HANDLE SUBMIT (VALIDATE + CALCULATE + UPDATE)
------------------------------------------------------------------ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$isApproved) {

    // --- Birth date validation (dd/mm/YYYY พ.ศ.) ---
    $birthDateInput = trim($_POST['birth_date'] ?? '');
    if ($birthDateInput === '' && !empty($contract['customer_birth_date'])) {
        $d  = new DateTime($contract['customer_birth_date']);
        $gy = (int)$d->format('Y');
        $by = $gy + 543;
        $birthDateInput = $d->format('d/m/') . $by;
    }
    if ($birthDateInput === '') {
        $errors[] = 'กรุณาเลือกวันเกิด';
    } elseif (preg_match('#^(\d{1,2})/(\d{1,2})/(\d{4})$#', $birthDateInput, $m)) {
        list(, $dd, $mm, $by) = $m;
        $gy = (int)$by - 543;
        if (checkdate($mm, $dd, $gy)) {
            $birthDate = sprintf('%04d-%02d-%02d', $gy, $mm, $dd);
        } else {
            $errors[] = 'รูปแบบวันเกิดไม่ถูกต้อง';
        }
    } else {
        $errors[] = 'รูปแบบวันเกิดต้องเป็น dd/mm/YYYY';
    }

    // --- Lock settings validation ---
    if ($lockType === 'mdm') {
        if (trim($mdmReference) === '') {
            $errors[] = 'กรุณากรอก reference MDM';
        }
    } elseif ($lockType === 'icloud') {
        if (!filter_var($icloudEmail, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'รูปแบบ iCloud Email ไม่ถูกต้อง';
        }
        if (!preg_match('/^\d{4}$/', $icloudPin)) {
            $errors[] = 'Pincode ต้องเป็นตัวเลข 4 หลัก';
        }
    }

    // --- Other field validations ---
    if ($firstName === '')     $errors[] = 'กรุณากรอกชื่อผู้กู้';
    if ($lastName === '')      $errors[] = 'กรุณากรอกนามสกุลผู้กู้';
    if ($phone === '')         $errors[] = 'กรุณากรอกเบอร์โทรศัพท์';
    if ($loanAmount <= 0)      $errors[] = 'จำนวนเงินต้องมากกว่า 0';
    // if ($periodMonths <= 0)    $errors[] = 'กรุณาระบุจำนวนเดือน';
if ($interestType !== 'manual' && $periodMonths <= 0) {
    $errors[] = 'กรุณาเลือกจำนวนเดือน';
}
    if ($startDateRaw === '')  $errors[] = 'กรุณาระบุวันเริ่มสัญญา';
    if ($deviceBrand === '')   $errors[] = 'กรุณาเลือกยี่ห้ออุปกรณ์';
    if ($deviceModel === '')   $errors[] = 'กรุณาเลือกรุ่นอุปกรณ์';
    if ($deviceCapacity === '')$errors[] = 'กรุณาระบุความจุ';
    if (strlen($deviceIMEI) !== 15)   $errors[] = 'IMEI ต้องเป็นตัวเลข 15 หลัก';
    if (strlen($deviceSerial) !== 10) $errors[] = 'Serial ต้องเป็นตัวอักษร/ตัวเลข 10 หลัก';

    if (empty($errors)) {
        try {
            // แปลง startDateRaw → SQL format
            $startDateSql = str_replace('T', ' ', $startDateRaw);
            // default endDate for non-manual
            $endDate = date('Y-m-d', strtotime("+{$periodMonths} months", strtotime($startDateSql)));

            // เริ่มคำนวณงวดผ่อน
            $intervalDays  = 0;
            $installments  = [];
            switch ($interestType) {
                case 'floating_interest':
                    if (!isset($floatingSettings[$periodMonths])) {
                        throw new Exception("ไม่มีการตั้งดอกลอยสำหรับ {$periodMonths} เดือน");
                    }
                    $intervalDays = $intervalFloating;
                    $rate         = $floatingSettings[$periodMonths];
                    $num          = (int)ceil(($periodMonths * 30) / $intervalDays);
                    $amt          = round($loanAmount * $rate * ($intervalDays / 30), 2);
                    $installments = array_fill(0, $num, $amt);
                    break;

                case 'fixed_interest':
                    if (!isset($fixedMultipliers[$periodMonths])) {
                        throw new Exception("ไม่มีการตั้งจบดอกสำหรับ {$periodMonths} เดือน");
                    }
                    $intervalDays = $intervalFixed;
                    $mult         = $fixedMultipliers[$periodMonths];
                    $total        = $loanAmount * $mult;
                    $num          = (int)ceil(($periodMonths * 30) / $intervalDays);
                    $installments = array_fill(0, $num, round($total / $num, 2));
                    break;

                case 'custom':
                    $intervalDays = $intervalCustom;
                    $rows         = array_filter($customEntries, fn($r) => $r['months'] <= $periodMonths);
                    if (!$rows) {
                        throw new Exception("ไม่มีการตั้งค่า Custom สำหรับ {$periodMonths} เดือน");
                    }
                    foreach ($rows as $r) {
                        $installments[] = round($loanAmount * $r['value'], 2);
                    }
                    break;

                case 'manual':
                    $intervalDays = $intervalManual;
                    $group = null;
                    foreach ($manualGroups as $g) {
                        if ($g['group_id'] == $manualGroupId) {
                            $group = $g;
                            break;
                        }
                    }
                    if (!$group || !$group['entries']) {
                        throw new Exception('ไม่พบกลุ่ม Manual หรือยังไม่ได้ตั้งค่างวดผ่อน');
                    }
                    usort($group['entries'], fn($a, $b) => $a['month_idx'] <=> $b['month_idx']);
                    foreach ($group['entries'] as $e) {
                        $installments[] = (float)$e['repayment'];
                    }
                    // Override loanAmount, periodMonths, endDate ให้ตรงกับ Manual
                    $loanAmount   = (float)$group['principal'];
                    $periodMonths = count($group['entries']);
                    $lastIdx      = end($group['entries'])['month_idx'];
                    $endDate      = date(
                        'Y-m-d',
                        strtotime("+" . ($lastIdx * $intervalDays) . " days", strtotime($startDateSql))
                    );
                    break;
            }

            /* --- UPDATE CONTRACT --- */
            $upd = $pdo->prepare("
                UPDATE contracts SET
                    customer_firstname     = ?,
                    customer_lastname      = ?,
                    customer_birth_date    = ?,
                    customer_phone         = ?,
                    customer_line          = ?,
                    customer_facebook      = ?,
                    loan_amount            = ?,
                    installment_amount     = ?,
                    contract_type          = ?,
                    period_months          = ?,
                    start_date             = ?,
                    end_date               = ?,
                    manual_group_id        = ?,
                    device_brand           = ?,
                    device_model           = ?,
                    device_capacity        = ?,
                    device_color           = ?,
                    device_imei            = ?,
                    device_serial_no       = ?,
                    lock_type              = ?,
                    mdm_reference          = ?,
                    icloud_email           = ?,
                    icloud_password        = ?,
                    icloud_pin             = ?,
                    contract_update_at             = NOW()
                WHERE id = ?
            ");
            $upd->execute([
                $firstName,
                $lastName,
                $birthDate,    // แปลงเป็น YYYY-MM-DD แล้ว
                $phone,
                $lineId,
                $facebook,
                $loanAmount,
                $installments[0],
                $interestType,
                $periodMonths,
                $startDateSql,
                $endDate,
                $interestType === 'manual' ? $manualGroupId : null,
                $deviceBrand,
                $deviceModel,
                $deviceCapacity,
                $deviceColor,
                $deviceIMEI,
                $deviceSerial,
                $lockType ?: null,
                $mdmReference ?: null,
                $icloudEmail ?: null,
                $icloudPassword ?: null,
                $icloudPin ?: null,
                $contractId
            ]);

            /* --- RESET PAYMENTS TABLE --- */
            $pdo->prepare("DELETE FROM payments WHERE contract_id = ?")->execute([$contractId]);
            foreach ($installments as $idx => $amt) {
                $due = date(
                    'Y-m-d',
                    strtotime("+" . (($idx + 1) * $intervalDays) . " days", strtotime($startDateSql))
                );
                $pdo->prepare("
                    INSERT INTO payments (contract_id, pay_no, due_date, amount_due, status)
                    VALUES (?, ?, ?, ?, 'pending')
                ")->execute([$contractId, $idx + 1, $due, $amt]);
            }

            setFlash('success', 'บันทึกการแก้ไขสัญญาเรียบร้อยแล้ว');
            header("Location: view-contract-admin.php?id={$contractId}");
            exit;
        } catch (Exception $ex) {
            $errors[] = 'เกิดข้อผิดพลาด: ' . $ex->getMessage();
        }
    }
}

/* ------------------------------------------------------------------
   7) RENDER PAGE (HTML + JS)
------------------------------------------------------------------ */
$pageTitle = 'ดู/แก้ไข สัญญา: ' . htmlspecialchars($contract['contract_no_shop']);
include ROOT_PATH . '/public/includes/header.php';
include ROOT_PATH . '/public/includes/sidebar.php';
?>
<link rel="stylesheet" href="<?= htmlspecialchars($baseURL) ?>/assets/css/dashboard.css">
<style>
.card-header {
    font-size: larger;
    padding: 1.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
}
</style>

<main class="main-content">
    <div class="container-fluid py-4">
        <?php displayFlash(); ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="m-0">
                <i class="fa-solid fa-eye me-2"></i><?= htmlspecialchars($pageTitle) ?>
            </h2>
            <!-- กลุ่มปุ่มทางขวา (Sidebar Toggle + Theme Toggle) -->
            <div class="header-actions d-flex align-items-center">
                <button id="sidebarToggle" class="btn-icon" aria-label="Toggle sidebar">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <button id="themeToggle" class="btn-icon ms-2" aria-label="Toggle theme">
                    <i id="themeIcon" class="fa-solid"></i>
                </button>
            </div>
        </div>
        <hr>

        <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $e): ?>
                <li><?= htmlspecialchars($e) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <form id="editContractForm" method="post" class="needs-validation" novalidate>
            <?php if ($contract['approval_status'] === 'rejected'): ?>
            <div class="alert alert-warning">
                สัญญานี้ถูกปฏิเสธด้วยเหตุผล: <strong><?= htmlspecialchars($rejectReason) ?></strong><br>
                กรุณาแก้ไขและกด “บันทึก” เพื่อส่งกลับมาพิจารณาใหม่
            </div>
            <?php endif; ?>
            <!-- เลขที่สัญญา & ร้านค้า & สถานะ -->
            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label class="form-label">เลขที่สัญญา</label>
                    <input type="text" class="form-control"
                        value="<?= htmlspecialchars($contract['contract_no_shop']) ?>" readonly>
                </div>
                <div class="col-md-4">
                    <label class="form-label">ร้านค้า</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($contract['shop_name']) ?>"
                        readonly>
                </div>
                <div class="col-md-2 d-flex flex-row flex-column justify-content-center">
                    <label
                        class="form-label mb-1 d-flex flex-row flex-column justify-content-center">สถานะอนุมัติ</label>
                    <?php
                        $approvalMap = [
                            'pending'  => ['รอการอนุมัติ', 'badge bg-warning text-dark'],
                            'approved' => ['อนุมัติแล้ว', 'badge bg-success'],
                            'rejected' => ['ไม่อนุมัติ', 'badge bg-danger'],
                        ];
                        [$aText, $aCls] = $approvalMap[$contract['approval_status']];
                    ?>
                    <span class="<?= htmlspecialchars($aCls) ?>">
                        <?= htmlspecialchars($aText) ?>
                    </span>
                </div>

            </div>

            <!-- 1. การคำนวณดอกเบี้ย -->
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <strong><i class="fa-solid fa-calculator me-2"></i>1. การคำนวณอัตราค่าบริการ</strong>
                </div>
                <div class="card-body row g-3">
                    <!-- สูตรดอกเบี้ย -->
                    <div class="col-md-4">
                        <label class="form-label">
                            <i class="fa-solid fa-percent me-1"></i>
                            สูตรค่าบริการ<span class="text-danger">*</span>
                        </label>
                        <select id="interest_type" name="interest_type" class="form-select"
                            <?= $isApproved ? 'disabled' : '' ?>>
                            <option value="">-- เลือกสูตร --</option>
                            <?php
                            $formulaLabels = $pdo
                                ->query("SELECT formula_key, display_name FROM service_fee_formulas WHERE is_active=1")
                                ->fetchAll(PDO::FETCH_KEY_PAIR);

                            $order = ['fixed_interest','floating_interest','custom','manual'];
                            foreach ($order as $key):
                                if (!isset($formulaLabels[$key])) continue;
                                $show = match($key) {
                                    'fixed_interest'    => (bool)$fixedMultipliers,
                                    'floating_interest' => (bool)$floatingSettings,
                                    'custom'            => (bool)$customEntries,
                                    'manual'            => (bool)$manualGroups,
                                };
                                if (!$show) continue;
                            ?>
                            <option value="<?= $key ?>" <?= $interestType === $key ? 'selected' : '' ?>>
                                <?= htmlspecialchars($formulaLabels[$key]) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback">กรุณาเลือกสูตรค่าบริการ</div>
                    </div>

                    <!-- จำนวนเงิน (ปกติ) -->
                    <div class="col-md-4" id="col_loan">
                        <label class="form-label">
                            <i class="fa-solid fa-money-bill-wave me-1"></i>จำนวนเงิน (บาท)<span
                                class="text-danger">*</span>
                        </label>
                        <input id="loan_amount" name="loan_amount" type="number" step="0.01" class="form-control"
                            required value="<?= htmlspecialchars($loanAmount) ?>" <?= $isApproved ? 'readonly' : '' ?>>
                        <div class="invalid-feedback">กรุณากรอกจำนวนเงิน</div>
                    </div>

                    <!-- เลือกกลุ่ม Manual -->
                    <div class="col-md-4 d-none" id="col_manual_group">
                        <label class="form-label">
                            <i class="fa-solid fa-users me-1"></i>เลือกยอดเงินต้น (Manual)<span
                                class="text-danger">*</span>
                        </label>
                        <select id="manual_group" name="manual_group" class="form-select"
                            <?= $isApproved ? 'disabled' : '' ?>>
                            <option value="">-- เลือกยอดเงินต้น --</option>
                            <?php foreach ($manualGroups as $g): ?>
                            <option value="<?= $g['group_id'] ?>"
                                <?= $manualGroupId == $g['group_id'] ? 'selected' : '' ?>>
                                <?= number_format($g['principal'], 2) ?> บาท (<?= htmlspecialchars($g['group_name']) ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback">กรุณาเลือกยอดเงินต้น</div>
                    </div>

                    <?php
                    // Debug: แสดงข้อมูล Manual Groups ใน Console
                    echo "<script>";
                    echo "console.log('=== PHP DEBUG INFO ===');";
                    echo "console.log('Total manualGroups from PHP:', " . count($manualGroups) . ");";
                    echo "console.log('Manual Groups Data:', " . json_encode($manualGroups, JSON_UNESCAPED_UNICODE) . ");";
                    echo "console.log('Manual Interval:', " . $intervalManual . ");";
                    foreach ($manualGroups as $index => $group) {
                        echo "console.log('Group " . $index . ":', " . json_encode($group, JSON_UNESCAPED_UNICODE) . ");";
                    }
                    echo "</script>";
                    ?>

                    <!-- แสดง preview เมื่อเลือก Manual -->
                    <div class="col-12 mb-3 d-none" id="manualInfo">
                        <p class="mb-1">
                            ยอดเงินต้น: <strong id="manualPrincipalValue">0.00</strong> บาท
                        </p>
                        <p class="mb-0">
                            ผ่อนทุก <strong id="manualIntervalDisplay">0</strong> วัน
                        </p>
                    </div>

                    <!-- จำนวนเดือน -->
                    <div class="col-md-4" id="col_months">
                        <label class="form-label">
                            <i class="fa-solid fa-calendar-alt me-1"></i>จำนวนเดือน<span class="text-danger">*</span>
                        </label>
                        <select id="period_months" name="period_months" class="form-select"
                            <?= $isApproved ? 'disabled' : '' ?>>
                            <option value="">-- เลือกเดือน --</option>
                        </select>
                        <div class="invalid-feedback">กรุณาเลือกจำนวนเดือน</div>
                    </div>

                    <!-- ยอดผ่อนต่อตอน -->
                    <div class="col-12">
                        <label id="installment-label" class="form-label">
                            <i class="fa-solid fa-receipt me-1"></i>ยอดผ่อนต่อตอน (บาท)
                        </label>
                        <input id="computed_installment" type="text" class="form-control mb-2" readonly>
                    </div>

                    <!-- ตารางผ่อนชำระ -->
                    <div class="col-12 mt-3">
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-center"><i class="fa-solid fa-hashtag me-1"></i>งวด</th>
                                        <th class="text-center"><i class="fa-solid fa-calendar-day me-1"></i>ครบกำหนด
                                        </th>
                                        <th class="text-end"><i class="fa-solid fa-coins me-1"></i>ยอดผ่อน (บาท)</th>
                                    </tr>
                                </thead>
                                <tbody id="scheduleBody"></tbody>
                            </table>
                        </div>
                    </div>

                    <!-- วันที่เริ่มสัญญา -->
                    <div class="col-md-6">
                        <label class="form-label"><i class="fa-solid fa-clock me-1"></i>วันที่เริ่มสัญญา<span
                                class="text-danger">*</span></label>
                        <input name="start_date" type="datetime-local" class="form-control" required
                            value="<?= htmlspecialchars($startDate) ?>" <?= $isApproved ? 'readonly' : '' ?>>
                        <div class="invalid-feedback">กรุณาระบุวันและเวลา</div>
                    </div>
                </div>
            </div>

            <!-- 2. ข้อมูลลูกค้า -->
            <div class="card mb-4">
                <div class="card-header bg-light"><strong>2. ข้อมูลลูกค้า</strong></div>
                <div class="card-body row g-3">
                    <div class="col-md-6">
                        <label class="form-label">ชื่อผู้กู้ <span class="text-danger">*</span></label>
                        <input name="customer_firstname" type="text" class="form-control" required
                            value="<?= htmlspecialchars($firstName) ?>" <?= $isApproved ? 'readonly' : '' ?>>
                        <div class="invalid-feedback">กรุณากรอกชื่อผู้กู้</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">นามสกุลผู้กู้ <span class="text-danger">*</span></label>
                        <input name="customer_lastname" type="text" class="form-control" required
                            value="<?= htmlspecialchars($lastName) ?>" <?= $isApproved ? 'readonly' : '' ?>>
                        <div class="invalid-feedback">กรุณากรอกนามสกุลผู้กู้</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">เบอร์โทรศัพท์ <span class="text-danger">*</span></label>
                        <input name="customer_phone" type="text" class="form-control" required
                            value="<?= htmlspecialchars($phone) ?>" <?= $isApproved ? 'readonly' : '' ?>>
                        <div class="invalid-feedback">กรุณากรอกเบอร์โทรศัพท์</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">วันเกิด <span class="text-danger">*</span></label>
                        <input name="birth_date" id="birth_date" type="text" class="form-control"
                            placeholder="dd/mm/YYYY" required value="<?= htmlspecialchars($birthDateInput) ?>"
                            <?= $isApproved ? 'readonly' : '' ?>>
                        <div class="invalid-feedback">กรุณาเลือกวันเกิด</div>
                    </div>
                </div>
            </div>

            <!-- 3. ช่องทางการติดต่อ -->
            <div class="card mb-4">
                <div class="card-header bg-light"><strong>3. ช่องทางการติดต่อ</strong></div>
                <div class="card-body row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Line ID</label>
                        <input name="customer_line" class="form-control" value="<?= htmlspecialchars($lineId) ?>"
                            <?= $isApproved ? 'readonly' : '' ?>>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Facebook</label>
                        <input name="customer_facebook" class="form-control" value="<?= htmlspecialchars($facebook) ?>"
                            <?= $isApproved ? 'readonly' : '' ?>>
                    </div>
                </div>
            </div>

            <!-- 4. ที่อยู่ -->
            <div class="card mb-4">
                <div class="card-header bg-light"><strong>4. ที่อยู่</strong></div>
                <div class="card-body row g-3">
                    <div class="col-md-3">
                        <label class="form-label">บ้านเลขที่ <span class="text-danger">*</span></label>
                        <input name="house_number" class="form-control" required
                            value="<?= htmlspecialchars($houseNumber) ?>" <?= $isApproved ? 'readonly' : '' ?>>
                        <div class="invalid-feedback">กรุณากรอกบ้านเลขที่</div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">หมู่ที่</label>
                        <input name="moo" class="form-control" value="<?= htmlspecialchars($moo) ?>"
                            <?= $isApproved ? 'readonly' : '' ?>>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">ซอย/ตรอก</label>
                        <input name="soi" class="form-control" value="<?= htmlspecialchars($soi) ?>"
                            <?= $isApproved ? 'readonly' : '' ?>>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">อื่นๆ</label>
                        <input name="other_address" class="form-control" value="<?= htmlspecialchars($otherAddress) ?>"
                            <?= $isApproved ? 'readonly' : '' ?>>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">จังหวัด <span class="text-danger">*</span></label>
                        <select id="province" name="province" class="form-select" required
                            <?= $isApproved ? 'disabled' : '' ?>>
                            <option value="">-- เลือกจังหวัด --</option>
                        </select>
                        <div class="invalid-feedback">กรุณาเลือกจังหวัด</div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">อำเภอ/เขต <span class="text-danger">*</span></label>
                        <select id="amphur" name="amphur" class="form-select" required
                            <?= $isApproved ? 'disabled' : '' ?>>
                            <option value="">-- เลือกอำเภอ/เขต --</option>
                        </select>
                        <div class="invalid-feedback">กรุณาเลือกอำเภอ/เขต</div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">ตำบล/แขวง <span class="text-danger">*</span></label>
                        <select id="district" name="district" class="form-select" required
                            <?= $isApproved ? 'disabled' : '' ?>>
                            <option value="">-- เลือกตำบล/แขวง --</option>
                        </select>
                        <div class="invalid-feedback">กรุณาเลือกตำบล/แขวง</div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">รหัสไปรษณีย์</label>
                        <input id="postal_code" name="postal_code" class="form-control" readonly
                            value="<?= htmlspecialchars($postalCode) ?>" required>
                    </div>
                </div>
            </div>

            <!-- 5. ข้อมูลตัวเครื่อง -->
            <div class="card mb-4">
                <div class="card-header bg-light"><strong>5. ข้อมูลตัวเครื่อง</strong></div>
                <div class="card-body row g-3">
                    <div class="col-md-3">
                        <label class="form-label">ยี่ห้อ <span class="text-danger">*</span></label>
                        <select id="device_brand" name="device_brand" class="form-select" required
                            <?= $isApproved ? 'disabled' : '' ?>>
                            <option value="">-- ยี่ห้อ --</option>
                            <?php foreach ($brands as $b): ?>
                            <option value="<?= htmlspecialchars($b) ?>" <?= $b === $deviceBrand ? 'selected' : '' ?>>
                                <?= htmlspecialchars($b) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback">กรุณาเลือกยี่ห้อ</div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">รุ่น <span class="text-danger">*</span></label>
                        <select id="device_model" name="device_model" class="form-select" required
                            <?= $isApproved ? 'disabled' : '' ?>>
                            <option value="">-- รุ่น --</option>
                            <?php if ($deviceBrand): ?>
                            <?php 
            // ถ้ามีแบรนด์ที่เลือกอยู่ใน PHP ให้วนเติม options ของรุ่นทั้งหมด 
            foreach (($modelsByBrand[$deviceBrand] ?? []) as $md): ?>
                            <option value="<?= htmlspecialchars($md) ?>" <?= $deviceModel === $md ? 'selected' : '' ?>>
                                <?= htmlspecialchars($md) ?>
                            </option>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                        <div class="invalid-feedback">กรุณาเลือกรุ่น</div>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">ความจุ <span class="text-danger">*</span></label>
                        <select id="device_capacity" name="device_capacity" class="form-select" required
                            <?= $isApproved ? 'disabled' : '' ?>>
                            <option value="">-- เลือกความจุ --</option>
                            <?php
                            $capacityOptions = ['64 GB', '128 GB', '256 GB', '512 GB', '1 TB', '2 TB'];
                            foreach ($capacityOptions as $cap): ?>
                            <option value="<?= htmlspecialchars($cap) ?>"
                                <?= $deviceCapacity === $cap ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cap) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback">กรุณาเลือกความจุ</div>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">สี <span class="text-danger"></span></label>
                        <input id="device_color" name="device_color" class="form-control"
                            value="<?= htmlspecialchars($deviceColor) ?>" <?= $isApproved ? 'readonly' : '' ?>>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">IMEI (15 หลัก) <span class="text-danger">*</span></label>
                        <input id="device_imei" name="device_imei" type="text" class="form-control" pattern="\d{15}"
                            minlength="15" maxlength="15" inputmode="numeric"
                            value="<?= htmlspecialchars($deviceIMEI) ?>" <?= $isApproved ? 'readonly' : '' ?>>
                        <div class="invalid-feedback">กรุณากรอก IMEI 15 หลัก</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Serial# (10 ตัว) <span class="text-danger">*</span></label>
                        <input id="device_serial" name="device_serial" type="text" class="form-control"
                            pattern="[A-Z0-9]{10}" minlength="10" maxlength="10" style="text-transform:uppercase;"
                            value="<?= htmlspecialchars($deviceSerial) ?>" <?= $isApproved ? 'readonly' : '' ?>>
                        <div class="invalid-feedback">กรุณากรอก Serial 10 ตัว อักษรใหญ่หรือตัวเลข</div>
                    </div>
                </div>
            </div>

            <!-- 6. รูปแบบการล็อค -->
            <div class="card mb-4">
                <div class="card-header bg-light"><strong>6. รูปแบบการล็อค (ไม่บังคับ)</strong></div>
                <div class="card-body row g-3">
                    <!-- Dropdown เลือกประเภทการล็อค -->
                    <div class="col-md-4">
                        <label class="form-label">ประเภทการล็อค</label>
                        <select id="lock_type" name="lock_type" class="form-select"
                            <?= $isApproved ? 'disabled' : '' ?>>
                            <option value="">-- ไม่ล็อค --</option>
                            <option value="mdm" <?= $lockType === 'mdm'   ? 'selected' : '' ?>>ล็อค MDM</option>
                            <option value="icloud" <?= $lockType === 'icloud'? 'selected' : '' ?>>ล็อค iCloud</option>
                        </select>
                    </div>

                    <!-- ช่อง MDM Reference: แสดงเมื่อ $lockType === 'mdm' -->
                    <div class="col-md-4 <?= ($lockType === 'mdm') ? '' : 'd-none' ?>" id="mdm_reference_col">
                        <label class="form-label">MDM Reference</label>
                        <input type="text" name="mdm_reference" class="form-control"
                            value="<?= htmlspecialchars($mdmReference) ?>" <?= $isApproved ? 'readonly' : '' ?>>
                    </div>

                    <!-- ช่อง iCloud Info: แสดงเมื่อ $lockType === 'icloud' -->
                    <div class="col-md-12 <?= ($lockType === 'icloud') ? '' : 'd-none' ?>" id="icloud_info_col">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">iCloud Email</label>
                                <input type="email" name="icloud_email" class="form-control"
                                    value="<?= htmlspecialchars($icloudEmail) ?>" <?= $isApproved ? 'readonly' : '' ?>>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">รหัสผ่าน iCloud</label>
                                <input type="text" name="icloud_password" class="form-control"
                                    value="<?= htmlspecialchars($icloudPassword) ?>"
                                    <?= $isApproved ? 'readonly' : '' ?>>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Pincode</label>
                                <input type="text" name="icloud_pin" class="form-control" pattern="\d{4}" minlength="4"
                                    maxlength="4" inputmode="numeric" title="4 หลัก"
                                    value="<?= htmlspecialchars($icloudPin) ?>" <?= $isApproved ? 'readonly' : '' ?>>
                            </div>
                        </div>
                    </div>
                </div>
            </div>


            <!-- Action button -->
            <?php if (!$isApproved): ?>
            <div class="text-end mb-4">
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-save me-1"></i>บันทึกการแก้ไข
                </button>
                <a href="list-contracts-admin.php" class="btn btn-secondary ms-2">
                    ย้อนกลับ
                </a>
            </div>
            <?php else: ?>
            <div class="text-end mb-4">
                <a href="list-contracts-admin.php" class="btn btn-secondary">
                    ย้อนกลับ
                </a>
            </div>
            <?php endif; ?>
        </form>

        <!-- Toast ยืนยันก่อนบันทึก -->
        <div id="confirmToast"
            class="toast position-fixed top-0 start-50 translate-middle-x align-items-center text-white bg-primary border-0 p-4"
            role="alert" aria-live="assertive" aria-atomic="true" data-bs-autohide="false"
            style="--bs-toast-width: 400px; font-size: 1.25rem; margin-top: 1rem; z-index: 1080;">
            <div class="d-flex flex-column align-items-center">
                <div class="toast-body text-center mb-3">
                    ยืนยันการแก้ไขสัญญาหรือไม่?
                </div>
                <div>
                    <button type="button" class="btn-close btn-close-white me-3" data-bs-dismiss="toast"
                        aria-label="ยกเลิก"></button>
                    <button id="confirmSubmitBtn" type="button" class="btn btn-light btn-lg">
                        ยืนยัน
                    </button>
                </div>
            </div>
        </div>

        <!-- Toast แจ้งช่องที่ยังไม่กรอก -->
        <div id="fieldErrorToast"
            class="toast position-fixed top-0 start-50 translate-middle-x mt-5 bg-danger text-white" role="alert"
            aria-live="assertive" aria-atomic="true" data-bs-delay="5000">
            <div class="d-flex">
                <div class="toast-body"></div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"
                    aria-label="Close"></button>
            </div>
        </div>

        <!-- jQuery UI CSS for Datepicker -->
        <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
        <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
        <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>

        <script>
        $(function() {
            // 1) ตั้ง Locale ไทย สำหรับ Datepicker
            $.datepicker.regional['th'] = {
                closeText: 'ปิด',
                prevText: '&#xAB;ย้อน',
                nextText: 'ถัดไป&#xBB;',
                currentText: 'วันนี้',
                monthNames: [
                    'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน',
                    'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'
                ],
                monthNamesShort: [
                    'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.',
                    'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'
                ],
                dayNames: ['อาทิตย์', 'จันทร์', 'อังคาร', 'พุธ', 'พฤหัส', 'ศุกร์', 'เสาร์'],
                dayNamesShort: ['อา.', 'จ.', 'อ.', 'พ.', 'พฤ.', 'ศ.', 'ส.'],
                dayNamesMin: ['อา.', 'จ.', 'อ.', 'พ.', 'พฤ.', 'ศ.', 'ส.'],
                weekHeader: 'Wk',
                dateFormat: 'dd/mm/yy',
                firstDay: 0,
                isRTL: false,
                showMonthAfterYear: false,
                yearSuffix: ''
            };
            $.datepicker.setDefaults($.datepicker.regional['th']);

            // 2) ตั้งช่วงปีย้อนหลัง 120 ปี
            const today = new Date();
            const currentAD = today.getFullYear();
            const startAD = currentAD - 120;

            function adjustYearDropdown(inst) {
                setTimeout(() => {
                    inst.dpDiv.find('.ui-datepicker-year option').each(function() {
                        const ad = parseInt(this.value, 10);
                        this.text = ad + 543; // แสดง พ.ศ.
                    });
                }, 0);
            }

            $('#birth_date').datepicker({
                changeMonth: true,
                changeYear: true,
                yearRange: `${startAD}:${currentAD}`,
                beforeShow: function(input, inst) {
                    const v = $(input).val();
                    const m = v.match(/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/);
                    if (m) {
                        const d = parseInt(m[1], 10),
                            mo = parseInt(m[2], 10) - 1,
                            by = parseInt(m[3], 10) - 543;
                        $(input).datepicker('option', 'defaultDate', new Date(by, mo, d));
                    }
                    adjustYearDropdown($(input).data('datepicker'));
                },
                onChangeMonthYear: function(year, month, inst) {
                    adjustYearDropdown(inst);
                },
                onSelect: function(txt) {
                    const [d, m, gy] = txt.split('/');
                    const by = parseInt(gy, 10) + 543;
                    $(this).val(`${d}/${m}/${by}`);
                }
            });
        });
        </script>

        <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Manual / Calculator UI
            const fixedM = <?= json_encode($fixedMultipliers, JSON_UNESCAPED_UNICODE) ?>;
            const floatS = <?= json_encode($floatingSettings, JSON_UNESCAPED_UNICODE) ?>;
            const customE = <?= json_encode($customEntries, JSON_UNESCAPED_UNICODE) ?>;
            const manualG = <?= json_encode($manualGroups, JSON_UNESCAPED_UNICODE) ?>;
            const initialPeriodMonths = <?= json_encode($periodMonths, JSON_UNESCAPED_UNICODE) ?>;
            const freqFix = <?= $intervalFixed ?>;
            const freqFloat = <?= $intervalFloating ?>;
            const freqCust = <?= $intervalCustom ?>;
            const freqMan = <?= $intervalManual ?>;

            const typeEl = document.getElementById('interest_type');
            const loanEl = document.getElementById('loan_amount');
            const monthsEl = document.getElementById('period_months');
            const manualEl = document.getElementById('manual_group');
            const startEl = document.querySelector('[name=start_date]');
            const instEl = document.getElementById('computed_installment');
            const schedBody = document.getElementById('scheduleBody');
            const labelEl = document.getElementById('installment-label');
            const colLoan = document.getElementById('col_loan');
            const colManual = document.getElementById('col_manual_group');
            const colMonths = document.getElementById('col_months');
            const manualInfo = document.getElementById('manualInfo');
            const manualPV = document.getElementById('manualPrincipalValue');
            const manualIntD = document.getElementById('manualIntervalDisplay');

            // remove duplicate manual options
            if (manualEl) {
                const seen = new Set();
                [...manualEl.options].forEach(opt => {
                    if (opt.value && seen.has(opt.value)) opt.remove();
                    else seen.add(opt.value);
                });
            }

            function setLabel(days) {
                labelEl.textContent = `ยอดผ่อนทุก ${days} วัน (บาท)`;
            }

            function toggle(el, show) {
                el.classList.toggle('d-none', !show);
            }

            function populateMonths() {
                monthsEl.innerHTML = '<option value="">-- เลือกเดือน --</option>';
                const t = typeEl.value;
                if (t === 'fixed_interest') {
                    setLabel(freqFix);
                    Object.keys(fixedM).forEach(m => {
                        monthsEl.insertAdjacentHTML('beforeend',
                            `<option value="${m}">${m} เดือน</option>`);
                    });
                } else if (t === 'floating_interest') {
                    setLabel(freqFloat);
                    Object.keys(floatS).forEach(m => {
                        monthsEl.insertAdjacentHTML('beforeend',
                            `<option value="${m}">${m} เดือน</option>`);
                    });
                } else if (t === 'custom') {
                    setLabel(freqCust);
                    [...new Set(customE.map(e => e.months))].forEach(m => {
                        monthsEl.insertAdjacentHTML('beforeend',
                            `<option value="${m}">${m} เดือน</option>`);
                    });
                } else if (t === 'manual') {
                    setLabel(freqMan);
                }
            }

            function showManualInfo() {
                const gid = Number(manualEl.value);
                const g = manualG.find(v => Number(v.group_id) === gid);
                if (!g) {
                    manualPV.textContent = '0.00';
                    manualIntD.textContent = '0';
                    instEl.value = '';
                    return;
                }
                manualPV.textContent = (+g.principal).toLocaleString('th-TH', {
                    minimumFractionDigits: 2
                });
                manualIntD.textContent = freqMan;
                const first = g.entries.length ? (+g.entries[0].repayment).toFixed(2) : '';
                instEl.value = first;
            }

            function renderManualSchedule() {
                schedBody.innerHTML = '';
                const gid = parseInt(manualEl.value || 0);
                const g = manualG.find(x => x.group_id == gid);
                const sDate = startEl.value;
                if (!g || !g.entries.length || !sDate) {
                    instEl.value = '';
                    schedBody.insertAdjacentHTML('beforeend',
                        '<tr><td colspan="3" class="text-center text-danger">กรุณาเลือกยอดเงินต้น</td></tr>'
                    );
                    return;
                }
                const entries = [...g.entries].sort((a, b) => a.month_idx - b.month_idx);
                const base = new Date(sDate);
                entries.forEach(e => {
                    const due = new Date(base);
                    due.setDate(due.getDate() + freqMan * e.month_idx);
                    const y = due.getFullYear();
                    const m = String(due.getMonth() + 1).padStart(2, '0');
                    const d = String(due.getDate()).padStart(2, '0');
                    schedBody.insertAdjacentHTML('beforeend', `
                        <tr>
                          <td class="text-center">งวดที่ ${e.month_idx}</td>
                          <td class="text-center">${y}-${m}-${d}</td>
                          <td class="text-end">${(+e.repayment).toLocaleString('th-TH', {
                                minimumFractionDigits: 2,
                                maximumFractionDigits: 2
                          })}</td>
                        </tr>
                    `);
                });
            }

            function calculateAndRender() {
                if (typeEl.value === 'manual') {
                    showManualInfo();
                    renderManualSchedule();
                    return;
                }
                const L = +loanEl.value || 0;
                const M = +monthsEl.value || 0;
                if (!L || !M || !startEl.value) {
                    instEl.value = '';
                    schedBody.innerHTML = '';
                    return;
                }
                let interval = 0,
                    inst = [];
                if (typeEl.value === 'fixed_interest') {
                    interval = freqFix;
                    const mult = fixedM[M];
                    if (!mult) return;
                    const total = L * mult;
                    const num = Math.ceil((M * 30) / interval);
                    inst = Array(num).fill(total / num);
                } else if (typeEl.value === 'floating_interest') {
                    interval = freqFloat;
                    const rate = floatS[M];
                    if (!rate) return;
                    const num = Math.ceil((M * 30) / interval);
                    inst = Array(num).fill(L * rate * (interval / 30));
                } else if (typeEl.value === 'custom') {
                    interval = freqCust;
                    const rows = customE.filter(r => r.months <= M);
                    if (!rows.length) return;
                    inst = rows.map(r => L * r.value);
                }
                instEl.value = inst[0]?.toFixed(2) ?? '';
                schedBody.innerHTML = '';
                const base = new Date(startEl.value);
                inst.forEach((a, i) => {
                    const due = new Date(base);
                    due.setDate(due.getDate() + interval * (i + 1));
                    const d =
                        `${due.getFullYear()}-${String(due.getMonth() + 1).padStart(2, '0')}-${String(due.getDate()).padStart(2, '0')}`;
                    schedBody.insertAdjacentHTML('beforeend', `
                        <tr>
                          <td class="text-center">${i + 1}</td>
                          <td class="text-center">${d}</td>
                          <td class="text-end">${a.toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                        </tr>
                    `);
                });
            }

            function refreshUI() {
                const isManual = typeEl.value === 'manual';
                toggle(colLoan, !isManual);
                toggle(colManual, isManual);
                toggle(colMonths, !isManual);
                toggle(manualInfo, isManual);
            }

            typeEl.addEventListener('change', () => {
                refreshUI();
                populateMonths();

                if (typeEl.value === 'manual' && manualEl.options.length > 1) {
                    manualEl.selectedIndex = 1;
                    showManualInfo();
                    renderManualSchedule();
                } else {
                    calculateAndRender();
                }
            });
            monthsEl.addEventListener('change', calculateAndRender);
            loanEl.addEventListener('input', calculateAndRender);
            startEl.addEventListener('change', calculateAndRender);
            manualEl.addEventListener('change', () => {
                showManualInfo();
                renderManualSchedule();
            });
            startEl.addEventListener('change', () => {
                if (typeEl.value === 'manual') renderManualSchedule();
            });

            // init
            refreshUI();
            populateMonths();
            calculateAndRender();


            /* -----------------------------------------------------------------
               9) GEOGRAPHY DROPDOWNS
            ----------------------------------------------------------------- */
            const baseURL = <?= json_encode($baseURL, JSON_UNESCAPED_SLASHES) ?>;
            let GEO = [];
            const provEl = document.getElementById('province'),
                amphEl = document.getElementById('amphur'),
                distEl = document.getElementById('district'),
                postEl = document.getElementById('postal_code');

            fetch(`${baseURL}/assets/fonts/data/geography.json`)
                .then(r => r.json())
                .then(d => {
                    GEO = d;
                    fillProvinces();
                });

            function fillProvinces() {
                provEl.innerHTML = '<option value="">-- เลือกจังหวัด --</option>';
                [...new Set(GEO.map(o => o.provinceCode))].forEach(code => {
                    const o = GEO.find(x => x.provinceCode == code);
                    provEl.add(new Option(o.provinceNameTh, code));
                });
                provEl.value = <?= json_encode($provinceId) ?>;
                provEl.dispatchEvent(new Event('change'));
            }

            function fillAmphures(provinceCode) {
                amphEl.innerHTML = '<option value="">-- เลือกอำเภอ/เขต --</option>';
                [...new Set(GEO.filter(o => o.provinceCode == provinceCode).map(o => o.districtCode))]
                .forEach(dc => {
                    const o = GEO.find(x => x.districtCode == dc);
                    amphEl.add(new Option(o.districtNameTh, dc));
                });
                amphEl.value = <?= json_encode($amphurId) ?>;
                amphEl.dispatchEvent(new Event('change'));
            }

            function fillDistricts(districtCode) {
                distEl.innerHTML = '<option value="">-- เลือกตำบล/แขวง --</option>';
                GEO.filter(o => o.districtCode == districtCode)
                    .forEach(o => distEl.add(new Option(o.subdistrictNameTh, o.subdistrictCode)));
                distEl.value = <?= json_encode($districtId) ?>;
                distEl.dispatchEvent(new Event('change'));
            }

            provEl.addEventListener('change', () => {
                fillAmphures(provEl.value);
                postEl.value = '';
            });
            amphEl.addEventListener('change', () => {
                fillDistricts(amphEl.value);
                postEl.value = '';
            });
            distEl.addEventListener('change', () => {
                const e = GEO.find(o => String(o.subdistrictCode) === String(distEl.value));
                postEl.value = e ? e.postalCode : '';
            });


            /* -----------------------------------------------------------------
               10) DEVICE BRAND/MODEL DROPDOWN
            ----------------------------------------------------------------- */
            const modelsByBrand = <?= json_encode($modelsByBrand, JSON_UNESCAPED_UNICODE) ?>;
            const brandEl = document.getElementById('device_brand');
            const modelEl = document.getElementById('device_model');

            function populateModels(brand) {
                modelEl.innerHTML = '<option value="">-- รุ่น --</option>';
                (modelsByBrand[brand] || []).forEach(md => {
                    const o = document.createElement('option');
                    o.value = o.textContent = md;
                    modelEl.appendChild(o);
                });
            }

            // ถ้ามีแบรนด์เดิมอยู่ ให้เติมรุ่นและเลือกค่าเดิม
            if (brandEl.value) {
                populateModels(brandEl.value);
                modelEl.value = <?= json_encode($deviceModel) ?> || '';
            }

            // เมื่อ user เปลี่ยนแบรนด์ ให้เติมรุ่นชุดใหม่
            brandEl.addEventListener('change', () => {
                populateModels(brandEl.value);
            });


            /* -----------------------------------------------------------------
               VALIDATION & TOAST
            ----------------------------------------------------------------- */
            const form = document.getElementById('editContractForm');
            const confirmBtn = document.getElementById('confirmSubmitBtn');
            const confirmToast = new bootstrap.Toast(document.getElementById('confirmToast'));
            const fieldErrorToast = new bootstrap.Toast(document.getElementById('fieldErrorToast'));

            // Validate “IMEI or Serial at least one”
            const imeiInput = document.getElementById('device_imei');
            const serialInput = document.getElementById('device_serial');

            function validateEitherImeiOrSerial() {
                if (!imeiInput.value.trim() && !serialInput.value.trim()) {
                    imeiInput.setCustomValidity('กรุณากรอก IMEI หรือ Serial# อย่างใดอย่างหนึ่ง');
                    serialInput.setCustomValidity('กรุณากรอก IMEI หรือ Serial# อย่างใดอย่างหนึ่ง');
                } else {
                    imeiInput.setCustomValidity('');
                    serialInput.setCustomValidity('');
                }
            }

            form.addEventListener('submit', e => {
                validateEitherImeiOrSerial();
                if (!form.checkValidity()) {
                    e.preventDefault();
                    e.stopPropagation();
                    form.classList.add('was-validated');

                    const invalidEls = Array.from(
                        form.querySelectorAll('.form-control:invalid, .form-select:invalid')
                    );
                    const missing = invalidEls.map(el => {
                        const label = el.closest('div[class*="col-"]')?.querySelector('label');
                        return label?.textContent.replace('*', '').trim() || el.name;
                    });
                    const uniqueMissing = [...new Set(missing)];
                    document.getElementById('fieldErrorToast').querySelector('.toast-body')
                        .textContent =
                        'กรุณากรอก: ' + uniqueMissing.join(', ');
                    fieldErrorToast.show();
                    return;
                }
                e.preventDefault();
                confirmToast.show();
            });

            confirmBtn.addEventListener('click', () => {
                confirmToast.hide();
                form.submit();
            });

            // Toggle lock fields
            const lockEl = document.getElementById('lock_type');
            const mdmCol = document.getElementById('mdm_reference_col');
            const iclCol = document.getElementById('icloud_info_col');

            function toggleLock() {
                mdmCol.classList.toggle('d-none', lockEl.value !== 'mdm');
                iclCol.classList.toggle('d-none', lockEl.value !== 'icloud');
            }
            lockEl.addEventListener('change', toggleLock);
            toggleLock(); // init

            // Sidebar & Theme Toggle script (เหมือนใน list-contracts-admin.php)
            document.getElementById('sidebarToggle').onclick = () =>
                document.body.classList.toggle('collapsed');

            (function() {
                const root = document.documentElement;
                const btn = document.getElementById('themeToggle');
                const icon = document.getElementById('themeIcon');

                function updateIcon() {
                    icon.className = root.getAttribute('data-theme') === 'dark' ?
                        'fa-solid fa-sun' :
                        'fa-solid fa-moon';
                }

                btn.onclick = () => {
                    const next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
                    root.setAttribute('data-theme', next);
                    updateIcon();
                };

                updateIcon();
            })();
        });
        </script>

    </div>
</main>

<?php include ROOT_PATH . '/public/includes/footer.php'; ?>