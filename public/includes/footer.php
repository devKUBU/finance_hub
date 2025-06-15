<?php
// File: public/includes/footer.php

// ถ้าไม่ใช่หน้า login ให้แสดง footer
if (empty($hideNav)): ?>
<footer class="site-footer text-end py-3">
    &copy; <?= date('Y') ?> Nano Friend Technology. All Rights Reserved.
</footer>
<?php endif; ?>



<?php if (!empty($hideNav)): ?>
<script src="<?= $baseURL ?>/assets/js/login.js" defer></script>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Elements
    const sbBtn = document.getElementById('sidebarToggle'),
        sidebar = document.getElementById('sidebar'),
        main = document.querySelector('.main-content'),
        themeBtn = document.getElementById('themeToggle'),
        themeIcon = document.getElementById('themeIcon'),
        apiUrl = '<?= $baseURL ?>/api/toggle_theme.php',
        mobileBp = 991.98; // breakpoint for Mobile

    // 1) Load saved sidebar state from localStorage (Desktop only)
    if (window.innerWidth > mobileBp) {
        const savedCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
        if (savedCollapsed) {
            sidebar.classList.add('collapsed');
            main.classList.add('sidebar-collapsed');
        }
    }

    // 2) Sidebar toggle
    if (sbBtn && sidebar && main) {
        sbBtn.addEventListener('click', () => {
            if (window.innerWidth <= mobileBp) {
                // Mobile: just show/hide
                sidebar.classList.toggle('show');
            } else {
                // Desktop: collapse/expand + save to localStorage
                sidebar.classList.toggle('collapsed');
                main.classList.toggle('sidebar-collapsed');
                localStorage.setItem(
                    'sidebarCollapsed',
                    sidebar.classList.contains('collapsed')
                );
            }
        });

        // On resize, if moving to desktop, remove mobile show
        window.addEventListener('resize', () => {
            if (window.innerWidth > mobileBp) {
                sidebar.classList.remove('show');
            }
        });
    }

    // 3) Theme toggle
    if (themeBtn && themeIcon) {
        // initialize icon
        let theme = document.documentElement.getAttribute('data-theme') || 'light';
        themeIcon.className = 'fa-solid ' + (theme === 'light' ? 'fa-moon' : 'fa-sun');

        themeBtn.addEventListener('click', async () => {
            theme = theme === 'light' ? 'dark' : 'light';
            const resp = await fetch(apiUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'theme=' + encodeURIComponent(theme)
            });
            const json = await resp.json();
            if (json.theme) {
                document.documentElement.setAttribute('data-theme', json.theme);
                themeIcon.className = 'fa-solid ' + (json.theme === 'light' ? 'fa-moon' : 'fa-sun');
            }
        });
    }

    // 4) Initialize all Bootstrap modals on the page
    document.querySelectorAll('.modal').forEach(modalEl => {
        bootstrap.Modal.getOrCreateInstance(modalEl);
    });
});
</script>


<?php
// ดึงข้อมูล Toast จากเซสชันและลบทิ้งหลังแสดงผล
$toast = $_SESSION['toast'] ?? null;
unset($_SESSION['toast']);

if ($toast):
  $type = $toast['type'] ?? 'success';    // กำหนดประเภท Toast (success, danger, warning)
  $msg  = htmlspecialchars($toast['msg'] ?? ''); // ข้อความของ Toast
  $icon = [ // กำหนดไอคอนตามประเภท Toast
              'success' => 'fa-circle-check',
              'danger'  => 'fa-circle-xmark',
              'warning' => 'fa-triangle-exclamation'
            ][$type] ?? 'fa-circle-info';
?>
<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index:1100">
    <div id="liveToast" class="toast toast-solid text-<?= $type ?> border-0" role="alert" data-bs-delay="3500">
        <div class="d-flex">
            <div class="toast-leftbar bg-<?= $type ?>"></div>
            <div class="toast-icon"><i class="fa-solid <?= $icon ?>"></i></div>
            <div class="toast-body flex-grow-1"><?= $msg ?></div>
            <button type="button" class="btn-close ms-2 me-2" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // แสดง Toast Message ทันทีที่ DOM โหลดเสร็จ
    const el = document.getElementById('liveToast');
    if (el) bootstrap.Toast.getOrCreateInstance(el).show();
});
</script>
<?php endif; ?>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.querySelectorAll('.btn-delete-admin').forEach(btn => {
    btn.addEventListener('click', () => {
        const id = btn.dataset.id;
        const name = btn.dataset.username;
        Swal.fire({
            title: `ลบแอดมิน ${name}`,
            text: 'ต้องการลบแอดมินคนนี้ใช่หรือไม่?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'ใช่, ลบเลย',
            cancelButtonText: 'ยกเลิก'
        }).then(result => {
            if (result.isConfirmed) {
                window.location.href = `?delete=${id}`;
            }
        });
    });
});
</script>
</body>

</html>