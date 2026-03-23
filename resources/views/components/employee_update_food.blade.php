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

{{-- Delete Confirmation Modal --}}
<div id="deleteFoodOverlay" class="deletefood-overlay">
  <div class="deletefood-dialog">

    <div class="deletefood-icon">
      <svg width="32" height="32" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <polyline points="3 6 5 6 21 6"/>
        <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
        <path d="M10 11v6M14 11v6"/>
        <path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/>
      </svg>
    </div>

    <h3 class="deletefood-title">Remove Food Item?</h3>
    <p class="deletefood-message">
      This will permanently remove <strong id="deleteFoodName">this item</strong> from the menu.<br>
      This action cannot be undone.
    </p>

    <div class="deletefood-actions">
      <button type="button" id="deleteFoodCancel" class="deletefood-btn-cancel">Cancel</button>
      <button type="button" id="deleteFoodConfirm" class="deletefood-btn-confirm">Yes, Remove</button>
    </div>

  </div>
</div>

<style>
.deletefood-overlay {
  display: none;
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.55);
  z-index: 1100;
  align-items: center;
  justify-content: center;
  padding: 18px;
}
.deletefood-overlay.active {
  display: flex;
  animation: dfFadeIn 0.18s ease-out;
}
@keyframes dfFadeIn {
  from { opacity: 0; }
  to   { opacity: 1; }
}
.deletefood-dialog {
  background: #fff;
  border-radius: 14px;
  box-shadow: 0 12px 40px rgba(0,0,0,0.2);
  padding: 36px 32px 28px;
  max-width: 400px;
  width: 100%;
  text-align: center;
  animation: dfSlideUp 0.22s ease-out;
}
@keyframes dfSlideUp {
  from { opacity: 0; transform: translateY(16px); }
  to   { opacity: 1; transform: translateY(0); }
}
.deletefood-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 64px;
  height: 64px;
  border-radius: 50%;
  background: #fee2e2;
  color: #dc2626;
  margin-bottom: 18px;
}
.deletefood-title {
  font-size: 1.15rem;
  font-weight: 700;
  color: #1a1a2e;
  margin-bottom: 10px;
}
.deletefood-message {
  font-size: 0.88rem;
  color: #6b7280;
  line-height: 1.6;
  margin-bottom: 26px;
}
.deletefood-message strong {
  color: #374151;
}
.deletefood-actions {
  display: flex;
  gap: 10px;
  justify-content: center;
}
.deletefood-btn-cancel {
  padding: 10px 24px;
  background: #f3f4f6;
  color: #374151;
  border: none;
  border-radius: 8px;
  font-size: 0.88rem;
  font-weight: 600;
  font-family: inherit;
  cursor: pointer;
  transition: background 0.2s;
}
.deletefood-btn-cancel:hover { background: #e5e7eb; }
.deletefood-btn-confirm {
  padding: 10px 24px;
  background: #dc2626;
  color: #fff;
  border: none;
  border-radius: 8px;
  font-size: 0.88rem;
  font-weight: 700;
  font-family: inherit;
  cursor: pointer;
  transition: background 0.2s, transform 0.15s;
}
.deletefood-btn-confirm:hover {
  background: #b91c1c;
  transform: translateY(-1px);
}
</style>