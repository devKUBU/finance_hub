<?php
// File: public/assets/mail/templates/reset_password.php
/** 
 * ตัวแปรที่มีให้ใช้:
 *  - $cssContent  : string CSS ของอีเมล
 *  - $toName      : ชื่อผู้รับ
 *  - $tokenUrl    : ลิงก์สำหรับรีเซ็ต
 */
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <style>
    <?= $cssContent /* ฝัง CSS ที่อ่านมาจาก email.css */ ?>
  </style>
</head>
<body>
  <div class="container">
    <h1>สวัสดี <?= htmlspecialchars($toName) ?></h1>
    <p>คุณได้ร้องขอกู้คืนรหัสผ่าน กรุณาคลิกปุ่มด้านล่างเพื่อสร้างรหัสใหม่</p>
    <p><a href="<?= htmlspecialchars($tokenUrl) ?>" class="btn">รีเซ็ตรหัสผ่าน</a></p>
    <p>ลิงก์นี้จะใช้งานได้ภายใน 1 ชั่วโมง</p>
    <hr>
    <p>— ทีมงาน Nano Friend</p>
  </div>
</body>
</html>
