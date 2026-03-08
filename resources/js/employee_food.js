document.addEventListener('DOMContentLoaded', () => {

  /* =========================
     FOOD MAIN MODAL
  ========================== */

  const foodOverlay = document.getElementById('foodModalOverlay')
  const foodModal = document.getElementById('foodModal')
  const foodCloseBtn = document.getElementById('foodModalClose')
  const foodOpenBtn = document.getElementById('food_button')
  const addFoodBtn = document.getElementById('add_food_button')

  const updateFoodOverlay = document.getElementById('updateFoodOverlay')
  const updateFoodModal = document.getElementById('updateFoodModal')
  const updateFoodCloseBtn = document.getElementById('updateFoodClose')

  const openFoodModal = () => {
    foodOverlay?.classList.add('show')
    foodModal?.classList.add('show')
  }

  const closeFoodModal = () => {
    foodOverlay?.classList.remove('show')
    foodModal?.classList.remove('show')
  }

  foodOpenBtn?.addEventListener('click', openFoodModal)
  foodCloseBtn?.addEventListener('click', closeFoodModal)
  addFoodBtn?.addEventListener('click', closeFoodModal)

  foodOverlay?.addEventListener('click', (e) => {
    if (e.target === foodOverlay) closeFoodModal()
  })


  /* =========================
     UPDATE FOOD MODAL
  ========================== */

  function openUpdateModal() {
    updateFoodOverlay?.classList.add('active')
    updateFoodModal?.classList.add('active')
  }

  function closeUpdateModal() {
    updateFoodOverlay?.classList.remove('active')
    updateFoodModal?.classList.remove('active')
  }

  function showUpdateFoodModal() {
    closeFoodModal()
    openUpdateModal()
  }

  updateFoodCloseBtn?.addEventListener('click', closeUpdateModal)

  updateFoodOverlay?.addEventListener('click', (e) => {
    if (e.target === updateFoodOverlay) {
      closeUpdateModal()
    }
  })

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      if (dateOverlay?.classList.contains('show')) closeDateModal()
      if (updateFoodOverlay?.classList.contains('active')) closeUpdateModal()
    }
  })


  /* =========================
     FOOD DATE MODAL
  ========================== */

  const dateOpenBtn = document.querySelector('.date-select-btn')
  const dateOverlay = document.getElementById('foodDateOverlay')
  const dateCloseBtn = document.getElementById('foodDateClose')
  const confirmBtn = document.getElementById('foodDateConfirm')
  const dateList = document.getElementById('foodDateList')
  const errorEl = document.getElementById('foodDateError')
  const hiddenInput = document.getElementById('foodServiceDate')

  let selectedDate = ''

  const openDateModal = () => {
    dateOverlay?.classList.add('show')
    errorEl && (errorEl.style.display = 'none')
  }

  const closeDateModal = () => {
    dateOverlay?.classList.remove('show')
  }

  dateOpenBtn?.addEventListener('click', openDateModal)
  dateCloseBtn?.addEventListener('click', closeDateModal)

  dateOverlay?.addEventListener('click', (e) => {
    if (e.target === dateOverlay) closeDateModal()
  })

  dateList?.addEventListener('click', (e) => {
    const chip = e.target.closest('.fooddate-chip')
    if (!chip) return

    dateList.querySelectorAll('.fooddate-chip').forEach(c => {
      c.classList.remove('selected')
    })

    chip.classList.add('selected')
    selectedDate = chip.dataset.date || ''

    if (errorEl) errorEl.style.display = 'none'
    console.log('Picked food date:', selectedDate)
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
    if (dateOpenBtn) dateOpenBtn.textContent = `Food Date: ${selectedDate}`

    console.log('CONFIRMED food date:', selectedDate)
    closeDateModal()
  })


  /* =========================
     FOOD SELECTION LOGIC
  ========================== */

  const masterWrap = document.querySelector('.food-toggle-section .toggle-buttons')
  const masterBtns = document.querySelectorAll('.food-toggle-section .toggle-btn')
  const mealSections = document.querySelectorAll('.meal-section')

  const setActiveBtn = (btn, wrap) => {
    wrap?.querySelectorAll('.toggle-btn').forEach(b => {
      b.classList.remove('active')
    })
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
      item.classList.toggle('unavailable', !enabled)
      if (!enabled) item.classList.remove('selected')
    })
  }

  function setAllMeals(enabled) {
    mealSections.forEach(meal => setMealEnabled(meal, enabled))
  }

  if (masterWrap && masterBtns.length) {
    const yesBtn = [...masterBtns].find(b =>
      b.textContent.trim().toLowerCase() === 'yes'
    )
    if (yesBtn) setActiveBtn(yesBtn, masterWrap)
  }

  setAllMeals(true)

  document.querySelectorAll('.food-item').forEach(item => {
    item.classList.remove('selected')
    item.classList.remove('unavailable')

    item.addEventListener('click', () => {
      showUpdateFoodModal()
    })
  })

  mealSections.forEach(meal => {
    const pill = meal.querySelector('.toggle-status')
    if (!pill) return

    pill.addEventListener('click', () => {
      if (!isMasterYes()) return

      const enabled = meal.dataset.enabled === '1'
      setMealEnabled(meal, !enabled)
    })
  })

  masterBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      setActiveBtn(btn, masterWrap)
      const yes = btn.textContent.trim().toLowerCase() === 'yes'
      setAllMeals(yes)
    })
  })

})