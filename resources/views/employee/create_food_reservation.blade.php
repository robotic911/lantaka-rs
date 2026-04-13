@extends('layouts.employee')
<link rel="stylesheet" href="{{ asset('css/client_food_options.css') }}">
@vite('resources/js/client_food_option.js')

@php
    $purpose     = strtolower($bookingData['purpose'] ?? '');
    $isSpiritual = in_array($purpose, ['retreat', 'recollection']);
@endphp

<script>
    window.IS_SPIRITUAL        = {{ $isSpiritual ? 'true' : 'false' }};
    window.RESERVATION_PURPOSE = "{{ $purpose }}";
    window.previousFoodSelections = @json($bookingData['prefill_food_selections'] ?? []);
    window.previousFoodEnabled    = @json($bookingData['prefill_food_enabled']    ?? []);
    window.previousMealEnabled    = @json($bookingData['prefill_meal_enabled']    ?? []);
    window.previousMealMode       = @json($bookingData['prefill_meal_mode']       ?? []);
    window.previousSetSelections  = @json($bookingData['prefill_set_selections']  ?? []);
    window.previousFoodUpgrades   = @json($bookingData['prefill_food_upgrades']   ?? []);
    window.foodAjaxUrl     = "{{ route('foods.ajax.list') }}";
    window.foodSetsAjaxUrl = "{{ route('foods.ajax.sets') }}?purpose={{ urlencode($bookingData['purpose'] ?? '') }}";
</script>

@section('content')
<main class="main-content">
    <form action="{{ route('employee.reservations.store') }}" method="POST" id="foodReservationForm" style="padding:20px">
        @csrf

        {{-- Hidden booking data --}}
        <input type="hidden" name="user_id"           value="{{ $bookingData['user_id'] }}">
        <input type="hidden" name="accommodation_id"  value="{{ $bookingData['accommodation_id'] }}">
        <input type="hidden" name="type"              value="{{ $bookingData['type'] }}">
        <input type="hidden" name="check_in"          value="{{ $bookingData['check_in'] }}">
        <input type="hidden" name="check_out"         value="{{ $bookingData['check_out'] }}">
        <input type="hidden" name="pax"   id="paxValue" value="{{ $bookingData['pax'] }}">
        <input type="hidden" name="purpose"           value="{{ $bookingData['purpose'] ?? '' }}">
        <input type="hidden" name="notes"             value="{{ $bookingData['notes'] ?? '' }}">
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

        {{-- Page heading --}}
        <div class="fo-page-heading">
            <h1 class="fo-page-title">Food Reservation</h1>
            <p class="fo-page-sub">
                @if($isSpiritual)
                    Retreat / Recollection package — ₱220.00/pax. Select a set for each meal time.
                @else
                    Select a meal set (₱150.00/pack) and optional snacks (₱80.00/pax) for the event.
                @endif
            </p>
        </div>

        @php
            $startDate = \Carbon\Carbon::parse($bookingData['check_in']);
            $endDate   = \Carbon\Carbon::parse($bookingData['check_out']);
            $period    = \Carbon\CarbonPeriod::create($startDate, $endDate);
        @endphp

        @foreach ($period as $venueDates)
            @php $dateKey = $venueDates->format('Y-m-d'); @endphp

            <div class="reservation-card" data-date="{{ $dateKey }}">

                <div class="card-header">
                    <div class="card-title-wrap">
                        <h2>{{ $bookingData['res_name'] ?? 'Venue' }}</h2>
                        <span class="reservation-date-text">Food Reservations for {{ $venueDates->format('F d, Y') }}</span>
                    </div>

                    <div class="fo-mode-toggle" data-date="{{ $dateKey }}">
                        <button type="button" class="fo-mode-btn fo-mode-btn--active" data-mode="set">{{ $isSpiritual ? 'Set' : 'Packed Meal' }}</button>
                        <button type="button" class="fo-mode-btn" data-mode="buffet">Buffet</button>
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

                <div class="fo-columns fo-columns--loading" data-date="{{ $dateKey }}">
                    <div class="fo-loading-msg">Loading menu…</div>
                </div>

            </div>
        @endforeach

        <div class="action-section">
            <div class="total-section">
                <span class="total-label">Food Total</span>
                <span class="total-amount" id="displayTotalPrice">₱ 0.00</span>
                <span class="total-pax">
                    × <span id="paxDisplay">{{ $bookingData['pax'] ?? 1 }}</span> pax
                </span>
            </div>
            <button type="submit" class="add-to-cart-btn">CONFIRM FOOD RESERVATION</button>
        </div>
    </form>
</main>

@if(!empty($bookingData['skip_food']))
{{-- User chose "Proceed without food" on the viewing page — auto-disable all food and submit. --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Disable food for every date card
    document.querySelectorAll('.food-enabled-input').forEach(function (inp) {
        inp.value = '0';
    });
    // Short delay to let client_food_option.js finish initialising, then submit
    setTimeout(function () {
        var form = document.getElementById('foodReservationForm');
        if (form) { form.requestSubmit ? form.requestSubmit() : form.submit(); }
    }, 300);
});
</script>
@endif

@endsection
