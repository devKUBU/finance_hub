<?php
// File: public/pages/superadmin/setting/contract-number.php

require_once realpath(__DIR__ . '/../../../../config/bootstrap.php');
require_once ROOT_PATH . '/includes/helpers.php';
if (session_status() === PHP_SESSION_NONE) session_start();
requireRole(['superadmin', 'admin']);


require_once ROOT_PATH . '/config/db.php';
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// 1) โหลดตั้งค่าเลขสัญญา ปัจจุบัน
$stmt = $pdo->query("SELECT * FROM contract_number_settings LIMIT 1");
$cfg  = $stmt->fetch(PDO::FETCH_ASSOC) ?: [
    'prefix'=>'', 'include_date'=>0,'date_format'=>'Ymd',
    'pattern'=>'sequential','next_sequence'=>1,'seq_length'=>4,
    'random_length'=>6,'is_active'=>0,'id'=>null
];

// 2) จัดการ POST
$errors = [];
$flash   = '';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    // อ่านค่าจากฟอร์ม
    $id       = (int)($_POST['id'] ?? $cfg['id']);
    $prefix   = trim($_POST['prefix'] ?? '');
    $inclDt   = !empty($_POST['include_date']) ? 1:0;
    $df       = trim($_POST['date_format'] ?? '');
    $pattern  = ($_POST['pattern'] ?? 'sequential')==='random'?'random':'sequential';
    $nextSeq  = max(1,(int)($_POST['next_sequence'] ?? 1));
    $seqLen   = max(1,(int)($_POST['seq_length']    ?? 4));
    $randLen  = max(1,(int)($_POST['random_length'] ?? 6));
    $active   = !empty($_POST['is_active']) ? 1:0;

    // Validation
    if ($inclDt && $df==='')    $errors[] = 'กรุณาระบุรูปแบบวันที่';
    if ($pattern==='sequential' && $seqLen<1) $errors[] = 'กรุณาระบุความยาวตัวเลข';
    if ($pattern==='random'     && $randLen<1) $errors[] = 'กรุณาระบุความยาวสุ่ม';

    if (empty($errors)) {
        if ($id) {
            $sql = "UPDATE contract_number_settings
                      SET prefix=?,include_date=?,date_format=?,pattern=?,
                          next_sequence=?,seq_length=?,random_length=?,is_active=?
                    WHERE id=?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $prefix,$inclDt,$df,$pattern,
                $nextSeq,$seqLen,$randLen,$active,
                $id
            ]);
        } else {
            $sql = "INSERT INTO contract_number_settings
                    (prefix,include_date,date_format,pattern,
                     next_sequence,seq_length,random_length,is_active)
                    VALUES (?,?,?,?,?,?,?,?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $prefix,$inclDt,$df,$pattern,
                $nextSeq,$seqLen,$randLen,$active
            ]);
        }
        // หลังบันทึกสำเร็จ
        $_SESSION['flash_contract_no'] = 'บันทึกการตั้งค่าเรียบร้อยแล้ว';
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit;

    }
}

$flash = $_SESSION['flash_contract_no'] ?? '';
unset($_SESSION['flash_contract_no']);


// 3) แสดงผล
$pageTitle = 'ตั้งค่าหมายเลขสัญญา';
include ROOT_PATH.'/public/includes/header.php';
include ROOT_PATH.'/public/includes/sidebar.php';
function previewContractNo(array $cfg): string {
    $parts = [];
    if ($cfg['prefix'] !== '') {
        $parts[] = $cfg['prefix'];
    }
    if ($cfg['include_date']) {
        $parts[] = date($cfg['date_format']);
    }
    if ($cfg['pattern'] === 'sequential') {
        // ใช้ next_sequence ปัจจุบัน แต่ไม่อัปเดตลง DB
        $parts[] = str_pad(
            (int)$cfg['next_sequence'],
            (int)$cfg['seq_length'],
            '0',
            STR_PAD_LEFT
        );
    } else {
        // สุ่มตัวเลข ตามความยาวที่ตั้งไว้
        $rnd = '';
        $pool = '0123456789';
        for ($i = 0; $i < $cfg['random_length']; $i++) {
            $rnd .= $pool[random_int(0, strlen($pool)-1)];
        }
        $parts[] = $rnd;
    }
    return implode('', $parts);
}

// สร้างตัวอย่างเลขสัญญาจาก config ปัจจุบัน
$sampleNo = previewContractNo($cfg);
?>
<link rel="stylesheet" href="<?=htmlspecialchars($baseURL)?>/assets/css/dashboard.css">

<main class="main-content">
    <!-- Header with toggles -->
    <header class="app-header d-flex align-items-center justify-content-between mb-3">
        <h2 class="header-title m-0">
            <i class="fa-solid fa-hashtag me-2"></i><?= htmlspecialchars($pageTitle) ?>
        </h2>
        <div class="header-actions d-flex align-items-center">
            <!-- ไม่มีปุ่มเพิ่มใหม่ เพราะมีเพียง 1 record -->
            <button id="sidebarToggle" class="btn-icon me-2" aria-label="Toggle sidebar">
                <i class="fa-solid fa-bars"></i>
            </button>
            <button id="themeToggle" class="btn-icon ms-2" aria-label="Toggle theme">
                <i id="themeIcon" class="fa-solid"></i>
            </button>
        </div>
    </header>
    <hr>

    <div class="container-fluid py-4">
        <?php if ($flash): ?>
        <div class="alert alert-success"><?= htmlspecialchars($flash) ?></div>
        <?php endif; ?>
        <?php if ($errors): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $e): ?>
                <li><?= htmlspecialchars($e) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        <!-- Preview card -->
        <div class="card mb-4 border-none">
            <div class="card-body text-center">
                <h3 class="text-primary mb-2"><i class="fa-solid fa-eye me-2"></i>ตัวอย่างเลขสัญญา</h3>
                <div style="font-size:2rem;font-weight:600;">
                    <?= htmlspecialchars($sampleNo) ?>
                </div>
                <p class="mt-3 mb-0 preview-format">
                    รูปแบบ:
                    <?php
    $parts = [];
    if ($cfg['prefix']!=='')        $parts[] = "Prefix “{$cfg['prefix']}”";
    if ($cfg['include_date'])        $parts[] = "วันที่ format “{$cfg['date_format']}”";
    if ($cfg['pattern']==='sequential')
       $parts[] = "เรียงลำดับ ยาว {$cfg['seq_length']} หลัก";
    else                            $parts[] = "สุ่มตัวเลข {$cfg['random_length']} หลัก";
    echo implode(' + ', $parts);
  ?>
                </p>

            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-light">
                <strong>รูปแบบหมายเลขสัญญา</strong>
            </div>
            <div class="card-body">
                <form method="post" class="row g-4 needs-validation" novalidate>
                    <input type="hidden" name="id" value="<?= htmlspecialchars($cfg['id']) ?>">

                    <div class="col-md-4">
                        <label class="form-label">Prefix</label>
                        <input type="text" name="prefix" class="form-control"
                            value="<?= htmlspecialchars($cfg['prefix']) ?>">
                    </div>

                    <div class="col-md-4 d-flex align-items-center">
                        <input type="hidden" name="include_date" value="0">
                        <input type="checkbox" id="include_date" name="include_date" value="1"
                            <?= $cfg['include_date']?'checked':''?>>
                        <label for="include_date" class="form-check-label ms-2">
                            ใส่วันที่ในเลขสัญญา
                        </label>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">รูปแบบวันที่ (PHP date-format)</label>
                        <input type="text" name="date_format" class="form-control" required
                            value="<?= htmlspecialchars($cfg['date_format']) ?>" readonly>
                        <div class="invalid-feedback">เช่น <code>Ymd</code> หรือ <code>Y-m-d</code></div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">รูปแบบเลขสัญญา</label>
                        <select name="pattern" id="pattern" class="form-select">
                            <option value="sequential" <?= $cfg['pattern']==='sequential'?'selected':''?>>
                                เรียงลำดับ (sequential)
                            </option>
                            <option value="random" <?= $cfg['pattern']==='random'?'selected':''?>>
                                สุ่มตัวเลข (random)
                            </option>
                        </select>
                    </div>

                    <div class="col-md-4" id="seq_opts">
                        <label class="form-label">เริ่มที่ลำดับ (next_sequence)</label>
                        <input type="number" name="next_sequence" min="1" class="form-control"
                            value="<?= htmlspecialchars($cfg['next_sequence']) ?>">
                        <label class="form-label mt-3">ความยาวตัวเลข (seq_length)</label>
                        <input type="number" name="seq_length" min="1" class="form-control"
                            value="<?= htmlspecialchars($cfg['seq_length']) ?>">
                    </div>

                    <div class="col-md-4 d-none" id="rand_opts">
                        <label class="form-label">ความยาวตัวเลขสุ่ม (random_length)</label>
                        <input type="number" name="random_length" min="1" class="form-control"
                            value="<?= htmlspecialchars($cfg['random_length']) ?>">
                    </div>

                    <div class="col-md-4 d-flex align-items-center">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" id="is_active" name="is_active" value="1"
                            <?= $cfg['is_active']?'checked':''?>>
                        <label for="is_active" class="form-check-label ms-2">เปิดใช้งาน</label>
                    </div>

                    <div class="col-12 text-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-save me-1"></i>บันทึก
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>
<!-- Toast แสดงผลเมื่อบันทึกสำเร็จ -->
<?php if ($flash): ?>
<div class="position-fixed top-0 end-0 p-3" style="z-index:2000">
    <div id="toastContractNo" class="toast align-items-center text-white bg-success border-0" role="alert"
        aria-live="assertive" aria-atomic="true" data-bs-delay="3000">
        <div class="d-flex">
            <div class="toast-body"><?= htmlspecialchars($flash) ?></div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"
                aria-label="ปิด"></button>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- สคริปต์แยกสำหรับ Toast -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    const toastEl = document.getElementById('toastContractNo');
    if (toastEl) {
        new bootstrap.Toast(toastEl).show();
    }
});
</script>

<?php include ROOT_PATH.'/public/includes/footer.php'; ?>

<script>
// Sidebar & Theme toggle (copy จากตัวอย่าง)
document.getElementById('sidebarToggle').onclick = () =>
    document.body.classList.toggle('collapsed');
(function() {
    const btn = document.getElementById('themeToggle'),
        ico = document.getElementById('themeIcon'),
        root = document.documentElement;

    function upd() {
        ico.className =
            root.getAttribute('data-theme') === 'dark' ?
            'fa-solid fa-sun' : 'fa-solid fa-moon';
    }
    btn.onclick = () => {
        root.setAttribute('data-theme',
            root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark');
        upd();
    };
    upd();
})();

// จัดการแสดง/ซ่อน ส่วน sequential vs random
document.addEventListener('DOMContentLoaded', () => {
    const pat = document.getElementById('pattern'),
        seq = document.getElementById('seq_opts'),
        rnd = document.getElementById('rand_opts');

    function toggle() {
        if (pat.value === 'sequential') {
            seq.classList.remove('d-none');
            rnd.classList.add('d-none');
        } else {
            seq.classList.add('d-none');
            rnd.classList.remove('d-none');
        }
    }
    pat.addEventListener('change', toggle);
    toggle();
});
</script>