<?php
// File: public/pages/superadmin/payments/partials/payment_modals.php
// – Modal “รายละเอียดสัญญา”
?>
<div class="modal fade" id="contractModal" tabindex="-1" aria-labelledby="contractModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title" id="contractModalLabel">
                    <i class="fa-solid fa-file-contract me-2"></i>รายละเอียดสัญญา
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="ปิด"></button>
            </div>
            <div class="modal-body" id="contractBody">
                <div class="text-center text-muted py-4">กำลังโหลดข้อมูล...</div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal ดูรายละเอียดการชำระ -->
<div class="modal fade" id="viewPaymentModal" data-bs-backdrop="false" tabindex="-1" aria-hidden="true">

    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">รายละเอียดการชำระ</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="viewPaymentBody">
                <!-- JS จะเติมข้อมูลตรงนี้ -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="slipLightbox" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-transparent border-0">
            <img src="" id="lightboxImage" class="img-fluid rounded" alt="Slip Image">
        </div>
    </div>
</div>

<div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title" id="paymentModalLabel">
                    <i class="fa-solid fa-hand-holding-dollar me-2"></i>
                    <span id="modalModeTitle">ชำระงวด</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="ปิด"></button>
            </div>
            <div class="modal-body py-3">
                <form id="paymentForm" method="post" enctype="multipart/form-data">
                    <input type="hidden" id="modal_payment_id" name="payment_id" value="">
                    <input type="hidden" id="modal_mode" name="mode" value="new">

                    <!-- Section A: ข้อมูลสัญญา & งวดที่กำลังชำระ (readonly) -->
                    <div class="container-fluid mb-4">
                        <div class="row gx-3 gy-2">
                            <div class="col-md-3">
                                <label class="form-label text-muted"><i
                                        class="fa-solid fa-file-contract me-1"></i>เลขสัญญา</label>
                                <input type="text" id="modal_contract_no" class="form-control" readonly>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label text-muted"><i
                                        class="fa-solid fa-user me-1"></i>ชื่อลูกค้า</label>
                                <input type="text" id="modal_customer_name" class="form-control" readonly>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label text-muted"><i class="fa-solid fa-hashtag me-1"></i>งวด
                                    (No.)</label>
                                <input type="text" id="modal_pay_no" class="form-control text-center" readonly>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label text-muted"><i
                                        class="fa-solid fa-calendar-day text-info me-1"></i>วันที่ครบกำหนด</label>
                                <input type="text" id="modal_due_date" class="form-control text-center" readonly>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label text-muted"><i
                                        class="fa-solid fa-exclamation-triangle text-warning me-1"></i>ยอดที่ต้องจ่าย</label>
                                <input type="text" id="modal_amount_due" class="form-control text-end" readonly>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label text-muted"><i
                                        class="fa-solid fa-wallet me-1"></i>ยอดคงเหลือ</label>
                                <input type="text" id="modal_remaining_balance" class="form-control text-end" readonly>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <!-- Section B: ฟอร์มกรอกข้อมูลการชำระจริง -->
                    <div class="container-fluid">
                        <div class="row gx-3 gy-2">
                            <div class="col-md-4">
                                <label for="amount_paid" class="form-label"><i
                                        class="fa-solid fa-money-bill-wave me-1"></i>จำนวนเงินที่จ่ายจริง</label>
                                <input type="number" step="0.01" name="amount_paid" id="amount_paid"
                                    class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label for="penalty_amount" class="form-label"><i
                                        class="fa-solid fa-gavel me-1"></i>ค่าปรับ</label>
                                <input type="number" step="0.01" name="penalty_amount" id="penalty_amount"
                                    class="form-control" value="0.00">
                            </div>
                            <div class="col-md-4">
                                <label for="fee_unlock" class="form-label"><i
                                        class="fa-solid fa-lock-open me-1"></i>ค่าปลดล็อค</label>
                                <input type="number" step="0.01" name="fee_unlock" id="fee_unlock" class="form-control"
                                    value="0.00">
                            </div>
                            <div class="col-md-4">
                                <label for="fee_document" class="form-label"><i
                                        class="fa-solid fa-file-lines me-1"></i>ค่าเอกสาร</label>
                                <input type="number" step="0.01" name="fee_document" id="fee_document"
                                    class="form-control" value="0.00">
                            </div>
                            <div class="col-md-4">
                                <label for="fee_other" class="form-label"><i
                                        class="fa-solid fa-handshake-simple me-1"></i>ค่าบริการอื่น ๆ</label>
                                <input type="number" step="0.01" name="fee_other" id="fee_other" class="form-control"
                                    value="0.00">
                            </div>
                            <div class="col-md-8">
                                <label for="note" class="form-label"><i
                                        class="fa-solid fa-note-sticky me-1"></i>หมายเหตุ</label>
                                <textarea name="note" id="note" class="form-control" rows="2"></textarea>
                            </div>
                            <div class="col-md-4">
                                <label for="slip_file" class="form-label"><i
                                        class="fa-solid fa-file-image me-1"></i>อัปโหลดสลิปโอนเงิน</label>
                                <input type="file" name="slip_file[]" id="slip_file" class="form-control" multiple
                                    accept="image/*">
                                <div class="form-text">ขนาดไฟล์สูงสุด 2 MB</div>
                            </div>
                        </div>
                    </div>

                    <!-- ซ่อนฟิลด์ close_balance ภายใน form -->
                    <input type="hidden" name="close_balance" id="close_balance_field" value="0">
                </form>
            </div>

            <div class="modal-footer border-0">
                <div class="d-flex justify-content-end w-100">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="fa-solid fa-xmark me-1"></i>ยกเลิก
                    </button>
                    <button type="button" id="modalSaveBtn" class="btn btn-primary ms-2">
                        <span id="modalSaveSpinner" class="spinner-border spinner-border-sm d-none" role="status"
                            aria-hidden="true"></span>
                        <i class="fa-solid fa-floppy-disk me-1"></i>บันทึก
                    </button>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- ───────────────── Modal ยืนยันการบันทึก ────────────────── -->
<div class="modal fade" id="confirmSaveModal" tabindex="-1" aria-labelledby="confirmSaveLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title" id="confirmSaveLabel">
                    <i class="fa-solid fa-circle-question text-warning me-2"></i>
                    ยืนยันการดำเนินการ
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="ปิด"></button>
            </div>
            <div class="modal-body">
                <p id="confirmSaveText" class="m-0 text-center fs-5">
                    <!-- จะใส่ข้อความผ่าน JS -->
                </p>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                <button type="button" id="confirmSaveYes" class="btn btn-primary">
                    ตกลง
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ======================================================
     Modal “ต้นทุน/ค่าใช้จ่ายสัญญา” (expenseModal)
====================================================== -->
<div class="modal fade" id="expenseModal" tabindex="-1" aria-labelledby="expenseModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <!-- Header -->
            <div class="modal-header border-0">
                <h5 class="modal-title" id="expenseModalLabel">
                    <i class="fa-solid fa-wallet me-1"></i>ต้นทุน/ค่าใช้จ่ายสัญญา
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="ปิด"></button>
            </div>

            <!-- Body -->
            <div class="modal-body">

                <!-- (1) ส่วนสรุปต้นทุนทั้งหมด -->
                <div class="mb-4">
                    <h6>
                        <i class="fa-solid fa-wallet text-primary me-1"></i>
                        สรุปต้นทุนทั้งหมด
                    </h6>
                    <div class="row gx-2">
                        <div class="col-md-6">
                            <div class="card shadow-sm">
                                <div class="card-body p-2 d-flex align-items-center">
                                    <i class="fa-solid fa-money-bill-transfer text-success fa-lg me-2"></i>
                                    <div>
                                        ค่าคอมมิชชั่นร้านค้า:
                                        <span id="expense_commission_amount">0.00</span> ฿
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card shadow-sm">
                                <div class="card-body p-2 d-flex align-items-center">
                                    <i class="fa-solid fa-file-lock text-warning fa-lg me-2"></i>
                                    <div>
                                        ค่าโปรแกรมล็อค:
                                        <span id="expense_lock_program_amount">0.00</span> ฿
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mt-2">
                            <div class="card shadow-sm">
                                <div class="card-body p-2 d-flex align-items-center">
                                    <i class="fa-solid fa-file-contract text-info fa-lg me-2"></i>
                                    <div>
                                        ต้นทุนสินเชื่อ (สัญญา):
                                        <span id="expense_loan_amount">0.00</span> ฿
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mt-2">
                            <div class="card shadow-sm">
                                <div class="card-body p-2 d-flex align-items-center">
                                    <i class="fa-solid fa-file-invoice-dollar text-secondary fa-lg me-2"></i>
                                    <div>
                                        ค่าใช้จ่ายอื่นๆ:
                                        <span id="expense_other_amount">0.00</span> ฿
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 my-4">
                            <div class="card border-primary shadow-sm">
                                <div class="card-body bg-light text-center">
                                    <!-- ไอคอนใหญ่ -->
                                    <i class="fa-solid fa-coins fa-3x text-danger mb-2"></i>
                                    <!-- หัวข้อ -->
                                    <h5 class="card-title mb-1">ต้นทุนรวมทั้งหมด</h5>
                                    <!-- ตัวเลขใหญ่เด่นชัด -->
                                    <div id="expense_total_amount" class="display-4 fw-bold text-danger">
                                        0.00 ฿
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <hr>

                <!-- (2) ตารางแสดงรายการค่าใช้จ่ายที่เคยบันทึก -->
                <div class="mb-3">
                    <h6><i class="fa-solid fa-list-ul text-secondary me-1"></i> รายการค่าใช้จ่ายที่บันทึกแล้ว</h6>
                    <div class="table-responsive" style="max-height:200px; overflow:auto;">
                        <table class="table table-sm table-bordered align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-center" style="width: 40px;">#</th>
                                    <th>ประเภท</th>
                                    <th class="text-end">จำนวนเงิน (฿)</th>
                                    <th>หมายเหตุ</th>
                                    <th class="text-center">วันที่บันทึก</th>
                                    <th class="text-center">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody id="expenseExistingList">
                                <tr>
                                    <td colspan="6" class="text-center text-muted">กำลังโหลด…</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <hr>

                <!-- (3) ฟอร์มเพิ่ม/แก้ไขรายการค่าใช้จ่าย -->
                <div>
                    <h6><i class="fa-solid fa-plus-circle text-success me-1"></i> เพิ่ม/แก้ไขรายการค่าใช้จ่าย</h6>
                    <form id="expenseForm" class="row gx-2 gy-2">
                        <input type="hidden" name="contract_id" id="expense_contract_id" value="">
                        <input type="hidden" name="expense_id" id="expense_id" value="">

                        <div class="col-md-4">
                            <label class="form-label">ประเภท <span class="text-danger">*</span></label>
                            <select name="expense_type" id="newExpenseType" class="form-select">
                                <option value="">-- เลือกประเภท --</option>
                                <option value="lock_program">ค่าโปรแกรมล็อค</option>
                                <!-- <option value="disbursement">ต้นทุนปล่อยสินเชื่อ</option> -->
                                <option value="other">ค่าใช้จ่ายอื่น ๆ</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">จำนวนเงิน (฿) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="amount" id="newExpenseAmount" class="form-control"
                                placeholder="0.00">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">หมายเหตุ (ถ้ามี)</label>
                            <input type="text" name="note" id="newExpenseNote" class="form-control"
                                placeholder="เช่น ค่าจัดส่ง, เบิกสำรอง">
                        </div>

                        <div class="col-md-2 d-grid">
                            <button type="button" id="btnAddExpense" class="btn btn-primary">
                                <i class="fa-solid fa-plus me-1"></i>
                                <span id="expenseButtonLabel">เพิ่ม</span>
                            </button>
                        </div>
                    </form>
                </div>

            </div> <!-- /.modal-body -->

            <!-- Footer -->
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">
                    ปิด
                </button>
            </div>
        </div>
    </div>
</div>

<?php
// หลังจาก modal ตัวสุดท้ายของไฟล์ payment_modals.php
// —————————————
// Modal สรุปยอดปิดสัญญา
// —————————————
?>
<div class="modal fade" id="closeContractModal" tabindex="-1" aria-labelledby="closeContractModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="closeContractModalLabel">
                    <i class="fa-solid fa-file-contract me-2"></i>สรุปยอดปิดสัญญา
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="ปิด"></button>
            </div>
            <div class="modal-body">
                <ul class="list-group">
                    <li class="list-group-item d-flex justify-content-between">
                        ยอดรวมที่ต้องจ่าย:
                        <span id="close_sumDue">0.00</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        ยอดที่จ่ายแล้ว:
                        <span id="close_sumPaid">0.00</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        ค่าปรับ:
                        <span id="close_sumPenalty">0.00</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        ค่าปลดล็อค:
                        <span id="close_sumUnlock">0.00</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        ค่าเอกสาร:
                        <span id="close_sumDoc">0.00</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        ค่าบริการอื่น:
                        <span id="close_sumOther">0.00</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between fw-bold">
                        ยอดคงเหลือ:
                        <span id="close_remaining">0.00</span>
                    </li>
                </ul>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">ยกเลิก</button>
                <button type="button" class="btn btn-danger" id="confirmCloseContract"
                    data-contract-id="<?= $contractId ?>">
                    <i class="fa-solid fa-check me-1"></i>ยืนยันปิดสัญญา
                </button>
            </div>
        </div>
    </div>
</div>