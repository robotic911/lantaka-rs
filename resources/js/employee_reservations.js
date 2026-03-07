document.addEventListener('DOMContentLoaded', () => {
  const expandButtons = document.querySelectorAll('.expand-btn');
  const modalOverlay = document.querySelector('.modal-overlay');
  const closeBtn = document.querySelector('.close-btn');
  const statusForm = document.getElementById('statusForm');
  const statusInput = document.getElementById('statusInput');
  
  console.log("employee_reservations.js connection working");

  // --- OPEN MODAL AND POPULATE DATA ---
  expandButtons.forEach(button => {
    button.addEventListener('click', function () {
      const rawData = this.getAttribute('data-info');
      const data = JSON.parse(rawData);

      // 1. Update form action dynamically
      if (statusForm) {
        statusForm.action = `/employee/reservations/${Number(data.id)}/status`;
      }

      // 2. Get current status and toggle appropriate action buttons
      const currentStatus = data.status ? data.status.toLowerCase().trim() : '';
      
      const statusGroups = {
        'pending': document.getElementById('pendingActions'),
        /* checked in guest is also a confirmed reservation */
        'rejected': document.getElementById('pendingActions'),

        'confirmed': document.getElementById('confirmedActions'),
        'cancelled': document.getElementById('confirmedActions'),
        
        'checked-in': document.getElementById('checkedInActions'),
        'cancelled': document.getElementById('checkedInActions'),

        'checked-out': document.getElementById('checkedInActions'),
      };

      // Hide all action groups, then show only the one matching current status
      Object.values(statusGroups).forEach(group => {
        if (group) group.style.display = 'none';
      });

      if (statusGroups[currentStatus]) {
        statusGroups[currentStatus].style.display = 'flex';
      } else if (statusGroups['pending']) {
        statusGroups['pending'].style.display = 'flex';
      }

      // 3. Populate form and summary fields
      let fullName = data.name || 'Unknown';
      let nameParts = fullName.trim().split(' ');

      let status = data.status.charAt(0).toUpperCase() + data.status.slice(1);
      console.log("test user type:" + data.type)
      console.log("accomodation :" + data.accommodation)

      document.getElementById('modalTitle').textContent = status + " Reservation";
      document.getElementById('firstName').value = nameParts[0] || '';
      document.getElementById('lastName').value = nameParts.length > 1 ? nameParts.slice(1).join(' ') : '';
      document.getElementById('modalName').textContent = data.accommodation || 'N/A';
      document.getElementById('modalLastName').textContent = data.pax || '1';
      document.getElementById('modalCheckIn').textContent = data.check_in || '';
      document.getElementById('modalCheckOut').textContent = data.check_out || '';
      document.getElementById('accomodation-type').textContent = data.accommodation || '';
      document.getElementById('unit-price').textContent = `₱` +  data.price || '';
      document.getElementById('totalAmount').textContent = `₱` +  data.price|| '';


      // 4. Populate food list if available
      const foodListContainer = document.getElementById('modalFoodList');
      if (foodListContainer && data.food_items) {
        foodListContainer.innerHTML = data.food_items;
      }

      // 5. Open modal
      modalOverlay.style.display = 'flex';
    });
  });

  // --- CLOSE MODAL ---
  if (closeBtn) {
    closeBtn.addEventListener('click', () => {
      modalOverlay.style.display = 'none';
    });
  }

  // Close modal when clicking overlay background
  if (modalOverlay) {
    modalOverlay.addEventListener('click', (e) => {
      if (e.target === modalOverlay) {
        modalOverlay.style.display = 'none';
      }
    });
  }

  // --- GLOBAL SUBMIT FUNCTION ---
  window.submitStatus = function (statusValue) {
    if (statusInput && statusForm) {
      statusInput.value = statusValue;
      statusForm.submit();
    }
  };

  // --- STATUS FILTER CARD TOGGLE ---
  const statusCards = document.querySelector('.status-cards');
  if (statusCards) {
    statusCards.addEventListener('click', (e) => {
      const activeCard = e.target.closest('.status-card');
      if (!activeCard) return;

      const isActive = activeCard.classList.contains('active');
      statusCards.querySelectorAll('.status-card').forEach(card => card.classList.remove('active'));

      if (!isActive) {
        activeCard.classList.add('active');
      }
    });
  }
});
