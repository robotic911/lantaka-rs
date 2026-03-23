document.addEventListener('DOMContentLoaded', function () {
  const viewModal = document.getElementById('accountOverlay');
  const viewButtons = document.querySelectorAll('.action-btn-view');
  const exitViewModal = document.querySelector('.account-close');
  const updateForm = document.getElementById('updateAccountForm');

  // ── Password show/hide toggle ─────────────────────────────────
  const eyeOpen = `<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#888" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>`;
  const eyeClosed = `<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#888" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>`;

  const passwordEye   = document.getElementById('viewPasswordEye');
  const passwordInput = document.getElementById('view_password_input');

  if (passwordEye && passwordInput) {
    passwordEye.addEventListener('click', function () {
      const isHidden = passwordInput.type === 'password';
      passwordInput.type = isHidden ? 'text' : 'password';
      passwordEye.innerHTML = isHidden ? eyeClosed : eyeOpen;
    });
  }

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

      const idPreview   = document.getElementById('view_id_preview');
      const idFileInput = document.getElementById('view_id_file');

      if (idFileInput) idFileInput.value = '';

      if (idPreview) {
        const placeholder = idPreview.dataset.placeholder || '';
        idPreview.src = user.valid_id_url || placeholder;
        idPreview.style.display = 'block';
      }

      // Reset password field state on every open
      if (passwordInput) { passwordInput.value = ''; passwordInput.type = 'password'; }
      if (passwordEye)   { passwordEye.innerHTML = eyeOpen; }

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
