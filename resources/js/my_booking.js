let cart = {};

const mealLabels = {
  breakfast: 'Breakfast',
  am_snack: 'AM Snack',
  lunch: 'Lunch',
  pm_snack: 'PM Snack',
  dinner: 'Dinner'
};

function getCartKeyFromElement(element) {
  const type = element.dataset.type;
  const id = element.dataset.id;
  const checkIn = element.dataset.in;
  const checkOut = element.dataset.out;

  return `${type}_${id}_${checkIn}_${checkOut}`;
}

function formatPeso(value) {
  return `₱ ${Number(value || 0).toLocaleString(undefined, {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  })}`;
}

function formatCategory(category) {
  return String(category || '')
    .replace(/_/g, ' ')
    .replace(/\b\w/g, char => char.toUpperCase());
}

function renderSummaryItems() {
  const summaryItemsContainer = document.getElementById('summary-items');
  if (!summaryItemsContainer) return;

  summaryItemsContainer.innerHTML = '';

  Object.values(cart).forEach(item => {
    summaryItemsContainer.innerHTML += `
      <div class="summary-item">
        <span class="item-label">${item.name}</span>
        <span class="item-amount">${formatPeso(item.baseTotal)}</span>
      </div>
    `;
  });
}

function renderSummaryFoods() {
  const foodContainer = document.getElementById('summary-foods');
  if (!foodContainer) return;

  foodContainer.innerHTML = '';

  let hasFoods = false;

  Object.values(cart).forEach(item => {
    if (item.type !== 'venue' || !item.foodDetails || item.foodDetails.length === 0) {
      return;
    }

    if (!hasFoods) {
      foodContainer.innerHTML += `
        <p style="font-weight: bold; margin: 10px 0 8px 0; font-size: 0.9em; color: #333;">
          Selected Foods:
        </p>
      `;
      hasFoods = true;
    }

    foodContainer.innerHTML += `
      <div style="margin-bottom: 14px; padding: 8px 0; border-top: 1px dashed #ddd;">
        <div style="font-weight: 600; font-size: 0.9em; color: #444; margin-bottom: 8px;">
          ${item.name}
        </div>
      </div>
    `;

    item.foodDetails.forEach(group => {
      foodContainer.innerHTML += `
        <div style="margin-bottom: 10px; padding-left: 4px;">
          <div style="font-weight: 600; font-size: 0.82em; color: #555; margin-bottom: 6px;">
            ${group.dateLabel}
          </div>

          ${group.items.map(entry => `
            <div style="display:flex; justify-content:space-between; gap:10px; font-size:0.8em; color:#666; margin-bottom:4px; padding-left:8px;">
              <span>• ${entry.food_name} (${entry.mealLabel} • ${entry.categoryLabel} × ${entry.pax} pax)</span>
              <span>${formatPeso(entry.subtotal)}</span>
            </div>
          `).join('')}
        </div>
      `;
    });
  });
}

function updateGrandTotal() {
  const grandTotal = Object.values(cart).reduce((sum, item) => {
    return sum + Number(item.total || 0);
  }, 0);

  const grandTotalEl = document.getElementById('summary-grand-total');
  if (grandTotalEl) {
    grandTotalEl.innerText = formatPeso(grandTotal);
  }

  renderSummaryItems();
  renderSummaryFoods();
}

function updateHiddenSelectedItemsInput() {
  const selectedItemsInput = document.getElementById('selected-items-input');
  if (!selectedItemsInput) return;

  const selectedItems = Object.values(cart).map(item => ({
    id: item.id,
    type: item.type,
    basePrice: item.basePrice,
    check_in: item.checkIn,
    check_out: item.checkOut,
    pax: item.pax,
    purpose: item.purpose || '',
    total_amount: item.total,
    food: item.food || [],
    food_enabled: item.foodEnabled || {},
    meal_enabled: item.mealEnabled || {},
    food_selections: item.foodSelections || {}
  }));

  selectedItemsInput.value = JSON.stringify(selectedItems);
}

window.selectItem = function (element) {
  const cartKey = getCartKeyFromElement(element);

  const name = element.dataset.name;
  const baseTotal = Number(element.dataset.total || 0);
  const basePrice = Number(element.dataset.base || 0);
  const id = element.dataset.id;
  const type = element.dataset.type;
  const checkIn = element.dataset.in;
  const checkOut = element.dataset.out;
  const pax = Number(element.dataset.pax || 0);
  const purpose = element.dataset.purpose || '';

  const foodsJson = element.dataset.food || '[]';
  const foodSelectionsJson = element.dataset.foodSelections || '{}';
  const foodEnabledJson = element.dataset.foodEnabled || '{}';
  const mealEnabledJson = element.dataset.mealEnabled || '{}';

  const emptyMsg = document.getElementById('empty-msg');
  const summaryDetails = document.getElementById('summary-details');

  if (emptyMsg) emptyMsg.style.display = 'none';
  if (summaryDetails) summaryDetails.style.display = 'block';

  let foodArr = [];
  let foodDetails = [];
  let foodTotal = 0;

  let parsedFoodSelections = {};
  let parsedFoodEnabled = {};
  let parsedMealEnabled = {};

  try {
    const foods = JSON.parse(foodsJson);
    parsedFoodSelections = JSON.parse(foodSelectionsJson);
    parsedFoodEnabled = JSON.parse(foodEnabledJson);
    parsedMealEnabled = JSON.parse(mealEnabledJson);

    const foodMap = {};
    if (Array.isArray(foods)) {
      foods.forEach(food => {
        foodMap[String(food.food_id)] = food;
      });
    }

    Object.entries(parsedFoodSelections || {}).forEach(([date, meals]) => {
      if ((parsedFoodEnabled?.[date] ?? '1') !== '1') {
        return;
      }

      const dateEntries = [];

      Object.entries(meals || {}).forEach(([mealType, categories]) => {
        if ((parsedMealEnabled?.[date]?.[mealType] ?? '1') !== '1') {
          return;
        }

        if (!categories || typeof categories !== 'object') {
          return;
        }

        Object.entries(categories).forEach(([category, foodId]) => {
          if (!foodId) return;

          const food = foodMap[String(foodId)];
          if (!food) return;

          const unitPrice = Number(food.food_price || food.Food_Price || 0);
          const subtotal = unitPrice * pax;

          foodArr.push(food.food_id);
          foodTotal += subtotal;

          dateEntries.push({
            food_id: food.food_id,
            food_name: food.food_name,
            mealType: mealType,
            mealLabel: mealLabels[mealType] || mealType,
            category: category,
            categoryLabel: formatCategory(category),
            pax: pax,
            subtotal: subtotal
          });
        });
      });

      if (dateEntries.length > 0) {
        foodDetails.push({
          date: date,
          dateLabel: new Date(date).toLocaleDateString(undefined, {
            year: 'numeric',
            month: 'long',
            day: '2-digit'
          }),
          items: dateEntries
        });
      }
    });
  } catch (error) {
    console.error('Error parsing selected food data:', error);
  }

  const finalTotal = baseTotal + foodTotal;

  cart[cartKey] = {
    key: cartKey,
    name: name,
    baseTotal: baseTotal,
    basePrice: basePrice,
    foodTotal: foodTotal,
    total: finalTotal,
    id: id,
    type: type,
    checkIn: checkIn,
    checkOut: checkOut,
    pax: pax,
    purpose: purpose,
    food: [...new Set(foodArr)],
    foodDetails: foodDetails,
    foodEnabled: parsedFoodEnabled,
    mealEnabled: parsedMealEnabled,
    foodSelections: parsedFoodSelections
  };

  updateGrandTotal();
  updateHiddenSelectedItemsInput();
};

document.addEventListener('DOMContentLoaded', () => {
  const cartContainer = document.querySelector('.cart-items');
  const summaryDetails = document.getElementById('summary-details');
  const emptyMsg = document.getElementById('empty-msg');
  const checkoutForm = document.querySelector('form[action*="reservation.store"]');

  if (cartContainer) {
    cartContainer.addEventListener('click', (e) => {
      const drawerToggle = e.target.closest('.food-drawer-toggle');
      if (drawerToggle) {
        return;
      }

      // Let Remove / Edit form buttons submit normally without toggling the item
      const actionForm = e.target.closest('.cart-action-form');
      if (actionForm) {
        return;
      }

      const item = e.target.closest('.cart-item');
      if (!item) return;

      const cartKey = getCartKeyFromElement(item);

      if (!item.classList.contains('highlighted')) {
        item.classList.add('highlighted');
        selectItem(item);
      } else {
        item.classList.remove('highlighted');

        delete cart[cartKey];

        updateGrandTotal();
        updateHiddenSelectedItemsInput();

        if (Object.keys(cart).length === 0) {
          if (summaryDetails) summaryDetails.style.display = 'none';
          if (emptyMsg) emptyMsg.style.display = 'block';
        }
      }
    });
  }

  if (checkoutForm) {
    checkoutForm.addEventListener('submit', function (e) {
      updateHiddenSelectedItemsInput();

      if (Object.keys(cart).length === 0) {
        e.preventDefault();
        alert('Please select at least one item to confirm.');
      }
    });
  }
});