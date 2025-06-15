<?php
// File: public/includes/sidebar.php

$role = $_SESSION['user']['role'];
require_once ROOT_PATH . '/config/db.php';
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
require_once ROOT_PATH . '/includes/helpers.php';

$isSuper = $role === 'superadmin';

// เมนูหลักของ superadmin และ admin (ใช้ path /pages/superadmin/*)
$superAdminMenus = [
    ['dashboard.php', 'แดชบอร์ด', 'fa-gauge', 'text-primary', null],
    ['payments/manage_payments.php', 'การเงิน', 'fa-money-bill-trend-up', 'text-success', 'view_payments'],
    ['contracts/list-contracts-admin.php', 'จัดการสัญญา', 'fa-people-group', 'text-info', 'manage_contracts'],
    ['apple_ids/apple_ids.php', 'จัดการ Apple IDs', 'fa-brands fa-apple', 'text-danger', null],
    ['admin_manage/admins.php', 'จัดการแอดมิน', 'fa-user-shield', 'text-warning', 'manage_admins'],
    ['shop_manage/shop_manage.php', 'ร้านค้า', 'fa-store', 'text-secondary', 'manage_shops'],
    ['setting/device_model.php', 'ตั้งค่ารุ่น', 'fa-cogs', 'text-muted', 'manage_settings'],
    ['setting/contract-number.php', 'ตั้งค่าเลขสัญญา', 'fa-hashtag', 'text-primary', 'manage_settings'],
    ['setting/interest_settings.php', 'ตั้งค่าดอกเบี้ย', 'fa-calculator', 'text-success', 'manage_settings'],
    ['setting/company-settings.php', 'ตั้งค่าข้อมูลบริษัท', 'fa-building', 'text-info', 'manage_settings'],
    ['setting/penalty_settings.php', 'ตั้งค่าค่าปรับ', 'fa-gavel', 'text-danger', 'manage_settings'],
    ['activity_log.php', 'Activity Log', 'fa-book', 'text-secondary', null]
];
// เมนูของ shop (ใช้ path /pages/shop/*)
$shopMenus = [
    ['dashboard.php', 'แดชบอร์ด', 'fa-gauge', 'text-primary'],
    ['contract/new-contract.php', 'สร้างสัญญา', 'fa-file-circle-plus', 'text-success'],
    ['contract/list-contracts.php', 'รายการสัญญา', 'fa-file-contract', 'text-info'],
    ['income/profit.php', 'รายได้', 'fa-baht-sign', 'text-warning']
];

?>
<aside id="sidebar" class="sidebar">
    <div class="sidebar-brand">
        <img src="<?= $baseURL ?>/assets/images/logo.png" alt="Logo">
        <span>Nano Friend</span>
    </div>
    <nav class="sidebar-nav">
        <?php if ($role === 'superadmin' || $role === 'admin'): ?>
        <?php
            $menusToRender = array_filter($superAdminMenus, function ($item) use ($isSuper, $pdo) {
                [$file, , , , $perm] = $item;
                return $isSuper || !$perm || hasPermission($pdo, $_SESSION['user']['id'], $perm);
            });
            foreach ($menusToRender as [$file, $label, $icon, $color]):
                $active = basename($_SERVER['PHP_SELF']) === basename($file) ? ' active' : '';
            ?>
        <a href="<?= $baseURL ?>/pages/superadmin/<?= $file ?>" class="nav-link<?= $active ?>">
            <i class="fa-solid <?= $icon ?> <?= $color ?> me-2"></i>
            <span><?= $label ?></span>
        </a>
        <?php endforeach; ?>
        <?php elseif ($role === 'shop'): ?>
        <?php foreach ($shopMenus as [$file, $label, $icon, $color]):
                $active = basename($_SERVER['PHP_SELF']) === basename($file) ? ' active' : '';
            ?>
        <a href="<?= $baseURL ?>/pages/shop/<?= $file ?>" class="nav-link<?= $active ?>">
            <i class="fa-solid <?= $icon ?> <?= $color ?> me-2"></i>
            <span><?= $label ?></span>
        </a>
        <?php endforeach; ?>
        <?php endif; ?>

        <div class="mt-auto">
            <a href="<?= $baseURL ?>/logout.php" class="nav-link text-danger">
                <i class="fa-solid fa-right-from-bracket text-danger me-2"></i>
                <span>ออกจากระบบ</span>
            </a>
        </div>
    </nav>
</aside>