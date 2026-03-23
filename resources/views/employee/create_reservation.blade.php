@extends('layouts.employee')
<title>{{ $data->display_name }} - Lantaka Portal</title>
<link rel="stylesheet" href="{{ asset('css/client_room_venue_viewing.css') }}">
<link href="https://fonts.googleapis.com/css2?family=Alexandria:wght@700;800&family=Arsenal:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">

@section('content')

<div class="content">
  <h1 class="page-title">
    {{ isset($reservationId) && $reservationId ? 'Edit Reservation for:' : 'Create Reservation for:' }}
    <strong>{{ $client->Account_Name }}</strong>
  </h1>

  <div class="content-wrapper">
    <div class="left-section">
      <div class="main-image-container">
        <img src="{{ $data->image ? media_url($data->image) : asset('images/' . (strtolower($category) === 'room' ? 'placeholder_room' : 'placeholder_venue') . '.svg') }}"
          alt="{{ $data->display_name }}"
          class="main-image">
      </div>

      <!-- @php
        $mainImg = $data->image ? media_url($data->image) : asset('images/' . (strtolower($category) === 'room' ? 'placeholder_room' : 'placeholder_venue') . '.svg');
      @endphp
      <div class="thumbnail-gallery">
        <div class="thumbnail active"
             data-src="{{ $mainImg }}"
             style="background-image: url('{{ $mainImg }}'); background-size: cover; background-position: center;"></div>
        <div class="thumbnail"></div>
        <div class="thumbnail"></div>
        <div class="thumbnail"></div>
        <div class="thumbnail"></div>
      </div> -->

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

        <p class="price">₱ {{ number_format($data->external_price, 2) }}<span>/use</span></p>

        <p class="venue-description">
          {{ $data->description ?? 'No description provided for this accommodation.' }}
        </p>
      </div>
    </div>
    {{--
          <h3>Select Dates</h3>
          <input type="text" id="calendar-input" placeholder="Check-in  →  Check-out" 
                 style="width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 8px; margin-top: 10px;">
          --}}
    <div class="right-section">
      <div class="calendar-container">

        <x-booking_calendar
          :occupiedDates="json_encode($occupiedDates)"
          :currentReservationDates="json_encode($currentReservationDates ?? [])" />

      </div>
      <div class="booking-section">

        @if ($errors->any())
        <div style="background:#ffd6d6;padding:10px;border-radius:6px;margin-bottom:10px;">
          <strong>Validation Errors:</strong>
          <ul>
            @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
          </ul>
        </div>
        @endif
        <form action="{{ route('employee.reservations.prepare') }}" method="POST" class="booking-form" id="bookingForm">
          @csrf

          <input type="hidden" name="user_id" value="{{ $client->Account_ID }}">
          <input type="hidden" name="accommodation_id" value="{{ $data->id }}">
          <input type="hidden" name="res_name" id="res_name" value="{{ $data->display_name }}">
          <input type="hidden" name="type" value="{{ strtolower($category) }}">
          <input type="hidden" name="check_in" id="check_in">
          <input type="hidden" name="check_out" id="check_out">
          @if(isset($reservationId) && $reservationId)
          <input type="hidden" name="reservation_id" value="{{ $reservationId }}">
          @endif
          <div style="display: flex; flex-direction: column; gap: 4px; width:100%;">
            <div style="display: flex; flex-direction: row; align-items: center;
    gap: 13px;">
              <label for="pax-input" class="pax-label">Number of Pax</label>
              <input
                type="number"
                name="pax"
                id="pax-input"
                class="pax-input"
                placeholder="Enter No. of Pax"
                min="1"
                max="{{ $data->capacity }}"
                value="{{ $prefillPax ?? '' }}"
                required>
            </div>

            <div style="display: flex; flex-direction: column; gap: 8px;">
              <label for="purpose-input" class="pax-label" style="margin-top: 12px;">Purpose</label>
              <textarea
                name="purpose"
                id="purpose-input"
                placeholder="Enter purpose of reservation"
                style="width:100%; padding:10px; border:1px solid #ccc; border-radius:8px; resize:vertical; min-height:80px; font-family:inherit; font-size:0.9rem;"
                required>{{ $prefillPurpose ?? '' }}</textarea>
            </div>
          </div>

          <button type="submit" class="proceed-button" style="font-size: 14px;">

            {{ isset($reservationId) && $reservationId ? 'UPDATE RESERVATION' : 'PROCEED' }}
            
          </button>

        </form>
      </div>
      <!-- <a href="{{ route('employee.create_food_reservation') }}">test</a> -->
    </div>
  </div>
</div>
@endsection

<script>
  document.addEventListener('DOMContentLoaded', function() {

    // ── Thumbnail gallery ──
    const mainImage   = document.querySelector('.main-image');
    const thumbnails  = document.querySelectorAll('.thumbnail');

    thumbnails.forEach(thumb => {
      thumb.addEventListener('click', function () {
        const src = this.dataset.src;
        if (!src) return; // grey placeholder — nothing to show

        // Swap main image
        mainImage.src = src;

        // Update active state
        thumbnails.forEach(t => t.classList.remove('active'));
        this.classList.add('active');
      });
    });

    const bookingForm = document.getElementById('bookingForm');
    if (!bookingForm) return;

    bookingForm.addEventListener('submit', function(e) {

      const calendarCheckIn = document.getElementById('checkinDate');
      const calendarCheckOut = document.getElementById('checkoutDate');

      const hiddenCheckIn = document.getElementById('check_in');
      const hiddenCheckOut = document.getElementById('check_out');


      // Only fill values if calendar exists
      if (calendarCheckIn && calendarCheckOut) {
        hiddenCheckIn.value = calendarCheckIn.value;
        hiddenCheckOut.value = calendarCheckOut.value;
      }

      // Validate after assignment
      if (!hiddenCheckIn.value || !hiddenCheckOut.value) {
        e.preventDefault();
        window.showToast('Please select both a check-in and check-out date.');
        return;
      }

    });

  });
</script>