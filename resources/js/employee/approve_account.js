document.addEventListener('DOMContentLoaded', function () {
  const approveModal = document.getElementById('approvalOverlay');
  const approveButtons = document.querySelectorAll('.action-btn-approve');
  const exitApproveModal = approveModal ? approveModal.querySelector('.approval-close') : null;

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
      const noIdText = document.getElementById('approve_no_id');

      if (user.valid_id_path) {
        imgElement.src = `/storage/${user.valid_id_path}`;
        imgElement.style.display = 'block';
        if (noIdText) noIdText.style.display = 'none';
      } else {
        imgElement.style.display = 'none';
        if (noIdText) noIdText.style.display = 'block';
      }

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