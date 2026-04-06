let currentNights = 1;
let currentDays = 1;
let mode = "";


document.addEventListener('DOMContentLoaded', () => {
  const expandButtons = document.querySelectorAll('.expand-btn');
  const modalOverlay = document.querySelector('.modal-overlay');
  const closeBtn = document.querySelector('.close-btn');
  const statusForm = document.getElementById('statusForm');
  const statusInput = document.getElementById('statusInput');
  const chargesContainer = document.getElementById('chargesContainer');
  const addChargesBtn = document.getElementById('addAdditsionalCharges');
  const discInput = document.getElementById('discount');

  console.log("employee_reservations.js connection working - FULL VERSION");

  // --- 1. GLOBAL CALCULATION LOGIC ---
  window.calculateLiveTotal = () => {
    const unitPriceEl = document.getElementById('unit-price');
    // Improved regex to handle the ₱ symbol and commas
    const base = unitPriceEl ? (parseFloat(unitPriceEl.textContent.replace(/[^\d.-]/g, '')) || 0) : 0;
    const foodEl = document.getElementById('summaryFood');
    const food = foodEl ? (parseFloat(foodEl.textContent.replace(/[^\d.-]/g, '')) || 0) : 0;
    // const days = document.getElementById('');

    const discInput = document.getElementById('discount');
    const disc = discInput ? (parseFloat(discInput.value) || 0) : 0;

    let extra = 0;
    document.querySelectorAll('input[name="additional_fees[]"]').forEach(input => {
      const row = input.closest('.charges-container-sub');
      const qtyInput = row ? row.querySelector('input[placeholder="Qty"]') : null;
      const qty = qtyInput ? (parseFloat(qtyInput.value) || 1) : 1;
      extra += (parseFloat(input.value) || 0) * qty;
    });
    let multiplier = mode == "venue" ? currentDays : currentNights;
    const totalPriceWithCurrenNights = base * multiplier;
    const modalNightsEl = document.getElementById('night-price');
    if (modalNightsEl) modalNightsEl.textContent =  `₱${Number(totalPriceWithCurrenNights).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2})}`;


    console.log(mode + multiplier);

    const grandTotal = ((base * multiplier) + food + extra) - disc;

    // Debugging: Check your F12 console to see these numbers!
    console.log(`Calculation: Base(${base}) + Food(${food}) + Extra(${extra}) - Disc(${disc}) = ${grandTotal}`);

    const summaryExtra = document.getElementById('summaryExtra');
    if (summaryExtra) summaryExtra.textContent = `₱ ${extra.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2})}`;

    const summaryDiscount = document.getElementById('summaryDiscount');
    if (summaryDiscount) summaryDiscount.textContent = `₱ ${disc.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2})}`;

    const totalAmountEl = document.getElementById('totalAmount');
    if (totalAmountEl) totalAmountEl.textContent = `₱${grandTotal.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
  };

  if (discInput) {
    discInput.addEventListener('input', window.calculateLiveTotal);
  }

  // --- 2. MODAL POPULATION ---
  // --- Edit button wiring ---
  const editLinkBtn = document.getElementById('editLink');
  if (editLinkBtn) {
    editLinkBtn.addEventListener('click', function () {
      const d = window.currentModalData;
      console.log("hereserser");

      console.log(d);
      if (!d) return;

      const category    = d.accommodationType || 'Room';  // "Room" or "Venue"
      const idx         = d.idx;
      const userId      = d.userId;
      const reservationId = d.id;
      const checkIn     = d.check_in_raw || d.check_in;
      const checkOut    = d.check_out_raw || d.check_out;
      const pax         = d.pax || 1;
      const purpose     = d.purpose || '';

      const url = `/employee/create_reservation?` +
        `category=${encodeURIComponent(category)}` +
        `&id=${encodeURIComponent(idx)}` +
        `&user_id=${encodeURIComponent(userId)}` +
        `&reservation_id=${encodeURIComponent(reservationId)}` +
        `&check_in=${encodeURIComponent(checkIn)}` +
        `&check_out=${encodeURIComponent(checkOut)}` +
        `&pax=${encodeURIComponent(pax)}` +
        `&purpose=${encodeURIComponent(purpose)}`;

      window.location.href = url;
    });
  }

  expandButtons.forEach(button => {
    button.addEventListener('click', function () {
      const data = JSON.parse(this.getAttribute('data-info'));
      // Store so the Edit button can read it
      window.currentModalData = data;

      // Reset left column visibility so previous modal state doesn't bleed into the next open
      const leftCol = document.querySelector('.modal-left-column');
      if (leftCol) leftCol.style.display = 'flex';

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
            let date = '';

            if (typeof item === 'string' && item.includes(':')) {
              const parts = item.split(':');

              desc = parts[0] || '';
              qty = parseFloat(parts[1]) || 1;
              amount = parseFloat(parts[2]) || 0;
              date = parts[3] || '';
            }

            window.addAdditionalCharges(desc, amount, qty, date);
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

      const checkIn = new Date(data.check_in);
      const checkOut = new Date(data.check_out);

      // milliseconds → days
      const diffDays = Math.ceil((checkOut - checkIn) / (1000 * 60 * 60 * 24));

      console.log(diffDays);

      console.log(checkIn + "check in");

      console.log(checkOut + "check Out");
      const modalNightsEl = document.getElementById('modalNights');
      mode = resTypeField.value;
      if (resTypeField?.value === "room") {
          currentNights = diffDays || 1;
          currentDays = 1;
          console.log('nights =', data.nights);
          if (modalNightsEl) modalNightsEl.textContent = currentNights;
      } else if (resTypeField?.value === "venue") {
          currentDays = (diffDays + 1) || 1;
          currentNights = 1;
          console.log("Current Days:" + currentDays);
          if (modalNightsEl) modalNightsEl.textContent = currentDays;
      }





      const nightsLabelEl = document.getElementById('nightsLabel');
      if (nightsLabelEl) nightsLabelEl.textContent = data.accommodationType === 'Venue' ? 'Days' : 'Nights';

      updateSoaLink(data.userId);

      // Load cancellation request banner (if any pending request for this reservation)
      if (typeof loadCancellationBanner === 'function') {
        loadCancellationBanner(data.id, data.res_type, data.status);
      }

      const currentStatus  = data.status          ? data.status.toLowerCase().trim()          : '';
      const currentPayment = data.payment_status  ? data.payment_status.toLowerCase().trim() : '';

      // --- Hide ALL action panels first ---
      const allPanels = [
        'pendingActions', 'confirmedActions', 'checkedInActions',
        'checkedOutUnpaidActions', 'checkedOutPaidActions'
      ];
      allPanels.forEach(id => {
        const el = document.getElementById(id);
        if (el) el.style.display = 'none';
      });

      // --- Show the correct panel for the current status ---
      const statusGroups = {
        'pending':    'pendingActions',
        'rejected':   'pendingActions',
        'confirmed':  'confirmedActions',
        'cancelled':  'confirmedActions',
        'checked-in': 'checkedInActions',
      };

      if (currentStatus === 'checked-out') {
        // After checkout: show payment panel based on payment_status
        const payPanel = (currentPayment === 'paid')
          ? document.getElementById('checkedOutPaidActions')
          : document.getElementById('checkedOutUnpaidActions');
        if (payPanel) payPanel.style.display = 'flex';

        // Stash IDs so doMarkAsPaid / doMarkAsUnpaid can use them
        window._paymentResId   = data.id;
        window._paymentResType = data.res_type;

      } else if (statusGroups[currentStatus]) {
        const el = document.getElementById(statusGroups[currentStatus]);
        if (el) el.style.display = 'flex';
      } else {
        const fallback = document.getElementById('pendingActions');
        if (fallback) fallback.style.display = 'flex';
      }

      const blockCheckin    = document.getElementById('confirmedActions');
      const blockCheckout   = document.getElementById('checkedInActions');
      const blockAccept     = document.getElementById('pendingActions');
      const showSOA         = document.getElementById('exportSection');
      const showAddChSection = document.getElementById('modal-bottom');


      const discountSection   = document.getElementById('discountSection');
      const checkAccomodation = data.accommodationType;
      const row = this.closest('tr');
      const badge = row.querySelector('.badge');
      // Use the real status from data (not badge text, which can be relabelled on the Guest page)
      document.querySelector('#editLink').style.display = 'flex';

      if (currentStatus === 'completed' || currentStatus === 'checked-out' || currentStatus === 'cancelled') {
        if (blockCheckout) blockCheckout.style.display = 'none';
        document.querySelector('#editLink').style.display = 'none';
      }
      if (currentStatus === 'rejected') {
        if (blockAccept) blockAccept.style.display = 'none';
        document.querySelector('#editLink').style.display = 'none';
      }
      if (currentStatus === 'cancelled' || currentStatus === 'completed' || currentStatus === 'pending' || currentStatus === 'checked-out') {
        if (blockCheckin) blockCheckin.style.display = 'none';
        document.querySelector('#editLink').style.display = 'none';
        // For room reservations there is no food — hide the left column entirely.
        // For venue reservations keep it visible so the food table is always shown.
        if (data.accommodationType !== 'Venue') {
          document.querySelector('.modal-left-column').style.display = 'none';
        }
      }

      if (currentStatus !== 'checked-in') {
        showSOA.style.display = 'none';
        showAddChSection.style.display = 'none';
        document.querySelector('.meals-container').style.maxHeight = '100vh';
      } else {
        showSOA.style.display = 'flex';
        showAddChSection.style.display = 'flex';
      }

      if (currentStatus === 'checked-in' && checkAccomodation === 'Venue') {
        discountSection.classList.remove('none');
      } else {
        discountSection.classList.add('none');
      }

      console.log(data);

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
      document.getElementById('modalCheckIn') && (document.getElementById('modalCheckIn').textContent = data.check_in || '');
      document.getElementById('modalCheckOut').textContent = data.check_out || '';
      document.getElementById('accomodation-type').textContent = data.accommodationType || '';

      if (data.accommodationType == "Venue") {
        document.getElementById('meal-container-left').style.display = "block";
        document.querySelector('.modal-body').classList.remove('room-mode');
        // Render card-based food display
        renderFoodCards(data.foods || [], data.food_sets || [], data.pax || 1);
    } else {
        document.getElementById('meal-container-left').style.display = "none";
        document.querySelector('.modal-body').classList.add('room-mode');
    }

      document.getElementById('fullName_r').textContent = fullName;
      document.getElementById('phoneNumber_r').textContent = data.phone || '';
      document.getElementById('email_r').textContent = data.email || '';
      document.getElementById('affiliation_r').textContent = data.type || '';
      const purposeEl = document.getElementById('purpose_r');
      if (purposeEl) purposeEl.textContent = data.purpose || '';


      const basePrice = data.price || data.total_amount || 0;
      const unitPriceEl = document.getElementById('unit-price');
      if (unitPriceEl) unitPriceEl.textContent = `₱${parseFloat(basePrice).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2})}`;

      // 2. Fix the Food Total (This explicitly updates the food text so the math works)
      const foodTotal = data.food_total || 0;
      const summaryFoodEl = document.getElementById('summaryFood');
      if (summaryFoodEl) summaryFoodEl.textContent = `₱${parseFloat(foodTotal).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2})}`;

      // 3. Fix the Discount (Targets both possible ID variations safely)
      const discountValue = data.discount || 0;
      const discInputEl = document.getElementById('discount');
      const discInputAlt = document.getElementById('discountInput');
      if (discInputEl) discInputEl.value = discountValue;
      if (discInputAlt) discInputAlt.value = discountValue;

      // 4. Update the Account ID
      const userIdEl = document.getElementById('userId');
      if (userIdEl) userIdEl.value = data.userId || '';

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
      window.showEmailToast && window.showEmailToast('sending');
      form.submit();
    } else {
      console.error("Form or Reservation ID missing!");
    }
  };

  // --- Mark an already-checked-out reservation as paid ---
  window.doMarkAsPaid = function () {
    const id   = window._paymentResId;
    const type = window._paymentResType;
    if (!id || !type) {
      console.error('markAsPaid: missing id or type');
      return;
    }

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = `/employee/reservations/${id}/mark-paid?type=${type}`;

    const csrf = document.createElement('input');
    csrf.type  = 'hidden';
    csrf.name  = '_token';
    csrf.value = document.querySelector('meta[name="csrf-token"]')?.content
                 || document.querySelector('input[name="_token"]')?.value
                 || '';
    form.appendChild(csrf);

    document.body.appendChild(form);
    form.submit();
  };

  // --- Revert a paid reservation back to unpaid (admin only, enforced server-side) ---
  window.doMarkAsUnpaid = function () {
    const id   = window._paymentResId;
    const type = window._paymentResType;
    if (!id || !type) {
      console.error('markAsUnpaid: missing id or type');
      return;
    }

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = `/employee/reservations/${id}/mark-unpaid?type=${type}`;

    const csrf = document.createElement('input');
    csrf.type  = 'hidden';
    csrf.name  = '_token';
    csrf.value = document.querySelector('meta[name="csrf-token"]')?.content
                 || document.querySelector('input[name="_token"]')?.value
                 || '';
    form.appendChild(csrf);

    document.body.appendChild(form);
    form.submit();
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

window.addAdditionalCharges = function (description = '', amount = 0, qty = 1, date = '') {
  const chargesContainer = document.getElementById('chargesContainer');
  if (!chargesContainer) return;

  const newRow = document.createElement('div');
  newRow.className = 'charges-container-sub';
  newRow.style.marginTop = '8px';

  newRow.innerHTML = `
  <input type="date" name="additional_fees_date[]" value="${date}" class="charge-input date-input" style="width: 140px;" title="Date of charge">
  <input type="text" name="additional_fees_desc[]" value="${description}" placeholder="Description" class="charge-input" style="width: 180px;" required>
  <span style="display: flex;
    flex-direction: row;
    width: 100%;
    column-span: 2;
    grid-column: 1 / -1;
    gap: 8px;
    padding-right: 6px;">
  <input type="number" name="additional_fees_qty[]" value="${qty}" placeholder="Qty" class="charge-input qty-input" style="width: 60px;" min="1">
  <input type="number" name="additional_fees[]" value="${amount}" placeholder="₱" class="charge-input amount-input" style="width: 90px;" required>
  <button type="button" class="remove-btn" onclick="this.parentElement.remove(); window.calculateLiveTotal();" style="background:none; border:none; color:red; cursor:pointer; font-size: 20px; padding-left: 5px;">&times;</button>
  </span>
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
  addChargesBtn.addEventListener('click', () => {
    const todayStr = new Date().toISOString().split('T')[0];
    window.addAdditionalCharges('', 0, 1, todayStr);
  });
}

// --- 6. FOOD TABLE HELPERS ---

/**
 * Reset every food cell back to "None"
 */
function resetSingleFoodTable(table) {
  table.querySelectorAll('.food-display').forEach(el => {
    el.textContent = 'None';
  });
}

/**
 * Render food reservation details as client-style cards.
 * Replaces the old table-based createFoodTables().
 *
 * @param {Array}  foods    – individual food items (Food_Name, Food_Category, Food_Price, pivot.*)
 * @param {Array}  foodSets – pre-processed set rows {date, meal_time, total_price, set_name, set_price, custom_names}
 * @param {number} pax      – number of guests
 */
function renderFoodCards(foods, foodSets, pax) {
  const container = document.getElementById('foodTablesContainer');
  if (!container) return;
  container.innerHTML = '';
  pax = Number(pax) || 1;

  const MEAL_ORDER  = ['breakfast', 'am_snack', 'lunch', 'pm_snack', 'dinner', 'snacks'];
  const MEAL_LABELS = { breakfast: 'Breakfast', am_snack: 'AM Snack', lunch: 'Lunch', pm_snack: 'PM Snack', dinner: 'Dinner', snacks: 'Snack' };
  const MEAL_ICONS  = { breakfast: '🌅', am_snack: '🍪', lunch: '☀️', pm_snack: '🍃', dinner: '🌙', snacks: '🍪' };

  function peso(amount) {
    return '₱' + Number(amount).toLocaleString('en-PH', { minimumFractionDigits: 2 });
  }
  function fmtDate(raw) {
    const d = new Date(raw + 'T00:00:00');
    return d.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
  }
  function capLabel(str) {
    return str.charAt(0).toUpperCase() + str.slice(1).replace(/_/g, ' ');
  }

  // Collect all unique dates
  const dateSet = new Set();
  (foods    || []).forEach(f => { const d = f.pivot?.Food_Reservation_Serving_Date; if (d) dateSet.add(d); });
  (foodSets || []).forEach(s => { if (s.date) dateSet.add(s.date); });

  if (!dateSet.size) {
    container.innerHTML = '<p class="em-food-empty">No food reservations for this booking.</p>';
    return;
  }

  [...dateSet].sort().forEach(date => {
    // Group individual foods by meal time
    const indivByMeal = {};
    (foods || []).forEach(f => {
      if (f.pivot?.Food_Reservation_Serving_Date !== date) return;
      const mt = (f.pivot?.Food_Reservation_Meal_time || 'other').toLowerCase();
      (indivByMeal[mt] = indivByMeal[mt] || []).push(f);
    });

    // Group set rows by meal time
    const setsByMeal = {};
    (foodSets || []).forEach(s => {
      if (s.date !== date) return;
      const mt = (s.meal_time || 'other').toLowerCase();
      (setsByMeal[mt] = setsByMeal[mt] || []).push(s);
    });

    const allMeals   = new Set([...Object.keys(indivByMeal), ...Object.keys(setsByMeal)]);
    const sortedMeals = MEAL_ORDER.filter(m => allMeals.has(m))
                                  .concat([...allMeals].filter(m => !MEAL_ORDER.includes(m)));

    let dateSubtotal = 0;
    let html = `<div class="em-food-date-group">`;
    html += `<div class="em-food-date-label">📅 ${fmtDate(date)}</div>`;

    sortedMeals.forEach(mealKey => {
      const indivItems = indivByMeal[mealKey] || [];
      const setItems   = setsByMeal[mealKey]  || [];
      if (!indivItems.length && !setItems.length) return;

      const mealLabel = MEAL_LABELS[mealKey] || capLabel(mealKey);
      const mealIcon  = MEAL_ICONS[mealKey]  || '🍽';
      let mealSubtotal = 0;

      html += `<div class="em-food-meal-group">`;
      html += `<div class="em-food-meal-label">${mealIcon} ${mealLabel}</div>`;

      // Individual items
      indivItems.forEach(food => {
        const price = Number(food.Food_Price || 0);
        mealSubtotal += price * pax;
        html += `
          <div class="em-food-item">
            <span class="em-food-cat">${capLabel(food.Food_Category || '')}</span>
            <span class="em-food-name">${food.Food_Name}</span>
            <span class="em-food-price">₱${price.toLocaleString('en-PH',{minimumFractionDigits:2})} × ${pax}pax</span>
          </div>`;
      });

      // Set items
      setItems.forEach(s => {
        const baseP = Number(s.set_price || 0);
        mealSubtotal += Number(s.total_price || 0);

        // Set header row
        html += `
          <div class="em-food-item em-food-item--set-header">
            <span class="em-food-cat">Set</span>
            <span class="em-food-name">${s.set_name}</span>
            <span class="em-food-price"><span class="em-price-formula">₱${baseP.toLocaleString('en-PH',{minimumFractionDigits:2})} × ${pax}pax</span></span>
          </div>`;

        // Base foods included in the set definition (viands, sides, etc.)
        (s.set_foods || []).forEach(sf => {
          html += `
            <div class="em-food-item em-food-item--set-food">
              <span class="em-food-cat">${sf.category}</span>
              <span class="em-food-name">${sf.name}</span>
              <span class="em-food-price"></span>
            </div>`;
        });

        // Customised items (Rice, Drink, Dessert, Fruit) chosen by the client
        (s.custom_items || []).forEach(ci => {
          html += `
            <div class="em-food-item em-food-item--custom">
              <span class="em-food-cat">${ci.category}</span>
              <span class="em-food-name">${ci.name}</span>
              <span class="em-food-price"></span>
            </div>`;
        });
      });

      if (mealSubtotal > 0) {
        html += `
          <div class="em-meal-subtotal">
            <span>${mealLabel} Subtotal</span>
            <span>${peso(mealSubtotal)}</span>
          </div>`;
      }
      dateSubtotal += mealSubtotal;
      html += `</div>`;  // .em-food-meal-group
    });

    if (dateSubtotal > 0) {
      html += `
        <div class="em-date-subtotal">
          <span>Subtotal</span>
          <span>${peso(dateSubtotal)}</span>
        </div>`;
    }

    html += `</div>`;  // .em-food-date-group
    container.insertAdjacentHTML('beforeend', html);
  });
}

function groupFoodsByDate(foods) {
  const grouped = {};

  foods.forEach(food => {
    // pivot.Food_Reservation_Serving_Date is the date ("2026-03-19"), not a meal label
    const rawDate = food.pivot?.Food_Reservation_Serving_Date;

    const date = rawDate
      ? new Date(rawDate).toLocaleDateString('en-US', {
          year: 'numeric',
          month: 'long',
          day: 'numeric'
        })
      : 'No Date';

    if (!grouped[date]) {
      grouped[date] = [];
    }

    grouped[date].push(food);
  });

  return grouped;
}

/**
 * Populate the food table from the foods array in data-info.
 * Each food has: Food_Name, Food_Category, pivot.Food_Reservation_Serving_Date (date),
 *               pivot.Food_Reservation_Meal_time (Breakfast / AM Snack / Lunch / PM Snack / Dinner)
 *
 * Columns (th order): Rice=1, Set Viand=2, Sidedish=3,
 *                     Drinks=4, Desserts=5, Other Viand=6, Snack=7
 * Rows (.meal-name):  Breakfast, AM Snack, Lunch, PM Snack, Dinner
 */
function createFoodTables(foods) {
  const container = document.getElementById('foodTablesContainer');
  const template  = document.querySelector('.food-table');

  if (!container || !template) return;

  container.innerHTML = '';

  // Food_Category values stored in DB (lowercase / snake_case)
  const categoryColMap = {
    rice:        1,
    set_viand:   2,
    sidedish:    3,
    drinks:      4,
    desserts:    5,
    other_viand: 6,
    snack:       7,
  };

  // ── Group foods by pivot.Food_Reservation_Serving_Date (the date) ──
  const groupedByDate = groupFoodsByDate(foods);

  Object.entries(groupedByDate).forEach(([date, foodList]) => {
    const newTable = template.cloneNode(true);

    // Reset every food cell
    newTable.querySelectorAll('.food-display').forEach(el => {
      el.textContent = '';
    });

    // Restore all meal rows (in case template had some hidden)
    newTable.querySelectorAll('tbody .meal-row').forEach(row => {
      row.style.display = '';
    });

    // Show the date in the header
    const dateEl = newTable.querySelector('.food-date');
    if (dateEl) dateEl.textContent = date;

    // ── Populate columns by Food_Reservation_Meal_time + Food_Category ──
    foodList.forEach(food => {
      const category = food.Food_Category?.toLowerCase().trim();
      const name     = food.Food_Name;
      // Food_Reservation_Meal_time stored in DB: e.g. "breakfast", "am_snack", "lunch", "pm_snack", "dinner"
      // Match against the visible .meal-name text (case-insensitive, underscore → space)
      const mealTimeRaw = food.pivot?.Food_Reservation_Meal_time;

      if (!category || !name) return;

      const colIndex = categoryColMap[category];
      if (!colIndex) return;

      // Find the correct meal row by matching .meal-name text
      let targetRow = null;
      if (mealTimeRaw) {
        // Normalise stored value: "am_snack" → "am snack", "PM_Snack" → "pm snack"
        const mealNormalized = mealTimeRaw.replace(/_/g, ' ').toLowerCase().trim();

        newTable.querySelectorAll('tbody .meal-row').forEach(row => {
          const labelEl = row.querySelector('.meal-name');
          if (labelEl) {
            const labelNormalized = labelEl.textContent.toLowerCase().trim();
            if (labelNormalized === mealNormalized) {
              targetRow = row;
            }
          }
        });
      }

      // Fallback: if Food_Reservation_Meal_time not found, put it in the first row
      if (!targetRow) {
        targetRow = newTable.querySelector('tbody .meal-row');
      }

      if (!targetRow) return;

      // td:nth-child(1) = label cell, food cells start at nth-child(2)
      const cell = targetRow.querySelector(`td:nth-child(${colIndex + 1})`);
      if (!cell) return;

      const display = cell.querySelector('.food-display');
      if (!display) return;

      // Append to the cell (multiple items in same category → comma-separated)
      if (display.textContent === 'None' || display.textContent === '') {
        display.textContent = name;
      } else {
        display.textContent += `, ${name}`;
      }
    });

    container.appendChild(newTable);
  });
}

function renderFoodTables(foods) {
  const container = document.getElementById('foodTablesContainer');
  const template = container.querySelector('.food-table-template');

  if (!container || !template) return;

  // clear old generated tables
  container.innerHTML = '';

  const groupedFoods = groupFoodsByDate(foods);

  Object.entries(groupedFoods).forEach(([date, foodsForDate], index) => {
    const newTable = template.cloneNode(true);

    newTable.classList.remove('food-table-template');
    newTable.classList.add('generated-food-table');

    const dateEl = newTable.querySelector('.food-date');
    if (dateEl) {
      dateEl.textContent = date;
    }

    populateSingleFoodTable(newTable, foodsForDate);

    container.appendChild(newTable);
  });
}

// --- 7. SAVE MODIFICATIONS SCRIPT ---
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
