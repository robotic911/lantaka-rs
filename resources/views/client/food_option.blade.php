@extends('layouts.client')
  <link rel="stylesheet" href="{{ asset('css/client_food_options.css') }}">
  @vite('resources/js/client_food_option.js')

@section('content')
  <main class="main-content">
    
    <form action="{{ route('checkout') }}" method="GET" id="foodReservationForm">
      
      <input type="hidden" name="accommodation_id" value="{{ request('accommodation_id') }}">
      <input type="hidden" name="type" value="{{ request('type') }}">
      <input type="hidden" name="check_in" value="{{ request('check_in') }}">
      <input type="hidden" name="check_out" value="{{ request('check_out') }}">
      <input type="hidden" name="pax" id="paxValue" value="{{ request('pax', 1) }}">

      <div class="back-section">
        <button type="button" class="back-btn" onclick="window.history.back();">
        <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
          <path d="M15 10H5M5 10L10 15M5 10L10 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
          <span>Back</span>
        </button>
      </div>

      <div class="reservation-card">
        <div class="card-header">
          <h2>Hall A - Food Reservation</h2>
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"><polyline points="6 9 12 15 18 9"></polyline></svg>
        </div>

        <div class="food-toggle-section">
          <div class="toggle-label">Include Food</div>
          <div class="toggle-buttons">
            <button type="button" class="toggle-btn">No</button>
            <button type="button" class="toggle-btn active">Yes</button>
          </div>
        </div>

        <div class="meals-container">
          
          <div class="meal-section">
            <div class="meal-header">
              <span class="meal-name">Breakfast</span>
              <span class="toggle-status">No</span>
            </div>
            <div class="food-items">
              @if(isset($foods['breakfast']) && $foods['breakfast']->count() > 0)
                @foreach($foods['breakfast'] as $food)
                  <label class="food-item" style="cursor: pointer;">
                    <input type="checkbox" name="selected_foods[]" value="{{ $food->food_id }}" data-price="{{ $food->food_price }}" class="food-checkbox" style="display:none;">
                    <div class="food-name">{{ $food->food_name }}</div>
                    <div class="food-price">₱ {{ number_format($food->food_price, 2) }}</div>
                  </label>
                @endforeach
              @else
                <p>No breakfast items available.</p>
              @endif
            </div>
          </div>

          <div class="meal-section">
            <div class="meal-header">
              <span class="meal-name">Snack</span>
              <span class="toggle-status">No</span>
            </div>
            <div class="food-items">
              @if(isset($foods['snack']) && $foods['snack']->count() > 0)
                @foreach($foods['snack'] as $food)
                  <label class="food-item" style="cursor: pointer;">
                    <input type="checkbox" name="selected_foods[]" value="{{ $food->food_id }}" data-price="{{ $food->food_price }}" class="food-checkbox" style="display:none;">
                    <div class="food-name">{{ $food->food_name }}</div>
                    <div class="food-price">₱ {{ number_format($food->food_price, 2) }}</div>
                  </label>
                @endforeach
              @else
                <p>No snack items available.</p>
              @endif
            </div>
          </div>

          <div class="meal-section">
            <div class="meal-header">
              <span class="meal-name">Lunch</span>
              <span class="toggle-status">No</span>
            </div>
            <div class="food-items">
              @if(isset($foods['lunch']) && $foods['lunch']->count() > 0)
                @foreach($foods['lunch'] as $food)
                  <label class="food-item" style="cursor: pointer;">
                    <input type="checkbox" name="selected_foods[]" value="{{ $food->food_id }}" data-price="{{ $food->food_price }}" class="food-checkbox" style="display:none;">
                    <div class="food-name">{{ $food->food_name }}</div>
                    <div class="food-price">₱ {{ number_format($food->food_price, 2) }}</div>
                  </label>
                @endforeach
              @else
                <p>No lunch items available.</p>
              @endif
            </div>
          </div>

          <div class="meal-section">
            <div class="meal-header">
              <span class="meal-name">Dinner</span>
              <span class="toggle-status active">No</span>
            </div>
            <div class="food-items">
              @if(isset($foods['dinner']) && $foods['dinner']->count() > 0)
                @foreach($foods['dinner'] as $food)
                  <label class="food-item" style="cursor: pointer;">
                    <input type="checkbox" name="selected_foods[]" value="{{ $food->food_id }}" data-price="{{ $food->food_price }}" class="food-checkbox" style="display:none;">
                    <div class="food-name">{{ $food->food_name }}</div>
                    <div class="food-price">₱ {{ number_format($food->food_price, 2) }}</div>
                  </label>
                @endforeach
              @else
                <p>No dinner items available.</p>
              @endif
            </div>
          </div>

        </div>
      </div>

      <div class="other-request-section">
        <div class="section-header">
          <h3>Other Request</h3>
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"><polyline points="6 9 12 15 18 9"></polyline></svg>
        </div>
      </div>

      <div class="action-section">
        <button type="button" class="date-select-btn">Select Venue Date</button>
        <div class="total-section">
          <span class="total-label">Total:</span>
          <span class="total-amount" id="displayTotalPrice">₱ 0.00</span>
        </div>
        <button type="submit" class="add-to-cart-btn">ADD TO BOOKING CART</button>
      </div>

      <input type="hidden" id="foodServiceDate" name="food_service_date" value="">

    </form>
    
    <div class="fooddate-overlay" id="foodDateOverlay" aria-hidden="true">
    
        <div class="fooddate-modal" role="dialog" aria-modal="true" aria-labelledby="foodDateTitle">
            
            <div class="fooddate-header">
                <h3 id="foodDateTitle">Select Date for Food Reservation</h3>
                <button type="button" class="fooddate-close" id="foodDateClose">×</button>
            </div>

            <div class="fooddate-body">
                <div class="fooddate-dates" id="foodDateList">
                    @php
                        $startDate = \Carbon\Carbon::parse(request('check_in'));
                        $endDate = \Carbon\Carbon::parse(request('check_out'));
                        $period = \Carbon\CarbonPeriod::create($startDate, $endDate);
                    @endphp

                    @foreach($period as $date)
                        <button type="button" class="fooddate-chip" data-date="{{ $date->format('Y-m-d') }}">
                            <span class="chip-day">{{ $date->format('d') }}</span>
                            <span class="chip-month">{{ $date->format('M') }}</span>
                        </button>
                    @endforeach
                </div>
                <p class="fooddate-error" id="foodDateError" style="display:none;"></p>
            </div>

            <div class="fooddate-footer">
                <button type="button" class="fooddate-confirm" id="foodDateConfirm">CONFIRM</button>
            </div>
        </div>
    </div>
@endsection