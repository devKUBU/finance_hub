document.addEventListener('DOMContentLoaded', () => {
  // ดักทุก modal ที่เป็น evidence
  document.querySelectorAll('.modal[id^="evidenceModal-"]').forEach(modalEl => {
    // รอ event ก่อน modal จะเปิด (Bootstrap 5)
    modalEl.addEventListener('show.bs.modal', () => {
      const form = modalEl.querySelector('form.evidence-form');
      if (!form) return; // ไม่มีฟอร์มก็ออก

      // ดึง contract_id จาก hidden input
      const cid = form.querySelector('[name="evidence_contract_id"]').value;

      fetch(`${baseURL}/pages/shop/contract/api/contract-status.php?id=${cid}`)
        .then(res => res.json())
        .then(data => {
          if (data.approval_status === 'approved') {
            // ถ้าอนุมัติแล้ว ให้ปิดการอัปโหลด / ลบ
            // 1) ปิด input file กับปุ่มบันทึก
            form.querySelectorAll('input[type="file"], button.save-btn')
                .forEach(el => el.disabled = true);
            // 2) ปิดปุ่มลบหลักฐาน (delete-evidence.php)
            modalEl.querySelectorAll('form[action="delete-evidence.php"] button[type="submit"]')
                .forEach(el => el.disabled = true);
            // 3) แสดง alert บน modal ถ้ายังไม่แสดง
            if (!modalEl.querySelector('.evidence-locked-alert')) {
              const alert = document.createElement('div');
              alert.className = 'alert alert-secondary evidence-locked-alert';
              alert.innerHTML = '<i class="fa-solid fa-lock me-1"></i> สัญญานี้อนุมัติแล้ว ไม่สามารถแก้ไขหลักฐานได้';
              form.prepend(alert);
            }
          }
        })
        .catch(console.error);
    });
  });
});
