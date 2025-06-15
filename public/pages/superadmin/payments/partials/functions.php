<?php
// File: public/pages/superadmin/payments/partials/functions.php

/**
 * สร้างเงื่อนไขค้นหา (WHERE … LIKE …) สำหรับ contract_no_shop, firstname, lastname
 */
function buildFilterClause(string $search, array &$params): string
{
    $clauses = ["c.approval_status = 'approved'"];
    if ($search !== '') {
        $clauses[] = "(
            c.contract_no_shop LIKE ? OR
            c.customer_firstname LIKE ? OR
            c.customer_lastname LIKE ? OR
            c.customer_phone LIKE ?
        )";
        $like = '%' . $search . '%';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }
    return ' AND ' . implode(' AND ', $clauses);
}

/**
 * ดึงข้อมูล “สัญญาที่มีงวดกำลังจะถึง” (pending/paid & due_date ≥ วันนี้)
 */
function getUpcomingContracts(PDO $pdo): array
{
    $sql = "
        SELECT 
          c.id                            AS contract_id,
          c.contract_no_shop              AS contract_no,
          c.customer_firstname,
          c.customer_lastname,
          p_next.pay_no                   AS next_pay_no,
          DATE(p_next.due_date)           AS next_due_date,
          p_next.amount_due               AS next_amount_due,
          COALESCE(p_next.amount_paid,0)  AS next_amount_paid,
          p_next.status                   AS next_status
        FROM contracts c
        JOIN (
          SELECT 
            p.id,
            p.contract_id, 
            p.pay_no, 
            p.due_date,
            p.amount_due,
            p.amount_paid,
            p.status
          FROM payments p
          WHERE 
            p.status IN ('pending','paid')
            AND DATE(p.due_date) >= CURDATE()
          ORDER BY p.contract_id, p.due_date ASC
        ) AS p_next ON p_next.contract_id = c.id
        WHERE c.approval_status = 'approved'
        GROUP BY c.id
        ORDER BY p_next.due_date ASC
    ";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * UPDATE การชำระงาน — ใช้ทั้งในโหมด new (แทนยอดเดิม) และ edit
 *
 * @param PDO    $pdo
 * @param int    $paymentId
 * @param float  $amountPaid
 * @param float  $penalty
 * @param float  $feeUnlock
 * @param float  $feeDocument
 * @param float  $feeOther
 * @param string $note
 * @param string $status      // 'paid' | 'closed'
 * @return bool
 */
function updatePaymentById(
    PDO $pdo,
    int $paymentId,
    float $amountPaid,
    float $penalty,
    float $feeUnlock,
    float $feeDocument,
    float $feeOther,
    string $note,
    string $status
): bool {
    $sql = "
      UPDATE payments
         SET amount_paid    = :amount_paid,
             penalty_amount = :penalty,
             fee_unlock     = :fee_unlock,
             fee_document   = :fee_document,
             fee_other      = :fee_other,
             note           = :note,
             status         = :status,
             paid_at        = NOW()
       WHERE id = :id
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':amount_paid',  $amountPaid);
    $stmt->bindValue(':penalty',      $penalty);
    $stmt->bindValue(':fee_unlock',   $feeUnlock);
    $stmt->bindValue(':fee_document', $feeDocument);
    $stmt->bindValue(':fee_other',    $feeOther);
    $stmt->bindValue(':note',         $note);
    $stmt->bindValue(':status',       $status);
    $stmt->bindValue(':id',           $paymentId, PDO::PARAM_INT);
    return $stmt->execute();
}

/**
 * (ถ้าต้องการ สร้างงวดใหม่ แทนการ UPDATE ล้วนๆ)
 */
function insertNewPayment(
    PDO $pdo,
    int $contractId,
    int $payNo,
    float $amountPaid,
    string $paidDate,
    string $paymentMethod,
    string $note,
    ?string $slipPath
): bool {
    $sql = "
      INSERT INTO payments
        (contract_id, pay_no, amount_paid, paid_date, payment_method, note, slip_paths, status, paid_at)
      VALUES
        (:cid, :pay_no, :amt, :pdate, :pmethod, :note, :slip, 'paid', NOW())
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':cid',      $contractId, PDO::PARAM_INT);
    $stmt->bindValue(':pay_no',   $payNo,      PDO::PARAM_INT);
    $stmt->bindValue(':amt',      $amountPaid);
    $stmt->bindValue(':pdate',    $paidDate);
    $stmt->bindValue(':pmethod',  $paymentMethod);
    $stmt->bindValue(':note',     $note);
    $stmt->bindValue(':slip',     $slipPath);
    return $stmt->execute();
}