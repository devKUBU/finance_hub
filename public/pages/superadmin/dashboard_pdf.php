<?php
// -----------------------------------------------------------------------------
// File: public/pages/superadmin/dashboard_pdf.php
// -----------------------------------------------------------------------------
require_once realpath(__DIR__.'/../../../config/bootstrap.php');
require_once ROOT_PATH.'/includes/helpers.php';
if (session_status()===PHP_SESSION_NONE) session_start();
requireRole(['superadmin', 'admin']);

require_once ROOT_PATH.'/config/db.php';
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
date_default_timezone_set('Asia/Bangkok');
$today = date('Y-m-d');

// — Dompdf setup with Thai font
require_once ROOT_PATH.'/vendor/autoload.php';
use Dompdf\Dompdf;
use Dompdf\Options;

$options = new Options();
// ตั้ง fontDir เป็นโฟลเดอร์ที่วางฟอนต์ไทย
$options->set('fontDir', ROOT_PATH.'/public/assets/fonts');
// ตั้ง defaultFont
$options->set('defaultFont', 'THSarabun');

$dompdf = new Dompdf($options);

// — 1) General statistics
$stats = [
    'ร้านค้าทั้งหมด (Shops Total)'            => (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='shop'")->fetchColumn(),
    'สัญญาทั้งหมด (Contracts Total)'         => (int)$pdo->query("SELECT COUNT(*) FROM contracts")->fetchColumn(),
    'สัญญารออนุมัติ (Contracts Pending)'      => (int)$pdo->query("SELECT COUNT(*) FROM contracts WHERE approval_status='pending'")->fetchColumn(),
    'งวดค้างชำระ (Overdue Payments)'          => (int)$pdo->query("SELECT COUNT(*) FROM payments WHERE due_date<CURDATE() AND paid_at IS NULL")->fetchColumn(),
    'งวดถึงกำหนดวันนี้ (Due Today)'           => (int)$pdo->query("SELECT COUNT(*) FROM payments WHERE DATE(due_date)=CURDATE() AND paid_at IS NULL")->fetchColumn(),
];

// — 2) Financial summary
$totalInstall    = (float)$pdo->query("SELECT COALESCE(SUM(amount_paid),0) FROM payments WHERE paid_at IS NOT NULL")->fetchColumn();
$otherIncome     = (float)$pdo->query("SELECT COALESCE(SUM(p.penalty_amount+p.fee_unlock+p.fee_document+p.fee_other),0) FROM payments p JOIN contracts c ON c.id=p.contract_id WHERE c.approval_status='approved'")->fetchColumn();
$totalRevenue    = $totalInstall + $otherIncome;
$loanPrincipal   = (float)$pdo->query("SELECT COALESCE(SUM(loan_amount),0) FROM contracts WHERE approval_status='approved'")->fetchColumn();
$totalCommission = (float)$pdo->query("SELECT COALESCE(SUM(commission_amount),0) FROM contracts WHERE approval_status='approved'")->fetchColumn();
$otherCost       = (float)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM expenses")->fetchColumn();
$totalCost       = $loanPrincipal + $totalCommission + $otherCost;
$profit          = $totalRevenue - $totalCost;
$isNegative      = $profit < 0;
$profitLabel     = $isNegative ? 'ขาดทุน (Loss)' : 'กำไร (Profit)';

// — Render HTML for PDF
ob_start();
?>
<!doctype html>
<html lang="th">

<head>
    <meta charset="utf-8">
    <title>แดชบอร์ด Super Admin (PDF)</title>
    <style>
    /* โหลดฟอนต์ไทย */
    @font-face {
        font-family: 'THSarabun';
        font-style: normal;
        font-weight: normal;
        src: url('<?= ROOT_PATH ?>/public/assets/fonts/THSarabun-Regular.ttf') format('truetype');
    }

    body {
        font-family: 'THSarabun', sans-serif;
        line-height: 1.5;
        margin: 1rem;
    }

    h2 {
        text-align: center;
        margin-bottom: 1rem;
    }

    h3 {
        margin: 1.5rem 0 .5rem;
        font-size: 1.1rem;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 1rem;
    }

    th,
    td {
        border: 1px solid #ccc;
        padding: .5rem .7rem;
    }

    th {
        background: #f0f0f0;
        text-align: left;
    }

    .text-right {
        text-align: right;
    }

    .profit {
        color: #198754;
    }

    .loss {
        color: #dc3545;
    }

    small {
        font-size: .85rem;
        color: #555;
    }
    </style>
</head>

<body>
    <h2>แดชบอร์ด Super Admin<br><small>Super Admin Dashboard</small></h2>

    <h3>สถิติทั่วไป (General Statistics)</h3>
    <table>
        <tbody>
            <?php foreach($stats as $label=>$value): ?>
            <tr>
                <th><?= $label ?></th>
                <td class="text-right"><?= number_format($value) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h3>สรุปการเงิน (Financial Summary)</h3>
    <table>
        <tbody>
            <tr>
                <th>รวมรายได้ (Total Revenue)</th>
                <td class="text-right"><?= number_format($totalRevenue,2) ?> ฿</td>
            </tr>
            <tr>
                <th>รวมต้นทุน (Total Cost)</th>
                <td class="text-right"><?= number_format($totalCost,2) ?> ฿</td>
            </tr>
            <tr>
                <th><?= $profitLabel ?></th>
                <td class="text-right <?= $isNegative ? 'loss' : 'profit' ?>">
                    <?= number_format(abs($profit),2) ?> ฿
                </td>
            </tr>
        </tbody>
    </table>

    <p style="text-align:center; font-size:.85rem; color:#666;">
        สร้างวันที่ <?= date('d/m/Y H:i') ?> | Generated on <?= date('Y-m-d H:i') ?>
    </p>
</body>

</html>
<?php
$html = ob_get_clean();

// — Generate PDF
$dompdf->loadHtml($html);
$dompdf->setPaper('A4','portrait');
$dompdf->render();
$dompdf->stream("dashboard_superadmin_".date('Ymd_His').".pdf", ['Attachment'=>true]);
exit;