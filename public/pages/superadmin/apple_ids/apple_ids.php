<?php
// File: public/pages/superadmin/apple_ids/apple_ids.php

date_default_timezone_set('Asia/Bangkok');

// 1) LOAD BOOTSTRAP & HELPERS
// from .../public/pages/superadmin/apple_ids  ↑4 dirs→ config/bootstrap.php
require_once realpath(__DIR__ . '/../../../../config/bootstrap.php');
require_once ROOT_PATH    . '/includes/helpers.php';

if (session_status() === PHP_SESSION_NONE) session_start();
requireRole(['superadmin', 'admin']);
;

require_once ROOT_PATH . '/config/db.php';
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Handle deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $pdo->prepare("DELETE FROM apple_ids WHERE id = ?")
        ->execute([(int)$_POST['delete_id']]);
    header('Location: apple_ids.php'); exit;
}

// Fetch Apple IDs
$appleIds = $pdo
    ->query("SELECT * FROM apple_ids ORDER BY updated_at DESC, created_at DESC")
    ->fetchAll(PDO::FETCH_ASSOC);

// Check in-use
function isInUse(PDO $pdo, string $json): bool {
    $ids = json_decode($json, true) ?: [];
    if (!$ids) return false;
    $ph = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("
      SELECT 1 FROM contracts
       WHERE id IN ($ph) AND end_date >= CURDATE()
       LIMIT 1
    ");
    $stmt->execute($ids);
    return (bool)$stmt->fetchColumn();
}
$countInUse = $countFree = 0;
foreach ($appleIds as $r) {
    $inUseList = getInUseDetails($pdo, $r['apple_id']);
}

/**
 * ดึงสัญญาที่ใช้ Apple ID นี้อยู่ (ยังไม่ปิดยอด)
 * คืนค่าเป็น array ของ [contract_no_shop, customer_fullname]
 */
function getInUseDetails(PDO $pdo, string $appleId): array {
    $sql = "
      SELECT c.contract_no_shop,
             CONCAT(c.customer_firstname,' ',c.customer_lastname) AS customer_name
        FROM contracts c
       WHERE c.icloud_email = ?
         AND c.end_date >= CURDATE()
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$appleId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fetch Apple IDs
$appleIds = $pdo
    ->query("SELECT * FROM apple_ids ORDER BY updated_at DESC, created_at DESC")
    ->fetchAll(PDO::FETCH_ASSOC);

// 1) นับจำนวนพร้อมใช้/กำลังใช้งาน ด้วย getInUseDetails()

// นับสถิติ
$totalCount    = count($appleIds);
$activeCount   = 0;
$inactiveCount = 0;
$inUseCount    = 0;
$freeCount     = 0;
foreach ($appleIds as $r) {
    // Active / Inactive
    if ($r['is_active']) $activeCount++;
    else                $inactiveCount++;
    // In Use / Free
    $inUseList = getInUseDetails($pdo, $r['apple_id']);
    if (count($inUseList)) $inUseCount++;
    else                   $freeCount++;
}

$pageTitle = 'จัดการ Apple IDs';
include ROOT_PATH . '/public/includes/header.php';
include ROOT_PATH . '/public/includes/sidebar.php';
?>
<link rel="stylesheet" href="<?=$baseURL?>/assets/css/dashboard.css">
<style>
#appleTable th,
#appleTable td {
    font-size: 0.85rem;
    vertical-align: middle;
}

[data-theme="dark"] .modal-content {
    background: #343a40;
    /* พื้นหลังเข้ม */
    color: #e9ecef;
    /* ตัวอักษรสว่าง */
}

[data-theme="dark"] .modal-header,
[data-theme="dark"] .modal-footer {
    background: #343a40;
    border-color: #454d55;
}
</style>


<main class="main-content">
    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="m-0">
                <i class="fa-brands fa-apple me-2"></i><?=htmlspecialchars($pageTitle)?>
            </h3>
            <div class="header-actions d-flex align-items-center">
                <a href="apple_id_form.php" class="btn btn-sm btn-outline-primary me-2">
                    <i class="fa-solid fa-plus me-1"></i>เพิ่ม Apple ID
                </a>
                <button id="sidebarToggle" class="btn-icon" aria-label="Toggle sidebar">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <button id="themeToggle" class="btn-icon ms-2" aria-label="Toggle theme">
                    <i id="themeIcon" class="fa-solid fa-moon"></i>
                </button>
            </div>
        </div>
        <hr>
        <!-- Filter & Search -->
        <div class="d-flex align-items-center flex-wrap mb-3 gap-2">
            <span class="badge bg-primary">ทั้งหมด: <?= $totalCount ?></span>
            <span class="badge bg-success">Active: <?= $activeCount ?></span>
            <span class="badge bg-secondary">Inactive: <?= $inactiveCount ?></span>
            <span class="badge bg-danger">กำลังใช้งาน: <?= $inUseCount ?></span>
            <span class="badge bg-success">พร้อมใช้: <?= $freeCount ?></span>

            <select id="filterSelect" class="form-select form-select-sm me-3" style="width:auto;">
                <option value="all">แสดงทั้งหมด</option>
                <option value="free">เฉพาะพร้อมใช้</option>
                <option value="inuse">เฉพาะกำลังใช้งาน</option>
                <option value="active">เฉพาะ Active</option>
                <option value="inactive">เฉพาะ Inactive</option>
            </select>

            <input id="searchInput" class="form-control form-control-sm ms-auto" style="max-width:240px;"
                placeholder="ค้นหา Apple ID…">
        </div>



        <!-- Table -->
        <div class="table-responsive rounded overflow-hidden shadow-sm">
            <table id="appleTable" class="table table-sm table-bordered table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th style="width:60px;">ลำดับ</th>
                        <th>Apple ID</th>
                        <th>สถานะ</th>
                        <th>In Use</th>
                        <th>Created</th>
                        <th>Updated</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($appleIds)): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">ยังไม่มีข้อมูล Apple ID</td>
                    </tr>
                    <?php else: foreach($appleIds as $i=>$r):
                    $inUse = isInUse($pdo,$r['contracts']);
                    $inUseList = getInUseDetails($pdo, $r['apple_id']);
                    $histJson = htmlspecialchars(
                    json_encode(json_decode($r['history'] ?? '[]', true), JSON_UNESCAPED_UNICODE)
                    );
                ?>
                    <tr>
                        <td><?= $i+1 ?></td>
                        <td class="col-id"><?= htmlspecialchars($r['apple_id']) ?></td>
                        <td>
                            <span class="badge <?= $r['is_active'] ? 'bg-success' : 'bg-secondary' ?>">
                                <?= $r['is_active'] ? 'Active' : 'Inactive' ?>
                            </span>
                        </td>
                        <td>
                            <?php if (empty($inUseList)): ?>
                            <span class="badge bg-success">พร้อมใช้</span>
                            <?php else: ?>
                            <span class="badge bg-danger">กำลังใช้งาน</span>
                            <?php
                            // สร้าง list ของ “สัญญา … (ชื่อลูกค้า)” แล้วต่อด้วย comma
                            $items = array_map(function($c) {
                                $no   = htmlspecialchars($c['contract_no_shop']);
                                $name = htmlspecialchars($c['customer_name']);
                                return "สัญญา {$no} ({$name})";
                            }, $inUseList);
                            ?>
                            <span class="ms-2 text-success"><?= implode(', ', $items) ?></span>
                            <?php endif; ?>
                        </td>

                        <td><?=$r['created_at']?></td>
                        <td><?=$r['updated_at']?></td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-warning me-1 btn-edit" data-id="<?= $r['id'] ?>"
                                data-apple="<?= htmlspecialchars($r['apple_id']) ?>"
                                data-password="<?= htmlspecialchars($r['password']) ?>"
                                data-pincode="<?= htmlspecialchars($r['pincode']) ?>"
                                data-active="<?= $r['is_active'] ?>" title="แก้ไข">
                                <i class="fa-solid fa-edit text-white"></i>
                            </button>
                            <button class="btn btn-sm btn-info me-1 btn-history"
                                data-apple="<?= htmlspecialchars($r['apple_id']) ?>"
                                data-password="<?= htmlspecialchars($r['password']) ?>"
                                data-pincode="<?= htmlspecialchars($r['pincode']) ?>" data-history='<?= $histJson ?>'
                                title="ประวัติ">
                                <i class="fa-solid fa-clock-rotate-left"></i>
                            </button>
                            <button class="btn btn-sm btn-danger btn-delete-apple" data-id="<?= $r['id'] ?>" title="ลบ">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<!-- HISTORY MODAL -->
<div class="modal fade" id="historyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" data-bs-theme="auto">
            <!-- Header -->
            <div class="modal-header bg-body text-body">
                <h5 class="modal-title">
                    <i class="fa-solid fa-clock-rotate-left me-2"></i>ประวัติเปลี่ยนข้อมูล
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <!-- Body -->
            <div class="modal-body bg-body text-body">
                <!-- 1) Current Info (แยกกล่อง) -->
                <div class="border rounded p-3 mb-4">
                    <h6>ข้อมูลปัจจุบัน</h6>
                    <div class="mb-2">
                        <label class="form-label mb-1"><strong>Apple ID:</strong></label>
                        <div class="input-group">
                            <input id="ciAppleInput" type="text" class="form-control" readonly>
                            <button id="copyAppleBtn" class="btn btn-outline-secondary" type="button"
                                title="คัดลอก Apple ID">
                                <i class="fa-regular fa-copy"></i>
                            </button>
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label mb-1"><strong>Password:</strong></label>
                        <div><code id="ciPassword"></code></div>
                    </div>
                    <div>
                        <label class="form-label mb-1"><strong>Pincode:</strong></label>
                        <div><code id="ciPincode"></code></div>
                    </div>
                </div>

                <!-- 2) History Table -->
                <div class="table-responsive">
                    <table class="table table-striped mb-0" data-bs-theme="auto">
                        <thead>
                            <tr class="bg-body text-body">
                                <th>วันที่เปลี่ยน</th>
                                <th>รหัสเก่า</th>
                                <th>PIN เก่า</th>
                                <th>โดย (User)</th>
                            </tr>
                        </thead>
                        <tbody id="historyBody"></tbody>
                    </table>
                </div>
            </div>

            <!-- Footer (pagination + close) -->
            <div class="modal-footer bg-body text-body d-flex justify-content-between align-items-center">
                <div>
                    <button id="histPrev" class="btn btn-sm btn-outline-secondary" disabled>
                        <i class="fa-solid fa-chevron-left"></i> ก่อนหน้า
                    </button>
                    <button id="histNext" class="btn btn-sm btn-outline-secondary" disabled>
                        ถัดไป <i class="fa-solid fa-chevron-right"></i>
                    </button>
                </div>
                <small id="histPageInfo" class="text-muted"></small>
                <button class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fa-solid fa-xmark me-1"></i>ปิด
                </button>
            </div>

        </div>
    </div>
</div>


<!-- Edit Apple ID Modal -->
<div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <form id="editForm">
                <div class="modal-header">
                    <h5 class="modal-title">แก้ไข Apple ID</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="editId">
                    <div class="mb-3">
                        <label class="form-label">Apple ID</label>
                        <input name="apple_id" id="editApple" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input name="password" id="editPass" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Pincode</label>
                        <input name="pincode" id="editPin" class="form-control" pattern="\d{4}" required>
                    </div>
                    <div class="form-check">
                        <input name="is_active" type="checkbox" id="editActive" class="form-check-input">
                        <label for="editActive" class="form-check-label">Active</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-success btn-sm">บันทึก</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- ปุ่มลบ -->
<button class="btn btn-sm btn-danger btn-delete-apple" data-id="<?= $r['id'] ?>" title="ลบ">
    <i class="fa-solid fa-trash"></i>
</button>

<!-- Modal ยืนยันการลบ -->
<div class="modal fade" id="confirmDeleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="deleteAppleForm" method="post" action="<?= $_SERVER['PHP_SELF'] ?>">
                <div class="modal-header">
                    <h5 class="modal-title">ลบ Apple ID</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p id="deleteMessage">กำลังตรวจสอบ...</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <!-- ปุ่มยืนยันลบ -->
                    <button type="submit" class="btn btn-danger" id="confirmDeleteBtn" disabled>ลบ</button>
                </div>
                <input type="hidden" name="delete_id" id="deleteAppleId" value="">
            </form>
        </div>
    </div>
</div>

<?php include ROOT_PATH.'/public/includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const editModalEl = document.getElementById('editModal');
    const editModal = new bootstrap.Modal(editModalEl);
    const form = document.getElementById('editForm');

    // เมื่อคลิกปุ่ม “แก้ไข” บนตาราง
    document.querySelectorAll('.btn-edit').forEach(btn => {
        btn.addEventListener('click', () => {
            // กรอกค่าเดิมลงในฟอร์ม
            document.getElementById('editId').value = btn.dataset.id;
            document.getElementById('editApple').value = btn.dataset.apple;
            document.getElementById('editPass').value = btn.dataset.password;
            document.getElementById('editPin').value = btn.dataset.pincode;
            document.getElementById('editActive').checked = btn.dataset.active === '1';

            editModal.show();
        });
    });

    // เมื่อ submit ฟอร์มใน modal
    form.addEventListener('submit', async e => {
        e.preventDefault();
        const data = new URLSearchParams(new FormData(form));
        try {
            const res = await fetch('apple_id_form.php', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: data
            });
            const json = await res.json();
            if (json.success) {
                // อัปเดตแถวในตารางแบบง่าย: reload หน้า หรือปรับเฉพาะแถวก็ได้
                location.reload();
            } else {
                alert(json.error || 'บันทึกไม่สำเร็จ');
            }
        } catch (err) {
            console.error(err);
            alert('เกิดข้อผิดพลาด');
        }
    });
});
</script>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Theme toggle
    const html = document.documentElement;
    const icon = document.getElementById('themeIcon');
    let theme = html.getAttribute('data-theme') || 'light';
    icon.className = `fa-solid ${theme==='light'?'fa-moon':'fa-sun'}`;

    document.getElementById('themeToggle').onclick = async () => {
        theme = theme === 'light' ? 'dark' : 'light';
        try {
            const res = await fetch('<?=$baseURL?>/api/toggle_theme.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'theme=' + encodeURIComponent(theme)
            });
            const j = await res.json();
            html.setAttribute('data-theme', j.theme);
            icon.className = `fa-solid ${j.theme==='light'?'fa-moon':'fa-sun'}`;
        } catch (e) {
            console.error(e);
        }
    };

    // 1) Sync modal theme
    const syncModalTheme = () => {
        const t = html.getAttribute('data-theme') || 'light';
        document.querySelectorAll('.modal-content')
            .forEach(mc => mc.setAttribute('data-bs-theme', t));
    };
    syncModalTheme();

    // 2) Pagination setup
    const PAGE_SIZE = 10;
    let histArr = [],
        page = 1,
        pages = 1;
    const tbody = document.getElementById('historyBody');
    const btnPrev = document.getElementById('histPrev');
    const btnNext = document.getElementById('histNext');
    const info = document.getElementById('histPageInfo');
    const histModal = new bootstrap.Modal(
        document.getElementById('historyModal')
    );

    function render() {
        tbody.innerHTML = '';
        if (!histArr.length) {
            tbody.innerHTML = `<tr>
        <td colspan="4" class="text-center text-muted">ยังไม่มีประวัติ</td>
      </tr>`;
        } else {
            const start = (page - 1) * PAGE_SIZE;
            histArr.slice(start, start + PAGE_SIZE).forEach(h => {
                const d = new Date(h.changed_at);
                const fmt =
                    `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')} ` +
                    `${String(d.getHours()).padStart(2,'0')}:${String(d.getMinutes()).padStart(2,'0')}:${String(d.getSeconds()).padStart(2,'0')}`;
                const tr = document.createElement('tr');
                tr.innerHTML = `<td>${fmt}</td>
                        <td>${h.old_password}</td>
                        <td>${h.old_pincode}</td>
                        <td>${h.changed_by}</td>`;
                tbody.appendChild(tr);
            });
        }
        pages = Math.max(1, Math.ceil(histArr.length / PAGE_SIZE));
        info.textContent = `หน้า ${page} / ${pages}`;
        btnPrev.disabled = page <= 1;
        btnNext.disabled = page >= pages;
    }

    btnPrev.addEventListener('click', () => {
        if (page > 1) {
            page--;
            render();
        }
    });
    btnNext.addEventListener('click', () => {
        if (page < pages) {
            page++;
            render();
        }
    });

    // Real-time search (unchanged) …
    document.getElementById('searchInput').addEventListener('input', function() {
        const term = this.value.toLowerCase();
        document.querySelectorAll('#appleTable tbody tr').forEach(tr => {
            tr.style.display = tr.querySelector('.col-id').textContent
                .toLowerCase().includes(term) ? '' : 'none';
        });
    });

    // 3) History button handler
    document.querySelectorAll('.btn-history').forEach(btn => {
        btn.addEventListener('click', () => {
            syncModalTheme();

            // Populate current info — note use of ciAppleInput.value
            document.getElementById('ciAppleInput').value = btn.dataset.apple;
            document.getElementById('ciPassword').textContent = btn.dataset.password;
            document.getElementById('ciPincode').textContent = btn.dataset.pincode;

            // Load history & reset pagination
            histArr = JSON.parse(btn.dataset.history || '[]');
            page = 1;
            render();

            // Show the modal
            histModal.show();
        });
    });

    // แทนที่ listener เก่า ด้วยอันนี้
    document.getElementById('copyAppleBtn').addEventListener('click', () => {
        const apple = document.getElementById('ciAppleInput').value;
        const pass = document.getElementById('ciPassword').textContent;
        const pincode = document.getElementById('ciPincode').textContent;

        // สร้างข้อความแบบหลายบรรทัด
        const text =
            `Apple ID: ${apple}\n` +
            `Password: ${pass}\n` +
            `Pincode: ${pincode}`;

        navigator.clipboard.writeText(text)
            .then(() => {
                // แจ้งเตือนเล็กๆ
                const toast = document.createElement('div');
                toast.className = 'toast align-items-center text-bg-success border-0';
                toast.style.position = 'fixed';
                toast.style.top = '1rem';
                toast.style.right = '1rem';
                toast.innerHTML = `
        <div class="d-flex">
          <div class="toast-body">คัดลอกเรียบร้อยแล้ว</div>
          <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>`;
                document.body.appendChild(toast);
                const bsToast = new bootstrap.Toast(toast, {
                    delay: 2000
                });
                bsToast.show();
                bsToast._element.addEventListener('hidden.bs.toast', () => toast.remove());
            })
            .catch(() => {
                alert('ไม่สามารถคัดลอกได้');
            });
    });

    // 2) ผูก event ให้ dropdown
    document.getElementById('filterSelect').addEventListener('change', applyFilter);

    // 3) แก้ให้ search และ filter ทำงานร่วมกัน
    document.getElementById('searchInput').addEventListener('input', function() {
        const term = this.value.toLowerCase();
        document.querySelectorAll('#appleTable tbody tr').forEach(tr => {
            const matchesSearch = tr.querySelector('.col-id').textContent.toLowerCase()
                .includes(term);
            tr.dataset.matchesSearch = matchesSearch; // เก็บสถานะไว้
        });
        // แล้วเรียก applyFilter() ใหม่ เพื่อรวมทั้งเงื่อนไข
        applyFilter();
    });


});
</script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const filter = document.getElementById('filterSelect');
    const search = document.getElementById('searchInput');
    const rows = document.querySelectorAll('#appleTable tbody tr');

    function applyFilter() {
        const f = filter.value;
        const term = search.value.trim().toLowerCase();

        rows.forEach(tr => {
            const idText = tr.querySelector('.col-id').textContent.toLowerCase();
            const badgeUse = tr.querySelector('td:nth-child(4) .badge').textContent.trim();
            const badgeAct = tr.querySelector('td:nth-child(3) .badge').textContent.trim();


            let show = true;

            // 1) Filter by use-status
            if (f === 'free' && badgeUse !== 'พร้อมใช้') show = false;
            if (f === 'inuse' && badgeUse !== 'กำลังใช้งาน') show = false;
            if (f === 'active' && badgeAct !== 'Active') show = false;
            if (f === 'inactive' && badgeAct !== 'Inactive') show = false;

            // 2) Filter by search term
            if (term && !idText.includes(term)) show = false;

            tr.style.display = show ? '' : 'none';
        });
    }

    filter.addEventListener('change', applyFilter);
    search.addEventListener('input', applyFilter);
    if (term && !idText.includes(term)) show = false;
});
</script>

<!-- ... โค้ดส่วนหัว, ตาราง Apple IDs ... -->

<!-- ปุ่มลบ -->
<button class="btn btn-sm btn-danger btn-delete-apple" data-id="<?= $r['id'] ?>" title="ลบ">
    <i class="fa-solid fa-trash"></i>
</button>

<!-- Modal ยืนยันการลบ -->
<div class="modal fade" id="confirmDeleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="deleteAppleForm" method="post" action="<?= $_SERVER['PHP_SELF'] ?>">
                <div class="modal-header">
                    <h5 class="modal-title">ลบ Apple ID</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p id="deleteMessage">กำลังตรวจสอบ...</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <!-- ปุ่มยืนยันลบ -->
                    <button type="submit" class="btn btn-danger" id="confirmDeleteBtn" disabled>ลบ</button>
                </div>
                <input type="hidden" name="delete_id" id="deleteAppleId" value="">
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const modalEl = document.getElementById('confirmDeleteModal'),
        deleteModal = new bootstrap.Modal(modalEl),
        msgEl = modalEl.querySelector('#deleteMessage'),
        confirmBtn = modalEl.querySelector('#confirmDeleteBtn'),
        inputId = modalEl.querySelector('#deleteAppleId'),
        checkUrl = '<?= $baseURL ?>/pages/superadmin/apple_ids/check_apple_usage.php';

    document.querySelectorAll('.btn-delete-apple').forEach(btn => {
        btn.addEventListener('click', async () => {
            const id = btn.dataset.id;
            inputId.value = id;
            msgEl.textContent = 'กำลังตรวจสอบการใช้งาน...';
            confirmBtn.disabled = true; // ปิดปุ่มก่อนตรวจสอบ
            confirmBtn.textContent = 'ลบ'; // คืนค่าเดิม
            deleteModal.show();

            try {
                const res = await fetch(`${checkUrl}?id=${id}`);
                const json = await res.json();
                if (json.used) {
                    // ถ้าใช้งานอยู่ ให้แจ้งและไม่เปิดปุ่มลบ
                    msgEl.innerHTML = `
            <div class="text-danger">
              🚫 ไม่สามารถลบได้!<br>
              Apple ID นี้ถูกใช้งานกับสัญญา:<br>
              ${ json.contracts.map(c=>`• ${c.contract_no}`).join('<br>') }
            </div>`;
                    confirmBtn.disabled = true;
                } else {
                    // ถ้าไม่ถูกใช้งาน เปิดปุ่มลบได้
                    msgEl.textContent =
                        'Apple ID นี้ไม่ได้ถูกใช้งานกับสัญญาใด ต้องการลบหรือไม่?';
                    confirmBtn.disabled = false;
                }
            } catch (err) {
                console.error(err);
                msgEl.innerHTML =
                    `<div class="text-danger">เกิดข้อผิดพลาดในการตรวจสอบ:<br>${err.message}</div>`;
                confirmBtn.disabled = true;
            }
        });
    });

    // แค่ submit form ปกติไปหน้า PHP เดิม
});
</script>