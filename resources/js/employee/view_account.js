document.addEventListener('DOMContentLoaded', function () {
  const viewModal = document.getElementById('accountOverlay');
  const viewButtons = document.querySelectorAll('.action-btn-view');
  const exitViewModal = document.querySelector('.account-close');
  const updateForm = document.getElementById('updateAccountForm');

  viewButtons.forEach(button => {
    button.addEventListener('click', function () {
      // 1. Get the user data from the button
      const userData = this.getAttribute('data-user');
      if (!userData) {
        console.error("Error: No data-user attribute found on this button.");
        return;
      }

      const user = JSON.parse(this.getAttribute('data-user'));
      const updateForm = document.getElementById('updateAccountForm');
      console.log("Attempting to open modal for User ID:", user.id);

      // 2. DYNAMICALLY UPDATE THE FORM ACTION
      // This prevents the "405 Method Not Allowed" error
      if (updateForm && user.id) {
        // We use a leading slash to ensure we start from the root domain
        const newAction = `/employee/accounts/${user.id}/update`;
        updateForm.setAttribute('action', newAction);

        // Debugging: check your browser console (F12) to see this output
        console.log("Form action updated to: " + updateForm.getAttribute('action'));
      }

      // 3. Populate First Name and Last Name
      // Handles cases where user.name exists but first_name/last_name don't
      const name = user.name || (user.first_name + ' ' + user.last_name) || '';
      const nameParts = name.trim().split(/\s+/);

      document.getElementById('view_fname').value = user.first_name || nameParts[0] || '';
      document.getElementById('view_lname').value = user.last_name || nameParts.slice(1).join(' ') || '';

      // 4. Populate other fields
      document.getElementById('view_username').value = user.username || '';
      document.getElementById('view_phone').value = user.phone_no || user.phone || 'N/A';
      document.getElementById('view_email').value = user.email || '';

      const idInfoField = document.getElementById('view_id_info');
      if (idInfoField) {
        idInfoField.value = user.id_info || '';
      }

      // 5. Show the modal
      viewModal.classList.add('active');
    });
  });

  // Close Modal Logic
  if (exitViewModal) {
    exitViewModal.addEventListener('click', () => viewModal.classList.remove('active'));
  }

  window.addEventListener('click', function (event) {
    if (event.target === viewModal) {
      viewModal.classList.remove('active');
    }
  });
});