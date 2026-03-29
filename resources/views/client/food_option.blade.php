@extends('layouts.client')

<link rel="stylesheet" href="{{ asset('css/client_food_options.css') }}">
@vite('resources/js/client_food_option.js')

@php
    // Pull saved edit selections from session (only set when coming from Edit cart)
    $editFoodSelections     = session()->pull('edit_food_selections', []);
    $editFoodEnabled        = session()->pull('edit_food_enabled',    []);
    $editMealEnabled        = session()->pull('edit_meal_enabled',    []);
    $editMealMode           = session()->pull('edit_meal_mode',       []);   {{-- 'individual'|'set' --}}
    $editSetSelections      = session()->pull('edit_set_selections',  []);
@endphp
<script>
    window.previousFoodSelections = @json($editFoodSelections);
    window.previousFoodEnabled    = @json($editFoodEnabled);
    window.previousMealEnabled    = @json($editMealEnabled);
    window.previousMealMode       = @json($editMealMode);
    window.previousSetSelections  = @json($editSetSelections);
    window.foodAjaxUrl     = "{{ route('foods.ajax.list') }}";
    window.foodSetsAjaxUrl = "{{ route('foods.ajax.sets') }}";
</script>

@section('content')
<main class="main-content">
    <form action="{{ route('checkout') }}" method="GET" id="foodReservationForm">
        <input type="hidden" name="accommodation_id" value="{{ $bookingData['accommodation_id'] }}">
        <input type="hidden" name="res_name"         value="{{ $bookingData['res_name'] }}">
        <input type="hidden" name="type"             value="{{ $bookingData['type'] }}">
        <input type="hidden" name="check_in"         value="{{ $bookingData['check_in'] }}">
        <input type="hidden" name="check_out"        value="{{ $bookingData['check_out'] }}">
        <input type="hidden" name="pax" id="paxValue" value="{{ $bookingData['pax'] ?? 1 }}">
        <input type="hidden" name="purpose"          value="{{ $bookingData['purpose'] ?? '' }}">

        {{-- ── Back button ── --}}
        <div class="back-section">
            <button type="button" class="back-btn" onclick="window.history.back();">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                    <path d="M15 10H5M5 10L10 15M5 10L10 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span>Back</span>
            </button>
        </div>

        {{-- ── Page heading ── --}}
        <div class="fo-page-heading">
            <h1 class="fo-page-title">Food Reservation</h1>
            <p class="fo-page-sub">Select food for each meal time on each day of your stay.</p>
        </div>

        {{-- ── PAX POLICY NOTICE ── --}}
        <div class="fo-pax-policy">
            <div class="fo-pax-policy__icon">ℹ</div>
            <div class="fo-pax-policy__body">
                <strong>Food Service Policy</strong>
                <ul class="fo-pax-policy__list">
                    <li>Food service is available for <strong>venues only</strong> (not applicable to room bookings).</li>
                    <li>Minimum of <strong>10 pax</strong> is required to avail food service.</li>
                    <li>Maximum of <strong>500 pax</strong> per meal service.</li>
                    <li>Prices shown are <strong>per pax</strong>. The total will be multiplied by your group size ({{ $bookingData['pax'] ?? 1 }} pax).</li>
                    <li>You may choose between <strong>Individual Items</strong> (select per food category) or a <strong>Food Set</strong> (pre-arranged package) for each meal time.</li>
                    <li>Selections can be toggled per meal time — disable meals you do not need.</li>
                </ul>
            </div>
        </div>

        @php
            $startDate = \Carbon\Carbon::parse($bookingData['check_in']);
            $endDate   = \Carbon\Carbon::parse($bookingData['check_out']);
            $period    = \Carbon\CarbonPeriod::create($startDate, $endDate);

            $mealTypes = [
                'breakfast' => 'Breakfast',
                'am_snack'  => 'AM Snack',
                'lunch'     => 'Lunch',
                'pm_snack'  => 'PM Snack',
                'dinner'    => 'Dinner',
            ];

            $categories = [
                'rice'        => 'Rice',
                'set_viand'   => 'Set Viand',
                'sidedish'    => 'Sidedish',
                'drinks'      => 'Drinks',
                'desserts'    => 'Desserts',
                'other_viand' => 'Other Viand',
                'snacks'      => 'Snack',
            ];
        @endphp

        @foreach ($period as $venueDates)
            @php $dateKey = $venueDates->format('Y-m-d'); @endphp

            <div class="reservation-card" data-date="{{ $dateKey }}">
                <div class="card-header">
                    <div class="card-title-wrap">
                        <h2>Venue — {{ $bookingData['res_name'] }}</h2>
                        <span class="reservation-date-text">Food Reservation for {{ $venueDates->format('F d, Y') }}</span>
                    </div>

                    <div class="food-toggle-section">
                        <div class="toggle-label">Include Food</div>
                        <div class="toggle-buttons">
                            <button type="button" class="toggle-btn" data-toggle="no">No</button>
                            <button type="button" class="toggle-btn active" data-toggle="yes">Yes</button>
                        </div>
                    </div>
                </div>

                <input type="hidden" name="food_enabled[{{ $dateKey }}]" value="1" class="food-enabled-input">

                <div class="meals-container">
                    <table class="food-table">
                        <thead>
                            <tr>
                                <th class="meal-column">Meal Time</th>
                                <th class="meal-mode-col">Mode</th>
                                {{-- Individual-food columns (hidden when set-mode is active for a row) --}}
                                @foreach($categories as $categoryKey => $categoryLabel)
                                    <th class="indiv-col">{{ $categoryLabel }}</th>
                                @endforeach
                                {{-- Food-set column (hidden when individual-mode is active) --}}
                                <th class="set-col" style="display:none;">Food Set</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($mealTypes as $mealKey => $mealLabel)
                                <tr class="meal-row" data-meal-row="{{ $dateKey }}-{{ $mealKey }}" data-mode="individual">
                                    {{-- Meal label + include toggle --}}
                                    <td class="meal-label-cell">
                                        <div class="meal-header">
                                            <span class="meal-name">{{ $mealLabel }}</span>
                                            <label class="meal-toggle-wrap">
                                                <input
                                                    type="checkbox"
                                                    class="meal-toggle-checkbox"
                                                    data-date="{{ $dateKey }}"
                                                    data-meal="{{ $mealKey }}"
                                                    checked
                                                >
                                                <span class="meal-toggle-text">Include</span>
                                            </label>
                                            <input type="hidden"
                                                name="meal_enabled[{{ $dateKey }}][{{ $mealKey }}]"
                                                value="1"
                                                class="meal-enabled-hidden">
                                        </div>
                                    </td>

                                    {{-- Mode switcher: Individual ↔ Set --}}
                                    <td class="meal-mode-col meal-mode-cell">
                                        <div class="meal-mode-switcher">
                                            <button type="button"
                                                class="mode-btn mode-btn--indiv active"
                                                data-date="{{ $dateKey }}"
                                                data-meal="{{ $mealKey }}"
                                                data-mode="individual"
                                                title="Choose individual food items per category">
                                                Items
                                            </button>
                                            <button type="button"
                                                class="mode-btn mode-btn--set"
                                                data-date="{{ $dateKey }}"
                                                data-meal="{{ $mealKey }}"
                                                data-mode="set"
                                                title="Choose a pre-arranged food set package">
                                                Set
                                            </button>
                                        </div>
                                        {{-- Hidden: tracks current mode for this meal --}}
                                        <input type="hidden"
                                            name="meal_mode[{{ $dateKey }}][{{ $mealKey }}]"
                                            value="individual"
                                            class="meal-mode-hidden">
                                    </td>

                                    {{-- Individual food selects (one per category) --}}
                                    @foreach($categories as $categoryKey => $categoryLabel)
                                        <td class="food-cell indiv-cell">
                                            <select
                                                name="food_selections[{{ $dateKey }}][{{ $mealKey }}][{{ $categoryKey }}]"
                                                class="food-select"
                                                data-category="{{ $categoryKey }}"
                                            >
                                                <option value="">Loading…</option>
                                            </select>
                                        </td>
                                    @endforeach

                                    {{-- Food-set select (hidden by default) --}}
                                    <td class="food-cell set-cell" style="display:none;">
                                        <select
                                            name="food_set_selection[{{ $dateKey }}][{{ $mealKey }}]"
                                            class="food-set-select"
                                            data-date="{{ $dateKey }}"
                                            data-meal="{{ $mealKey }}"
                                            disabled
                                        >
                                            <option value="">Loading sets…</option>
                                        </select>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endforeach

        {{-- ── Sticky footer: total + submit ── --}}
        <div class="action-section">
            <div class="total-section">
                <span class="total-label">Food Total</span>
                <span class="total-amount" id="displayTotalPrice">₱ 0.00</span>
                <span class="total-pax">
                    × <span id="paxDisplay">{{ $bookingData['pax'] ?? 1 }}</span> pax
                </span>
            </div>
            <button type="submit" class="add-to-cart-btn">ADD TO BOOKING CART</button>
        </div>
    </form>
</main>
@endsection

<style>
/* ── Page heading ── */
.fo-page-heading {
    padding: 0 0 4px;
    margin-bottom: 4px;
}
.fo-page-title {
    font-size: 1.55rem;
    font-weight: 700;
    color: #1a1a2e;
    margin: 0 0 4px;
}
.fo-page-sub {
    font-size: 0.85rem;
    color: #6b7280;
    margin: 0;
}

/* ── PAX policy box ── */
.fo-pax-policy {
    display: flex;
    gap: 14px;
    background: #fffbeb;
    border: 1px solid #fcd34d;
    border-radius: 10px;
    padding: 14px 18px;
    margin-bottom: 22px;
    align-items: flex-start;
}
.fo-pax-policy__icon {
    font-size: 1.1rem;
    color: #d97706;
    flex-shrink: 0;
    margin-top: 1px;
}
.fo-pax-policy__body {
    font-size: 0.82rem;
    color: #78350f;
    line-height: 1.5;
}
.fo-pax-policy__body strong { color: #92400e; }
.fo-pax-policy__list {
    margin: 6px 0 0 16px;
    padding: 0;
}
.fo-pax-policy__list li { margin-bottom: 3px; }

/* ── Card header ── */
.card-title-wrap {
    display: flex;
    flex-direction: column;
    gap: 2px;
    align-items: flex-start;
}
.reservation-date-text {
    font-size: 14px;
    color: #666;
}

/* ── Meal mode switcher ── */
.meal-mode-col { width: 100px; min-width: 90px; }
.meal-mode-cell { vertical-align: middle; }
.meal-mode-switcher {
    display: flex;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    overflow: hidden;
    width: fit-content;
}
.mode-btn {
    padding: 5px 10px;
    font-size: 0.72rem;
    font-weight: 600;
    border: none;
    background: #f9fafb;
    color: #6b7280;
    cursor: pointer;
    transition: background 0.15s, color 0.15s;
    font-family: inherit;
}
.mode-btn + .mode-btn { border-left: 1px solid #d1d5db; }
.mode-btn.active { background: #2563eb; color: #fff; }
.mode-btn:disabled { opacity: 0.4; cursor: not-allowed; }

/* ── Food table ── */
.food-table {
    width: 100%;
    border-collapse: collapse;
    text-align: left;
    table-layout: fixed;
    margin-top: 15px;
    background: #fff;
}
.food-table th,
.food-table td {
    border: 1px solid #d9d9d9;
    padding: 10px;
    vertical-align: middle;
}
.food-table th {
    background: #f5f5f5;
    font-weight: 700;
    font-size: 14px;
    text-align: center;
}
.meal-column {
    width: 160px;
    min-width: 160px;
}
.meal-label-cell {
    background: #fafafa;
    width: 160px;
}
.meal-header {
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.meal-name {
    font-weight: 700;
    font-size: 15px;
    color: #222;
}
.meal-toggle-wrap {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    color: #666;
    cursor: pointer;
    width: fit-content;
}
.meal-toggle-wrap input { cursor: pointer; }

/* ── Food cells ── */
.food-cell {
    min-width: 130px;
    background: #fff;
}
.set-cell { min-width: 220px; }
.food-select,
.food-set-select {
    width: 100%;
    min-width: 110px;
    padding: 8px 10px;
    border: 1px solid #d6d6d6;
    border-radius: 8px;
    background: #fff;
    font-size: 13px;
    color: #333;
    outline: none;
}
.food-select:focus,
.food-set-select:focus {
    border-color: #7aa7e0;
    box-shadow: 0 0 0 3px rgba(122, 167, 224, 0.12);
}

/* ── Disabled states ── */
.cell-disabled { background: #f2f2f2 !important; }
.cell-disabled .food-select,
.cell-disabled .food-set-select {
    background: #ebebeb;
    color: #999;
    cursor: not-allowed;
    border-color: #dddddd;
}
.meal-row.row-disabled td { background: #efefef !important; }
.meal-row.row-disabled .meal-name,
.meal-row.row-disabled .meal-toggle-text { color: #9a9a9a; }
.meal-row.row-disabled .food-select,
.meal-row.row-disabled .food-set-select {
    background: #e5e5e5;
    color: #9c9c9c;
    border-color: #d0d0d0;
    cursor: not-allowed;
}

.reservation-card.food-disabled-card { opacity: 0.85; }
.reservation-card.food-disabled-card .food-table,
.reservation-card.food-disabled-card .meal-label-cell,
.reservation-card.food-disabled-card .food-cell { background: #f1f1f1; }

/* ── Sticky footer ── */
.action-section {
    position: sticky;
    bottom: 0;
    background: #fff;
    border-top: 2px solid #e5e7eb;
    padding: 14px 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    z-index: 50;
    box-shadow: 0 -4px 16px rgba(0,0,0,0.07);
}
.total-section {
    display: flex;
    align-items: baseline;
    gap: 8px;
}
.total-label { font-size: 0.85rem; color: #6b7280; }
.total-amount { font-size: 1.4rem; font-weight: 700; color: #111; }
.total-pax    { font-size: 0.78rem; color: #9ca3af; }

/* ── Scroll ── */
.meals-container { overflow-x: auto; }
@media (max-width: 1024px) {
    .food-table { min-width: 1200px; }
}
</style>
