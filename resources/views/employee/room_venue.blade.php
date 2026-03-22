@extends('layouts.employee')
  <title>Rooms / Venue - Lantaka</title>
  <link rel="stylesheet" href="{{asset('css/employee_room_venue.css')}}">


  @vite('resources/js/employee_food.js')
  @vite('resources/js/employee_add_food.js')
  @vite('resources/js/employee/create_reservation.js')
  @vite('resources/js/employee_rv_viewing_modal.js')

  <link href="https://fonts.googleapis.com/css2?family=Alexandria:wght@200;300;400;500;600;700;800;900&family=Arsenal:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">

@section('content')
      <!-- Content Section -->
      <div class="content">
        <h1 class="page-title">Room / Venue</h1>

        <!-- Top Controls -->
        <div class="controls-section">
        <form class="search-bar" method="GET" action="{{ route('employee.room_venue') }}">
          {{-- preserve date range when searching --}}
          @if($dateFrom)<input type="hidden" name="date_from" value="{{ $dateFrom }}">@endif
          @if($dateTo)<input type="hidden" name="date_to" value="{{ $dateTo }}">@endif
          <input
              type="text"
              name="search"
              value="{{ request('search') }}"
              placeholder="Search room or venue"
              class="search-input"
          >
          <button type="submit" class="search-icon">🔍</button>
        </form>

        <form class="filters-actions" method="GET" action="{{ route('employee.room_venue') }}">
          {{-- preserve date range when filtering by status --}}
          @if($dateFrom)<input type="hidden" name="date_from" value="{{ $dateFrom }}">@endif
          @if($dateTo)<input type="hidden" name="date_to" value="{{ $dateTo }}">@endif
          @if(request('search'))<input type="hidden" name="search" value="{{ request('search') }}">@endif
          <select name="status" class="status-filter" onchange="this.form.submit()">
            <option value="">All Status</option>
            <option value="available"       {{ request('status') == 'available'       ? 'selected' : '' }}>Available</option>
            <option value="occupied"        {{ request('status') == 'occupied'        ? 'selected' : '' }}>Occupied</option>
            <option value="undermaintenance"{{ request('status') == 'undermaintenance'? 'selected' : '' }}>Under Maintenance</option>
            <option value="reserved"        {{ request('status') == 'reserved'        ? 'selected' : '' }}>Reserved</option>
          </select>
        </form>

          <div class="button-section">
            <button class="btn btn-secondary" id="food_button">Food Menu</button>
            @if(auth()->user()->Account_Role === 'admin')
              <button class="btn btn-primary" id="add_room_venue_button">Add Room/Venue</button>
            @endif
          </div>
        </div>

        <!-- Date Range Availability Picker -->
        <div class="availability-picker-wrap">
          <form method="GET" action="{{ route('employee.room_venue') }}" class="availability-form" id="availabilityForm">
            @if(request('search'))
              <input type="hidden" name="search" value="{{ request('search') }}">
            @endif
            @if(request('status'))
              <input type="hidden" name="status" value="{{ request('status') }}">
            @endif

            <span class="availability-label">View Status For:</span>

            <div class="date-range-group">
              <label class="date-label" for="dateFrom">From</label>
              <input type="date" name="date_from" id="dateFrom"
                     class="date-input"
                     value="{{ $dateFrom ?? '' }}">
            </div>

            <div class="date-range-group">
              <label class="date-label" for="dateTo">To</label>
              <input type="date" name="date_to" id="dateTo"
                     class="date-input"
                     value="{{ $dateTo ?? '' }}">
            </div>

            <button type="submit" class="btn btn-check-avail">Apply</button>

            @if($dateFrom && $dateTo)
              <a href="{{ route('employee.room_venue', array_filter(['search' => request('search'), 'status' => request('status')])) }}"
                 class="btn btn-clear-dates">Clear</a>
            @endif
          </form>

          @if($dateFrom && $dateTo)
            <div class="availability-result-label">
              Showing Room &amp; Venue status from
              <strong>{{ \Carbon\Carbon::parse($dateFrom)->format('M j, Y') }}</strong>
              to
              <strong>{{ \Carbon\Carbon::parse($dateTo)->format('M j, Y') }}</strong>
            </div>
          @else
            <div class="availability-result-label availability-result-label--today">
              Showing status for Today
            </div>
          @endif
        </div>

        <div class="room-venue-divider">

          <!-- Div Content for both Rooms and Venues -->
          <div class="room-venue-content">

            <!-- Rooms Section -->
            <section class="rooms-section">
              <h2 class="section-title">Room</h2>
              <div class="rooms-grid">

              @foreach($rooms as $room)
                <div class="room-card {{ $room->effective_status }}">
                  {{ $room->Room_Number }}

                  <input type="hidden" class="room-details"
                        data-id="{{ $room->Room_ID }}"
                        data-name="{{ $room->Room_Number }}"
                        data-type="{{ $room->Room_Type }}"
                        data-capacity="{{ $room->Room_Capacity }}"
                        data-price="{{ $room->Room_Internal_Price }}"
                        data-external_price="{{ $room->Room_External_Price }}"
                        data-status="{{ $room->Room_Status }}"
                        data-effective_status="{{ $room->effective_status }}"
                        data-description="{{ $room->Room_Description }}"
                        data-image="{{ $room->Room_Image ? asset('storage/' . $room->Room_Image) : '' }}">
                </div>
              @endforeach

                @if($rooms->isEmpty())
                  <p style="color: #666; font-style: italic;">No rooms found.</p>
                @endif
              </div>
            </section>

            <!-- Venues Section -->
            <section class="venues-section">
              <h2 class="section-title">Venue</h2>
              <div class="venue-grid">
              @foreach($venues as $venue)
                <div class="venue-card {{ $venue->effective_status }}">
                  {{ $venue->Venue_Name }}

                  <input type="hidden" class="venue-details"
                        data-id="{{ $venue->Venue_ID }}"
                        data-name="{{ $venue->Venue_Name }}"
                        data-capacity="{{ $venue->Venue_Capacity }}"
                        data-price="{{ $venue->Venue_Internal_Price }}"
                        data-external_price="{{ $venue->Venue_External_Price }}"
                        data-status="{{ $venue->Venue_Status }}"
                        data-effective_status="{{ $venue->effective_status }}"
                        data-description="{{ $venue->Venue_Description }}"
                        data-image="{{ $venue->Venue_Image ? asset('storage/' . $venue->Venue_Image) : '' }}">
                </div>
              @endforeach

                  @if($venues->isEmpty())
                    <p style="color: #666; font-style: italic;">No venues found.</p>
                  @endif
              </div>
            </section>
          </div>
          </div>

        <!-- Status Legend -->
        <div class="status-legend">
          <div class="legend-items">
            <div class="legend-item">
              <span class="legend-swatch legend-available"></span>
              <span>Available</span>
            </div>
            <div class="legend-item">
              <span class="legend-swatch legend-occupied"></span>
              <span>Occupied (Checked-in)</span>
            </div>
            <div class="legend-item">
              <span class="legend-swatch legend-reserved"></span>
              <span>Reserved (Pending / Confirmed)</span>
            </div>
            <div class="legend-item">
              <span class="legend-swatch legend-undermaintenance"></span>
              <span>Under Maintenance</span>
            </div>
          </div>
        </div>

      </div>
  <!-- Add Room Venue Modal -->
      <!-- Modal Content -->
      <x-add_room_venue/>
      <x-create_reservation_modal/>
      <x-employee_rv_viewing_modal/>
      <x-employee_food :foods="$foods" />

<script>
  (function () {
    const fromInput = document.getElementById('dateFrom');
    const toInput   = document.getElementById('dateTo');
    if (!fromInput || !toInput) return;

    fromInput.addEventListener('change', function () {
      toInput.min = this.value;
      if (toInput.value && toInput.value < this.value) {
        toInput.value = this.value;
      }
    });
  })();
</script>

@endsection
