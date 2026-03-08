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