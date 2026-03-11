document.addEventListener('DOMContentLoaded', () => {
  const expandButtons = document.querySelectorAll('.expand-button');
  const modalOverlay = document.querySelector('.modal-overlay');
  const closeBtn = document.querySelector('.close-btn');

  expandButtons.forEach(button => {
    button.addEventListener('click', function () {
      const data = JSON.parse(this.getAttribute('data-info'));

      // --- 1. Fill basic text details ---
      document.getElementById('modalAccommodation').textContent = data.accommodation;
      document.getElementById('modalPax').textContent = data.pax;
      document.getElementById('modalCheckIn').textContent = data.check_in;
      document.getElementById('modalCheckOut').textContent = data.check_out;
      document.getElementById('modalFoodIdLabel').textContent = `Food ID (${data.id}):`;

      const cancelBtn = document.querySelector('.detail-section-cancel');
      if (data.status !== "pending"){
        cancelBtn.style.display = 'none';
      }else if(data.status === "pending"){
        cancelBtn.style.display = 'flex';
      }
      ;

      // NEW: Store the ID in the hidden input for Cancel/Edit functions
      const idInput = document.getElementById('cancelReservationId');
      if (idInput) {
        idInput.value = data.real_id;
        // SAVE THE TYPE HERE
        idInput.setAttribute('data-type', data.type);
        console.log("Captured ID:", idInput.value, "Type:", data.type);
      }

      // --- 2. Handle the food logic (PRESERVED) ---
      const foodListContainer = document.getElementById('modalFoodList');
      let foodHtml = '';

      if (data.foods && data.foods.length > 0) {
        let groupedFoods = {};
        data.foods.forEach(food => {
          let cat = food.food_category || food.category || 'Other';
          if (!groupedFoods[cat]) groupedFoods[cat] = [];
          groupedFoods[cat].push(food);
        });

        for (const [category, items] of Object.entries(groupedFoods)) {
          foodHtml += `
            <div class="food-category">
                <p class="food-category-title">${category.toUpperCase()}</p>
                <ul class="food-items">
          `;
          items.forEach(food => {
            const foodName = food.food_name || food.name || 'Unknown Food';
            foodHtml += `<li>${foodName}</li>`;
          });
          foodHtml += `</ul></div>`;
        }
      } else {
        foodHtml = '<p class="detail-value" style="margin-top: 5px;">No food reserved.</p>';
      }

      foodListContainer.innerHTML = foodHtml;
      modalOverlay.style.display = 'flex';
    });
  });

  // --- NEW: CANCELLATION LOGIC ---
  window.confirmCancellation = async function () {
    // 1. Get the hidden input element
    const inputEl = document.getElementById('cancelReservationId');

    // 2. Extract the values from it
    const id = inputEl.value;
    const type = inputEl.getAttribute('data-type');

    if (!confirm("Are you sure you want to cancel this reservation?")) return;

    try {
      const response = await fetch(`/client/reservations/${id}/cancel`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        // 3. Now 'type' is defined and can be sent!
        body: JSON.stringify({ type: type })
      });

      if (response.ok) {
        alert("Reservation cancelled successfully.");
        window.location.reload();
      } else {
        const result = await response.json();
        alert(result.message || "Failed to cancel reservation.");
      }
    } catch (error) {
      console.error("Error:", error);
      alert("An error occurred. Check the console.");
    }
  };

  // Close Modal Logic (PRESERVED)
  if (closeBtn) {
    closeBtn.addEventListener('click', () => {
      modalOverlay.style.display = 'none';
    });
  }
});