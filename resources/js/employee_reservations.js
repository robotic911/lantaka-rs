document.addEventListener('DOMContentLoaded', () => {
  const expandButtons = document.querySelectorAll('.expand-btn');
  const modalOverlay = document.querySelector('.modal-overlay');
  const closeBtn = document.querySelector('.close-btn');
  const statusForm = document.getElementById('statusForm');
  const statusInput = document.getElementById('statusInput');
  const chargesContainer = document.getElementById('chargesContainer');
  const addChargesBtn = document.getElementById('addAdditionalCharges');
  const discInput = document.getElementById('discount');

  console.log("employee_reservations.js connection working - FULL VERSION");

  // --- 1. GLOBAL CALCULATION LOGIC ---
  window.calculateLiveTotal = () => {
    const unitPriceEl = document.getElementById('unit-price');
    // Improved regex to handle the ₱ symbol and commas
    const base = unitPriceEl ? (parseFloat(unitPriceEl.textContent.replace(/[^\d.-]/g, '')) || 0) : 0;

    const foodEl = document.getElementById('summaryFood');
    const food = foodEl ? (parseFloat(foodEl.textContent.replace(/[^\d.-]/g, '')) || 0) : 0;

    const discInput = document.getElementById('discount');
    const disc = discInput ? (parseFloat(discInput.value) || 0) : 0;

    let extra = 0;
    document.querySelectorAll('input[name="additional_fees[]"]').forEach(input => {
      const row = input.closest('.charges-container-sub');
      const qtyInput = row ? row.querySelector('input[placeholder="Qty"]') : null;
      const qty = qtyInput ? (parseFloat(qtyInput.value) || 1) : 1;
      extra += (parseFloat(input.value) || 0) * qty;
    });

    const grandTotal = (base + food + extra) - disc;

    // Debugging: Check your F12 console to see these numbers!
    console.log(`Calculation: Base(${base}) + Food(${food}) + Extra(${extra}) - Disc(${disc}) = ${grandTotal}`);

    const summaryExtra = document.getElementById('summaryExtra');
    if (summaryExtra) summaryExtra.textContent = `₱ ${extra.toFixed(2)}`;

    const summaryDiscount = document.getElementById('summaryDiscount');
    if (summaryDiscount) summaryDiscount.textContent = `₱ ${disc.toFixed(2)}`;

    const totalAmountEl = document.getElementById('totalAmount');
    if (totalAmountEl) totalAmountEl.textContent = `₱${grandTotal.toFixed(2)}`;
  };

  if (discInput) {
    discInput.addEventListener('input', window.calculateLiveTotal);
  }

  // --- 2. MODAL POPULATION ---
  expandButtons.forEach(button => {
    button.addEventListener('click', function () {
      const data = JSON.parse(this.getAttribute('data-info'));
      if (chargesContainer) {
        chargesContainer.innerHTML = '';
  
        let descriptions = [];
  
        try {
          descriptions = typeof data.additional_fees_desc === 'string'
            ? JSON.parse(data.additional_fees_desc)
            : data.additional_fees_desc;
        } catch (e) {
          descriptions = [];
        }
  
        if (!Array.isArray(descriptions)) {
          descriptions = [];
        }
  
        if (descriptions.length > 0) {
          descriptions.forEach((item) => {

            let desc = '';
            let qty = 1;
            let amount = 0;
          
            if (typeof item === 'string' && item.includes(':')) {
              const parts = item.split(':');
          
              desc = parts[0] || '';
              qty = parseFloat(parts[1]) || 1;
              amount = parseFloat(parts[2]) || 0;
            }
          
            window.addAdditionalCharges(desc, amount, qty);
          });
        } else {
          window.addAdditionalCharges('', 0);
        }
      }

      const resIdField = document.getElementById('modalResId');
      const resTypeField = document.getElementById('modalResType');

      if (resIdField) resIdField.value = data.id;
      if (resTypeField) resTypeField.value = data.res_type;

      console.log('modalResId =', resIdField?.value);
      console.log('modalResType =', resTypeField?.value);
      console.log('full data =', data);

      updateSoaLink(data.userId);

      const currentStatus = data.status ? data.status.toLowerCase().trim() : '';
      const statusGroups = {
        'pending': document.getElementById('pendingActions'),
        'rejected': document.getElementById('pendingActions'),
        'confirmed': document.getElementById('confirmedActions'),
        'cancelled': document.getElementById('confirmedActions'),
        'checked-in': document.getElementById('checkedInActions'),
        'checked-out': document.getElementById('checkedInActions'),
      };

      Object.values(statusGroups).forEach(group => {
        if (group) group.style.display = 'none';
      });

      if (statusGroups[currentStatus]) {
        statusGroups[currentStatus].style.display = 'flex';
      } else if (statusGroups['pending']) {
        statusGroups['pending'].style.display = 'flex';
      }

      const blockCheckin = document.getElementById('confirmedActions');
      const blockCheckout = document.getElementById('checkedInActions');
      const blockAccept = document.getElementById('pendingActions');
      const showSOA = document.getElementById('exportSection');
      const showAddChSection = document.getElementById('additionalChargesSection');

      const row = this.closest('tr');
      const badge = row.querySelector('.badge');
      const badgeStatus = badge ? badge.textContent.trim() : '';
      const discountSection = document.getElementById('discountSection');
      const checkAccomodation = data.accommodationType;

      if (badgeStatus === "Completed" || badgeStatus === "Checked-out" || badgeStatus === "Cancelled") {
        if (blockCheckout) blockCheckout.style.display = 'none';
      }
      if (badgeStatus === "Rejected") {
        if (blockAccept) blockAccept.style.display = 'none';
      }
      if (badgeStatus === "Cancelled" || badgeStatus === "Completed") {
        if (blockCheckin) blockCheckin.style.display = 'none';
      }

      if (badgeStatus !== "Checked-in") {
        showSOA.style.display = 'none';
        showAddChSection.style.display = 'none';
      } else {
        showSOA.style.display = 'block';
        showAddChSection.style.display = 'block';
      }

      if (badgeStatus === "Checked-in" && checkAccomodation === "Venue") {
        discountSection.classList.remove('none');
      } else {
        discountSection.classList.add('none');
      }

      let fullName = data.name || 'Unknown';
      let nameParts = fullName.trim().split(' ');

      document.getElementById('modalTitle').textContent = badge ? badge.textContent.trim() : '';
      document.getElementById('firstName').value = nameParts[0] || '';
      document.getElementById('lastName').value = nameParts.length > 1 ? nameParts.slice(1).join(' ') : '';
      document.getElementById('phoneNumber').value = data.phone || '';
      document.getElementById('email').value = data.email || '';
      document.getElementById('affiliation').value = data.type || '';
      document.getElementById('modalName').textContent = data.accommodation || 'N/A';
      document.getElementById('modalLastName').textContent = data.pax || '1';
      document.getElementById('modalCheckIn').textContent = data.check_in || '';
      document.getElementById('modalCheckOut').textContent = data.check_out || '';
      document.getElementById('accomodation-type').textContent = data.accommodationType || '';

      const basePrice = data.price || data.total_amount || 0;
      const unitPriceEl = document.getElementById('unit-price');
      if (unitPriceEl) unitPriceEl.textContent = `₱${parseFloat(basePrice).toFixed(2)}`;

      // 2. Fix the Food Total (This explicitly updates the food text so the math works)
      const foodTotal = data.food_total || 0;
      const summaryFoodEl = document.getElementById('summaryFood');
      if (summaryFoodEl) summaryFoodEl.textContent = `₱${parseFloat(foodTotal).toFixed(2)}`;

      // 3. Fix the Discount (Targets both possible ID variations safely)
      const discountValue = data.discount || 0;
      const discInputEl = document.getElementById('discount');
      const discInputAlt = document.getElementById('discountInput');
      if (discInputEl) discInputEl.value = discountValue;
      if (discInputAlt) discInputAlt.value = discountValue;

      // 4. Update the User ID
      const userIdEl = document.getElementById('userId');
      if (userIdEl) userIdEl.value = data.userId || '';

      // 5. Update Food List (if applicable)
      const foodListContainer = document.getElementById('modalFoodList');
      if (foodListContainer && data.food_items) {
        foodListContainer.innerHTML = data.food_items;
      }

      window.calculateLiveTotal();
      modalOverlay.style.display = 'flex';
    });
  });

  // --- 3. MODAL CONTROLS & STATUS SAVING ---
  if (closeBtn) {
    closeBtn.addEventListener('click', () => { modalOverlay.style.display = 'none'; });
  }

  if (modalOverlay) {
    modalOverlay.addEventListener('click', (e) => {
      if (e.target === modalOverlay) modalOverlay.style.display = 'none';
    });
  }

  window.submitStatus = function (statusValue) {
    // 1. Correct the variable name (was 'status', now 'statusValue')
    const statusInput = document.getElementById('statusInput');
    if (statusInput) {
      statusInput.value = statusValue;
    }

    // 2. Get the values
    const resId = document.getElementById('modalResId').value;
    const resType = document.getElementById('modalResType').value;

    // 3. Get the form and update the action URL
    const form = document.getElementById('statusForm');

    if (resId && form) {
      // This builds: /employee/reservations/5/status?type=Room
      form.action = `/employee/reservations/${resId}/status?type=${resType}`;

      console.log("Submitting to:", form.action); // Debugging line
      form.submit();
    } else {
      console.error("Form or Reservation ID missing!");
    }
  };

  // --- 4. STATUS CARDS ANIMATION ---
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

// --- 5. GLOBAL HELPERS ---
function updateSoaLink(clientId) {
  const soaLink = document.getElementById('soaLink');
  const userIdInput = document.getElementById('userId');

  if (!soaLink || !userIdInput) return;

  userIdInput.value = clientId;
  soaLink.href = `/employee/SOA/${clientId}`;
}

window.addAdditionalCharges = function (description = '', amount = 0, qty = 1) {
  const chargesContainer = document.getElementById('chargesContainer');
  if (!chargesContainer) return;

  const newRow = document.createElement('div');
  newRow.className = 'charges-container-sub';
  newRow.style.marginTop = '8px';

  newRow.innerHTML = `
  <input type="text" name="additional_fees_desc[]" value="${description}" placeholder="Description" class="charge-input" style="width: 230px;" required>
  <input type="number" name="additional_fees_qty[]" value="${qty}" placeholder="Qty" class="charge-input qty-input"  style="width: 70px;" min="1" value="1">
  <input type="number" name="additional_fees[]" value="${amount}" placeholder="₱" class="charge-input amount-input" style="width: 100px;" required>
  <button type="button" class="remove-btn" onclick="this.parentElement.remove(); window.calculateLiveTotal();" style="background:none; border:none; color:red; cursor:pointer; font-size: 20px; padding-left: 5px;">&times;</button>
`;

  chargesContainer.appendChild(newRow);

  const amountInput = newRow.querySelector('.amount-input');
  const qtyInput = newRow.querySelector('.qty-input');
  const removeBtn = newRow.querySelector('.remove-btn');

  if (typeof window.calculateLiveTotal === 'function') {
    amountInput?.addEventListener('input', window.calculateLiveTotal);
    qtyInput?.addEventListener('input', window.calculateLiveTotal);
  }

  removeBtn?.addEventListener('click', function () {
    newRow.remove();
    window.calculateLiveTotal();
  });
};

const addChargesBtn = document.getElementById('addAdditionalCharges');
if (addChargesBtn) {
  addChargesBtn.addEventListener('click', () => window.addAdditionalCharges('', 0));
}

// --- 6. SAVE MODIFICATIONS SCRIPT ---
window.saveModificationsAndSubmit = function (e) {
  e.preventDefault();

  console.log(
    'Descriptions:',
    [...document.querySelectorAll('input[name="additional_fees_desc[]"]')].map(i => i.value)
  );

  console.log(
    'Amounts:',
    [...document.querySelectorAll('input[name="additional_fees[]"]')].map(i => i.value)
  );

  console.log(
    'Qtys:',
    [...document.querySelectorAll('input[name="additional_fees_qty[]"]')].map(i => i.value)
  );

  console.log('reservation_id:', document.getElementById('modalResId')?.value);
  console.log('res_type:', document.getElementById('modalResType')?.value);

  const modificationForm = document.getElementById('modificationForm');
  if (modificationForm) {
    modificationForm.submit();
  }
};