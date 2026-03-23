@extends('layouts.client')
  <title>{{ $data->display_name }} - Lantaka Portal</title>
  <link rel="stylesheet" href="{{ asset('css/client_room_venue_viewing.css') }}">
  <link rel="stylesheet" href="{{ asset('css/nav.css') }}">
  <link href="https://fonts.googleapis.com/css2?family=Alexandria:wght@700;800&family=Arsenal:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">

@section('content')
    <div class="back-section">
      <a href="{{ route('client.room_venue') }}">
      <button class="back-button">
      <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
          <path d="M15 10H5M5 10L10 15M5 10L10 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <span>Back</span>
      </button>
      </a>
    </div>

    <div class="content-wrapper">
      <div class="left-section">
        <div class="main-image-container">
           <img src="{{ $data->image ? media_url($data->image) : asset('images/' . (strtolower($category) === 'room' ? 'placeholder_room' : 'placeholder_venue') . '.svg') }}"
                alt="{{ $data->display_name }}"
                class="main-image">
        </div>

        <div class="venue-details">
          <h2 class="venue-name">{{ $data->display_name }}</h2>
          <p class="venue-type">{{ $category }}</p>
          <div class="venue-specs">
            <div class="spec-item">
              <div>
                <p class="spec-label">Max Guests</p>
                <p class="spec-value">{{ $data->capacity }}</p>
              </div>
            </div>
            <div class="spec-item">
              <div>
                <p class="spec-label">Status</p>
                <p class="spec-value" style="color: {{ $data->status === 'Available' ? 'green' : 'red' }}">
                    {{ $data->status }}
                </p>
              </div>
            </div>
          </div>

          @if (isset(Auth()->user()->Account_Type))
            @if (Auth()->user()->Account_Type == 'Internal')
                <p class="price">₱ {{ number_format($data->internal_price, 2) }}<span>/use</span></p>
              @else
                <p class="price">₱ {{ number_format($data->external_price, 2) }}<span>/use</span></p>
              @endif
          @else
              <p class="price">₱ {{ number_format($data->external_price, 2) }}<span>/use</span></p>
          @endif

          <p class="venue-description">
            {{ $data->description ?? 'No description provided for this accommodation.' }}
          </p>
        </div>
      </div>

      <div class="right-section">
        <div class="calendar-container">
          <x-booking_calendar :occupiedDates="json_encode($occupiedDates)" />
        </div>

        <div class="booking-section">
          <form action="{{ route('booking.prepare') }}" method="GET" class="booking-form" id="bookingForm">

              <input type="hidden" name="accommodation_id" value="{{ $data->id }}">
              <input type="hidden" name="res_name" id="res_name" value="{{ $data->display_name }}">
              <input type="hidden" name="type" value="{{ stripos($category, 'room') !== false ? 'room' : 'venue' }}">
              <input type="hidden" name="check_in" id="check_in" required>
              <input type="hidden" name="check_out" id="check_out" required>

              <div style="display: flex; flex-direction: column; gap: 4px; width: 100%;">
                <div style="display: flex; flex-direction: row; align-items: center; gap: 13px;">
                  <label for="pax-input" class="pax-label">Number of Pax</label>
                  <input type="number" name="pax" id="pax-input" class="pax-input"
                         placeholder="Enter No. of Pax"
                         min="1"
                         max="{{ $data->capacity }}" required>
                </div>

                <div style="display: flex; flex-direction: column; gap: 8px;">
                  <label for="purpose-input" class="pax-label" style="margin-top: 12px;">Purpose</label>
                  <textarea name="purpose" id="purpose-input"
                            placeholder="Enter purpose of reservation"
                            style="width:100%; padding:10px; border:1px solid #ccc; border-radius:8px; resize:vertical; min-height:80px; font-family:inherit; font-size:0.9rem;"
                            required></textarea>
                </div>
              </div>

              <button type="submit" class="proceed-button" style="font-size: 14px;">PROCEED</button>
          </form>
        </div>
      </div>
    </div>
  <script>
    document.addEventListener('DOMContentLoaded', function () {

      // Listen for when the user clicks "PROCEED"
      document.getElementById('bookingForm').addEventListener('submit', function(e) {

        const checkIn  = document.getElementById('checkinDate').value;
        const checkOut = document.getElementById('checkoutDate').value;

        document.getElementById('check_in').value  = checkIn;
        document.getElementById('check_out').value = checkOut;

        if (!checkIn || !checkOut) {
          e.preventDefault();
          alert('Please select both check-in and check-out dates.');
          return;
        }

        if (checkIn === checkOut) {
          e.preventDefault();
          alert('Check-in and check-out dates cannot be the same.');
          return;
        }
      });

    });
  </script>
@endsection
