@extends('layouts.employee')
<title>Food Management — Lantaka</title>
<link rel="stylesheet" href="{{ asset('css/employee_food_management.css') }}">

@section('content')
@php
  $isAdmin   = auth()->user()->Account_Role === 'admin';
  $activeTab = request('tab', 'individual');

  $categories = [
    'rice'        => 'Rice',
    'meatviand'   => 'Meat Viand',
    'noodleviand' => 'Noodle Viand',
    'veggieviand' => 'Veggie Viand',
    'drinks'      => 'Drinks',
    'desserts'    => 'Desserts',
    'fruits'      => 'Fruits',
    'snacks'      => 'Snacks',
  ];

  $mealTimes = [
    'any'       => 'Any',
    'breakfast' => 'Breakfast',
    'am_snack'  => 'AM Snack',
    'lunch'     => 'Lunch',
    'pm_snack'  => 'PM Snack',
    'dinner'    => 'Dinner',
  ];

  // Purpose group definitions — keyed by slug so we never need Str::slug() in the template
  // Structure: [ slug => ['label' => '...', 'items' => [key => label, ...]] ]
  $purposeGroups = [
    'spiritual' => [
      'label' => 'Spiritual',
      'items' => [
        'retreat'      => 'Retreat',
        'recollection' => 'Recollection',
      ],
    ],
    'general-event' => [
      'label' => 'General Event',
      'items' => [
        'meeting'     => 'Meeting',
        'seminar'     => 'Seminar',
        'birthday'    => 'Birthday',
        'lecture'     => 'Lecture',
        'wedding'     => 'Wedding',
        'orientation' => 'Orientation',
        'others'      => 'Others'
      ],  
    ],
  ];

  // Flat lookup: purpose key → display label (includes 'all')
  $allPurposes = ['all' => 'All'];
  foreach ($purposeGroups as $grp) {
    $allPurposes = array_merge($allPurposes, $grp['items']);
  }

  // JS-safe map: slug → [keys] for the group-toggle script
  $purposeGroupsJs = array_map(fn($g) => array_keys($g['items']), $purposeGroups);
@endphp

<div class="fm-page">

  {{-- ── Page header ── --}}
  <div class="fm-page-header">
    <div>
      <h1 class="fm-page-title"><span class="icon food-icon"></span> Food Management</h1>
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
      <span class="fm-tab-badge">{{ $foods->total() }}</span>
    </a>
    <a href="{{ route('employee.food', ['tab' => 'sets']) }}"
       class="fm-tab {{ $activeTab === 'sets' ? 'fm-tab--active' : '' }}">
      Food Sets
      <span class="fm-tab-badge">{{ $foodSets->total() }}</span>
    </a>
  </div>

  {{-- ════════════════════════════════════════
       TAB 1 — INDIVIDUAL FOODS
  ════════════════════════════════════════ --}}
  @if($activeTab === 'individual')
  @php $selectedCategory = request('category', ''); @endphp
  <div class="fm-panel">
    <div class="fm-panel-toolbar">
      <span class="fm-panel-info">
        {{ $foods->total() }} item{{ $foods->total() !== 1 ? 's' : '' }}
        @if($selectedCategory)
          &nbsp;·&nbsp;<span class="fm-filter-active-label">{{ $categories[$selectedCategory] ?? ucfirst($selectedCategory) }}</span>
        @endif
      </span>

      <form method="GET" action="{{ route('employee.food') }}" class="fm-filter-form">
        <input type="hidden" name="tab" value="individual">
        <label class="fm-filter-label" for="fm-cat-filter">Category</label>
        <select id="fm-cat-filter" name="category" class="fm-filter-select"
                onchange="this.form.submit()">
          <option value="">All Categories</option>
          @foreach($categories as $key => $label)
            <option value="{{ $key }}" {{ $selectedCategory === $key ? 'selected' : '' }}>
              {{ $label }}
            </option>
          @endforeach
        </select>
        @if($selectedCategory)
          <a href="{{ route('employee.food', ['tab' => 'individual']) }}" class="fm-filter-clear" title="Clear filter">✕</a>
        @endif
      </form>
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
          @forelse($foods as $food)
          <tr>
            <td class="fm-td-num">{{ $foods->firstItem() + $loop->index }}</td>
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
              <button class="fm-btn-edit" onclick="openEditFoodModal('{{ $food->Food_ID }}', '{{ addslashes($food->Food_Name) }}', '{{ $food->Food_Category }}', '{{ $food->Food_Price }}', '{{ $food->Food_Status }}')">Edit</button>

              <form method="POST" action="{{ route('admin.food.destroy', $food->Food_ID) }}"
                    onsubmit="return confirm('Delete ' + @json($food->Food_Name) + '?')"
                    style="display:inline">
                @csrf
                @method('DELETE')
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

    @if($foods->hasPages())
    <div class="fm-pagination">
      {{ $foods->appends(request()->only(['tab', 'category']))->links('vendor.pagination.simple') }}
    </div>
    @endif
  </div>
  @endif

  {{-- ════════════════════════════════════════
       TAB 2 — FOOD SETS
  ════════════════════════════════════════ --}}
  @if($activeTab === 'sets')
  <div class="fm-panel">
    <div class="fm-panel-toolbar">
      <span class="fm-panel-info">{{ $foodSets->total() }} set{{ $foodSets->total() !== 1 ? 's' : '' }}</span>
    </div>

    <div class="fm-table-wrap">
      @php $foodsById = $allFoods->keyBy('Food_ID'); @endphp
      <table class="fm-table">
        <thead>
          <tr>
            <th>#</th>
            <th>Set Name</th>
            <th>Meal Time(s)</th>
            <th>Purpose(s)</th>
            <th>Included Foods</th>
            <th>Price</th>
            <th>Status</th>
            @if($isAdmin)<th>Actions</th>@endif
          </tr>
        </thead>
        <tbody>
          @forelse($foodSets as $set)
          @php
            $setMealTimes  = is_array($set->Food_Set_Meal_Time) ? $set->Food_Set_Meal_Time : [$set->Food_Set_Meal_Time];
            $setPurposes   = is_array($set->Food_Set_Purpose)   ? $set->Food_Set_Purpose   : [$set->Food_Set_Purpose];
            $setFoodIds    = is_array($set->Food_Set_Food_IDs)  ? $set->Food_Set_Food_IDs  : [];
            $setFoodModels = collect($setFoodIds)->map(fn($id) => $foodsById->get($id))->filter();
          @endphp
          <tr>
            <td class="fm-td-num">{{ $foodSets->firstItem() + $loop->index }}</td>
            <td class="fm-td-name">{{ $set->Food_Set_Name }}</td>
            <td class="fm-td-pills">
              @foreach($setMealTimes as $mt)
                <span class="fm-meal-pill">{{ $mealTimes[$mt] ?? ucfirst($mt) }}</span>
              @endforeach
            </td>
            <td class="fm-td-pills">
              @foreach(array_slice($setPurposes, 0, 2) as $pu)
                <span class="fm-purpose-pill">{{ $allPurposes[$pu] ?? ucfirst($pu) }}</span>
              @endforeach
              @if(count($setPurposes) > 2)
                <span class="fm-purpose-pill fm-pill--more">+{{ count($setPurposes) - 2 }} more</span>
              @endif
            </td>
            <td class="fm-td-foods">
              @if($setFoodModels->isNotEmpty())
                <div class="fm-food-pills">
                  @foreach($setFoodModels->take(3) as $fi)
                    <span class="fm-food-pill">{{ $fi->Food_Name }}</span>
                  @endforeach
                  @if($setFoodModels->count() > 3)
                    <span class="fm-food-pill fm-food-pill--more">+{{ $setFoodModels->count() - 3 }} more</span>
                  @endif
                </div>
              @else
                <span class="fm-no-foods">—</span>
              @endif
            </td>
            <td class="fm-td-price">₱ {{ number_format($set->Food_Set_Price, 2) }}</td>
            <td>
              <span class="fm-status-badge {{ $set->Food_Set_Status === 'available' ? 'fm-status--on' : 'fm-status--off' }}">
                {{ ucfirst($set->Food_Set_Status) }}
              </span>
            </td>
            @if($isAdmin)
            <td class="fm-td-actions">
              <button class="fm-btn-edit js-edit-set-btn"
                data-id="{{ $set->Food_Set_ID }}"
                data-name="{{ $set->Food_Set_Name }}"
                data-meal-times="{{ json_encode($setMealTimes) }}"
                data-purposes="{{ json_encode($setPurposes) }}"
                data-price="{{ $set->Food_Set_Price }}"
                data-status="{{ $set->Food_Set_Status }}"
                data-food-ids="{{ json_encode($setFoodIds) }}">Edit</button>
              <form method="POST" action="{{ route('admin.food_set.destroy', $set->Food_Set_ID) }}" onsubmit="return confirm('Delete \'{{ addslashes($set->Food_Set_Name) }}\'?')" style="display:inline">
                @csrf @method('DELETE')
                <button type="submit" class="fm-btn-del">Delete</button>
              </form>
            </td>
            @endif
          </tr>
          @empty
          <tr><td colspan="{{ $isAdmin ? 8 : 7 }}" class="fm-empty-row">No food sets yet.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if($foodSets->hasPages())
    <div class="fm-pagination">
      {{ $foodSets->appends(request()->except('set_page'))->links('vendor.pagination.simple') }}
    </div>
    @endif
  </div>
  @endif

</div>

{{-- ════════════════════════════════════════
     REUSABLE MACROS
════════════════════════════════════════ --}}
@php
  /* Inline helper — renders a food checklist block */
  $renderFoodChecklist = function(string $inputName, string $checklistId, string $summaryId) use ($allFoods, $categories) {
    // rendered inline below — PHP closures can't echo Blade; we just split into two sections
  };
@endphp

{{-- ════════════════════════════════════════
     ADD MODAL — switches content by tab
════════════════════════════════════════ --}}
@if($isAdmin)
<div class="fm-modal-overlay" id="addModalOverlay">
  <div class="fm-modal {{ $activeTab === 'sets' ? 'fm-modal--wide' : '' }}" id="addModal">
    <div class="fm-modal-header">
      <h3>Add {{ $activeTab === 'sets' ? 'Food Set' : 'Food Item' }}</h3>
      <button class="fm-modal-close" onclick="closeAddModal()">✕</button>
    </div>
    <div class="fm-modal-body">

      @if($activeTab === 'individual')
      {{-- ── ADD INDIVIDUAL FOOD ── --}}
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
      {{-- ── ADD FOOD SET ── --}}
      <form method="POST" action="{{ route('admin.food_set.store') }}" class="fm-form" id="addSetForm">
        @csrf
        <div class="fm-form-grid">
          <div class="fm-form-group fm-form-group--full">
            <label>Set Name</label>
            <input type="text" name="Food_Set_Name" required maxlength="255" placeholder="e.g. Breakfast Package A">
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

        {{-- Meal Times --}}
        <div class="fm-form-group fm-form-group--full fm-form-group--checks">
          <label>Meal Time(s) <span class="fm-label-hint">(select one or more)</span></label>
          <div class="fm-check-row" id="addMealTimeRow">
            @foreach($mealTimes as $key => $label)
              <label class="fm-pill-check">
                <input type="checkbox" name="Food_Set_Meal_Time[]" value="{{ $key }}">
                <span>{{ $label }}</span>
              </label>
            @endforeach
          </div>
        </div>

        {{-- Purposes --}}
        <div class="fm-form-group fm-form-group--full fm-form-group--checks">
          <label>Applicable For <span class="fm-label-hint">(select group or individual purposes)</span></label>
          <div class="fm-purpose-ui" id="addPurposeUI">
            {{-- Group toggle buttons --}}
            <div class="fm-group-btns">
              <button type="button" class="fm-group-btn" data-target="addPurposeUI" data-group="all">All</button>
              @foreach($purposeGroups as $grpSlug => $grp)
                <button type="button" class="fm-group-btn"
                        data-target="addPurposeUI"
                        data-group="{{ $grpSlug }}">{{ $grp['label'] }}</button>
              @endforeach
            </div>
            {{-- Sub-items per group --}}
            @foreach($purposeGroups as $grpSlug => $grp)
              <div class="fm-purpose-sub" id="addPurposeUI-{{ $grpSlug }}" style="display:none">
                <div class="fm-check-row">
                  @foreach($grp['items'] as $key => $label)
                    <label class="fm-pill-check">
                      <input type="checkbox" name="Food_Set_Purpose[]" value="{{ $key }}"
                             class="add-purpose-check" data-group="{{ $grpSlug }}">
                      <span>{{ $label }}</span>
                    </label>
                  @endforeach
                </div>
              </div>
            @endforeach
            {{-- Hidden "all" checkbox — checked when All group button is active --}}
            <input type="checkbox" name="Food_Set_Purpose[]" value="all"
                   id="addPurposeAll" class="add-purpose-all-check" style="display:none">
          </div>
        </div>

        {{-- Food item selection --}}
        <div class="fm-form-group fm-form-group--full fm-form-group--foods">
          <label>Include Food Items <span class="fm-label-hint">(optional)</span></label>
          <div class="fm-selected-summary" id="addFoodSummary">
            <span class="fm-summary-empty">No foods selected yet</span>
          </div>
          <div class="fm-food-checklist" id="addFoodChecklist">
            @php $prevCat = null; @endphp
            @foreach($allFoods as $f)
              @if($f->Food_Category !== $prevCat)
                @if($prevCat !== null)</div>@endif
                <div class="fm-check-category">
                  <span class="fm-check-cat-label">{{ $categories[$f->Food_Category] ?? ucfirst($f->Food_Category) }}</span>
                @php $prevCat = $f->Food_Category; @endphp
              @endif
              <label class="fm-check-item {{ $f->Food_Status === 'unavailable' ? 'fm-check-item--dim' : '' }}"
                     data-food-name="{{ $f->Food_Name }}">
                <input type="checkbox" name="food_ids[]" value="{{ $f->Food_ID }}"
                       class="add-food-check"
                       {{ $f->Food_Status === 'unavailable' ? 'disabled' : '' }}>
                <span class="fm-check-name">{{ $f->Food_Name }}</span>
                <span class="fm-check-price">₱{{ number_format($f->Food_Price, 2) }}</span>
                @if($f->Food_Status === 'unavailable')
                  <span class="fm-check-unavail">unavailable</span>
                @endif
              </label>
            @endforeach
            @if($prevCat !== null)</div>@endif
            @if($allFoods->isEmpty())
              <p class="fm-no-foods-hint">No food items found. Add individual foods first.</p>
            @endif
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

{{-- ── EDIT FOOD MODAL ── --}}
<div class="fm-modal-overlay" id="editFoodOverlay" style="display:none">
  <div class="fm-modal" id="editFoodModal">
    <div class="fm-modal-header">
      <h3>Edit Food Item</h3>
      <button class="fm-modal-close" onclick="closeEditFoodModal()">✕</button>
    </div>
    <div class="fm-modal-body">
      <form method="POST" id= "editFoodForm" class="fm-form">
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

{{-- ── EDIT FOOD SET MODAL ── --}}
<div class="fm-modal-overlay" id="editSetOverlay" style="display:none">
  <div class="fm-modal fm-modal--wide" id="editSetModal">
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

        {{-- Meal Times --}}
        <div class="fm-form-group fm-form-group--full fm-form-group--checks">
          <label>Meal Time(s) <span class="fm-label-hint">(select one or more)</span></label>
          <div class="fm-check-row" id="editMealTimeRow">
            @foreach($mealTimes as $key => $label)
              <label class="fm-pill-check">
                <input type="checkbox" name="Food_Set_Meal_Time[]" value="{{ $key }}"
                       class="edit-mealtime-check" data-value="{{ $key }}">
                <span>{{ $label }}</span>
              </label>
            @endforeach
          </div>
        </div>

        {{-- Purposes --}}
        <div class="fm-form-group fm-form-group--full fm-form-group--checks">
          <label>Applicable For <span class="fm-label-hint">(select group or individual purposes)</span></label>
          <div class="fm-purpose-ui" id="editPurposeUI">
            {{-- Group toggle buttons --}}
            <div class="fm-group-btns">
              <button type="button" class="fm-group-btn" data-target="editPurposeUI" data-group="all">All</button>
              @foreach($purposeGroups as $grpSlug => $grp)
                <button type="button" class="fm-group-btn"
                        data-target="editPurposeUI"
                        data-group="{{ $grpSlug }}">{{ $grp['label'] }}</button>
              @endforeach
            </div>
            {{-- Sub-items per group --}}
            @foreach($purposeGroups as $grpSlug => $grp)
              <div class="fm-purpose-sub" id="editPurposeUI-{{ $grpSlug }}" style="display:none">
                <div class="fm-check-row">
                  @foreach($grp['items'] as $key => $label)
                    <label class="fm-pill-check">
                      <input type="checkbox" name="Food_Set_Purpose[]" value="{{ $key }}"
                             class="edit-purpose-check" data-group="{{ $grpSlug }}" data-value="{{ $key }}">
                      <span>{{ $label }}</span>
                    </label>
                  @endforeach
                </div>
              </div>
            @endforeach
            {{-- Hidden "all" checkbox — checked when All group button is active --}}
            <input type="checkbox" name="Food_Set_Purpose[]" value="all"
                   id="editPurposeAll" class="edit-purpose-all-check" style="display:none">
          </div>
        </div>

        {{-- Food item selection --}}
        <div class="fm-form-group fm-form-group--full fm-form-group--foods">
          <label>Included Food Items <span class="fm-label-hint">(check/uncheck to update grouping)</span></label>
          <div class="fm-selected-summary" id="editFoodSummary">
            <span class="fm-summary-empty">No foods selected yet</span>
          </div>
          <div class="fm-food-checklist" id="editFoodChecklist">
            @php $prevCat = null; @endphp
            @foreach($allFoods as $f)
              @if($f->Food_Category !== $prevCat)
                @if($prevCat !== null)</div>@endif
                <div class="fm-check-category">
                  <span class="fm-check-cat-label">{{ $categories[$f->Food_Category] ?? ucfirst($f->Food_Category) }}</span>
                @php $prevCat = $f->Food_Category; @endphp
              @endif
              <label class="fm-check-item {{ $f->Food_Status === 'unavailable' ? 'fm-check-item--dim' : '' }}"
                     data-food-name="{{ $f->Food_Name }}">
                <input type="checkbox" name="food_ids[]" value="{{ $f->Food_ID }}"
                       class="edit-food-check"
                       data-food-id="{{ $f->Food_ID }}"
                       {{ $f->Food_Status === 'unavailable' ? 'disabled' : '' }}>
                <span class="fm-check-name">{{ $f->Food_Name }}</span>
                <span class="fm-check-price">₱{{ number_format($f->Food_Price, 2) }}</span>
                @if($f->Food_Status === 'unavailable')
                  <span class="fm-check-unavail">unavailable</span>
                @endif
              </label>
            @endforeach
            @if($prevCat !== null)</div>@endif
            @if($allFoods->isEmpty())
              <p class="fm-no-foods-hint">No food items found.</p>
            @endif
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
const BASE_FOOD_URL = '{{ url("employee/food") }}';
const BASE_SET_URL  = '{{ url("employee/food-sets") }}';

/* ═══════════════════════════════════════════
   FOOD CHECKLIST — Selected foods summary
═══════════════════════════════════════════ */
function updateFoodSummary(checklistId, summaryId) {
  const checklist = document.getElementById(checklistId);
  const summary   = document.getElementById(summaryId);
  if (!checklist || !summary) return;

  const checked = checklist.querySelectorAll('input[type="checkbox"]:checked');
  summary.innerHTML = '';

  if (checked.length === 0) {
    summary.innerHTML = '<span class="fm-summary-empty">No foods selected yet</span>';
    return;
  }

  const countEl = document.createElement('span');
  countEl.className = 'fm-summary-count';
  countEl.textContent = checked.length + ' food' + (checked.length !== 1 ? 's' : '') + ' selected:';
  summary.appendChild(countEl);

  checked.forEach(cb => {
    const label = cb.closest('.fm-check-item');
    const name  = label ? label.dataset.foodName : cb.value;
    const tag   = document.createElement('span');
    tag.className   = 'fm-summary-tag';
    tag.textContent = name;
    summary.appendChild(tag);
  });
}

function initFoodChecklist(checklistId, summaryId) {
  const checklist = document.getElementById(checklistId);
  if (!checklist) return;

  checklist.querySelectorAll('input[type="checkbox"]').forEach(cb => {
    cb.addEventListener('change', () => {
      const label = cb.closest('.fm-check-item');
      if (label) label.classList.toggle('fm-check-item--checked', cb.checked);
      updateFoodSummary(checklistId, summaryId);
    });
  });
  updateFoodSummary(checklistId, summaryId);
}

/* ── Add modal ── */
function openAddModal()  { document.getElementById('addModalOverlay').style.display = 'flex'; }
function closeAddModal() {
  document.getElementById('addModalOverlay').style.display = 'none';
  // Reset add-set food checklist
  const cl = document.getElementById('addFoodChecklist');
  if (cl) cl.querySelectorAll('input[type="checkbox"]').forEach(cb => {
    cb.checked = false;
    cb.closest('.fm-check-item')?.classList.remove('fm-check-item--checked');
  });
  // Reset meal time checkboxes
  document.querySelectorAll('#addMealTimeRow input').forEach(cb => cb.checked = false);
  // Reset purpose UI
  preloadPurposes('addPurposeUI', []);
  updateFoodSummary('addFoodChecklist', 'addFoodSummary');
}
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

/* ═══════════════════════════════════════════
   PURPOSE GROUP-TOGGLE UI
   Groups: All, Spiritual, General Event
   Each group button expands/collapses its sub-items.
   "All" checks a hidden input with value="all" and hides subs.
═══════════════════════════════════════════ */

// Group slug → array of purpose keys
const PURPOSE_GROUPS = {!! json_encode($purposeGroupsJs) !!};

function initPurposeUI(containerId) {
  const container = document.getElementById(containerId);
  if (!container) return;

  container.querySelectorAll('.fm-group-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      const group = btn.dataset.group;
      togglePurposeGroup(containerId, group);
    });
  });

  // When individual sub-items change, update group button state
  container.querySelectorAll('.add-purpose-check, .edit-purpose-check').forEach(cb => {
    cb.addEventListener('change', () => refreshGroupBtnState(containerId, cb.dataset.group));
  });
}

function togglePurposeGroup(containerId, group) {
  const container = document.getElementById(containerId);
  if (!container) return;

  if (group === 'all') {
    // Toggle All — if already active, deactivate; else activate
    const allCheckbox = container.querySelector('.add-purpose-all-check, .edit-purpose-all-check');
    const isActive = allCheckbox?.checked;

    // Deactivate all group buttons & hide all subs
    container.querySelectorAll('.fm-group-btn').forEach(b => b.classList.remove('fm-group-btn--active', 'fm-group-btn--partial'));
    container.querySelectorAll('.fm-purpose-sub').forEach(s => { s.style.display = 'none'; });
    // Uncheck all sub-items
    container.querySelectorAll('.add-purpose-check, .edit-purpose-check').forEach(cb => { cb.checked = false; });

    if (!isActive) {
      // Activate All
      if (allCheckbox) allCheckbox.checked = true;
      container.querySelector('[data-group="all"]')?.classList.add('fm-group-btn--active');
    } else {
      if (allCheckbox) allCheckbox.checked = false;
    }
    return;
  }

  // Non-"All" group: toggle sub panel visibility
  const subPanel = document.getElementById(`${containerId}-${group}`);
  const btn      = container.querySelector(`[data-group="${group}"]`);
  const isOpen   = subPanel?.style.display !== 'none';

  if (isOpen) {
    // Collapse: hide sub, uncheck all its items, remove active state
    if (subPanel) subPanel.style.display = 'none';
    container.querySelectorAll(`.add-purpose-check[data-group="${group}"], .edit-purpose-check[data-group="${group}"]`)
             .forEach(cb => { cb.checked = false; });
    btn?.classList.remove('fm-group-btn--active', 'fm-group-btn--partial');
  } else {
    // Expand: show sub, check all its items
    // Deactivate "All" first
    const allCheckbox = container.querySelector('.add-purpose-all-check, .edit-purpose-all-check');
    if (allCheckbox) allCheckbox.checked = false;
    container.querySelector('[data-group="all"]')?.classList.remove('fm-group-btn--active');

    if (subPanel) subPanel.style.display = 'block';
    container.querySelectorAll(`.add-purpose-check[data-group="${group}"], .edit-purpose-check[data-group="${group}"]`)
             .forEach(cb => { cb.checked = true; });
    btn?.classList.add('fm-group-btn--active');
    btn?.classList.remove('fm-group-btn--partial');
  }
}

function refreshGroupBtnState(containerId, group) {
  if (!group) return;
  const container = document.getElementById(containerId);
  if (!container) return;

  const checks = [...container.querySelectorAll(`.add-purpose-check[data-group="${group}"], .edit-purpose-check[data-group="${group}"]`)];
  const total   = checks.length;
  const checked = checks.filter(c => c.checked).length;
  const btn     = container.querySelector(`[data-group="${group}"]`);
  if (!btn) return;

  btn.classList.remove('fm-group-btn--active', 'fm-group-btn--partial');
  if (checked === total && total > 0) btn.classList.add('fm-group-btn--active');
  else if (checked > 0)               btn.classList.add('fm-group-btn--partial');
}

/** Pre-load an existing purposes array into a purpose UI container. */
function preloadPurposes(containerId, purposesArr) {
  const container = document.getElementById(containerId);
  if (!container) return;

  // Reset everything first
  container.querySelectorAll('.fm-group-btn').forEach(b => b.classList.remove('fm-group-btn--active', 'fm-group-btn--partial'));
  container.querySelectorAll('.fm-purpose-sub').forEach(s => { s.style.display = 'none'; });
  container.querySelectorAll('.add-purpose-check, .edit-purpose-check').forEach(cb => { cb.checked = false; });
  const allCheckbox = container.querySelector('.add-purpose-all-check, .edit-purpose-all-check');
  if (allCheckbox) allCheckbox.checked = false;

  if (!Array.isArray(purposesArr) || purposesArr.length === 0) return;

  if (purposesArr.includes('all')) {
    // Activate All button
    if (allCheckbox) allCheckbox.checked = true;
    container.querySelector('[data-group="all"]')?.classList.add('fm-group-btn--active');
    return;
  }

  // Check individual items and open their groups
  const openedGroups = new Set();
  container.querySelectorAll('.add-purpose-check, .edit-purpose-check').forEach(cb => {
    if (purposesArr.includes(cb.value)) {
      cb.checked = true;
      const grp = cb.dataset.group;
      if (grp && !openedGroups.has(grp)) {
        document.getElementById(`${containerId}-${grp}`)?.style && (document.getElementById(`${containerId}-${grp}`).style.display = 'block');
        openedGroups.add(grp);
      }
    }
  });

  // Refresh group button states
  openedGroups.forEach(g => refreshGroupBtnState(containerId, g));
}

/* ── Edit Set modal ── */
function openEditSetModal(id, name, mealTimes, purposes, price, status, selectedFoodIds) {
  document.getElementById('editSetForm').action = `${BASE_SET_URL}/${id}`;
  document.getElementById('editSetName').value   = name;
  document.getElementById('editSetPrice').value  = price;
  document.getElementById('editSetStatus').value = status;

  // Pre-check meal times
  const mtArr = Array.isArray(mealTimes) ? mealTimes : [mealTimes];
  document.querySelectorAll('#editMealTimeRow .edit-mealtime-check').forEach(cb => {
    cb.checked = mtArr.includes(cb.dataset.value);
  });

  // Pre-load purposes using the group UI
  const puArr = Array.isArray(purposes) ? purposes : [purposes];
  preloadPurposes('editPurposeUI', puArr);

  // Pre-check foods and refresh summary
  const foodIds   = Array.isArray(selectedFoodIds) ? selectedFoodIds.map(Number) : [];
  const checklist = document.getElementById('editFoodChecklist');
  checklist?.querySelectorAll('.edit-food-check').forEach(cb => {
    const isChecked = foodIds.includes(Number(cb.dataset.foodId));
    cb.checked = isChecked;
    cb.closest('.fm-check-item')?.classList.toggle('fm-check-item--checked', isChecked);
  });
  updateFoodSummary('editFoodChecklist', 'editFoodSummary');

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

/* Init food checklists on page load */
initFoodChecklist('addFoodChecklist',  'addFoodSummary');
initFoodChecklist('editFoodChecklist', 'editFoodSummary');

/* Init purpose group-toggle UIs */
initPurposeUI('addPurposeUI');
initPurposeUI('editPurposeUI');

/* ── Edit Set button — wired via data-* to avoid inline JSON encoding issues ── */
document.querySelectorAll('.js-edit-set-btn').forEach(btn => {
  btn.addEventListener('click', function () {
    openEditSetModal(
      Number(this.dataset.id),
      this.dataset.name,
      JSON.parse(this.dataset.mealTimes),
      JSON.parse(this.dataset.purposes),
      Number(this.dataset.price),
      this.dataset.status,
      JSON.parse(this.dataset.foodIds)
    );
  });
});
</script>
@endif

@endsection
