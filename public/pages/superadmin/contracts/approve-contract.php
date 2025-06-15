<?php
// File: public/pages/superadmin/contracts/approve-contract.php
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
   3) VALIDATE INPUT
------------------------------------------------------------------ */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    setFlash('error', 'Invalid request method.');
    header('Location: list-contracts-admin.php');
    exit;
}

$contractId    = isset($_POST['contract_id']) ? (int)$_POST['contract_id'] : 0;
$action        = $_POST['action']       ?? '';
$rejectReason  = trim($_POST['reject_reason'] ?? '');
$allowResubmit = isset($_POST['allow_resubmit']) && $_POST['allow_resubmit'] === '1';

if ($contractId <= 0 || !in_array($action, ['approve', 'reject'], true)) {
    setFlash('error', 'ข้อมูลไม่ถูกต้อง');
    header('Location: list-contracts-admin.php');
    exit;
}

/* ------------------------------------------------------------------
   4) PERFORM ACTION
------------------------------------------------------------------ */
try {
    if ($action === 'approve') {
        // กรณีอนุมัติ ให้ตั้ง approval_status = 'approved' และ commission_status = 'commission_pending'
        $stmt = $pdo->prepare("
            UPDATE contracts
               SET approval_status     = 'approved',
                   commission_status   = 'commission_pending',
                   reject_reason       = NULL,
                   contract_update_at  = NOW()
             WHERE id = ?
        ");
        $stmt->execute([$contractId]);
        setFlash('success', 'อนุมัติสัญญาเรียบร้อยแล้ว');

    } else {
        // กรณี reject
        if ($rejectReason === '') {
            setFlash('error', 'กรุณากรอกเหตุผลการปฏิเสธ');
            header('Location: list-contracts-admin.php');
            exit;
        }

        if ($allowResubmit) {
            // เซ็ตสถานะเป็น ‘rejected’ แต่ให้ร้านค้าส่งใหม่ได้ (เซ็ต allow_resubmit = 1)
            $stmt = $pdo->prepare("
                UPDATE contracts
                SET approval_status     = 'rejected',
                    allow_resubmit      = 1,
                    reject_reason       = :reason,
                    contract_update_at  = NOW()
                WHERE id = :cid
            ");
            $stmt->execute([
                ':reason' => $rejectReason,
                ':cid'    => $contractId
            ]);
            setFlash('success', 'ปฏิเสธสัญญา (ส่งกลับให้แก้ไข) เรียบร้อยแล้ว');

        } else {
            // ปฏิเสธขั้นสุดท้าย (approval_status = 'rejected', commission_status = 'commission_cancelled', allow_resubmit = 0)
            $stmt = $pdo->prepare("
                UPDATE contracts
                SET approval_status     = 'rejected',
                    commission_status   = 'commission_cancelled',
                    allow_resubmit      = 0,
                    reject_reason       = :reason,
                    contract_update_at  = NOW()
                WHERE id = :cid
            ");
            $stmt->execute([
                ':reason' => $rejectReason,
                ':cid'    => $contractId
            ]);
            setFlash('success', 'ปฏิเสธสัญญาเรียบร้อยแล้ว');
        }

    }
} catch (Exception $ex) {
    setFlash('error', 'เกิดข้อผิดพลาด: ' . $ex->getMessage());
}

/* ------------------------------------------------------------------
   5) REDIRECT BACK TO LIST
------------------------------------------------------------------ */
header('Location: list-contracts-admin.php');
exit;