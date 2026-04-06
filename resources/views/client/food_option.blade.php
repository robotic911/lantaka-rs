@extends('layouts.client')

<link rel="stylesheet" href="{{ asset('css/client_food_options.css') }}">
@vite('resources/js/client_food_option.js')

@php
    // Access control: food reservation is only for venue bookings
    if (($bookingData['type'] ?? '') !== 'venue') {
        redirect()->route('checkout')->send();
        exit;
    }

    $purpose      = strtolower($bookingData['purpose'] ?? '');
    $isSpiritual  = in_array($purpose, ['retreat', 'recollection']);

    // Restore edit-cart state
    $editFoodSelections = session()->pull('edit_food_selections', []);
    $editFoodEnabled    = session()->pull('edit_food_enabled', []);
    $editMealEnabled    = session()->pull('edit_meal_enabled', []);
    $editMealMode       = session()->pull('edit_meal_mode', []);
    $editSetSelections  = session()->pull('edit_set_selections', []);
@endphp

<script>
    window.IS_SPIRITUAL        = {{ $isSpiritual ? 'true' : 'false' }};
    window.RESERVATION_PURPOSE = "{{ $purpose }}";
    window.previousFoodSelections = @json($editFoodSelections);
    window.previousFoodEnabled    = @json($editFoodEnabled);
    window.previousMealEnabled    = @json($editMealEnabled);
    window.previousMealMode       = @json($editMealMode);
    window.previousSetSelections  = @json($editSetSelections);
    window.foodAjaxUrl     = "{{ route('foods.ajax.list') }}";
    window.foodSetsAjaxUrl = "{{ route('foods.ajax.sets') }}?purpose={{ urlencode($bookingData['purpose'] ?? '') }}";
    window.BOOKING_PAX         = {{ (int)($bookingData['pax'] ?? 1) }};
    window.FOOD_MIN_PAX        = 30; {{-- Minimum pax required for food reservation --}}
</script>

@section('content')
<main class="main-content" style="padding: 20px;">
    <form action="{{ route('checkout') }}" method="GET" id="foodReservationForm">
        <input type="hidden" name="accommodation_id" value="{{ $bookingData['accommodation_id'] }}">
        <input type="hidden" name="res_name"         value="{{ $bookingData['res_name'] }}">
        <input type="hidden" name="type"             value="{{ $bookingData['type'] }}">
        <input type="hidden" name="check_in"         value="{{ $bookingData['check_in'] }}">
        <input type="hidden" name="check_out"        value="{{ $bookingData['check_out'] }}">
        <input type="hidden" name="pax"   id="paxValue" value="{{ $bookingData['pax'] ?? 1 }}">
        <input type="hidden" name="purpose"          value="{{ $bookingData['purpose'] ?? '' }}">

        {{-- Back button --}}
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
                    Select a meal set (₱150.00/pack) and optional snacks (₱80.00/pax) for your event.
                @endif
            </p>
        </div>

        @php
            $startDate = \Carbon\Carbon::parse($bookingData['check_in']);
            $endDate   = \Carbon\Carbon::parse($bookingData['check_out']);
            $period    = \Carbon\CarbonPeriod::create($startDate, $endDate);
        @endphp

        {{-- Business logic guide --}}
        <div class="fo-guide-panel">
            <button type="button" class="fo-guide-toggle" id="foGuideToggle">
                <span>📋 Food Reservation Guide</span>
                <span class="fo-guide-caret">▾</span>
            </button>
            <div class="fo-guide-body" id="foGuideBody">

                @if($isSpiritual)
                {{-- Spiritual / retreat guide --}}
                <div class="fo-guide-grid fo-guide-grid--spiritual">
                    <div class="fo-guide-item">
                        <span class="fo-guide-icon">🍱</span>
                        <div>
                            <strong>Food Sets</strong>
                            <p>All served with rice (plain or fried).</p>
                        </div>
                    </div>
                    <div class="fo-guide-item">
                        <span class="fo-guide-icon">☕</span>
                        <div>
                            <strong>Breakfast</strong>
                            <p>Served with drinks (hot coffee, tea or chocolate drink), and a slice of fruit in season.</p>
                        </div>
                    </div>
                    <div class="fo-guide-item">
                        <span class="fo-guide-icon">🥤</span>
                        <div>
                            <strong>Lunch / Dinner</strong>
                            <p>Served with 1 round softdrinks, and dessert or fruit.</p>
                        </div>
                    </div>
                </div>
                @else
                {{-- General event guide --}}
                <div class="fo-guide-grid">
                    <div class="fo-guide-item">
                        <span class="fo-guide-icon">🍚</span>
                        <div>
                            <strong>Rice</strong>
                            <p>Choose between fried rice or plain rice for your set.</p>
                        </div>
                    </div>
                    <div class="fo-guide-item">
                        <span class="fo-guide-icon">🥤</span>
                        <div>
                            <strong>Softdrinks or Juice</strong>
                            <p>Every set includes one drink — pick softdrinks or juice.</p>
                        </div>
                    </div>
                    <div class="fo-guide-item fo-guide-item--upgrade">
                        <span class="fo-guide-icon">🔄</span>
                        <div>
                            <strong>Switch Viand <span class="fo-guide-price">+₱20</span></strong>
                            <p>Replace the included viand with another viand of your choice.</p>
                        </div>
                    </div>
                    <div class="fo-guide-item fo-guide-item--upgrade">
                        <span class="fo-guide-icon">➕</span>
                        <div>
                            <strong>Extra Viand <span class="fo-guide-price">+₱40 each</span></strong>
                            <p>Add one or more additional viands from the menu.</p>
                        </div>
                    </div>
                    <div class="fo-guide-item fo-guide-item--upgrade">
                        <span class="fo-guide-icon">🍮</span>
                        <div>
                            <strong>Add Dessert <span class="fo-guide-price">+₱20</span></strong>
                            <p>Add a dessert of your choice to any set.</p>
                        </div>
                    </div>
                </div>
                @endif

            </div>
        </div>

        @foreach ($period as $venueDates)
            @php $dateKey = $venueDates->format('Y-m-d'); @endphp

            <div class="reservation-card" data-date="{{ $dateKey }}">

                {{-- Card header --}}
                <div class="card-header">
                    <div class="card-title-wrap">
                        <h2>{{ $bookingData['res_name'] }}</h2>
                        <span class="reservation-date-text">Food Reservations for {{ $venueDates->format('F d, Y') }}</span>
                    </div>

                    {{-- Individual / Set toggle (all reservations) --}}
                    <div class="fo-mode-toggle" data-date="{{ $dateKey }}">
                    <button type="button" class="fo-mode-btn fo-mode-btn--active" data-mode="set">Set</button>
                        <button type="button" class="fo-mode-btn" data-mode="individual">Individual Order</button>
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

                {{-- Columns populated by JS --}}
                <div class="fo-columns fo-columns--loading" data-date="{{ $dateKey }}">
                    <div class="fo-loading-msg">Loading menu…</div>
                </div>

            </div>
        @endforeach

        {{-- Sticky footer --}}
        <div class="action-section">
            <div class="total-section">
                <span class="total-label">Food Total</span>
                <span class="total-amount" id="displayTotalPrice">₱ 0.00</span>
                <span class="total-pax">
                    × <span id="paxDisplay">{{ $bookingData['pax'] ?? 1 }}</span> pax
                </span>
            </div>
            <button type="button" id="addToCartBtn" class="add-to-cart-btn">ADD TO BOOKING CART</button>
        </div>
    </form>

    {{-- No-food-selected confirmation modal --}}
    <div id="noFoodModal" class="nfm-overlay" style="display:none;" role="dialog" aria-modal="true" aria-labelledby="nfmTitle">
        <div class="nfm-box">
            <div class="nfm-icon">🍽️</div>
            <h3 id="nfmTitle" class="nfm-title">No food selected</h3>
            <p class="nfm-body">You haven't added any food to this reservation. Would you like to go back and select food, or proceed without food?</p>
            <div class="nfm-actions">
                <button type="button" id="nfmGoBack" class="nfm-btn nfm-btn--secondary">← Go Back</button>
                <button type="button" id="nfmProceed" class="nfm-btn nfm-btn--primary">Proceed without food</button>
            </div>
        </div>
    </div>
</main>
<style>
/* ── No-Food Modal ─────────────────────────────────────────── */
.nfm-overlay {
  position: fixed; inset: 0;
  background: rgba(0,0,0,.45);
  display: flex; align-items: center; justify-content: center;
  z-index: 9999;
  animation: nfmFade .18s ease;
}
@keyframes nfmFade { from { opacity:0; } to { opacity:1; } }
.nfm-box {
  background: #fff;
  border-radius: 14px;
  padding: 36px 32px 28px;
  max-width: 420px; width: 92%;
  text-align: center;
  box-shadow: 0 8px 32px rgba(0,0,0,.18);
}
.nfm-icon  { font-size: 44px; margin-bottom: 12px; }
.nfm-title { font-size: 19px; font-weight: 700; color: #1e3a8a; margin-bottom: 10px; }
.nfm-body  { font-size: 14px; color: #4b5563; line-height: 1.55; margin-bottom: 24px; }
.nfm-actions { display: flex; gap: 12px; justify-content: center; }
.nfm-btn {
  padding: 10px 20px; border-radius: 8px; font-size: 14px;
  font-weight: 600; border: none; cursor: pointer; transition: opacity .15s;
}
.nfm-btn:hover { opacity: .85; }
.nfm-btn--secondary { background: #f3f4f6; color: #374151; border: 1px solid #d1d5db; }
.nfm-btn--primary   { background: #1e3a8a; color: #fff; }
</style>
<script>
(function () {
    /* ── Guide toggle ── */
    const guideBtn  = document.getElementById('foGuideToggle');
    const guideBody = document.getElementById('foGuideBody');
    if (guideBtn && guideBody) {
        guideBtn.addEventListener('click', function () {
            const open = guideBody.classList.toggle('fo-guide-body--open');
            guideBtn.querySelector('.fo-guide-caret').textContent = open ? '▴' : '▾';
        });
    }

    /* ── No-food modal ── */
    const form       = document.getElementById('foodReservationForm');
    const addBtn     = document.getElementById('addToCartBtn');
    const modal      = document.getElementById('noFoodModal');
    const goBackBtn  = document.getElementById('nfmGoBack');
    const proceedBtn = document.getElementById('nfmProceed');

    function hasFoodSelected() {
        // Check any food-select (set selections, individual selects)
        const anySelect = form.querySelectorAll('.food-select');
        for (const sel of anySelect) {
            if (sel.value && sel.name) return true;
        }
        // Check radio buttons (drink_choice)
        const anyChecked = form.querySelector('input[type="radio"]:checked');
        if (anyChecked) return true;
        // Check chips (extra viand / dessert hidden inputs)
        const chips = form.querySelectorAll('.fo-indiv-chip-price[name]');
        if (chips.length > 0) return true;
        return false;
    }

    function isAnyDateEnabled() {
        const enabled = form.querySelectorAll('.food-enabled-input');
        for (const inp of enabled) {
            if (inp.value === '1') return true;
        }
        return false;
    }

    if (addBtn && form && modal) {
        addBtn.addEventListener('click', function () {
            // No-food selected check
            if (isAnyDateEnabled() && !hasFoodSelected()) {
                modal.style.display = 'flex';
                return;
            }

            form.requestSubmit ? form.requestSubmit() : form.submit();
        });

        goBackBtn.addEventListener('click', function () {
            modal.style.display = 'none';
        });

        proceedBtn.addEventListener('click', function () {
            modal.style.display = 'none';
            // Disable all food-enabled inputs so no food is submitted
            form.querySelectorAll('.food-enabled-input').forEach(inp => { inp.value = '0'; });
            form.requestSubmit ? form.requestSubmit() : form.submit();
        });

        modal.addEventListener('click', function (e) {
            if (e.target === modal) modal.style.display = 'none';
        });
    }
})();
</script>
@endsection
