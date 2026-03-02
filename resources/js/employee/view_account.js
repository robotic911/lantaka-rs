

// Close modal when clicking overlay
document.addEventListener('DOMContentLoaded', function() {

  const viewModal = document.getElementById('accountOverlay')
  const viewAccount = document.querySelector('.action-btn-view')
  const exitViewModal = document.querySelector('.account-close')

  function openViewModal() {
    viewModal.classList.add('active');
  }

  function closeViewModal() {
    viewModal.classList.remove('active');
}
  

  viewAccount.addEventListener('click',openViewModal)
  exitViewModal.addEventListener('click',closeViewModal)

});