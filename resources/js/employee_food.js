document.addEventListener('DOMContentLoaded', () => {

  /* =========================
     FOOD MAIN MODAL
  ========================== */

  const foodOverlay = document.getElementById('foodModalOverlay')
  const foodModal   = document.getElementById('foodModal')
  const foodCloseBtn = document.getElementById('foodModalClose')
  const foodOpenBtn  = document.getElementById('food_button')
  const addFoodBtn   = document.getElementById('add_food_button')

  window.openFoodModal = () => {
    foodOverlay?.classList.add('show')
    foodModal?.classList.add('show')
  }

  window.closeFoodModal = () => {
    foodOverlay?.classList.remove('show')
    foodModal?.classList.remove('show')
  }

  foodOpenBtn?.addEventListener('click', openFoodModal)
  foodCloseBtn?.addEventListener('click', closeFoodModal)

  foodOverlay?.addEventListener('click', (e) => {
    if (e.target === foodOverlay) closeFoodModal()
  })

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      if (foodOverlay?.classList.contains('show')) closeFoodModal()
      if (updateFoodOverlay?.classList.contains('active')) closeUpdateModal()
    }
  })


  /* =========================
     UPDATE FOOD MODAL (admin)
  ========================== */

  const updateFoodOverlay = document.getElementById('updateFoodOverlay')
  const updateFoodModal   = document.getElementById('updateFoodModal')
  const updateFoodCloseBtn = document.getElementById('updateFoodClose')

  function openUpdateModal() {
    updateFoodOverlay?.classList.add('active')
    updateFoodModal?.classList.add('active')
  }

  function closeUpdateModal() {
    updateFoodOverlay?.classList.remove('active')
    updateFoodModal?.classList.remove('active')
  }

  updateFoodCloseBtn?.addEventListener('click', closeUpdateModal)
  updateFoodOverlay?.addEventListener('click', (e) => {
    if (e.target === updateFoodOverlay) closeUpdateModal()
  })


  /* =========================
     CATEGORY TAB FILTERING
  ========================== */

  const tabs     = document.querySelectorAll('.fm-tab')
  const sections = document.querySelectorAll('.fm-section')

  tabs.forEach(tab => {
    tab.addEventListener('click', () => {
      // Update active tab
      tabs.forEach(t => t.classList.remove('active'))
      tab.classList.add('active')

      const cat = tab.dataset.cat

      if (cat === 'all') {
        sections.forEach(s => s.classList.remove('hidden'))
      } else {
        sections.forEach(s => {
          if (s.dataset.section === cat) {
            s.classList.remove('hidden')
          } else {
            s.classList.add('hidden')
          }
        })
      }
    })
  })


  /* =========================
     FOOD ITEM CLICKS (admin only: open update modal)
  ========================== */

  const isAdmin = window._foodIsAdmin === true

  document.querySelectorAll('.fm-item').forEach(item => {
    if (!isAdmin) return  // staff: no click behaviour

    item.addEventListener('click', () => {
      // Populate the update food form
      const updateFoodId       = document.getElementById('updateFoodId')
      const updateFoodName     = document.getElementById('updateFoodName')
      const updateFoodType     = document.getElementById('updateFoodType')
      const updateFoodPrice    = document.getElementById('updateFoodPrice')
      const updateFoodStatus   = document.getElementById('updateFoodStatus')

      const updateFoodForm = document.getElementById('updateFoodForm')

      if (updateFoodId)     updateFoodId.value     = item.dataset.id
      if (updateFoodName)   updateFoodName.value   = item.dataset.name
      if (updateFoodType)   updateFoodType.value   = item.dataset.type
      if (updateFoodPrice)  updateFoodPrice.value  = item.dataset.price
      if (updateFoodStatus) updateFoodStatus.value = item.dataset.status
      if (updateFoodForm)   updateFoodForm.action  = `/employee/room_venue/${item.dataset.id}`

      closeFoodModal()
      openUpdateModal()
    })
  })

})
