<?php
// File: public/pages/superadmin/activity_log.php (Final Fix for Theming)

require_once realpath(__DIR__ . '/../../../config/bootstrap.php');
require_once ROOT_PATH . '/includes/helpers.php';
if (session_status() === PHP_SESSION_NONE) session_start();
requireRole(['superadmin', 'admin']);

$pageTitle = 'Activity Log';
include ROOT_PATH . '/public/includes/header.php';
include ROOT_PATH . '/public/includes/sidebar.php';

// --- โหลด CSS ที่จำเป็นทั้งหมด ---
echo '<link rel="stylesheet" href="'. htmlspecialchars($baseURL) .'/assets/css/dashboard.css">';
echo '<link rel="stylesheet" href="'. htmlspecialchars($baseURL) .'/assets/css/activity_log.css">';
?>
<style>
/* สไตล์สำหรับไอคอนสีและตาราง (ยังคงไว้เหมือนเดิม) */
.log-icon {
    width: 30px;
    height: 30px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    color: #fff;
    font-size: 0.9em;
}

.log-icon.login {
    background-color: #28a745;
}

.log-icon.logout {
    background-color: #6c757d;
}

.log-icon.create,
.log-icon.save_payment {
    background-color: #007bff;
}

.log-icon.edit,
.log-icon.update,
.log-icon.approve_contracts {
    background-color: #ffc107;
}

.log-icon.delete {
    background-color: #dc3545;
}

.log-icon.approve {
    background-color: #17a2b8;
}

.log-icon.reject {
    background-color: #343a40;
}

.log-icon.default {
    background-color: #6c757d;
}

th.sortable {
    cursor: pointer;
    user-select: none;
}

th.sortable:hover {
    background-color: rgba(0, 0, 0, 0.05);
}

[data-theme="dark"] th.sortable:hover {
    background-color: rgba(255, 255, 255, 0.05);
}
</style>

<main class="main-content">
    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="m-0"><i class="fa-solid fa-book me-2"></i> Activity Log</h3>
            <div class="header-actions d-flex align-items-center">
                <button id="sidebarToggle" class="btn-icon"><i class="fa-solid fa-bars"></i></button>
                <button id="themeToggle" class="btn-icon ms-2"><i id="themeIcon" class="fa-solid"></i></button>
            </div>
        </div>
        <hr>

        <div class="card shadow-sm">
            <div class="card-header bg-body-tertiary d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h6 class="m-0">ประวัติการใช้งานทั้งหมด</h6>
                <div class="col-12 col-md-5 col-lg-4">
                    <input type="text" id="searchInput" class="form-control form-control-sm" placeholder="🔍 ค้นหา...">
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="activityLogTable">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center sortable" data-sort="time" style="width: 5%;"># <i
                                        class="fa-solid fa-sort"></i></th>
                                <th class="sortable" data-sort="time" style="width: 15%;">เวลา <i
                                        class="fa-solid fa-sort"></i></th>
                                <th class="sortable" data-sort="username">ผู้กระทำ <i class="fa-solid fa-sort"></i></th>
                                <th class="sortable" data-sort="action">Action <i class="fa-solid fa-sort"></i></th>
                                <th>Target</th>
                                <th>รายละเอียด</th>
                                <th style="width: 10%;">IP Address</th>
                            </tr>
                        </thead>
                        <tbody id="logTableBody">
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-body-tertiary d-flex justify-content-between align-items-center">
                <div id="pagination-summary" class="text-muted small"></div>
                <div id="pagination-controls"></div>
            </div>
        </div>
    </div>
</main>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
<script>
window.BASE_URL = "<?= rtrim($baseURL, '/') ?>";

// โค้ด JavaScript จะเหลือแค่ส่วนที่ควบคุมตารางเท่านั้น
$(function() {
    const tableBody = $('#logTableBody');
    const searchInput = $('#searchInput');
    const paginationControls = $('#pagination-controls');
    const paginationSummary = $('#pagination-summary');

    let currentPage = 1,
        search = '',
        sort = 'time',
        dir = 'desc';
    let request = null,
        searchTimeout;

    function fetchData() {
        if (request) {
            request.abort();
        }
        let loadingRow =
            `<tr><td colspan="7" class="text-center p-5"><div class="spinner-border text-primary" role="status"></div></td></tr>`;
        tableBody.html(loadingRow);
        paginationSummary.text('กำลังโหลด...');

        request = $.ajax({
            url: `${window.BASE_URL}/pages/superadmin/payments/api/get_activity_log.php`,
            type: 'GET',
            data: {
                page: currentPage,
                search: search,
                sort: sort,
                dir: dir
            },
            dataType: 'json',
            success: function(response) {
                renderTable(response.logs, response.pagination);
                renderPagination(response.pagination);
            }
        });
    }

    const actionIcons = {
        login: {
            icon: 'fa-sign-in-alt',
            color: 'login'
        },
        logout: {
            icon: 'fa-sign-out-alt',
            color: 'logout'
        },
        create: {
            icon: 'fa-plus',
            color: 'create'
        },
        save_payment: {
            icon: 'fa-hand-holding-dollar',
            color: 'create'
        },
        edit: {
            icon: 'fa-pencil-alt',
            color: 'edit'
        },
        update: {
            icon: 'fa-sync-alt',
            color: 'edit'
        },
        approve_contracts: {
            icon: 'fa-check-double',
            color: 'edit'
        },
        delete: {
            icon: 'fa-trash-alt',
            color: 'delete'
        },
        approve: {
            icon: 'fa-check',
            color: 'approve'
        },
        reject: {
            icon: 'fa-times',
            color: 'reject'
        },
        default: {
            icon: 'fa-info-circle',
            color: 'default'
        }
    };

    function renderTable(logs, pagination) {
        tableBody.empty();
        if (logs.length === 0) {
            tableBody.html(
                `<tr><td colspan="7" class="text-center p-5 text-muted"><h4><i class="fa-regular fa-folder-open"></i></h4> ไม่พบข้อมูล</td></tr>`
                );
            return;
        }

        const itemsPerPage = 50; // ต้องตรงกับใน API
        logs.forEach((log, index) => {
            const sequenceNumber = pagination.totalItems - ((currentPage - 1) * itemsPerPage) - index;
            const actionKey = Object.keys(actionIcons).find(key => log.action.toLowerCase().includes(
                key)) || 'default';
            const {
                icon,
                color
            } = actionIcons[actionKey];
            const logIcon = `<span class="log-icon ${color}"><i class="fa-solid ${icon}"></i></span>`;
            const formattedTime = new Date(log.created_at).toLocaleString('th-TH', {
                dateStyle: 'short',
                timeStyle: 'medium'
            });
            const row = `
                    <tr>
                        <td class="text-center">${sequenceNumber}</td>
                        <td>${formattedTime}</td>
                        <td>${log.username || 'System'}</td>
                        <td><div class="d-flex align-items-center">${logIcon}<span class="ms-2">${log.action}</span></div></td>
                        <td>${log.target_type || ''} ${log.target_id ? '#' + log.target_id : ''}</td>
                        <td>${log.description}</td>
                        <td>${log.ip_address}</td>
                    </tr>
                `;
            tableBody.append(row);
        });
    }

    function renderPagination(p) {
        paginationControls.empty();
        paginationSummary.empty();
        if (p.totalItems === 0) {
            paginationSummary.text('ไม่พบรายการ');
            return;
        }
        let itemsPerPage = 50; // ต้องตรงกับใน API
        let startItem = (p.page - 1) * itemsPerPage + 1;
        let endItem = Math.min(p.page * itemsPerPage, p.totalItems);
        paginationSummary.text(`แสดง ${startItem} - ${endItem} จากทั้งหมด ${p.totalItems} รายการ`);
        if (p.totalPages <= 1) return;
        let ul = $('<ul class="pagination pagination-sm mb-0"></ul>');
        ul.append(
            `<li class="page-item ${p.page === 1 ? 'disabled' : ''}"><a class="page-link" href="#" data-page="${p.page - 1}">&laquo;</a></li>`
            );
        // สามารถเพิ่ม Logic การแสดงเลขหน้าแบบย่อได้ที่นี่
        for (let i = 1; i <= p.totalPages; i++) {
            ul.append(
                `<li class="page-item ${p.page === i ? 'active' : ''}"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`
                );
        }
        ul.append(
            `<li class="page-item ${p.page === p.totalPages ? 'disabled' : ''}"><a class="page-link" href="#" data-page="${p.page + 1}">&raquo;</a></li>`
            );
        paginationControls.append(ul);
    }

    $('#activityLogTable thead').on('click', 'th.sortable', function() {
        const newSort = $(this).data('sort');
        if (sort === newSort) {
            dir = dir === 'desc' ? 'asc' : 'desc';
        } else {
            sort = newSort;
            dir = 'desc';
        }
        $('#activityLogTable thead i').removeClass('fa-sort-up fa-sort-down').addClass('fa-sort');
        $(this).find('i').removeClass('fa-sort').addClass(dir === 'desc' ? 'fa-sort-down' :
            'fa-sort-up');
        currentPage = 1;
        fetchData();
    });

    searchInput.on('keyup', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            currentPage = 1;
            search = $(this).val();
            fetchData();
        }, 500);
    });

    paginationControls.on('click', 'a.page-link', function(e) {
        e.preventDefault();
        const page = parseInt($(this).data('page'));
        if (page && !isNaN(page) && page !== currentPage) {
            currentPage = page;
            fetchData();
        }
    });

    fetchData();
});
</script>

<?php include ROOT_PATH . '/public/includes/footer.php'; ?>