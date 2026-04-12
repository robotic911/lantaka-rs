/**
 * Escape a string for safe use as HTML text content inside innerHTML.
 * Prevents XSS when interpolating DB-sourced data into template literals.
 */
function escHtml(str) {
  if (str == null) return '';
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

/**
 * client_food_option.js
 *
 * BOTH spiritual (retreat/recollection) and general events support:
 *   – "Buffet" mode: flat-rate buffet with tier selection (350/pax or 380/pax)
 *   – "Set" mode (spiritual): card-based set selection
 *   – "Packed Meal" mode (general): card-based set selection with additional food search
 *
 * Spiritual Set mode:
 *   Columns: Breakfast | Lunch | Dinner | AM Snacks | PM Snacks
 *   Each set shows a preview line (truncated), expands on click.
 *   Expanded: all foods listed + rice-type selector + meal-specific extras
 *     Breakfast extras: drinks + fruit
 *     Dinner extras:    softdrinks + dessert/fruit
 *
 * General Packed Meal mode:
 *   Multi-select set cards, rice-type dropdown per selected set,
 *   additional-food search bar. (Snacks are not shown in Packed Meal mode.)
 *
 * Buffet mode (both event types):
 *   Tier selector: ₱350/pax (3 viands) or ₱380/pax (4 viands)
 *   Dropdowns for N viands (from Meat Viand, Noodle Viand, Veggie Viand) + 1 Dessert.
 *   No "+ Add" button, no snacks section.
 */

document.addEventListener('DOMContentLoaded', function () {

  const IS_SPIRITUAL = window.IS_SPIRITUAL || false;
  const paxInput     = document.getElementById('paxValue');
  const displayTotal = document.getElementById('displayTotalPrice');

  let foodsData    = {};   // { category: [{ Food_ID, Food_Name, Food_Price }] }
  let foodSetsData = {};   // { meal_time: [{ Food_Set_ID, Food_Set_Name, Food_Set_Price, foods:[] }] }

  // Per-date mode: 'set' | 'individual'
  const cardModes = {};

  // Per-date selection state (used for total calc)
  const selections = {};

  // Persisted customization state for general set cards: { [date_setId]: {...} }
  const customizationState = {};

  // ── Date remapping for pre-fill restore ────────────────────────────────
  // When the user changes dates on a change request, the old session data uses
  // old dates as keys but the new cards use new dates.  We map by day-index:
  // the i-th new date gets the i-th old date's pre-fill data.
  // If dates are identical this is a no-op (oldDate === newDate).
  const _prevDateSources = [
    window.previousFoodSelections || {},
    window.previousSetSelections  || {},
    window.previousMealMode       || {},
  ];
  const _prevDates = [...new Set(_prevDateSources.flatMap(o => Object.keys(o)))].sort();
  const _newDates  = [...document.querySelectorAll('.reservation-card')]
                       .map(c => c.dataset.date).sort();
  // prevDateMap: { newDate -> oldDate }
  const prevDateMap = {};
  _newDates.forEach((nd, i) => { prevDateMap[nd] = _prevDates[i] !== undefined ? _prevDates[i] : nd; });

  /* ═══════════════════════════════════════════════════════════
     1. FETCH
  ═══════════════════════════════════════════════════════════ */
  async function fetchAll() {
    try {
      const [foodsRes, setsRes] = await Promise.all([
        fetch(window.foodAjaxUrl,     { headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' } }),
        fetch(window.foodSetsAjaxUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' } }),
      ]);
      if (!foodsRes.ok || !setsRes.ok) throw new Error('Failed to fetch');

      foodsData    = await foodsRes.json();
      foodSetsData = await setsRes.json();

      initAllCards();
      wireCardToggles();

    } catch (err) {
      console.error('Food fetch error:', err);
      document.querySelectorAll('.fo-columns').forEach(c => {
        c.innerHTML = '<p class="fo-empty fo-error">Failed to load menu. Please refresh.</p>';
        c.classList.remove('fo-columns--loading');
      });
    }
  }

  /* ═══════════════════════════════════════════════════════════
     2. INIT ALL DATE CARDS
  ═══════════════════════════════════════════════════════════ */
  function initAllCards() {
    document.querySelectorAll('.reservation-card').forEach(card => {
      const date = card.dataset.date;

      // ── Determine initial mode from previous edit state (if any) ──
      // Use prevDateMap to translate the new date back to the corresponding old date
      // so pre-fill works even when the user changes dates during a change request.
      const prevMealModeForDate = (window.previousMealMode || {})[prevDateMap[date] || date] || {};
      // Snack slots (am_snack, pm_snack, snacks) are always set-style
      // regardless of the date's set/buffet toggle — exclude them so they
      // don't accidentally flip the whole card into buffet mode.
      const snackKeys = ['am_snack', 'pm_snack', 'snacks'];
      const wasBuffet = Object.entries(prevMealModeForDate)
        .filter(([k]) => !snackKeys.includes(k))
        .some(([, v]) => v === 'buffet');
      cardModes[date]  = wasBuffet ? 'buffet' : 'set';
      selections[date] = {};

      // Wire mode toggle + pre-activate the correct button
      const toggle = card.querySelector('.fo-mode-toggle');
      toggle?.querySelectorAll('.fo-mode-btn').forEach(btn => {
        // Reflect restored mode in the button UI
        btn.classList.toggle('fo-mode-btn--active', btn.dataset.mode === cardModes[date]);

        btn.addEventListener('click', function () {
          if (this.classList.contains('fo-mode-btn--active')) return;
          toggle.querySelectorAll('.fo-mode-btn').forEach(b => b.classList.remove('fo-mode-btn--active'));
          this.classList.add('fo-mode-btn--active');
          cardModes[date]  = this.dataset.mode;
          selections[date] = {};
          renderCardContent(date, card);
          updateTotal();
        });
      });

      renderCardContent(date, card);
    });

    // Restore previous food/set selections (edit-cart flow)
    restorePreviousSelections();
  }

  /* ═══════════════════════════════════════════════════════════
     2b. RESTORE PREVIOUS SELECTIONS  (edit-cart flow)
  ═══════════════════════════════════════════════════════════ */

  /**
   * Called once from initAllCards() after all cards have been rendered.
   * Reads window.previous* globals set by the blade (from session edit_* keys)
   * and programmatically re-applies food_enabled state, set card selections,
   * and individual food selections.
   */
  function restorePreviousSelections() {
    const prevFoodSel  = window.previousFoodSelections || {};
    const prevFoodEn   = window.previousFoodEnabled    || {};
    const prevMealEn   = window.previousMealEnabled    || {};
    const prevSetSel   = window.previousSetSelections  || {};

    const hasAny = Object.keys(prevFoodSel).length > 0 ||
                   Object.keys(prevSetSel).length  > 0 ||
                   Object.keys(prevFoodEn).length  > 0;
    if (!hasAny) return;

    document.querySelectorAll('.reservation-card').forEach(card => {
      const date = card.dataset.date;

      // Resolve the old-date key (handles date changes in change-request flow)
      const oldDate = prevDateMap[date] || date;

      // 1. Restore food_enabled (Yes/No toggle) ──────────────────────
      const foodEnabledVal = prevFoodEn[oldDate];
      if (foodEnabledVal !== undefined && String(foodEnabledVal) === '0') {
        const hiddenInput = card.querySelector('.food-enabled-input');
        const cols        = card.querySelector('.fo-columns');
        if (hiddenInput) hiddenInput.value = '0';
        card.querySelector('[data-toggle="no"]')?.classList.add('active');
        card.querySelector('[data-toggle="yes"]')?.classList.remove('active');
        card.classList.add('food-disabled-card');
        if (cols) { cols.style.opacity = '0.3'; cols.style.pointerEvents = 'none'; }
        return; // nothing more to restore for a disabled date
      }

      const mode        = cardModes[date] || 'set';
      const dateFoodSel = prevFoodSel[oldDate] || {};
      const dateSetSel  = prevSetSel[oldDate]  || {};
      const dateMealEn  = prevMealEn[oldDate]  || {};

      if (mode === 'buffet') {
        restoreBuffetMode(date, card, dateFoodSel);
      } else if (IS_SPIRITUAL) {
        restoreSpiritualSets(date, card, dateSetSel, dateFoodSel);
      } else {
        restoreGeneralSets(date, card, dateSetSel, dateFoodSel);
      }
    });

    updateTotal();
  }

  /** Programmatically set a searchable-select value and update its display span. */
  function ssSetValue(wrap, val) {
    if (!wrap || !val) return;
    const sel = wrap.ssSelect;
    if (!sel) return;
    sel.value = String(val);
    const opt = sel.options[sel.selectedIndex];
    if (!opt || !opt.value) return;
    const display = wrap.querySelector('.ss-display');
    if (display) {
      display.textContent = opt.text;
      display.classList.add('ss-display--has-value');
    }
    wrap.classList.remove('ss-wrap--error');
  }

  /** Restore spiritual set-mode selections for one date. */
  function restoreSpiritualSets(date, card, dateSetSel, dateFoodSel) {
    // Restore breakfast / lunch / dinner set cards
    ['breakfast', 'lunch', 'dinner'].forEach(mealKey => {
      const setId = dateSetSel[mealKey];
      if (!setId) return;

      const col     = card.querySelector(`.fo-meal-col[data-meal="${mealKey}"]`);
      if (!col) return;
      const setCard = col.querySelector(`.fo-set-card[data-set-id="${setId}"]`);
      if (!setCard) return;

      const set = (foodSetsData[mealKey] || []).find(s => String(s.Food_Set_ID) === String(setId));
      if (!set) return;

      selectSpiritualCard(date, mealKey, set, setCard);

      // Restore expanded extras (rice_type, hot_drink, softdrinks, dessert, fruits)
      const mealExtras = dateFoodSel[mealKey] || {};
      const expanded   = setCard.querySelector('.fo-set-expanded');
      if (!expanded) return;

      expanded.querySelectorAll('.fo-extras-row .ss-wrap').forEach(wrap => {
        const sel = wrap.ssSelect;
        if (!sel || !sel.name) return;
        const keyMatch = sel.name.match(/\[([^\]]+)\]$/);
        if (!keyMatch) return;
        ssSetValue(wrap, mealExtras[keyMatch[1]]);
      });
    });

    // Restore single-select snacks (am_snack / pm_snack)
    ['am_snack', 'pm_snack'].forEach(snackKey => {
      const snackId = (dateFoodSel[snackKey] || {}).snacks;
      if (!snackId) return;

      const hiddenSnack = card.querySelector(
        `input[name="food_selections[${date}][${snackKey}][snacks]"]`
      );
      if (!hiddenSnack) return;

      const sub = hiddenSnack.closest('.fo-snack-sub-col');
      if (!sub) return;

      const snackItem = sub.querySelector(`.fo-snack-item[data-food-id="${snackId}"]`);
      if (snackItem && !snackItem.classList.contains('fo-snack-item--selected')) {
        snackItem.click();
      }
    });
  }

  /** Restore general event set-mode selections for one date. */
  function restoreGeneralSets(date, card, dateSetSel, dateFoodSel) {
    // dateSetSel = { mealKey: [setId, ...] }  (arrays because of [] in input names)
    card.querySelectorAll('.fo-general-set-card').forEach(setCard => {
      const setId   = setCard.dataset.setId;
      const mealKey = setCard.dataset.mealKey;

      const prevIds = dateSetSel[mealKey];
      if (!prevIds) return;

      const prevIdsArr = (Array.isArray(prevIds) ? prevIds : [prevIds]).map(String);
      if (!prevIdsArr.includes(String(setId))) return;

      // Find the set object for toggleGeneralSet
      let setData = null;
      for (const sets of Object.values(foodSetsData)) {
        setData = sets.find(s => String(s.Food_Set_ID) === String(setId));
        if (setData) break;
      }
      if (!setData) return;

      if (!setCard.classList.contains('fo-general-set-card--selected')) {
        toggleGeneralSet(date, setData, setCard);
      }

      // Restore inline rice select (food_selections[date][gen_setId][rice])
      const genMealKey = `gen_${setId}`;
      const genFoodSel = dateFoodSel[genMealKey] || {};

      const riceSsWrap = setCard.querySelector(`.fo-rice-inline-ss`);
      if (riceSsWrap) ssSetValue(riceSsWrap, genFoodSel.rice);

      // Restore drink searchable-select (food_selections[date][gen_setId][drink])
      const drinkSsWrap = setCard.querySelector('.fo-drink-ss');
      if (drinkSsWrap) ssSetValue(drinkSsWrap, genFoodSel.drink);

      // Restore dessert hidden input (food_selections[date][gen_setId][dessert])
      const dessertHidden = setCard.querySelector('.fo-gen-dessert-hidden');
      if (dessertHidden && genFoodSel.dessert) {
        dessertHidden.value = String(genFoodSel.dessert);
      }
    });

    // Restore multi-select snacks (food_selections[date][snacks][])
    const snackIds = dateFoodSel['snacks'];
    if (snackIds) {
      const idsArr = (Array.isArray(snackIds)
        ? snackIds
        : Object.values(snackIds)
      ).map(String).filter(Boolean);

      card.querySelectorAll('.fo-snack-item').forEach(snackItem => {
        if (idsArr.includes(String(snackItem.dataset.foodId)) &&
            !snackItem.classList.contains('fo-snack-item--selected')) {
          snackItem.click();
        }
      });
    }
  }

  /** Restore buffet mode selections for one date. */
  function restoreBuffetMode(date, card, dateFoodSel) {
    // Iterate each per-meal section (breakfast / lunch / dinner)
    card.querySelectorAll('.fo-buffet-sel-section').forEach(selSection => {
      const mealKey = selSection.dataset.meal;
      if (!mealKey) return;

      // Per-meal data lives under dateFoodSel[mealKey]; fall back to the
      // legacy 'buffet' key if this session was saved before the per-meal refactor.
      const mealSel = dateFoodSel[mealKey] || dateFoodSel['buffet'] || {};

      // Restore tier hidden input and click the correct tier button
      const tierHidden = selSection.closest('.fo-indiv-meal-card')
                           ?.querySelector('.fo-buffet-meal-tier-hidden');
      if (tierHidden && mealSel._tier) {
        const tier = parseInt(mealSel._tier);
        tierHidden.value = String(tier);
        if (!selections[date]) selections[date] = {};
        if (!selections[date][mealKey]) selections[date][mealKey] = {};
        selections[date][mealKey].buffetTier = tier;

        const tierBtn = selSection.closest('.fo-indiv-meal-card')
                          ?.querySelector(`.fo-buffet-tier-btn[data-tier="${tier}"]`);
        if (tierBtn && !tierBtn.classList.contains('fo-buffet-tier-btn--active')) {
          tierBtn.click();
        }
      }

      // Restore viand / dessert dropdown values
      selSection.querySelectorAll('.ss-wrap').forEach(wrap => {
        const sel = wrap.ssSelect;
        if (!sel || !sel.name) return;
        const keyMatch = sel.name.match(/\[([^\]]+)\]$/);
        if (!keyMatch) return;
        const key = keyMatch[1];
        if (mealSel[key]) ssSetValue(wrap, mealSel[key]);
      });
    });
  }

  /** Restore individual-order mode selections for one date. */
  function restoreIndividualMode(date, card, dateFoodSel, dateMealEn) {
    card.querySelectorAll('.fo-indiv-meal-card').forEach(mealCard => {
      // Identify meal key from the fo-indiv-enabled hidden input name
      const enabledInput = mealCard.querySelector('.fo-indiv-enabled');
      if (!enabledInput) return;
      const mKeyMatch = enabledInput.name.match(/\[([^\]]+)\]$/);
      const mealKey   = mKeyMatch ? mKeyMatch[1] : null;
      if (!mealKey) return;

      const mealFoodSel = dateFoodSel[mealKey] || {};

      // Restore meal-included toggle (checked by default; uncheck if was disabled)
      const mealEnabledVal = dateMealEn[mealKey];
      if (mealEnabledVal !== undefined && String(mealEnabledVal) === '0') {
        const checkbox = mealCard.querySelector('.fo-indiv-toggle-cb');
        if (checkbox && checkbox.checked) {
          checkbox.checked = false;
          checkbox.dispatchEvent(new Event('change'));
        }
      }

      // Restore searchable selects (rice, viand1, viand2, drink, etc.)
      mealCard.querySelectorAll('.ss-wrap').forEach(wrap => {
        const sel = wrap.ssSelect;
        if (!sel || !sel.name) return;
        const keyMatch = sel.name.match(/\[([^\]]+)\]$/);
        if (!keyMatch) return;
        const key = keyMatch[1];
        if (mealFoodSel[key]) ssSetValue(wrap, mealFoodSel[key]);
      });
    });

    // Restore multi-select snacks (shared snack section in individual mode)
    const snackIds = dateFoodSel['snacks'];
    if (snackIds) {
      const idsArr = (Array.isArray(snackIds)
        ? snackIds
        : Object.values(snackIds)
      ).map(String).filter(Boolean);

      card.querySelectorAll('.fo-snack-item').forEach(snackItem => {
        if (idsArr.includes(String(snackItem.dataset.foodId)) &&
            !snackItem.classList.contains('fo-snack-item--selected')) {
          snackItem.click();
        }
      });
    }
  }

  function renderCardContent(date, card) {
    const columnsDiv = card.querySelector('.fo-columns');
    columnsDiv.className = 'fo-columns';          // reset classes

    // Clean up any portal dropdowns that were created inside this card
    columnsDiv.querySelectorAll('.ss-wrap').forEach(w => {
      if (w._ssDropdown) w._ssDropdown.remove();
    });
    columnsDiv.innerHTML = '';

    // Remove any stale Sub Total bar (lives outside columnsDiv as a sibling)
    document.querySelectorAll(`.fo-spiritual-subtotal[data-subtotal-for="${date}"]`)
      .forEach(el => el.remove());

    const mode = cardModes[date] || 'set';

    if (mode === 'buffet') {
      renderBuffetMode(date, columnsDiv);
    } else if (IS_SPIRITUAL) {
      renderSpiritualSet(date, columnsDiv);
    } else {
      renderGeneralSet(date, columnsDiv);
    }
  }

  /* ═══════════════════════════════════════════════════════════
     3. BUFFET MODE  (flat-rate per pax — 350 or 380)
     Replaces the old "Individual Order" mode.
     - 350/pax: 3 viand dropdowns + 1 dessert dropdown
     - 380/pax: 4 viand dropdowns + 1 dessert dropdown
     - No snacks section
     - No "+ Add" buttons
  ═══════════════════════════════════════════════════════════ */
  const BUFFET_MEAL_CARDS = [
    { key: 'breakfast', label: 'Breakfast' },
    { key: 'lunch',     label: 'Lunch' },
    { key: 'dinner',    label: 'Dinner' },
  ];

  function renderBuffetMode(date, container) {
    container.classList.add('fo-buffet-mode');

    // Initialise per-meal selections state
    if (!selections[date]) selections[date] = {};

    const mealGrid = el('div', 'fo-indiv-meal-grid');

    BUFFET_MEAL_CARDS.forEach(({ key: mealKey, label }) => {
      const card = el('div', 'fo-indiv-meal-card');

      // ── Hidden base inputs ──────────────────────────────────
      const enabledHidden = makeHidden(`meal_enabled[${date}][${mealKey}]`, '1');
      enabledHidden.classList.add('fo-indiv-enabled');
      card.appendChild(enabledHidden);
      card.appendChild(makeHidden(`meal_mode[${date}][${mealKey}]`, 'buffet'));

      // ── Card header ──────────────────────────────────────────
      const header   = el('div', 'fo-indiv-card-header');
      const nameSpan = el('span', 'fo-indiv-meal-name');
      nameSpan.textContent = label;

      const toggleLabel = el('label', 'fo-indiv-toggle');
      const checkbox    = el('input');
      checkbox.type    = 'checkbox';
      checkbox.checked = true;
      checkbox.classList.add('fo-indiv-toggle-cb');
      const toggleSpan  = el('span', 'fo-indiv-toggle-text');
      toggleSpan.textContent = 'Include';
      toggleLabel.appendChild(checkbox);
      toggleLabel.appendChild(toggleSpan);

      header.appendChild(nameSpan);
      header.appendChild(toggleLabel);
      card.appendChild(header);

      // ── Per-card tier selector ────────────────────────────────
      const tierWrap = el('div', 'fo-buffet-card-tier');

      // _tier stored inside food_selections so it survives the cart restore flow
      const tierHidden = makeHidden(`food_selections[${date}][${mealKey}][_tier]`, '350');
      tierHidden.classList.add('fo-buffet-meal-tier-hidden');
      tierWrap.appendChild(tierHidden);

      const tier350 = el('button', 'fo-buffet-tier-btn fo-buffet-tier-btn--sm fo-buffet-tier-btn--active');
      tier350.type = 'button';
      tier350.dataset.tier  = '350';
      tier350.dataset.count = '3';
      tier350.innerHTML = '<strong>₱350/pax</strong><span>3 Viands</span>';

      const tier380 = el('button', 'fo-buffet-tier-btn fo-buffet-tier-btn--sm');
      tier380.type = 'button';
      tier380.dataset.tier  = '380';
      tier380.dataset.count = '4';
      tier380.innerHTML = '<strong>₱380/pax</strong><span>4 Viands</span>';

      tierWrap.appendChild(tier350);
      tierWrap.appendChild(tier380);
      card.appendChild(tierWrap);

      // ── Card body (viand + dessert dropdowns) ─────────────────
      const body       = el('div', 'fo-indiv-card-body');
      const selSection = el('div', 'fo-buffet-sel-section');
      selSection.dataset.date = date;
      selSection.dataset.meal = mealKey;
      body.appendChild(selSection);
      card.appendChild(body);

      // Initialise selections for this meal
      selections[date][mealKey] = { buffetTier: 350 };

      // Build initial dropdowns (3 viands for ₱350)
      buildBuffetMealSelections(date, mealKey, selSection, 3);

      // ── Wire tier buttons ──────────────────────────────────
      [tier350, tier380].forEach(btn => {
        btn.addEventListener('click', () => {
          [tier350, tier380].forEach(b => b.classList.remove('fo-buffet-tier-btn--active'));
          btn.classList.add('fo-buffet-tier-btn--active');
          const tier  = parseInt(btn.dataset.tier);
          const count = parseInt(btn.dataset.count);
          tierHidden.value = String(tier);
          if (!selections[date][mealKey]) selections[date][mealKey] = {};
          selections[date][mealKey].buffetTier = tier;
          buildBuffetMealSelections(date, mealKey, selSection, count);
          updateTotal();
        });
      });

      // ── Include / Exclude toggle ────────────────────────────
      checkbox.addEventListener('change', function () {
        enabledHidden.value = this.checked ? '1' : '0';
        body.classList.toggle('fo-indiv-card-body--disabled', !this.checked);
        card.classList.toggle('fo-indiv-meal-card--disabled', !this.checked);
        tierWrap.style.opacity       = this.checked ? '' : '0.35';
        tierWrap.style.pointerEvents = this.checked ? '' : 'none';
        if (this.checked) {
          if (!selections[date][mealKey]) selections[date][mealKey] = {};
          selections[date][mealKey].buffetTier = parseInt(tierHidden.value) || 350;
        } else {
          delete selections[date][mealKey];
        }
        updateTotal();
      });

      mealGrid.appendChild(card);
    });

    container.appendChild(mealGrid);
    updateTotal();
  }

  /**
   * Build (or rebuild) viand + dessert dropdowns inside one meal card's selSection.
   *
   * Layout (viandCount = 3 → ₱350 tier, viandCount = 4 → ₱380 tier):
   *   N × Meat Viand  (separate dropdown each, from meatviand pool)
   *   1 × Noodle Viand (from noodleviand pool)
   *   1 × Veggie Viand (from veggieviand pool)
   *   1 × Dessert      (from desserts + fruits pool)
   */
  function buildBuffetMealSelections(date, mealKey, section, viandCount) {
    // Clean up any portal dropdowns attached to existing ss-wraps
    section.querySelectorAll('.ss-wrap').forEach(w => {
      if (w._ssDropdown) w._ssDropdown.remove();
    });
    section.innerHTML = '';

    const meatPool   = foodsData['meatviand']   || [];
    const noodlePool = foodsData['noodleviand']  || [];
    const veggiePool = foodsData['veggieviand']  || [];
    const dessertPool = [
      ...(foodsData['desserts'] || []),
      ...(foodsData['fruits']   || []),
    ];

    const grid = el('div', 'fo-buffet-grid');

    // N × Meat Viand dropdowns
    for (let i = 1; i <= viandCount; i++) {
      grid.appendChild(buildBuffetRow(
        `Meat Viand ${i}`,
        makeSearchableSelect(
          `food_selections[${date}][${mealKey}][meatviand${i}]`,
          meatPool,
          `Select meat viand ${i}…`,
          'fo-buffet-ss'
        )
      ));
    }

    // 1 × Noodle Viand dropdown
    grid.appendChild(buildBuffetRow(
      'Noodle Viand',
      makeSearchableSelect(
        `food_selections[${date}][${mealKey}][noodleviand]`,
        noodlePool,
        'Select noodle viand…',
        'fo-buffet-ss'
      )
    ));

    // 1 × Veggie Viand dropdown
    grid.appendChild(buildBuffetRow(
      'Veggie Viand',
      makeSearchableSelect(
        `food_selections[${date}][${mealKey}][veggieviand]`,
        veggiePool,
        'Select veggie viand…',
        'fo-buffet-ss'
      )
    ));

    // 1 × Dessert dropdown
    grid.appendChild(buildBuffetRow(
      'Dessert',
      makeSearchableSelect(
        `food_selections[${date}][${mealKey}][dessert]`,
        dessertPool,
        'Select dessert or fruit…',
        'fo-buffet-ss'
      )
    ));

    section.appendChild(grid);
  }

  /** @deprecated Use buildBuffetMealSelections — kept so any stale references don't crash. */
  function buildBuffetSelections(date, section, viandCount) {
    buildBuffetMealSelections(date, 'buffet', section, viandCount);
  }

  function buildBuffetRow(labelText, selectEl) {
    const row = el('div', 'fo-buffet-row');
    const lbl = el('span', 'fo-buffet-row-label');
    lbl.textContent = labelText;
    row.appendChild(lbl);
    row.appendChild(selectEl);
    return row;
  }

  // ── Keep old helpers for restore logic (restoreIndividualMode → restoreBuffetMode) ──
  function makeIndivMealCard(date, mealKey, label) {
    // Legacy stub — not called anymore in buffet mode but kept so restore helpers compile.
    return el('div', 'fo-indiv-meal-card');
  }

  function renderIndividualMode(date, container) {
    // Legacy alias for backward-compatibility with any remaining references.
    renderBuffetMode(date, container);
  }

  /** Row: label on left, single select on right (kept for backward compat) */
  function buildIndivRowLegacy(labelText, selectEl) {
    return buildIndivRow(labelText, selectEl);
  }

  // ── (Kept for restore flow reference) ──
  function makeIndivMealCardFull(date, mealKey, label) {
    const card = el('div', 'fo-indiv-meal-card');

    // Hidden inputs always present
    const enabledHidden = makeHidden(`meal_enabled[${date}][${mealKey}]`, '1');
    enabledHidden.classList.add('fo-indiv-enabled');
    card.appendChild(enabledHidden);
    card.appendChild(makeHidden(`meal_mode[${date}][${mealKey}]`, 'buffet'));

    // ── Card header ──────────────────────────────────────────
    const header = el('div', 'fo-indiv-card-header');
    const nameSpan = el('span', 'fo-indiv-meal-name');
    nameSpan.textContent = label;

    const toggleLabel = el('label', 'fo-indiv-toggle');
    const checkbox    = el('input');
    checkbox.type    = 'checkbox';
    checkbox.checked = true;
    checkbox.classList.add('fo-indiv-toggle-cb');
    const toggleSpan  = el('span', 'fo-indiv-toggle-text');
    toggleSpan.textContent = 'Include';
    toggleLabel.appendChild(checkbox);
    toggleLabel.appendChild(toggleSpan);

    header.appendChild(nameSpan);
    header.appendChild(toggleLabel);
    card.appendChild(header);

    // ── Card body ────────────────────────────────────────────
    const body = el('div', 'fo-indiv-card-body');

    // Pool: Meat Viand + Noodle Viand + Veggie Viand
    const viandOpts = [
      ...(foodsData['meatviand']   || []),
      ...(foodsData['noodleviand'] || []),
      ...(foodsData['veggieviand'] || []),
    ];

    // 1. Rice
    body.appendChild(buildIndivRow('Rice',
      makeIndivSelect(`food_selections[${date}][${mealKey}][rice]`,
        foodsData['rice'] || [], 'Choose rice…')
    ));

    // 2. Viand (1st)
    body.appendChild(buildIndivRow('Viand',
      makeIndivSelect(`food_selections[${date}][${mealKey}][viand1]`,
        viandOpts, 'Choose viand…')
    ));

    // 3. Viand (2nd)
    body.appendChild(buildIndivRow('Viand',
      makeIndivSelect(`food_selections[${date}][${mealKey}][viand2]`,
        viandOpts, 'Choose viand…')
    ));

    // 4. + Extra Viand (add row + chips) — REMOVED per buffet spec (no + Add button)
    const extraChips  = el('div', 'fo-indiv-chips');
    const extraSelWrap = makeIndivSelect(null, viandOpts, 'Add extra viand…');
    const extraBtn    = el('button', 'fo-indiv-add-btn');
    extraBtn.type     = 'button';
    extraBtn.textContent = '+ Add';
    extraBtn.addEventListener('click', () => {
      const nativeSel = extraSelWrap.ssSelect;
      const opt = nativeSel.options[nativeSel.selectedIndex];
      if (!opt || !opt.value || extraChips.querySelector(`[data-cid="${opt.value}"]`)) return;
      extraChips.appendChild(buildIndivChip(
        opt.value, opt.text, opt.dataset.price || 0,
        `food_selections[${date}][${mealKey}][extra_viands][]`, extraChips
      ));
      extraSelWrap.ssReset();
      updateTotal();
    });
    body.appendChild(buildIndivAddRow('Extra Viand', extraSelWrap, extraBtn, extraChips));

    // 5. Drink — searchable select (stores actual Food_ID)
    body.appendChild(buildIndivRow('Drink',
      makeIndivSelect(`food_selections[${date}][${mealKey}][drink]`,
        foodsData['drinks'] || [], 'Choose drink…')
    ));

    // 6. + Dessert (add row + chips)
    const dessertChips   = el('div', 'fo-indiv-chips');
    const dessertSelWrap = makeIndivSelect(null, foodsData['desserts'] || [], 'Add dessert…');
    const dessertBtn     = el('button', 'fo-indiv-add-btn');
    dessertBtn.type      = 'button';
    dessertBtn.textContent = '+ Add';
    dessertBtn.addEventListener('click', () => {
      const nativeSel = dessertSelWrap.ssSelect;
      const opt = nativeSel.options[nativeSel.selectedIndex];
      if (!opt || !opt.value || dessertChips.querySelector(`[data-cid="${opt.value}"]`)) return;
      dessertChips.appendChild(buildIndivChip(
        opt.value, opt.text, opt.dataset.price || 0,
        `food_selections[${date}][${mealKey}][desserts][]`, dessertChips
      ));
      dessertSelWrap.ssReset();
      updateTotal();
    });
    body.appendChild(buildIndivAddRow('Dessert', dessertSelWrap, dessertBtn, dessertChips));

    card.appendChild(body);

    // ── Include toggle wiring ────────────────────────────────
    checkbox.addEventListener('change', function () {
      enabledHidden.value = this.checked ? '1' : '0';
      body.classList.toggle('fo-indiv-card-body--disabled', !this.checked);
      card.classList.toggle('fo-indiv-meal-card--disabled', !this.checked);
      updateTotal();
    });

    return card;
  }

  /** Row: label on left, single select on right */
  function buildIndivRow(labelText, selectEl) {
    const row = el('div', 'fo-indiv-row');
    const lbl = el('span', 'fo-indiv-row-label');
    lbl.textContent = labelText;
    row.appendChild(lbl);
    row.appendChild(selectEl);
    return row;
  }

  /** Row: label on left, add-row (select+btn) + chips stacked on right */
  function buildIndivAddRow(labelText, selectEl, btnEl, chipsEl) {
    const row   = el('div', 'fo-indiv-row fo-indiv-row--add');
    const lbl   = el('span', 'fo-indiv-row-label');
    lbl.textContent = labelText;
    const right = el('div', 'fo-indiv-row-right');
    const addRow = el('div', 'fo-indiv-add-row');
    addRow.appendChild(selectEl);
    addRow.appendChild(btnEl);
    right.appendChild(addRow);
    right.appendChild(chipsEl);
    row.appendChild(lbl);
    row.appendChild(right);
    return row;
  }

  /* ═══════════════════════════════════════════════════════════
     SEARCHABLE SELECT  (custom Select2-style dropdown)
  ═══════════════════════════════════════════════════════════ */

  /**
   * makeSearchableSelect(name, items, placeholder, extraClass, preselect)
   *
   * items:  array of { Food_ID|value, Food_Name|label, Food_Price|price }
   * Returns a .ss-wrap div with:
   *   .ssSelect  — the hidden native <select> (has class food-select for updateTotal)
   *   .ssReset() — clears selection back to placeholder
   * The dropdown is portal-appended to <body> to avoid overflow clipping.
   */
  function makeSearchableSelect(name, items, placeholder, extraClass, preselect) {
    const wrap = el('div', 'ss-wrap');
    if (extraClass) extraClass.split(' ').forEach(c => c && wrap.classList.add(c));

    // ── Hidden native select (form submit + updateTotal reads .food-select) ──
    const sel = el('select', 'food-select ss-hidden-select');
    sel.style.cssText = 'position:absolute;opacity:0;pointer-events:none;width:0;height:0;';
    if (name) sel.name = name;
    // Blank placeholder option
    const blankOpt = makeOpt('', '');
    sel.appendChild(blankOpt);
    items.forEach(f => {
      const id    = f.Food_ID  !== undefined ? f.Food_ID  : f.value;
      const label = f.Food_Name !== undefined ? f.Food_Name : f.label;
      const price = f.Food_Price !== undefined ? f.Food_Price : (f.price || 0);
      const o = makeOpt(id, label);
      o.dataset.price = price;
      sel.appendChild(o);
    });
    if (preselect !== undefined && preselect !== null && preselect !== '') {
      sel.value = preselect;
    }
    wrap.appendChild(sel);

    // ── Trigger button ──
    const trigger = el('div', 'ss-trigger');
    const display = el('span', 'ss-display');
    const arrow   = el('span', 'ss-arrow');
    arrow.innerHTML = '&#9662;';

    // Set initial display text if preselected
    if (preselect && sel.selectedIndex > 0) {
      display.textContent = sel.options[sel.selectedIndex].text;
      display.classList.add('ss-display--has-value');
    } else {
      display.textContent = placeholder || 'Select…';
    }

    trigger.appendChild(display);
    trigger.appendChild(arrow);
    wrap.appendChild(trigger);

    // ── Dropdown (portal: appended to <body>) ──
    const dropdown = el('div', 'ss-dropdown');
    document.body.appendChild(dropdown);
    wrap._ssDropdown = dropdown;   // track for cleanup

    const searchInput = el('input', 'ss-search');
    searchInput.type        = 'text';
    searchInput.placeholder = 'Search…';
    searchInput.autocomplete = 'off';
    const list = el('ul', 'ss-list');
    dropdown.appendChild(searchInput);
    dropdown.appendChild(list);

    // ── Render filtered list ──
    function renderList(q) {
      list.innerHTML = '';
      const query    = (q || '').toLowerCase();
      const filtered = items.filter(f => {
        const n = f.Food_Name || f.label || '';
        return n.toLowerCase().includes(query);
      });
      if (!filtered.length) {
        const noRes = el('li', 'ss-no-results');
        noRes.textContent = 'No results';
        list.appendChild(noRes);
        return;
      }
      filtered.forEach(f => {
        const id    = f.Food_ID  !== undefined ? f.Food_ID  : f.value;
        const fName = f.Food_Name !== undefined ? f.Food_Name : f.label;
        const price = f.Food_Price !== undefined ? f.Food_Price : (f.price || null);
        const item  = el('li', 'ss-option');
        if (String(id) === String(sel.value)) item.classList.add('ss-option--selected');
        const nameEl = el('span', 'ss-opt-name');
        nameEl.textContent = fName;
        // REMOVED FOOD PRICE DROPDOWN
        item.appendChild(nameEl);
        if (price !== null && price !== undefined) {
          const prEl = el('span', 'ss-opt-price');
          // prEl.textContent = '₱' + parseFloat(price).toFixed(2);
          item.appendChild(prEl);
        }
        item.addEventListener('mousedown', e => {
          e.preventDefault();
          sel.value = id;
          display.textContent = fName;
          display.classList.add('ss-display--has-value');
          wrap.classList.remove('ss-wrap--error');  // clear validation error on selection
          closeDropdown();
          sel.dispatchEvent(new Event('change', { bubbles: true }));
        });
        list.appendChild(item);
      });
    }

    // ── Position dropdown below (or above) trigger ──
    function positionDropdown() {
      const rect       = trigger.getBoundingClientRect();
      const scrollY    = window.scrollY || document.documentElement.scrollTop;
      const scrollX    = window.scrollX || document.documentElement.scrollLeft;
      const spaceBelow = window.innerHeight - rect.bottom;
      const spaceAbove = rect.top;
      const maxH       = 240;

      dropdown.style.width = rect.width + 'px';
      dropdown.style.left  = (rect.left + scrollX) + 'px';

      if (spaceBelow >= maxH || spaceBelow >= spaceAbove) {
        dropdown.style.top    = (rect.bottom + scrollY + 2) + 'px';
        dropdown.style.bottom = 'auto';
      } else {
        dropdown.style.top    = 'auto';
        dropdown.style.bottom = (window.innerHeight - rect.top - scrollY + 2) + 'px';
      }
    }

    // ── Open / close ──
    function openDropdown() {
      // Close any other open dropdowns
      document.querySelectorAll('.ss-dropdown--open').forEach(d => d.classList.remove('ss-dropdown--open'));
      document.querySelectorAll('.ss-wrap.ss-open').forEach(w => w.classList.remove('ss-open'));

      renderList('');
      searchInput.value = '';
      positionDropdown();
      wrap.classList.add('ss-open');
      dropdown.classList.add('ss-dropdown--open');
      requestAnimationFrame(() => searchInput.focus());
    }
    function closeDropdown() {
      wrap.classList.remove('ss-open');
      dropdown.classList.remove('ss-dropdown--open');
    }

    // ── Events ──
    trigger.addEventListener('click', e => {
      e.stopPropagation();
      wrap.classList.contains('ss-open') ? closeDropdown() : openDropdown();
    });
    searchInput.addEventListener('input', () => renderList(searchInput.value));
    document.addEventListener('mousedown', e => {
      if (!wrap.contains(e.target) && !dropdown.contains(e.target)) closeDropdown();
    });

    // ── Public API ──
    wrap.ssSelect = sel;
    wrap.ssReset  = function () {
      sel.value = '';
      display.textContent = placeholder || 'Select…';
      display.classList.remove('ss-display--has-value');
      searchInput.value = '';
    };

    return wrap;
  }

  /** Individual order searchable select (delegates to makeSearchableSelect) */
  function makeIndivSelect(name, items, placeholder) {
    return makeSearchableSelect(name, items, placeholder, 'fo-indiv-ss');
  }

  /** Chip for individual order extras (extra viand / dessert) */
  function buildIndivChip(id, label, price, inputName, container) {
    const chip = el('div', 'fo-indiv-chip');
    chip.dataset.cid = id;
    const nameSpan = el('span', 'fo-indiv-chip-label');
    nameSpan.textContent = label;
    // Hidden input contributes to form submit AND total calc
    const hidden = makeHidden(inputName, id);
    hidden.classList.add('fo-indiv-chip-price');
    hidden.dataset.price = price;
    chip.appendChild(nameSpan);
    chip.appendChild(hidden);
    const rm = el('button', 'fo-indiv-chip-rm');
    rm.type = 'button';
    rm.textContent = '×';
    rm.addEventListener('click', () => { chip.remove(); updateTotal(); });
    chip.appendChild(rm);
    return chip;
  }

  /* ═══════════════════════════════════════════════════════════
     4. SPIRITUAL SET MODE
  ═══════════════════════════════════════════════════════════ */
  function renderSpiritualSet(date, container) {
    container.classList.add('fo-spiritual-cols');

    // Meal columns: Breakfast, Lunch, Dinner (scrollable set lists)
    const mealCols = [
      { key: 'breakfast', label: 'Breakfast', extras: 'breakfast' },
      { key: 'lunch',     label: 'Lunch',     extras: 'lunch'     },
      { key: 'dinner',    label: 'Dinner',     extras: 'dinner'    },
    ];

    mealCols.forEach(({ key, label, extras }) => {
      const col = el('div', 'fo-meal-col');
      col.dataset.meal = key;

      const header = el('div', 'fo-meal-col-header');
      header.innerHTML = `<span class="fo-col-title">${label}</span>`;
      col.appendChild(header);

      buildSpiritualSetList(date, key, extras, col);
      container.appendChild(col);
    });

    // 4th column: AM Snacks on top, PM Snacks below (stacked)
    const snackCol = el('div', 'fo-meal-col fo-meal-col--snacks');

    ['am_snack', 'pm_snack'].forEach((key, i) => {
      const label = i === 0 ? 'AM Snacks' : 'PM Snacks';
      const sub   = el('div', 'fo-snack-sub-col');

      const hdr = el('div', 'fo-meal-col-header');
      hdr.innerHTML = `<span class="fo-col-title">${label}</span><span class="fo-col-sub">w/ juice</span>`;
      sub.appendChild(hdr);

      buildSnackSection(date, key, sub, false);
      snackCol.appendChild(sub);
    });

    container.appendChild(snackCol);

    // Sub Total bar below all columns
    const subtotalBar = el('div', 'fo-spiritual-subtotal');
    subtotalBar.dataset.subtotalFor = date;
    subtotalBar.innerHTML =
      'Sub Total &nbsp;<span class="fo-spir-subtotal-amount">₱ 0.00</span>';
    container.after(subtotalBar);
  }

  function buildSpiritualSetList(date, mealKey, extras, col) {
    const sets   = foodSetsData[mealKey] || [];
    const list   = el('div', 'fo-sets-list');
    const hidden = makeHidden(`food_set_selection[${date}][${mealKey}]`, '');
    hidden.classList.add('fo-set-hidden');
    col.appendChild(hidden);

    if (!sets.length) {
      list.appendChild(emptyMsg('No sets available'));
    } else {
      sets.forEach(set => list.appendChild(makeSpiritualCard(date, mealKey, set, extras)));
    }
    col.appendChild(list);
  }

  function makeSpiritualCard(date, mealKey, set, extras) {
    const card = el('div', 'fo-set-card');
    card.dataset.setId = set.Food_Set_ID;

    /* ── Header: name + price (always visible) ── */
    const header = el('div', 'fo-scard-header');
    const nameSpan  = el('span', 'fo-scard-name');
    nameSpan.textContent = set.Food_Set_Name;
    const priceSpan = el('span', 'fo-scard-price');
    priceSpan.textContent = `₱${parseFloat(set.Food_Set_Price).toFixed(2)}/pax`;
    header.appendChild(nameSpan);
    header.appendChild(priceSpan);
    card.appendChild(header);

    /* ── Collapsed: bullet list of included foods ── */
    const bullet = el('ul', 'fo-scard-bullet');
    (set.foods || []).forEach(food => {
      const li = el('li', 'fo-scard-bullet-item');
      li.textContent = food.Food_Name;
      bullet.appendChild(li);
    });
    card.appendChild(bullet);

    /* ── Expanded section (hidden until selected) ── */
    const expanded = el('div', 'fo-set-expanded');
    expanded.style.display = 'none';

    // 1. Rice type select
    expanded.appendChild(buildExtrasRow(
      `food_selections[${date}][${mealKey}][rice_type]`,
      'Rice', foodsData['rice'] || [], 'Choose type…'
    ));

    // 2. Non-rice food items (plain list)
    const nonRice = (set.foods || []).filter(f => (f.Food_Category || '').toLowerCase() !== 'rice');
    if (nonRice.length) {
      const foodItems = el('div', 'fo-scard-food-items');
      nonRice.forEach(food => {
        const item = el('div', 'fo-scard-food-item');
        item.textContent = food.Food_Name;
        foodItems.appendChild(item);
      });
      expanded.appendChild(foodItems);
    }

    // 3. Breakfast extras: hot drink + fruit
    if (extras === 'breakfast') {
      const note = el('p', 'fo-extras-note');
      note.innerHTML = '☕ Serve with rice (plain or fried), drinks (hot coffee, tea or chocolate drink), and a slice of fruit in season.';
      expanded.appendChild(note);
      expanded.appendChild(buildExtrasRow(
        `food_selections[${date}][${mealKey}][hot_drink]`,
        'Drink', foodsData['drinks'] || [], 'Select Drink'
      ));
      expanded.appendChild(buildExtrasRow(
        `food_selections[${date}][${mealKey}][fruits]`,
        'Fruit', foodsData['fruits'] || [], 'Select Fruit'
      ));
    }

    // 4. Lunch / Dinner extras: softdrinks + dessert + fruit
    if (extras === 'lunch' || extras === 'dinner') {
      const note = el('p', 'fo-extras-note');
      note.innerHTML = '🥤 Serve with rice (plain or fried), 1 round softdrinks, and dessert or fruit.';
      expanded.appendChild(note);
      expanded.appendChild(buildExtrasRow(
        `food_selections[${date}][${mealKey}][softdrinks]`,
        'Drink', foodsData['drinks'] || [], 'Select Drink'
      ));
      expanded.appendChild(buildExtrasRow(
        `food_selections[${date}][${mealKey}][dessert]`,
        'Dessert', foodsData['desserts'] || [], 'Select Dessert'
      ));
      expanded.appendChild(buildExtrasRow(
        `food_selections[${date}][${mealKey}][fruits]`,
        'Fruit', foodsData['fruits'] || [], 'Select Fruit'
      ));
    }

    // 5. Deselect button
    const changeBtn = el('button', 'fo-change-btn');
    changeBtn.type        = 'button';
    changeBtn.textContent = '✕ Change';
    changeBtn.addEventListener('click', e => {
      e.stopPropagation();
      deselectSpiritualCard(date, mealKey, card);
    });
    expanded.appendChild(changeBtn);

    // Cards start collapsed/unselected — disable all selects so they don't
    // submit empty values that would overwrite a selected card's choices.
    expanded.querySelectorAll('select').forEach(s => { s.disabled = true; });

    card.appendChild(expanded);

    card.addEventListener('click', () => {
      if (!card.classList.contains('fo-set-card--selected')) {
        selectSpiritualCard(date, mealKey, set, card);
      }
    });

    return card;
  }

  function buildExtrasRow(name, labelText, items, placeholder, date, mealKey) {
    const row   = el('div', 'fo-extras-row');
    const label = el('label', 'fo-extras-label');
    label.textContent = labelText;
    const ssWrap = makeSearchableSelect(name, items, placeholder, 'fo-extras-ss');
    // Keep a visible name on the hidden select for form submission
    row.appendChild(label);
    row.appendChild(ssWrap);
    return row;
  }

  function selectSpiritualCard(date, mealKey, set, card) {
    const col = card.closest('.fo-meal-col');
    col?.querySelectorAll('.fo-set-card--selected').forEach(c => collapseSpiritualCard(c));
    card.classList.add('fo-set-card--selected');
    const expanded = card.querySelector('.fo-set-expanded');
    expanded.style.display = '';
    // Enable all selects so this card's choices are submitted with the form
    expanded.querySelectorAll('select').forEach(s => { s.disabled = false; });
    const bullet = card.querySelector('.fo-scard-bullet');
    if (bullet) bullet.style.display = 'none';
    const hidden = col?.querySelector('.fo-set-hidden');
    if (hidden) hidden.value = set.Food_Set_ID;
    if (!selections[date]) selections[date] = {};
    selections[date][mealKey] = { price: set.Food_Set_Price, surcharge: 0 };
    updateTotal();
  }

  function deselectSpiritualCard(date, mealKey, card) {
    collapseSpiritualCard(card);
    const hidden = card.closest('.fo-meal-col')?.querySelector('.fo-set-hidden');
    if (hidden) hidden.value = '';
    if (selections[date]) delete selections[date][mealKey];
    updateTotal();
  }

  function collapseSpiritualCard(card) {
    card.classList.remove('fo-set-card--selected');
    const expanded = card.querySelector('.fo-set-expanded');
    expanded.style.display = 'none';
    // Disable selects so empty values from unselected cards don't overwrite
    // the selected card's rice/drink choices when the form submits.
    expanded.querySelectorAll('select').forEach(s => { s.disabled = true; });
    const bullet = card.querySelector('.fo-scard-bullet');
    if (bullet) bullet.style.display = '';
  }

  /* ═══════════════════════════════════════════════════════════
     5. GENERAL EVENT SET MODE
  ═══════════════════════════════════════════════════════════ */
  function renderGeneralSet(date, container) {
    container.classList.add('fo-general-layout');

    /* ── Set cards ── */
    const setsSection = el('div', 'fo-section');
    const setsTitle   = el('div', 'fo-section-title');
    setsTitle.innerHTML = '<span>Meal Sets</span><small>Multi-select — pick one or more</small>';
    setsSection.appendChild(setsTitle);

    const seen = new Set();
    const allSets = [];
    Object.entries(foodSetsData).forEach(([mk, sets]) =>
      sets.forEach(s => { if (!seen.has(s.Food_Set_ID)) { seen.add(s.Food_Set_ID); allSets.push({ ...s, _mealKey: mk }); } })
    );

    const setsRow = el('div', 'fo-general-sets-row');
    if (!allSets.length) {
      setsRow.appendChild(emptyMsg('No sets available for this event type.'));
    } else {
      allSets.forEach(set => setsRow.appendChild(makeGeneralSetCard(date, set)));
    }
    setsSection.appendChild(setsRow);
    container.appendChild(setsSection);

    // /* ── Additional food search ── */
    // const addlSection = el('div', 'fo-section');
    // const addlTitle   = el('div', 'fo-section-title');
    // addlTitle.innerHTML = '<span>Additional Food</span><small>Search and add extra items</small>';
    // addlSection.appendChild(addlTitle);
    // buildAdditionalSearch(date, addlSection);
    // container.appendChild(addlSection);

    /* ── Snacks ── */
    const snacks = foodsData['snacks'] || [];
    if (snacks.length) {
      const snackSection = el('div', 'fo-section');
      const snackTitle   = el('div', 'fo-section-title');
      snackTitle.innerHTML = '<span>Snacks</span><small>w/ juice — multi-select</small>';
      snackSection.appendChild(snackTitle);
      buildSnackSection(date, 'snacks', snackSection, true);
      container.appendChild(snackSection);
    }
  }

  function makeGeneralSetCard(date, set) {
    const card = el('div', 'fo-general-set-card');
    card.dataset.setId   = set.Food_Set_ID;
    card.dataset.mealKey = set._mealKey;

    // Header (always visible)
    const header = el('div', 'fo-general-set-header');
    header.innerHTML =
      `<span class="fo-gen-set-name">${escHtml(set.Food_Set_Name)}</span>` +
      `<span class="fo-gen-set-price">₱${parseFloat(set.Food_Set_Price).toFixed(2)}/pax</span>`;
    card.appendChild(header);

    // Body: numbered food list + drink choice (always visible)
    const body = el('div', 'fo-gen-card-body');
    body.appendChild(buildSetFoodList(date, `gen_${set.Food_Set_ID}`, set, true));
    body.appendChild(buildDrinkChoice(date, `gen_${set.Food_Set_ID}`));
    card.appendChild(body);

    // Customize button — shown only when selected
    const custArea = el('div', 'fo-gen-cust-area');
    custArea.style.display = 'none';
    const custBtn = el('button', 'fo-customize-btn');
    custBtn.type = 'button';
    custBtn.textContent = 'Customize';
    custBtn.addEventListener('click', e => {
      e.stopPropagation();
      openCustomizeModal(date, set, card);
    });
    custArea.appendChild(custBtn);
    card.appendChild(custArea);

    // Hidden selection input (submitted with form)
    const hidden = makeHidden(`food_set_selection[${date}][${set._mealKey}][]`, '');
    hidden.classList.add('fo-gen-set-hidden');
    hidden.dataset.setId = set.Food_Set_ID;
    card.appendChild(hidden);

    // Hidden input for the dessert chosen via the Customize modal.
    // Stored under food_selections so it reaches the controller via the
    // selected_items JSON (food_upgrades is not serialised into that JSON).
    const dessertHidden = makeHidden(
      `food_selections[${date}][gen_${set.Food_Set_ID}][dessert]`, ''
    );
    dessertHidden.classList.add('fo-gen-dessert-hidden');
    card.appendChild(dessertHidden);

    card.addEventListener('click', e => {
      if (['SELECT','OPTION','LABEL','BUTTON','INPUT'].includes(e.target.tagName)) return;
      toggleGeneralSet(date, set, card);
    });

    return card;
  }

  function toggleGeneralSet(date, set, card) {
    const isOn    = card.classList.contains('fo-general-set-card--selected');
    const custArea = card.querySelector('.fo-gen-cust-area');
    if (isOn) {
      card.classList.remove('fo-general-set-card--selected');
      if (custArea) custArea.style.display = 'none';
      card.querySelector('.fo-gen-set-hidden').value = '';
      if (selections[date]?.sets)
        selections[date].sets = selections[date].sets.filter(s => s.setId !== set.Food_Set_ID);
    } else {
      card.classList.add('fo-general-set-card--selected');
      if (custArea) custArea.style.display = 'flex';
      card.querySelector('.fo-gen-set-hidden').value = set.Food_Set_ID;
      if (!selections[date]) selections[date] = {};
      if (!selections[date].sets) selections[date].sets = [];
      // Re-use saved surcharge if this set was previously customized
      const stateKey  = `${date}_${set.Food_Set_ID}`;
      const savedSurcharge = customizationState[stateKey]?.surcharge || 0;
      selections[date].sets.push({ setId: set.Food_Set_ID, price: set.Food_Set_Price, surcharge: savedSurcharge });
    }
    updateTotal();
  }

  /* ── Additional food search ── */
  function buildAdditionalSearch(date, section) {
    const wrap    = el('div', 'fo-search-wrap');
    const input   = el('input', 'fo-search-input');
    input.type        = 'text';
    input.placeholder = 'Search food items to add…';
    input.autocomplete = 'off';

    const results = el('div', 'fo-search-results');
    results.style.display = 'none';

    input.addEventListener('input', () => {
      const q = input.value.trim().toLowerCase();
      results.innerHTML = '';
      if (!q) { results.style.display = 'none'; return; }

      const allFoods = Object.values(foodsData).flat();
      const matches  = allFoods.filter(f => f.Food_Name.toLowerCase().includes(q)).slice(0, 8);
      if (!matches.length) { results.style.display = 'none'; return; }

      matches.forEach(food => {
        const row = el('div', 'fo-search-option');
        row.innerHTML =
          `<span class="fo-sopt-name">${escHtml(food.Food_Name)}</span>` +
          `<span class="fo-sopt-cat">${escHtml(food.Food_Category)}</span>` +
          `<span class="fo-sopt-price">₱${parseFloat(food.Food_Price).toFixed(2)}</span>`;
        row.addEventListener('mousedown', e => {
          e.preventDefault();
          addAdditionalFood(date, food, section);
          input.value = '';
          results.style.display = 'none';
        });
        results.appendChild(row);
      });
      results.style.display = 'block';
    });

    input.addEventListener('blur', () => setTimeout(() => { results.style.display = 'none'; }, 150));

    wrap.appendChild(input);
    wrap.appendChild(results);
    section.appendChild(wrap);

    const chipsWrap = el('div', 'fo-additional-chips');
    chipsWrap.dataset.date = date;
    section.appendChild(chipsWrap);
  }

  function addAdditionalFood(date, food, section) {
    if (!selections[date]) selections[date] = {};
    if (!selections[date].additional) selections[date].additional = [];
    if (selections[date].additional.find(f => String(f.foodId) === String(food.Food_ID))) return;

    selections[date].additional.push({ foodId: food.Food_ID, price: food.Food_Price });

    const hidden = makeHidden(`food_additional[${date}][]`, food.Food_ID);
    hidden.dataset.foodId = food.Food_ID;
    hidden.classList.add('fo-additional-hidden');
    section.appendChild(hidden);

    const chipsWrap = section.querySelector('.fo-additional-chips');
    const chip = el('div', 'fo-additional-chip');
    chip.innerHTML =
      `<span class="fo-chip-name">${escHtml(food.Food_Name)}</span>` +
      `<span class="fo-chip-cat">${escHtml(food.Food_Category)}</span>` +
      `<span class="fo-chip-price">₱${parseFloat(food.Food_Price).toFixed(2)}</span>` +
      `<button type="button" class="fo-chip-remove" title="Remove">×</button>`;
    chip.querySelector('.fo-chip-remove').addEventListener('click', () => {
      chip.remove();
      section.querySelector(`.fo-additional-hidden[data-food-id="${food.Food_ID}"]`)?.remove();
      selections[date].additional = selections[date].additional.filter(f => String(f.foodId) !== String(food.Food_ID));
      updateTotal();
    });
    chipsWrap.appendChild(chip);
    updateTotal();
  }

  /* ── Snack section (shared, multi/single toggle) ── */
  function buildSnackSection(date, mealKey, parent, multiSelect) {
    const grid = el('div', multiSelect ? 'fo-snacks-grid fo-snacks-multi' : 'fo-snacks-grid');
    const items = foodsData['snacks'] || [];

    if (!items.length) { grid.appendChild(emptyMsg('No snacks available')); }
    else { items.forEach(snack => grid.appendChild(makeSnackItem(date, mealKey, snack, multiSelect))); }

    parent.appendChild(grid);

    if (!multiSelect) {
      // Single-select: one hidden input updated on click
      const hidden = makeHidden(`food_selections[${date}][${mealKey}][snacks]`, '');
      hidden.classList.add('fo-snack-hidden');
      parent.appendChild(hidden);
    }
  }

  function makeSnackItem(date, mealKey, snack, multiSelect) {
    const item = el('div', 'fo-snack-item');
    item.textContent    = snack.Food_Name;
    item.dataset.foodId = snack.Food_ID;
    item.dataset.price  = snack.Food_Price;

    item.addEventListener('click', () => {
      const isOn = item.classList.contains('fo-snack-item--selected');

      if (!multiSelect) {
        item.closest('.fo-snacks-grid')?.querySelectorAll('.fo-snack-item--selected')
          .forEach(s => s.classList.remove('fo-snack-item--selected'));
      }

      if (!isOn) {
        item.classList.add('fo-snack-item--selected');
        if (multiSelect) {
          if (!selections[date]) selections[date] = {};
          if (!selections[date].snacks) selections[date].snacks = [];
          if (!selections[date].snacks.find(s => String(s.foodId) === String(snack.Food_ID))) {
            selections[date].snacks.push({ foodId: snack.Food_ID, price: snack.Food_Price });
            const h = makeHidden(`food_selections[${date}][${mealKey}][]`, snack.Food_ID);
            h.classList.add('fo-snack-multi-hidden');
            h.dataset.foodId = snack.Food_ID;
            item.appendChild(h);
          }
        } else {
          if (!selections[date]) selections[date] = {};
          selections[date][mealKey] = { price: snack.Food_Price };
          const hidden = (item.closest('.fo-snack-sub-col') || item.closest('.fo-meal-col, .fo-section'))?.querySelector('.fo-snack-hidden');
          if (hidden) hidden.value = snack.Food_ID;
        }
      } else {
        item.classList.remove('fo-snack-item--selected');
        if (multiSelect) {
          item.querySelector('.fo-snack-multi-hidden')?.remove();
          if (selections[date]?.snacks)
            selections[date].snacks = selections[date].snacks.filter(s => String(s.foodId) !== String(snack.Food_ID));
        } else {
          const hidden = (item.closest('.fo-snack-sub-col') || item.closest('.fo-meal-col, .fo-section'))?.querySelector('.fo-snack-hidden');
          if (hidden) hidden.value = '';
          if (selections[date]) delete selections[date][mealKey];
        }
      }
      updateTotal();
    });

    return item;
  }

  /* ═══════════════════════════════════════════════════════════
     6. SET CARD HELPERS
  ═══════════════════════════════════════════════════════════ */

  const CAT_LABELS = {
    rice: 'Rice',
    meatviand: 'Meat Viand', noodleviand: 'Noodle Viand', veggieviand: 'Veggie Viand',
    // Legacy keys kept for backward compatibility with old data
    viand: 'Viand', sidedish: 'Side Dish',
    drinks: 'Drinks', desserts: 'Dessert', fruits: 'Fruit', snacks: 'Snacks',
  };

  /** Numbered <ol> of foods in a set; rice item gets an inline type-selector.
   *  showDrinkPlaceholder: show "Softdrinks or Juice — Your choice ↓" as last row (general events only) */
  function buildSetFoodList(date, mealKey, set, showDrinkPlaceholder = false) {
    const wrap = el('div', 'fo-food-list-wrap');
    const ol   = el('ol', 'fo-food-list');

    // Group: rice first, then others
    const riceItems  = (set.foods || []).filter(f => (f.Food_Category || '').toLowerCase() === 'rice');
    const otherItems = (set.foods || []).filter(f => (f.Food_Category || '').toLowerCase() !== 'rice');

    // Rice row (with inline searchable type selector)
    const riceLi   = el('li', 'fo-food-list-item fo-list-rice-row');
    const riceName = el('span', 'fo-list-name');
    riceName.textContent = riceItems.length ? riceItems[0].Food_Name : 'Rice';
    const riceSelWrap = makeSearchableSelect(
      `food_selections[${date}][${mealKey}][rice]`,
      foodsData['rice'] || [], 'Choose type…', 'fo-rice-inline-ss'
    );
    riceLi.appendChild(riceName);
    riceLi.appendChild(riceSelWrap);
    ol.appendChild(riceLi);

    // Other foods
    otherItems.forEach(food => {
      const cat = (food.Food_Category || '').toLowerCase();
      const li  = el('li', 'fo-food-list-item');
      // Store food identity so applyCustomizationToCard can update switched names
      li.dataset.foodId       = food.Food_ID;
      li.dataset.originalName = food.Food_Name;
      li.dataset.originalCat  = cat;
      li.innerHTML =
        `<span class="fo-list-name">${escHtml(food.Food_Name)}</span>` +
        `<span class="fo-list-cat">${escHtml(CAT_LABELS[cat] || cat)}</span>`;
      ol.appendChild(li);
    });

    
    // Softdrinks or Juice placeholder row (general events only)
    if (showDrinkPlaceholder) {
      const drinkLi = el('li', 'fo-food-list-item fo-list-drink-row');
      drinkLi.innerHTML = '<span class="fo-list-name">Softdrinks or Juice</span><span class="fo-list-cat">Your choice ↓</span>';
      ol.appendChild(drinkLi);
    }
    wrap.appendChild(ol);
    
    return wrap;
  }

  /** Drink searchable-select for general set cards (stores actual Food_ID) */
  function buildDrinkChoice(date, mealKey) {
    const wrap = el('div', 'fo-drink-choice');
    const lbl  = el('span', 'fo-drink-label');
    lbl.textContent = 'Drink:';
    wrap.appendChild(lbl);

    const ssWrap = makeSearchableSelect(
      `food_selections[${date}][${mealKey}][drink]`,
      foodsData['drinks'] || [],
      'Choose drink…',
      'fo-drink-ss'
    );
    wrap.appendChild(ssWrap);
    return wrap;
  }

  /** Upgrade rows: switch viand, add extra viand, add dessert */
  function buildUpgradesSection(date, mealKey, card) {
    const section = el('div', 'fo-upgrades-section');

    const title = el('p', 'fo-upgrades-title');
    title.textContent = 'Optional Upgrades';
    section.appendChild(title);

    // Switch viand +₱20
    const _upgradeViandPool = [
      ...(foodsData['meatviand']   || []),
      ...(foodsData['noodleviand'] || []),
      ...(foodsData['veggieviand'] || []),
      ...(foodsData['viand']       || []),   // legacy compat
    ];
    const switchRow = el('div', 'fo-upgrade-row');
    switchRow.innerHTML =
      '<span class="fo-upgrade-label">Switch viand ' +
      '<span class="fo-upgrade-price">+₱20</span></span>';
    const switchSelWrap = makeSearchableSelect(
      `food_upgrades[${date}][${mealKey}][viand_switch]`,
      _upgradeViandPool, 'Keep original viand', 'fo-viand-switch-ss'
    );
    switchSelWrap.ssSelect.classList.add('fo-viand-switch');
    switchSelWrap.ssSelect.addEventListener('change', () => recalcSurcharge(date, mealKey, card));
    switchRow.appendChild(switchSelWrap);
    section.appendChild(switchRow);

    // Additional viand +₱40 each
    const extraRow = el('div', 'fo-upgrade-row');
    extraRow.innerHTML =
      '<span class="fo-upgrade-label">Add extra viand ' +
      '<span class="fo-upgrade-price">+₱40 each</span></span>';
    const addRowWrap    = el('div', 'fo-add-viand-row');
    const extraSelWrap2 = makeSearchableSelect(
      null, _upgradeViandPool, 'Choose viand…', 'fo-extra-viand-ss'
    );
    const addBtn = el('button', 'fo-add-btn');
    addBtn.type = 'button';
    addBtn.textContent = '+ Add';
    const extraList = el('div', 'fo-extra-viand-list');

    addBtn.addEventListener('click', e => {
      e.stopPropagation();
      const nativeSel = extraSelWrap2.ssSelect;
      const opt = nativeSel.options[nativeSel.selectedIndex];
      if (!opt || !opt.value) return;
      if (extraList.querySelector(`[data-viand-id="${opt.value}"]`)) return;
      const item = el('div', 'fo-extra-viand-item');
      item.dataset.viandId = opt.value;
      const itemName = el('span', 'fo-extra-viand-name');
      itemName.textContent = opt.text;
      const rmBtn = el('button', 'fo-chip-remove');
      rmBtn.type = 'button';
      rmBtn.textContent = '×';
      const hidden = makeHidden(`food_upgrades[${date}][${mealKey}][extra_viands][]`, opt.value);
      rmBtn.addEventListener('click', ev => {
        ev.stopPropagation();
        item.remove();
        recalcSurcharge(date, mealKey, card);
      });
      item.appendChild(itemName);
      item.appendChild(rmBtn);
      item.appendChild(hidden);
      extraList.appendChild(item);
      extraSelWrap2.ssReset();
      recalcSurcharge(date, mealKey, card);
    });

    addRowWrap.appendChild(extraSelWrap2);
    addRowWrap.appendChild(addBtn);
    extraRow.appendChild(addRowWrap);
    extraRow.appendChild(extraList);
    section.appendChild(extraRow);

    // Dessert +₱20
    const dessertRow = el('div', 'fo-upgrade-row');
    dessertRow.innerHTML =
      '<span class="fo-upgrade-label">Add dessert ' +
      '<span class="fo-upgrade-price">+₱20</span></span>';
    const dessertSelWrap2 = makeSearchableSelect(
      `food_upgrades[${date}][${mealKey}][dessert]`,
      foodsData['desserts'] || [], 'No dessert', 'fo-dessert-ss'
    );
    dessertSelWrap2.ssSelect.classList.add('fo-dessert-select');
    dessertSelWrap2.ssSelect.addEventListener('change', () => recalcSurcharge(date, mealKey, card));
    dessertRow.appendChild(dessertSelWrap2);
    section.appendChild(dessertRow);

    return section;
  }

  /** Recalculate and store surcharge for a spiritual set card */
  function recalcSurcharge(date, mealKey, card) {
    let extra = 0;
    if (card.querySelector('.fo-viand-switch')?.value) extra += 20;
    extra += card.querySelectorAll('.fo-extra-viand-item').length * 40;
    if (card.querySelector('.fo-dessert-select')?.value) extra += 20;

    if (!selections[date]) selections[date] = {};
    // Spiritual path
    if (selections[date][mealKey]) {
      selections[date][mealKey].surcharge = extra;
    }
    // General path (keyed by gen_<setId> style mealKey)
    if (selections[date]?.sets) {
      const setId = card.dataset.setId;
      const entry = selections[date].sets.find(s => String(s.setId) === String(setId));
      if (entry) entry.surcharge = extra;
    }
    updateTotal();
  }

  /* ═══════════════════════════════════════════════════════════
     7. CUSTOMIZE MODAL  (general events)
  ═══════════════════════════════════════════════════════════ */

  function openCustomizeModal(date, set, card) {
    const stateKey  = `${date}_${set.Food_Set_ID}`;
    const saved     = customizationState[stateKey] || {};
    const basePrice = parseFloat(set.Food_Set_Price);

    // ── Build overlay ──────────────────────────────────────
    const overlay = el('div', 'fo-cust-overlay');
    document.body.appendChild(overlay);

    const modal = el('div', 'fo-cust-modal');

    // Live price display
    let tempSurcharge = saved.surcharge || 0;
    const priceEl = el('span', 'fo-cust-modal-price');
    const refreshPrice = () => {
      priceEl.textContent = `₱ ${(basePrice + tempSurcharge).toFixed(2)} /pax`;
    };
    refreshPrice();

    // ── Header ─────────────────────────────────────────────
    const mHead = el('div', 'fo-cust-modal-header');
    const mTitle = el('span', 'fo-cust-modal-title');
    mTitle.textContent = set.Food_Set_Name;
    mHead.appendChild(mTitle);
    mHead.appendChild(priceEl);
    modal.appendChild(mHead);

    // ── Body (3 columns) ───────────────────────────────────
    const mBody = el('div', 'fo-cust-modal-body');

    /* ── Left: Rice · Switch Food · Drinks ── */
    const leftCol = el('div', 'fo-cust-col');

    // Rice
    leftCol.appendChild(buildCustSelectRow('Rice', null,
      makeSelectEl(`food_upgrades[${date}][${set.Food_Set_ID}][rice]`,
        (foodsData['rice'] || []).map(r => ({ value: r.Food_ID, label: r.Food_Name })),
        'Choose type…', saved.rice || ''
      )
    ));

    // Switch Food (+₱20 per changed viand)
    const switchables = (set.foods || []).filter(f => {
      const cat = (f.Food_Category || '').toLowerCase();
      return cat === 'meatviand' || cat === 'noodleviand' || cat === 'veggieviand'
          || cat === 'viand'     || cat === 'sidedish'; // legacy compat
    });
    if (switchables.length) {
      const switchGroup = el('div', 'fo-cust-field-group');
      const switchHead  = el('p', 'fo-cust-field-label');
      switchHead.innerHTML = 'Switch Food <span class="fo-cust-price-tag">+₱20 each</span>';
      switchGroup.appendChild(switchHead);
      // Pool all viand categories as replacement options
      const switchOpts = [
        ...(foodsData['meatviand']   || []).map(v => ({ value: v.Food_ID, label: v.Food_Name })),
        ...(foodsData['noodleviand'] || []).map(v => ({ value: v.Food_ID, label: v.Food_Name })),
        ...(foodsData['veggieviand'] || []).map(v => ({ value: v.Food_ID, label: v.Food_Name })),
        ...(foodsData['viand']       || []).map(v => ({ value: v.Food_ID, label: v.Food_Name })),
        ...(foodsData['sidedish']    || []).map(v => ({ value: v.Food_ID, label: v.Food_Name })),
      ];
      switchables.forEach(food => {
        const selWrap = makeSelectEl(
          `food_upgrades[${date}][${set.Food_Set_ID}][switch][${food.Food_ID}]`,
          switchOpts, food.Food_Name,
          (saved.switches && saved.switches[food.Food_ID]) || food.Food_ID
        );
        // Apply classes/data to the hidden native select so querySelectorAll finds them
        selWrap.ssSelect.classList.add('fo-switch-sel');
        selWrap.ssSelect.dataset.originalId = food.Food_ID;
        selWrap.ssSelect.addEventListener('change', recalcModal);
        switchGroup.appendChild(selWrap);
      });
      leftCol.appendChild(switchGroup);
    }

    // Drinks
    leftCol.appendChild(buildCustSelectRow('Drinks', null,
      makeSelectEl(`food_upgrades[${date}][${set.Food_Set_ID}][drinks]`,
        [{ value: 'softdrinks', label: 'Softdrinks' }, { value: 'juice', label: 'Juice' }],
        null, saved.drinks || 'softdrinks'
      )
    ));

    mBody.appendChild(leftCol);

    /* ── Middle: Add extra viand (+₱40) ── */
    const midCol    = el('div', 'fo-cust-col');
    const extraChips = el('div', 'fo-cust-chips');
    // Restore saved extra viands
    (saved.extraViands || []).forEach(v =>
      extraChips.appendChild(buildCustChip(v.id, v.label, extraChips, recalcModal))
    );

    const _allViands = [
      ...(foodsData['meatviand']   || []),
      ...(foodsData['noodleviand'] || []),
      ...(foodsData['veggieviand'] || []),
      ...(foodsData['viand']       || []),   // legacy compat
    ];
    const extraSelWrap = makeSelectEl(null,
      _allViands.map(v => ({ value: v.Food_ID, label: v.Food_Name })),
      'Choose viand'
    );
    const extraAddBtn = el('button', 'fo-cust-add-btn');
    extraAddBtn.type = 'button';
    extraAddBtn.textContent = '+ Add';
    extraAddBtn.addEventListener('click', () => {
      const nativeSel = extraSelWrap.ssSelect;
      const opt = nativeSel.options[nativeSel.selectedIndex];
      if (!opt || !opt.value || extraChips.querySelector(`[data-cid="${opt.value}"]`)) return;
      extraChips.appendChild(buildCustChip(opt.value, opt.text, extraChips, recalcModal));
      extraSelWrap.ssReset();
      recalcModal();
    });

    const extraHead = el('p', 'fo-cust-field-label');
    extraHead.innerHTML = 'Add extra viand <span class="fo-cust-price-tag">+₱40 each</span>';
    const extraAddRow = el('div', 'fo-cust-add-row');
    extraAddRow.appendChild(extraSelWrap);
    extraAddRow.appendChild(extraAddBtn);
    midCol.appendChild(extraHead);
    midCol.appendChild(extraAddRow);
    midCol.appendChild(extraChips);
    mBody.appendChild(midCol);

    /* ── Right: Add dessert (+₱20) ── */
    const rightCol    = el('div', 'fo-cust-col');
    const dessertChips = el('div', 'fo-cust-chips');
    // Restore saved desserts
    (saved.desserts || []).forEach(d =>
      dessertChips.appendChild(buildCustChip(d.id, d.label, dessertChips, recalcModal))
    );

    const dessertSelWrap = makeSelectEl(null,
      (foodsData['desserts'] || []).map(d => ({ value: d.Food_ID, label: d.Food_Name })),
      'No dessert'
    );
    const dessertAddBtn = el('button', 'fo-cust-add-btn');
    dessertAddBtn.type = 'button';
    dessertAddBtn.textContent = '+ Add';
    dessertAddBtn.addEventListener('click', () => {
      const nativeSel = dessertSelWrap.ssSelect;
      const opt = nativeSel.options[nativeSel.selectedIndex];
      if (!opt || !opt.value || dessertChips.querySelector(`[data-cid="${opt.value}"]`)) return;
      dessertChips.appendChild(buildCustChip(opt.value, opt.text, dessertChips, recalcModal));
      dessertSelWrap.ssReset();
      recalcModal();
    });

    const dessertHead = el('p', 'fo-cust-field-label');
    dessertHead.innerHTML = 'Add dessert <span class="fo-cust-price-tag">+₱20 each</span>';
    const dessertAddRow = el('div', 'fo-cust-add-row');
    dessertAddRow.appendChild(dessertSelWrap);
    dessertAddRow.appendChild(dessertAddBtn);
    rightCol.appendChild(dessertHead);
    rightCol.appendChild(dessertAddRow);
    rightCol.appendChild(dessertChips);
    mBody.appendChild(rightCol);

    modal.appendChild(mBody);

    // Recalc live price
    function recalcModal() {
      let extra = 0;
      modal.querySelectorAll('.fo-switch-sel').forEach(sel => {
        if (String(sel.value) !== String(sel.dataset.originalId)) extra += 20;
      });
      extra += extraChips.querySelectorAll('.fo-cust-chip').length * 40;
      extra += dessertChips.querySelectorAll('.fo-cust-chip').length * 20;
      tempSurcharge = extra;
      refreshPrice();
    }
    recalcModal();

    // ── Footer ─────────────────────────────────────────────
    const mFoot = el('div', 'fo-cust-modal-footer');

    const cancelBtn = el('button', 'fo-cust-cancel-btn');
    cancelBtn.type = 'button';
    cancelBtn.textContent = 'Cancel';
    cancelBtn.addEventListener('click', () => { overlay.remove(); });

    const saveBtn = el('button', 'fo-cust-save-btn');
    saveBtn.type = 'button';
    saveBtn.textContent = 'Save Changes';
    saveBtn.addEventListener('click', () => {
      // Persist state for re-open
      const switches = {};
      modal.querySelectorAll('.fo-switch-sel').forEach(sel => {
        switches[sel.dataset.originalId] = sel.value;
      });
      customizationState[stateKey] = {
        surcharge:   tempSurcharge,
        rice:        modal.querySelector(`[name="food_upgrades[${date}][${set.Food_Set_ID}][rice]"]`)?.value || '',
        switches,
        drinks:      modal.querySelector(`[name="food_upgrades[${date}][${set.Food_Set_ID}][drinks]"]`)?.value || 'softdrinks',
        extraViands: [...extraChips.querySelectorAll('.fo-cust-chip')].map(c => ({ id: c.dataset.cid, label: c.dataset.clabel })),
        desserts:    [...dessertChips.querySelectorAll('.fo-cust-chip')].map(c => ({ id: c.dataset.cid, label: c.dataset.clabel })),
      };

      // Copy all modal selects to hidden inputs on the card (for form submission)
      card.querySelectorAll('.fo-cust-persisted').forEach(i => i.remove());
      modal.querySelectorAll('select[name]').forEach(sel => {
        if (!sel.value) return;
        const h = makeHidden(sel.name, sel.value);
        h.classList.add('fo-cust-persisted');
        card.appendChild(h);
      });
      // Extra viands and desserts as hidden arrays
      customizationState[stateKey].extraViands.forEach(v => {
        const h = makeHidden(`food_upgrades[${date}][${set.Food_Set_ID}][extra_viands][]`, v.id);
        h.classList.add('fo-cust-persisted');
        card.appendChild(h);
      });
      customizationState[stateKey].desserts.forEach(d => {
        const h = makeHidden(`food_upgrades[${date}][${set.Food_Set_ID}][desserts][]`, d.id);
        h.classList.add('fo-cust-persisted');
        card.appendChild(h);
      });

      // Update surcharge in selections
      if (selections[date]?.sets) {
        const entry = selections[date].sets.find(s => String(s.setId) === String(set.Food_Set_ID));
        if (entry) entry.surcharge = tempSurcharge;
      }
      updateTotal();
      // Reflect all saved changes visually on the card
      applyCustomizationToCard(date, set, card, customizationState[stateKey]);
      overlay.remove();
    });

    mFoot.appendChild(cancelBtn);
    mFoot.appendChild(saveBtn);
    modal.appendChild(mFoot);

    overlay.appendChild(modal);

    // Close on backdrop click
    overlay.addEventListener('click', e => { if (e.target === overlay) overlay.remove(); });
  }

  /**
   * After saving customizations, sync the card's visible food list to reflect:
   * rice type, switched viands/side dishes, drink choice, extra viands, desserts.
   */
  function applyCustomizationToCard(date, set, card, state) {
    const body = card.querySelector('.fo-gen-card-body');
    if (!body) return;
    const ol = body.querySelector('.fo-food-list');
    if (!ol) return;

    // 1. Rice select — update the inline searchable-select wrapper so the
    //    hidden native <select> (food_selections[date][gen_setId][rice]) reflects
    //    the customize-modal choice when the form submits.
    const riceSsWrap = card.querySelector('.fo-rice-inline-ss');
    if (riceSsWrap && state.rice) ssSetValue(riceSsWrap, state.rice);

    // 2. Switched viands / side dishes — update name + category badge
    const allSwappable = [
      ...(foodsData['viand']    || []),
      ...(foodsData['sidedish'] || []),
    ];
    ol.querySelectorAll('.fo-food-list-item[data-food-id]').forEach(li => {
      const origId = li.dataset.foodId;
      const newId  = state.switches && state.switches[origId];
      const nameSpan = li.querySelector('.fo-list-name');
      const catSpan  = li.querySelector('.fo-list-cat');
      if (newId && String(newId) !== String(origId)) {
        const newFood = allSwappable.find(f => String(f.Food_ID) === String(newId));
        if (newFood && nameSpan) {
          nameSpan.textContent = newFood.Food_Name;
          li.classList.add('fo-food-item--switched');
          if (catSpan) {
            const cat = (newFood.Food_Category || '').toLowerCase();
            catSpan.textContent = CAT_LABELS[cat] || cat;
          }
        }
      } else {
        // Restore original (in case user removed a switch on re-save)
        if (nameSpan) nameSpan.textContent = li.dataset.originalName;
        li.classList.remove('fo-food-item--switched');
        if (catSpan) {
          const cat = li.dataset.originalCat || '';
          catSpan.textContent = CAT_LABELS[cat] || cat;
        }
      }
    });

    // 3. Drink radio — check the saved choice
    const drinkChoice = body.querySelector('.fo-drink-choice');
    if (drinkChoice && state.drinks) {
      const radio = drinkChoice.querySelector(`input[value="${state.drinks}"]`);
      if (radio) radio.checked = true;
    }

    // 4. Remove previously added extra items before re-adding
    ol.querySelectorAll('.fo-cust-added-item').forEach(li => li.remove());

    // 5. Extra viands
    (state.extraViands || []).forEach(v => {
      const li = el('li', 'fo-food-list-item fo-cust-added-item');
      li.innerHTML =
        `<span class="fo-list-name">${escHtml(v.label)}</span>` +
        `<span class="fo-list-cat fo-list-cat--added">+₱40</span>`;
      ol.appendChild(li);
    });

    // 6. Desserts
    (state.desserts || []).forEach(d => {
      const li = el('li', 'fo-food-list-item fo-cust-added-item');
      li.innerHTML =
        `<span class="fo-list-name">${escHtml(d.label)}</span>` +
        `<span class="fo-list-cat fo-list-cat--added">+₱20</span>`;
      ol.appendChild(li);
    });

    // 7. Update price/pax badge on the card header
    const priceSpan = card.querySelector('.fo-gen-set-price');
    if (priceSpan) {
      const basePrice = parseFloat(set.Food_Set_Price);
      const total     = basePrice + (state.surcharge || 0);
      priceSpan.textContent = `₱${total.toFixed(2)}/pax`;
    }

    // 8. Sync dessert hidden input so the first chosen dessert reaches the
    //    controller via food_selections[date][gen_setId][dessert].
    const dessertHidden = card.querySelector('.fo-gen-dessert-hidden');
    if (dessertHidden) {
      const firstDessert = (state.desserts || [])[0];
      dessertHidden.value = firstDessert ? String(firstDessert.id) : '';
    }
  }

  /** Chip element used inside the customize modal */
  function buildCustChip(id, label, container, onChange) {
    const chip = el('div', 'fo-cust-chip');
    chip.dataset.cid    = id;
    chip.dataset.clabel = label;
    const nameSpan = el('span');
    nameSpan.textContent = label;
    const rm = el('button', 'fo-cust-chip-rm');
    rm.type = 'button';
    rm.textContent = '×';
    rm.addEventListener('click', e => { e.stopPropagation(); chip.remove(); onChange(); });
    chip.appendChild(nameSpan);
    chip.appendChild(rm);
    return chip;
  }

  /** Helper: build a labelled field group with a single select */
  function buildCustSelectRow(labelText, priceBadge, selectEl) {
    const group = el('div', 'fo-cust-field-group');
    const lbl   = el('p', 'fo-cust-field-label');
    lbl.textContent = labelText;
    if (priceBadge) {
      const badge = el('span', 'fo-cust-price-tag');
      badge.textContent = priceBadge;
      lbl.appendChild(badge);
    }
    group.appendChild(lbl);
    group.appendChild(selectEl);
    return group;
  }

  /** Helper: create a searchable select from an options array (customize modal) */
  function makeSelectEl(name, options, placeholder, preselect) {
    return makeSearchableSelect(name, options, placeholder || 'Select…', 'fo-cust-ss', preselect);
  }

  /* ═══════════════════════════════════════════════════════════
     8. TOTAL
  ═══════════════════════════════════════════════════════════ */
  function updateTotal() {
    let subtotal = 0;
    const pax    = parseInt(paxInput?.value || 1);

    document.querySelectorAll('.reservation-card').forEach(card => {
      const date = card.dataset.date;
      if (card.querySelector('.food-enabled-input')?.value !== '1') return;

      const mode = cardModes[date] || 'set';
      const sel  = selections[date] || {};
      let dateSubtotal = 0;

      if (mode === 'buffet') {
        // Sum enabled meal-card tiers (each card is 350 or 380 per pax independently)
        BUFFET_MEAL_CARDS.forEach(({ key: mealKey }) => {
          // Check if this meal card's "Include" toggle is enabled
          const mealCard = card.querySelector(`.fo-indiv-meal-card .fo-indiv-enabled[name="meal_enabled[${date}][${mealKey}]"]`);
          if (mealCard && mealCard.value !== '1') return; // meal excluded
          const mealSel = sel[mealKey];
          if (mealSel && mealSel.buffetTier) {
            dateSubtotal += mealSel.buffetTier;
          }
        });
      } else if (IS_SPIRITUAL) {
        Object.values(sel).forEach(v => {
          if (v?.price) dateSubtotal += parseFloat(v.price) + parseFloat(v.surcharge || 0);
        });
        // Update inline Sub Total bar for this reservation date
        const subtotalBar = document.querySelector(`.fo-spiritual-subtotal[data-subtotal-for="${date}"]`);
        if (subtotalBar) {
          const pax = parseInt(paxInput?.value || 1);
          const amtEl = subtotalBar.querySelector('.fo-spir-subtotal-amount');
          if (amtEl) amtEl.textContent = '₱ ' + (dateSubtotal * pax).toLocaleString(undefined, {
            minimumFractionDigits: 2, maximumFractionDigits: 2,
          });
        }
      } else {
        (sel.sets || []).forEach(s => dateSubtotal += parseFloat(s.price || 0) + parseFloat(s.surcharge || 0));
        (sel.additional || []).forEach(f => dateSubtotal += parseFloat(f.price || 0));
        (sel.snacks || []).forEach(s => dateSubtotal += parseFloat(s.price || 0));
      }

      subtotal += dateSubtotal;
    });

    if (displayTotal) {
      displayTotal.textContent = '₱ ' + (subtotal * pax).toLocaleString(undefined, {
        minimumFractionDigits: 2, maximumFractionDigits: 2,
      });
    }
  }

  /* ═══════════════════════════════════════════════════════════
     7. INCLUDE FOOD TOGGLE  (Yes / No)
  ═══════════════════════════════════════════════════════════ */
  function wireCardToggles() {
    document.querySelectorAll('.reservation-card').forEach(card => {
      const hidden = card.querySelector('.food-enabled-input');
      const cols   = card.querySelector('.fo-columns');

      card.querySelector('[data-toggle="yes"]')?.addEventListener('click', () => {
        hidden.value = '1';
        card.querySelector('[data-toggle="yes"]').classList.add('active');
        card.querySelector('[data-toggle="no"]').classList.remove('active');
        card.classList.remove('food-disabled-card');
        cols.style.opacity = '1';
        cols.style.pointerEvents = '';
        updateTotal();
      });

      card.querySelector('[data-toggle="no"]')?.addEventListener('click', () => {
        hidden.value = '0';
        card.querySelector('[data-toggle="no"]').classList.add('active');
        card.querySelector('[data-toggle="yes"]').classList.remove('active');
        card.classList.add('food-disabled-card');
        cols.style.opacity = '0.3';
        cols.style.pointerEvents = 'none';
        updateTotal();
      });
    });
  }

  /* ═══════════════════════════════════════════════════════════
     UTILS
  ═══════════════════════════════════════════════════════════ */
  function el(tag, cls) {
    const n = document.createElement(tag);
    if (cls) n.className = cls;
    return n;
  }
  function makeHidden(name, value) {
    const i = document.createElement('input');
    i.type = 'hidden'; i.name = name; i.value = value;
    return i;
  }
  function makeOpt(val, text) {
    const o = document.createElement('option');
    o.value = val; o.textContent = text;
    return o;
  }
  function th(text) {
    const t = document.createElement('th');
    t.textContent = text;
    return t;
  }
  function emptyMsg(text) {
    const p = el('p', 'fo-empty');
    p.textContent = text;
    return p;
  }

  /* ═══════════════════════════════════════════════════════════
     9. FORM SUBMIT VALIDATION
  ═══════════════════════════════════════════════════════════ */
  document.getElementById('foodReservationForm')?.addEventListener('submit', function (e) {
    const invalidWraps = [];
    let errorMsg       = '';

    document.querySelectorAll('.reservation-card').forEach(card => {
      const date = card.dataset.date;
      if (card.querySelector('.food-enabled-input')?.value !== '1') return;

      const mode = cardModes[date] || 'set';

      if (mode === 'buffet') {
        // Validate all per-meal buffet sections (breakfast / lunch / dinner)
        card.querySelectorAll('.fo-buffet-sel-section').forEach(selSection => {
          const mealKey  = selSection.dataset.meal;
          if (!mealKey) return;

          // Check the meal-enabled toggle — skip if meal is excluded
          const mealCard = selSection.closest('.fo-indiv-meal-card');
          if (!mealCard) return;
          const enabledHidden = mealCard.querySelector('.fo-indiv-enabled');
          if (enabledHidden && enabledHidden.value !== '1') return;

          // Determine tier for this meal card
          const tierHidden = mealCard.querySelector('.fo-buffet-meal-tier-hidden');
          const tierVal    = parseInt(tierHidden?.value || '350');
          const meatCount  = tierVal === 380 ? 4 : 3;

          // Validate N meat viand dropdowns
          for (let i = 1; i <= meatCount; i++) {
            const nativeSel = selSection.querySelector(`select[name="food_selections[${date}][${mealKey}][meatviand${i}]"]`);
            if (nativeSel && !nativeSel.value) {
              const wrap = nativeSel.closest('.ss-wrap');
              if (wrap && !wrap.classList.contains('ss-wrap--error')) {
                wrap.classList.add('ss-wrap--error');
                invalidWraps.push(wrap);
              }
              if (!errorMsg) errorMsg = `Please select Meat Viand ${i} for ${mealKey} buffet on ${date}.`;
            }
          }

          // Validate noodle viand
          const noodleSel = selSection.querySelector(`select[name="food_selections[${date}][${mealKey}][noodleviand]"]`);
          if (noodleSel && !noodleSel.value) {
            const wrap = noodleSel.closest('.ss-wrap');
            if (wrap && !wrap.classList.contains('ss-wrap--error')) {
              wrap.classList.add('ss-wrap--error');
              invalidWraps.push(wrap);
            }
            if (!errorMsg) errorMsg = `Please select a Noodle Viand for ${mealKey} buffet on ${date}.`;
          }

          // Validate veggie viand
          const veggieSel = selSection.querySelector(`select[name="food_selections[${date}][${mealKey}][veggieviand]"]`);
          if (veggieSel && !veggieSel.value) {
            const wrap = veggieSel.closest('.ss-wrap');
            if (wrap && !wrap.classList.contains('ss-wrap--error')) {
              wrap.classList.add('ss-wrap--error');
              invalidWraps.push(wrap);
            }
            if (!errorMsg) errorMsg = `Please select a Veggie Viand for ${mealKey} buffet on ${date}.`;
          }

          // Validate dessert
          const dessertSel = selSection.querySelector(`select[name="food_selections[${date}][${mealKey}][dessert]"]`);
          if (dessertSel && !dessertSel.value) {
            const wrap = dessertSel.closest('.ss-wrap');
            if (wrap && !wrap.classList.contains('ss-wrap--error')) {
              wrap.classList.add('ss-wrap--error');
              invalidWraps.push(wrap);
            }
            if (!errorMsg) errorMsg = `Please select a Dessert for ${mealKey} buffet on ${date}.`;
          }
        });

      } else if (IS_SPIRITUAL) {
        // At least one set must be chosen per available meal column
        ['breakfast', 'lunch', 'dinner'].forEach(key => {
          if (!foodSetsData[key] || !foodSetsData[key].length) return;
          const sel = selections[date] || {};
          if (!sel[key] || !sel[key].price) {
            const col = card.querySelector(`.fo-meal-col[data-meal="${key}"]`);
            if (col) {
              col.classList.add('fo-col--error');
              invalidWraps.push(col);
            }
            if (!errorMsg) {
              errorMsg = `Please select a ${key} food set for ${date}.`;
            }
          } else {
            // Set IS selected — validate expanded extras (rice, drink, fruit)
            const selectedCard = card.querySelector(`.fo-meal-col[data-meal="${key}"] .fo-set-card--selected`);
            if (selectedCard) {
              selectedCard.querySelectorAll('.fo-set-expanded .ss-wrap').forEach(wrap => {
                const hiddenSel = wrap.querySelector('select');
                if (hiddenSel && !hiddenSel.value) {
                  if (!wrap.classList.contains('ss-wrap--error')) {
                    wrap.classList.add('ss-wrap--error');
                    invalidWraps.push(wrap);
                  }
                  if (!errorMsg) {
                    const fieldLabel = wrap.closest('.fo-extras-row')
                      ?.querySelector('.fo-extras-label')?.textContent?.trim() || 'a required field';
                    errorMsg = `Please select ${fieldLabel.toLowerCase()} for the ${key} set on ${date}.`;
                  }
                }
              });
            }
          }
        });

      } else {
        // General set mode: at least one set selected
        const sel = selections[date] || {};
        if (!sel.sets || sel.sets.length === 0) {
          // Highlight all set-list columns
          card.querySelectorAll('.fo-meal-col').forEach(col => {
            col.classList.add('fo-col--error');
            invalidWraps.push(col);
          });
          if (!errorMsg) errorMsg = `Please select at least one food set for ${date}.`;
        } else {
          // Sets ARE selected — validate drink choice on each selected card
          card.querySelectorAll('.fo-general-set-card--selected').forEach(setCard => {
            const drinkSsWrap = setCard.querySelector('.fo-drink-ss');
            if (drinkSsWrap) {
              const drinkVal = drinkSsWrap.ssSelect?.value;
              if (!drinkVal) {
                const drinkChoiceDiv = setCard.querySelector('.fo-drink-choice');
                if (drinkChoiceDiv && !drinkChoiceDiv.classList.contains('fo-row--error')) {
                  drinkChoiceDiv.classList.add('fo-row--error');
                }
                drinkSsWrap.classList.add('ss-wrap--error');
                invalidWraps.push(drinkSsWrap);
                if (!errorMsg) {
                  const setName = setCard.querySelector('.fo-gen-set-name')?.textContent?.trim()
                    || 'a selected set';
                  errorMsg = `Please choose a drink for "${setName}" (${date}).`;
                }
              }
            }
          });
        }
      }
    });

    if (invalidWraps.length) {
      e.preventDefault();
      window.showToast(errorMsg || 'Please complete all required food selections.');
      // Scroll first invalid element into view
      invalidWraps[0]?.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
  });

  // Clear column error highlight when a set is selected
  document.addEventListener('click', function (e) {
    const col = e.target.closest('.fo-meal-col');
    if (col) col.classList.remove('fo-col--error');
    // Clear drink/dessert row error when user interacts with those rows (individual mode)
    const row = e.target.closest('.fo-indiv-row');
    if (row) row.classList.remove('fo-row--error');
    // Clear drink-choice error on general set cards
    const drinkChoice = e.target.closest('.fo-drink-choice');
    if (drinkChoice) drinkChoice.classList.remove('fo-row--error');
  });

  fetchAll();
});
