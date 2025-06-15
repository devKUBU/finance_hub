<?php
ob_start();

// File: public/pages/shop/contract/list-contracts.php

require_once realpath(__DIR__ . '/../../../../config/bootstrap.php');
require_once ROOT_PATH . '/includes/helpers.php';
if (session_status() === PHP_SESSION_NONE) session_start();
requireRole(['shop']);

require_once ROOT_PATH . '/config/db.php';
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// 1) Handle evidence uploads
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['evidence_contract_id'])) {
    $cid    = (int) $_POST['evidence_contract_id'];
    $shopId = (int) $_SESSION['user']['id'];

    $st = $pdo->prepare("SELECT approval_status FROM contracts WHERE id = ? AND shop_id = ?");
    $st->execute([$cid, $shopId]);
    if ($st->fetchColumn() === 'approved') {
        setFlash('danger', 'สัญญานี้อนุมัติแล้ว ไม่สามารถแก้ไขหลักฐานได้');
        header('Location: list-contracts.php');
        exit;
    }

    $fields = [
        'signed_contract'   => 'ใบเซ็นต์สัญญา',
        'customer_photo'    => 'รูปภาพลูกค้า',
        'device_photo'      => 'รูปภาพตัวเครื่อง',
        'imei_photo'        => 'รูปภาพ IMEI',
        'lock_screen_photo' => 'รูปภาพหน้าล็อค'
    ];
    $uploadDir = ROOT_PATH . "/public/uploads/evidence/{$cid}/";
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    $stmtIns = $pdo->prepare("
      INSERT INTO contract_evidence
        (contract_id, evidence_type, file_path, uploaded_by)
      VALUES (?, ?, ?, ?)
    ");

    $errors  = [];
    $missing = [];

    foreach ($fields as $field => $label) {
        if ($field === 'signed_contract') {
            if (empty($_FILES[$field]['name'][0])) {
                $missing[] = $label;
            } else {
                $count = count($_FILES[$field]['name']);
                for ($i = 0; $i < $count; $i++) {
                    if ($_FILES[$field]['error'][$i] !== UPLOAD_ERR_OK) {
                        $errors[] = "{$label} (แผ่นที่ ".($i+1).") ผิดพลาด (รหัส {$_FILES[$field]['error'][$i]})";
                        continue;
                    }
                    $tmp  = $_FILES[$field]['tmp_name'][$i];
                    $name = preg_replace('/[^\w\-\._]/','_', basename($_FILES[$field]['name'][$i]));
                    $dst  = $uploadDir . time() . "_{$field}_{$i}_{$name}";
                    if (move_uploaded_file($tmp, $dst)) {
                        $rel = "uploads/evidence/{$cid}/".basename($dst);
                        $stmtIns->execute([$cid, $field, $rel, $shopId]);
                    } else {
                        $errors[] = "ไม่สามารถอัปโหลด {$label} (แผ่นที่ ".($i+1).")";
                    }
                }
            }
            continue;
        }

        if (empty($_FILES[$field]['name'])) {
            $missing[] = $label;
            continue;
        }
        if ($_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
            $errors[] = "{$label} ผิดพลาด (รหัส {$_FILES[$field]['error']})";
            continue;
        }
        $tmp  = $_FILES[$field]['tmp_name'];
        $name = preg_replace('/[^\w\-\._]/','_', basename($_FILES[$field]['name']));
        $dst  = $uploadDir . time() . "_{$field}_{$name}";
        if (move_uploaded_file($tmp, $dst)) {
            $rel = "uploads/evidence/{$cid}/".basename($dst);
            $stmtIns->execute([$cid, $field, $rel, $shopId]);
        } else {
            $errors[] = "ไม่สามารถอัปโหลด {$label}";
        }
    }

    if (!empty($missing)) {
        setFlash('danger', 'ยังไม่ได้อัปโหลดไฟล์:<ul><li>'
                 . implode('</li><li>', $missing) . '</li></ul>');
    } elseif (!empty($errors)) {
        setFlash('danger', '<ul><li>'
                 . implode('</li><li>', $errors) . '</li></ul>');
    } else {
        setFlash('success', 'อัปโหลดเรียบร้อยแล้ว');
    }
    header('Location: list-contracts.php');
    exit;
}

// อัปเดตคอมมิชชั่นเป็น 'commission_cancelled' ถ้าถูกปฏิเสธ
$pdo->exec("
    UPDATE contracts
    SET commission_status = 'commission_cancelled'
    WHERE approval_status = 'rejected'
      AND commission_status = 'commission_pending'
");

// 2) Fetch contracts (เพิ่ม reject_reason)
$stmt = $pdo->prepare("
  SELECT id,
         contract_no_shop,
         CONCAT(customer_firstname,' ',customer_lastname) AS customer_name,
         loan_amount, installment_amount,
         approval_status, approval_at,
         commission_status, commission_at,
         period_months, start_date, end_date,
         allow_resubmit,
         reject_reason
    FROM contracts
   WHERE shop_id = ?
   ORDER BY id DESC
");
$stmt->execute([ $_SESSION['user']['id'] ]);
$contracts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 2.5) Fetch payments for these contracts
$ids = array_column($contracts, 'id');
$paymentsByContract = [];
if ($ids) {
    $in = implode(',', array_map('intval', $ids));
    $rows = $pdo->query("
      SELECT contract_id, pay_no, due_date, amount_due, status
        FROM payments
       WHERE contract_id IN ({$in})
       ORDER BY contract_id, pay_no
    ")->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as $p) {
        $paymentsByContract[$p['contract_id']][] = $p;
    }
}

// 3) Count evidence per contract
$evidenceCounts = [];
if ($ids) {
  $in = implode(',', $ids);
  $r2 = $pdo->query("
    SELECT contract_id, COUNT(*) AS cnt
      FROM contract_evidence
     WHERE contract_id IN ({$in})
     GROUP BY contract_id
  ");
  foreach ($r2->fetchAll() as $r) {
    $evidenceCounts[$r['contract_id']] = $r['cnt'];
  }
}

// 4) Status maps
$approvalMap = [
  'pending'  => ['รอการอนุมัติ','fa-spinner fa-spin','badge bg-warning text-dark'],
  'approved' => ['อนุมัติแล้ว','fa-check-circle','badge bg-success'],
  'rejected' => ['ไม่อนุมัติ','fa-times-circle','badge bg-danger']
];
$commissionMap = [
  'commission_pending'     => ['รอโอนคอมมิชชั่น','fa-spinner fa-spin','badge bg-info text-dark'],
  'commission_transferred' => ['โอนคอมมิชชั่นแล้ว','fa-check-circle','badge bg-success'],
  'commission_cancelled'   => ['ยกเลิก','fa-ban','badge bg-secondary']
];

$pageTitle = 'รายการสัญญา';
include ROOT_PATH.'/public/includes/header.php';
include ROOT_PATH.'/public/includes/sidebar.php';
?>
<?php
$fields = [
    'signed_contract'   => 'ใบเซ็นต์สัญญา',
    'customer_photo'    => 'รูปภาพลูกค้า',
    'device_photo'      => 'รูปภาพตัวเครื่อง',
    'imei_photo'        => 'รูปภาพ IMEI',
    'lock_screen_photo' => 'รูปภาพหน้าล็อค'
];
?>
<link rel="stylesheet" href="<?=htmlspecialchars($baseURL)?>/assets/css/dashboard.css">
<style>
#contractsTable th,
#contractsTable td {
    font-size: 0.75rem;
    padding: 0.25rem 0.4rem;
}

:root {
    --btn-grad: linear-gradient(135deg, #3e6ae1 0%, #2f52c6 100%);
}

[data-theme="dark"] {
    --btn-grad: linear-gradient(135deg, #746bff 0%, #1f1cff 100%);
}

/* พื้นหลัง modal */
[data-theme="dark"] .modal-content {
    background-color: #1e1e2f;
    color: #ffffff;
}

[data-theme="light"] .modal-content .btn-primary {
    background: linear-gradient(135deg, #3e6ae1 0%, #2f52c6 100%);
    color: #000000;
}

/* ปรับ label + input */
.modal-content .form-label {
    font-weight: 500;
}

.modal-content .form-control {
    background-color: var(--input-bg);
    color: var(--text-main);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

[data-theme="light"] .modal-content .form-control {
    background-color: #f8f9fa;
    color: #000;
    border: 1px solid #ced4da;
}

/* ปุ่ม */
.modal-content .btn-primary {
    background: var(--btn-grad);
    border: none;
}

/* Card แสดงรูป */
.modal-content .card {
    background-color: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
}

[data-theme="light"] .modal-content .card {
    background-color: #f0f0f0;
    border: 1px solid #ddd;
}

.modal-content,
.card {
    border-radius: 1rem;
}

.lightbox-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.85);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1200;
    flex-direction: column;
    padding: 1rem;
}

.lightbox-overlay img {
    max-height: 80vh;
    object-fit: contain;
}

.lightbox-overlay .btn {
    z-index: 9999;
}

.lightbox-overlay .lightbox-caption {
    text-align: center;
}

/* Text-muted ให้ชัดเจนขึ้นใน dark mode */
[data-theme="dark"] .text-muted {
    color: #aaa !important;
}
</style>

<main class="main-content">
    <?php if($m=getFlash('success')):?>
    <div class="position-fixed top-0 end-0 p-3" style="z-index:1100">
        <div id="toastSuccess" class="toast align-items-center text-white bg-success border-0" role="alert"
            data-bs-delay="3000">
            <div class="d-flex">
                <div class="toast-body"><?=$m?></div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    </div>
    <?php endif?>
    <?php if($m=getFlash('danger')):?>
    <div class="position-fixed top-0 end-0 p-3" style="z-index:1100">
        <div id="toastError" class="toast align-items-center text-white bg-danger border-0" role="alert"
            data-bs-delay="3000">
            <div class="d-flex">
                <div class="toast-body"><?=$m?></div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    </div>
    <?php endif?>

    <header class="app-header d-flex align-items-center justify-content-between">
        <h2 class="header-title m-0">
            <i class="fa-solid fa-list-check me-2"></i><?=htmlspecialchars($pageTitle)?>
        </h2>
        <div class="header-actions d-flex align-items-center">
            <a href="<?=$baseURL?>/pages/shop/contract/new-contract.php" class="btn btn-sm btn-primary me-2">
                <i class="fa-solid fa-plus me-1"></i>สร้างสัญญาใหม่
            </a>
            <button id="sidebarToggle" class="btn-icon"><i class="fa-solid fa-bars"></i></button>
            <button id="themeToggle" class="btn-icon ms-2"><i id="themeIcon" class="fa-solid"></i></button>
        </div>
    </header>
    <hr>

    <div class="mb-3">
        <h5><i class="fa-solid fa-file-contract me-2"></i>จำนวนสัญญา <?=count($contracts)?> รายการ</h5>
    </div>
    <div class="mb-3">
        <input id="searchInput" class="form-control" placeholder="ค้นหา เลขที่สัญญา, ชื่อลูกค้า...">
    </div>

    <div class="table-responsive">
        <table id="contractsTable" class="table mb-0 text-center align-middle">
            <thead class="table-light">
                <tr>
                    <th>ลำดับ</th>
                    <th>เลขที่สัญญา</th>
                    <th>ชื่อลูกค้า</th>
                    <th>ยอดเงินกู้</th>
                    <th>ยอดผ่อน/งวด</th>
                    <th>สถานะอนุมัติ</th>
                    <th>สถานะคอมมิชชั่น</th>
                    <th>หลักฐาน</th>
                    <th>ระยะเวลา</th>
                    <th>วันที่เริ่ม</th>
                    <th>วันที่สิ้นสุด</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($contracts as $i => $ctr):
                    $fmt = fn($dt)=>(new DateTime($dt,new DateTimeZone('Asia/Bangkok')))
                                ->format('d/m/'.((new DateTime($dt))->format('Y')+543).' H:i');
                    [$aText,$aIcon,$aCls] = $approvalMap[$ctr['approval_status']]   ?? ['-','','badge bg-secondary'];
                    [$cText,$cIcon,$cCls] = $commissionMap[$ctr['commission_status']] ?? ['-','','badge bg-secondary'];
                    $cnt = $evidenceCounts[$ctr['id']] ?? 0;
                    $rejectReason = trim($ctr['reject_reason'] ?? '');
                ?>
                <tr data-contract="<?=strtolower($ctr['contract_no_shop'])?>"
                    data-customer="<?=htmlspecialchars(strtolower($ctr['customer_name']))?>">
                    <td><?=$i+1?></td>
                    <td><?=htmlspecialchars($ctr['contract_no_shop'])?></td>
                    <td><?=htmlspecialchars($ctr['customer_name'])?></td>
                    <td class="text-end"><?=number_format($ctr['loan_amount'],2)?> ฿</td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal"
                            data-bs-target="#scheduleModal-<?= $ctr['id'] ?>" title="ดูตารางผ่อน">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                    </td>

                    <!-- สถานะอนุมัติ -->
                    <td title="<?= $ctr['approval_at'] ? $fmt($ctr['approval_at']) : 'ยังไม่อนุมัติ' ?>">
                        <div>
                            <span class="<?= $aCls ?>">
                                <?php if($aIcon): ?><i class="fa-solid <?= $aIcon ?> me-1"></i><?php endif; ?>
                                <?= $aText ?>
                            </span>
                        </div>
                        <?php if ($ctr['approval_status'] === 'rejected'): ?>
                        <?php if ((int)$ctr['allow_resubmit'] === 1): ?>
                        <small class="text-success">สามารถส่งใหม่ได้</small>
                        <?php else: ?>
                        <small class="text-muted">ไม่สามารถส่งใหม่ได้</small>
                        <?php endif; ?>
                        <?php endif; ?>
                    </td>

                    <!-- สถานะคอมมิชชั่น -->
                    <td title="<?= $ctr['commission_at'] ? $fmt($ctr['commission_at']) : 'ยังไม่ถึงเวลาโอน' ?>">
                        <span class="<?= $cCls ?>">
                            <?php if($cIcon): ?><i class="fa-solid <?= $cIcon ?> me-1"></i><?php endif; ?>
                            <?= $cText ?>
                        </span>
                    </td>

                    <!-- หลักฐาน -->
                    <td>
                        <?php if ($cnt > 0): ?>
                        <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal"
                            data-bs-target="#evidenceModal-<?=$ctr['id']?>">
                            ดูหลักฐาน (<?=$cnt?>)
                        </button>
                        <?php else: ?>
                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal"
                            data-bs-target="#evidenceModal-<?=$ctr['id']?>">
                            อัปโหลด
                        </button>
                        <?php endif; ?>
                    </td>

                    <td><?=htmlspecialchars($ctr['period_months'])?> งวด</td>
                    <td><?=$fmt($ctr['start_date'])?></td>
                    <td><?=$fmt($ctr['end_date'])?></td>

                    <!-- Action -->
                    <td class="text-nowrap">
                        <!-- ปุ่มพิมพ์ -->
                        <a href="print-contract.php?contract_id=<?= $ctr['id'] ?>"
                            class="btn btn-sm btn-outline-dark me-1" title="พิมพ์สัญญา" target="_blank">
                            <i class="fa-solid fa-print"></i>
                        </a>

                        <?php if ($ctr['approval_status'] === 'approved'): ?>
                        <!-- ถ้าอนุมัติแล้ว -->
                        <button class="btn btn-sm btn-secondary" title="ไม่สามารถแก้ไขหลังอนุมัติ" disabled>
                            <i class="fa-solid fa-lock"></i>
                        </button>

                        <?php elseif ($ctr['approval_status'] === 'rejected'): ?>
                        <!-- ถ้าถูกปฏิเสธ -->
                        <!-- 1) ปุ่มดูเหตุผล -->
                        <button class="btn btn-sm btn-warning me-1" data-bs-toggle="modal"
                            data-bs-target="#reasonModal-<?= $ctr['id'] ?>" title="เหตุผลที่ปฏิเสธ">
                            <i class="fa-solid fa-comment-dots me-1"></i>เหตุผล
                        </button>

                        <?php if ((int)$ctr['allow_resubmit'] === 1): ?>
                        <!-- 2) ปุ่มแก้ไข (เฉพาะเมื่อ allow_resubmit=1) -->
                        <a href="edit_contract.php?id=<?= $ctr['id'] ?>" class="btn btn-sm btn-outline-primary me-1"
                            title="แก้ไขสัญญา">
                            <i class="fa-solid fa-pen-to-square"></i>
                        </a>
                        <small class="text-success d-block mt-1">สามารถส่งใหม่ได้</small>
                        <?php else: ?>
                        <small class="text-muted d-block mt-1">ไม่สามารถส่งใหม่ได้</small>
                        <?php endif; ?>

                        <?php else: /* pending */ ?>
                        <!-- ถ้ายังรออนุมัติ -->
                        <button class="btn btn-sm btn-outline-primary" onclick="alert('กรุณารอการอนุมัติ');">
                            <i class="fa-solid fa-hourglass-half"></i>
                        </button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Modal ตารางผ่อนชำระ -->
    <?php foreach($contracts as $ctr): ?>
    <div class="modal fade" id="scheduleModal-<?= $ctr['id'] ?>" tabindex="-1"
        aria-labelledby="scheduleModalLabel-<?= $ctr['id'] ?>" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="scheduleModalLabel-<?= $ctr['id'] ?>">ตารางผ่อนชำระ
                        <?= htmlspecialchars($ctr['contract_no_shop']) ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="ปิด"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-bordered mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-center">งวด</th>
                                    <th class="text-center">ครบกำหนด</th>
                                    <th class="text-end">ยอดผ่อน (บาท)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($paymentsByContract[$ctr['id']] ?? [] as $p):
                                    $due = (new DateTime($p['due_date'], new DateTimeZone('Asia/Bangkok')))->format('d/m/Y');
                                ?>
                                <tr>
                                    <td class="text-center"><?= intval($p['pay_no']) ?></td>
                                    <td class="text-center"><?= $due ?></td>
                                    <td class="text-end"><?= number_format($p['amount_due'],2) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

    <!-- Pagination -->
    <nav id="pagination" class="mt-3"></nav>

    <!-- Modal หลักฐาน -->
    <?php foreach($contracts as $ctr): ?>
    <div class="modal fade" id="evidenceModal-<?=$ctr['id']?>" tabindex="-1"
        aria-labelledby="evidenceModalLabel<?=$ctr['id']?>" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="evidenceModalLabel<?=$ctr['id']?>">
                        <i class="fa-solid fa-folder-open me-2"></i>หลักฐานสัญญา
                        <?=htmlspecialchars($ctr['contract_no_shop'])?>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <?php if ($ctr['approval_status'] !== 'approved'): ?>
                    <form class="evidence-form" method="post" enctype="multipart/form-data">
                        <input type="hidden" name="evidence_contract_id" value="<?=$ctr['id']?>">
                        <div class="row g-3">
                            <?php foreach($fields as $field => $label): ?>
                            <div class="col-md-6">
                                <label class="form-label"><?=$label?><?php if($field==='signed_contract'): ?><small
                                        class="text-muted">(อัปโหลดได้หลายไฟล์)</small><?php endif; ?></label>
                                <?php if($field==='signed_contract'): ?>
                                <input type="file" name="<?= $field ?>[]" class="form-control" multiple>
                                <?php else: ?>
                                <input type="file" name="<?= $field ?>" class="form-control">
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="mt-3 text-end">
                            <button type="submit" class="btn btn-primary save-btn">บันทึก</button>
                        </div>
                    </form>
                    <?php else: ?>
                    <div class="alert alert-secondary">
                        <i class="fa-solid fa-lock me-1"></i> ไม่สามารถแก้ไขหลักฐานหลังอนุมัติได้
                    </div>
                    <?php endif; ?>

                    <hr>

                    <?php
                    $r3 = $pdo->prepare("
                      SELECT id, evidence_type, file_path, uploaded_at
                        FROM contract_evidence
                       WHERE contract_id = ?
                       ORDER BY uploaded_at DESC
                    ");
                    $r3->execute([$ctr['id']]);
                    $files = $r3->fetchAll();
                    ?>
                    <?php if($files): ?>
                    <h6 class="mb-3"><i class="fa-solid fa-images me-2"></i>ไฟล์ที่เคยอัปโหลด</h6>
                    <div class="row g-3">
                        <?php foreach($files as $f): ?>
                        <div class="col-md-4">
                            <div class="card shadow-sm border-0">
                                <a href="#" class="evidence-lightbox"
                                    data-url="<?= $baseURL . '/' . htmlspecialchars($f['file_path'])?>"
                                    data-label="<?= htmlspecialchars($fields[$f['evidence_type']]) ?>">
                                    <img src="<?= $baseURL . '/' . htmlspecialchars($f['file_path'])?>"
                                        class="card-img-top" alt="Evidence">
                                </a>

                                <div class="card-body py-2 px-3">
                                    <small
                                        class="text-muted d-block mb-1"><?= htmlspecialchars($fields[$f['evidence_type']]) ?></small>
                                    <small
                                        class="text-muted"><?= (new DateTime($f['uploaded_at']))->format('d/m/Y H:i') ?></small>

                                    <?php if ($ctr['approval_status'] !== 'approved'): ?>
                                    <form method="post" action="delete-evidence.php"
                                        onsubmit="return confirm('ยืนยันลบไฟล์นี้?')" class="mt-2">
                                        <input type="hidden" name="id" value="<?= $f['id'] ?>">
                                        <input type="hidden" name="contract_id" value="<?= $ctr['id'] ?>">
                                        <button class="btn btn-sm btn-outline-danger w-100" type="submit">
                                            <i class="fa-solid fa-trash-can me-1"></i> ลบ
                                        </button>
                                    </form>
                                    <?php else: ?>
                                    <button class="btn btn-sm btn-secondary mt-2 w-100" disabled>
                                        <i class="fa-solid fa-lock me-1"></i> ไม่สามารถลบได้
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

    <!-- Modal แสดง “เหตุผลที่ปฏิเสธ” -->
    <?php foreach($contracts as $ctr): 
        $rejectReason = trim($ctr['reject_reason'] ?? '');
    ?>
    <div class="modal fade" id="reasonModal-<?= $ctr['id'] ?>" tabindex="-1"
        aria-labelledby="reasonModalLabel-<?= $ctr['id'] ?>" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="reasonModalLabel-<?= $ctr['id'] ?>">เหตุผลที่ปฏิเสธสัญญา
                        #<?= htmlspecialchars($ctr['contract_no_shop']) ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <?php if ($rejectReason !== ''): ?>
                    <p><?= nl2br(htmlspecialchars($rejectReason)) ?></p>
                    <?php else: ?>
                    <p class="text-muted">ไม่ได้ระบุเหตุผล</p>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">ปิด</button>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

    <!-- Lightbox Overlay -->
    <div id="lightboxOverlay" class="lightbox-overlay d-none">
        <button type="button" class="btn btn-close btn-close-white position-absolute top-0 end-0 m-3" aria-label="Close"
            onclick="closeLightbox()"></button>
        <div id="lightboxContent" class="text-center"></div>
        <div class="lightbox-caption text-white mt-3" id="lightboxCaption"></div>
        <button type="button" class="btn btn-sm btn-light mt-3" onclick="closeLightbox()">ปิด</button>
    </div>
</main>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
function closeLightbox() {
    const ovr = document.getElementById('lightboxOverlay');
    const cont = document.getElementById('lightboxContent');
    const cap = document.getElementById('lightboxCaption');
    ovr.classList.add('d-none');
    cont.innerHTML = '';
    cap.textContent = '';
}

document.addEventListener('DOMContentLoaded', () => {
    // ✅ Toast
    document.querySelectorAll('.toast').forEach(el => {
        const toast = bootstrap.Toast.getOrCreateInstance(el);
        if (el.id === 'toastSuccess' || el.id === 'toastError') {
            toast.show();
        }
    });

    // ✅ Submit Spinner + Confirm
    document.querySelectorAll('.evidence-form').forEach(form => {
        form.addEventListener('submit', (e) => {
            if (!confirm("คุณต้องการบันทึกไฟล์หลักฐานใช่หรือไม่?")) {
                e.preventDefault();
                return;
            }

            const btn = form.querySelector('.save-btn');
            if (btn) {
                btn.disabled = true;
                btn.innerHTML =
                    '<span class="spinner-border spinner-border-sm me-2"></span>กำลังบันทึก';
            }
        });
    });

    // ✅ Lightbox
    document.querySelectorAll('.evidence-lightbox').forEach(el => {
        el.addEventListener('click', e => {
            e.preventDefault();
            const url = el.getAttribute('data-url');
            const label = el.getAttribute('data-label') || '';
            const ovr = document.getElementById('lightboxOverlay');
            const cont = document.getElementById('lightboxContent');
            const caption = document.getElementById('lightboxCaption');

            if (/\.pdf$/i.test(url)) {
                cont.innerHTML =
                    `<iframe src="${url}" style="width:100%;height:80vh;border:none"></iframe>`;
            } else {
                cont.innerHTML = `<img src="${url}" class="img-fluid rounded">`;
            }

            caption.textContent = label;
            ovr.classList.remove('d-none');
        });
    });

    // ✅ ESC to close lightbox
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') closeLightbox();
    });

    // ✅ Search + Pagination
    const $tbody = $('#contractsTable tbody');
    const $rows = $tbody.find('tr');
    const $searchInput = $('#searchInput');
    const $pagination = $('#pagination');
    const itemsPerPage = 20;
    let filteredRows = $rows;
    let currentPage = 1;

    function renderTable() {
        $tbody.empty();
        const start = (currentPage - 1) * itemsPerPage;
        const end = start + itemsPerPage;

        filteredRows.slice(start, end).each(function(idx) {
            $(this).find('td:first').text(start + idx + 1);
            $tbody.append(this);
        });
        renderPagination();
    }

    function renderPagination() {
        $pagination.empty();
        const totalPages = Math.ceil(filteredRows.length / itemsPerPage);
        if (totalPages <= 1) return;

        const $ul = $('<ul class="pagination"></ul>');

        const addPage = (num, label = num, disabled = false, active = false) => {
            const $li = $('<li class="page-item"></li>');
            if (disabled) $li.addClass('disabled');
            if (active) $li.addClass('active');
            $li.append(`<a class="page-link" href="#">${label}</a>`).on('click', 'a', e => {
                e.preventDefault();
                if (!disabled && currentPage !== num) {
                    currentPage = num;
                    renderTable();
                }
            });
            $ul.append($li);
        };

        addPage(currentPage - 1, '«', currentPage === 1);
        for (let i = 1; i <= totalPages; i++) addPage(i, i, false, i === currentPage);
        addPage(currentPage + 1, '»', currentPage === totalPages);
        $pagination.append($ul);
    }

    function filterRows() {
        const q = $searchInput.val().toLowerCase().trim();
        filteredRows = $rows.filter(function() {
            const contract = $(this).data('contract') || '';
            const customer = $(this).data('customer') || '';
            return contract.includes(q) || customer.includes(q);
        });
        currentPage = 1;
        renderTable();
    }

    $searchInput.on('input', filterRows);
    renderTable();
});
</script>
<script src="<?= htmlspecialchars($baseURL) ?>/assets/js/lock-evidence-upload.js"></script>

<?php include ROOT_PATH.'/public/includes/footer.php';?>