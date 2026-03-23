document.addEventListener('DOMContentLoaded', () => {

  const updateFoodForm = document.getElementById('updateFoodForm')
  const updateFoodId = document.getElementById('updateFoodId')
  const updateFoodName = document.getElementById('updateFoodName')
  const updateFoodStatus = document.getElementById('updateFoodStatus')
  const updateFoodPrice = document.getElementById('updateFoodPrice')
  const deleteFoodBtn = document.getElementById('deleteFoodBtn')
  
  document.querySelectorAll('.food-item').forEach(item => {
  
    item.addEventListener('click', () => {
    
    const id = item.dataset.id
    const name = item.dataset.name
    const status = item.dataset.status
    const type = item.dataset.type
    const price = item.dataset.price
    
    updateFoodId.value = id
    updateFoodName.value = name
    updateFoodStatus.value = status
    updateFoodPrice.value = price
  
  const typeSelect = document.getElementById('updateFoodType');
    if (typeSelect) typeSelect.value = type;
    updateFoodForm.action = `/employee/food/${id}`
    showUpdateFoodModal()
    })
  })
  
  // ── Delete confirmation modal wiring ──
  const deleteFoodOverlay  = document.getElementById('deleteFoodOverlay')
  const deleteFoodNameSpan = document.getElementById('deleteFoodName')
  const deleteFoodCancel   = document.getElementById('deleteFoodCancel')
  const deleteFoodConfirm  = document.getElementById('deleteFoodConfirm')

  function openDeleteModal() {
    const name = updateFoodName?.value || 'this item'
    if (deleteFoodNameSpan) deleteFoodNameSpan.textContent = `"${name}"`
    deleteFoodOverlay?.classList.add('active')
  }

  function closeDeleteModal() {
    deleteFoodOverlay?.classList.remove('active')
  }

  // Open modal when Remove Food is clicked
  deleteFoodBtn?.addEventListener('click', () => {
    if (!updateFoodId.value) return
    openDeleteModal()
  })

  // Cancel — just close the modal
  deleteFoodCancel?.addEventListener('click', closeDeleteModal)

  // Close modal on overlay background click
  deleteFoodOverlay?.addEventListener('click', (e) => {
    if (e.target === deleteFoodOverlay) closeDeleteModal()
  })

  // Confirm — build and submit a proper DELETE form
  deleteFoodConfirm?.addEventListener('click', () => {
    const id = updateFoodId.value
    if (!id) return

    const form = document.createElement('form')
    form.method = 'POST'
    form.action = `/employee/food/${id}/delete`

    const csrfInput = document.createElement('input')
    csrfInput.type  = 'hidden'
    csrfInput.name  = '_token'
    csrfInput.value = document.querySelector('meta[name="csrf-token"]').getAttribute('content')

    const methodInput = document.createElement('input')
    methodInput.type  = 'hidden'
    methodInput.name  = '_method'
    methodInput.value = 'DELETE'

    form.appendChild(csrfInput)
    form.appendChild(methodInput)
    document.body.appendChild(form)
    form.submit()
  })
})