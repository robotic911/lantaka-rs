document.addEventListener('DOMContentLoaded', function () {
  const viewModal = document.getElementById('accountOverlay');
  const viewButtons = document.querySelectorAll('.action-btn-view');
  const exitViewModal = document.querySelector('.account-close');
  const updateForm = document.getElementById('updateAccountForm');

  const btnDeactivate = document.getElementById('btn-deactivate');
  const btnReactivate = document.getElementById('btn-reactivate');
  const btnSave       = document.getElementById('btn-save');

  viewButtons.forEach(button => {
    button.addEventListener('click', function () {
      const userData = this.getAttribute('data-user');
      if (!userData) return;

      const user = JSON.parse(userData);
      console.log(user);
      
      // 1. UPDATE FORM ACTION
      if (updateForm && user.Account_ID) {
        updateForm.setAttribute('action', `/employee/accounts/${user.Account_ID}/update`);
      }

      // 2. TOGGLE BUTTONS based on account status
      const isDeactivated = user.Account_Status === 'deactivate';
      if (btnDeactivate) btnDeactivate.style.display = isDeactivated ? 'none'  : '';
      if (btnReactivate) btnReactivate.style.display = isDeactivated ? ''      : 'none';
      if (btnSave)       btnSave.style.display       = isDeactivated ? 'none'  : '';

      // 2. SPLIT NAME CAREFULLY
      const fullName = user.Account_Name || '';
      const firstSpaceIndex = fullName.indexOf(' ');

      let firstName = '';
      let lastName = '';

      if (firstSpaceIndex !== -1) {
        firstName = fullName.substring(0, firstSpaceIndex);
        lastName = fullName.substring(firstSpaceIndex + 1);
      } else {
        firstName = fullName;
      }

      // 3. FILL INPUTS
      document.getElementById('view_fname').value = firstName;
      document.getElementById('view_lname').value = lastName;
      document.getElementById('view_username').value = user.Account_Username || '';
      document.getElementById('view_email').value = user.Account_Email || '';
      document.getElementById('view_phone').value = user.Account_Phone || '';

      const idPreview     = document.getElementById('view_id_preview');
      const idPlaceholder = document.getElementById('view_id_placeholder');
      const idFileInput   = document.getElementById('view_id_file');

      if (idFileInput) idFileInput.value = '';

      if (idPreview && idPlaceholder) {
        if (user.valid_id_path) {
          idPreview.src = '/storage/' + user.valid_id_path;
          idPreview.style.display = 'block';
          idPlaceholder.style.display = 'none';
        } else {
          idPreview.src = '';
          idPreview.style.display = 'none';
          idPlaceholder.style.display = 'inline';
        }
      }

      viewModal.classList.add('active');
    });
  });

  // Modal closing logic (keeps it clean)
  if (exitViewModal) {
    exitViewModal.addEventListener('click', () => viewModal.classList.remove('active'));
  }
  window.addEventListener('click', (e) => {
    if (e.target === viewModal) viewModal.classList.remove('active');
  });
});
