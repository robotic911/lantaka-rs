document.addEventListener('DOMContentLoaded', () => {
  const masterWrap = document.querySelector('.food-toggle-section .toggle-buttons')
  const masterBtns = document.querySelectorAll('.food-toggle-section .toggle-btn')
  const mealSections = document.querySelectorAll('.meal-section')

  // ---------- NEW VARIABLES FOR PRICING ----------
  const displayTotalPrice = document.getElementById('displayTotalPrice')
  const paxValueInput = document.getElementById('paxValue')
  let pax = parseInt(paxValueInput?.value) || 1

  // ---------- NEW FUNCTION: Calculate Total ----------
  const calculateTotal = () => {
    let grandTotal = 0; // Changed this to just be the grand total

    // Loop through all currently selected items and add their flat price
    document.querySelectorAll('.food-item.selected').forEach(item => {
      const checkbox = item.querySelector('.food-checkbox');
      if (checkbox) {
        grandTotal += parseFloat(checkbox.getAttribute('data-price')) || 0;
      }
    });

    // We completely removed the " * pax " multiplication here!

    // Update the UI
    if (displayTotalPrice) {
      displayTotalPrice.textContent = '₱ ' + grandTotal.toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
      });
    }
  }

  // ---------- helpers ----------
  const setActiveBtn = (btn, wrap) => {
    wrap.querySelectorAll('.toggle-btn').forEach(b => b.classList.remove('active'))
    btn.classList.add('active')
  }

  const isMasterYes = () => {
    const active = document.querySelector('.food-toggle-section .toggle-btn.active')
    return active && active.textContent.trim().toLowerCase() === 'yes'
  }

  function setMealEnabled(meal, enabled) {
    meal.dataset.enabled = enabled ? '1' : '0'

    const pill = meal.querySelector('.toggle-status')
    if (pill) {
      pill.textContent = enabled ? 'Yes' : 'No'
      pill.classList.toggle('active', !enabled)
    }

    meal.querySelectorAll('.food-item').forEach(item => {
      item.classList.toggle('unavailable', !enabled);
      if (!enabled) {
        item.classList.remove('selected'); // This is good!
        const checkbox = item.querySelector('.food-checkbox');
        if (checkbox) checkbox.checked = false;
      }
    });
    calculateTotal();
  }

  function setAllMeals(enabled) {
    mealSections.forEach(meal => setMealEnabled(meal, enabled))
  }

  // ---------- DEFAULT STATE ----------
  if (masterWrap && masterBtns.length) {
    const yesBtn = [...masterBtns].find(b => b.textContent.trim().toLowerCase() === 'yes')
    if (yesBtn) setActiveBtn(yesBtn, masterWrap)
  }

  setAllMeals(true)

  document.querySelectorAll('.food-item').forEach(item => {
    item.classList.remove('selected')
    item.classList.remove('unavailable')
  })

  // ---------- MULTI-SELECT (per row) ----------
  document.querySelectorAll('.food-items').forEach(row => {
    row.addEventListener('click', (e) => {
      // Prevent default label click so we can manually control the checkbox
      e.preventDefault();

      const item = e.target.closest('.food-item')
      if (!item) return
      if (item.classList.contains('unavailable')) return

      // Toggle visual class
      item.classList.toggle('selected')

      // Toggle the actual hidden checkbox so it submits correctly
      const checkbox = item.querySelector('.food-checkbox')
      if (checkbox) {
        checkbox.checked = item.classList.contains('selected');
      }

      // Update the price!
      calculateTotal();
    })
  })

  // ---------- PER-MEAL TOGGLE (only affects that row) ----------
  mealSections.forEach(meal => {
    const pill = meal.querySelector('.toggle-status')
    if (!pill) return

    pill.addEventListener('click', () => {
      if (!isMasterYes()) return

      const enabled = meal.dataset.enabled === '1'
      setMealEnabled(meal, !enabled)
    })
  })

  // ---------- MASTER "Include Food" toggle ----------
  masterBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      if (!masterWrap) return

      setActiveBtn(btn, masterWrap)
      const yes = btn.textContent.trim().toLowerCase() === 'yes'

      if (!yes) {
        setAllMeals(false)
      } else {
        setAllMeals(true)
      }
    })
  })

  // Run calculation once on page load just to be safe
  calculateTotal();
})

// ---------- DATE SELECTOR MODAL LOGIC ----------
document.addEventListener('DOMContentLoaded', () => {
  const openBtn = document.querySelector('.date-select-btn')

  const overlay = document.getElementById('foodDateOverlay')
  const closeBtn = document.getElementById('foodDateClose')
  const confirmBtn = document.getElementById('foodDateConfirm')

  const dateList = document.getElementById('foodDateList')
  const errorEl = document.getElementById('foodDateError')

  const hiddenInput = document.getElementById('foodServiceDate')

  let selectedDate = '' // ISO YYYY-MM-DD

  const openModal = () => {
    if (!overlay) return
    overlay.classList.add('show')
    overlay.setAttribute('aria-hidden', 'false')

    errorEl && (errorEl.style.display = 'none')
  }

  const closeModal = () => {
    if (!overlay) return
    overlay.classList.remove('show')
    overlay.setAttribute('aria-hidden', 'true')
  }

  openBtn?.addEventListener('click', openModal)
  closeBtn?.addEventListener('click', closeModal)

  overlay?.addEventListener('click', (e) => {
    if (e.target === overlay) closeModal()
  })

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && overlay?.classList.contains('show')) closeModal()
  })

  dateList?.addEventListener('click', (e) => {
    const chip = e.target.closest('.fooddate-chip')
    if (!chip) return

    dateList.querySelectorAll('.fooddate-chip').forEach(c => c.classList.remove('selected'))
    chip.classList.add('selected')

    selectedDate = chip.dataset.date || ''
    if (errorEl) errorEl.style.display = 'none'
  })

  confirmBtn?.addEventListener('click', () => {
    if (!selectedDate) {
      if (errorEl) {
        errorEl.textContent = 'Please select a date.'
        errorEl.style.display = 'block'
      }
      return
    }

    if (hiddenInput) hiddenInput.value = selectedDate

    if (openBtn) openBtn.textContent = `Food Date: ${selectedDate}`

    closeModal()
  })
})









document.addEventListener('DOMContentLoaded', function () {

  const dateCards = document.querySelectorAll('.reservation-card');
  let foodsData = {};

  async function fetchFoods() {

      try {
          const response = await fetch(window.foodAjaxUrl, {
              headers: {
                  'X-Requested-With': 'XMLHttpRequest',
                  'Accept': 'application/json'
              }
          });

          if (!response.ok) {
              throw new Error('Failed to fetch foods');
          }

          foodsData = await response.json();

          populateFoodSelects();
          restorePreviousSelections(); // re-apply old selections if coming from Edit
          bindSelectEvents();
          initializeMealRows();
          updateTotal();

      } catch (error) {

          console.error('Error fetching foods:', error);

          document.querySelectorAll('.food-select').forEach(select => {
              select.innerHTML = '<option value="">Failed to load</option>';
              select.disabled = true;
              select.closest('.food-cell')?.classList.add('cell-disabled');
          });

      }
  }

  function populateFoodSelects() {

      document.querySelectorAll('.food-select').forEach(select => {

          const category = (select.dataset.category || '').toLowerCase();
          const categoryFoods = foodsData[category] || [];

          select.innerHTML = '';

          const defaultOption = document.createElement('option');
          defaultOption.value = '';
          defaultOption.textContent = 'None';
          select.appendChild(defaultOption);

          if (!categoryFoods.length) {

              select.disabled = true;
              select.closest('.food-cell')?.classList.add('cell-disabled');
              return;

          }

          select.closest('.food-cell')?.classList.remove('cell-disabled');

          categoryFoods.forEach(food => {

              const option = document.createElement('option');
              option.value = food.Food_ID;
              option.dataset.price = food.Food_Price;

              option.textContent =
                  `${food.Food_Name} - ₱${parseFloat(food.Food_Price).toFixed(2)}`;

              select.appendChild(option);

          });

      });

  }


  function bindSelectEvents() {

      document.querySelectorAll('.food-select').forEach(select => {
          select.addEventListener('change', updateTotal);
      });

  }


  function updateTotal() {

      let total = 0;

      document.querySelectorAll('.reservation-card').forEach(card => {

          const isFoodEnabled =
              card.querySelector('.food-enabled-input')?.value === '1';

          if (!isFoodEnabled) return;

          card.querySelectorAll('.meal-row').forEach(row => {

              if (row.classList.contains('row-disabled')) return;

              row.querySelectorAll('.food-select').forEach(select => {

                  if (select.disabled) return;

                  const selectedOption =
                      select.options[select.selectedIndex];

                  if (selectedOption && selectedOption.value) {

                      total += parseFloat(
                          selectedOption.dataset.price || 0
                      );

                  }

              });

          });

      });

      const pax =
          parseInt(document.getElementById('paxValue')?.value || 1);

      const displayTotal =
          document.getElementById('displayTotalPrice');

      if (displayTotal) {

          displayTotal.textContent =
              '₱ ' + (total * pax).toLocaleString(undefined, {
                  minimumFractionDigits: 2,
                  maximumFractionDigits: 2
              });

      }

  }


  function updateMealRowState(checkbox) {

      const date = checkbox.dataset.date;
      const meal = checkbox.dataset.meal;

      const row =
          document.querySelector(`[data-meal-row="${date}-${meal}"]`);

      if (!row) return;

      const hiddenInput = row.querySelector('.meal-enabled-hidden');
      const selects = row.querySelectorAll('.food-select');

      if (checkbox.checked) {

          row.classList.remove('row-disabled');
          hiddenInput.value = '1';

          selects.forEach(select => {

              if (!select.closest('.food-cell')
                  .classList.contains('cell-disabled')) {

                  select.disabled = false;

              }

          });

      } else {

          row.classList.add('row-disabled');
          hiddenInput.value = '0';

          selects.forEach(select => {

              select.value = '';
              select.disabled = true;

          });

      }

      updateTotal();

  }


  function updateCardFoodState(card, enabled) {

      const hidden =
          card.querySelector('.food-enabled-input');

      if (hidden)
          hidden.value = enabled ? '1' : '0';

      const yesBtn =
          card.querySelector('[data-toggle="yes"]');

      const noBtn =
          card.querySelector('[data-toggle="no"]');

      if (enabled) {

          yesBtn?.classList.add('active');
          noBtn?.classList.remove('active');

          card.classList.remove('food-disabled-card');

          card.querySelectorAll('.meal-toggle-checkbox')
              .forEach(checkbox => {

                  checkbox.disabled = false;
                  updateMealRowState(checkbox);

              });

      } else {

          noBtn?.classList.add('active');
          yesBtn?.classList.remove('active');

          card.classList.add('food-disabled-card');

          card.querySelectorAll('.meal-toggle-checkbox')
              .forEach(checkbox => {

                  checkbox.disabled = true;

              });

          card.querySelectorAll('.meal-row')
              .forEach(row => {

                  row.classList.add('row-disabled');

                  const hiddenInput =
                      row.querySelector('.meal-enabled-hidden');

                  if (hiddenInput)
                      hiddenInput.value = '0';

                  row.querySelectorAll('.food-select')
                      .forEach(select => {

                          select.value = '';
                          select.disabled = true;

                      });

              });

      }

      updateTotal();

  }


  function initializeMealRows() {

      document.querySelectorAll('.meal-toggle-checkbox')
          .forEach(checkbox => {

              updateMealRowState(checkbox);

              checkbox.addEventListener('change', function () {
                  updateMealRowState(this);
              });

          });

      dateCards.forEach(card => {

          const yesBtn =
              card.querySelector('[data-toggle="yes"]');

          const noBtn =
              card.querySelector('[data-toggle="no"]');

          yesBtn?.addEventListener('click', function () {
              updateCardFoodState(card, true);
          });

          noBtn?.addEventListener('click', function () {
              updateCardFoodState(card, false);
          });

      });

  }

  // Restore previous food selections (only set when coming from Edit cart)
  function restorePreviousSelections() {
      const prev         = window.previousFoodSelections || {};
      const foodEnabled  = window.previousFoodEnabled    || {};
      const mealEnabled  = window.previousMealEnabled    || {};

      if (!Object.keys(prev).length && !Object.keys(foodEnabled).length) return;

      document.querySelectorAll('.reservation-card').forEach(card => {
          const date = card.dataset.date;
          if (!date) return;

          // Restore food-disabled state for this date
          if (foodEnabled[date] === '0') {
              const noBtn = card.querySelector('[data-toggle="no"]');
              if (noBtn) noBtn.click();
              return;
          }

          const dateMeals = prev[date] || {};

          Object.entries(dateMeals).forEach(([mealType, categories]) => {
              // Restore disabled meal rows
              const isMealEnabled = (mealEnabled[date]?.[mealType] ?? '1') !== '0';
              if (!isMealEnabled) {
                  const checkbox = card.querySelector(
                      `.meal-toggle-checkbox[data-date="${date}"][data-meal="${mealType}"]`
                  );
                  if (checkbox && checkbox.checked) {
                      checkbox.checked = false;
                      checkbox.dispatchEvent(new Event('change'));
                  }
                  return;
              }

              // Restore selected food per category
              if (categories && typeof categories === 'object') {
                  Object.entries(categories).forEach(([category, foodId]) => {
                      if (!foodId) return;
                      const select = card.querySelector(
                          `select[name="food_selections[${date}][${mealType}][${category}]"]`
                      );
                      if (select) {
                          select.value = String(foodId);
                          select.dispatchEvent(new Event('change'));
                      }
                  });
              }
          });
      });

      updateTotal();
  }

  fetchFoods();

});
