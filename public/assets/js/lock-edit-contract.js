// File: public/assets/js/lock-edit-contract.js
document.addEventListener('DOMContentLoaded', () => {
  const editForm = document.querySelector('form.needs-validation');
  if (!editForm) return;

  const contractId = editForm.querySelector('input[name="id"]').value; // หรือชื่อ field จริง
  async function isApproved(id) {
    try {
      const res = await fetch(`${baseURL}/pages/shop/contract/api/contract-status.php?id=${id}`);
      const j   = await res.json();
      return j.approval_status === 'approved';
    } catch {
      return false;
    }
  }

  editForm.addEventListener('submit', async e => {
    e.preventDefault();
    if (await isApproved(contractId)) {
      alert('สัญญานี้อนุมัติแล้ว ไม่สามารถแก้ไขได้');
      editForm.querySelectorAll('input,select,button[type=submit]').forEach(x => x.disabled = true);
    } else {
      e.target.submit();
    }
  });
});
