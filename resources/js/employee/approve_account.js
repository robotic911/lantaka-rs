document.addEventListener('DOMContentLoaded', function () {
  const approveModal = document.getElementById('approvalOverlay');
  const approveButtons = document.querySelectorAll('.action-btn-approve');
  const exitApproveModal = approveModal ? approveModal.querySelector('.approval-close') : null;

  // ── Password show/hide toggle ─────────────────────────────────
  const eyeOpen = `<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#888" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>`;
  const eyeClosed = `<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#888" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>`;

  const approveEye      = document.getElementById('approvePasswordEye');
  const approvePassInput = document.getElementById('approve_password_input');

  if (approveEye && approvePassInput) {
    approveEye.addEventListener('click', function () {
      const isHidden = approvePassInput.type === 'password';
      approvePassInput.type = isHidden ? 'text' : 'password';
      approveEye.innerHTML  = isHidden ? eyeClosed : eyeOpen;
    });
  }

  // get buttons INSIDE this modal only
  const acceptBtn = approveModal ? approveModal.querySelector('.btn-accept') : null;
  const declineBtn = approveModal ? approveModal.querySelector('.btn-decline') : null;

  let currentUserId = null;

  approveButtons.forEach(button => {
    button.addEventListener('click', function () {
      const user = JSON.parse(this.getAttribute('data-user'));
      currentUserId = user.id ?? user.Account_ID;

      const nameParts = (user.Account_Name || '').trim().split(/\s+/);
      document.getElementById('approve_fname').value = nameParts[0] || '';
      document.getElementById('approve_lname').value = nameParts.slice(1).join(' ') || '';
      document.getElementById('approve_username').value = user.Account_Username || '';
      document.getElementById('approve_phone').value = user.Account_Phone || '';
      document.getElementById('approve_email').value = user.Account_Email || '';

      const imgElement = document.getElementById('approve_id_image');
      const placeholder = imgElement ? imgElement.dataset.placeholder : '';

      if (imgElement) {
        imgElement.src = user.valid_id_url || placeholder;
        imgElement.style.display = 'block';
      }

      // Reset password visibility on every open
      if (approvePassInput) { approvePassInput.type = 'password'; }
      if (approveEye)       { approveEye.innerHTML  = eyeOpen; }

      approveModal.classList.add('active');
    });
  });

  async function handleStatusUpdate(status) {
    if (!currentUserId) {
      console.log('No currentUserId');
      return;
    }

    const csrf = document.querySelector('meta[name="csrf-token"]');
    if (!csrf) {
      console.log('Missing csrf meta tag');
      return;
    }

    window.showEmailToast && window.showEmailToast('sending');

    try {
      const response = await fetch(`/employee/accounts/${currentUserId}/update-status`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrf.getAttribute('content'),
          'Accept': 'application/json'
        },
        body: JSON.stringify({ status: status })
      });

      const data = await response.json();
      console.log(data);

      if (data.success) {
        window.showEmailToast && window.showEmailToast('sent');
        setTimeout(() => location.reload(), 2500);
      }
    } catch (error) {
      console.error('Fetch error:', error);
    }
  }

  if (acceptBtn) {
    acceptBtn.addEventListener('click', () => handleStatusUpdate('approved'));
  }

  if (declineBtn) {
    declineBtn.addEventListener('click', () => handleStatusUpdate('declined'));
  }

  if (exitApproveModal) {
    exitApproveModal.addEventListener('click', () => approveModal.classList.remove('active'));
  }

  window.addEventListener('click', function (event) {
    if (event.target === approveModal) {
      approveModal.classList.remove('active');
    }
  });
});