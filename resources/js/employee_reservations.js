document.addEventListener('DOMContentLoaded', () => {
  const expandButtons = document.querySelectorAll('.expand-btn');
  const modalOverlay = document.querySelector('.modal-overlay');
  const closeBtn = document.querySelector('.close-btn');

  const statusForm = document.getElementById('statusForm');
  const statusInput = document.getElementById('statusInput');

  expandButtons.forEach(button => {
    button.addEventListener('click', function () {
      const rawData = this.getAttribute('data-info');
      const data = JSON.parse(rawData);

      // --- 1. DYNAMICALLY UPDATE FORM ACTION ---
      if (statusForm) {
        statusForm.action = `/employee/reservations/${Number(data.id)}/status`;
      }

      // --- NEW: TOGGLE MODAL BUTTONS BASED ON STATUS ---
      // We assume your data-info now includes: 'status' => strtolower($res->status)
      // --- TOGGLE MODAL BUTTONS BASED ON STATUS ---
      // --- TOGGLE MODAL BUTTONS ---
      // 1. Get the status from the button data
      const currentStatus = data.status ? data.status.toLowerCase().trim() : '';

      // 2. Identify the groups
      const groups = {
        'pending': document.getElementById('pendingActions'),
        'confirmed': document.getElementById('confirmedActions'),
        'checked-in': document.getElementById('checkedInActions')
      };

      // 3. Hide all groups first
      Object.values(groups).forEach(group => {
        if (group) group.style.display = 'none';
      });

      // 4. Show only the one matching the current status
      if (groups[currentStatus]) {
        groups[currentStatus].style.display = 'flex';
      } else {
        // Fallback: If status is unknown, show Pending buttons so you aren't stuck
        if (groups['pending']) groups['pending'].style.display = 'flex';
      }

      // THIS LINE MUST BE AT THE END
      modalOverlay.style.display = 'flex';

      // --- 2. SPLIT THE NAME ---
      let fullName = data.name || 'Unknown';
      let nameParts = fullName.trim().split(' ');
      document.getElementById('modalName').textContent = nameParts[0];
      document.getElementById('modalLastName').textContent = nameParts.length > 1 ? nameParts.slice(1).join(' ') : '';

      // --- 3. BASIC INFO ---
      document.getElementById('modalCheckIn').textContent = data.check_in;
      document.getElementById('modalCheckOut').textContent = data.check_out;
      document.getElementById('modalFoodIdLabel').textContent = `Food ID (${data.id}):`;

      // --- 4. HANDLE THE FOOD ---
      const foodListContainer = document.getElementById('modalFoodList');
      // ... (Your existing food logic remains the same) ...

      foodListContainer.innerHTML = foodHtml;
      modalOverlay.style.display = 'flex';
    });
  });

  // Global submit function
  window.submitStatus = function (statusValue) {
    if (statusInput && statusForm) {
      statusInput.value = statusValue;
      statusForm.submit();
    }
  };

  if (closeBtn) {
    closeBtn.addEventListener('click', () => {
      modalOverlay.style.display = 'none';
    });
  }
});