@extends('layouts.client')
  <title>Book Now - Lantaka Room and Venue Reservation Portal</title>
  <link rel="stylesheet" href="{{asset('css/client_room_venue.css')}}">
  <link href="https://fonts.googleapis.com/css2?family=Alexandria:wght@700;800&family=Arsenal:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">

@section('content')
    <section class="hero">
      <h2 class="hero-title">Purposeful spaces.</h2>
      <p class="hero-subtitle">Where Faith, Fellowship, and Formation Come Together.</p>
    </section>

    <section class="filters-section">
      <form action="{{ route('client.index') }}" method="GET" id="filterForm">
        <input type="hidden" name="type"      id="typeInput"      value="{{ request('type', 'All') }}">
        <input type="hidden" name="room_type" id="roomTypeInput"  value="{{ request('room_type', '') }}">
        <input type="hidden" name="date_from" id="dateFromHidden" value="{{ $dateFrom ?? '' }}">
        <input type="hidden" name="date_to"   id="dateToHidden"   value="{{ $dateTo ?? '' }}">
        <div class="filter-tabs">
          <button type="button" class="tab-btn {{ request('type', 'All') == 'All' ? 'active' : '' }}" onclick="filterTab('All')">All</button>

          {{-- Rooms button with anchored dropdown --}}
          <div class="rooms-btn-wrap">
            <button type="button" class="tab-btn {{ request('type') == 'Rooms' ? 'active' : '' }}" onclick="filterTab('Rooms')">Rooms</button>

            @if($roomTypes->isNotEmpty())
              <div id="roomTypeTabs" class="room-type-dropdown {{ request('type') === 'Rooms' ? 'visible' : '' }}">
                @foreach($roomTypes as $rt)
                  <button type="button"
                          class="rt-btn {{ request('room_type') === $rt ? 'active' : '' }}"
                          onclick="filterRoomType('{{ $rt }}')">
                    {{ $rt }}
                  </button>
                @endforeach
              </div>
            @endif
          </div>

          <button type="button" class="tab-btn {{ request('type') == 'Venue' ? 'active' : '' }}" onclick="filterTab('Venue')">Venue</button>
        </div>

        <div class="filter-dropdowns">
          <select name="capacity" class="dropdown" onchange="this.form.submit()">
            <option value="">Capacity</option>
            <option value="2"   {{ request('capacity') == '2'   ? 'selected' : '' }}>2 Guests</option>
            <option value="4"   {{ request('capacity') == '4'   ? 'selected' : '' }}>4 Guests</option>
            <option value="50+" {{ request('capacity') == '50+' ? 'selected' : '' }}>50+ Guests</option>
          </select>
        </div>
      </form>

      {{-- Date range picker (its own form so it can submit independently) --}}
      <form action="{{ route('client.index') }}" method="GET" id="dateRangeForm" class="date-range-form">
        {{-- preserve type, capacity & room_type --}}
        <input type="hidden" name="type"      value="{{ request('type', 'All') }}">
        <input type="hidden" name="capacity"  value="{{ request('capacity') }}">
        <input type="hidden" name="room_type" value="{{ request('room_type') }}">

        <div class="date-range-wrap">
          <div class="date-field">
            <label for="clientDateFrom">Check-in</label>
            <input type="date" id="clientDateFrom" name="date_from"
                   class="date-input-client"
                   value="{{ $dateFrom ?? '' }}">
          </div>

          <span class="date-arrow">→</span>

          <div class="date-field">
            <label for="clientDateTo">Check-out</label>
            <input type="date" id="clientDateTo" name="date_to"
                   class="date-input-client"
                   value="{{ $dateTo ?? '' }}">
          </div>

          <button type="submit" class="btn-search-dates">Search</button>

          @if($dateFrom && $dateTo)
            <a href="{{ route('client.index', array_filter(['type' => request('type'), 'capacity' => request('capacity')])) }}"
               class="btn-clear-dates-client">✕ Clear</a>
          @endif
        </div>
      </form>
    </section>

    {{-- Result label shown only when a date range is active --}}
    @if($dateFrom && $dateTo)
      <div class="date-result-banner">
        <span class="date-result-icon">✓</span>
        @php
          $activeType = request('type', 'All');
          $bannerLabel = match($activeType) {
              'Rooms' => 'Available rooms for',
              'Venue' => 'Available venues for',
              default => 'Available rooms & venues for',
          };
        @endphp
        {{ $bannerLabel }}
        <strong>{{ \Carbon\Carbon::parse($dateFrom)->format('M j, Y') }}</strong>
        &nbsp;→&nbsp;
        <strong>{{ \Carbon\Carbon::parse($dateTo)->format('M j, Y') }}</strong>
      </div>
    @endif

    @if(session('error'))
    <script>
      document.addEventListener('DOMContentLoaded', function () {
        window.showToast && window.showToast('{{ session('error') }}', 'error');
      });
    </script>
    @endif

    <script>
      function filterTab(type) {
        document.getElementById('typeInput').value = type;
        if (type !== 'Rooms') {
          document.getElementById('roomTypeInput').value = '';
        }
        var rtTabs = document.getElementById('roomTypeTabs');
        if (rtTabs) rtTabs.classList.toggle('visible', type === 'Rooms');
        document.getElementById('filterForm').submit();
      }

      function filterRoomType(roomType) {
        document.getElementById('roomTypeInput').value = roomType;
        document.getElementById('filterForm').submit();
      }

      (function () {
        const from = document.getElementById('clientDateFrom');
        const to   = document.getElementById('clientDateTo');
        if (!from || !to) return;

        from.addEventListener('change', function () {
          to.min = this.value;
          if (to.value && to.value < this.value) to.value = this.value;
        });
      })();
    </script>

    <section class="accommodations">

        @if(isset($all_accommodations) && $all_accommodations->isNotEmpty())
            @foreach($all_accommodations as $item)
              <a href="{{ route('client.show', parameters: ['category' => $item->category, 'id' => $item->id]) }}"
              class="book-btn">
                <div class="accommodations-grid">
                    <div class="card">
                        <div class="card-image">
                            <img src="{{ $item->image ? media_url($item->image) : asset('images/' . ($item->category === 'Room' ? 'placeholder_room' : 'placeholder_venue') . '.svg') }}"
                                alt="{{ $item->display_name }}">
                        </div>

                        <div class="card-content">
                            <div>
                                <p class="card-type">{{ $item->category }}</p>
                                <h3 class="card-title">{{ $item->display_name }}</h3>

                                <div class="card-details">
                                    <span class="detail-item">👤  {{ $item->capacity }} Guests</span>
                                    @if (isset(Auth()->user()->Account_Type))
                                        @if (Auth()->user()->Account_Type == 'Internal')
                                          <span class="detail-item">₱ {{ number_format($item->internal_price, 2) }}</span>
                                        @else
                                          <span class="detail-item">₱ {{ number_format($item->external_price, 2) }}</span>
                                        @endif
                                    @else
                                        <span class="detail-item">₱ {{ number_format($item->external_price, 2) }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
              </a>
            @endforeach

        @else
            <p class="no-results">
              @if($dateFrom && $dateTo)
                No rooms or venues are available from
                <strong>{{ \Carbon\Carbon::parse($dateFrom)->format('M j, Y') }}</strong>
                to
                <strong>{{ \Carbon\Carbon::parse($dateTo)->format('M j, Y') }}</strong>.
              @else
                No rooms or venues found.
              @endif
            </p>
        @endif
    </section>
@endsection
