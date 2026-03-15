@extends('layouts.employee')
<link rel="stylesheet" href="{{ asset('css/client_food_options.css') }}">
@vite('resources/js/client_food_option.js')

<script>
    {{-- Pre-fill food dropdowns when editing an existing venue reservation --}}
    window.previousFoodSelections = @json($bookingData['prefill_food_selections'] ?? []);
    window.previousFoodEnabled    = @json($bookingData['prefill_food_enabled']    ?? []);
    window.previousMealEnabled    = @json($bookingData['prefill_meal_enabled']    ?? []);
</script>

@section('content')
<main class="main-content">
    <form action="{{ route('employee.reservations.store') }}" method="POST" id="foodReservationForm">
        @csrf

        {{-- Hidden booking data --}}
        <input type="hidden" name="user_id"           value="{{ $bookingData['user_id'] }}">
        <input type="hidden" name="accommodation_id"  value="{{ $bookingData['accommodation_id'] }}">
        <input type="hidden" name="type"              value="{{ $bookingData['type'] }}">
        <input type="hidden" name="check_in"          value="{{ $bookingData['check_in'] }}">
        <input type="hidden" name="check_out"         value="{{ $bookingData['check_out'] }}">
        <input type="hidden" name="pax" id="paxValue" value="{{ $bookingData['pax'] }}">
        @if(!empty($bookingData['venue_reservation_id']))
        <input type="hidden" name="venue_reservation_id" value="{{ $bookingData['venue_reservation_id'] }}">
        @endif

        <div class="back-section">
            <button type="button" class="back-btn" onclick="window.history.back();">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                    <path d="M15 10H5M5 10L10 15M5 10L10 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span>Back</span>
            </button>
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
                        <h2>Venue - {{ $bookingData['res_name'] ?? 'Venue' }}</h2>
                        <span class="reservation-date-text">Food Reservations for {{ $venueDates->format('F d, Y') }}</span>
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
                                @foreach($categories as $categoryKey => $categoryLabel)
                                    <th>{{ $categoryLabel }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($mealTypes as $mealKey => $mealLabel)
                                <tr class="meal-row" data-meal-row="{{ $dateKey }}-{{ $mealKey }}">
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

                                            <input
                                                type="hidden"
                                                name="meal_enabled[{{ $dateKey }}][{{ $mealKey }}]"
                                                value="1"
                                                class="meal-enabled-hidden"
                                            >
                                        </div>
                                    </td>

                                    @foreach($categories as $categoryKey => $categoryLabel)
                                        <td class="food-cell">
                                            <select
                                                name="food_selections[{{ $dateKey }}][{{ $mealKey }}][{{ $categoryKey }}]"
                                                class="food-select"
                                                data-category="{{ $categoryKey }}"
                                            >
                                                <option value="">Loading...</option>
                                            </select>
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endforeach

        <div class="action-section" style="
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
        ">
            <div class="total-section" style="display:flex; align-items:baseline; gap:8px;">
                <span class="total-label" style="font-size:0.85rem; color:#6b7280;">Food Total</span>
                <span class="total-amount" id="displayTotalPrice" style="font-size:1.4rem; font-weight:700; color:#111;">₱ 0.00</span>
                <span style="font-size:0.78rem; color:#9ca3af;">
                    × <span id="paxDisplay">{{ $bookingData['pax'] ?? 1 }}</span> pax
                </span>
            </div>
            <button type="submit" class="add-to-cart-btn">CONFIRM FOOD RESERVATION</button>
        </div>
    </form>
</main>
@endsection

<style>
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
        width: 180px;
        min-width: 180px;
    }

    .meal-label-cell {
        background: #fafafa;
        width: 180px;
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

    .meal-toggle-wrap input {
        cursor: pointer;
    }

    .food-cell {
        min-width: 150px;
        background: #fff;
    }

    .food-select {
        width: 100%;
        min-width: 120px;
        padding: 10px 12px;
        border: 1px solid #d6d6d6;
        border-radius: 8px;
        background: #fff;
        font-size: 13px;
        color: #333;
        outline: none;
    }

    .food-select:focus {
        border-color: #7aa7e0;
        box-shadow: 0 0 0 3px rgba(122, 167, 224, 0.12);
    }

    .cell-disabled {
        background: #f2f2f2 !important;
    }

    .cell-disabled .food-select {
        background: #ebebeb;
        color: #999;
        cursor: not-allowed;
        border-color: #dddddd;
    }

    .meal-row.row-disabled td {
        background: #efefef !important;
    }

    .meal-row.row-disabled .meal-name,
    .meal-row.row-disabled .meal-toggle-text {
        color: #9a9a9a;
    }

    .meal-row.row-disabled .food-select {
        background: #e5e5e5;
        color: #9c9c9c;
        border-color: #d0d0d0;
        cursor: not-allowed;
    }

    .reservation-card.food-disabled-card {
        opacity: 0.85;
    }

    .reservation-card.food-disabled-card .food-table,
    .reservation-card.food-disabled-card .meal-label-cell,
    .reservation-card.food-disabled-card .food-cell {
        background: #f1f1f1;
    }

    .meals-container {
        overflow-x: auto;
    }

    @media (max-width: 1024px) {
        .food-table {
            min-width: 1400px;
        }
    }
</style>

<script>
    window.foodAjaxUrl = "{{ route('foods.ajax.list') }}";
</script>
