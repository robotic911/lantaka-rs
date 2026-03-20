@vite('resources/js/employee_update_food.js')
<link rel="stylesheet" href="{{ asset('css/employee_update_food.css') }}">

<div id="updateFoodOverlay" class="updatefood-overlay">
  <div id="updateFoodModal" class="updatefood-modal">
    <div class="updatefood-content">

      <div class="updatefood-header">
        <h2>Update Food</h2>
        <button class="updatefood-close" id="updateFoodClose">&times;</button>
      </div>

      @if ($errors->any())
        <div style="background-color: #ffe6e6; color: red; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
          <ul style="margin: 0; padding-left: 20px;">
            @foreach ($errors->all() as $error)
              <li>{{ $error }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      <form id="updateFoodForm" method="POST" class="updatefood-form">
        @csrf
        @method('PUT')

        <input type="hidden" id="updateFoodId" name="Food_ID">

        <div class="updatefood-row">
          <div class="updatefood-group">
            <label for="updateFoodName">Food Name</label>
            <input type="text" id="updateFoodName" name="Food_Name" placeholder="Rice" required>
          </div>

          <div class="updatefood-group">
            <label for="updateFoodStatus">Status</label>
            <select id="updateFoodStatus" name="Food_Status" required>
              <option value="available">Available</option>
              <option value="unavailable">Unavailable</option>
            </select>
          </div>
        </div>

        <div class="updatefood-group">
          <label for="updateFoodType">Food Category</label>
          <select id="updateFoodType" name="Food_Category" required>
            <option value="rice">Rice</option>
            <option value="set_viand">Set Viand</option>
            <option value="sidedish">Side Dish</option>
            <option value="drinks">Drinks</option>
            <option value="desserts">Desserts</option>
            <option value="snacks">Snacks</option>
            <option value="other_viand">Other Viand</option>
          </select>
        </div>

        <div class="updatefood-group">
          <label for="updateFoodPrice">Pricing</label>
          <div class="updatefood-price">
            <span class="updatefood-currency">₱</span>
            <input type="number" id="updateFoodPrice" name="Food_Price" placeholder="500.00" step="0.01" required>
          </div>
        </div>


        <div class="btn-container">
          <button type="button" id="deleteFoodBtn" class="updatefood-delete">
            Remove Food
          </button>
          <button type="submit" class="updatefood-submit">
            Update Food
          </button>
        </div>
      </form>

    </div>
  </div>
</div>