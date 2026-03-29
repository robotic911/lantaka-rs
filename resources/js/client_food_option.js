/**
 * client_food_option.js
 * Handles the Food Reservation page:
 *  – Loads individual foods (by category) and food sets (by meal time) via AJAX
 *  – Manages per-date "Include Food" toggle
 *  – Manages per-meal "Include" checkbox
 *  – Manages per-meal mode switcher (Individual ↔ Set)
 *  – Calculates running food total (per-pax × pax count)
 *  – Restores previous selections when editing a cart item
 */

document.addEventListener('DOMContentLoaded', function () {

  /* ── AJAX data stores ───────────────────────────────────────── */
  let foodsData    = {};   // { category: [{ Food_ID, Food_Name, Food_Price }] }
  let foodSetsData = {};   // { meal_time: [{ Food_Set_ID, Food_Set_Name, Food_Set_Price }] }

  /* ── DOM helpers ────────────────────────────────────────────── */
  const dateCards    = document.querySelectorAll('.reservation-card');
  const displayTotal = document.getElementById('displayTotalPrice');
  const paxValue     = document.getElementById('paxValue');

  /* ─────────────────────────────────────────────────────────────
     1. FETCH FOODS & SETS IN PARALLEL, THEN WIRE UP
  ───────────────────────────────────────────────────────────── */
  async function fetchAll() {
    try {
      const [foodsRes, setsRes] = await Promise.all([
        fetch(window.foodAjaxUrl,     { headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' } }),
        fetch(window.foodSetsAjaxUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' } }),
      ]);

      if (!foodsRes.ok) throw new Error('Failed to fetch foods');
      if (!setsRes.ok)  throw new Error('Failed to fetch food sets');

      foodsData    = await foodsRes.json();   // { category → [...] }
      foodSetsData = await setsRes.json();    // { meal_time → [...] }

      populateIndividualSelects();
      populateSetSelects();
      initializeMealRows();
      restorePreviousSelections();
      updateTotal();

    } catch (err) {
      console.error('Food fetch error:', err);
      document.querySelectorAll('.food-select').forEach(s => {
        s.innerHTML  = '<option value="">Failed to load</option>';
        s.disabled   = true;
        s.closest('.food-cell')?.classList.add('cell-disabled');
      });
      document.querySelectorAll('.food-set-select').forEach(s => {
        s.innerHTML = '<option value="">Failed to load</option>';
        s.disabled  = true;
      });
    }
  }

  /* ─────────────────────────────────────────────────────────────
     2. POPULATE INDIVIDUAL FOOD SELECTS
  ───────────────────────────────────────────────────────────── */
  function populateIndividualSelects() {
    document.querySelectorAll('.food-select').forEach(select => {
      const cat   = (select.dataset.category || '').toLowerCase();
      const items = foodsData[cat] || [];

      select.innerHTML = '';
      select.appendChild(makeOption('', 'None'));

      if (!items.length) {
        select.disabled = true;
        select.closest('.food-cell')?.classList.add('cell-disabled');
        return;
      }
      select.closest('.food-cell')?.classList.remove('cell-disabled');

      items.forEach(food => {
        const opt       = makeOption(food.Food_ID, `${food.Food_Name} — ₱${parseFloat(food.Food_Price).toFixed(2)}`);
        opt.dataset.price = food.Food_Price;
        select.appendChild(opt);
      });

      select.addEventListener('change', updateTotal);
    });
  }

  /* ─────────────────────────────────────────────────────────────
     3. POPULATE FOOD SET SELECTS (one per meal-row)
  ───────────────────────────────────────────────────────────── */
  function populateSetSelects() {
    document.querySelectorAll('.food-set-select').forEach(select => {
      const meal  = (select.dataset.meal || '').toLowerCase();
      const items = foodSetsData[meal] || [];

      select.innerHTML = '';
      select.appendChild(makeOption('', 'No set'));

      if (!items.length) {
        const na = makeOption('', 'No sets available for this meal');
        na.disabled = true;
        select.appendChild(na);
      } else {
        items.forEach(set => {
          const opt       = makeOption(set.Food_Set_ID, `${set.Food_Set_Name} — ₱${parseFloat(set.Food_Set_Price).toFixed(2)}/pax`);
          opt.dataset.price = set.Food_Set_Price;
          select.appendChild(opt);
        });
      }

      select.addEventListener('change', updateTotal);
    });
  }

  /* ─────────────────────────────────────────────────────────────
     4. TOTAL CALCULATION
  ───────────────────────────────────────────────────────────── */
  function updateTotal() {
    let subtotal = 0;
    const pax    = parseInt(paxValue?.value || 1);

    document.querySelectorAll('.reservation-card').forEach(card => {
      if (card.querySelector('.food-enabled-input')?.value !== '1') return;

      card.querySelectorAll('.meal-row').forEach(row => {
        if (row.classList.contains('row-disabled')) return;

        const mode = row.dataset.mode || 'individual';

        if (mode === 'set') {
          // Only the selected food set contributes
          const setSelect = row.querySelector('.food-set-select');
          if (setSelect && !setSelect.disabled && setSelect.value) {
            const opt = setSelect.options[setSelect.selectedIndex];
            subtotal += parseFloat(opt.dataset.price || 0);
          }
        } else {
          // Individual mode: sum each category select
          row.querySelectorAll('.food-select').forEach(select => {
            if (select.disabled || !select.value) return;
            const opt = select.options[select.selectedIndex];
            subtotal += parseFloat(opt.dataset.price || 0);
          });
        }
      });
    });

    if (displayTotal) {
      displayTotal.textContent = '₱ ' + (subtotal * pax).toLocaleString(undefined, {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      });
    }
  }

  /* ─────────────────────────────────────────────────────────────
     5. MEAL ROW STATE (enabled / disabled)
  ───────────────────────────────────────────────────────────── */
  function setMealRowEnabled(checkbox) {
    const date    = checkbox.dataset.date;
    const meal    = checkbox.dataset.meal;
    const row     = document.querySelector(`[data-meal-row="${date}-${meal}"]`);
    if (!row) return;

    const hidden  = row.querySelector('.meal-enabled-hidden');
    const enabled = checkbox.checked;

    if (hidden) hidden.value = enabled ? '1' : '0';

    if (enabled) {
      row.classList.remove('row-disabled');
      // Re-enable selects based on current mode
      applyModeToRow(row, row.dataset.mode || 'individual');
    } else {
      row.classList.add('row-disabled');
      row.querySelectorAll('.food-select, .food-set-select').forEach(s => {
        s.value   = '';
        s.disabled = true;
      });
      row.querySelectorAll('.mode-btn').forEach(b => b.disabled = true);
    }
    updateTotal();
  }

  /* ─────────────────────────────────────────────────────────────
     6. MODE SWITCHER (Individual ↔ Set) per meal row
  ───────────────────────────────────────────────────────────── */
  function applyModeToRow(row, mode) {
    row.dataset.mode = mode;

    const modeHidden   = row.querySelector('.meal-mode-hidden');
    if (modeHidden) modeHidden.value = mode;

    const indivCells   = row.querySelectorAll('.indiv-cell');
    const setCells     = row.querySelectorAll('.set-cell');
    const indivSelects = row.querySelectorAll('.food-select');
    const setSelect    = row.querySelector('.food-set-select');

    if (mode === 'set') {
      indivCells.forEach(c => (c.style.display = 'none'));
      setCells.forEach(c   => (c.style.display = ''));
      indivSelects.forEach(s => { s.disabled = true;  s.value = ''; });
      if (setSelect && !row.classList.contains('row-disabled')) {
        setSelect.disabled = false;
      }
    } else {
      indivCells.forEach(c => (c.style.display = ''));
      setCells.forEach(c   => (c.style.display = 'none'));
      if (setSelect) { setSelect.disabled = true; setSelect.value = ''; }
      indivSelects.forEach(s => {
        if (!s.closest('.food-cell')?.classList.contains('cell-disabled')) {
          s.disabled = false;
        }
      });
    }

    updateTotal();
  }

  function bindModeButtons() {
    document.querySelectorAll('.mode-btn').forEach(btn => {
      btn.addEventListener('click', function () {
        const date = this.dataset.date;
        const meal = this.dataset.meal;
        const mode = this.dataset.mode;
        const row  = document.querySelector(`[data-meal-row="${date}-${meal}"]`);
        if (!row || row.classList.contains('row-disabled')) return;

        // Toggle active class on sibling buttons
        const switcher = this.closest('.meal-mode-switcher');
        switcher?.querySelectorAll('.mode-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');

        applyModeToRow(row, mode);
      });
    });
  }

  /* ─────────────────────────────────────────────────────────────
     7. CARD FOOD TOGGLE (Yes / No per date)
  ───────────────────────────────────────────────────────────── */
  function setCardFoodEnabled(card, enabled) {
    const hidden = card.querySelector('.food-enabled-input');
    if (hidden) hidden.value = enabled ? '1' : '0';

    const yesBtn = card.querySelector('[data-toggle="yes"]');
    const noBtn  = card.querySelector('[data-toggle="no"]');

    if (enabled) {
      yesBtn?.classList.add('active');
      noBtn?.classList.remove('active');
      card.classList.remove('food-disabled-card');
      card.querySelectorAll('.meal-toggle-checkbox').forEach(cb => {
        cb.disabled = false;
        setMealRowEnabled(cb);
      });
    } else {
      noBtn?.classList.add('active');
      yesBtn?.classList.remove('active');
      card.classList.add('food-disabled-card');
      card.querySelectorAll('.meal-toggle-checkbox').forEach(cb => {
        cb.disabled = true;
      });
      card.querySelectorAll('.meal-row').forEach(row => {
        row.classList.add('row-disabled');
        const mh = row.querySelector('.meal-enabled-hidden');
        if (mh) mh.value = '0';
        row.querySelectorAll('.food-select, .food-set-select').forEach(s => {
          s.value   = '';
          s.disabled = true;
        });
        row.querySelectorAll('.mode-btn').forEach(b => b.disabled = true);
      });
    }
    updateTotal();
  }

  /* ─────────────────────────────────────────────────────────────
     8. WIRE UP EVENT LISTENERS
  ───────────────────────────────────────────────────────────── */
  function initializeMealRows() {
    // Meal include checkboxes
    document.querySelectorAll('.meal-toggle-checkbox').forEach(cb => {
      setMealRowEnabled(cb);
      cb.addEventListener('change', function () { setMealRowEnabled(this); });
    });

    // Card Yes/No buttons
    dateCards.forEach(card => {
      card.querySelector('[data-toggle="yes"]')?.addEventListener('click', () => setCardFoodEnabled(card, true));
      card.querySelector('[data-toggle="no"]')?.addEventListener('click',  () => setCardFoodEnabled(card, false));
    });

    // Mode switcher buttons
    bindModeButtons();
  }

  /* ─────────────────────────────────────────────────────────────
     9. RESTORE PREVIOUS SELECTIONS (Edit Cart flow)
  ───────────────────────────────────────────────────────────── */
  function restorePreviousSelections() {
    const prev        = window.previousFoodSelections || {};
    const foodEnabled = window.previousFoodEnabled    || {};
    const mealEnabled = window.previousMealEnabled    || {};
    const mealMode    = window.previousMealMode       || {};
    const setSelects  = window.previousSetSelections  || {};

    if (!Object.keys(prev).length && !Object.keys(foodEnabled).length) return;

    document.querySelectorAll('.reservation-card').forEach(card => {
      const date = card.dataset.date;
      if (!date) return;

      // Restore food disabled
      if (foodEnabled[date] === '0') {
        card.querySelector('[data-toggle="no"]')?.click();
        return;
      }

      const dateMeals    = prev[date]       || {};
      const dateModes    = mealMode[date]   || {};
      const dateSetSels  = setSelects[date] || {};

      Object.entries(dateMeals).forEach(([mealKey, categories]) => {
        const isMealEnabled = (mealEnabled[date]?.[mealKey] ?? '1') !== '0';
        const row = card.querySelector(`[data-meal-row="${date}-${mealKey}"]`);

        if (!isMealEnabled) {
          const cb = card.querySelector(`.meal-toggle-checkbox[data-date="${date}"][data-meal="${mealKey}"]`);
          if (cb && cb.checked) { cb.checked = false; cb.dispatchEvent(new Event('change')); }
          return;
        }

        // Restore mode
        const savedMode = dateModes[mealKey] || 'individual';
        if (row) {
          const modeBtn = row.querySelector(`.mode-btn[data-mode="${savedMode}"]`);
          if (modeBtn) modeBtn.click();
        }

        if (savedMode === 'set') {
          const setId  = dateSetSels[mealKey];
          const setSel = card.querySelector(`.food-set-select[data-date="${date}"][data-meal="${mealKey}"]`);
          if (setSel && setId) {
            setSel.value = String(setId);
            setSel.dispatchEvent(new Event('change'));
          }
        } else {
          // Individual: restore per-category
          if (categories && typeof categories === 'object') {
            Object.entries(categories).forEach(([category, foodId]) => {
              if (!foodId) return;
              const sel = card.querySelector(`select[name="food_selections[${date}][${mealKey}][${category}]"]`);
              if (sel) { sel.value = String(foodId); sel.dispatchEvent(new Event('change')); }
            });
          }
        }
      });
    });

    updateTotal();
  }

  /* ─────────────────────────────────────────────────────────────
     UTIL
  ───────────────────────────────────────────────────────────── */
  function makeOption(value, text) {
    const o   = document.createElement('option');
    o.value   = value;
    o.textContent = text;
    return o;
  }

  // Kick off
  fetchAll();

});
