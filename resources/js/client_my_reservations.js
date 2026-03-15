document.addEventListener('DOMContentLoaded', () => {
  const expandButtons = document.querySelectorAll('.expand-button');
  const modalOverlay = document.querySelector('.modal-overlay');
  const closeBtn = document.querySelector('.close-btn');

  function formatDateHeader(dateString) {
    if (!dateString) return 'Unknown Date';

    const date = new Date(dateString);
    if (isNaN(date.getTime())) return dateString;

    return date.toLocaleDateString('en-US', {
      month: 'short',
      day: '2-digit',
      year: 'numeric'
    });
  }

  expandButtons.forEach(button => {
    button.addEventListener('click', function () {
      const data = JSON.parse(this.getAttribute('data-info'));

      // --- 1. Fill basic text details ---
      document.getElementById('modalAccommodation').textContent = data.accommodation;
      document.getElementById('modalPax').textContent = data.pax;
      document.getElementById('modalCheckIn').textContent = data.check_in;
      document.getElementById('modalCheckOut').textContent = data.check_out;
      document.getElementById('modalFoodIdLabel').textContent = `Food:`;

      const cancelBtn = document.querySelector('.detail-section-cancel');
      if (cancelBtn) {
        if (data.status !== 'pending') {
          cancelBtn.style.display = 'none';
        } else {
          cancelBtn.style.display = 'flex';
        }
      }

      // --- 2. Store ID + type for cancellation ---
      const idInput = document.getElementById('cancelReservationId');
      if (idInput) {
        idInput.value = data.real_id;
        idInput.setAttribute('data-type', data.type);
        console.log('Captured ID:', idInput.value, 'Type:', data.type);
      }

      // --- 3. Build food table by date ---
      const foodListContainer = document.getElementById('modalFoodList');
      let foodHtml = '';

      if (data.foods && data.foods.length > 0) {
        const foodsByDate = {};

        data.foods.forEach(food => {
          const servingTime =
            food.pivot?.serving_time ||
            food.serving_time ||
            null;

          const foodName =
            food.food_name ||
            food.name ||
            'Unknown Food';

          if (!servingTime) return;

          if (!foodsByDate[servingTime]) {
            foodsByDate[servingTime] = [];
          }

          foodsByDate[servingTime].push(foodName);
        });

        const dates = Object.keys(foodsByDate).sort();

        if (dates.length > 0) {
          let maxRows = 0;

          dates.forEach(date => {
            maxRows = Math.max(maxRows, foodsByDate[date].length);
          });

          foodHtml += `
            <div style="overflow-x: auto; margin-top: 8px;">
              <table style="width: 100%; border-collapse: collapse; font-size: 0.85em;">
                <thead>
                  <tr style="background: #f5f5f5;">
          `;

          dates.forEach(date => {
            foodHtml += `
              <th style="border: 1px solid #ddd; padding: 8px; text-align: left; min-width: 130px;">
                ${formatDateHeader(date)}
              </th>
            `;
          });

          foodHtml += `
                  </tr>
                </thead>
                <tbody>
          `;

          for (let row = 0; row < maxRows; row++) {
            foodHtml += `<tr>`;

            dates.forEach(date => {
              foodHtml += `
                <td style="border: 1px solid #ddd; padding: 8px; vertical-align: top;">
                  ${foodsByDate[date][row] ?? '—'}
                </td>
              `;
            });

            foodHtml += `</tr>`;
          }

          foodHtml += `
                </tbody>
              </table>
            </div>
          `;
        } else {
          foodHtml = '<p class="detail-value" style="margin-top: 5px;">No food reserved.</p>';
        }
      } else {
        foodHtml = '<p class="detail-value" style="margin-top: 5px;">No food reserved.</p>';
      }

      foodListContainer.innerHTML = foodHtml;

      if (modalOverlay) {
        modalOverlay.style.display = 'flex';
      }
    });
  });

  // --- 4. Cancellation logic ---
  window.confirmCancellation = async function () {
    const inputEl = document.getElementById('cancelReservationId');
    if (!inputEl) return;

    const id = inputEl.value;
    const type = inputEl.getAttribute('data-type');

    if (!confirm('Are you sure you want to cancel this reservation?')) return;

    try {
      const response = await fetch(`/client/reservations/${id}/cancel`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ type: type })
      });

      if (response.ok) {
        alert('Reservation cancelled successfully.');
        window.location.reload();
      } else {
        const result = await response.json();
        alert(result.message || 'Failed to cancel reservation.');
      }
    } catch (error) {
      console.error('Error:', error);
      alert('An error occurred. Check the console.');
    }
  };

  // --- 5. Close modal ---
  if (closeBtn && modalOverlay) {
    closeBtn.addEventListener('click', () => {
      modalOverlay.style.display = 'none';
    });
  }

  if (modalOverlay) {
    modalOverlay.addEventListener('click', (e) => {
      if (e.target === modalOverlay) {
        modalOverlay.style.display = 'none';
      }
    });
  }
});