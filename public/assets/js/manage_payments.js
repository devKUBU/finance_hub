// assets/js/manage_payments.js (The Truly Final Version with ALL Logic)

$(function () {
    // =========================================================================
    //  1. ─── ตัวแปรและฟังก์ชันหลัก ──────────────────────────────────────────
    // =========================================================================
    const searchInput = $('#searchContracts');
    const statusSelect = $('#filterStatus');
    const tableBody = $('#contractsTableBody');
    const paginationControls = $('#pagination-controls');
    const paginationSummary = $('#pagination-summary');

    let currentPage = 1, search = '', status = 'all';
    let sort = '', dir = '';
    let request = null, searchTimeout, currentContractId = null, payMode = 'new';
    const fmt = n => (parseFloat(n) || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    function fetchData() {
        search = searchInput.val();
        status = statusSelect.val();
        let loadingRow = `<tr><td colspan="9" class="text-center p-5"><div class="spinner-border text-primary" role="status"></div><div class="mt-2 text-muted">กำลังโหลดข้อมูล...</div></td></tr>`;
        tableBody.html(loadingRow);
        paginationSummary.text('');
        paginationControls.empty();
        if (request) { request.abort(); }

        request = $.ajax({
            url: `${window.BASE_URL}/pages/superadmin/payments/api/get_payments_data.php`,
            type: 'GET',
            data: { page: currentPage, search: search, status: status, sort: sort, dir: dir },
            dataType: 'json',
            success: (response) => {
                if (response && response.contracts && response.pagination) {
                    renderTable(response.contracts, response.pagination);
                    renderPagination(response.pagination);
                    updateURL();
                } else {
                    tableBody.html(`<tr><td colspan="9" class="text-center p-5 text-danger">ข้อมูลที่ได้รับจาก Server ไม่ถูกต้อง</td></tr>`);
                }
            },
            error: (jqXHR, textStatus) => {
                if (textStatus === 'abort') { return; }
                tableBody.html(`<tr><td colspan="9" class="text-center p-5 text-danger">เกิดข้อผิดพลาดในการโหลดข้อมูล</td></tr>`);
                paginationSummary.text('โหลดข้อมูลล้มเหลว');
            }
        });
    }

    function renderTable(contracts, pagination) {
        tableBody.empty();
        if (contracts.length === 0) {
            tableBody.html(`<tr><td colspan="9" class="text-center p-5 text-muted"><h4><i class="fa-regular fa-folder-open"></i></h4> ไม่พบข้อมูล</td></tr>`);
            return;
        }
        
        const itemsPerPage = 25;
        const today = new Date().toISOString().slice(0, 10);
        
        contracts.forEach((contract, index) => {
            const sequenceNumber = ((pagination.page - 1) * itemsPerPage) + index + 1;
            let remainingAmount = (parseFloat(contract.total_due_amount) || 0) - (parseFloat(contract.total_paid_amount) || 0);
            let isClosed = (remainingAmount <= 0 && parseFloat(contract.total_due_amount) > 0);
            let statusBadge = '', blinkClass = 'blink-overdue';

            if (isClosed) { statusBadge = '<span class="badge text-bg-secondary">ปิดสัญญา</span>'; }
            else if (contract.next_status === 'paid') { statusBadge = '<span class="badge text-bg-success">ชำระล่าสุด</span>'; }
            else if (contract.next_due_date < today) { statusBadge = `<span class="badge text-bg-danger ${blinkClass}">เกินกำหนด</span>`; }
            else if (contract.next_due_date === today) { statusBadge = '<span class="badge text-bg-warning">ครบวันนี้</span>'; }
            else { statusBadge = '<span class="badge text-bg-info">รอชำระ</span>'; }
            
            let actionButtons = `
                <button class="btn btn-sm btn-outline-warning btn-add-expense me-1" 
                    data-contract-id="${contract.contract_id}" 
                    title="เพิ่ม/ดูค่าใช้จ่าย"
                    data-bs-toggle="modal" data-bs-target="#expenseModal">
                    <i class="fa-solid fa-wallet"></i> ค่าใช้จ่าย
                </button>
                <button class="btn btn-sm btn-outline-primary btn-details" 
                    data-contract-id="${contract.contract_id}"
                    data-contract-no="${contract.contract_no}"
                    data-customer-name="${contract.customer_name}"
                    title="ดูรายละเอียดทั้งหมด">
                    <i class="fa-solid fa-list"></i> รายละเอียด
                </button>
            `;
            
            let row = `
                <tr id="contract-row-${contract.contract_id}">
                    <td class="text-center">${sequenceNumber}</td>
                    <td>${contract.contract_no}</td>
                    <td>${contract.customer_name}</td>
                    <td class="text-center"><div>งวดที่ ${contract.next_pay_no || '-'}/${contract.total_installments || '-'}</div><div class="small text-muted">${contract.next_due_date ? new Date(contract.next_due_date).toLocaleDateString('th-TH', { day: '2-digit', month: 'short', year: 'numeric'}) : '-'}</div></td>
                    <td class="text-end">${fmt(contract.next_amount_due)} ฿</td>
                    <td class="text-end text-success-emphasis">${fmt(contract.total_paid_amount)} ฿</td>
                    <td class="text-end text-danger-emphasis fw-bold">${fmt(remainingAmount)} ฿</td>
                    <td class="text-center">${statusBadge}</td>
                    <td class="text-center">${actionButtons}</td>
                </tr>
            `;
            tableBody.append(row);
        });
    }

    function renderPagination(p) {
        paginationControls.empty();
        paginationSummary.empty();
        if(!p || p.totalItems === 0) { paginationSummary.text('ไม่พบรายการ'); return; }
        let itemsPerPage = 25;
        let startItem = (p.page - 1) * itemsPerPage + 1;
        let endItem = Math.min(p.page * itemsPerPage, p.totalItems);
        paginationSummary.text(`แสดง ${startItem} - ${endItem} จากทั้งหมด ${p.totalItems} รายการ`);
        if (p.totalPages <= 1) return;
        let ul = $('<ul class="pagination pagination-sm mb-0"></ul>');
        ul.append(`<li class="page-item ${p.page === 1 ? 'disabled' : ''}"><a class="page-link" href="#" data-page="${p.page - 1}">&laquo;</a></li>`);
        for (let i = 1; i <= p.totalPages; i++) { ul.append(`<li class="page-item ${p.page === i ? 'active' : ''}"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`); }
        ul.append(`<li class="page-item ${p.page === p.totalPages ? 'disabled' : ''}"><a class="page-link" href="#" data-page="${p.page + 1}">&raquo;</a></li>`);
        paginationControls.append(ul);
    }
    
    function updateURL() {
        const params = new URLSearchParams(window.location.search);
        params.set('page', currentPage);
        if (search) { params.set('search', search); } else { params.delete('search'); }
        if (status !== 'all') { params.set('status', status); } else { params.delete('status'); }
        if (sort) { params.set('sort', sort); } else { params.delete('sort'); }
        if (dir) { params.set('dir', dir); } else { params.delete('dir'); }
        history.replaceState({}, '', `${window.location.pathname}?${params.toString()}`);
    }

    // --- Event Listeners ---
    searchInput.on('keyup', function () { clearTimeout(searchTimeout); searchTimeout = setTimeout(() => { currentPage = 1; fetchData(); }, 500); });
    statusSelect.on('change', function () { currentPage = 1; fetchData(); });
    paginationControls.on('click', 'a.page-link', function (e) { e.preventDefault(); const page = parseInt($(this).data('page')); if (page && !isNaN(page) && page !== currentPage) { currentPage = page; fetchData(); } });
    $('#contractsTable thead').on('click', 'th.sortable', function() {
        const newSort = $(this).data('sort');
        if (!newSort) return;
        if (sort === newSort) {
            dir = dir === 'asc' ? 'desc' : 'asc';
        } else {
            sort = newSort;
            dir = 'asc';
        }
        $('#contractsTable thead i.fa-sort, #contractsTable thead i.fa-sort-up, #contractsTable thead i.fa-sort-down').removeClass('fa-sort-up fa-sort-down').addClass('fa-sort');
        $(this).find('i').removeClass('fa-sort').addClass(dir === 'asc' ? 'fa-sort-up' : 'fa-sort-down');
        currentPage = 1;
        fetchData();
    });
    
    // --- Initial Load ---
    const initialParams = new URLSearchParams(window.location.search);
    currentPage = parseInt(initialParams.get('page')) || 1;
    searchInput.val(initialParams.get('search') || '');
    statusSelect.val(initialParams.get('status') || 'all');
    sort = initialParams.get('sort') || '';
    dir = initialParams.get('dir') || '';
    fetchData();

    // =========================================================================
    //  2. ─── ระบบ MODAL ─────────────────────────────────────────────────────
    // =========================================================================
    $(document).on('click', '.btn-details', function () {
        currentContractId = $(this).attr('data-contract-id');
        const contractNo = $(this).attr('data-contract-no');
        const customerName = $(this).attr('data-customer-name');
        $('#contractModalLabel').text(`สัญญา: ${contractNo} | ลูกค้า: ${customerName}`);
        $('#contractBody').html('<div class="text-center text-muted py-4">กำลังโหลดข้อมูล…</div>');
        $('#contractModal').modal('show');
        $.get(`${window.BASE_URL}/pages/superadmin/payments/api/get_installments.php`, { contract_id: currentContractId, contract_no: contractNo, customer_name: customerName })
            .done(html => $('#contractBody').html(html))
            .fail(() => $('#contractBody').html('<div class="text-danger text-center py-4">โหลดข้อมูลไม่สำเร็จ</div>'));
    });

    function openPaymentModal(mode, data) {
        payMode = mode;
        $('#modal_mode').val(mode);
        $('#modalModeTitle').text(mode === 'new' ? `ชำระงวด ${data.payNo}` : `แก้ไขงวด ${data.payNo}`);
        $('#paymentForm')[0].reset();
        $('#modal_payment_id').val(data.paymentId);
        $('#modal_contract_no').val(data.contractNo);
        $('#modal_customer_name').val(data.customerName);
        $('#modal_pay_no').val(data.payNo);
        $('#modal_due_date').val(new Date(data.dueDate).toLocaleDateString('th-TH'));
        $('#modal_amount_due').val(fmt(data.amountDue));
        $('#penalty_amount').val(fmt(data.penalty || 0));
        if (mode === 'new') {
            // คำนวณยอดที่ต้องจ่ายสำหรับงวดนี้
            $('#amount_paid').val(fmt(data.amountDue - (data.amountPaid || 0)));
            // ตั้งค่าธรรมเนียมอื่นๆ เป็น 0 สำหรับการชำระใหม่
            $('#fee_unlock').val('0.00');
            $('#fee_document').val('0.00');
            $('#fee_other').val('0.00');
        } else { // 'edit' mode
            $('#amount_paid').val(fmt(data.amountPaid));
            // ค่าปรับจะถูกตั้งค่าไปแล้วด้านบน แต่เราจะตั้งค่าธรรมเนียมอื่นๆ จากข้อมูลที่ได้รับ
            $('#fee_unlock').val(fmt(data.feeUnlock));
            $('#fee_document').val(fmt(data.feeDocument));
            $('#fee_other').val(fmt(data.feeOther));
            $('#note').val(data.note);
        }
        $('#paymentModal').modal('show');
    }

    $(document).on('click', '.btn-pay-installment, .btn-edit-payment', function () {
        const $btn = $(this);
        const mode = $btn.hasClass('btn-pay-installment') ? 'new' : 'edit';
        // อ่านค่าจาก data attributes ทีละตัว
        const data = {
            paymentId:    $btn.attr('data-payment-id'), payNo: $btn.attr('data-pay-no'),
            contractNo:   $btn.attr('data-contract-no'), customerName: $btn.attr('data-customer-name'),
            dueDate:      $btn.attr('data-due-date'), amountDue: $btn.attr('data-amount-due'),
            amountPaid:   $btn.attr('data-amount-paid'), penalty: $btn.attr('data-penalty'),
            feeUnlock:    $btn.attr('data-fee-unlock'), feeDocument: $btn.attr('data-fee-document'),
            feeOther:     $btn.attr('data-fee-other'), note: $btn.attr('data-note')
        };
        openPaymentModal(mode, data);
    });

    $('#modalSaveBtn').on('click', () => {
        $('#confirmSaveText').text(`ยืนยันการ${payMode === 'new' ? 'บันทึก' : 'แก้ไข'}ยอดชำระงวดนี้ใช่หรือไม่?`);
        $('#confirmSaveModal').modal('show');
    });

    $('#confirmSaveYes').on('click', () => {
        $('#confirmSaveModal').modal('hide');
        savePayment();
    });

    function savePayment() {
        const $btn = $('#modalSaveBtn'), $spin = $('#modalSaveSpinner');
        $btn.prop('disabled', true);
        $spin.removeClass('d-none');
        $.ajax({
            url: `${window.BASE_URL}/pages/superadmin/payments/api/save_payment.php`,
            method: 'POST', data: new FormData($('#paymentForm')[0]),
            processData: false, contentType: false, dataType: 'json'
        })
        .done(res => {
            if (res.success) {
                $('#paymentModal').modal('hide');
                $('#contractModal').modal('hide');
                Swal.fire({
                    icon: 'success', title: 'บันทึกสำเร็จ!',
                    text: 'ข้อมูลการชำระเงินได้รับการอัปเดตแล้ว',
                    timer: 2000, showConfirmButton: false, timerProgressBar: true
                });
                fetchData();
            } else {
                Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', text: res.error || 'บันทึกข้อมูลไม่สำเร็จ' });
            }
        })
        .fail(() => {
            Swal.fire({ icon: 'error', title: 'การเชื่อมต่อล้มเหลว', text: 'ไม่สามารถติดต่อเซิร์ฟเวอร์ได้' });
        })
        .always(() => { $spin.addClass('d-none'); $btn.prop('disabled', false); });
    }

    $(document).on('click', '.btn-add-expense', function () { const cid = $(this).data('contract-id'); $('#expense_contract_id').val(cid); loadExpenseData(cid); });
    $('#expenseModal').on('hidden.bs.modal', function () { $('#expenseForm')[0].reset(); $('#expense_id').val(''); $('#expenseButtonLabel').text('เพิ่ม'); });

    function loadExpenseData(cid) {
        $('#expenseExistingList').html('<tr><td colspan="6" class="text-center text-muted py-3">กำลังโหลด…</td></tr>');
        $('#expense_commission_amount, #expense_lock_program_amount, #expense_loan_amount, #expense_other_amount, #expense_total_amount').text('0.00 ฿');
        $.get(`${window.BASE_URL}/pages/superadmin/payments/api/get_expenses.php`, { contract_id: cid }, 'json')
        .done(r => {
            if (r.error) { $('#expenseExistingList').html(`<tr><td colspan="6" class="text-center text-danger">${r.error}</td></tr>`); return; }
            $('#expense_commission_amount').text(fmt(r.commission) + ' ฿');
            $('#expense_lock_program_amount').text(fmt(r.lock_program) + ' ฿');
            $('#expense_loan_amount').text(fmt(r.loan_amount) + ' ฿');
            $('#expense_other_amount').text(fmt(r.other) + ' ฿');
            $('#expense_total_amount').html(`${fmt(r.total)} ฿`);
            if (!r.expenses_list?.length) { $('#expenseExistingList').html('<tr><td colspan="6" class="text-center text-muted">ยังไม่มีรายการ</td></tr>'); return; }
            const rows = r.expenses_list.map((v, i) => {
                const typeMap = { lock_program: 'ค่าโปรแกรมล็อค', disbursement: 'ต้นทุนปล่อยสินเชื่อ', other: 'อื่นๆ' };
                return `<tr data-expense-id="${v.id}" data-expense-type="${v.expense_type}" data-amount="${v.amount}" data-note="${v.note || ''}">
                    <td class="text-center">${i + 1}</td><td>${typeMap[v.expense_type] || v.expense_type}</td>
                    <td class="text-end">${fmt(v.amount)}</td><td>${v.note || '-'}</td>
                    <td class="text-center">${new Date(v.created_at).toLocaleString('th-TH')}</td>
                    <td class="text-center"><button class="btn btn-sm btn-warning btn-edit-expense"><i class="fa fa-pen"></i></button> <button class="btn btn-sm btn-danger btn-delete-expense"><i class="fa fa-trash"></i></button></td>
                </tr>`;
            }).join('');
            $('#expenseExistingList').html(rows);
        }).fail(() => $('#expenseExistingList').html('<tr><td colspan="6" class="text-center text-danger">โหลดข้อมูลไม่สำเร็จ</td></tr>'));
    }

    $('#btnAddExpense').on('click', function () {
        const d = {
            contract_id: $('#expense_contract_id').val(), expense_id: $('#expense_id').val(),
            expense_type: $('#newExpenseType').val(), amount: $('#newExpenseAmount').val(),
            note: $('#newExpenseNote').val().trim()
        };
        if (!d.contract_id || !d.expense_type || !(+d.amount > 0)) { return alert('กรุณากรอกประเภทและจำนวนเงิน (>0)'); }
        $.post(`${window.BASE_URL}/pages/superadmin/payments/partials/add_expense.php`, d, 'json')
            .done(r => {
                if (r.success) {
                    $('#expenseForm')[0].reset(); $('#expense_id').val(''); $('#expenseButtonLabel').text('เพิ่ม');
                    loadExpenseData(d.contract_id);
                } else { alert(r.error || 'บันทึกไม่สำเร็จ'); }
            }).fail(() => alert('ระบบมีปัญหา ไม่สามารถบันทึกค่าใช้จ่ายได้'));
    });

    $(document).on('click', '.btn-edit-expense', function () {
        const tr = $(this).closest('tr');
        $('#expense_id').val(tr.data('expense-id'));
        $('#newExpenseType').val(tr.data('expense-type'));
        $('#newExpenseAmount').val(tr.data('amount'));
        $('#newExpenseNote').val(tr.data('note'));
        $('#expenseButtonLabel').text('อัปเดต');
    });

    $(document).on('click', '.btn-delete-expense', function () {
        if (!confirm('ลบรายการนี้ใช่หรือไม่?')) return;
        const id = $(this).closest('tr').data('expense-id');
        const cid = $('#expense_contract_id').val();
        $.post(`${window.BASE_URL}/pages/superadmin/payments/partials/delete_expense.php`, { expense_id: id }, 'json')
            .done(r => r.success ? loadExpenseData(cid) : alert(r.error || 'ลบไม่สำเร็จ'))
            .fail(() => alert('ระบบมีปัญหา ไม่สามารถลบค่าใช้จ่ายได้'));
    });
    
    $(document).on('click', '.btn-view-payment', function () {
        const d = $(this).data();
        let html = `<p><strong>ยอดที่จ่ายแล้ว:</strong> ${fmt(d.amountPaid)}</p><p><strong>ค่าปรับ:</strong> ${fmt(d.penalty)}</p><p><strong>ค่าปลดล็อค:</strong> ${fmt(d.feeUnlock)}</p><p><strong>ค่าเอกสาร:</strong> ${fmt(d.feeDocument)}</p><p><strong>ค่าบริการอื่น:</strong> ${fmt(d.feeOther)}</p><p><strong>หมายเหตุ:</strong> ${d.note || '-'}</p>`;
        if (d.slips) {
            const paths = String(d.slips).split(',');
            if (paths[0]) {
                html += '<hr><p><strong>สลิป:</strong></p><div class="d-flex flex-wrap">';
                paths.forEach(p => {
                    if(p) { 
                        const fullUrl = `${window.BASE_URL}${p}`;
                        html += `<img src="${fullUrl}" class="slip-thumb img-thumbnail me-2 mb-2" style="width:100px; height:100px; object-fit: cover; cursor:pointer;" data-src="${fullUrl}" alt="Slip thumbnail">`;
                    }
                });
                html += '</div>';
            }
        }
        $('#viewPaymentBody').html(html);
        $('#viewPaymentModal').modal('show');
    });
    
    $(document).on('click', '.slip-thumb', function () {
      $('#lightboxImage').attr('src', $(this).data('src'));
      $('#slipLightbox').modal('show');
    });
});