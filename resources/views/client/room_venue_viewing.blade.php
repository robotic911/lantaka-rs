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

              <div style="display: flex; flex-direction: column; gap: 15px; width: 100%;">
                @if (strtolower($category) === 'venue')
                  <div style="display: flex; flex-direction: column; gap: 2px;">
                    <div style="display: flex; flex-direction: row; align-items: center; gap: 13px;">
                      <label for="pax-input" class="pax-label">Number of Pax</label>
                      <input type="number" name="pax" id="pax-input" class="pax-input"
                            placeholder="Enter No. of Pax"
                            min="1"
                            max="{{ $data->capacity }}"
                            data-capacity="{{ $data->capacity }}"
                            required>
                    </div>
                    <span id="pax-error" class="pax-error-msg"></span>
                  </div>
                @endif

                <div style="display: flex; flex-direction: row; gap: 6px;">
                  <div>
                    <label class="pax-label" style="margin-top: 12px;">Purpose</label>
                    <input type="hidden" name="purpose" id="purposeValue">
                    <span id="purpose-error" class="purpose-error-msg"></span>
                  </div>
                  <div class="purpose-pills">
                    @if(strtolower($category) === 'room')
                      <button type="button" class="purpose-pill" data-value="overnight">Overnight Stay</button>
                      <button type="button" class="purpose-pill" data-value="retreat">Retreat</button>
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
                    <div id="othersWrapper" style="display:none; width:100%;">
                    <input type="text" id="othersText" class="purpose-others-input"
                           placeholder="Please specify your purpose...">
                    </div>
                  </div>
                </div>
              </div>

              <button type="submit" class="proceed-button">PROCEED</button>
          </form>
        </div>
      </div>
    </div>
  <script>
    const IS_ROOM = {{ strtolower($category) === 'room' ? 'true' : 'false' }};

    document.addEventListener('DOMContentLoaded', function () {

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
      const paxInput    = document.getElementById('pax-input');
      const paxError    = document.getElementById('pax-error');
      const purposeError = document.getElementById('purpose-error');
      const capacity    = parseInt(paxInput?.dataset.capacity || paxInput?.max || 9999);

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

      /* ── Form submit ── */
      document.getElementById('bookingForm').addEventListener('submit', function(e) {

        const checkIn  = document.getElementById('checkinDate').value;
        const checkOut = document.getElementById('checkoutDate').value;

        document.getElementById('check_in').value  = checkIn;
        document.getElementById('check_out').value = checkOut;

        if (!checkIn || !checkOut) {
          e.preventDefault();
          window.showToast('Please select both check-in and check-out dates.');
          return;
        }

        if (IS_ROOM && checkIn === checkOut) {
          e.preventDefault();
          window.showToast('Check-in and check-out dates cannot be the same.');
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

      /* ── Prefill pax & purpose from URL params (used when editing a cart item) ── */
      (function prefillFromUrl() {
        const params      = new URLSearchParams(window.location.search);
        const prefillPax  = params.get('pax');
        const prefillPurp = (params.get('purpose') || '').toLowerCase().trim();

        // Pax
        if (prefillPax && paxInput) {
          paxInput.value = prefillPax;
          validatePax();
        }

        // Purpose — click the matching pill (triggers active + purposeValue update)
        if (prefillPurp) {
          const matchingPill = document.querySelector(`.purpose-pill[data-value="${prefillPurp}"]`);
          if (matchingPill) {
            matchingPill.click();
          } else {
            // Non-standard purpose → use "Others" pill + fill the text input
            const othersPill = document.querySelector('.purpose-pill[data-value="others"]');
            if (othersPill) othersPill.click();
            if (othersText) {
              othersText.value = prefillPurp;
              purposeValue.value = prefillPurp;
            }
          }
        }
      })();

    });
  </script>
@endsection
