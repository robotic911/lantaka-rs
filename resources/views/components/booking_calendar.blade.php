<link rel="stylesheet" href="{{ asset('css/booking_calendar.css') }}">
@props(['occupiedDates' => '[]', 'currentReservationDates' => '[]'])
@vite(['resources/js/booking_calendar.js'])

  <div class="calendar-container">
    <!-- Date Selection Display -->
    

    <!-- Hidden Input Fields for Laravel -->
    <input type="hidden" id="checkinDate" value="">
    <input type="hidden" id="checkoutDate" value="">

    <!-- Calendar -->
    <div class="calendar-header">
      <button id="prevMonth" class="nav-btn prev-btn">‹</button>
      <h2 class="month">November 2025</h2>
      <button id="nextMonth" class="nav-btn next-btn">›</button>
    </div>

    <div class="calendar-main">
      <div class="calendar-weekdays">
        <div class="weekday">Mo</div>
        <div class="weekday">Tu</div>
        <div class="weekday">We</div>
        <div class="weekday">Th</div>
        <div class="weekday">Fr</div>
        <div class="weekday">Sa</div>
        <div class="weekday">Su</div>
      </div>

      <div class="calendar-days">
          <!-- days render-->
      </div>
    </div>
    <br>
    <div class="date-display-section">
      <div class="date-box">
        <label>Check-in Date</label>
        <span id="checkinDisplay" class="date-value">-</span>
      </div>
      ͢
      <div class="date-box">
        <label>Check-out Date</label>
        <span id="checkoutDisplay" class="date-value">-</span>
      </div>
      <p id="dateError" class="date-error" style="display:none;"></p>

    </div>

  </div>
  <script>
    // Inject the Laravel data into global JS variables
    window.serverOccupiedDates          = {!! $occupiedDates !!};
    window.serverCurrentReservationDates = {!! $currentReservationDates !!};
  </script>

  <style>
    /* Past dates — grayish background, very faint text, not selectable */
    .day.past-date {
      color: #c2c2c2;
      background: #efefef;
      cursor: not-allowed;
      pointer-events: none;
    }
    /* Dates that belong to the reservation being edited — selectable, shown in light blue */
    .day.current-reservation {
      background: #a8d4f0;
      color: #2c3e7f;
      cursor: pointer;
    }
    .day.current-reservation:hover {
      background: #8ec5e8;
    }
  </style>