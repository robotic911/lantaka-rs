@extends('layouts.employee')
  <title>Rooms / Venue - Lantaka</title>
  <link rel="stylesheet" href="{{asset('css/employee_room_venue.css')}}">

  @vite('resources/js/employee/create_reservation.js')
  @vite('resources/js/employee_rv_viewing_modal.js')

  <link href="https://fonts.googleapis.com/css2?family=Alexandria:wght@200;300;400;500;600;700;800;900&family=Arsenal:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">

@section('content')
  <div class="content">
    <h1 class="page-title">Room / Venue</h1>

    {{-- ── Top Controls ──────────────────────────────── --}}
    <div class="controls-section">
      <form class="search-bar" method="GET" action="{{ route('employee.room_venue') }}">
        @if($dateFrom)<input type="hidden" name="date_from" value="{{ $dateFrom }}">@endif
        @if($dateTo)<input type="hidden" name="date_to" value="{{ $dateTo }}">@endif
        <input type="text" name="search" value="{{ request('search') }}"
               placeholder="Search room or venue" class="search-input">
        <button type="submit" class="search-icon">🔍</button>
      </form>

      <form class="filters-actions" method="GET" action="{{ route('employee.room_venue') }}">
        @if($dateFrom)<input type="hidden" name="date_from" value="{{ $dateFrom }}">@endif
        @if($dateTo)<input type="hidden" name="date_to" value="{{ $dateTo }}">@endif
        @if(request('search'))<input type="hidden" name="search" value="{{ request('search') }}">@endif
        <select name="status" class="status-filter" onchange="this.form.submit()">
          <option value="">All Status</option>
          <option value="available"        {{ request('status') == 'available'        ? 'selected' : '' }}>Available</option>
          <option value="occupied"         {{ request('status') == 'occupied'         ? 'selected' : '' }}>Occupied</option>
          <option value="undermaintenance" {{ request('status') == 'undermaintenance' ? 'selected' : '' }}>Under Maintenance</option>
          <option value="reserved"         {{ request('status') == 'reserved'         ? 'selected' : '' }}>Reserved</option>
        </select>
      </form>

      <div class="button-section">
        @if(auth()->user()->Account_Role === 'admin')
          <button class="btn btn-primary" id="add_room_venue_button">Add Room/Venue</button>
        @endif
      </div>
    </div>

    {{-- ── Date Range Picker ─────────────────────────── --}}
      <div class="availability-picker-wrap">
        <div>
          <form method="GET" action="{{ route('employee.room_venue') }}" class="availability-form" id="availabilityForm">
            @if(request('search'))<input type="hidden" name="search" value="{{ request('search') }}">@endif
            @if(request('status'))<input type="hidden" name="status" value="{{ request('status') }}">@endif
            <span class="availability-label">View Status For:</span>
            <div class="date-range-group">
              <label class="date-label" for="dateFrom">From</label>
              <input type="date" name="date_from" id="dateFrom" class="date-input" value="{{ $dateFrom ?? '' }}">
            </div>
            <div class="date-range-group">
              <label class="date-label" for="dateTo">To</label>
              <input type="date" name="date_to" id="dateTo" class="date-input" value="{{ $dateTo ?? '' }}">
            </div>
            <button type="submit" class="btn btn-check-avail">Apply</button>
            @if($dateFrom && $dateTo)
              <a href="{{ route('employee.room_venue', array_filter(['search' => request('search'), 'status' => request('status')])) }}"
                class="btn btn-clear-dates">Clear</a>
            @endif
        </div>
      
        <div>
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
      </form>
    </div>

    {{-- ── Rooms Table ───────────────────────────────── --}}
    <div class="rv-section-header">
      <span class="rv-section-title">Rooms</span>
      <span class="rv-count-badge">{{ $rooms->count() }} {{ Str::plural('room', $rooms->count()) }}</span>
    </div>
    <div class="rv-table-wrapper">
      <table class="rv-table rooms-table">
        <thead>
          <tr>
            <th>#</th>
            <th class="rv-th-photo">Photo</th>
            <th>Room Number</th>
            <th>Type</th>
            <th>Capacity</th>
            <th>Internal Price</th>
            <th>External Price</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          @forelse($rooms as $i => $room)
            @php
              $es = $room->effective_status;
              $label = match($es) {
                'available'        => 'Available',
                'occupied'         => 'Occupied',
                'reserved'         => 'Reserved',
                'undermaintenance' => 'Under Maintenance',
                default            => ucfirst($es),
              };
              $imgSrc = $room->Room_Image ? media_url($room->Room_Image) : asset('images/placeholder_room.svg');
            @endphp
            <tr class="room-card {{ $es }}">
              <td class="rv-idx">{{ $i + 1 }}
                {{-- hidden data for the JS modal, nested inside td so HTML is valid --}}
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
                       data-image="{{ $imgSrc }}">
              </td>
              <td class="rv-td-photo">
                <img src="{{ $imgSrc }}" alt="{{ $room->Room_Number }}" class="rv-thumb">
              </td>
              <td><strong>{{ $room->Room_Number }}</strong></td>
              <td>{{ $room->Room_Type ?? '—' }}</td>
              <td>{{ $room->Room_Capacity ?? '—' }}</td>
              <td class="rv-price">₱{{ number_format($room->Room_Internal_Price ?? 0, 2) }}</td>
              <td class="rv-price">₱{{ number_format($room->Room_External_Price ?? 0, 2) }}</td>
              <td><span class="rv-status-badge {{ $es }}">{{ $label }}</span></td>
            </tr>
          @empty
            <tr>
              <td colspan="8" style="text-align:center; color:#999; font-style:italic; padding:24px;">
                No rooms found.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    {{-- ── Venues Table ──────────────────────────────── --}}
    <div class="rv-section-header">
      <span class="rv-section-title">Venues</span>
      <span class="rv-count-badge">{{ $venues->count() }} {{ Str::plural('venue', $venues->count()) }}</span>
    </div>
    <div class="rv-table-wrapper">
      <table class="rv-table venues-table">
        <thead>
          <tr>
            <th>#</th>
            <th class="rv-th-photo">Photo</th>
            <th>Venue Name</th>
            <th>Capacity</th>
            <th>Internal Price / day</th>
            <th>External Price / day</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          @forelse($venues as $i => $venue)
            @php
              $es = $venue->effective_status;
              $label = match($es) {
                'available'        => 'Available',
                'occupied'         => 'Occupied',
                'reserved'         => 'Reserved',
                'undermaintenance' => 'Under Maintenance',
                default            => ucfirst($es),
              };
              $imgSrc = $venue->Venue_Image ? media_url($venue->Venue_Image) : asset('images/placeholder_venue.svg');
            @endphp
            <tr class="venue-card {{ $es }}">
              <td class="rv-idx">{{ $i + 1 }}
                {{-- hidden data for the JS modal, nested inside td so HTML is valid --}}
                <input type="hidden" class="venue-details"
                       data-id="{{ $venue->Venue_ID }}"
                       data-name="{{ $venue->Venue_Name }}"
                       data-capacity="{{ $venue->Venue_Capacity }}"
                       data-price="{{ $venue->Venue_Internal_Price }}"
                       data-external_price="{{ $venue->Venue_External_Price }}"
                       data-status="{{ $venue->Venue_Status }}"
                       data-effective_status="{{ $venue->effective_status }}"
                       data-description="{{ $venue->Venue_Description }}"
                       data-image="{{ $imgSrc }}">
              </td>
              <td class="rv-td-photo">
                <img src="{{ $imgSrc }}" alt="{{ $venue->Venue_Name }}" class="rv-thumb">
              </td>
              <td><strong>{{ $venue->Venue_Name }}</strong></td>
              <td>{{ $venue->Venue_Capacity ?? '—' }}</td>
              <td class="rv-price">₱{{ number_format($venue->Venue_Internal_Price ?? 0, 2) }}</td>
              <td class="rv-price">₱{{ number_format($venue->Venue_External_Price ?? 0, 2) }}</td>
              <td><span class="rv-status-badge {{ $es }}">{{ $label }}</span></td>
            </tr>
          @empty
            <tr>
              <td colspan="7" style="text-align:center; color:#999; font-style:italic; padding:24px;">
                No venues found.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    {{-- ── Status Legend ─────────────────────────────── --}}
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

  <x-add_room_venue/>
  <x-create_reservation_modal/>
  <x-employee_rv_viewing_modal/>

<script>
  (function () {
    const fromInput = document.getElementById('dateFrom');
    const toInput   = document.getElementById('dateTo');
    if (!fromInput || !toInput) return;
    fromInput.addEventListener('change', function () {
      toInput.min = this.value;
      if (toInput.value && toInput.value < this.value) toInput.value = this.value;
    });
  })();
</script>

@endsection
