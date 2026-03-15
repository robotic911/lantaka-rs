<link rel="stylesheet" href="{{ asset('css/employee_food.css') }}">

<div class="food-modal-overlay" id="foodModalOverlay">
  <div class="food-modal" id="foodModal">
    <!-- <button class="food-modal-close" id="foodModalClose">&times;</button> -->

    <main class="food-main-content toggle">
      <div class="reservation-card">
        <div class="card-header food-card-header">
          <h2>Food Menu</h2>
          <button class="add-food-button" id="add_food_button">Add Food</button>
        </div>

        @php
          $categories = [
            'rice' => 'Rice',
            'set_viand' => 'Set Viand',
            'sidedish' => 'Sidedish',
            'drinks' => 'Drinks',
            'desserts' => 'Desserts',
            'other_viand' => 'Other Viand',
            'snacks' => 'Snack',
          ];
        @endphp

        <div class="meals-container">
          @foreach($categories as $key => $label)
            <div class="meal-section">
              <div class="meal-header">
                <span class="meal-name">{{ $label }}</span>
                <span class="meal-count">{{ isset($foods[$key]) ? $foods[$key]->count() : 0 }} items</span>
              </div>

              <div class="food-items">
                @if(isset($foods[$key]) && $foods[$key]->count() > 0)
                  @foreach($foods[$key] as $food)
                    <div
                      class="food-item {{ $food->status === 'unavailable' ? 'unavailable' : '' }}"
                      data-id="{{ $food->food_id }}"
                      data-name="{{ $food->food_name }}"
                      data-status="{{ $food->status }}"
                      data-type="{{ $food->food_category }}"
                      data-price="{{ $food->food_price }}"
                    >
                      <div class="food-name">{{ $food->food_name }}</div>

                      <div class="food-meta">
                        <div class="food-price">₱ {{ number_format($food->food_price, 2) }}</div>

                        @if($food->status === 'unavailable')
                          <span class="food-badge unavailable-badge">Unavailable</span>
                        @else
                          <span class="food-badge available-badge">Available</span>
                        @endif
                      </div>
                    </div>
                  @endforeach
                @else
                  <p class="empty-food-msg">No {{ strtolower($label) }} items added yet.</p>
                @endif
              </div>
            </div>
          @endforeach
        </div>
      </div>
    </main>
  </div>
</div>

<x-employee_add_food />
<x-employee_update_food />


<style>
  .food-modal {
  width: 95%;
  max-width: 1200px;
  max-height: 92vh;
  background: #F3F5EB;
  border-radius: 18px;
  overflow: auto;
  box-shadow: 0 20px 60px rgba(0,0,0,0.25);
  position: relative;
  transform: scale(0.96);
  opacity: 0;
  transition: transform .25s ease, opacity .25s ease;
}

.reservation-card {
  background-color: var(--white);
  border-radius: 16px;
  padding: 28px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.food-card-header {
  display: flex;
  flex-direction: row;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 28px;
  padding-bottom: 18px;
  border-bottom: 2px solid var(--light-gray);
  gap: 16px;
}

.food-card-header h2 {
  font-size: 24px;
  font-weight: 700;
  margin: 0;
  justify-content: flex-start;
}

.add-food-button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 120px;
  padding: 10px 18px;
  border: none;
  color: #fff;
  background-color: var(--primary-blue);
  border-radius: 8px;
  cursor: pointer;
  font-weight: 600;
  transition: all 0.3s ease;
}

.add-food-button:hover {
  background-color: var(--dark-blue);
}

.meals-container {
  display: flex;
  flex-direction: column;
  gap: 22px;
}

.meal-section {
  display: flex;
  flex-direction: column;
  gap: 14px;
  padding: 18px;
  border: 1px solid var(--border-gray);
  border-radius: 14px;
  background: #fcfcf8;
}

.meal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  min-width: unset;
  gap: 12px;
  padding-bottom: 10px;
  border-bottom: 1px solid #ece8e1;
}

.meal-name {
  font-weight: 700;
  font-size: 17px;
  text-decoration: none;
  color: var(--dark-blue);
}

.meal-count {
  font-size: 13px;
  font-weight: 600;
  color: var(--text-light);
  background: #eef1f7;
  padding: 6px 10px;
  border-radius: 999px;
}

.food-items {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(190px, 1fr));
  gap: 14px;
}

.food-item {
  padding: 14px;
  border: 1.5px solid var(--border-gray);
  border-radius: 12px;
  cursor: pointer;
  transition: all 0.25s ease;
  background-color: var(--white);
  text-align: left;
  min-height: 95px;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
}

.food-item:hover {
  border-color: var(--light-blue);
  transform: translateY(-2px);
  box-shadow: 0 8px 18px rgba(44, 62, 127, 0.08);
}

.food-item.selected {
  border-color: var(--primary-blue);
  background-color: #f4f7ff;
}

.food-item.unavailable {
  background-color: #f3f3f3;
  color: var(--disabled-gray);
  cursor: not-allowed;
  border-color: #e3e3e3;
  box-shadow: none;
  transform: none;
}

.food-item.unavailable:hover {
  border-color: #e3e3e3;
  transform: none;
  box-shadow: none;
}

.food-name {
  font-weight: 700;
  font-size: 14px;
  margin-bottom: 10px;
  line-height: 1.35;
  color: var(--text-dark);
}

.food-item.unavailable .food-name {
  color: #8d8d8d;
}

.food-meta {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
}

.food-price {
  font-size: 13px;
  color: var(--primary-blue);
  font-weight: 700;
}

.food-item.unavailable .food-price {
  color: #9b9b9b;
}

.food-badge {
  font-size: 11px;
  font-weight: 700;
  border-radius: 999px;
  padding: 5px 9px;
  white-space: nowrap;
}

.available-badge {
  background: #e9f7ee;
  color: #1b7a3d;
}

.unavailable-badge {
  background: #fdecec;
  color: #c62828;
}

.empty-food-msg {
  padding: 18px;
  color: #777;
  background: #fafafa;
  border: 1px dashed #d8d8d8;
  border-radius: 10px;
  text-align: center;
  font-size: 14px;
}

@media (max-width: 768px) {
  .food-modal {
    width: 100%;
    max-width: 100%;
    border-radius: 14px;
  }

  .reservation-card {
    padding: 18px;
  }

  .food-card-header {
    flex-direction: column;
    align-items: stretch;
  }

  .food-card-header h2 {
    justify-content: center;
    text-align: center;
  }

  .add-food-button {
    width: 100%;
  }

  .meal-header {
    flex-direction: column;
    align-items: flex-start;
  }

  .food-items {
    grid-template-columns: 1fr;
  }
}
</style>