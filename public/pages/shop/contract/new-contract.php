<?php
// File: public/pages/shop/contract/new-contract.php
// -------------------------------------------------
date_default_timezone_set('Asia/Bangkok');

/* ------------------------------------------------------------------
   1) LOAD BOOTSTRAP & HELPERS
------------------------------------------------------------------ */
require_once realpath(__DIR__ . '/../../../../config/bootstrap.php');
require_once ROOT_PATH . '/includes/helpers.php';
if (session_status() === PHP_SESSION_NONE) session_start();
requireRole(['shop']);

/* ------------------------------------------------------------------
   2) CONNECT DB
------------------------------------------------------------------ */
require_once ROOT_PATH . '/config/db.php';
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

/* ------------------------------------------------------------------
   3) FETCH ACTIVE INTEREST FORMULAS
------------------------------------------------------------------ */
// Fixed
$fixedMultipliers = $pdo->query("
        SELECT months,multiplier
          FROM fixed_interest_multipliers
         WHERE is_active=1
      ORDER BY months
")->fetchAll(PDO::FETCH_KEY_PAIR);
$intervalFixed = (int)$pdo->query("
        SELECT interval_days
          FROM payment_frequency
         WHERE formula='fixed' AND is_active=1
")->fetchColumn() ?: 15;

// Floating
$floatingSettings = $pdo->query("
        SELECT months,rate
          FROM floating_interest_settings
         WHERE is_active=1
      ORDER BY months
")->fetchAll(PDO::FETCH_KEY_PAIR);
$intervalFloating = (int)$pdo->query("
        SELECT interval_days
          FROM payment_frequency
         WHERE formula='floating' AND is_active=1
")->fetchColumn() ?: 15;

// Custom
$rowCustom      = $pdo->query("
        SELECT interval_days
          FROM payment_frequency
         WHERE formula='custom' AND is_active=1
")->fetch(PDO::FETCH_ASSOC);
$intervalCustom = (int)($rowCustom['interval_days'] ?? 15);
$customEntries  = $pdo->query("
        SELECT months,value
          FROM custom_interest_entries
         WHERE formula_id=1
      ORDER BY months
")->fetchAll(PDO::FETCH_ASSOC);

// Manual
$rowManual      = $pdo->query("
        SELECT interval_days
          FROM payment_frequency
         WHERE formula='manual' AND is_active=1
")->fetch(PDO::FETCH_ASSOC);
$intervalManual = (int)($rowManual['interval_days'] ?? 15);
$rawManual = $pdo->query("
    SELECT g.group_id, g.group_name, g.principal,
           e.month_idx, e.repayment, e.is_active
      FROM manual_interest_formulas f
      JOIN manual_interest_groups   g ON f.formula_id=g.formula_id
 LEFT JOIN manual_interest_entries e ON g.group_id=e.group_id
     WHERE f.is_active=1
  ORDER BY g.group_id, e.month_idx
")->fetchAll(PDO::FETCH_ASSOC);


/* --- Reshape manual groups --- */
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
            'repayment' => $row['repayment'],
            'is_active' => (int)$row['is_active']
        ];
    }
}
$manualGroups = array_values($manualGroups);

/* ------------------------------------------------------------------
   4) INITIALISE FORM VARIABLES
------------------------------------------------------------------ */
$shopId        = $_SESSION['user']['id'];
$errors        = [];

$loanAmount    = (float)($_POST['loan_amount']   ?? 0);
$interestType  =           $_POST['interest_type']   ?? '';
$periodMonths  = (int)($_POST['period_months']   ?? 0);
$manualGroupId = (int)($_POST['manual_group']    ?? 0);

// We'll store the ISO value in a hidden input; the displayed date is dd/mm/YYYY
$startDate     =           $_POST['start_date']  ?? date('Y-m-d\TH:i');

$firstName     = trim($_POST['first_name']      ?? '');
$lastName      = trim($_POST['last_name']       ?? '');
$idCard        = trim($_POST['id_card']         ?? '');
$phone         = trim($_POST['phone']           ?? '');
$lineId        = trim($_POST['line_id']         ?? '');
$facebook      = trim($_POST['facebook']        ?? '');
$provinceId    = (int)($_POST['province']       ?? 0);
$amphurId      = (int)($_POST['amphur']         ?? 0);
$districtId    = (int)($_POST['district']       ?? 0);
$postalCode    = trim($_POST['postal_code']     ?? '');
$houseNumber   = trim($_POST['house_number']    ?? '');
$moo           = trim($_POST['moo']             ?? '');
$soi           = trim($_POST['soi']             ?? '');
$otherAddress  = trim($_POST['other_address']   ?? '');
$deviceBrand   =           $_POST['device_brand']    ?? '';
$deviceModel   =           $_POST['device_model']    ?? '';
$deviceCapacity=           $_POST['device_capacity'] ?? '';
$deviceColor   =           $_POST['device_color']    ?? '';
$deviceIMEI    = trim($_POST['device_imei']     ?? '');
$deviceSerial  = trim($_POST['device_serial']   ?? '');

$birthDateInput = trim($_POST['birth_date'] ?? '');
$birthDate = '';

$lockType      =           $_POST['lock_type']      ?? '';
$mdmReference  = trim($_POST['mdm_reference']      ?? '');
$icloudEmail   = trim($_POST['icloud_email']       ?? '');
$icloudPassword= trim($_POST['icloud_password']    ?? '');
$icloudPin     = trim($_POST['icloud_pin']         ?? '');

$stmtCfg = $pdo->prepare("SELECT * FROM contract_number_settings WHERE is_active = 1 LIMIT 1");
$stmtCfg->execute();
$cnf = $stmtCfg->fetch(PDO::FETCH_ASSOC);

function generateContractNo(PDO $pdo, array $cfg): string {
    $parts = [];
    if ($cfg['prefix'] !== '') {
        $parts[] = $cfg['prefix'];
    }
    if (!empty($cfg['include_date'])) {
        $parts[] = date($cfg['date_format']);
    }
    if ($cfg['pattern'] === 'sequential') {
        $seq = (int)$cfg['next_sequence'];
        $parts[] = str_pad($seq, (int)$cfg['seq_length'], '0', STR_PAD_LEFT);
        $pdo->prepare("
          UPDATE contract_number_settings
             SET next_sequence = next_sequence + 1
           WHERE id = ?
        ")->execute([$cfg['id']]);
    } else {
        $len  = (int)$cfg['random_length'];
        $rand = substr(str_shuffle(str_repeat('0123456789', $len)), 0, $len);
        $parts[] = $rand;
    }
    return implode('', $parts);
}

$contractNo = trim($_POST['contract_no_shop'] ?? '');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $contractNo = $cnf
      ? generateContractNo($pdo, $cnf)
      : 'NN-'.strtoupper(bin2hex(random_bytes(3)));
}

/* ------------------------------------------------------------------
   5) HANDLE FORM SUBMISSION (Validate + Calculate + Insert)
------------------------------------------------------------------ */
$fullSchedule     = [];
$fullInstallments = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* ---------- 5-A  VALIDATION ---------- */
    if ($firstName === '')              $errors[] = 'กรุณากรอกชื่อ';
    if ($lastName === '')               $errors[] = 'กรุณากรอกนามสกุล';
    if ($idCard === '')                 $errors[] = 'กรุณากรอกเลขบัตรประชาชน';
    if (!preg_match('/^\d{13}$/', $idCard)) {
        $errors[] = 'เลขบัตรประชาชนต้องเป็นตัวเลข 13 หลัก';
    }

    if ($provinceId <= 0)               $errors[] = 'กรุณาเลือกจังหวัด';
    if ($amphurId <= 0)                 $errors[] = 'กรุณาเลือกอำเภอ/เขต';
    if ($districtId <= 0)               $errors[] = 'กรุณาเลือกตำบล/แขวง';
    if ($houseNumber === '')            $errors[] = 'กรุณากรอกบ้านเลขที่';

    if ($interestType !== 'manual' && $loanAmount <= 0) {
        $errors[] = 'จำนวนเงินต้องมากกว่า 0';
    }
    if (!in_array($interestType, ['fixed_interest','floating_interest','custom','manual'], true)) {
        $errors[] = 'กรุณาเลือกสูตรค่าบริการ';
    }
    if ($interestType !== 'manual' && $periodMonths <= 0) {
        $errors[] = 'กรุณาเลือกจำนวนเดือน';
    }
    if ($interestType === 'manual' && $manualGroupId <= 0) {
        $errors[] = 'กรุณาเลือกกลุ่ม Manual';
    }

    // We expect a hidden ISO field 'start_date'; ensure it's set
    if (empty($_POST['start_date'])) {
        $errors[] = 'กรุณาระบุวันที่เริ่มสัญญา';
    }

    if ($deviceBrand === '')            $errors[] = 'กรุณาเลือกยี่ห้ออุปกรณ์';
    if ($deviceModel === '')            $errors[] = 'กรุณาเลือกรุ่นอุปกรณ์';
    if ($deviceCapacity === '')         $errors[] = 'กรุณาเลือกความจุ';
    if ($deviceIMEI === '' && $deviceSerial === '') {
        $errors[] = 'กรุณากรอก IMEI หรือ Serial อย่างใดอย่างหนึ่ง';
    } else {
        if ($deviceIMEI !== '' && strlen($deviceIMEI) < 15) {
            $errors[] = 'กรุณากรอก IMEI 15 หลัก';
        }
        if ($deviceSerial !== '' && strlen($deviceSerial) < 10) {
            $errors[] = 'กรุณากรอก Serial 10 ตัว';
        }
    }

    // Validate birth date
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

    /* -------------------- Lock Type Validation ----------------------*/
    if ($lockType === 'mdm') {
        if (trim($mdmReference) === '') {
            $errors[] = 'กรุณากรอก reference MDM';
        }
    } elseif ($lockType === 'icloud') {
        if (trim($icloudEmail) === '') {
            $errors[] = 'กรุณากรอก iCloud Email';
        } elseif (!filter_var($icloudEmail, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'รูปแบบ iCloud Email ไม่ถูกต้อง';
        }
        if (trim($icloudPassword) === '') {
            $errors[] = 'กรุณากรอกรหัสผ่าน iCloud';
        }
        if (trim($icloudPin) === '') {
            $errors[] = 'กรุณากรอก Pincode';
        } elseif (!preg_match('/^\d{4}$/', $icloudPin)) {
            $errors[] = 'Pincode ต้องเป็นตัวเลข 4 หลัก';
        }
    }

    /* ---------- 5-B  CALCULATE & INSERT ---------- */
    if (empty($errors)) {
        try {
            // Combine ISO datetime for start_date
            $startDateSql = $_POST['start_date']; // this is "YYYY-MM-DDTHH:MM:00"
            if ($interestType !== 'manual') {
                $endDate = date('Y-m-d', strtotime("+{$periodMonths} months", strtotime($startDateSql)));
            }

            switch ($interestType) {
                case 'floating_interest':
                    if (!isset($floatingSettings[$periodMonths])) {
                        throw new Exception("ไม่มีการตั้งค่าสูตรดอกลอยสำหรับ {$periodMonths} เดือน");
                    }
                    $intervalDays    = $intervalFloating;
                    $rate            = $floatingSettings[$periodMonths];
                    $numInst         = (int)ceil(($periodMonths*30)/$intervalDays);
                    $amtPerInst      = round($loanAmount*$rate*($intervalDays/30),2);
                    $installments    = array_fill(0,$numInst,$amtPerInst);
                    for ($i = 1; $i <= $numInst; $i++) {
                        $due = date('Y-m-d', strtotime("+".($intervalDays*$i)." days", strtotime($startDateSql)));
                        $fullSchedule[] = [
                            'installment_no' => $i,
                            'due_date'       => $due,
                            'amount'         => $amtPerInst
                        ];
                    }
                    break;

                case 'fixed_interest':
                    if (!isset($fixedMultipliers[$periodMonths])) {
                        throw new Exception("ไม่มีการตั้งค่าสูตรจบดอกสำหรับ {$periodMonths} เดือน");
                    }
                    $intervalDays    = $intervalFixed;
                    $mult            = $fixedMultipliers[$periodMonths];
                    $totalPayment    = $loanAmount*$mult;
                    $numInst         = (int)ceil(($periodMonths*30)/$intervalDays);
                    $amtPerInst      = round($totalPayment/$numInst,2);
                    $installments    = array_fill(0,$numInst,$amtPerInst);
                    for ($i = 1; $i <= $numInst; $i++) {
                        $due = date('Y-m-d', strtotime("+".($intervalDays*$i)." days", strtotime($startDateSql)));
                        $fullSchedule[] = [
                            'installment_no' => $i,
                            'due_date'       => $due,
                            'amount'         => $amtPerInst
                        ];
                    }
                    break;

                case 'custom':
                    $intervalDays = $intervalCustom;
                    $rows = array_values(
                        array_filter($customEntries,
                            fn($r)=>$r['months'] <= $periodMonths)
                    );
                    if (!$rows) {
                        throw new Exception("ไม่มีการตั้งค่า Custom สำหรับ {$periodMonths} เดือน");
                    }
                    foreach ($rows as $r) {
                        $amt = round($loanAmount*$r['value'],2);
                        $installments[] = $amt;
                        $due = date('Y-m-d', strtotime("+".(count($installments)*$intervalDays)." days", strtotime($startDateSql)));
                        $fullSchedule[] = [
                            'installment_no' => count($installments),
                            'due_date'       => $due,
                            'amount'         => $amt
                        ];
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
                    usort($group['entries'], fn($a,$b)=> $a['month_idx'] <=> $b['month_idx']);
                    $installments = [];
                    foreach ($group['entries'] as $e) {
                        $installments[] = (float)$e['repayment'];
                        $due = date('Y-m-d', strtotime("+".($e['month_idx']*$intervalDays)." days", strtotime($startDateSql)));
                        $fullSchedule[] = [
                            'installment_no' => $e['month_idx'],
                            'due_date'       => $due,
                            'amount'         => (float)$e['repayment']
                        ];
                    }
                    $loanAmount    = (float)$group['principal'];
                    $periodMonths  = count($group['entries']);
                    $lastIdx = end($group['entries'])['month_idx'];
                    $endDate = date('Y-m-d', strtotime("+".($lastIdx * $intervalDays)." days", strtotime($startDateSql)));
                    break;
            }

            /* --- INSERT CONTRACT --- */
            $pdo->prepare("
                INSERT INTO contracts (
                    contract_no_shop, shop_id,
                    customer_firstname, customer_lastname,
                    customer_id_card,customer_birth_date, customer_phone,
                    customer_line, customer_facebook,
                    province_id, amphur_id, district_id, postal_code,
                    house_number, moo, soi, other_address,
                    loan_amount, installment_amount, contract_type,
                    period_months, start_date, end_date,
                    device_brand, device_model,
                    device_capacity, device_color,
                    device_imei, device_serial_no,
                    manual_group_id, lock_type, mdm_reference,
                    icloud_email, icloud_password, icloud_pin
                ) VALUES (
                    ?,?,?,?,?,?, ?,?,?,?,?,?, ?,?,?,?,?, ?,?,?,?,?, ?,?,?,?,?, ?,?,?,?,?,?,?,?
                    
                )
            ")->execute([
                $contractNo, $shopId,
                $firstName, $lastName,
                $idCard, $birthDate, $phone,
                $lineId, $facebook,
                $provinceId, $amphurId, $districtId, $postalCode,
                $houseNumber, $moo, $soi, $otherAddress,
                $loanAmount, $installments[0] ?? 0, $interestType,
                $periodMonths, $startDateSql, $endDate,
                $deviceBrand, $deviceModel,
                $deviceCapacity, $deviceColor,
                $deviceIMEI, $deviceSerial,
                $interestType==='manual' ? $manualGroupId : null,
                $lockType      ?: null,
                $mdmReference  ?: null,
                $icloudEmail   ?: null,
                $icloudPassword?: null,
                $icloudPin     ?: null,
            ]);

            /* --- INSERT PAYMENTS --- */
            $contractId = $pdo->lastInsertId();
            foreach ($installments as $idx=>$amt) {
                $due = date('Y-m-d',
                       strtotime('+'.($intervalDays*($idx+1)).' days',
                       strtotime($startDateSql)));
                $pdo->prepare("
                    INSERT INTO payments (contract_id,pay_no,due_date,amount_due,status)
                    VALUES (?,?,?,?, 'pending')
                ")->execute([$contractId,$idx+1,$due,$amt]);
            }

            header("Location: {$baseURL}/pages/shop/contract/list-contracts.php");
            exit;

        } catch (Exception $ex) {
            $errors[] = 'เกิดข้อผิดพลาด: '.$ex->getMessage();
        }
    }
}

/* ------------------------------------------------------------------
   6) DEVICE BRANDS / MODELS
------------------------------------------------------------------ */
$modelsByBrand = [];
foreach ($pdo->query("
        SELECT brand,model_name
          FROM device_models
      ORDER BY brand,model_name
")->fetchAll(PDO::FETCH_ASSOC) as $r) {
    $modelsByBrand[$r['brand']][] = $r['model_name'];
}
$brands = array_keys($modelsByBrand);

// ดึงชื่อสูตรที่แอดมินตั้งไว้ (active เท่านั้น)
$formulaLabels = $pdo
  ->query("SELECT formula_key, display_name FROM service_fee_formulas WHERE is_active=1")
  ->fetchAll(PDO::FETCH_KEY_PAIR);

/* ------------------------------------------------------------------
   7) RENDER PAGE (HTML + JS)
------------------------------------------------------------------ */
$pageTitle = 'สร้างสัญญาใหม่';
include ROOT_PATH . '/public/includes/header.php';
include ROOT_PATH . '/public/includes/sidebar.php';
?>
<link rel="stylesheet" href="<?=htmlspecialchars($baseURL)?>/assets/css/dashboard.css">
<link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">

<style>
.card-header {
    font-size: larger;
    padding: 1.5rem;
    align-items: center;
    justify-content: center;
}

.required {
    color: #dc3545;
}

.table-light th {
    background-color: #f8f9fa;
}

#confirmToast {
    --bs-toast-width: 400px;
    font-size: 1.25rem;
    margin-top: 1rem;
    z-index: 1080;
}
</style>

<main class="main-content">
    <header class="app-header d-flex align-items-center justify-content-between">
        <h2 class="header-title m-0">
            <i class="fa-solid fa-file-contract me-2"></i><?=htmlspecialchars($pageTitle)?>
        </h2>
        <div class="header-actions d-flex align-items-center">
            <a href="<?=$baseURL?>/pages/shop/contract/list-contracts.php" class="btn btn-sm btn-outline-primary me-2">
                <i class="fa-solid fa-list me-1"></i>รายการสัญญา
            </a>
            <button id="sidebarToggle" class="btn-icon"><i class="fa-solid fa-bars"></i></button>
            <button id="themeToggle" class="btn-icon ms-2"><i id="themeIcon" class="fa-solid"></i></button>
        </div>
    </header>
    <hr>
    <div class="container-fluid py-4">

        <?php if ($errors): ?>
        <div class="alert alert-danger">
            <h6><i class="fa-solid fa-exclamation-triangle me-2"></i>พบข้อผิดพลาด:</h6>
            <ul class="mb-0">
                <?php foreach ($errors as $e): ?>
                <li><?=htmlspecialchars($e)?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <form method="post" class="needs-validation" novalidate autocomplete="off" id="contractForm">

            <!-- Contract Number -->
            <div class="mb-4 d-flex align-items-center">
                <label class="form-label mb-0 me-2" style="width:120px;">
                    <i class="fa-solid fa-hashtag me-1"></i>เลขที่สัญญา
                </label>
                <input type="text" name="contract_no_shop" class="form-control form-control-sm" style="max-width:300px;"
                    required value="<?=htmlspecialchars($contractNo)?>">
                <div class="invalid-feedback">กรุณาระบุเลขที่สัญญา</div>
            </div>

            <!-- 1. Interest Calculation -->
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <strong><i class="fa-solid fa-calculator me-2"></i>1. การคำนวณอัตราค่าบริการ</strong>
                </div>
                <div class="card-body row g-3">
                    <!-- Interest Type -->
                    <div class="col-md-4">
                        <label class="form-label">
                            <i class="fa-solid fa-percent me-1"></i>สูตรค่าบริการ<span class="required">*</span>
                        </label>
                        <select id="interest_type" name="interest_type" class="form-select" required>
                            <option value="">-- เลือกสูตร --</option>
                            <?php
                            $order = ['fixed_interest','floating_interest','custom','manual'];
                            foreach ($order as $key):
                                if (!isset($formulaLabels[$key])) continue;
                                $show = match($key) {
                                    'fixed_interest'   => (bool)$fixedMultipliers,
                                    'floating_interest'=> (bool)$floatingSettings,
                                    'custom'           => (bool)$customEntries,
                                    'manual'           => (bool)$manualGroups,
                                };
                                if (!$show) continue;
                            ?>
                            <option value="<?= $key ?>" <?= $interestType === $key ? 'selected' : ''?>>
                                <?= htmlspecialchars($formulaLabels[$key])?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback">กรุณาเลือกสูตรค่าบริการ</div>
                    </div>

                    <!-- Loan Amount -->
                    <div class="col-md-4" id="col_loan">
                        <label class="form-label">
                            <i class="fa-solid fa-money-bill-wave me-1"></i>จำนวนเงิน (บาท)<span
                                class="required">*</span>
                        </label>
                        <input id="loan_amount" name="loan_amount" type="number" step="0.01" class="form-control"
                            required value="<?=htmlspecialchars($loanAmount)?>">
                        <div class="invalid-feedback">กรุณากรอกจำนวนเงิน</div>
                    </div>

                    <!-- Manual Group -->
                    <div class="col-md-4 d-none" id="col_manual_group">
                        <label class="form-label">
                            <i class="fa-solid fa-users me-1"></i>เลือกยอดเงินต้น (Manual)<span
                                class="required">*</span>
                        </label>
                        <select id="manual_group" name="manual_group" class="form-select">
                            <option value="">-- เลือกยอดเงินต้น --</option>
                            <?php 
                            $addedGroups = [];
                            foreach($manualGroups as $g): 
                                if (!in_array($g['group_id'], $addedGroups)):
                                    $addedGroups[] = $g['group_id'];
                            ?>
                            <option value="<?= htmlspecialchars($g['group_id']) ?>"
                                <?= $manualGroupId === $g['group_id'] ? 'selected' : '' ?>>
                                <?= number_format($g['principal'], 2) ?> บาท
                                (<?= htmlspecialchars($g['group_name']) ?>)
                            </option>
                            <?php 
                                endif;
                            endforeach; 
                            ?>
                        </select>
                        <div class="invalid-feedback">กรุณาเลือกยอดเงินต้น</div>
                    </div>

                    <!-- Manual Info -->
                    <div class="col-12 mb-3 d-none" id="manualInfo">
                        <p class="mb-1">
                            <i class="fa-solid fa-money-bill-wave text-primary me-1"></i>
                            ยอดเงินต้น: <strong id="manualPrincipalValue">0.00</strong> บาท
                        </p>
                        <p class="mb-0">
                            <i class="fa-solid fa-calendar-days text-success me-1"></i>
                            ยอดผ่อนทุก <strong id="manualIntervalDisplay">0</strong> วัน
                        </p>
                    </div>

                    <!-- Period Months -->
                    <div class="col-md-4" id="col_months">
                        <label class="form-label">
                            <i class="fa-solid fa-calendar-alt me-1"></i>จำนวนเดือน<span class="required">*</span>
                        </label>
                        <select id="period_months" name="period_months" class="form-select">
                            <option value="">-- เลือกเดือน --</option>
                        </select>
                        <div class="invalid-feedback">กรุณาเลือกจำนวนเดือน</div>
                    </div>

                    <!-- Computed Installment -->
                    <div class="col-12">
                        <label id="installment-label" class="form-label">
                            <i class="fa-solid fa-receipt me-1"></i>ยอดผ่อนต่อตอน (บาท)
                        </label>
                        <input id="computed_installment" type="text" class="form-control mb-2" readonly>
                    </div>

                    <!-- Schedule Table -->
                    <div class="col-12 mt-3">
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-center">
                                            <i class="fa-solid fa-hashtag me-1"></i>งวด
                                        </th>
                                        <th class="text-center">
                                            <i class="fa-solid fa-calendar-day me-1"></i>ครบกำหนด
                                        </th>
                                        <th class="text-end">
                                            <i class="fa-solid fa-coins me-1"></i>ยอดผ่อน (บาท)
                                        </th>
                                    </tr>
                                </thead>
                                <tbody id="scheduleBody">
                                    <tr>
                                        <td colspan="3" class="text-center text-muted">
                                            <i class="fa-solid fa-info-circle me-1"></i>
                                            กรุณาเลือกสูตรและกรอกข้อมูลให้ครบถ้วน
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Start Date -->
                    <div class="col-md-6">
                        <label class="form-label">
                            <i class="fa-solid fa-calendar-days me-1"></i>วันที่เริ่มสัญญา<span
                                class="required">*</span>
                        </label>
                        <input id="start_date_display" type="text" class="form-control" placeholder="dd/mm/YYYY"
                            readonly required>
                        <input id="start_date_hidden" name="start_date" type="hidden">
                        <div class="invalid-feedback">กรุณาระบุวันที่</div>
                        <small class="text-success">รูปแบบ: วว/ดด/ปปปป (ค.ศ.)</small>
                    </div>

                </div>
            </div>

            <!-- 2. ข้อมูลลูกค้า -->
            <div class="card mb-4">
                <div class="card-header bg-light"><strong><i class="fa-solid fa-user me-2"></i>2. ข้อมูลลูกค้า</strong>
                </div>
                <div class="card-body row g-3">
                    <div class="col-md-6">
                        <label class="form-label">
                            <i class="fa-solid fa-id-badge me-1"></i>ชื่อ (ระบุคำนำหน้า)<span class="required">*</span>
                        </label>
                        <input name="first_name" class="form-control" required value="<?=htmlspecialchars($firstName)?>"
                            autocomplete="off">
                        <div class="invalid-feedback">กรุณากรอกชื่อ</div>
                        <small class="text-success">เช่น นาย สุภาพ</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">
                            <i class="fa-solid fa-id-badge me-1"></i>นามสกุล<span class="required">*</span>
                        </label>
                        <input name="last_name" class="form-control" required value="<?=htmlspecialchars($lastName)?>"
                            autocomplete="off">
                        <div class="invalid-feedback">กรุณากรอกนามสกุล</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">
                            <i class="fa-solid fa-id-card me-1"></i>เลขบัตรประชาชน<span class="required">*</span>
                        </label>
                        <input type="text" name="id_card" class="form-control" required pattern="\d{13}" minlength="13"
                            maxlength="13" inputmode="numeric" title="กรุณากรอกเลขบัตรประชาชน 13 หลัก"
                            value="<?=htmlspecialchars($idCard)?>" autocomplete="off">
                        <div class="invalid-feedback">เลขบัตรประชาชนต้องเป็นตัวเลข 13 หลัก</div>
                        <small class="text-success">เช่น 3350800087956</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">
                            <i class="fa-solid fa-birthday-cake me-1"></i>วันเกิด พ.ศ.<span class="required">*</span>
                        </label>
                        <input name="birth_date" id="birth_date" type="text" class="form-control"
                            placeholder="dd/mm/YYYY" required value="<?=htmlspecialchars($_POST['birth_date'] ?? '')?>">
                        <div class="invalid-feedback">กรุณาเลือกวันเกิด</div>
                        <small class="text-success fst-italic">รูปแบบ: วว/ดด/ปปปป (ค.ศ.)
                        </small>
                    </div>
                </div>
            </div>

            <!-- 3. ช่องทางการติดต่อ -->
            <div class="card mb-4">
                <div class="card-header bg-light"><strong><i class="fa-solid fa-phone me-2"></i>3.
                        ช่องทางการติดต่อ</strong></div>
                <div class="card-body row g-3">
                    <div class="col-md-4">
                        <label class="form-label">
                            <i class="fa-solid fa-phone me-1"></i>เบอร์โทรศัพท์
                        </label>
                        <input name="phone" class="form-control" value="<?=htmlspecialchars($phone)?>"
                            autocomplete="off">
                        <small class="text-success">เช่น 080-9990099</small>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">
                            <i class="fa-brands fa-line me-1"></i>Line ID
                        </label>
                        <input name="line_id" class="form-control" value="<?=htmlspecialchars($lineId)?>"
                            autocomplete="off">
                        <small class="text-success">เช่น https://line.me/R/ti/p/@387mkssg</small>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">
                            <i class="fa-brands fa-facebook me-1"></i>Facebook
                        </label>
                        <input name="facebook" class="form-control" value="<?=htmlspecialchars($facebook)?>"
                            autocomplete="off">
                        <small class="text-success">เช่น https://facebook.com/example</small>
                    </div>
                </div>
            </div>

            <!-- 4. ที่อยู่ -->
            <div class="card mb-4">
                <div class="card-header bg-light"><strong><i class="fa-solid fa-home me-2"></i>4.
                        ที่อยู่-หมู่บ้าน</strong></div>
                <div class="card-body row g-3">
                    <div class="col-md-3">
                        <label class="form-label">
                            <i class="fa-solid fa-house me-1"></i>บ้านเลขที่ / หมู่บ้าน<span class="required">*</span>
                        </label>
                        <input name="house_number" class="form-control" required
                            value="<?=htmlspecialchars($houseNumber)?>" autocomplete="off">
                        <div class="invalid-feedback">กรุณากรอกบ้านเลขที่</div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">
                            <i class="fa-solid fa-map-location me-1"></i>หมู่ที่
                        </label>
                        <input name="moo" class="form-control" value="<?=htmlspecialchars($moo)?>" autocomplete="off">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">
                            <i class="fa-solid fa-road me-1"></i>ซอย/ตรอก
                        </label>
                        <input name="soi" class="form-control" value="<?=htmlspecialchars($soi)?>" autocomplete="off">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">
                            <i class="fa-solid fa-ellipsis me-1"></i>อื่นๆ
                        </label>
                        <input name="other_address" class="form-control" value="<?=htmlspecialchars($otherAddress)?>"
                            autocomplete="off">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">
                            <i class="fa-solid fa-city me-1"></i>จังหวัด<span class="required">*</span>
                        </label>
                        <select id="province" name="province" class="form-select" required>
                            <option value="">-- เลือกจังหวัด --</option>
                        </select>
                        <div class="invalid-feedback">กรุณาเลือกจังหวัด</div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">
                            <i class="fa-solid fa-building me-1"></i>อำเภอ/เขต<span class="required">*</span>
                        </label>
                        <select id="amphur" name="amphur" class="form-select" required>
                            <option value="">-- เลือกอำเภอ/เขต --</option>
                        </select>
                        <div class="invalid-feedback">กรุณาเลือกอำเภอ/เขต</div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">
                            <i class="fa-solid fa-map-pin me-1"></i>ตำบล/แขวง<span class="required">*</span>
                        </label>
                        <select id="district" name="district" class="form-select" required>
                            <option value="">-- เลือกตำบล/แขวง --</option>
                        </select>
                        <div class="invalid-feedback">กรุณาเลือกตำบล/แขวง</div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">
                            <i class="fa-solid fa-mail-bulk me-1"></i>รหัสไปรษณีย์
                        </label>
                        <input id="postal_code" name="postal_code" class="form-control" readonly
                            value="<?=htmlspecialchars($postalCode)?>">
                    </div>
                </div>
            </div>

            <!-- 5. ข้อมูลตัวเครื่อง -->
            <div class="card mb-4">
                <div class="card-header bg-light"><strong><i class="fa-solid fa-mobile-alt me-2"></i>5.
                        ข้อมูลตัวเครื่อง</strong></div>
                <div class="card-body row g-3">
                    <div class="col-md-3">
                        <label class="form-label">
                            <i class="fa-solid fa-tags me-1"></i>ยี่ห้อ<span class="required">*</span>
                        </label>
                        <select id="device_brand" name="device_brand" class="form-select" required>
                            <option value="">-- ยี่ห้อ --</option>
                            <?php foreach($brands as $b): ?>
                            <option value="<?=htmlspecialchars($b)?>" <?=$b === $deviceBrand ? 'selected' : ''?>>
                                <?=htmlspecialchars($b)?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback">กรุณาเลือกยี่ห้อ</div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">
                            <i class="fa-solid fa-mobile me-1"></i>รุ่น<span class="required">*</span>
                        </label>
                        <select id="device_model" name="device_model" class="form-select" required>
                            <option value="">-- รุ่น --</option>
                        </select>
                        <div class="invalid-feedback">กรุณาเลือกรุ่น</div>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">
                            <i class="fa-solid fa-hdd me-1"></i>ความจุ<span class="required">*</span>
                        </label>
                        <select id="device_capacity" name="device_capacity" class="form-select" required>
                            <option value="">-- เลือกความจุ --</option>
                            <?php
                            $capacityOptions = ['64 GB','128 GB','256 GB','512 GB','1 TB','2 TB'];
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
                        <label class="form-label">
                            <i class="fa-solid fa-palette me-1"></i>สี
                        </label>
                        <input id="device_color" name="device_color" class="form-control"
                            value="<?=htmlspecialchars($deviceColor)?>" autocomplete="off">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">
                            <i class="fa-solid fa-barcode me-1"></i>IMEI (15 หลัก)
                        </label>
                        <input id="device_imei" name="device_imei" type="text" class="form-control" pattern="\d{15}"
                            minlength="15" maxlength="15" inputmode="numeric" value="<?=htmlspecialchars($deviceIMEI)?>"
                            autocomplete="off">
                        <div class="invalid-feedback">กรุณากรอก IMEI 15 หลัก หรือ Serial# อย่างใดอย่างหนึ่ง</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">
                            <i class="fa-solid fa-qrcode me-1"></i>Serial# (10 ตัว)
                        </label>
                        <input id="device_serial" name="device_serial" type="text" class="form-control"
                            pattern="[A-Z0-9]{10}" minlength="10" maxlength="10" style="text-transform:uppercase;"
                            value="<?=htmlspecialchars($deviceSerial)?>" autocomplete="off">
                        <div class="invalid-feedback">กรุณากรอก Serial 10 ตัว หรือ IMEI อย่างใดอย่างหนึ่ง</div>
                    </div>
                </div>
            </div>

            <!-- 6. รูปแบบการล็อค -->
            <div class="card mb-4">
                <div class="card-header bg-light"><strong><i class="fa-solid fa-lock me-2"></i>6. รูปแบบการล็อค
                        (ไม่บังคับ)</strong></div>
                <div class="card-body row g-3">
                    <div class="col-md-4">
                        <label class="form-label">
                            <i class="fa-solid fa-shield-alt me-1"></i>ประเภทการล็อค
                        </label>
                        <select id="lock_type" name="lock_type" class="form-select">
                            <option value="">-- เลือกประเภท --</option>
                            <option value="mdm" <?= $lockType==='mdm'    ? 'selected' : '' ?>>ล็อค MDM</option>
                            <option value="icloud" <?= $lockType==='icloud' ? 'selected' : '' ?>>ล็อค iCloud (Apple ID)
                            </option>
                        </select>
                    </div>
                    <div class="col-md-4 d-none" id="mdm_reference_col">
                        <label class="form-label">
                            <i class="fa-solid fa-key me-1"></i>MDM Reference<span class="required">*</span>
                        </label>
                        <input type="text" name="mdm_reference" id="mdm_reference" class="form-control"
                            value="<?=htmlspecialchars($mdmReference)?>" autocomplete="off">
                        <div class="invalid-feedback">กรุณากรอก reference MDM</div>
                    </div>
                    <div class="col-12 d-none" id="icloud_info_col">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">
                                    <i class="fa-solid fa-envelope me-1"></i>iCloud Email<span class="required">*</span>
                                </label>
                                <input type="email" name="icloud_email" class="form-control"
                                    value="<?=htmlspecialchars($icloudEmail)?>" autocomplete="off">
                                <div class="invalid-feedback">กรุณากรอก iCloud Email</div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">
                                    <i class="fa-solid fa-lock me-1"></i>รหัสผ่าน<span class="required">*</span>
                                </label>
                                <input type="password" name="icloud_password" class="form-control"
                                    value="<?=htmlspecialchars($icloudPassword)?>" autocomplete="off">
                                <div class="invalid-feedback">กรุณากรอกรหัสผ่าน iCloud</div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">
                                    <i class="fa-solid fa-hashtag me-1"></i>Pincode<span class="required">*</span>
                                </label>
                                <input type="text" name="icloud_pin" class="form-control" pattern="\d{4}" minlength="4"
                                    maxlength="4" inputmode="numeric" title="กรุณากรอก Pincode 4 หลัก"
                                    value="<?=htmlspecialchars($icloudPin)?>" autocomplete="off">
                                <div class="invalid-feedback">Pincode ต้องเป็นตัวเลข 4 หลัก</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="text-center mb-4">
                <button id="submitBtn" type="submit" class="btn btn-success btn-lg">
                    <i class="fa-solid fa-plus me-1"></i>สร้างสัญญา
                </button>
                <a href="<?=$baseURL?>/pages/shop/contract/list-contracts.php"
                    class="btn btn-outline-secondary btn-lg ms-2">
                    <i class="fa-solid fa-times me-1"></i>ยกเลิก
                </a>
            </div>
        </form>

        <!-- Confirmation Toast -->
        <div id="confirmToast"
            class="toast position-fixed top-0 start-50 translate-middle-x align-items-center text-white bg-primary border-0 p-4"
            role="alert" aria-live="assertive" aria-atomic="true" data-bs-autohide="false">
            <div class="d-flex flex-column align-items-center">
                <div class="toast-body text-center mb-3">
                    <i class="fa-solid fa-question-circle fa-2x mb-2"></i><br>
                    ยืนยันการสร้างสัญญาหรือไม่?
                </div>
                <div>
                    <button type="button" class="btn-close btn-close-white me-3" data-bs-dismiss="toast"
                        aria-label="ยกเลิก"></button>
                    <button id="confirmSubmitBtn" type="button" class="btn btn-light btn-lg">
                        <i class="fa-solid fa-check me-1"></i>ยืนยัน
                    </button>
                </div>
            </div>
        </div>
    </div>
</main>

<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
<link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">

<!-- Timepicker Addon สำหรับ jQuery UI (รองรับการเลือกเวลาแบบ 24 ชั่วโมง) -->
<link rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/jquery-ui-timepicker-addon/1.6.3/jquery-ui-timepicker-addon.min.css"
    integrity="sha512-GXH/nnGnJrW0sjfrNCh9KcuR734qxuqhjkhxuLu7FX0jA3p2aGB3FkBAqL/GAtT8mw+V0WdAjLhgywHN5JmmmQ=="
    crossorigin="anonymous" referrerpolicy="no-referrer" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-ui-timepicker-addon/1.6.3/jquery-ui-timepicker-addon.min.js"
    integrity="sha512-LHYFjh8k3qZcwuoTOi93kmpZ0dbTgRg1vL/LBf8ToKnGDhwDMykANuLBRXw1rtl64lCliGRzFqmzOyx6P/CkMw=="
    crossorigin="anonymous" referrerpolicy="no-referrer"></script>

<script>
$(function() {
    const $disp = $('#start_date_display'); // dd/mm/YYYY
    const $hidden = $('#start_date_hidden'); // ISO yyyy-mm-ddTHH:MM:00

    // insert a time-picker if you want time
    let $time = $('#start_time');
    if (!$time.length) {
        $time = $('<input>', {
            type: 'time',
            id: 'start_time',
            name: 'start_time',
            class: 'form-control mt-2',
            required: true,
            step: 60
        }).insertAfter($disp);
    }

    function syncStartHidden() {
        // expect $disp.val() = "dd/mm/YYYY"
        const parts = $disp.val().match(/^(\d{2})\/(\d{2})\/(\d{4})$/);
        if (!parts) return;
        const [, dd, mm, yyyy] = parts;
        const t = $time.val() || '00:00';
        $hidden.val(`${yyyy}-${mm}-${dd}T${t}:00`).trigger('change');
    }

    // initialize jQuery UI datepicker in English mode:
    $disp.datepicker({
        dateFormat: 'dd/mm/yy', // dd/mm/YYYY
        changeMonth: true,
        changeYear: true,
        yearRange: '-10:+10', // adjust as needed
        onSelect: function(txt) {
            // txt is "dd/mm/yy"
            const [d, m, y2] = txt.split('/');
            // jquery.ui gives yy as 2-digit year; get full year:
            const fullYear = $.datepicker.formatDate('yy', new Date(y2, m - 1, d)) === y2 ?
                y2 :
                String(2000 + parseInt(y2, 10));
            // re-write as four-digit year
            const dd2 = d.padStart(2, '0');
            const mm2 = m.padStart(2, '0');
            $disp.val(`${dd2}/${mm2}/${fullYear}`);
            syncStartHidden();
        }
    });

    // set initial date to today
    $disp.datepicker('setDate', new Date());
    syncStartHidden();

    // when time changes, also sync
    $time.on('change', syncStartHidden);

    /* ---------- BIRTH-DATE (ค.ศ.) ---------- */
    const $birth = $('#birth_date');
    const adMin = new Date().getFullYear() - 120;
    const adMax = new Date().getFullYear();

    $birth.datepicker({
        dateFormat: 'dd/mm/yy', // dd/mm/YYYY in Gregorian
        changeMonth: true,
        changeYear: true,
        yearRange: `${adMin}:${adMax}`
    });


});
</script>


<?php include ROOT_PATH . '/public/includes/footer.php'; ?>

<!-- -----------------------------------------------------------------
     8) JS : CALCULATOR & UI (แก้ Custom / Manual ให้สมบูรณ์)
------------------------------------------------------------------ -->
<script>
const fixedM = <?=json_encode($fixedMultipliers,JSON_UNESCAPED_UNICODE)?>;
const floatS = <?=json_encode($floatingSettings,JSON_UNESCAPED_UNICODE)?>;
const customE = <?=json_encode($customEntries,JSON_UNESCAPED_UNICODE)?>;
const manualG = <?=json_encode($manualGroups,JSON_UNESCAPED_UNICODE)?>;
const freqFix = <?= $intervalFixed   ?>;
const freqFloat = <?= $intervalFloating?>;
const freqCust = <?= $intervalCustom  ?>;
const freqMan = <?= $intervalManual  ?>;

/* ---------- LOAD AFTER DOM READY ---------- */
document.addEventListener('DOMContentLoaded', () => {
    console.table(manualG.map(g => ({
        id: g.group_id,
        entries: g.entries.length
    })));

    /* --- DOM refs --- */
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
    const colManualInstallments = document.getElementById('col_manual_installments');
    const manualCountSelect = document.getElementById('manualInstallmentCount');
    const manualCountInput = document.getElementById('manualInstallmentCountInput');
    const manualInstallMax = document.getElementById('manualInstallMax');
    const manualInstallMaxTxt = document.getElementById('manualInstallmentMaxText');

    // เก็บตารางเต็มของ Manual
    let manualFullSchedule = [];

    /* --------------------
       HELPER: ฟังก์ชันแปลง date → 'YYYY-MM-DD'
    -------------------- */
    function formatYMD(dateObj) {
        const y = dateObj.getFullYear();
        const m = String(dateObj.getMonth() + 1).padStart(2, '0');
        const d = String(dateObj.getDate()).padStart(2, '0');
        return `${y}-${m}-${d}`;
    }

    /* --------------------
       HELPER: เติม <option> 1..N ให้ #manualInstallmentCount
    -------------------- */
    function populateManualCountOptions(totalCount) {
        manualCountSelect.innerHTML = '<option value="">แสดงทุกงวด</option>';
        for (let i = 1; i <= totalCount; i++) {
            const opt = document.createElement('option');
            opt.value = i;
            opt.textContent = `แสดง ${i} งวดแรก`;
            manualCountSelect.appendChild(opt);
        }
    }

    /* --------------------
          HELPER: คำนวณตาราง Manual เต็ม แล้วเรียก render + เติม dropdown
       -------------------- */
    function computeManualSchedule() {
        manualFullSchedule = [];
        // หากยังไม่ได้เลือกกลุ่ม หรือยังไม่ได้เลือกวันเริ่มสัญญา
        const gid = parseInt(manualEl.value || 0);
        const gData = manualG.find(x => x.group_id == gid);
        const sDate = startEl.value;

        if (!gData || !gData.entries.length || !sDate) {
            // Reset UI
            manualPV.textContent = '0.00';
            manualIntD.textContent = '0';
            manualFullSchedule = [];
            // ซ่อน dropdown และ clear table
            colManualInstallments.classList.add('d-none');
            renderManualSchedule();
            return;
        }

        // แสดงข้อมูล Manual Info
        manualPV.textContent = (+gData.principal).toLocaleString('th-TH', {
            minimumFractionDigits: 2
        });
        manualIntD.textContent = freqMan;

        // สร้าง full schedule จาก entries ในกลุ่ม
        const sorted = [...gData.entries].sort((a, b) => a.month_idx - b.month_idx);
        sorted.forEach(e => {
            const due = new Date(sDate);
            due.setDate(due.getDate() + freqMan * e.month_idx);
            manualFullSchedule.push({
                installment_no: e.month_idx,
                due_date: formatYMD(due),
                amount: (+e.repayment)
            });
        });
        populateManualCountOptions(activeEntries.length);
        // เติม <select> 1..N ที่ N = จำนวนงวดเต็ม
        // populateManualCountOptions(manualFullSchedule.length);


        // แสดง dropdown และอัปเดตตาราง
        colManualInstallments.classList.remove('d-none');
        renderManualSchedule();
    }



    /* --- remove duplicate manual options --- */
    if (manualEl) {
        const seen = new Set();
        [...manualEl.options].forEach(opt => {
            if (opt.value && seen.has(opt.value)) opt.remove();
            else seen.add(opt.value);
        });
    }

    /* ----------------------------------------------------------------
       UI helpers
    -----------------------------------------------------------------*/
    function setLabel(days) {
        labelEl.textContent = `ยอดผ่อนทุก ${days} วัน (บาท)`;
    }

    function toggle(el, show) {
        el.classList.toggle('d-none', !show);
    }

    /* ----------------------------------------------------------------
       populateMonths()
    -----------------------------------------------------------------*/
    function populateMonths() {
        monthsEl.innerHTML = '<option value="">-- เลือกเดือน --</option>';
        const t = typeEl.value;
        if (t === 'fixed_interest') {
            setLabel(freqFix);
            Object.keys(fixedM).forEach(m => {
                monthsEl.insertAdjacentHTML('beforeend', `<option value="${m}">${m} เดือน</option>`);
            });
        } else if (t === 'floating_interest') {
            setLabel(freqFloat);
            Object.keys(floatS).forEach(m => {
                monthsEl.insertAdjacentHTML('beforeend', `<option value="${m}">${m} เดือน</option>`);
            });
        } else if (t === 'custom') {
            setLabel(freqCust);
            [...new Set(customE.map(e => e.months))].forEach(m => {
                monthsEl.insertAdjacentHTML('beforeend', `<option value="${m}">${m} เดือน</option>`);
            });
        } else if (t === 'manual') {
            setLabel(freqMan);
        }
    }

    /* ----------------------------------------------------------------
       showManualInfo() + renderManualSchedule()
    -----------------------------------------------------------------*/
    /* ---------- SHOW INFO & RENDER MANUAL ------------- */
    function showManualInfo() {
        const gid = Number(manualEl.value);
        const g = manualG.find(v => Number(v.group_id) === gid);
        if (!g) {
            manualPV.textContent = '0.00';
            manualIntD.textContent = '0';
            instEl.value = '';
            return;
        }
        // กรองเฉพาะ active entries
        const activeEntries = g.entries.filter(e => e.is_active === 1);
        if (!activeEntries.length) {
            manualPV.textContent = Number(g.principal).toLocaleString('th-TH', {
                minimumFractionDigits: 2
            });
            manualIntD.textContent = freqMan;
            instEl.value = '';
            return;
        }
        // แสดง principal + interval เหมือนเดิม
        manualPV.textContent = Number(g.principal).toLocaleString('th-TH', {
            minimumFractionDigits: 2
        });
        manualIntD.textContent = freqMan;
        // ถ้าอยากให้แสดงยอดงวดแรก ให้เอา activeEntries[0]
        instEl.value = (+activeEntries[0].repayment).toFixed(2);
    }


    /* 2) <<<<<  วางฟังก์ชันนี้แทนตัวเก่า  >>>>> */
    function renderManualSchedule() {
        console.log('🔄 renderManualSchedule() fired');
        console.log('selected gid =', manualEl.value);
        console.log('start date   =', startEl.value);
        schedBody.innerHTML = '';

        const gid = parseInt(manualEl.value || 0);
        const g = manualG.find(x => x.group_id == gid);
        const sDate = startEl.value;

        if (!g || !g.entries.length || !sDate) {
            instEl.value = '';
            schedBody.insertAdjacentHTML(
                'beforeend',
                '<tr><td colspan="3" class="text-center text-danger">กรุณาเลือกยอดเงินต้น</td></tr>'
            );
            return;
        }

        // กรองเฉพาะ entries ที่ is_active===1 แล้วเรียงตาม month_idx
        const entries = g.entries
            .filter(e => e.is_active === 1)
            .sort((a, b) => a.month_idx - b.month_idx);

        if (!entries.length) {
            instEl.value = '';
            schedBody.insertAdjacentHTML(
                'beforeend',
                '<tr><td colspan="3" class="text-center text-danger">ยังไม่มีงวดที่เปิดใช้งาน</td></tr>'
            );
            return;
        }

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
                minimumFractionDigits: 2
              })}</td>
            </tr>
        `);
        });
    }


    /* ----------------------------------------------------------------
       calculateAndRender()  (Fixed / Floating / Custom)
    -----------------------------------------------------------------*/
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
                `${due.getFullYear()}-${String(due.getMonth()+1).padStart(2,'0')}-${String(due.getDate()).padStart(2,'0')}`;
            schedBody.insertAdjacentHTML('beforeend', `
                <tr>
                  <td class="text-center">${i+1}</td>
                  <td class="text-center">${d}</td>
                  <td class="text-end">${a.toLocaleString('th-TH',{minimumFractionDigits:2,maximunFractionDigits:2})}</td>
                </tr>`);
        });
    }

    /* ----------------------------------------------------------------
       refreshUI() & init
    -----------------------------------------------------------------*/
    function refreshUI() {
        const man = typeEl.value === 'manual';
        toggle(colLoan, !man);
        toggle(colManual, man);
        toggle(colMonths, !man);
        toggle(manualInfo, man);
    }

    /* event listeners */
    typeEl.addEventListener('change', () => {
        refreshUI();
        populateMonths();



        if (typeEl.value === 'manual' && manualEl.options.length > 1) {
            manualEl.selectedIndex = 1; // auto-select option แรก
            showManualInfo();
            renderManualSchedule();
        } else {
            calculateAndRender();
        }
    });


    monthsEl.addEventListener('change', calculateAndRender);
    loanEl.addEventListener('input', calculateAndRender);
    startEl.addEventListener('change', calculateAndRender);
    /*  LISTENER ที่สำคัญ  */
    manualEl.addEventListener('change', () => {
        console.log('🔔 manual group changed to', manualEl.value);
        showManualInfo();
        renderManualSchedule();
    });

    /* start date เปลี่ยน ก็วาดตารางใหม่กรณี Manual */
    startEl.addEventListener('change', () => {
        if (typeEl.value === 'manual') renderManualSchedule();
    });
    /*  *********  จบที่เพิ่ม  *********  */

    /* init */
    refreshUI();
    populateMonths();
    monthsEl.value = '<?= $periodMonths ?>';
    calculateAndRender();
});
</script>

<!-- -----------------------------------------------------------------
     9) JS : GEOGRAPHY DROPDOWNS  (คงเดิม)
------------------------------------------------------------------ -->
<script>
let GEO = [];
fetch('<?= $baseURL ?>/assets/fonts/data/geography.json')
    .then(r => r.json())
    .then(d => {
        GEO = d;
        fillProvinces();
        // รีเซ็ตค่าเดิมจาก PHP
        provEl.value = '<?= $provinceId ?>';
        fillAmphures();
        amphEl.value = '<?= $amphurId ?>';
        fillDistricts();
        distEl.value = '<?= $districtId ?>';
        // ใส่รหัสไปรษณีย์ด้วย
        const e = GEO.find(o => o.subdistrictCode == '<?= $districtId ?>');
        postEl.value = e ? e.postalCode : '';
    });
const provEl = document.getElementById('province'),
    amphEl = document.getElementById('amphur'),
    distEl = document.getElementById('district'),
    postEl = document.getElementById('postal_code');

function fillProvinces() {
    provEl.innerHTML = '<option value="">-- เลือกจังหวัด --</option>';
    [...new Set(GEO.map(o => o.provinceCode))].forEach(c => {
        const o = GEO.find(x => x.provinceCode == c);
        provEl.add(new Option(o.provinceNameTh, c));
    });
}

function fillAmphures() {
    amphEl.innerHTML = '<option value="">-- เลือกอำเภอ/เขต --</option>';
    new Set(GEO.filter(o => o.provinceCode == provEl.value).map(o => o.districtCode))
        .forEach(c => {
            const o = GEO.find(x => x.districtCode == c);
            amphEl.add(new Option(o.districtNameTh, c));
        });
}

function fillDistricts() {
    distEl.innerHTML = '<option value="">-- เลือกตำบล/แขวง --</option>';
    GEO.filter(o => o.districtCode == amphEl.value)
        .forEach(o => distEl.add(new Option(o.subdistrictNameTh, o.subdistrictCode)));
}
provEl.addEventListener('change', () => {
    fillAmphures();
    distEl.innerHTML = '<option>';
    postEl.value = '';
});
amphEl.addEventListener('change', () => {
    fillDistricts();
    postEl.value = '';
});
distEl.addEventListener('change', () => {
    const e = GEO.find(o => o.subdistrictCode == distEl.value);
    postEl.value = e ? e.postalCode : '';
});
</script>

<!-- -----------------------------------------------------------------
     10) JS : DEVICE BRAND/MODEL DROPDOWN  (คงเดิม)
------------------------------------------------------------------ -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    const modelsByBrand = <?=json_encode($modelsByBrand,JSON_UNESCAPED_UNICODE)?>;
    const bEl = document.getElementById('device_brand');
    const mEl = document.getElementById('device_model');

    function populate(brand) {
        mEl.innerHTML = '<option value="">-- รุ่น --</option>';
        (modelsByBrand[brand] || []).forEach(md => {
            const o = document.createElement('option');
            o.value = o.textContent = md;
            mEl.appendChild(o);
        });
    }
    if (bEl.value) {
        populate(bEl.value);
        mEl.value = <?=json_encode($deviceModel)?> || '';
    }
    bEl.addEventListener('change', () => populate(bEl.value));
});
</script>

<!-- ถ้ายังไม่มี ให้โหลด Bootstrap JS bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('form.needs-validation');
    const toastEl = document.getElementById('confirmToast');
    const confirmBtn = document.getElementById('confirmSubmitBtn');
    const toast = new bootstrap.Toast(toastEl);
    const imeiInput = document.getElementById('device_imei');
    const serialInput = document.getElementById('device_serial');

    function validateEitherImeiOrSerial() {
        // ถ้าว่างทั้งคู่ → ตั้ง custom validity
        if (imeiInput.value.trim() === '' && serialInput.value.trim() === '') {
            imeiInput.setCustomValidity('กรุณากรอก IMEI หรือ Serial# อย่างใดอย่างหนึ่ง');
            serialInput.setCustomValidity('กรุณากรอก IMEI หรือ Serial# อย่างใดอย่างหนึ่ง');
        } else {
            imeiInput.setCustomValidity('');
            serialInput.setCustomValidity('');
        }
    }
    form.addEventListener('submit', e => {
        // 1) เช็คเงื่อนไข “อย่างใดอย่างหนึ่ง”
        validateEitherImeiOrSerial();

        // 2) แล้วค่อยเช็ค Bootstrap validation แบบเดิม
        if (!form.checkValidity()) {
            e.preventDefault();
            e.stopPropagation();
            form.classList.add('was-validated');
            return;
        }

        // 3) ถ้าผ่านแล้ว ก็โชว์ Toast ยืนยันต่อ (โค้ดของคุณเดิม)
        e.preventDefault();
        new bootstrap.Toast(document.getElementById('confirmToast')).show();
    });


    // ดักการส่ง form
    form.addEventListener('submit', e => {
        e.preventDefault(); // หยุดส่งปกติ
        toast.show(); // โชว์ Toast ยืนยัน
    });

    // ถ้ากดยืนยัน จึงส่ง form จริง
    confirmBtn.addEventListener('click', () => {
        toast.hide();
        form.submit();
    });
});
</script>

<script>
$(function() {
    // on page‐load, and whenever the select changes…
    $('#lock_type').on('change', function() {
        const v = $(this).val();
        // show MDM only if v==='mdm'
        $('#mdm_reference_col').toggleClass('d-none', v !== 'mdm');
        // show iCloud only if v==='icloud'
        $('#icloud_info_col').toggleClass('d-none', v !== 'icloud');
    }).trigger('change');
});
</script>