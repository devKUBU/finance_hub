<?php
// File: public/pages/shop/contract/print-contract.php

require_once realpath(__DIR__ . '/../../../../config/bootstrap.php');
require_once ROOT_PATH . '/includes/helpers.php';
if (session_status() === PHP_SESSION_NONE) session_start();
requireRole(['shop', 'superadmin']);

require_once ROOT_PATH . '/config/db.php';
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// 0) โหลดข้อมูลตั้งค่าบริษัทจากตาราง company_settings (row id=1)
$stmtCfg = $pdo->prepare("SELECT * FROM company_settings WHERE id = 1 LIMIT 1");
$stmtCfg->execute();
$cfg = $stmtCfg->fetch(PDO::FETCH_ASSOC);
if (!$cfg) {
    // กำหนดค่าเริ่มต้นถ้าไม่มี record
    $cfg = [
        'company_name'    => 'บริษัท นาโนเฟรนด์ จำกัด',
        'company_address' => '61 ถ.อรุณอมรินทร์<br>แขวงอรุณอมรินทร์ เขตบางกอกน้อย<br>กรุงเทพมหานคร 10700',
        'branch_name'     => '',
        'payment_methods' => "ธนาคารทหารไทย<br>ชื่อบัญชี ธนวรรณณ์ วีระกิฎธารา<br>เลขที่บัญชี 922-9-63374-9<br>โทร: 080-559-3431<br>Line: @nanopay",
        'line_qr_path'    => '',
        'logo_path'       => '',
        'line_id'         => ''
    ];
}

// 1) รับ contract_id
$contractId = isset($_GET['contract_id']) ? (int)$_GET['contract_id'] : 0;

// 2) ดึงข้อมูลสัญญาจากฐาน
$stmt = $pdo->prepare("
    SELECT
      contract_no_shop,
      start_date,
      branch_name        AS contract_branch,
      customer_firstname,
      customer_lastname,
      customer_id_card,
      province_id,
      amphur_id,
      district_id,
      postal_code,
      house_number,
      moo,
      soi,
      other_address,
      customer_phone,
      loan_amount,
      installment_amount,
      period_months,
      device_brand,
      device_model,
      device_capacity,
      device_color,
      device_serial_no,
      device_imei
    FROM contracts
    WHERE id = ?
");
$stmt->execute([$contractId]);
$ctr = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$ctr) exit('ไม่พบสัญญานี้');

// 3) รวมชื่อ–สกุล
$ctr['customer_name'] = trim($ctr['customer_firstname'] . ' ' . $ctr['customer_lastname']);

// 4) โหลด geography.json เพื่อแปลรหัสเป็นชื่อ
$geoPath = ROOT_PATH . '/public/assets/fonts/data/geography.json';
$geoData = json_decode(file_get_contents($geoPath), true);
function findGeoName($data, $key, $value, $nameKey) {
    foreach ($data as $row) {
        if ((string)$row[$key] === (string)$value) {
            return $row[$nameKey];
        }
    }
    return '';
}
$provinceName = findGeoName($geoData, 'provinceCode',   $ctr['province_id'],   'provinceNameTh');
$amphurName   = findGeoName($geoData, 'districtCode',   $ctr['amphur_id'],     'districtNameTh');
$districtName = findGeoName($geoData, 'subdistrictCode',$ctr['district_id'],  'subdistrictNameTh');

// 5) จัดที่อยู่ลูกค้า
$addressParts = [];
if ($ctr['house_number'] !== '')   $addressParts[] = 'บ้านเลขที่ ' . $ctr['house_number'];
if ($ctr['moo'] !== '')            $addressParts[] = 'หมู่ที่ ' . $ctr['moo'];
if ($ctr['soi'] !== '')            $addressParts[] = 'ซอย ' . $ctr['soi'];
if ($ctr['other_address'] !== '')  $addressParts[] = $ctr['other_address'];
$fullAddress = implode(' ', $addressParts);

// 6) ดึงตารางผ่อนจ่าย
$payStmt = $pdo->prepare("
    SELECT
      pay_no,
      due_date,
      amount_due AS amount
    FROM payments
    WHERE contract_id = ?
    ORDER BY pay_no
");
$payStmt->execute([$contractId]);
$payments = $payStmt->fetchAll(PDO::FETCH_ASSOC);

// ฟังก์ชันแปลงวันที่เป็น dd/mm/yyyy พ.ศ.
function dateThai(string $date, bool $showTime = false): string {
    $dt = new DateTime($date, new DateTimeZone('Asia/Bangkok'));
    $thaiMonths = [
        1=>'ม.ค.',2=>'ก.พ.',3=>'มี.ค.',4=>'เม.ย.',
        5=>'พ.ค.',6=>'มิ.ย.',7=>'ก.ค.',8=>'ส.ค.',
        9=>'ก.ย.',10=>'ต.ค.',11=>'พ.ย.',12=>'ธ.ค.'
    ];
    $day   = $dt->format('j');
    $month = $thaiMonths[(int)$dt->format('n')];
    $year  = $dt->format('Y') + 543;
    $str   = "{$day} {$month} {$year}";
    if ($showTime) {
        $str .= ' ' . $dt->format('H:i');
    }
    return $str;
}

function dateTimeThai(string $datetime): string {
    $dt = new DateTime($datetime, new DateTimeZone('Asia/Bangkok'));
    $thaiMonths = [
        1=>'ม.ค.',2=>'ก.พ.',3=>'มี.ค.',4=>'เม.ย.',
        5=>'พ.ค.',6=>'มิ.ย.',7=>'ก.ค.',8=>'ส.ค.',
        9=>'ก.ย.',10=>'ต.ค.',11=>'พ.ย.',12=>'ธ.ค.'
    ];
    $day   = $dt->format('d');
    $month = $thaiMonths[(int)$dt->format('n')];
    $year  = $dt->format('Y') + 543;
    $time  = $dt->format('H:i');
    return "{$day} {$month} {$year} เวลา {$time}";
}

// เตรียมตัวแปร baseURL เพื่อใช้อ้างอิง CSS/ภาพ
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>พิมพ์สัญญาเช่าซื้อสินค้า</title>
    <link rel="stylesheet" href="<?= htmlspecialchars($baseURL . '/assets/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Sarabun&display=swap">
    <style>
    body {
        font-family: 'Sarabun', sans-serif;
        font-size: 13px;
        padding: 30px;
        line-height: 1.5;
    }

    h1 {
        text-align: center;
        margin-bottom: 20px;
        font-weight: bold;
    }

    .no-print {
        text-align: right;
        margin-bottom: 15px;
    }

    .signature {
        display: flex;
        justify-content: space-between;
        margin-top: 50px;
    }

    .signature div {
        text-align: center;
        width: 45%;
    }

    .table-pay th,
    .table-pay td {
        border: 1px solid #000;
        padding: 5px;
        text-align: center;
    }

    .logo-container {
        min-height: 90px;
        display: flex;
        align-items: center;
        justify-content: flex-start;
    }

    .logo-placeholder {
        width: 100%;
        height: 90px;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: transparent;
    }

    .qr-container {
        width: 100px;
        height: 100px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-direction: column;
    }

    .qr-placeholder {
        width: 80px;
        height: 80px;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: transparent;
    }

    .contact-info {
        background: #f8f9fa;
        border: 1px solid #e9ecef;
        border-radius: 8px;
        padding: 15px;
        font-size: 0.9rem;
        line-height: 1.4;
    }

    .line-id-section {
        background: #e8f5e8;
        border: 1px solid #28a745;
        border-radius: 6px;
        padding: 8px 12px;
        margin-top: 8px;
        font-weight: 600;
        color: #155724;
    }

    @media print {
        .no-print {
            display: none;
        }
    }

    .contract-page-2 .signature-block-2-col {
        display: flex;
        justify-content: space-between;
        margin-top: 50px;
        page-break-inside: avoid;
    }

    .contract-page-2 .signature-block-2-col>div {
        text-align: center;
        width: 45%;
    }

    .underline-fill {
        border-bottom: 1px dotted #000;
        padding: 0 5px;
        white-space: nowrap;
    }
    </style>
</head>

<body>
    <div class="no-print mb-3">
        <button onclick="window.print()" class="btn btn-primary">🖨️ พิมพ์สัญญา</button>
    </div>

    <!-- Title + Company Info -->
    <div class="row mb-2">
        <div class="col-8">
            <div class="text-left mb-3">
                <div class="logo-container">
                    <?php if (!empty($cfg['logo_path'])): ?>
                    <img src="<?= htmlspecialchars($baseURL . '/' . $cfg['logo_path']) ?>" alt="Company Logo"
                        style="max-height:90px; max-width:100%; object-fit:contain;">
                    <?php else: ?>
                    <div class="logo-placeholder"></div>
                    <?php endif; ?>
                </div>
            </div>
            <b class="text-left">หนังสือสัญญารายละเอียดการชำระค่าสินค้าและบริการ</b>
        </div>
        <div class="col-4 text-end" style="line-height:1.4; font-size:0.9rem;">
            <?= nl2br(htmlspecialchars($cfg['company_name'])) ?><br>
            <?= $cfg['company_address'] ?>
        </div>
    </div>

    <!-- Contract Meta -->
    <div class="row mb-4">
        <div class="col-md-6">
            <p style="font-size:0.9rem; line-height:1.4;">
                <span class="text-danger fw-bold">เลขที่สัญญา
                    <?= htmlspecialchars($ctr['contract_no_shop']) ?></span><br>
                วันที่ทำสัญญา <?= dateThai($ctr['start_date'], true) ?> น.<br>
                ใช้บริการที่สาขา <?= htmlspecialchars($cfg['branch_name']) ?>
            </p>
        </div>
    </div>

    <!-- Customer Info -->
    <p style="font-size:0.9rem; line-height:1.6;">
        เอกสารสัญญาฉบับนี้ทำขึ้นระหว่าง <strong><?= htmlspecialchars($cfg['company_name']) ?></strong> (ในฐานะ
        "บริษัทฯ") ฝ่ายหนึ่ง
        กับ <strong><?= htmlspecialchars($ctr['customer_name']) ?></strong> (ในฐานะ "ลูกค้า")<br>
        เลขบัตรประชาชน <strong><?= htmlspecialchars($ctr['customer_id_card']) ?></strong><br>
        ที่อยู่ <strong><?= htmlspecialchars($fullAddress) ?></strong>
        ตำบล <strong><?= htmlspecialchars($districtName) ?></strong>
        อำเภอ <strong><?= htmlspecialchars($amphurName) ?></strong>
        จังหวัด <strong><?= htmlspecialchars($provinceName) ?></strong>
        รหัสไปรษณีย์ <strong><?= htmlspecialchars($ctr['postal_code']) ?></strong><br>
        โทรศัพท์ <strong><?= htmlspecialchars($ctr['customer_phone']) ?></strong>
    </p>
    ในฐานะ "ลูกค้า"

    <p><strong>ข้อ 1.</strong> ลูกค้าตกลงเช่าซื้อ และ บริษัทฯ ตกลงให้เช่าซื้อสินค้าตามรายการประกอบสัญญาเช่าซื้อ
        โดยมียอดเงินเป็นจำนวน <?= number_format($ctr['loan_amount'],2) ?> บาท</p>

    <p><strong>ข้อ 2.</strong> ลูกค้าตกลงชำระราคาสินค้าดังกล่าวในข้อ 1 ให้แก่ บริษัทฯ ภายใน
        <?= htmlspecialchars($ctr['period_months']) ?> เดือน โดยจะชำระค่าบริการทุก ๆ 15 วัน นับจากวันทำสัญญา เป็นจำนวน
        <strong><?= number_format($ctr['installment_amount'],2) ?> บาทต่อครั้ง
            หรือตามตารางชำระค่าบริการในสัญญานี้</strong>
        และลูกค้าตกลงจะชำระค่าบริการขั้นต่ำ 3 งวด
    </p>

    <p><strong>ข้อ 3.</strong> ในระหว่างชำระค่าสินค้า บริษัทฯ ยังเป็นเจ้าของทรัพย์สิน แต่เพียงผู้เดียว
        ลูกค้าสัญญาว่าจะไม่นำทรัพย์สินไปจำหน่าย ให้เช่า ให้ยืม หรือให้ผู้อื่นครอบครอง หรือใช้หรือ นำไปไว้ที่อื่น
        นอกจากที่ระบุ ถ้าพบความบกพร่องให้แจ้งภายใน 1 วัน มิฉะนั้นถือว่าสินทรัพย์สมบูรณ์</p>

    <p><strong>ข้อ 4.</strong> บริษัทฯ ตกลงส่งมอบสินค้าในวันทำสัญญา และลูกค้าได้รับสินค้าแล้ว ตรวจสอบสภาพว่าถูกต้อง
        หากพบความบกพร่องให้แจ้งภายใน 1 วัน มิฉะนั้นถือว่าพร้อมใช้งาน</p>

    <p><strong>ข้อ 5.</strong> บริษัทฯ รับรองว่าสินค้าตามที่เสนอมีคุณภาพครบถ้วน
        การยกเลิกสัญญาหรือคืนสินค้าลูกค้ายินยอมให้หักเงินร้อยละ 100 ของยอดที่ชำระทั้งหมด</p>

    <p><strong>ข้อ 6.</strong> ในระหว่างผ่อนชำระ บริษัทฯ ติดตั้งระบบแจ้งเตือน หากผิดนัดชำระให้บริษัทฯ
        ถือสัญญาผิดนัดได้ทันที</p>

    <p><strong>ข้อ 7.</strong> หากทรัพย์สินถูกโจรกรรม อัคคีภัย สูญหาย ลูกค้าต้องรับผิดชอบฝ่ายเดียว</p>

    <p><strong>ข้อ 8.</strong> หากผิดนัดชำระงวดใด ลูกค้าถือว่าผิดนัดทั้งหมด บริษัทฯ
        สามารถบอกเลิกสัญญาได้ทันทีโดยไม่ต้องแจ้งล่วงหน้า</p>

    <p><strong>ข้อ 9.</strong> หากบริษัทฯ ยอมผ่อนผันการผิดนัดในงวดใด ไม่ถือเป็นการผ่อนผันงวดอื่น</p>

    <p><strong>ข้อ 10.</strong> ลูกค้ายินยอมให้บริษัทฯ จัดเก็บ รวบรวม ใช้ และเปิดเผยข้อมูลส่วนบุคคล ตาม พ.ร.บ.
        คุ้มครองข้อมูลส่วนบุคคล</p>

    <p><strong>ข้อ 11.</strong> ลูกค้ายินยอมให้บริษัทฯ ใช้ระบบจัดการอุปกรณ์เคลื่อนที่ Mobile Device Management (MDM)
        หรือระบบของไอคลาวหรือแอปเปิ้ลไอดี (Apple ID)</p>

    <p><strong>ข้อ 12.</strong> หากผิดนัดเกิน 3 วัน บริษัทฯ สามารถระงับการใช้แอปพลิเคชันบนมือถือ ยกเว้น Banking, LINE,
        รับ-โทรออก</p>

    <p><strong>ข้อ 13.</strong> หากไม่สามารถติดต่อได้ บริษัทฯ มีสิทธิ์ใช้ Lost Mode (แจ้งเตือนเครื่องหาย) รวมถึงลบข้อมูล
        เพื่อปกป้องข้อมูลส่วนบุคคล</p>

    <hr>

    <p><strong>รายละเอียดสินค้าและอุปกรณ์:</strong></p>
    <ul>
        <li>ซีเรียล นัมเบอร์ : <?= htmlspecialchars($ctr['device_serial_no']) ?></li>
        <li>เลขอีมี่ : <?= htmlspecialchars($ctr['device_imei']) ?></li>
        <li>ยี่ห้อ : <?= htmlspecialchars($ctr['device_brand']) ?></li>
        <li>รุ่น : <?= htmlspecialchars($ctr['device_model']) ?></li>
        <li>ความจุ : <?= htmlspecialchars($ctr['device_capacity']) ?></li>
        <li>สี : <?= htmlspecialchars($ctr['device_color']) ?></li>
    </ul>

    <h5>ตารางการชำระค่าบริการ</h5>
    <table class="table-pay" width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <th>งวดที่</th>
            <th>กำหนดจ่าย</th>
            <th>ค่าบริการ (บาท)</th>
            <th>งวดที่</th>
            <th>กำหนดจ่าย</th>
            <th>ค่าบริการ (บาท)</th>
        </tr>
        <?php
        $half = ceil(count($payments) / 2);
        for ($i = 0; $i < $half; $i++):
            $p1 = $payments[$i];
            $p2 = $payments[$i + $half] ?? null;
        ?>
        <tr>
            <td><?= htmlspecialchars($p1['pay_no']) ?>.</td>
            <td><?= dateThai($p1['due_date']) ?></td>
            <td><?= number_format($p1['amount'], 2) ?></td>
            <?php if ($p2): ?>
            <td><?= htmlspecialchars($p2['pay_no']) ?>.</td>
            <td><?= dateThai($p2['due_date']) ?></td>
            <td><?= number_format($p2['amount'], 2) ?></td>
            <?php else: ?>
            <td></td>
            <td></td>
            <td></td>
            <?php endif; ?>
        </tr>
        <?php endfor; ?>
    </table>

    <div class="signature">
        <div>
            ลงชื่อ .............................................. (ผู้รับบริการ)<br>
            (<?= htmlspecialchars($ctr['customer_name']) ?>)
        </div>
        <div>
            ลงชื่อ .............................................. (ตัวแทนผู้ให้บริการ)<br>
            (<?= htmlspecialchars($cfg['company_name']) ?>)
        </div>
    </div>

    <div class="mt-4 d-flex justify-content-between align-items-start">
        <!-- ช่องทางการชำระเงิน (ดึงจากฐาน) -->
        <div style="flex:1;">
            <div class="contact-info">
                <strong>ช่องทางการชำระเงิน:</strong><br>
                <?= nl2br(htmlspecialchars($cfg['payment_methods'])) ?>

                <?php if (!empty($cfg['line_id'])): ?>
                <div class="line-id-section">
                    📱 LINE ID: <?= htmlspecialchars($cfg['line_id']) ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- QR-Code LINE -->
        <div class="qr-container" style="margin-left: 20px;">
            <?php if (!empty($cfg['line_qr_path'])): ?>
            <img src="<?= htmlspecialchars($baseURL . '/' . $cfg['line_qr_path']) ?>" alt="LINE QR Code"
                style="width: 80px; height: 80px; object-fit: contain;">
            <small style="font-size:0.7rem; text-align:center; margin-top:5px; color:#666;">
                แจ้งสลิปโอนเงิน<br>เข้าไลน์นี้เท่านั้น
            </small>
            <?php else: ?>
            <div class="qr-placeholder"></div>
            <small style="font-size:0.7rem; text-align:center; margin-top:5px; color:#999;">
                แจ้งสลิปโอนเงิน<br>ผ่านช่องทางอื่น
            </small>
            <?php endif; ?>
        </div>
    </div>

    <div style="page-break-before: always; margin-top: 5rem;"></div>

    <div class="contract-page-2">
        <div class="d-flex justify-content-between mb-3">
            <div class="text-center mb-3">
                <div class="logo-container">
                    <?php if (!empty($cfg['logo_path'])): ?>
                    <img src="<?= htmlspecialchars($baseURL . '/' . $cfg['logo_path']) ?>" alt="Company Logo"
                        style="max-height:90px; max-width:100%; object-fit:contain;">
                    <?php else: ?>
                    <div class="logo-placeholder"></div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="text-end company-address-top-right" style="font-size:0.9rem; line-height:1.4;">
                <?= nl2br(htmlspecialchars($cfg['company_name'])) ?><br>
                <?= $cfg['company_address'] ?>
            </div>
        </div>

        <div class="d-flex justify-content-between mb-4">
            <div class="left-info" style="font-size:0.9rem;">
                <div>ที่ทำ <?= htmlspecialchars($cfg['company_name']) ?></div>
                <strong>วันที่ทำสัญญา:</strong> <?= date('d/m/Y H:i:s', strtotime($ctr['start_date'])) ?><br>
                <strong>สาขา:</strong> <?= htmlspecialchars($cfg['branch_name']) ?>
            </div>
        </div>

        <p style="font-size:0.9rem; line-height:1.6;">
            สัญญานี้ทำขึ้นระหว่าง <?= htmlspecialchars($cfg['company_name']) ?> ("ผู้ซื้อ") กับ
            <span
                class="underline-fill"><?= htmlspecialchars($ctr['customer_firstname'] . ' ' . $ctr['customer_lastname']) ?></span>
            บัตรประชาชนเลขที่
            <span class="underline-fill"><?= htmlspecialchars($ctr['customer_id_card']) ?></span>
            อยู่บ้านเลขที่
            <span class="underline-fill">
                <?= htmlspecialchars($ctr['house_number']) ?>
                <?php if (!empty($ctr['moo'])): ?> หมู่ที่ <?= htmlspecialchars($ctr['moo']) ?><?php endif; ?>
                <?php if (!empty($ctr['soi'])): ?> ซอย <?= htmlspecialchars($ctr['soi']) ?><?php endif; ?>
                <?php if (!empty($ctr['other_address'])): ?>
                <?= htmlspecialchars($ctr['other_address']) ?><?php endif; ?>
            </span>
            ตำบล
            <span class="underline-fill"><?= htmlspecialchars($districtName) ?></span>
            อำเภอ
            <span class="underline-fill"><?= htmlspecialchars($amphurName) ?></span>
            จังหวัด
            <span class="underline-fill"><?= htmlspecialchars($provinceName) ?></span>
            รหัสไปรษณีย์
            <span class="underline-fill"><?= htmlspecialchars($ctr['postal_code']) ?></span>
            โทรศัพท์
            <span class="underline-fill"><?= htmlspecialchars($ctr['customer_phone']) ?></span>
            ต่อไปนี้เรียกว่า ("ผู้ขาย")
        </p>

        <p style="font-size:0.9rem; line-height:1.6;">
            ผู้ขายตกลงขายและผู้ซื้อยินยอมซื้อสินค้า ยี่ห้อ
            <span class="underline-fill"><?= htmlspecialchars($ctr['device_brand']) ?></span>
            รุ่น
            <span class="underline-fill"><?= htmlspecialchars($ctr['device_model']) ?></span>
            ความจุ
            <span class="underline-fill"><?= htmlspecialchars($ctr['device_capacity']) ?> GB</span>
            สี
            <span class="underline-fill"><?= htmlspecialchars($ctr['device_color']) ?></span>
            (มือสอง) หมายเลขเครื่อง
            <span class="underline-fill">Serial No. <?= htmlspecialchars($ctr['device_serial_no']) ?></span>
            ราคา
            <span class="underline-fill"><?= number_format($ctr['loan_amount'], 2) ?> บาท</span>
            ผู้ขายส่งมอบสินค้าให้ผู้ซื้อได้รับและชำระเงินเรียบร้อย
        </p>

        <p style="font-size:0.9rem; line-height:1.6;">
            ผู้ขายรับรองว่าสินค้าใช้งานได้และเป็นกรรมสิทธิ์ของผู้ขาย หากสินค้ามีปัญหา ผู้ขายจะรับผิดชอบแต่เพียงผู้เดียว
        </p>

        <p style="font-size:0.9rem; line-height:1.6;">
            สัญญานี้ทำสองฉบับ ข้อความตรงกัน คู่สัญญาอ่านเข้าใจและลงนามเรียบร้อย
        </p>

        <div class="contract-page-2">
            <div class="d-flex justify-content-between mt-5">
                <div>
                    ลงชื่อ ________________________________<br>
                    (ผู้ขาย)
                </div>
                <div>
                    ลงชื่อ ________________________________<br>
                    (ผู้ซื้อ)
                </div>
            </div>
            <div class="d-flex justify-content-between mt-2">
                <div>
                    (<?= htmlspecialchars($ctr['customer_firstname'] . ' ' . $ctr['customer_lastname']) ?>)
                </div>
                <div>
                    (___________________________)
                </div>
            </div>
        </div>
    </div>
</body>

</html>