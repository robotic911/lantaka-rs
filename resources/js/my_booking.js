/**
 * my_booking.js — Checkout page logic
 *
 * Cart items are toggled (highlighted) by clicking them.
 * The right-side summary panel renders:
 *   • Accommodation line (name, days × rate, subtotal)
 *   • Per-date food breakdown:
 *       – Spiritual (retreat/recollection): set per meal + AM/PM snacks
 *       – General set mode:                selected sets + snacks
 *       – Individual order mode:           per-meal selections + snacks
 *   • Food subtotal per date
 *   • Grand total
 */

let cart = {};

/* ─── constants ─────────────────────────────────────────────────── */
const MEAL_LABELS = {
  breakfast: 'Breakfast',
  lunch:     'Lunch',
  dinner:    'Dinner',
  am_snack:  'AM Snack',
  pm_snack:  'PM Snack',
  snacks:    'Snacks',
};

const MEAL_ICONS = {
  breakfast: '🍳',
  lunch:     '🍽',
  dinner:    '🌙',
  am_snack:  '☕',
  pm_snack:  '🍰',
  snacks:    '🍪',
};

/* ─── helpers ───────────────────────────────────────────────────── */
function formatPeso(value) {
  return `₱ ${Number(value || 0).toLocaleString('en-PH', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  })}`;
}

function formatDate(dateStr) {
  const d = new Date(dateStr + 'T00:00:00');
  return d.toLocaleDateString('en-PH', { year: 'numeric', month: 'long', day: 'numeric' });
}

function getCartKeyFromElement(el) {
  return `${el.dataset.type}_${el.dataset.id}_${el.dataset.in}_${el.dataset.out}`;
}

/* ─── parse cart item from DOM element ─────────────────────────── */
function parseCartItem(element) {
  const pax         = Number(element.dataset.pax  || 1);
  const days        = Number(element.dataset.days || 1);
  const basePrice   = Number(element.dataset.base || 0);
  const baseTotal   = Number(element.dataset.total || 0);
  const purpose     = (element.dataset.purpose || '').toLowerCase();
  const notes       = element.dataset.notes || '';
  const isSpiritual = ['retreat', 'recollection'].includes(purpose);

  let foods          = [];
  let foodSets       = [];
  let foodSelections = {};
  let foodSetSel     = {};
  let foodEnabled    = {};
  let mealEnabled    = {};
  let mealMode       = {};

  try { foods          = JSON.parse(element.dataset.food          || '[]'); } catch {}
  try { foodSets       = JSON.parse(element.dataset.foodSets      || '[]'); } catch {}
  try { foodSelections = JSON.parse(element.dataset.foodSelections || '{}'); } catch {}
  try { foodSetSel     = JSON.parse(element.dataset.foodSetSelection || '{}'); } catch {}
  try { foodEnabled    = JSON.parse(element.dataset.foodEnabled   || '{}'); } catch {}
  try { mealEnabled    = JSON.parse(element.dataset.mealEnabled   || '{}'); } catch {}
  try { mealMode       = JSON.parse(element.dataset.mealMode      || '{}'); } catch {}

  // Build lookup maps
  const foodMap = {};
  foods.forEach(f => { foodMap[String(f.Food_ID)] = f; });

  const setMap = {};
  foodSets.forEach(s => { setMap[String(s.Food_Set_ID)] = s; });

  /**
   * PHP serialises food_selections[date][meal_key][] as a mixed-key object when
   * there is also a placeholder key (e.g. 'snacks' → ''), so we handle both
   * plain JS arrays AND plain objects with numeric/string keys.
   *
   * IMPORTANT: single-select snacks store the food ID under the 'snacks' key
   *   (food_selections[date][am_snack][snacks] = food_id), so we must NOT
   *   filter out all 'snacks' keyed entries — only filter when the value is empty.
   * We skip empty strings, null, and undefined values regardless of key name.
   */
  function extractIds(rawVal) {
    if (!rawVal) return [];
    if (Array.isArray(rawVal)) return rawVal.map(String).filter(Boolean);
    if (typeof rawVal === 'object') {
      return Object.entries(rawVal)
        .filter(([k, v]) => v !== '' && v !== null && v !== undefined)
        .map(([, v]) => String(v))
        .filter(Boolean);
    }
    const s = String(rawVal);
    return s ? [s] : [];
  }

  // Build per-date food detail groups for the summary
  const foodGroups = [];  // { date, dateLabel, type, sets:[], snacks:[], meals:[], foodSubtotal }

  // Collect all dates from both foodSetSel + foodSelections
  const allDates = [...new Set([
    ...Object.keys(foodSetSel),
    ...Object.keys(foodSelections),
  ])];

  let totalFoodCalc = 0;

  allDates.forEach(date => {
    if ((foodEnabled[date] ?? '1') !== '1') return;

    const dateModeMap  = mealMode[date] || {};
    const dateIsIndiv  = Object.values(dateModeMap).some(v => v === 'individual');
    const dateFoodSel  = foodSelections[date] || {};
    const dateSets     = foodSetSel[date] || {};

    let dateSubtotal = 0;
    const group = { date, dateLabel: formatDate(date), type: '', sets: [], snacks: [], meals: [] };

    if (dateIsIndiv) {
      /* ── Individual order ── */
      group.type = 'individual';

      ['breakfast', 'lunch', 'dinner'].forEach(mealKey => {
        if ((mealEnabled[date]?.[mealKey] ?? '1') !== '1') return;
        const mc = dateFoodSel[mealKey] || {};
        const mealItems = [];

        const riceFood = mc.rice ? foodMap[String(mc.rice)] : null;
        if (riceFood) {
          mealItems.push({ label: 'Rice', name: riceFood.Food_Name, price: Number(riceFood.Food_Price || 0), extra: false });
          dateSubtotal += Number(riceFood.Food_Price || 0) * pax;
        }

        const v1 = mc.viand1 ? foodMap[String(mc.viand1)] : null;
        if (v1) {
          mealItems.push({ label: 'Viand', name: v1.Food_Name, price: Number(v1.Food_Price || 0), extra: false });
          dateSubtotal += Number(v1.Food_Price || 0) * pax;
        }

        const v2 = mc.viand2 ? foodMap[String(mc.viand2)] : null;
        if (v2) {
          mealItems.push({ label: 'Viand', name: v2.Food_Name, price: Number(v2.Food_Price || 0), extra: false });
          dateSubtotal += Number(v2.Food_Price || 0) * pax;
        }

        const drk = mc.drink ? foodMap[String(mc.drink)] : null;
        if (drk) {
          mealItems.push({ label: 'Drink', name: drk.Food_Name, price: Number(drk.Food_Price || 0), extra: false });
          dateSubtotal += Number(drk.Food_Price || 0) * pax;
        }

        if (Array.isArray(mc.extra_viands)) {
          mc.extra_viands.forEach(id => {
            const f = foodMap[String(id)];
            if (f) {
              mealItems.push({ label: '+Viand', name: f.Food_Name, price: Number(f.Food_Price || 0), extra: true });
              dateSubtotal += Number(f.Food_Price || 0) * pax;
            }
          });
        }

        if (Array.isArray(mc.desserts)) {
          mc.desserts.forEach(id => {
            const f = foodMap[String(id)];
            if (f) {
              mealItems.push({ label: 'Dessert', name: f.Food_Name, price: Number(f.Food_Price || 0), extra: true });
              dateSubtotal += Number(f.Food_Price || 0) * pax;
            }
          });
        }

        if (mealItems.length) {
          group.meals.push({
            mealKey,
            label: MEAL_LABELS[mealKey] || mealKey,
            icon:  MEAL_ICONS[mealKey]  || '🍽',
            items: mealItems,
          });
        }
      });

      // Snacks for individual (PHP may encode as mixed-key object)
      const snackIds = extractIds(dateFoodSel.snacks);
      snackIds.forEach(id => {
        const f = foodMap[String(id)];
        if (f) {
          group.snacks.push({ label: 'Snack', icon: MEAL_ICONS.snacks, name: f.Food_Name, price: f.Food_Price });
          dateSubtotal += Number(f.Food_Price || 0) * pax;
        }
      });

      // AM/PM snacks for individual mode
      ['am_snack', 'pm_snack'].forEach(snackKey => {
        const ids = extractIds(dateFoodSel[snackKey]);
        ids.forEach(id => {
          const f = foodMap[String(id)];
          if (f) {
            group.snacks.push({
              label: MEAL_LABELS[snackKey],
              icon:  MEAL_ICONS[snackKey] || '🍪',
              name:  f.Food_Name,
              price: f.Food_Price,
            });
            dateSubtotal += Number(f.Food_Price || 0) * pax;
          }
        });
      });

    } else if (isSpiritual) {
      /* ── Spiritual set mode ── */
      group.type = 'spiritual';

      ['breakfast', 'lunch', 'dinner'].forEach(mealKey => {
        const setId = dateSets[mealKey];
        if (!setId) return;
        const set = setMap[String(setId)];
        if (!set) return;
        group.sets.push({
          mealKey,
          icon:     MEAL_ICONS[mealKey]  || '🍱',
          label:    MEAL_LABELS[mealKey] || mealKey,
          setName:  set.Food_Set_Name,
          setPrice: set.Food_Set_Price,
        });
        dateSubtotal += Number(set.Food_Set_Price || 0) * pax;
      });

      // AM/PM snacks (PHP may encode as mixed-key object)
      ['am_snack', 'pm_snack'].forEach(snackKey => {
        const ids = extractIds(dateFoodSel[snackKey]);
        ids.forEach(id => {
          const f = foodMap[String(id)];
          if (f) {
            group.snacks.push({
              label: MEAL_LABELS[snackKey],
              icon:  MEAL_ICONS[snackKey] || '🍪',
              name:  f.Food_Name,
              price: f.Food_Price,
            });
            dateSubtotal += Number(f.Food_Price || 0) * pax;
          }
        });
      });

    } else {
      /* ── General set mode ── */
      group.type = 'general';

      const shownSetIds = new Set();
      Object.values(dateSets).forEach(setIdOrIds => {
        const ids = Array.isArray(setIdOrIds) ? setIdOrIds : [setIdOrIds];
        ids.forEach(id => {
          if (!id || shownSetIds.has(String(id))) return;
          shownSetIds.add(String(id));
          const set = setMap[String(id)];
          if (set) {
            group.sets.push({
              setName:  set.Food_Set_Name,
              setPrice: set.Food_Set_Price,
            });
            dateSubtotal += Number(set.Food_Set_Price || 0) * pax;
          }
        });
      });

      // Snacks (PHP may encode as mixed-key object)
      const snackIds = extractIds(dateFoodSel.snacks);
      snackIds.forEach(id => {
        const f = foodMap[String(id)];
        if (f) {
          group.snacks.push({ icon: MEAL_ICONS.snacks, name: f.Food_Name, price: f.Food_Price });
          dateSubtotal += Number(f.Food_Price || 0) * pax;
        }
      });

      // AM/PM snacks for general mode
      ['am_snack', 'pm_snack'].forEach(snackKey => {
        const ids = extractIds(dateFoodSel[snackKey]);
        ids.forEach(id => {
          const f = foodMap[String(id)];
          if (f) {
            group.snacks.push({
              label: MEAL_LABELS[snackKey],
              icon:  MEAL_ICONS[snackKey] || '🍪',
              name:  f.Food_Name,
              price: f.Food_Price,
            });
            dateSubtotal += Number(f.Food_Price || 0) * pax;
          }
        });
      });
    }

    group.foodSubtotal = dateSubtotal;
    totalFoodCalc += dateSubtotal;

    if (group.sets.length || group.snacks.length || group.meals.length) {
      foodGroups.push(group);
    }
  });

  // Always use the JS-computed total (server value can miss set prices when sets aren't
  // in food_selections; also handles cases where snack-only total is stored server-side)
  const foodTotal  = totalFoodCalc;
  const finalTotal = baseTotal + foodTotal;

  return {
    key:           getCartKeyFromElement(element),
    name:          element.dataset.name,
    baseTotal,
    basePrice,
    days,
    foodTotal,
    total:         finalTotal,
    id:            element.dataset.id,
    type:          element.dataset.type,
    checkIn:       element.dataset.in,
    checkOut:      element.dataset.out,
    pax,
    purpose,
    notes,
    isSpiritual,
    food:          foods.map(f => f.Food_ID),
    foodGroups,
    foodEnabled,
    mealEnabled,
    foodSelections,
    foodSetSel,
  };
}

/* ─── render summary items (accommodation lines) ──────────────── */
function renderSummaryItems() {
  const container = document.getElementById('summary-items');
  if (!container) return;
  container.innerHTML = '';

  Object.values(cart).forEach(item => {
    const typeLabel = item.type === 'room' ? 'night' : 'day';
    const html = `
      <div class="summary-venue-block">
        <div class="summary-venue-name">${item.name}</div>
        <div class="summary-venue-line">
          <span class="summary-venue-rate">₱ ${Number(item.basePrice).toLocaleString('en-PH', {minimumFractionDigits:2})} × ${item.days} ${typeLabel}${item.days !== 1 ? 's' : ''}</span>
          <span class="summary-venue-amt">${formatPeso(item.baseTotal)}</span>
        </div>
      </div>
    `;
    container.insertAdjacentHTML('beforeend', html);
  });
}

/* ─── render food groups (right summary panel) ─────────────────── */
function renderSummaryFoods() {
  const container = document.getElementById('summary-foods');
  if (!container) return;
  container.innerHTML = '';

  Object.values(cart).forEach(item => {
    if (item.type !== 'venue' || !item.foodGroups || item.foodGroups.length === 0) return;

    item.foodGroups.forEach(group => {
      let html = `<div class="sf-date-block">`;
      html += `<div class="sf-date-header">📅 ${group.dateLabel}</div>`;

      /* ── Sets section ── */
      if (group.sets.length) {
        if (group.type === 'spiritual') {
          html += `<div class="sf-section-label">Meal Sets</div>`;
          group.sets.forEach(s => {
            const baseP = Number(s.setPrice || 0);
            const total = baseP * item.pax;
            html += `
              <div class="sf-meal-row">
                <span class="sf-meal-icon">${s.icon}</span>
                <span class="sf-meal-label">${s.label}</span>
                <span class="sf-meal-name">${s.setName}</span>
                <span class="sf-meal-price"><span class="sf-price-formula">₱${baseP.toLocaleString('en-PH',{minimumFractionDigits:2})} × ${item.pax} pax</span> = ${formatPeso(total)}</span>
              </div>`;
          });
        } else {
          html += `<div class="sf-section-label">Sets</div>`;
          group.sets.forEach(s => {
            const baseP = Number(s.setPrice || 0);
            const total = baseP * item.pax;
            html += `
              <div class="sf-meal-row">
                <span class="sf-meal-icon">🍱</span>
                <span class="sf-meal-name sf-meal-name--full">${s.setName}</span>
                <span class="sf-meal-price"><span class="sf-price-formula">₱${baseP.toLocaleString('en-PH',{minimumFractionDigits:2})} × ${item.pax} pax</span> = ${formatPeso(total)}</span>
              </div>`;
          });
        }
      }

      /* ── Individual meals section ── */
      if (group.meals.length) {
        group.meals.forEach(meal => {
          const mealSubtotal = meal.items.reduce((s, i) => s + (i.price || 0) * item.pax, 0);
          html += `
            <div class="sf-indiv-meal">
              <div class="sf-indiv-meal-header">
                <span>${meal.icon} ${meal.label}</span>
              </div>`;
          meal.items.forEach(item2 => {
            const priceStr = item2.price > 0
              ? `<span class="sf-indiv-price">₱${item2.price.toLocaleString('en-PH',{minimumFractionDigits:2})} × ${item.pax}pax</span>`
              : '';
            html += `
              <div class="sf-indiv-item ${item2.extra ? 'sf-indiv-item--extra' : ''}">
                <span class="sf-indiv-cat">${item2.label}</span>
                <span class="sf-indiv-name">${item2.name}</span>
                ${priceStr}
              </div>`;
          });
          if (mealSubtotal > 0) {
            html += `
              <div class="sf-indiv-meal-subtotal">
                <span>${meal.label} Subtotal</span>
                <span>${formatPeso(mealSubtotal)}</span>
              </div>`;
          }
          html += `</div>`;
        });
      }

      /* ── Snacks section ── */
      if (group.snacks.length) {
        html += `<div class="sf-section-label sf-section-label--snack">Snacks</div>`;
        group.snacks.forEach(s => {
          const baseP = Number(s.price || 0);
          const total = baseP * item.pax;
          html += `
            <div class="sf-meal-row sf-meal-row--snack">
              ${s.icon ? `<span class="sf-meal-icon">${s.icon}</span>` : `<span class="sf-meal-icon">🍪</span>`}
              ${s.label ? `<span class="sf-meal-label">${s.label}</span>` : ''}
              <span class="sf-meal-name">${s.name}</span>
              <span class="sf-meal-price"><span class="sf-price-formula">₱${baseP.toLocaleString('en-PH',{minimumFractionDigits:2})} × ${item.pax} pax</span> = ${formatPeso(total)}</span>
            </div>`;
        });
      }

      /* ── Food subtotal for this date ── */
      if (group.foodSubtotal > 0) {
        html += `
          <div class="sf-date-subtotal">
            <span>Subtotal</span>
            <span>${formatPeso(group.foodSubtotal)}</span>
          </div>`;
      }

      html += `</div>`;
      container.insertAdjacentHTML('beforeend', html);
    });

    /* ── Total food line ── */
    if (item.foodTotal > 0) {
      container.insertAdjacentHTML('beforeend', `
        <div class="sf-food-total-row">
          <span>Food Total</span>
          <span>${formatPeso(item.foodTotal)}</span>
        </div>
      `);
    }
  });
}

/* ─── grand total ───────────────────────────────────────────────── */
function updateGrandTotal() {
  const grandTotal = Object.values(cart).reduce((sum, item) => sum + Number(item.total || 0), 0);
  const el = document.getElementById('summary-grand-total');
  if (el) el.textContent = formatPeso(grandTotal);

  renderSummaryItems();
  renderSummaryFoods();
}

/* ─── hidden input for form submission ─────────────────────────── */
function updateHiddenSelectedItemsInput() {
  const input = document.getElementById('selected-items-input');
  if (!input) return;

  const selectedItems = Object.values(cart).map(item => ({
    id:               item.id,
    type:             item.type,
    basePrice:        item.basePrice,
    check_in:         item.checkIn,
    check_out:        item.checkOut,
    pax:              item.pax,
    purpose:          item.purpose || '',
    notes:            item.notes || '',
    total_amount:     item.total,
    food:             item.food || [],
    food_enabled:     item.foodEnabled     || {},
    meal_enabled:     item.mealEnabled     || {},
    food_selections:  item.foodSelections  || {},
    food_set_selection: item.foodSetSel    || {},
  }));

  input.value = JSON.stringify(selectedItems);
}

/* ─── select / deselect cart item ──────────────────────────────── */
window.selectItem = function (element) {
  const cartKey = getCartKeyFromElement(element);
  const parsed  = parseCartItem(element);

  cart[cartKey] = parsed;

  const emptyMsg     = document.getElementById('empty-msg');
  const summaryDetails = document.getElementById('summary-details');
  if (emptyMsg)      emptyMsg.style.display     = 'none';
  if (summaryDetails) summaryDetails.style.display = 'block';

  updateGrandTotal();
  updateHiddenSelectedItemsInput();
};

/* ─── DOMContentLoaded wiring ──────────────────────────────────── */
document.addEventListener('DOMContentLoaded', () => {
  const cartContainer  = document.querySelector('.cart-items');
  const summaryDetails = document.getElementById('summary-details');
  const emptyMsg       = document.getElementById('empty-msg');
  const checkoutForm   = document.getElementById('confirm-reservation-form');

  if (cartContainer) {
    cartContainer.addEventListener('click', e => {
      // Don't intercept form buttons
      if (e.target.closest('.cart-action-form')) return;

      const item = e.target.closest('.cart-item');
      if (!item) return;

      const cartKey = getCartKeyFromElement(item);

      if (!item.classList.contains('highlighted')) {
        item.classList.add('highlighted');
        selectItem(item);
      } else {
        item.classList.remove('highlighted');
        delete cart[cartKey];

        updateGrandTotal();
        updateHiddenSelectedItemsInput();

        if (Object.keys(cart).length === 0) {
          if (summaryDetails) summaryDetails.style.display = 'none';
          if (emptyMsg)        emptyMsg.style.display       = 'block';
        }
      }
    });
  }

  if (checkoutForm) {
    checkoutForm.addEventListener('submit', function (e) {
      updateHiddenSelectedItemsInput();

      if (Object.keys(cart).length === 0) {
        e.preventDefault();
        window.showToast('Please select at least one item to confirm.');
      }
    });
  }
});
