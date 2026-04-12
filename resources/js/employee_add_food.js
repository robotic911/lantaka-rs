// resources/js/employee_add_food.js
document.addEventListener('DOMContentLoaded', () => {

  const addFoodModal = document.getElementById('addFoodModal')
  const addFoodOverlay = document.getElementById('addFoodOverlay')
  const addFoodClose = document.getElementById('addFoodClose')

  // opener (from your food menu header)
  const addFoodBtn = document.getElementById('add_food_button')

  function showAddFoodModal() {
    addFoodModal.classList.add('active')
    addFoodOverlay.classList.add('active')
  }

  function hideAddFoodModal() {
    addFoodModal.classList.remove('active')
    addFoodOverlay.classList.remove('active')
  }

  addFoodBtn?.addEventListener('click', () => {
    window.closeFoodModal();
    showAddFoodModal()

  })

  addFoodClose?.addEventListener('click', hideAddFoodModal)

  // close when clicking overlay only (not modal content)
  addFoodOverlay?.addEventListener('click', (e) => {
    if (e.target === addFoodOverlay) hideAddFoodModal()
  })

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && addFoodModal?.classList.contains('active')) {
      hideAddFoodModal()
    }
  })

})
