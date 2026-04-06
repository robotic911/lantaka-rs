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
            @if (strtolower($category) === 'venue')
              <div style="display: flex; flex-direction: column; gap: 2px;">
                <div style="display: flex; flex-direction: row; align-items: center; gap: 13px;">
                  <label for="pax-input" class="pax-label">Number of Pax</label>
                  <input
                    type="number"
                    name="pax"
                    id="pax-input"
                    class="pax-input"
                    placeholder="Enter No. of Pax"
                    min="1"
                    max="{{ $data->capacity }}"
                    data-capacity="{{ $data->capacity }}"
                    value="{{ $prefillPax ?? '' }}"
                    required>
                </div>
                <span id="pax-error" class="pax-error-msg"></span>
              </div>
            @endif
            

            <div style="display: flex; flex-direction: column; gap: 6px;">
              <label class="pax-label" style="margin-top: 12px;">Purpose</label>
              <input type="hidden" name="purpose" id="purposeValue">
              <span id="purpose-error" class="purpose-error-msg"></span>
              <div class="purpose-pills">
                @if(strtolower($category) === 'room')
                  <button type="button" class="purpose-pill" data-value="overnight">Overnight Stay</button>
                  <button type="button" class="purpose-pill" data-value="retreat">Retreat</button>
                  <button type="button" class="purpose-pill" data-value="recollection">Recollection</button>
                @else
                  <button type="button" class="purpose-pill" data-value="meeting">Meeting</button>
                  <button type="button" class="purpose-pill" data-value="seminar">Seminar</button>
                  <button type="button" class="purpose-pill" data-value="birthday">Birthday</button>
                  <button type="button" class="purpose-pill" data-value="lecture">Lecture</button>
                  <button type="button" class="purpose-pill" data-value="wedding">Wedding</button>
                  <button type="button" class="purpose-pill" data-value="orientation">Orientation</button>
                  <button type="button" class="purpose-pill" data-value="retreat">Retreat</button>
                  <button type="button" class="purpose-pill" data-value="recollection">Recollection</button>
                @endif
                <button type="button" class="purpose-pill" data-value="others">Others</button>
              </div>
              <div id="othersWrapper" style="display:none;">
                <input type="text" id="othersText" class="purpose-others-input"
                       placeholder="Please specify your purpose...">
              </div>
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

    /* ── Purpose pill selector ── */
    const purposePills  = document.querySelectorAll('.purpose-pill');
    const purposeValue  = document.getElementById('purposeValue');
    const othersWrapper = document.getElementById('othersWrapper');
    const othersText    = document.getElementById('othersText');

    purposePills.forEach(pill => {
      pill.addEventListener('click', function () {
        purposePills.forEach(p => p.classList.remove('active'));
        this.classList.add('active');
        clearPurposeError();

        if (this.dataset.value === 'others') {
          othersWrapper.style.display = 'block';
          purposeValue.value = othersText.value.trim();
          othersText.focus();
        } else {
          othersWrapper.style.display = 'none';
          purposeValue.value = this.dataset.value;
        }
      });
    });

    othersText.addEventListener('input', function () {
      purposeValue.value = this.value.trim();
      if (this.value.trim()) clearPurposeError();
    });

    /* ── Pax validation ── */
    const paxInput     = document.getElementById('pax-input');
    const paxError     = document.getElementById('pax-error');
    const purposeError = document.getElementById('purpose-error');
    const capacity     = parseInt(paxInput?.dataset.capacity || paxInput?.max || 9999);

    function showPaxError(msg) {
      if (!paxError) return;
      paxError.textContent = msg;
      paxError.classList.add('pax-error-msg--visible');
      paxInput.classList.add('pax-input--error');
    }
    function clearPaxError() {
      if (!paxError) return;
      paxError.classList.remove('pax-error-msg--visible');
      paxInput.classList.remove('pax-input--error');
    }
    function showPurposeError(msg) {
      if (!purposeError) return;
      purposeError.textContent = msg;
      purposeError.classList.add('purpose-error-msg--visible');
    }
    function clearPurposeError() {
      if (!purposeError) return;
      purposeError.classList.remove('purpose-error-msg--visible');
    }
    function validatePax() {
      if (!paxInput) return true;
      const raw = paxInput.value.trim();
      const num = Number(raw);
      if (raw === '' || isNaN(num)) {
        showPaxError('Please enter the number of pax.');
        return false;
      }
      if (num <= 0) {
        showPaxError('Number of pax must be at least 1.');
        return false;
      }
      if (!Number.isInteger(num)) {
        showPaxError('Number of pax must be a whole number (no decimals).');
        return false;
      }
      if (num > capacity) {
        showPaxError(`Exceeds maximum capacity of ${capacity} guest${capacity !== 1 ? 's' : ''}.`);
        return false;
      }
      clearPaxError();
      return true;
    }

    paxInput?.addEventListener('input', validatePax);

    /* ── Pre-select purpose when editing an existing reservation ── */
    const prefillPurpose = '{{ addslashes($prefillPurpose ?? '') }}';
    if (prefillPurpose) {
      const matchPill = document.querySelector(`.purpose-pill[data-value="${prefillPurpose}"]`);
      if (matchPill) {
        matchPill.click();
      } else {
        // Custom "others" text
        const othersPill = document.querySelector('.purpose-pill[data-value="others"]');
        if (othersPill) {
          othersPill.click();
          othersText.value    = prefillPurpose;
          purposeValue.value  = prefillPurpose;
        }
      }
    }

    /* ── Form submit ── */
    bookingForm.addEventListener('submit', function(e) {

      const calendarCheckIn  = document.getElementById('checkinDate');
      const calendarCheckOut = document.getElementById('checkoutDate');
      const hiddenCheckIn    = document.getElementById('check_in');
      const hiddenCheckOut   = document.getElementById('check_out');

      if (calendarCheckIn && calendarCheckOut) {
        hiddenCheckIn.value  = calendarCheckIn.value;
        hiddenCheckOut.value = calendarCheckOut.value;
      }

      if (!hiddenCheckIn.value || !hiddenCheckOut.value) {
        e.preventDefault();
        window.showToast('Please select both a check-in and check-out date.');
        return;
      }

      if (paxInput && !validatePax()) {
        e.preventDefault();
        paxInput.focus();
        return;
      }

      if (!purposeValue.value.trim()) {
        e.preventDefault();
        showPurposeError('Select your purpose of stay.');
        window.showToast('Select your purpose of stay.');
        return;
      }

    });

  });
</script>