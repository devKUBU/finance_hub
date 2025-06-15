<?php
// File: public/pages/superadmin/setting/interest_settings.php

// 1) Load bootstrap & helpers, start session & check role
require_once realpath(__DIR__ . '/../../../../config/bootstrap.php');
require_once ROOT_PATH . '/includes/helpers.php';
if (session_status() === PHP_SESSION_NONE) session_start();
requireRole(['superadmin', 'admin']);


// 2) Flash messages
$flash_fixed  = $_SESSION['flash_fixed']  ?? ''; unset($_SESSION['flash_fixed']);
$flash_float  = $_SESSION['flash_float']  ?? ''; unset($_SESSION['flash_float']);
$flash_custom = $_SESSION['flash_custom'] ?? ''; unset($_SESSION['flash_custom']);
$flash_manual = $_SESSION['flash_manual'] ?? ''; unset($_SESSION['flash_manual']);



// 3) Connect to DB
require_once ROOT_PATH . '/config/db.php';
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// 4.1) Fetch display names for each formula
$formulaLabels = $pdo
  ->query("SELECT formula_key, display_name 
            FROM service_fee_formulas")
  ->fetchAll(PDO::FETCH_KEY_PAIR);

  // 4.1) Fetch display names & active flags for each formula
$formulas = $pdo
  ->query("SELECT formula_key, display_name, is_active FROM service_fee_formulas")
  ->fetchAll(PDO::FETCH_ASSOC);

// สร้าง 2 array: ชื่อ กับ สถานะ
$formulaLabels      = array_column($formulas, 'display_name', 'formula_key');
$formulaActiveFlags = array_column($formulas, 'is_active',    'formula_key');


// 4) Ensure tables exist (run-once DDL; safe with IF NOT EXISTS)
$pdo->exec("
  CREATE TABLE IF NOT EXISTS fixed_interest_multipliers (
    months     TINYINT UNSIGNED NOT NULL PRIMARY KEY,
    multiplier DECIMAL(8,4)    NOT NULL,
    is_active  TINYINT(1)      NOT NULL DEFAULT 1
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");
$pdo->exec("
  CREATE TABLE IF NOT EXISTS floating_interest_settings (
    months     TINYINT UNSIGNED NOT NULL PRIMARY KEY,
    rate       DECIMAL(5,4)    NOT NULL,
    is_active  TINYINT(1)      NOT NULL DEFAULT 1
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");
$pdo->exec("
  CREATE TABLE IF NOT EXISTS payment_frequency (
    formula       ENUM('fixed','floating','custom','manual') NOT NULL PRIMARY KEY,
    interval_days SMALLINT UNSIGNED                NOT NULL DEFAULT 15,
    is_active     TINYINT(1)                      NOT NULL DEFAULT 1
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");
$pdo->exec("
  CREATE TABLE IF NOT EXISTS custom_interest_formulas (
    formula_id    TINYINT UNSIGNED NOT NULL PRIMARY KEY,
    name          VARCHAR(100)     NOT NULL,
    description   TEXT,
    is_active     TINYINT(1)       NOT NULL DEFAULT 0
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");
$pdo->exec("
  CREATE TABLE IF NOT EXISTS custom_interest_entries (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    formula_id TINYINT UNSIGNED                NOT NULL,
    months     TINYINT UNSIGNED                NOT NULL,
    value      DECIMAL(8,4)                   NOT NULL,
    FOREIGN KEY(formula_id)
      REFERENCES custom_interest_formulas(formula_id)
      ON DELETE CASCADE
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");
$pdo->exec("
  CREATE TABLE IF NOT EXISTS manual_interest_formulas (
    formula_id TINYINT UNSIGNED NOT NULL PRIMARY KEY,
    is_active  TINYINT(1)      NOT NULL DEFAULT 0
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");
$pdo->exec("
  CREATE TABLE IF NOT EXISTS manual_interest_groups (
    group_id   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    formula_id TINYINT UNSIGNED NOT NULL,
    principal  DECIMAL(12,2)    NOT NULL,
    group_name VARCHAR(100)     DEFAULT NULL,
    INDEX idx_formula (formula_id),
    FOREIGN KEY (formula_id) REFERENCES manual_interest_formulas(formula_id)
      ON DELETE CASCADE
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");
$pdo->exec("
  CREATE TABLE IF NOT EXISTS manual_interest_entries (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    group_id   INT UNSIGNED     NOT NULL,
    month_idx  TINYINT UNSIGNED NOT NULL,
    repayment  DECIMAL(12,2)    NOT NULL,
    INDEX idx_group (group_id),
    FOREIGN KEY (group_id) REFERENCES manual_interest_groups(group_id)
      ON DELETE CASCADE
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

// 5) Initialize status messages
$errorFixed   = $successFixed   = '';
$errorFloat   = $successFloat   = '';
$errorCustom  = $successCustom  = '';
$errorManual  = $successManual  = '';

// 6) Handle POST submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  // ─── สูตรที่ 1: Fixed Interest ───────────────────────────────────────────────
if (($_POST['form_type'] ?? '') === 'fixed') {
    $newLabel      = trim($_POST['fixed_label'] ?? '');
    $monthsArr     = $_POST['fixed_month']      ?? [];
    $multArr       = $_POST['fixed_multiplier'] ?? [];
    $enabledArr    = $_POST['fixed_enabled']    ?? [];
    $intervalDay   = $_POST['fixed_interval']   ?? '';
    $formulaActive = !empty($_POST['fixed_active']) ? 1 : 0;

    // ตรวจชื่อก่อน
    if ($newLabel === '') {
        $errorFixed = 'กรุณาระบุชื่อสูตร';
    }
    // ตรวจ array แม่ง
    elseif (count($monthsArr) !== count($multArr)) {
        $errorFixed = 'ข้อมูลสูตรที่ 1 ไม่ครบถ้วน';
    }
    else {
        try {
            // 1) บันทึกชื่อสูตรเสมอ (active flag ก็ได้)
            $stmtL = $pdo->prepare("
              INSERT INTO service_fee_formulas
                (formula_key, display_name, is_active)
              VALUES ('fixed_interest', ?, ?)
              ON DUPLICATE KEY UPDATE
                display_name=VALUES(display_name),
                is_active=VALUES(is_active)
            ");
            $stmtL->execute([$newLabel, $formulaActive]);

            // 2) ถ้าเปิดใช้งาน ให้อัปเดต multipliers ด้วย
            if ($formulaActive) {
                $pdo->beginTransaction();
                // ลบ multipliers เก่า
                $pdo->exec("DELETE FROM fixed_interest_multipliers");
                // แทรก multipliers ใหม่
                $stmtM = $pdo->prepare("
                  INSERT INTO fixed_interest_multipliers
                    (months, multiplier, is_active)
                  VALUES (?, ?, ?)
                ");
                foreach ($monthsArr as $i => $m) {
                    $mon      = (int)$m;
                    $mul      = round((float)$multArr[$i] / 100, 4);
                    $isAct    = !empty($enabledArr[$mon]) ? 1 : 0;
                    $stmtM->execute([$mon, $mul, $isAct]);
                }

                // อัปเดต payment_frequency พร้อม commit
                $stmtF = $pdo->prepare("
                  REPLACE INTO payment_frequency
                    (formula, interval_days, is_active)
                  VALUES ('fixed', ?, 1)
                ");
                $stmtF->execute([ is_numeric($intervalDay) ? (int)$intervalDay : 15 ]);

                $pdo->commit();
            } else {
                // 3) ถ้าปิดใช้งาน แค่ปิด flag ใน payment_frequency
                $pdo->prepare("
                  REPLACE INTO payment_frequency
                    (formula, interval_days, is_active)
                  VALUES ('fixed', ?, 0)
                ")->execute([ is_numeric($intervalDay) ? (int)$intervalDay : 15 ]);
            }

            $_SESSION['flash_fixed'] = 'อัพเดตสูตรที่ 1 เรียบร้อย!';
            header('Location: ' . $_SERVER['PHP_SELF'] . '?tab=fixed');
            exit;

        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errorFixed = 'เกิดข้อผิดพลาดสูตรที่ 1: ' . $e->getMessage();
        }
    }
}



  // ─── สูตรที่ 2: Floating Interest ────────────────────────────────────────────
if (($_POST['form_type'] ?? '') === 'floating') {
    // 1) รับค่าจากฟอร์ม
    $newLabel      = trim($_POST['float_label']    ?? '');
    $monthsArr     = $_POST['float_month']        ?? [];
    $rateArr       = $_POST['float_rate']         ?? [];
    $enabledArr    = $_POST['float_enabled']      ?? [];
    $intervalDay   = $_POST['float_interval']     ?? '';
    $formulaActive = !empty($_POST['float_active']) ? 1 : 0;

    // 2) Validation
    if ($newLabel === '') {
        $errorFloat = 'กรุณาระบุชื่อสูตร';
    } elseif (count($monthsArr) !== count($rateArr)) {
        $errorFloat = 'ข้อมูลสูตรที่ 2 ไม่ครบถ้วน';
    } else {
        try {
            // 3) บันทึกชื่อสูตรเสมอ (insert หรือ update)
            $stmtL = $pdo->prepare("
              INSERT INTO service_fee_formulas
                (formula_key, display_name, is_active)
              VALUES ('floating_interest', ?, ?)
              ON DUPLICATE KEY UPDATE
                display_name = VALUES(display_name),
                is_active    = VALUES(is_active)
            ");
            $stmtL->execute([$newLabel, $formulaActive]);

            if ($formulaActive) {
                // 4) ถ้าเปิดใช้งาน → เริ่ม transaction เพื่อลบ–แทรกเรคคอร์ดใหม่
                $pdo->beginTransaction();

                // ลบ setting เดิม
                $pdo->exec("DELETE FROM floating_interest_settings");

                // แทรก setting ใหม่
                $stmtS = $pdo->prepare("
                  INSERT INTO floating_interest_settings
                    (months, rate, is_active)
                  VALUES (?, ?, ?)
                ");
                foreach ($monthsArr as $i => $m) {
                    $mon      = (int)$m;
                    $rateDec  = round((float)$rateArr[$i] / 100, 4);
                    $isAct    = !empty($enabledArr[$mon]) ? 1 : 0;
                    $stmtS->execute([$mon, $rateDec, $isAct]);
                }

                // อัปเดตความถี่การชำระ + เปิดใช้งาน
                $pdo->prepare("
                  REPLACE INTO payment_frequency
                    (formula, interval_days, is_active)
                  VALUES ('floating', ?, 1)
                ")->execute([
                    is_numeric($intervalDay) ? (int)$intervalDay : 15
                ]);

                $pdo->commit();
            } else {
                // 5) ถ้าปิดใช้งาน → แค่เซ็ต flag ใน payment_frequency เป็น inactive
                $pdo->prepare("
                  REPLACE INTO payment_frequency
                    (formula, interval_days, is_active)
                  VALUES ('floating', ?, 0)
                ")->execute([
                    is_numeric($intervalDay) ? (int)$intervalDay : 15
                ]);
            }

            $_SESSION['flash_float'] = 'อัพเดตสูตรที่ 2 เรียบร้อย!';
            header('Location: ' . $_SERVER['PHP_SELF'] . '?tab=floating');
            exit;
        } catch (Exception $e) {
            // ม้วนกลับถ้าอยู่ใน transaction
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errorFloat = 'เกิดข้อผิดพลาดสูตรที่ 2: ' . $e->getMessage();
        }
    }
}

// ─── สูตรที่ 3: Custom Formula ────────────────────────────────────────────────
if (($_POST['form_type'] ?? '') === 'custom') {
    // ดึงค่าจากฟอร์ม
    $name      = trim($_POST['custom_name']    ?? '');
    $desc      = trim($_POST['custom_desc']    ?? '');
    $enabled   = !empty($_POST['custom_active']) ? 1 : 0;
    $monthsArr = $_POST['custom_month']        ?? [];
    $valueArr  = $_POST['custom_value']        ?? [];
    $interval  = is_numeric($_POST['custom_interval'] ?? '') 
                   ? (int)$_POST['custom_interval'] 
                   : 15;

    // 1) ชื่อสูตรต้องไม่ว่าง, ตารางเดือน↔ค่า ต้องครบ
    if ($name === '') {
        $errorCustom = 'กรุณาระบุชื่อสูตร';
    } elseif (count($monthsArr) !== count($valueArr)) {
        $errorCustom = 'ข้อมูลสูตรที่ 3 ไม่ครบถ้วน';
    } else {
        try {
            // 2) บันทึกชื่อสูตร + สถานะ (insert or update)
            $stmt = $pdo->prepare("
              INSERT INTO service_fee_formulas
                (formula_key, display_name, is_active)
              VALUES ('custom', ?, ?)
              ON DUPLICATE KEY UPDATE
                display_name = VALUES(display_name),
                is_active    = VALUES(is_active)
            ");
            $stmt->execute([$name, $enabled]);

            if ($enabled) {
                // 3) ถ้าเปิดสูตร → transaction อัปเดตรายละเอียด
                $pdo->beginTransaction();

                // REPLACE ตาราง custom_interest_formulas
                $pdo->prepare("
                  REPLACE INTO custom_interest_formulas
                    (formula_id, name, description, interval_days, is_active)
                  VALUES (1, ?, ?, ?, 1)
                ")->execute([$name, $desc, $interval]);

                // ลบ entry เก่า → ใส่ entry ใหม่
                $pdo->exec("DELETE FROM custom_interest_entries WHERE formula_id=1");
                $ins = $pdo->prepare("
                  INSERT INTO custom_interest_entries
                    (formula_id, months, value)
                  VALUES (1, ?, ?)
                ");
                foreach ($monthsArr as $i => $m) {
                    $ins->execute([
                      (int)$m,
                      round($valueArr[$i]/100,4)
                    ]);
                }

                // อัปเดต payment_frequency
                $pdo->prepare("
                  REPLACE INTO payment_frequency
                    (formula, interval_days, is_active)
                  VALUES ('custom', ?, 1)
                ")->execute([$interval]);

                $pdo->commit();
            } else {
                // 4) ถ้าปิดสูตร → แค่เซ็ต flag ใน payment_frequency เป็น inactive
                $pdo->prepare("
                  REPLACE INTO payment_frequency
                    (formula, interval_days, is_active)
                  VALUES ('custom', ?, 0)
                ")->execute([$interval]);
            }

            $_SESSION['flash_custom'] = 'อัพเดตสูตรที่ 3 เรียบร้อย!';
            header('Location: '.$_SERVER['PHP_SELF'].'?tab=custom');
            exit;
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $errorCustom = 'เกิดข้อผิดพลาดสูตรที่ 3: '.$e->getMessage();
        }
    }
}


// ─── สูตรที่ 4: Manual Interest ───────────────────────────────────────────────
if (($_POST['form_type'] ?? '') === 'manual') {
    // ดึงค่า form พื้นฐาน
    $formulaActive = !empty($_POST['manual_active']) ? 1 : 0;
    $intervalDay   = is_numeric($_POST['manual_interval'] ?? '') 
                        ? (int)$_POST['manual_interval'] : 15;
    $principals    = $_POST['manual_principal']    ?? [];   // [0] => หลักกลุ่มแรก, [1] => หลักกลุ่มสอง, ...
    $groupNames    = $_POST['manual_group_name']   ?? [];   // [0] => ชื่อกลุ่มแรก, ...
    $monthsGroups  = $_POST['manual_month']        ?? [];   // 2 มิติ [gi][ri]
    $repayGroups   = $_POST['manual_repayment']    ?? [];   // 2 มิติ [gi][ri]
    $activeGroups  = $_POST['manual_active_entry'] ?? [];   // 2 มิติ [gi][ri], ค่าจะเป็น '1' ถ้าติ๊กใช้งาน, ไม่ได้ตั้งค่า => ถือเป็น 0

    // หากไม่ได้ระบุกลุ่มใดๆ เลย
    if (empty($principals)) {
        $errorManual = 'กรุณาระบุข้อมูลกลุ่มอย่างน้อย 1 กลุ่ม';
    } else {
        try {
            $pdo->beginTransaction();

            // 1) อัปเดตสถานะเปิด/ปิดทั้งสูตร
            $pdo->prepare("
              REPLACE INTO manual_interest_formulas
                (formula_id, is_active)
              VALUES (4, ?)
            ")->execute([$formulaActive]);

            // 2) (ถ้ามีตาราง payment_frequency) ก็อัปเดตความถี่ + สถานะ
            $pdo->prepare("
              REPLACE INTO payment_frequency
                (formula, interval_days, is_active)
              VALUES ('manual', ?, ?)
            ")->execute([$intervalDay, $formulaActive]);

            // 3) ลบข้อมูล groups & entries เดิมของสูตร 4 (cascade กับ entries)
            $pdo->exec("DELETE FROM manual_interest_groups WHERE formula_id=4");

            // 4) เริ่ม insert ข้อมูลกลุ่มใหม่ + entries
            $insertGroup = $pdo->prepare("
              INSERT INTO manual_interest_groups
                (formula_id, principal, group_name)
              VALUES (4, ?, ?)
            ");
            $insertEntry = $pdo->prepare("
              INSERT INTO manual_interest_entries
                (group_id, month_idx, repayment, is_active)
              VALUES (?, ?, ?, ?)
            ");

            $groupCount = 0;
            foreach ($principals as $gi => $prinValue) {
                $principal = (float)$prinValue;
                if ($principal <= 0) {
                    // ข้ามกลุ่มที่ไม่ได้กรอกหลักถูกต้อง
                    continue;
                }
                $groupCount++;
                $gName = trim($groupNames[$gi] ?? '');
                if ($gName === '') {
                    // ถ้าไม่มีชื่อนอกจาก form ให้ตั้งชื่อ default
                    $gName = "กลุ่มที่ {$groupCount}";
                }

                // 4.1) Insert กลุ่ม
                $insertGroup->execute([$principal, $gName]);
                $newGroupId = $pdo->lastInsertId();

                // 4.2) ไล่ insert entries ย่อยในกลุ่ม
                $monthsArr   = $monthsGroups[$gi]   ?? [];
                $repayArr    = $repayGroups[$gi]    ?? [];
                $activeArr   = $activeGroups[$gi]   ?? [];

                foreach ($monthsArr as $ri => $m) {
                    $monthIdx = (int)$m;
                    $repayVal = isset($repayArr[$ri]) ? (float)$repayArr[$ri] : 0;

                    // ถ้าเลขเดือนไม่ถูกต้องหรือยอดผ่อน <= 0 ให้ข้าม
                    if ($monthIdx <= 0 || $repayVal <= 0) {
                        continue;
                    }

                    // ค่าสถานะ is_active ของงวดนี้
                    $isActiveEntry = (
                        isset($activeArr[$ri]) 
                        && ((int)$activeArr[$ri] === 1)
                    ) ? 1 : 0;

                    // Insert ยอดผ่อนแต่ละเดือนพร้อม is_active
                    $insertEntry->execute([
                        $newGroupId,
                        $monthIdx,
                        $repayVal,
                        $isActiveEntry
                    ]);
                }
            }

            if ($groupCount === 0) {
                throw new Exception('ไม่พบกลุ่มที่กรอกข้อมูลถูกต้อง');
            }

            $pdo->commit();
            $_SESSION['flash_manual'] = 'อัพเดตสูตร Manual เรียบร้อยแล้ว';
            header('Location: ' . $_SERVER['PHP_SELF'] . '?tab=manual');
            exit;
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errorManual = 'เกิดข้อผิดพลาดสูตรที่ 4: ' . $e->getMessage();
        }
    }
}


} // end POST handler


// 7) Load Manual Formula Data
$row = $pdo->query("
  SELECT is_active
    FROM manual_interest_formulas
   WHERE formula_id=4
")->fetch(PDO::FETCH_ASSOC);
$manualRow = ['is_active' => false];
if ($row) {
    $manualRow['is_active'] = (bool)$row['is_active'];
}

// ดึงข้อมูล groups + entries (รวม is_active มาเลยตั้งแต่ SQL) 
$dbManualGroups = $pdo->query("
  SELECT 
    g.group_id, 
    g.principal, 
    g.group_name,
    e.month_idx, 
    e.repayment,
    e.is_active
  FROM manual_interest_groups g
  LEFT JOIN manual_interest_entries e 
    ON g.group_id = e.group_id
  WHERE g.formula_id = 4
  ORDER BY g.group_id, e.month_idx
")->fetchAll(PDO::FETCH_ASSOC);

$groupedData = [];
foreach ($dbManualGroups as $r) {
    $gid = $r['group_id'];
    if (!isset($groupedData[$gid])) {
        $groupedData[$gid] = [
            'principal'  => $r['principal'],
            'group_name' => $r['group_name'],
            'entries'    => []
        ];
    }
    if ($r['month_idx'] !== null) {
        $groupedData[$gid]['entries'][] = [
            'month_idx' => $r['month_idx'],
            'repayment' => $r['repayment'],
            // *** เพิ่มค่านี้เข้าไป ***
            'is_active'=> (int)$r['is_active']
        ];
    }
}


// 8) Fetch current frequencies
$freqFixedRow = $pdo->query("SELECT interval_days, is_active FROM payment_frequency WHERE formula='fixed'")
                     ->fetch(PDO::FETCH_ASSOC);
if ($freqFixedRow) {
    $currentFixedInterval = (int)$freqFixedRow['interval_days'];
    $currentFixedActive   = (bool)$freqFixedRow['is_active'];
} else {
    $currentFixedInterval = 15;
    $currentFixedActive   = false;
}

$freqFloatRow = $pdo->query("SELECT interval_days, is_active FROM payment_frequency WHERE formula='floating'")
                     ->fetch(PDO::FETCH_ASSOC);
if ($freqFloatRow) {
    $currentFloatInterval = (int)$freqFloatRow['interval_days'];
    $currentFloatActive   = (bool)$freqFloatRow['is_active'];
} else {
    $currentFloatInterval = 15;
    $currentFloatActive   = false;
}

$freqCustomRow = $pdo->query("SELECT interval_days, is_active FROM payment_frequency WHERE formula='custom'")
                      ->fetch(PDO::FETCH_ASSOC);
if ($freqCustomRow) {
    $currentCustomInterval = (int)$freqCustomRow['interval_days'];
    $currentCustomActive   = (bool)$freqCustomRow['is_active'];
} else {
    $currentCustomInterval = 15;
    $currentCustomActive   = false;
}

$freqManualRow = $pdo->query("SELECT interval_days, is_active FROM payment_frequency WHERE formula='manual'")
                      ->fetch(PDO::FETCH_ASSOC);
if ($freqManualRow) {
    $currentManualInterval = (int)$freqManualRow['interval_days'];
    $currentManualActive   = (bool)$freqManualRow['is_active'];
} else {
    $currentManualInterval = 15;
    $currentManualActive   = false;
}
// 9) Load existing settings for display
$dbFixed         = $pdo->query("SELECT months, multiplier, is_active FROM fixed_interest_multipliers ORDER BY months")
                       ->fetchAll(PDO::FETCH_ASSOC);
$dbFloat         = $pdo->query("SELECT months, rate,      is_active FROM floating_interest_settings ORDER BY months")
                       ->fetchAll(PDO::FETCH_ASSOC);
// ก่อนหน้านี้ load rowCustom:
$rowCustom = $pdo->query("
  SELECT name, description, interval_days, is_active
    FROM custom_interest_formulas
   WHERE formula_id=1
")->fetch(PDO::FETCH_ASSOC) ?: [];

// หลัง fetch $formulas[]
$formulaActiveFlags = array_column($formulas,'is_active','formula_key');
$currentCustomActive = !empty($formulaActiveFlags['custom']);


// กำหนด interval และ active flag
$currentCustomInterval = (int)($rowCustom['interval_days'] ?? 15);
$currentCustomActive   = !empty($rowCustom['is_active']);

$dbCustomEntries = $pdo->query("SELECT months, value FROM custom_interest_entries WHERE formula_id=1 ORDER BY months")
                       ->fetchAll(PDO::FETCH_ASSOC);

// 10) Determine active tab
$validTabs = ['fixed','floating','custom','manual'];
$tab = in_array($_GET['tab'] ?? '', $validTabs) ? $_GET['tab'] : 'fixed';

// 11) Page title
$pageTitle = 'ตั้งค่าสูตรดอกเบี้ย';

// 12) Render page
include ROOT_PATH . '/public/includes/header.php';
include ROOT_PATH . '/public/includes/sidebar.php';
echo '<link rel="stylesheet" href="'. htmlspecialchars($baseURL) .'/assets/css/dashboard.css">';
?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<?php if ($flash_fixed || $flash_float || $flash_custom || $flash_manual): ?>
<div class="position-fixed top-0 start-50 translate-middle-x p-3" style="z-index:2000">
    <!-- Fixed -->
    <?php if ($flash_fixed): ?>
    <div class="toast align-items-center text-bg-success border-0" role="alert" data-bs-delay="3000">
        <div class="d-flex">
            <i class="fa-solid fa-circle-check me-2"></i>
            <div class="toast-body"><?= htmlspecialchars($flash_fixed) ?></div>
            <button type="button" class="btn-close btn-close-white ms-auto me-2" data-bs-dismiss="toast"></button>
        </div>
    </div>
    <?php endif; ?>

    <!-- Floating -->
    <?php if ($flash_float): ?>
    <div class="toast align-items-center text-bg-success border-0" role="alert" data-bs-delay="3000">
        <div class="d-flex">
            <i class="fa-solid fa-circle-check me-2"></i>
            <div class="toast-body"><?= htmlspecialchars($flash_float) ?></div>
            <button type="button" class="btn-close btn-close-white ms-auto me-2" data-bs-dismiss="toast"></button>
        </div>
    </div>
    <?php endif; ?>

    <!-- Custom -->
    <?php if ($flash_custom): ?>
    <div class="toast align-items-center text-bg-success border-0" role="alert" data-bs-delay="3000">
        <div class="d-flex">
            <i class="fa-solid fa-circle-check me-2"></i>
            <div class="toast-body"><?= htmlspecialchars($flash_custom) ?></div>
            <button type="button" class="btn-close btn-close-white ms-auto me-2" data-bs-dismiss="toast"></button>
        </div>
    </div>
    <?php endif; ?>

    <!-- Manual -->
    <?php if ($flash_manual): ?>
    <div class="toast align-items-center text-bg-success border-0" role="alert" data-bs-delay="3000">
        <div class="d-flex">
            <i class="fa-solid fa-circle-check me-2"></i>
            <div class="toast-body"><?= htmlspecialchars($flash_manual) ?></div>
            <button type="button" class="btn-close btn-close-white ms-auto me-2" data-bs-dismiss="toast"></button>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.toast').forEach(toastEl => {
        new bootstrap.Toast(toastEl).show();
    });
});
</script>
<?php endif; ?>



<main class="main-content">
    <!-- Header with sidebar-toggle & theme-toggle -->
    <header class="app-header d-flex align-items-center mb-3">
        <h2 class="header-title m-0">
            <i class="fa-solid fa-calculator me-2"></i><?= htmlspecialchars($pageTitle) ?>
        </h2>
        <div class="header-actions ms-auto d-flex align-items-center">
            <button id="sidebarToggle" class="btn-icon me-2" aria-label="Toggle sidebar">
                <i class="fa-solid fa-bars"></i>
            </button>
            <button id="themeToggle" class="btn-icon" aria-label="Toggle theme">
                <i id="themeIcon" class="fa-solid"></i>
            </button>
        </div>
    </header>
    <hr>
    <div class="container-fluid py-4">
        <!-- Nav tabs -->
        <ul class="nav nav-tabs mb-4">
            <li class="nav-item">
                <a class="nav-link<?= $tab==='fixed'   ? ' active' : '' ?>" href="?tab=fixed">
                    สูตรที่ 1: จบต้นจบดอก Fixed Interest
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link<?= $tab==='floating'? ' active' : '' ?>" href="?tab=floating">
                    สูตรที่ 2: ดอกลอย Floating Interest
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link<?= $tab==='custom'  ? ' active' : '' ?>" href="?tab=custom">
                    สูตรที่ 3: กำหนดดอกเบี้ยเอง Custom Formula
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link<?= $tab==='manual'  ? ' active' : '' ?>" href="?tab=manual">
                    สูตรที่ 4: กำหนดผ่อนเอง Manual
                </a>
            </li>
        </ul>

        <!-- Tab content -->
        <div class="tab-content">
            <?php
      switch ($tab) {
        case 'floating':
          include __DIR__ . '/floating.php';
          break;
        case 'custom':
          include __DIR__ . '/custom.php';
          break;
        case 'manual':
          include __DIR__ . '/manual.php';
          break;
        default:
          include __DIR__ . '/fixed.php';
      }
      ?>
        </div>
    </div>
</main>

<?php include ROOT_PATH . '/public/includes/footer.php'; ?>