

// Close modal when clicking overlay
document.addEventListener('DOMContentLoaded', function() {

  const approveModal = document.getElementById('approvalOverlay')
  const viewApproval = document.querySelector('.action-btn-approve')
  const exitApprovalModal = document.querySelector('.approval-close')
  function openApprovalModal() {
    approveModal.classList.add('active');
  }

  function closeApprovalModal() {
    approveModal.classList.remove('active');
}
  
viewApproval.addEventListener('click',openApprovalModal)
  exitApprovalModal.addEventListener('click',closeApprovalModal)

});