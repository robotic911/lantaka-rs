@vite('resources/js/employee_add_food.js')
<link rel="stylesheet" href="{{ asset('css/employee_add_food.css') }}">

<div id="addFoodOverlay" class="addfood-overlay">

  <div id="addFoodModal" class="addfood-modal">
    <div class="addfood-content">

      <div class="addfood-header">
        <h2>Add Food</h2>
        <button class="addfood-close" id="addFoodClose">&times;</button>
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

      <form action="{{ route('admin.food.store') }}" method="POST" class="addfood-form">
        @csrf

        <div class="addfood-row">
          <div class="addfood-group">
            <label for="addFoodName">Food Name</label>
            <input type="text" id="addFoodName" name="Food_Name" placeholder="Rice" required>
          </div>

          <div class="addfood-group">
            <label for="addFoodStatus">Status</label>
            <select id="addFoodStatus" name="Food_Status" required>
              <option value="available">Available</option>
              <option value="unavailable">Unavailable</option>
            </select>
          </div>
        </div>

        <div class="addfood-group">
          <label for="addFoodType">Food Category</label>
          <select id="addFoodType" name="Food_Category" required>
            <option value="rice">Rice</option>
            <option value="set_viand">Set Viand</option>
            <option value="sidedish">Side Dish</option>
            <option value="drinks">Drinks</option>
            <option value="desserts">Desserts</option>
            <option value="snacks">Snacks</option>
            <option value="other_viand">Other Viand</option>
          </select>
        </div>

        <div class="addfood-group">
          <label for="addFoodPrice">Pricing</label>
          <div class="addfood-price">
            <span class="addfood-currency">₱</span>
            <input type="number" id="addFoodPrice" name="Food_Price" placeholder="500.00" step="0.01" required>
          </div>
        </div>

        <button type="submit" class="addfood-submit">Add Food</button>
      </form>
    </div>
  </div>

</div>