<?php
// File: public/pages/superadmin/contracts/commission-contract.php

date_default_timezone_set('Asia/Bangkok');

/* ------------------------------------------------------------------
   1) LOAD BOOTSTRAP & HELPERS
------------------------------------------------------------------ */
require_once realpath(__DIR__ . '/../../../../config/bootstrap.php');
require_once ROOT_PATH . '/includes/helpers.php';
if (session_status() === PHP_SESSION_NONE) session_start();
requireRole(['superadmin', 'admin']);


/* ------------------------------------------------------------------
   2) CONNECT TO DATABASE
------------------------------------------------------------------ */
require_once ROOT_PATH . '/config/db.php';
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 3) GET & VALIDATE INPUTS
    $contractId            = isset($_POST['contract_id']) ? (int)$_POST['contract_id'] : 0;
    $commissionAmount      = isset($_POST['commission_amount']) ? trim($_POST['commission_amount']) : '';
    $commissionTransferred = isset($_POST['commission_transferred_at']) ? trim($_POST['commission_transferred_at']) : '';
    $slipFile              = $_FILES['commission_slip'] ?? null;

    // 3.1) contract_id ต้องเป็นตัวเลขบวก
    if ($contractId <= 0) {
        $errors[] = 'ผิดพลาด: ไม่พบรหัสสัญญาที่ถูกต้อง';
    }

    // 3.2) commission_amount ต้องเป็นตัวเลข > 0
    if ($commissionAmount === '' || !is_numeric($commissionAmount) || (float)$commissionAmount <= 0) {
        $errors[] = 'กรุณาระบุจำนวนคอมมิชชั่นเป็นตัวเลขมากกว่า 0';
    } else {
        // จัดให้อยู่ในรูป decimal(10,2)
        $commissionAmount = number_format((float)$commissionAmount, 2, '.', '');
    }

    // 3.3) commission_transferred_at ต้องไม่ว่าง และควรเป็นรูปแบบ datetime-local
    if ($commissionTransferred === '') {
        $errors[] = 'กรุณาระบุวันที่-เวลาโอนคอมมิชชั่น';
    } else {
        // แปลงเป็นรูปแบบ SQL datetime (YYYY-MM-DD HH:MM:SS)
        // HTML5 datetime-local ส่งมาในรูป “YYYY-MM-DDTHH:MM”
        $dt = DateTime::createFromFormat('Y-m-d\TH:i', $commissionTransferred);
        if ($dt === false) {
            $errors[] = 'รูปแบบวันที่-เวลาโอนคอมมิชชั่นไม่ถูกต้อง';
        } else {
            $commissionTransferredSQL = $dt->format('Y-m-d H:i:s');
        }
    }

    // 3.4) ตรวจสอบไฟล์สลิป (ถ้ามีการอัปโหลด)
    $commissionSlipPath = null;
    if ($slipFile && $slipFile['error'] !== UPLOAD_ERR_NO_FILE) {
        // ตรวจสอบ error เบื้องต้น
        if ($slipFile['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'เกิดข้อผิดพลาดขณะอัปโหลดสลิป (Error code: ' . $slipFile['error'] . ')';
        } else {
            // ตรวจสอบนามสกุลไฟล์ (jpg, png, gif, pdf)
            $allowedMimeTypes = [
                'image/jpeg' => 'jpg',
                'image/png'  => 'png',
                'image/gif'  => 'gif',
                'application/pdf' => 'pdf'
            ];
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime  = $finfo->file($slipFile['tmp_name']);
            if (!array_key_exists($mime, $allowedMimeTypes)) {
                $errors[] = 'ไฟล์สลิปต้องเป็น JPG, PNG, GIF หรือ PDF เท่านั้น';
            } else {
                // เตรียมไดเรกทอรีสำหรับเก็บสลิป
                $uploadDir = ROOT_PATH . '/public/uploads/commission_slips';
                if (!is_dir($uploadDir)) {
                    if (!mkdir($uploadDir, 0755, true)) {
                        $errors[] = 'ไม่สามารถสร้างโฟลเดอร์สำหรับเก็บสลิปได้';
                    }
                }

                if (empty($errors)) {
                    // สร้างชื่อไฟล์ใหม่ให้ไม่ซ้ำ: contract_{id}_{timestamp}_{random}.{ext}
                    $ext        = $allowedMimeTypes[$mime];
                    $filename   = sprintf(
                        'contract_%d_%s.%s',
                        $contractId,
                        (new DateTime())->format('YmdHis') . '_' . bin2hex(random_bytes(4)),
                        $ext
                    );
                    $destination = $uploadDir . '/' . $filename;

                    if (!move_uploaded_file($slipFile['tmp_name'], $destination)) {
                        $errors[] = 'ไม่สามารถบันทึกไฟล์สลิปได้ โปรดลองใหม่';
                    } else {
                        // เก็บ path ที่ใช้เก็บในฐานข้อมูล (relative path จาก public/)
                        $commissionSlipPath = 'uploads/commission_slips/' . $filename;
                    }
                }
            }
        }
    }

    // หากไม่มี error ให้บันทึกลง DB
    if (empty($errors)) {
        try {
            // 4) อัปเดตฐานข้อมูล contracts
            $sql = "
                UPDATE contracts
                   SET commission_amount           = ?,
                       commission_transferred_at   = ?,
                       commission_slip_path        = ?,
                       commission_status           = 'commission_transferred'
                 WHERE id = ?
            ";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $commissionAmount,
                $commissionTransferredSQL,
                $commissionSlipPath,  // ถ้าไม่มีไฟล์ จะเป็น null
                $contractId
            ]);
            // <<< เพิ่มโค้ดนี้ เพื่อบันทึกค่าใช้จ่ายคอมมิชชั่น >>>
            $ins = $pdo->prepare("
                INSERT INTO contract_expenses
                (contract_id, expense_type, amount, note)
                VALUES (?, 'commission', ?, ?)
            ");
            $ins->execute([
                $contractId,
                $commissionAmount,
                null       // หากต้องการใส่ note ใดๆ ก็แทน null ด้วยตัวแปร $noteCommission
            ]);

            setFlash('success', 'บันทึกคอมมิชชั่นเรียบร้อยแล้ว');
            header('Location: list-contracts-admin.php');
            exit;

        } catch (PDOException $e) {
            $errors[] = 'เกิดข้อผิดพลาดขณะอัปเดตฐานข้อมูล: ' . $e->getMessage();
        }
    }

    // ถ้ามีข้อผิดพลาดใด ๆ ให้เก็บใน flash แล้ว redirect กลับ
    if (!empty($errors)) {
        $msg = implode('<br>', array_map('htmlspecialchars', $errors));
        setFlash('error', $msg);
        header('Location: list-contracts-admin.php');
        exit;
    }
}

// หากไม่ใช่ POST หรือเกิดข้อผิดพลาด สามารถ redirect กลับได้
setFlash('error', 'การร้องขอไม่ถูกต้อง');
header('Location: list-contracts-admin.php');
exit;