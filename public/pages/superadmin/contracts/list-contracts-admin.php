<?php
// File: public/pages/superadmin/contracts/list-contracts-admin.php

// 1) Load bootstrap & helpers
require_once realpath(__DIR__ . '/../../../../config/bootstrap.php');
require_once ROOT_PATH . '/includes/helpers.php';
if (session_status() === PHP_SESSION_NONE) session_start();
requireRole(['superadmin', 'admin']);

// 2) Connect to DB
require_once ROOT_PATH . '/config/db.php';
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// if (!hasPermission($pdo, $_SESSION['user']['id'], 'view_payments')) {
//     setFlash('error', 'คุณไม่มีสิทธิ์ดูข้อมูลการชำระเงิน');
//     header("Location: {$baseURL}/pages/superadmin/dashboard.php");
//     exit;
// }

if (!hasPermission($pdo, $_SESSION['user']['id'], 'view_payments')) {
    require ROOT_PATH . '/public/includes/permission_denied_modal.php';
    exit;
}



// 3) Fetch all contracts (ทุกร้านค้า)
// Note: our contracts table does not have approval_at or commission_at columns.
// We will use contract_update_at for approval timestamp and commission_transferred_at for commission timestamp.
$stmt = $pdo->query("
    SELECT c.*, u.name
      FROM contracts c
      JOIN users u ON u.id = c.shop_id
     ORDER BY c.created_at DESC
");
$contracts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ----- เพิ่มส่วนดึงข้อมูลหลักฐานจาก contract_evidence -----
$contractIds = array_column($contracts, 'id');
if (count($contractIds) > 0) {
    $placeholders = implode(',', array_fill(0, count($contractIds), '?'));
    $stmtE = $pdo->prepare("
        SELECT contract_id, evidence_type, file_path
          FROM contract_evidence
         WHERE contract_id IN ($placeholders)
    ");
    $stmtE->execute($contractIds);
    $rows = $stmtE->fetchAll(PDO::FETCH_ASSOC);

    $evidenceByContract = [];
    foreach ($rows as $r) {
        $cid   = (int)$r['contract_id'];
        $etype = $r['evidence_type'];
        $path  = $r['file_path'];
        $evidenceByContract[$cid][$etype][] = $path;
    }
} else {
    $evidenceByContract = [];
}

// 4) Status maps
$approvalMap = [
    'pending'  => ['รอการอนุมัติ',      'badge bg-warning text-dark'],
    'approved' => ['อนุมัติแล้ว',       'badge bg-success'],
    'rejected' => ['ไม่อนุมัติ',       'badge bg-danger'],
];
$commissionMap = [
    'commission_pending'     => ['รอโอนคอมมิชชั่น',     'badge bg-info text-dark'],
    'commission_transferred' => ['โอนคอมมิชชั่นแล้ว',   'badge bg-success'],
    'commission_cancelled'   => ['ยกเลิก',               'badge bg-secondary'],
];

// Helper: หาสถานะผ่อนชำระของสัญญา (เดิม unchanged)
function getRepaymentStatus(PDO $pdo, int $contractId): string {
    date_default_timezone_set('Asia/Bangkok');
    $today = date('Y-m-d');

    $stmtTotal = $pdo->prepare("
        SELECT COUNT(*) AS total
          FROM payments
         WHERE contract_id = ?
    ");
    $stmtTotal->execute([$contractId]);
    $total = (int)$stmtTotal->fetchColumn();

    $stmtPaid = $pdo->prepare("
        SELECT COUNT(*) AS paid
          FROM payments
         WHERE contract_id = ?
           AND status <> 'pending'
    ");
    $stmtPaid->execute([$contractId]);
    $paid = (int)$stmtPaid->fetchColumn();

    if ($paid >= $total && $total > 0) {
        return 'จ่ายครบแล้ว';
    }

    $stmtOver = $pdo->prepare("
        SELECT COUNT(*) AS overdue
          FROM payments
         WHERE contract_id = ?
           AND status = 'pending'
           AND due_date < ?
    ");
    $stmtOver->execute([$contractId, $today]);
    $overdue = (int)$stmtOver->fetchColumn();
    if ($overdue > 0) {
        return 'เกินกำหนด';
    }

    $stmtPending = $pdo->prepare("
        SELECT COUNT(*) AS pendingCount
          FROM payments
         WHERE contract_id = ?
           AND status = 'pending'
           AND due_date >= ?
    ");
    $stmtPending->execute([$contractId, $today]);
    $pendingCount = (int)$stmtPending->fetchColumn();
    if ($pendingCount > 0) {
        return 'กำลังผ่อน/รอเก็บเงิน';
    }

    return 'จ่ายครบแล้ว';
}
define('APPROVAL_EXPIRE_SECONDS', 48 * 3600);

$pageTitle = 'จัดการสัญญา (SuperAdmin)';
include ROOT_PATH . '/public/includes/header.php';
include ROOT_PATH . '/public/includes/sidebar.php';
?>

<link rel="stylesheet" href="<?= htmlspecialchars($baseURL) ?>/assets/css/dashboard.css">
<style>
/* ลดขนาดฟอนต์เฉพาะตาราง adminContractsTable */
#adminContractsTable th,
#adminContractsTable td {
    font-size: 0.85rem;
    padding: 0.275rem 0.65rem;
}

#adminContractsTable_wrapper .dataTables_length,
#adminContractsTable_wrapper .dataTables_filter {
    margin-bottom: 1rem;
}

/* ปรับสี Modal ตามธีม light/dark */
[data-theme="dark"] .modal-content {
    background-color: #343a40;
    color: #e9ecef;
}

[data-theme="dark"] .modal-header,
[data-theme="dark"] .modal-footer {
    background-color: #343a40;
    color: #e9ecef;
    border-color: #454d55;
}

[data-theme="dark"] .modal-content {
    border: 1px solid #454d55;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.5);
}

/* Thumbnail สลิปคอมมิชชั่น | หลักฐานรูปภาพ */
.evidence-thumb {
    max-height: 100px;
    cursor: pointer;
    border: 1px solid #ddd;
    border-radius: 4px;
    transition: transform .2s;
}

.evidence-thumb:hover {
    transform: scale(1.05);
}

/* กำหนดขนาด Modal แบบกว้างและสูงพอเหมาะ */
.modal-xl {
    max-width: 90vw;
    /* กว้างสุด 90% ของความกว้างจอ */
}

.iframe-preview {
    width: 100%;
    /* กว้างเต็ม Modal */
    height: 80vh;
    /* สูงประมาณ 80% ของ viewport */
    border: none;
}
</style>

<main class="main-content">
    <div class="container-fluid py-4">
        <!-- <?php displayFlash(); ?> -->

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="m-0">
                <i class="fa-solid fa-list-check me-2"></i>
                <?= htmlspecialchars($pageTitle) ?>
            </h3>
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

        <!-- ฟิลเตอร์สถานะ -->
        <div class="row mb-3">
            <div class="col-sm-6 col-md-3">
                <label class="form-label">สถานะอนุมัติ</label>
                <select id="filterApproval" class="form-select form-select-sm">
                    <option value="">ทั้งหมด</option>
                    <option value="รอการอนุมัติ">รอการอนุมัติ</option>
                    <option value="อนุมัติแล้ว">อนุมัติแล้ว</option>
                    <option value="ไม่อนุมัติ">ไม่อนุมัติ</option>
                </select>
            </div>
            <div class="col-sm-6 col-md-3">
                <label class="form-label">สถานะคอมมิชชั่น</label>
                <select id="filterCommission" class="form-select form-select-sm">
                    <option value="">ทั้งหมด</option>
                    <option value="รอโอนคอมมิชชั่น">รอโอนคอมมิชชั่น</option>
                    <option value="โอนคอมมิชชั่นแล้ว">โอนคอมมิชชั่นแล้ว</option>
                    <option value="ยกเลิก">ยกเลิก</option>
                </select>
            </div>
            <div class="col-sm-6 col-md-3">
                <label class="form-label">สถานะผ่อนชำระ</label>
                <select id="filterRepayment" class="form-select form-select-sm">
                    <option value="">ทั้งหมด</option>
                    <option value="กำลังผ่อน/รอเก็บเงิน">กำลังผ่อน/รอเก็บเงิน</option>
                    <option value="เกินกำหนด">เกินกำหนด</option>
                    <option value="จ่ายครบแล้ว">จ่ายครบแล้ว</option>
                </select>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-sm align-middle small" id="adminContractsTable">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>ร้านค้า</th>
                        <th>เลขที่สัญญา</th>
                        <th>ลูกค้า</th>
                        <th class="text-end">ยอดกู้ (฿)</th>
                        <th>สถานะอนุมัติ</th>
                        <th>สถานะคอมมิชชั่น</th>
                        <th>สถานะผ่อนชำระ</th>
                        <th>ดูหลักฐาน</th>
                        <th>ดูสัญญา</th>
                        <th>ล็อคด้วย</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
    // 1. (ส่วนที่เพิ่ม) นับจำนวนสัญญาทั้งหมดก่อนเริ่ม loop
    $totalContracts = count($contracts); 

    foreach ($contracts as $index => $c):
        // 1. ตรวจ expiration
        $created = new DateTime($c['created_at']);
        $now     = new DateTime();
        $diffSec = $now->getTimestamp() - $created->getTimestamp();
        $isExpired = ($c['approval_status'] === 'pending' && $diffSec > APPROVAL_EXPIRE_SECONDS);

        // สถานะการอนุมัติ + การคอมมิชชั่น
        [$aText, $aCls]  = $approvalMap[$c['approval_status']];
        [$cmText, $cmCls] = $commissionMap[$c['commission_status']];

        // สถานะผ่อนชำระ
        $repaymentStatus = ($c['approval_status'] === 'approved') ? getRepaymentStatus($pdo, (int)$c['id']) : '-';
        $repBadgeClass = [
            'กำลังผ่อน/รอเก็บเงิน' => 'badge bg-primary',
            'เกินกำหนด'           => 'badge bg-danger',
            'จ่ายครบแล้ว'         => 'badge bg-success'
        ][$repaymentStatus] ?? 'badge bg-secondary';

        // ตรวจว่าโอนคอมมิชชั่นแล้วหรือไม่
        $contractDone = ($c['commission_status'] === 'commission_transferred');
        
        // สลิปคอมมิชชั่น
        $slipPath = $c['commission_slip_path'] ?? '';
        $fullSlipURL = htmlspecialchars($baseURL . '/' . $slipPath);

        // หลักฐานสัญญา
        $evidenceFields = [
            'signed_contract'   => 'สัญญาที่เซ็น',
            'customer_photo'    => 'รูปภาพลูกค้า',
            'device_photo'      => 'รูปภาพตัวเครื่อง',
            'imei_photo'        => 'รูปภาพ IMEI',
            'lock_screen_photo' => 'รูปภาพหน้าล็อค'
        ];
        $evThis = $evidenceByContract[$c['id']] ?? [];
        
        // ดึงค่า allow_resubmit
        $allowResubmit = (int)$c['allow_resubmit'];
    ?>
                    <tr>
                        <td><?= $totalContracts - $index ?></td>

                        <td><?= htmlspecialchars($c['name']) ?></td>
                        <td>
                            <?= htmlspecialchars($c['contract_no_shop']) ?>
                            <?php if ($contractDone): ?>
                            <i class="fa-solid fa-check-circle text-success ms-1" title="สัญญานี้จบแล้ว"></i>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($c['customer_firstname'] . ' ' . $c['customer_lastname']) ?></td>
                        <td class="text-end">
                            <?= number_format($c['loan_amount'], 2) ?>
                            (<?= intval($c['period_months']) ?> งวด)
                        </td>
                        <td>
                            <?php if ($c['approval_status'] === 'pending' && $isExpired): ?>
                            <span class="badge bg-secondary text-dark">Expired</span>
                            <?php elseif ($c['approval_status'] === 'pending'): ?>
                            <span class="<?= htmlspecialchars($aCls) ?>">
                                <i class="fa-solid fa-spinner fa-spin me-1"></i>
                                <?= htmlspecialchars($aText) ?>
                            </span>
                            <?php else: ?>
                            <?= renderBadge($aText, $aCls) ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($c['commission_status'] === 'commission_pending' && $c['approval_status'] === 'approved'): ?>
                            <span class="<?= htmlspecialchars($cmCls) ?>">
                                <i class="fa-solid fa-spinner fa-spin me-1"></i>
                                <?= htmlspecialchars($cmText) ?>
                            </span>
                            <?php else: ?>
                            <?= renderBadge($cmText, $cmCls) ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($repaymentStatus === 'กำลังผ่อน/รอเก็บเงิน'): ?>
                            <span class="<?= $repBadgeClass ?>">
                                <i class="fa-solid fa-spinner fa-spin me-1"></i>
                                <?= htmlspecialchars($repaymentStatus) ?>
                            </span>
                            <?php else: ?>
                            <span class="<?= $repBadgeClass ?>"><?= htmlspecialchars($repaymentStatus) ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal"
                                data-bs-target="#evidenceModal<?= $c['id'] ?>">
                                <i class="fa-solid fa-paperclip"></i>
                            </button>
                        </td>
                        <td class="text-center">
                            <div class="btn-group" role="group" aria-label="ดูสัญญา">
                                <a href="view-contract-admin.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-outline-info"
                                    title="แก้ไขสัญญา">
                                    <i class="fa-solid fa-file-signature"></i>
                                </a>
                                <button type="button" class="btn btn-sm btn-outline-info btn-preview-contract"
                                    data-contract-id="<?= $c['id'] ?>" title="พรีวิวสัญญา">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                            </div>
                        </td>
                        <td>
                            <?php if (!empty($c['icloud_email'])): ?>
                            <button class="btn btn-sm btn-outline-dark btn-lock"
                                style="--bs-btn-color: var(--color-text-secondary); --bs-btn-border-color: var(--color-text-secondary);"
                                data-lock-type="Apple ID"
                                data-lock-val="<?= htmlspecialchars($c['icloud_email']) ?>">Apple ID</button>
                            <?php elseif (!empty($c['mdm_reference'])): ?>
                            <button class="btn btn-sm btn-outline-secondary btn-lock"
                                style="--bs-btn-color: var(--color-text-secondary); --bs-btn-border-color: var(--color-text-secondary);"
                                data-lock-type="MDM"
                                data-lock-val="<?= htmlspecialchars($c['mdm_reference']) ?>">MDM</button>
                            <?php else: ?>
                            <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($c['approval_status'] === 'pending' && !$isExpired): ?>
                            <button class="btn btn-sm btn-outline-success me-1" data-bs-toggle="modal"
                                data-bs-target="#approveModal-<?= $c['id'] ?>">อนุมัติ</button>
                            <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal"
                                data-bs-target="#rejectModal-<?= $c['id'] ?>">ปฏิเสธ</button>
                            <?php elseif ($c['approval_status'] === 'approved' && $c['commission_status'] === 'commission_pending'): ?>
                            <button class="btn btn-sm btn-primary" data-bs-toggle="modal"
                                data-bs-target="#commissionModal-<?= $c['id'] ?>">แจ้งโอนคอมมิชชั่น</button>
                            <?php elseif ($c['commission_status'] === 'commission_transferred'): ?>
                            <?php if (!empty($slipPath)): ?>
                            <i class="fa-regular fa-eye me-1 fs-5 evidence-thumb"
                                style="cursor:pointer; border:none; background:transparent;"
                                data-src="<?= $fullSlipURL ?>" title="คลิกเพื่อดูสลิป"></i>
                            <?php endif; ?>
                            <i class="fa-solid fa-circle-check text-success fs-5" title="สัญญาสำเร็จ"></i>
                            <?php else: ?>
                            <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>

                <!-- PREVIEW CONTRACT MODAL -->
                <div class="modal fade" id="previewContractModal" tabindex="-1" aria-labelledby="previewContractLabel"
                    aria-hidden="true">
                    <div class="modal-dialog modal-xl modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">พรีวิวสัญญา</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                    aria-label="ปิด"></button>
                            </div>
                            <div class="modal-body p-0">
                                <!-- iframe จะแสดงผล print-contract.php -->
                                <iframe id="contractIframe" class="iframe-preview" src="" allowfullscreen>
                                </iframe>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary btn-sm"
                                    data-bs-dismiss="modal">ปิด</button>
                            </div>
                        </div>
                    </div>
                </div>

            </table>

            <!-- Modals ทั้งหมดควรย้ายหลัง </tbody> -->
            <?php foreach ($contracts as $c):
                $evThis = $evidenceByContract[$c['id']] ?? [];
                $evidenceFields = [
                    'signed_contract'   => 'สัญญาที่เซ็น',
                    'customer_photo'    => 'รูปภาพลูกค้า',
                    'device_photo'      => 'รูปภาพตัวเครื่อง',
                    'imei_photo'        => 'รูปภาพ IMEI',
                    'lock_screen_photo' => 'รูปภาพหน้าล็อค'
                ];
            ?>

            <!-- Reject Modal -->
            <div class="modal fade" id="rejectModal-<?= $c['id'] ?>" tabindex="-1"
                aria-labelledby="rejectModalLabel<?= $c['id'] ?>" aria-hidden="true">
                <div class="modal-dialog">
                    <form method="post" action="approve-contract.php" class="needs-validation" novalidate>
                        <input type="hidden" name="contract_id" value="<?= $c['id'] ?>">
                        <input type="hidden" name="action" value="reject">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="rejectModalLabel<?= $c['id'] ?>">
                                    ปฏิเสธสัญญา #<?= htmlspecialchars($c['contract_no_shop']) ?>
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                    aria-label="ปิด"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label for="reject_reason_<?= $c['id'] ?>" class="form-label">
                                        เหตุผลการปฏิเสธ <span class="text-danger">*</span>
                                    </label>
                                    <textarea id="reject_reason_<?= $c['id'] ?>" name="reject_reason"
                                        class="form-control" rows="3" required
                                        placeholder="กรอกเหตุผลที่ปฏิเสธสัญญานี้"></textarea>
                                    <div class="invalid-feedback">กรุณากรอกเหตุผลการปฏิเสธ</div>
                                </div>

                                <!-- ติ๊กส่งกลับให้แก้ไขได้ (เริ่มต้นเช็คไว้) -->
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" name="allow_resubmit"
                                        id="allowResubmit_<?= $c['id'] ?>" value="1" checked>
                                    <label class="form-check-label" for="allowResubmit_<?= $c['id'] ?>">
                                        ส่งกลับให้ร้านค้าแก้ไขและส่งมาใหม่
                                    </label>
                                </div>

                                <p>ยืนยันการปฏิเสธสัญญานี้หรือไม่?</p>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">
                                    ยกเลิก
                                </button>
                                <button type="submit" class="btn btn-danger btn-sm">ยืนยันปฏิเสธ</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Evidence Modal: แก้ไขให้แสดงทุกไฟล์ -->
            <div class="modal fade" id="evidenceModal<?= $c['id'] ?>" tabindex="-1"
                aria-labelledby="evidenceModalLabel<?= $c['id'] ?>" aria-hidden="true">
                <div class="modal-dialog modal-xl modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="evidenceModalLabel<?= $c['id'] ?>">
                                หลักฐานสัญญา #<?= htmlspecialchars($c['contract_no_shop']) ?>
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="ปิด"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row g-3">
                                <?php 
                    // $evThis[$fieldKey] ตอนนี้กลายเป็นอาร์เรย์ของไฟล์แล้ว (หลังแก้ข้อ 1)
                    if (!empty($evThis)):
                        foreach ($evThis as $fieldKey => $paths):
                            // $paths เป็นอาร์เรย์ของ string file paths
                            // label ถ้ามีใน $evidenceFields: ถ้าไม่มีให้ใช้ $fieldKey แทน
                            $label = $evidenceFields[$fieldKey] ?? $fieldKey;

                            // วนดึงทุก $path ในอาร์เรย์ $paths
                            foreach ($paths as $filePath):
                                $imgPath = htmlspecialchars($baseURL . '/' . $filePath);
                    ?>
                                <div class="col-md-4 text-center">
                                    <p class="mb-1 fw-semibold"><?= htmlspecialchars($label) ?></p>
                                    <img src="<?= $imgPath ?>" data-src="<?= $imgPath ?>"
                                        class="img-fluid rounded shadow-sm evidence-thumb"
                                        style="max-height:200px; object-fit:cover; cursor:pointer;"
                                        alt="<?= htmlspecialchars($label) ?>">
                                </div>
                                <?php 
                            endforeach; // จบวนภาพในประเภทนั้น
                        endforeach;   // จบวนประเภททั้งหมด
                    else:
                    ?>
                                <div class="col-12 text-center text-danger">
                                    <p>ยังไม่มีหลักฐานที่อัปโหลดเข้ามาสำหรับสัญญานี้</p>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">
                                ปิด
                            </button>
                        </div>
                    </div>
                </div>
            </div>



            <!-- Close Contract Modal (เฉพาะเมื่อ “จ่ายครบแล้ว”) -->
            <?php if (getRepaymentStatus($pdo, (int)$c['id']) === 'จ่ายครบแล้ว'): ?>
            <div class="modal fade" id="closeModal<?= $c['id'] ?>" tabindex="-1"
                aria-labelledby="closeModalLabel<?= $c['id'] ?>" aria-hidden="true">
                <div class="modal-dialog">
                    <form method="post" action="close-contract.php">
                        <input type="hidden" name="contract_id" value="<?= $c['id'] ?>">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="closeModalLabel<?= $c['id'] ?>">
                                    ปิดสัญญา #<?= htmlspecialchars($c['contract_no_shop']) ?>
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label">กำไร/ขาดทุน (ตัวเลขบวก/ลบ)</label>
                                    <input name="profit_loss" type="number" step="0.01" class="form-control" required
                                        placeholder="เช่น 500.00 หรือ -200.00">
                                </div>
                                <p>เมื่อปิดสัญญา ระบบจะบันทึกข้อมูลสรุปกำไรหรือขาดทุนของสัญญานี้</p>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary btn-sm"
                                    data-bs-dismiss="modal">ยกเลิก</button>
                                <button type="submit" class="btn btn-primary btn-sm">
                                    ปิดสัญญา
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <!-- Approve Modal -->
            <div class="modal fade" id="approveModal-<?= $c['id'] ?>" tabindex="-1">
                <div class="modal-dialog">
                    <form method="post" action="approve-contract.php">
                        <input type="hidden" name="contract_id" value="<?= $c['id'] ?>">
                        <input type="hidden" name="action" value="approve">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">
                                    อนุมัติสัญญา <?= htmlspecialchars($c['contract_no_shop']) ?>
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                คุณแน่ใจจะอนุมัติสัญญานี้หรือไม่?
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary btn-sm"
                                    data-bs-dismiss="modal">ยกเลิก</button>
                                <button type="submit" class="btn btn-success btn-sm">อนุมัติ</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Commission Modal -->
            <div class="modal fade" id="commissionModal-<?= $c['id'] ?>" tabindex="-1">
                <div class="modal-dialog">
                    <form method="post" action="commission-contract.php" enctype="multipart/form-data">
                        <input type="hidden" name="contract_id" value="<?= $c['id'] ?>">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">
                                    ตั้งคอมมิชชั่น <?= htmlspecialchars($c['contract_no_shop']) ?>
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body row g-3">
                                <div class="col-6">
                                    <label class="form-label">จำนวนคอมมิชชั่น (฿)</label>
                                    <input name="commission_amount" type="number" step="0.01" class="form-control"
                                        required>
                                </div>
                                <div class="col-6">
                                    <label class="form-label">วันที่โอน</label>
                                    <input name="commission_transferred_at" type="datetime-local" class="form-control"
                                        required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">อัปโหลดสลิป</label>
                                    <input name="commission_slip" type="file" accept="image/*,.pdf"
                                        class="form-control">
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary btn-sm"
                                    data-bs-dismiss="modal">ยกเลิก</button>
                                <button type="submit" class="btn btn-primary btn-sm">บันทึก</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>

            <?php foreach ($contracts as $c): ?>
            <?php 
        $slipPath = $c['commission_slip_path'] ?? ''; 
        $fullSlipURL = htmlspecialchars($baseURL . '/' . $slipPath);
        // ตรวจนามสกุลไฟล์ว่าเป็นรูปภาพหรือ PDF
        $ext = strtolower(pathinfo($slipPath, PATHINFO_EXTENSION));
    ?>
            <!-- Slip Modal -->
            <div class="modal fade" id="slipModal-<?= $c['id'] ?>" tabindex="-1"
                aria-labelledby="slipModalLabel-<?= $c['id'] ?>" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="slipModalLabel-<?= $c['id'] ?>">
                                สลิปคอมมิชชั่น #<?= htmlspecialchars($c['contract_no_shop']) ?>
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="ปิด"></button>
                        </div>
                        <div class="modal-body text-center">
                            <?php if (!empty($slipPath)): ?>
                            <?php if (in_array($ext, ['jpg','jpeg','png','gif'])): ?>
                            <!-- แสดงรูปภาพ -->
                            <img src="<?= $fullSlipURL ?>" class="img-fluid rounded" alt="Slip">
                            <?php else: ?>
                            <!-- ถ้าเป็น PDF -->
                            <embed src="<?= $fullSlipURL ?>" type="application/pdf" width="100%" height="600px" />
                            <?php endif; ?>
                            <?php else: ?>
                            <p class="text-danger">ไม่มีสลิปคอมมิชชั่นในระบบ</p>
                            <?php endif; ?>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">
                                ปิด
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>


            <!-- Lightbox Modal (สร้างครั้งเดียวด้านล่าง) -->
            <div class="modal fade" id="lightboxModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content bg-transparent border-0">
                        <div class="modal-body p-0 position-relative">
                            <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-2"
                                data-bs-dismiss="modal" aria-label="Close"></button>
                            <img id="lightboxImage" src="" class="img-fluid rounded" alt="Full-size Evidence">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Lock Details Modal -->
            <div class="modal fade" id="lockModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-sm modal-dialog-centered">
                    <div class="modal-content" data-bs-theme="auto">
                        <div class="modal-header">
                            <h5 class="modal-title">รายละเอียดการล็อค</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p><strong>ประเภท:</strong> <span id="lockType"></span></p>
                            <p><strong>ค่า:</strong> <span id="lockVal" style="word-break:break-all;"></span></p>
                        </div>
                        <div class="modal-footer">
                            <button class="btn btn-secondary btn-sm" data-bs-dismiss="modal">ปิด</button>
                        </div>
                    </div>
                </div>
            </div>


            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
            <script>
            document.addEventListener('DOMContentLoaded', () => {
                const lockModalEl = document.getElementById('lockModal');
                const bsLockModal = new bootstrap.Modal(lockModalEl);
                const lockTypeEl = document.getElementById('lockType');
                const lockValEl = document.getElementById('lockVal');

                document.querySelectorAll('.btn-lock').forEach(btn => {
                    btn.addEventListener('click', () => {
                        const type = btn.getAttribute('data-lock-type');
                        const val = btn.getAttribute('data-lock-val');
                        lockTypeEl.textContent = type;
                        lockValEl.textContent = val;
                        bsLockModal.show();
                    });
                });
            });
            </script>

            <script>
            // ===========================
            // Sidebar & Theme Toggle
            // ===========================
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

            // ===========================
            // DataTable + Filters
            // ===========================
            jQuery(function($) {
                if (!$.fn.DataTable.isDataTable('#adminContractsTable')) {
                    var table = $('#adminContractsTable').DataTable({
                        language: {
                            url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/th.json'
                        }
                    });

                    $('#filterApproval').on('change', function() {
                        table.column(5).search(this.value).draw();
                    });
                    $('#filterCommission').on('change', function() {
                        table.column(6).search(this.value).draw();
                    });
                    $('#filterRepayment').on('change', function() {
                        table.column(7).search(this.value).draw();
                    });
                }
            });

            // ===========================
            // Lightbox Script
            // ===========================
            document.addEventListener('DOMContentLoaded', function() {
                const lightboxModalEl = document.getElementById('lightboxModal');
                const lightboxImageEl = document.getElementById('lightboxImage');
                const bsLightbox = new bootstrap.Modal(lightboxModalEl);

                document.querySelectorAll('.evidence-thumb').forEach(function(imgThumb) {
                    imgThumb.addEventListener('click', function() {
                        const fullSrc = this.getAttribute('data-src');
                        lightboxImageEl.setAttribute('src', fullSrc);
                        bsLightbox.show();
                    });
                });

                lightboxModalEl.addEventListener('hidden.bs.modal', function() {
                    lightboxImageEl.setAttribute('src', '');
                });
            });

            // ===========================
            // Bootstrap validation สำหรับ Reject Modal
            // ===========================
            document.addEventListener('DOMContentLoaded', function() {
                var rejectForms = document.querySelectorAll('form.needs-validation');
                rejectForms.forEach(function(form) {
                    form.addEventListener('submit', function(event) {
                        if (!form.checkValidity()) {
                            event.preventDefault();
                            event.stopPropagation();
                            form.classList.add('was-validated');
                        }
                    });
                });
            });
            </script>

            <script>
            // เมื่อเอกสารพร้อมใช้งาน
            document.addEventListener('DOMContentLoaded', function() {
                // ดึงปุ่ม “ดูสัญญา” ทุกตัว แล้วผูกอีเวนต์คลิก
                document.querySelectorAll('.btn-preview-contract').forEach(function(btn) {
                    btn.addEventListener('click', function(e) {
                        e.preventDefault();
                        // อ่าน contract_id จาก data attribute
                        const contractId = this.getAttribute('data-contract-id');
                        if (!contractId) return;

                        // สร้าง URL แบบสัมพัทธ์ที่ชี้มาที่ print-contract.php
                        // list-contracts-admin.php อยู่ใน /public/pages/superadmin/contracts/
                        // print-contract.php อยู่ใน     /public/pages/shop/contract/
                        // ดังนั้นเส้นทางคือ ../../shop/contract/print-contract.php
                        const url = '../../shop/contract/print-contract.php?contract_id=' +
                            contractId;

                        // ตั้ง src ให้ iframe
                        const iframe = document.getElementById('contractIframe');
                        iframe.setAttribute('src', url);

                        // เปิด Modal
                        const modalEl = document.getElementById('previewContractModal');
                        const bsModal = new bootstrap.Modal(modalEl);
                        bsModal.show();
                    });
                });

                // เมื่อ Modal ปิด ให้ล้าง src ของ iframe เพื่อหยุดโหลด
                const previewModalEl = document.getElementById('previewContractModal');
                previewModalEl.addEventListener('hidden.bs.modal', function() {
                    document.getElementById('contractIframe').setAttribute('src', '');
                });
            });
            </script>

        </div>
    </div>
</main>
<?php include ROOT_PATH . '/public/includes/footer.php'; ?>