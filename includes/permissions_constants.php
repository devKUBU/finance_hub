<?php
// File: includes/permissions_constants.php

return [
    // ===== 📂 จัดการสัญญา =====
    'manage_contracts'  => 'ดูและจัดการสัญญา',
    'edit_contracts'    => 'แก้ไขข้อมูลสัญญา (ก่อนอนุมัติ)',
    'approve_contracts' => 'อนุมัติ / ปฏิเสธสัญญา',
    'close_contracts'   => 'ปิดยอด / ปิดสัญญา',

    // ===== 💵 การชำระเงิน =====
    'view_payments'     => 'ดูรายการชำระเงิน',
    'edit_payments'     => 'แก้ไขงวดชำระ / เพิ่มค่าปรับ / แนบสลิป',

    // ===== 📊 รายงาน / การเงิน =====
    'view_reports'      => 'ดูรายงานการดำเนินงาน',
    'view_summary'      => 'ดูยอดรวมรายได้',
    'view_profits'      => 'ดูรายการกำไรร้านค้า',
    'view_costs'        => 'ดูต้นทุน / ค่าใช้จ่าย',

    // ===== 🛠 ตั้งค่า / จัดการผู้ใช้ =====
    'manage_admins'     => 'จัดการแอดมิน',
    'manage_shops'      => 'จัดการร้านค้า / สาขา',
    'manage_settings'   => 'จัดการการตั้งค่าระบบ'
];