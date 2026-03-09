<link rel="stylesheet" href="{{ asset('css/employee_food.css') }}">

<div class="food-modal-overlay" id="foodModalOverlay">
  <div class="food-modal" id="foodModal">
    <button class="food-modal-close" id="foodModalClose">&times;</button>

    <main class="food-main-content toggle">
      <div class="reservation-card">
        <div class="card-header">
          <h2>Food Menu</h2>
          <button class="add-food-button" id="add_food_button">Add Food</button>
        </div>

        <div class="meals-container">

          <div class="meal-section">
            <div class="meal-header">
              <span class="meal-name">Breakfast</span>
            </div>
            <div class="food-items">
              @if(isset($foods['breakfast']) && $foods['breakfast']->count() > 0)
                @foreach($foods['breakfast'] as $food)
                  <div
                    class="food-item"
                    data-id="{{ $food->food_id }}"
                    data-name="{{ $food->food_name }}"
                    data-status="{{ $food->status }}"
                    data-type="{{ strtolower($food->food_category) }}"
                    data-price="{{ $food->food_price }}"
                  >
                    <div class="food-name">
                      {{ $food->food_name }}
                      @if($food->status == 'unavailable')
                        <span style="color:red;font-size:0.8em">(Unavailable)</span>
                      @endif
                    </div>
                    <div class="food-price">₱ {{ number_format($food->food_price, 2) }}</div>
                  </div>
                @endforeach
              @else
                <p style="padding:10px;color:#666">No breakfast items added yet.</p>
              @endif
            </div>
          </div>

          <div class="meal-section">
            <div class="meal-header">
              <span class="meal-name">Snack</span>
            </div>
            <div class="food-items">
              @if(isset($foods['snack']) && $foods['snack']->count() > 0)
                @foreach($foods['snack'] as $food)
                  <div
                    class="food-item"
                    data-id="{{ $food->food_id }}"
                    data-name="{{ $food->food_name }}"
                    data-status="{{ $food->status }}"
                    data-type="{{ strtolower($food->food_category) }}"
                    data-price="{{ $food->food_price }}"
                  >
                    <div class="food-name">
                      {{ $food->food_name }}
                      @if($food->status == 'unavailable')
                        <span style="color:red;font-size:0.8em">(Unavailable)</span>
                      @endif
                    </div>
                    <div class="food-price">₱ {{ number_format($food->food_price, 2) }}</div>
                  </div>
                @endforeach
              @else
                <p style="padding:10px;color:#666">No snack items added yet.</p>
              @endif
            </div>
          </div>

          <div class="meal-section">
            <div class="meal-header">
              <span class="meal-name">Lunch</span>
            </div>
            <div class="food-items">
              @if(isset($foods['lunch']) && $foods['lunch']->count() > 0)
                @foreach($foods['lunch'] as $food)
                  <div
                    class="food-item"
                    data-id="{{ $food->food_id }}"
                    data-name="{{ $food->food_name }}"
                    data-status="{{ $food->status }}"
                    data-type="{{ strtolower($food->food_category) }}"
                    data-price="{{ $food->food_price }}"
                  >
                    <div class="food-name">
                      {{ $food->food_name }}
                      @if($food->status == 'unavailable')
                        <span style="color:red;font-size:0.8em">(Unavailable)</span>
                      @endif
                    </div>
                    <div class="food-price">₱ {{ number_format($food->food_price, 2) }}</div>
                  </div>
                @endforeach
              @else
                <p style="padding:10px;color:#666">No lunch items added yet.</p>
              @endif
            </div>
          </div>

          <div class="meal-section">
            <div class="meal-header">
              <span class="meal-name">Dinner</span>
            </div>
            <div class="food-items">
              @if(isset($foods['dinner']) && $foods['dinner']->count() > 0)
                @foreach($foods['dinner'] as $food)
                  <div
                    class="food-item"
                    data-id="{{ $food->food_id }}"
                    data-name="{{ $food->food_name }}"
                    data-status="{{ $food->status }}"
                    data-type="{{ strtolower($food->food_category) }}"
                    data-price="{{ $food->food_price }}"
                  >
                    <div class="food-name">
                      {{ $food->food_name }}
                      @if($food->status == 'unavailable')
                        <span style="color:red;font-size:0.8em">(Unavailable)</span>
                      @endif
                    </div>
                    <div class="food-price">₱ {{ number_format($food->food_price, 2) }}</div>
                  </div>
                @endforeach
              @else
                <p style="padding:10px;color:#666">No dinner items added yet.</p>
              @endif
            </div>
          </div>

        </div>
      </div>
    </main>
  </div>
</div>

<x-employee_add_food />
<x-employee_update_food />