<?php
include ROOT_PATH . '/public/includes/header.php';
?>

<div class="container py-5">
    <div class="alert alert-danger text-center">
        <i class="fa-solid fa-ban fa-3x mb-3 text-danger"></i>
        <h4 class="fw-bold">คุณไม่มีสิทธิ์เข้าถึงหน้านี้</h4>
        <p class="mb-4">โปรดติดต่อผู้ดูแลระบบหากต้องการสิทธิ์เพิ่มเติม</p>
        <a href="<?= $baseURL ?>/pages/superadmin/dashboard.php" class="btn btn-primary">
            <i class="fa-solid fa-arrow-left me-1"></i> กลับหน้าหลัก
        </a>
    </div>
</div>

<?php
include ROOT_PATH . '/public/includes/footer.php';