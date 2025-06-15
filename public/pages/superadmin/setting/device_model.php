<?php
// File: C:\xampp\htdocs\nano-friend\public\pages\superadmin\setting\device_model.php

require_once realpath(__DIR__ . '/../../../../config/bootstrap.php');
require_once ROOT_PATH    . '/includes/helpers.php';
if (session_status() === PHP_SESSION_NONE) session_start();
requireRole(['superadmin', 'admin']);


require_once ROOT_PATH . '/config/db.php';
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// —————————————————————————————————————————————————————————————————————————
// Handle add / edit
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id        = isset($_POST['id']) ? (int) $_POST['id'] : 0;
    $brand     = trim($_POST['brand']      ?? '');
    $modelName = trim($_POST['model_name'] ?? '');

    if ($brand === '' || $modelName === '') {
        $errors[] = 'กรุณากรอกทั้งยี่ห้อและชื่อรุ่น';
    }

    if (empty($errors)) {
        if ($id > 0) {
            $stmt = $pdo->prepare("
                UPDATE device_models
                   SET brand = ?, model_name = ?
                 WHERE id    = ?
            ");
            $stmt->execute([$brand, $modelName, $id]);
            setFlash('success', 'อัปเดตเรียบร้อยแล้ว');
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO device_models (brand, model_name, created_at)
                VALUES (?, ?, NOW())
            ");
            $stmt->execute([$brand, $modelName]);
            setFlash('success', 'เพิ่มรุ่นใหม่เรียบร้อยแล้ว');
        }
        header('Location: device_model.php');
        exit;
    }
}

// —————————————————————————————————————————————————————————————————————————
// Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $pdo->prepare("DELETE FROM device_models WHERE id = ?")
        ->execute([(int) $_GET['delete']]);
    setFlash('success', 'ลบเรียบร้อยแล้ว');
    header('Location: device_model.php');
    exit;
}

// —————————————————————————————————————————————————————————————————————————
// Fetch all models
$models = $pdo
    ->query("SELECT id, brand, model_name, created_at
              FROM device_models
             ORDER BY id DESC")
    ->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'ตั้งค่ารุ่นที่ผ่อน';
?>

<?php include ROOT_PATH . '/public/includes/header.php'; ?>
<?php include ROOT_PATH . '/public/includes/sidebar.php'; ?>

<link rel="stylesheet" href="<?= htmlspecialchars($baseURL) ?>/assets/css/dashboard.css">

<style>
/* ===== Dark mode overrides ===== */
:root[data-theme="dark"] .table-shop-manage,
:root[data-theme="dark"] .table-shop-manage th,
:root[data-theme="dark"] .table-shop-manage td {
    background: var(--bg-card) !important;
    color: var(--text-primary) !important;
}

:root[data-theme="dark"] .table-shop-manage thead th {
    background: var(--primary-gradient) !important;
    color: #fff !important;
}

:root[data-theme="dark"] .form-label,
:root[data-theme="dark"] .alert,
:root[data-theme="dark"] .form-control {
    color: var(--text-primary) !important;
}

:root[data-theme="dark"] .form-control {
    background: var(--bg-input) !important;
    border: 1px solid var(--border-color) !important;
}

:root[data-theme="dark"] .card {
    background: var(--bg-card) !important;
    border: 1px solid var(--border-color) !important;
}

/* Alert overrides */
:root[data-theme="dark"] .alert {
    background-color: var(--bg-card) !important;
    color: var(--text-primary) !important;
    border-color: var(--border-color) !important;
}

:root[data-theme="dark"] .alert-success {
    background-color: #1a4220 !important;
    border-color: #2b6a37 !important;
}

:root[data-theme="dark"] .alert-danger {
    background-color: #4a1f23 !important;
    border-color: #842029 !important;
}
</style>

<main class="main-content">
    <header class="app-header d-flex align-items-center justify-content-between">
        <h2 class="header-title">
            <i class="fa-solid fa-mobile-screen-button me-2"></i>
            <?= htmlspecialchars($pageTitle) ?>
        </h2>
        <div class="header-actions">
            <button id="sidebarToggle" class="btn-icon" aria-label="Toggle sidebar">
                <i class="fa-solid fa-bars"></i>
            </button>
            <button id="themeToggle" class="btn-icon ms-2" aria-label="Toggle theme">
                <i id="themeIcon" class="fa-solid"></i>
            </button>
        </div>
    </header>
    <hr>

    <?php if ($msg = getFlash('success')): ?>
    <div class="alert alert-success"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $e): ?>
            <li><?= htmlspecialchars($e) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-body">
            <form method="post" class="row g-3">
                <input type="hidden" name="id" id="model-id" value="">
                <div class="col-md-6">
                    <label for="model-brand" class="form-label">ยี่ห้อ</label>
                    <input type="text" class="form-control" id="model-brand" name="brand" placeholder="เช่น Apple">
                </div>
                <div class="col-md-6">
                    <label for="model-model" class="form-label">ชื่อรุ่น</label>
                    <input type="text" class="form-control" id="model-model" name="model_name"
                        placeholder="เช่น iPhone 14">
                </div>
                <div class="col-12 text-end">
                    <button type="submit" class="btn btn-primary">บันทึก</button>
                    <button type="reset" id="form-reset" class="btn btn-secondary">ยกเลิก</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body table-responsive">
            <table id="models-table" class="table-shop-manage table table-striped mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>ยี่ห้อ</th>
                        <th>ชื่อรุ่น</th>
                        <th>สร้างเมื่อ</th>
                        <th>จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($models as $m): ?>
                    <tr>
                        <td><?= $m['id'] ?></td>
                        <td><?= htmlspecialchars($m['brand']) ?></td>
                        <td><?= htmlspecialchars($m['model_name']) ?></td>
                        <td><?= date('Y-m-d H:i', strtotime($m['created_at'])) ?></td>
                        <td>
                            <button class="btn btn-sm btn-warning btn-edit" data-id="<?= $m['id'] ?>"
                                data-brand="<?= htmlspecialchars($m['brand'], ENT_QUOTES) ?>"
                                data-model="<?= htmlspecialchars($m['model_name'], ENT_QUOTES) ?>">
                                แก้ไข
                            </button>
                            <a href="?delete=<?= $m['id'] ?>" class="btn btn-sm btn-danger"
                                onclick="return confirm('แน่ใจว่าต้องการลบ?')">
                                ลบ
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<?php include ROOT_PATH . '/public/includes/footer.php'; ?>

<script>
// Sidebar Toggle
document.getElementById('sidebarToggle').onclick = () => {
    document.body.classList.toggle('collapsed');
};

// Theme Toggle (ใช้ data-theme ที่ header.php ตั้งไว้)
(function() {
    const btn = document.getElementById('themeToggle'),
        icon = document.getElementById('themeIcon'),
        root = document.documentElement;

    function updateIcon() {
        icon.className = root.getAttribute('data-theme') === 'dark' ?
            'fa-solid fa-sun' :
            'fa-solid fa-moon';
    }

    btn.onclick = () => {
        const newTheme = (root.getAttribute('data-theme') === 'dark') ? 'light' : 'dark';
        root.setAttribute('data-theme', newTheme);
        localStorage.setItem('theme', newTheme);
        updateIcon();
    };

    updateIcon();
})();

// DataTable + edit/reset
document.addEventListener('DOMContentLoaded', () => {
    // เรียก DataTables ถ้ามี jQuery + plugin โหลดไว้แล้ว
    if (window.jQuery && $.fn.DataTable) {
        $('#models-table').DataTable({
            paging: true,
            searching: true,
            ordering: true
        });
    }

    document.querySelectorAll('.btn-edit').forEach(btn => {
        btn.addEventListener('click', () => {
            document.getElementById('model-id').value = btn.dataset.id;
            document.getElementById('model-brand').value = btn.dataset.brand;
            document.getElementById('model-model').value = btn.dataset.model;
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    });

    document.getElementById('form-reset').addEventListener('click', () => {
        document.getElementById('model-id').value = '';
        document.getElementById('model-brand').value = '';
        document.getElementById('model-model').value = '';
    });
});
</script>