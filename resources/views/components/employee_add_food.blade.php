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
            <input type="text" id="addFoodName" name="name" placeholder="Rice" required>
          </div>

          <div class="addfood-group">
            <label for="addFoodStatus">Status</label>
            <select id="addFoodStatus" name="status" required>
              <option value="available">Available</option>
              <option value="unavailable">Unavailable</option>
            </select>
          </div>
        </div>

        <div class="addfood-group">
          <label>Food Type</label>
          <div class="addfood-radio">
            <label class="addfood-radio-label">
              <input type="radio" name="type" value="breakfast" checked>
              <span>Breakfast</span>
            </label>
            <label class="addfood-radio-label">
              <input type="radio" name="type" value="snack">
              <span>Snack</span>
            </label>
            <label class="addfood-radio-label">
              <input type="radio" name="type" value="lunch">
              <span>Lunch</span>
            </label>
            <label class="addfood-radio-label">
              <input type="radio" name="type" value="dinner">
              <span>Dinner</span>
            </label>
          </div>
        </div>

        <div class="addfood-group">
          <label for="addFoodPrice">Pricing</label>
          <div class="addfood-price">
            <span class="addfood-currency">₱</span>
            <input type="number" id="addFoodPrice" name="price" placeholder="500.00" step="0.01" required>
          </div>
        </div>

        <button type="submit" class="addfood-submit">Add Food</button>
      </form>
    </div>
  </div>

</div>