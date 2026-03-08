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
  
  document.querySelectorAll('input[name="type"]').forEach(radio=>{
    radio.checked = radio.value === type
    })
    updateFoodForm.action = `/employee/room_venue/${id}`
    showUpdateFoodModal()
    })
  })
  
  deleteFoodBtn?.addEventListener('click', () => {
  
    const id = updateFoodId.value
    
    if(!id) return
    window.location.href = `/employee/room_venue/${id}/delete`
    
    })
  })