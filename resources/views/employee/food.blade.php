@extends('layouts.employee')
<title>Food Management — Lantaka</title>
<link rel="stylesheet" href="{{ asset('css/employee_food_management.css') }}">

@section('content')
@php
  $isAdmin   = auth()->user()->Account_Role === 'admin';
  $activeTab = request('tab', 'individual');

  $categories = [
    'rice'       => 'Rice',
    'set_viand'  => 'Set Viand',
    'sidedish'   => 'Side Dish',
    'drinks'     => 'Drinks',
    'desserts'   => 'Desserts',
    'snacks'     => 'Snacks',
    'other_viand'=> 'Other Viand',
  ];

  $mealTimes = [
    'breakfast' => 'Breakfast',
    'am_snack'  => 'AM Snack',
    'lunch'     => 'Lunch',
    'pm_snack'  => 'PM Snack',
    'dinner'    => 'Dinner',
  ];
@endphp

<div class="fm-page">

  {{-- ── Page header ── --}}
  <div class="fm-page-header">
    <div>
      <h1 class="fm-page-title">🍽 Food Management</h1>
      <p class="fm-page-sub">Manage individual food items and food set packages</p>
    </div>
    @if($isAdmin)
      <button class="fm-add-btn" id="openAddModal">+ Add Item</button>
    @endif
  </div>

  {{-- Session flash --}}
  @if(session('success'))
    <div class="fm-alert fm-alert--success">{{ session('success') }}</div>
  @endif
  @if(session('error'))
    <div class="fm-alert fm-alert--error">{{ session('error') }}</div>
  @endif

  {{-- ── Tabs ── --}}
  <div class="fm-tabs-bar">
    <a href="{{ route('employee.food', ['tab' => 'individual']) }}"
       class="fm-tab {{ $activeTab === 'individual' ? 'fm-tab--active' : '' }}">
      Individual Foods
      <span class="fm-tab-badge">{{ $foods->count() }}</span>
    </a>
    <a href="{{ route('employee.food', ['tab' => 'sets']) }}"
       class="fm-tab {{ $activeTab === 'sets' ? 'fm-tab--active' : '' }}">
      Food Sets
      <span class="fm-tab-badge">{{ $foodSets->count() }}</span>
    </a>
  </div>

  {{-- ════════════════════════════════════════
       TAB 1 — INDIVIDUAL FOODS
  ════════════════════════════════════════ --}}
  @if($activeTab === 'individual')
  <div class="fm-panel">
    <div class="fm-panel-toolbar">
      <span class="fm-panel-info">{{ $foods->count() }} item{{ $foods->count() !== 1 ? 's' : '' }}</span>
    </div>

    <div class="fm-table-wrap">
      <table class="fm-table">
        <thead>
          <tr>
            <th>#</th>
            <th>Food Name</th>
            <th>Category</th>
            <th>Price</th>
            <th>Status</th>
            @if($isAdmin)<th>Actions</th>@endif
          </tr>
        </thead>
        <tbody>
          @forelse($foods as $i => $food)
          <tr>
            <td class="fm-td-num">{{ $i + 1 }}</td>
            <td class="fm-td-name">{{ $food->Food_Name }}</td>
            <td><span class="fm-cat-pill">{{ $categories[$food->Food_Category] ?? ucfirst($food->Food_Category) }}</span></td>
            <td class="fm-td-price">₱ {{ number_format($food->Food_Price, 2) }}</td>
            <td>
              <span class="fm-status-badge {{ $food->Food_Status === 'available' ? 'fm-status--on' : 'fm-status--off' }}">
                {{ ucfirst($food->Food_Status) }}
              </span>
            </td>
            @if($isAdmin)
            <td class="fm-td-actions">
              <button class="fm-btn-edit" onclick="openEditFoodModal({{ $food->Food_ID }}, '{{ addslashes($food->Food_Name) }}', '{{ $food->Food_Category }}', {{ $food->Food_Price }}, '{{ $food->Food_Status }}')">Edit</button>
              <form method="POST" action="{{ route('admin.food.destroy', $food->Food_ID) }}" onsubmit="return confirm('Delete \'{{ addslashes($food->Food_Name) }}\'?')" style="display:inline">
                @csrf @method('DELETE')
                <button type="submit" class="fm-btn-del">Delete</button>
              </form>
            </td>
            @endif
          </tr>
          @empty
          <tr><td colspan="{{ $isAdmin ? 6 : 5 }}" class="fm-empty-row">No food items yet.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
  @endif

  {{-- ════════════════════════════════════════
       TAB 2 — FOOD SETS
  ════════════════════════════════════════ --}}
  @if($activeTab === 'sets')
  <div class="fm-panel">
    <div class="fm-panel-toolbar">
      <span class="fm-panel-info">{{ $foodSets->count() }} set{{ $foodSets->count() !== 1 ? 's' : '' }}</span>
    </div>

    <div class="fm-table-wrap">
      <table class="fm-table">
        <thead>
          <tr>
            <th>#</th>
            <th>Set Name</th>
            <th>Meal Time</th>
            <th>Purpose</th>
            <th>Price</th>
            <th>Status</th>
            @if($isAdmin)<th>Actions</th>@endif
          </tr>
        </thead>
        <tbody>
          @forelse($foodSets as $i => $set)
          <tr>
            <td class="fm-td-num">{{ $i + 1 }}</td>
            <td class="fm-td-name">{{ $set->Food_Set_Name }}</td>
            <td><span class="fm-meal-pill">{{ $mealTimes[$set->Food_Set_Meal_Time] ?? ucfirst($set->Food_Set_Meal_Time) }}</span></td>
            <td>{{ $set->Food_Set_Purpose }}</td>
            <td class="fm-td-price">₱ {{ number_format($set->Food_Set_Price, 2) }}</td>
            <td>
              <span class="fm-status-badge {{ $set->Food_Set_Status === 'available' ? 'fm-status--on' : 'fm-status--off' }}">
                {{ ucfirst($set->Food_Set_Status) }}
              </span>
            </td>
            @if($isAdmin)
            <td class="fm-td-actions">
              <button class="fm-btn-edit" onclick="openEditSetModal({{ $set->Food_Set_ID }}, '{{ addslashes($set->Food_Set_Name) }}', '{{ $set->Food_Set_Meal_Time }}', '{{ addslashes($set->Food_Set_Purpose) }}', {{ $set->Food_Set_Price }}, '{{ $set->Food_Set_Status }}')">Edit</button>
              <form method="POST" action="{{ route('admin.food_set.destroy', $set->Food_Set_ID) }}" onsubmit="return confirm('Delete \'{{ addslashes($set->Food_Set_Name) }}\'?')" style="display:inline">
                @csrf @method('DELETE')
                <button type="submit" class="fm-btn-del">Delete</button>
              </form>
            </td>
            @endif
          </tr>
          @empty
          <tr><td colspan="{{ $isAdmin ? 7 : 6 }}" class="fm-empty-row">No food sets yet.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
  @endif

</div>

{{-- ════════════════════════════════════════
     ADD MODAL — switches content by tab
════════════════════════════════════════ --}}
@if($isAdmin)
<div class="fm-modal-overlay" id="addModalOverlay">
  <div class="fm-modal" id="addModal">
    <div class="fm-modal-header">
      <h3 id="addModalTitle">Add {{ $activeTab === 'sets' ? 'Food Set' : 'Food Item' }}</h3>
      <button class="fm-modal-close" onclick="closeAddModal()">✕</button>
    </div>
    <div class="fm-modal-body">

      @if($activeTab === 'individual')
      {{-- ADD INDIVIDUAL FOOD --}}
      <form method="POST" action="{{ route('admin.food.store') }}" class="fm-form">
        @csrf
        <div class="fm-form-grid">
          <div class="fm-form-group fm-form-group--full">
            <label>Food Name</label>
            <input type="text" name="Food_Name" required maxlength="50" placeholder="e.g. Chicken Adobo">
          </div>
          <div class="fm-form-group">
            <label>Category</label>
            <select name="Food_Category" required>
              @foreach($categories as $key => $label)
                <option value="{{ $key }}">{{ $label }}</option>
              @endforeach
            </select>
          </div>
          <div class="fm-form-group">
            <label>Price (₱)</label>
            <input type="number" name="Food_Price" min="0" step="0.01" required placeholder="0.00">
          </div>
          <div class="fm-form-group">
            <label>Status</label>
            <select name="Food_Status" required>
              <option value="available">Available</option>
              <option value="unavailable">Unavailable</option>
            </select>
          </div>
        </div>
        <div class="fm-form-footer">
          <button type="button" class="fm-btn-cancel" onclick="closeAddModal()">Cancel</button>
          <button type="submit" class="fm-btn-submit">Add Food Item</button>
        </div>
      </form>

      @else
      {{-- ADD FOOD SET --}}
      <form method="POST" action="{{ route('admin.food_set.store') }}" class="fm-form">
        @csrf
        <div class="fm-form-grid">
          <div class="fm-form-group fm-form-group--full">
            <label>Set Name</label>
            <input type="text" name="Food_Set_Name" required maxlength="255" placeholder="e.g. Breakfast Package A">
          </div>
          <div class="fm-form-group">
            <label>Meal Time</label>
            <select name="Food_Set_Meal_Time" required>
              @foreach($mealTimes as $key => $label)
                <option value="{{ $key }}">{{ $label }}</option>
              @endforeach
            </select>
          </div>
          <div class="fm-form-group">
            <label>Purpose / Description</label>
            <input type="text" name="Food_Set_Purpose" required maxlength="50" placeholder="e.g. Full meal for groups">
          </div>
          <div class="fm-form-group">
            <label>Price (₱)</label>
            <input type="number" name="Food_Set_Price" min="0" step="0.01" required placeholder="0.00">
          </div>
          <div class="fm-form-group">
            <label>Status</label>
            <select name="Food_Set_Status" required>
              <option value="available">Available</option>
              <option value="unavailable">Unavailable</option>
            </select>
          </div>
        </div>
        <div class="fm-form-footer">
          <button type="button" class="fm-btn-cancel" onclick="closeAddModal()">Cancel</button>
          <button type="submit" class="fm-btn-submit">Add Food Set</button>
        </div>
      </form>
      @endif

    </div>
  </div>
</div>

{{-- EDIT FOOD MODAL --}}
<div class="fm-modal-overlay" id="editFoodOverlay" style="display:none">
  <div class="fm-modal" id="editFoodModal">
    <div class="fm-modal-header">
      <h3>Edit Food Item</h3>
      <button class="fm-modal-close" onclick="closeEditFoodModal()">✕</button>
    </div>
    <div class="fm-modal-body">
      <form method="POST" id="editFoodForm" class="fm-form">
        @csrf @method('PUT')
        <div class="fm-form-grid">
          <div class="fm-form-group fm-form-group--full">
            <label>Food Name</label>
            <input type="text" name="Food_Name" id="editFoodName" required maxlength="255">
          </div>
          <div class="fm-form-group">
            <label>Category</label>
            <select name="Food_Category" id="editFoodCategory" required>
              @foreach($categories as $key => $label)
                <option value="{{ $key }}">{{ $label }}</option>
              @endforeach
            </select>
          </div>
          <div class="fm-form-group">
            <label>Price (₱)</label>
            <input type="number" name="Food_Price" id="editFoodPrice" min="0" step="0.01" required>
          </div>
          <div class="fm-form-group">
            <label>Status</label>
            <select name="Food_Status" id="editFoodStatus" required>
              <option value="available">Available</option>
              <option value="unavailable">Unavailable</option>
            </select>
          </div>
        </div>
        <div class="fm-form-footer">
          <button type="button" class="fm-btn-cancel" onclick="closeEditFoodModal()">Cancel</button>
          <button type="submit" class="fm-btn-submit">Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- EDIT FOOD SET MODAL --}}
<div class="fm-modal-overlay" id="editSetOverlay" style="display:none">
  <div class="fm-modal" id="editSetModal">
    <div class="fm-modal-header">
      <h3>Edit Food Set</h3>
      <button class="fm-modal-close" onclick="closeEditSetModal()">✕</button>
    </div>
    <div class="fm-modal-body">
      <form method="POST" id="editSetForm" class="fm-form">
        @csrf @method('PUT')
        <div class="fm-form-grid">
          <div class="fm-form-group fm-form-group--full">
            <label>Set Name</label>
            <input type="text" name="Food_Set_Name" id="editSetName" required maxlength="255">
          </div>
          <div class="fm-form-group">
            <label>Meal Time</label>
            <select name="Food_Set_Meal_Time" id="editSetMealTime" required>
              @foreach($mealTimes as $key => $label)
                <option value="{{ $key }}">{{ $label }}</option>
              @endforeach
            </select>
          </div>
          <div class="fm-form-group">
            <label>Purpose / Description</label>
            <input type="text" name="Food_Set_Purpose" id="editSetPurpose" required maxlength="50">
          </div>
          <div class="fm-form-group">
            <label>Price (₱)</label>
            <input type="number" name="Food_Set_Price" id="editSetPrice" min="0" step="0.01" required>
          </div>
          <div class="fm-form-group">
            <label>Status</label>
            <select name="Food_Set_Status" id="editSetStatus" required>
              <option value="available">Available</option>
              <option value="unavailable">Unavailable</option>
            </select>
          </div>
        </div>
        <div class="fm-form-footer">
          <button type="button" class="fm-btn-cancel" onclick="closeEditSetModal()">Cancel</button>
          <button type="submit" class="fm-btn-submit">Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
const BASE_FOOD_URL    = '{{ url("employee/food") }}';
const BASE_SET_URL     = '{{ url("employee/food-sets") }}';

/* ── Add modal ── */
function openAddModal()  { document.getElementById('addModalOverlay').style.display = 'flex'; }
function closeAddModal() { document.getElementById('addModalOverlay').style.display = 'none'; }
document.getElementById('openAddModal')?.addEventListener('click', openAddModal);
document.getElementById('addModalOverlay')?.addEventListener('click', function(e) {
  if (e.target === this) closeAddModal();
});

/* ── Edit Food modal ── */
function openEditFoodModal(id, name, category, price, status) {
  document.getElementById('editFoodForm').action = `${BASE_FOOD_URL}/${id}`;
  document.getElementById('editFoodName').value     = name;
  document.getElementById('editFoodCategory').value = category;
  document.getElementById('editFoodPrice').value    = price;
  document.getElementById('editFoodStatus').value   = status;
  document.getElementById('editFoodOverlay').style.display = 'flex';
}
function closeEditFoodModal() { document.getElementById('editFoodOverlay').style.display = 'none'; }
document.getElementById('editFoodOverlay')?.addEventListener('click', function(e) {
  if (e.target === this) closeEditFoodModal();
});

/* ── Edit Set modal ── */
function openEditSetModal(id, name, mealTime, purpose, price, status) {
  document.getElementById('editSetForm').action = `${BASE_SET_URL}/${id}`;
  document.getElementById('editSetName').value      = name;
  document.getElementById('editSetMealTime').value  = mealTime;
  document.getElementById('editSetPurpose').value   = purpose;
  document.getElementById('editSetPrice').value     = price;
  document.getElementById('editSetStatus').value    = status;
  document.getElementById('editSetOverlay').style.display = 'flex';
}
function closeEditSetModal() { document.getElementById('editSetOverlay').style.display = 'none'; }
document.getElementById('editSetOverlay')?.addEventListener('click', function(e) {
  if (e.target === this) closeEditSetModal();
});

/* Esc key closes any open modal */
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') { closeAddModal(); closeEditFoodModal(); closeEditSetModal(); }
});

/* Auto-dismiss flash alerts */
setTimeout(() => {
  document.querySelectorAll('.fm-alert').forEach(el => el.style.opacity = '0');
}, 3500);
</script>
@endif

@endsection
