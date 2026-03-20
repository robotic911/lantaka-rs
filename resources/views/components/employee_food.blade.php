<link rel="stylesheet" href="{{ asset('css/employee_food.css') }}">

@php
  $isAdmin = auth()->user()->Account_Role === 'admin';
  $categories = [
    'rice'       => 'Rice',
    'set_viand'  => 'Set Viand',
    'sidedish'   => 'Side Dish',
    'drinks'     => 'Drinks',
    'desserts'   => 'Desserts',
    'snacks'     => 'Snacks',
    'other_viand'=> 'Other Viand',
  ];
@endphp

{{-- Pass role to JS --}}
<script>window._foodIsAdmin = {{ $isAdmin ? 'true' : 'false' }};</script>

<div class="food-modal-overlay" id="foodModalOverlay">
  <div class="food-modal" id="foodModal">

    {{-- Header --}}
    <div class="fm-header">
      <div class="fm-header-left">
        <span class="fm-icon">🍽</span>
        <div>
          <h2 class="fm-title">Food Menu</h2>
          <p class="fm-subtitle">{{ $foods->sum(fn($g) => $g->count()) }} items available</p>
        </div>
      </div>
      <div class="fm-header-right">
        @if($isAdmin)
          <button class="fm-add-btn" id="add_food_button">+ Add Food</button>
        @endif
        <button class="fm-close-btn" id="foodModalClose">✕</button>
      </div>
    </div>

    {{-- Category Tabs --}}
    <div class="fm-tabs" id="fmTabs">
      <button class="fm-tab active" data-cat="all">All</button>
      @foreach($categories as $key => $label)
        @if(isset($foods[$key]) && $foods[$key]->count() > 0)
          <button class="fm-tab" data-cat="{{ $key }}">
            {{ $label }}
            <span class="fm-tab-count">{{ $foods[$key]->count() }}</span>
          </button>
        @endif
      @endforeach
    </div>

    {{-- Menu Body --}}
    <div class="fm-body" id="fmBody">
      @foreach($categories as $key => $label)
        <div class="fm-section" data-section="{{ $key }}">
          <div class="fm-section-title">
            <span>{{ $label }}</span>
            <span class="fm-section-count">{{ isset($foods[$key]) ? $foods[$key]->count() : 0 }} items</span>
          </div>

          @if(isset($foods[$key]) && $foods[$key]->count() > 0)
            <div class="fm-list">
              @foreach($foods[$key] as $food)
                <div
                  class="fm-item {{ $food->Food_Status === 'unavailable' ? 'fm-item--unavailable' : '' }} {{ $isAdmin ? 'fm-item--editable' : '' }}"
                  data-id="{{ $food->Food_ID }}"
                  data-name="{{ $food->Food_Name }}"
                  data-status="{{ $food->Food_Status }}"
                  data-type="{{ $food->Food_Category }}"
                  data-price="{{ $food->Food_Price }}"
                >
                  <div class="fm-item-left">
                    <span class="fm-item-dot {{ $food->Food_Status === 'unavailable' ? 'dot--off' : 'dot--on' }}"></span>
                    <span class="fm-item-name">{{ $food->Food_Name }}</span>
                  </div>
                  <div class="fm-item-right">
                    <span class="fm-item-price">₱ {{ number_format($food->Food_Price, 2) }}</span>
                    @if($food->Food_Status === 'unavailable')
                      <span class="fm-badge fm-badge--off">Unavailable</span>
                    @else
                      <span class="fm-badge fm-badge--on">Available</span>
                    @endif
                    @if($isAdmin)
                      <span class="fm-edit-hint">Edit ›</span>
                    @endif
                  </div>
                </div>
              @endforeach
            </div>
          @else
            <p class="fm-empty">No items in this category yet.</p>
          @endif
        </div>
      @endforeach
    </div>

  </div>
</div>

@if($isAdmin)
  <x-employee_add_food />
  <x-employee_update_food />
@endif


<style>
/* ================================
   FOOD MENU OVERLAY + MODAL
================================ */
.food-modal-overlay {
  display: none;
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.45);
  backdrop-filter: blur(3px);
  z-index: 1000;
  align-items: center;
  justify-content: center;
}
.food-modal-overlay.show {
  display: flex;
}

.food-modal {
  width: 94%;
  max-width: 780px;
  max-height: 90vh;
  background: #ffffff;
  border-radius: 16px;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  box-shadow: 0 24px 64px rgba(0,0,0,0.18);
  transform: translateY(12px);
  opacity: 0;
  transition: transform .22s ease, opacity .22s ease;
}
.food-modal.show {
  transform: translateY(0);
  opacity: 1;
}

/* ================================
   HEADER
================================ */
.fm-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 20px 24px 16px;
  border-bottom: 1px solid #f0f0f0;
  flex-shrink: 0;
}
.fm-header-left {
  display: flex;
  align-items: center;
  gap: 12px;
}
.fm-icon {
  font-size: 28px;
  line-height: 1;
}
.fm-title {
  margin: 0;
  font-size: 20px;
  font-weight: 700;
  color: #1e3a5f;
  line-height: 1.2;
}
.fm-subtitle {
  margin: 2px 0 0;
  font-size: 12px;
  color: #888;
  font-weight: 400;
}
.fm-header-right {
  display: flex;
  align-items: center;
  gap: 10px;
}
.fm-add-btn {
  padding: 8px 16px;
  background: #1e3a5f;
  color: #fff;
  border: none;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  transition: background .2s;
}
.fm-add-btn:hover { background: #162d4a; }
.fm-close-btn {
  width: 32px;
  height: 32px;
  border: none;
  background: #f5f5f5;
  border-radius: 8px;
  font-size: 14px;
  cursor: pointer;
  color: #555;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background .2s, color .2s;
}
.fm-close-btn:hover { background: #ffe0e0; color: #c00; }

/* ================================
   CATEGORY TABS
================================ */
.fm-tabs {
  display: flex;
  gap: 4px;
  padding: 12px 24px 0;
  overflow-x: auto;
  flex-shrink: 0;
  scrollbar-width: none;
  border-bottom: 1px solid #f0f0f0;
}
.fm-tabs::-webkit-scrollbar { display: none; }

.fm-tab {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 7px 14px 10px;
  border: none;
  background: transparent;
  font-size: 13px;
  font-weight: 500;
  color: #888;
  cursor: pointer;
  white-space: nowrap;
  border-bottom: 2px solid transparent;
  margin-bottom: -1px;
  transition: color .18s, border-color .18s;
}
.fm-tab:hover { color: #1e3a5f; }
.fm-tab.active {
  color: #1e3a5f;
  font-weight: 700;
  border-bottom-color: #1e3a5f;
}
.fm-tab-count {
  background: #eef2ff;
  color: #1e3a5f;
  border-radius: 999px;
  padding: 1px 7px;
  font-size: 11px;
  font-weight: 700;
}
.fm-tab.active .fm-tab-count {
  background: #1e3a5f;
  color: #fff;
}

/* ================================
   MENU BODY + SECTIONS
================================ */
.fm-body {
  flex: 1;
  overflow-y: auto;
  padding: 20px 24px;
  display: flex;
  flex-direction: column;
  gap: 28px;
}

.fm-section {
  display: flex;
  flex-direction: column;
  gap: 8px;
}
.fm-section.hidden { display: none; }

.fm-section-title {
  display: flex;
  align-items: center;
  justify-content: space-between;
  font-size: 11px;
  font-weight: 700;
  letter-spacing: .08em;
  text-transform: uppercase;
  color: #aaa;
  padding-bottom: 6px;
  border-bottom: 1px solid #f2f2f2;
}
.fm-section-count {
  font-weight: 500;
  font-size: 11px;
  color: #bbb;
  text-transform: none;
  letter-spacing: 0;
}

/* ================================
   FOOD ITEMS (LIST ROWS)
================================ */
.fm-list {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.fm-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 10px 12px;
  border-radius: 8px;
  transition: background .15s;
  gap: 12px;
}
.fm-item--editable {
  cursor: pointer;
}
.fm-item--editable:hover {
  background: #f5f8ff;
}
.fm-item--unavailable {
  opacity: 0.5;
}
.fm-item--unavailable.fm-item--editable:hover {
  background: #fafafa;
}

.fm-item-left {
  display: flex;
  align-items: center;
  gap: 10px;
  min-width: 0;
}
.fm-item-dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  flex-shrink: 0;
}
.dot--on  { background: #22c55e; }
.dot--off { background: #d1d5db; }

.fm-item-name {
  font-size: 14px;
  font-weight: 500;
  color: #1a202c;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.fm-item--unavailable .fm-item-name { color: #9ca3af; }

.fm-item-right {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-shrink: 0;
}
.fm-item-price {
  font-size: 14px;
  font-weight: 600;
  color: #1e3a5f;
}
.fm-item--unavailable .fm-item-price { color: #9ca3af; }

.fm-badge {
  font-size: 11px;
  font-weight: 600;
  border-radius: 999px;
  padding: 3px 9px;
}
.fm-badge--on  { background: #dcfce7; color: #166534; }
.fm-badge--off { background: #fee2e2; color: #991b1b; }

.fm-edit-hint {
  font-size: 11px;
  color: #9ca3af;
  font-weight: 500;
  opacity: 0;
  transition: opacity .15s;
}
.fm-item--editable:hover .fm-edit-hint { opacity: 1; }

.fm-empty {
  font-size: 13px;
  color: #bbb;
  padding: 10px 0 4px;
  font-style: italic;
}

/* ================================
   RESPONSIVE
================================ */
@media (max-width: 600px) {
  .fm-header { padding: 16px 16px 12px; }
  .fm-tabs   { padding: 10px 16px 0; }
  .fm-body   { padding: 16px; gap: 22px; }
  .fm-badge  { display: none; }
  .food-modal { max-width: 100%; border-radius: 12px 12px 0 0; align-self: flex-end; max-height: 85vh; }
}
</style>
