// 1. Selection and Food List Logic
window.selectItem = function (name, total, id, type, checkIn, checkOut, pax, foodsJson) {
  // Reveal the summary
  document.getElementById('empty-msg').style.display = 'none';
  document.getElementById('summary-details').style.display = 'block';

  // Build the food list
  let foodContainer = document.getElementById('summary-foods');
  foodContainer.innerHTML = '';

  try {
    let foods = JSON.parse(foodsJson);
    if (foods && foods.length > 0) {
      foodContainer.innerHTML = '<p style="font-weight: bold; margin: 10px 0 5px 0; font-size: 0.85em; color: #333;">Selected Foods:</p>';

      foods.forEach(food => {
        let foodSubtotal = food.food_price * pax;
        foodContainer.innerHTML += `
                    <div style="display: flex; justify-content: space-between; font-size: 0.8em; color: #666; margin-bottom: 4px; font-family: 'Arsenal', sans-serif;">
                        <span>• ${food.food_name} (x${pax})</span>
                        <span>₱ ${parseFloat(foodSubtotal).toLocaleString(undefined, { minimumFractionDigits: 2 })}</span>
                    </div>`;
      });
    }
  } catch (e) {
    console.error("Error parsing foods:", e);
  }

  // Update Text Labels
  document.getElementById('summary-name').innerText = name;
  let formattedTotal = '₱ ' + parseFloat(total).toLocaleString(undefined, { minimumFractionDigits: 2 });
  document.getElementById('summary-total').innerText = formattedTotal;
  document.getElementById('summary-grand-total').innerText = formattedTotal;

  // Update Hidden Inputs for the Controller
  document.getElementById('form-id').value = id;
  document.getElementById('form-type').value = type;
  document.getElementById('form-check-in').value = checkIn;
  document.getElementById('form-check-out').value = checkOut;
  document.getElementById('form-pax').value = pax;
  document.getElementById('form-total-amount').value = total;
};

// 2. Highlighting Logic
// Wrapped in DOMContentLoaded to ensure the cart-items container exists
document.addEventListener('DOMContentLoaded', () => {
  const cartContainer = document.querySelector('.cart-items');
  const summaryDetails = document.getElementById('summary-details');
  const emptyMsg = document.getElementById('empty-msg');

  if (cartContainer) {
    // Listen for clicks on the entire container
    cartContainer.addEventListener('click', (e) => {
      const item = e.target.closest('.cart-item');

      // 1. If we clicked an actual card
      if (item) {
        // Remove highlight from all others
        document.querySelectorAll('.cart-item').forEach(el => el.classList.remove('highlighted'));
        // Add highlight to current
        item.classList.add('highlighted');
      }
      // 2. If we clicked the "empty space" inside the container
      else {
        // Remove all highlights
        document.querySelectorAll('.cart-item').forEach(el => el.classList.remove('highlighted'));
        // Hide summary and show "Click an item" message
        if (summaryDetails && emptyMsg) {
          summaryDetails.style.display = 'none';
          emptyMsg.style.display = 'block';
        }
      }
    });
  }
});