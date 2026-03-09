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
          <select name="status" class="status-filter" onchange="this.form.submit()">
            <option value="">Status</option>

            <option value="Available" {{ request('status') == 'Available' ? 'selected' : '' }}>
              Available
            </option>

            <option value="Unavailable" {{ request('status') == 'Unavailable' ? 'selected' : '' }}>
              Unavailable
            </option>
          </select>
        </form>
        
          <div class="button-section">
            <button class="btn btn-secondary" id="food_button">Food Menu</button>
            <button class="btn btn-primary" id="add_room_venue_button">Add Room/Venue</button>
          </div>
        </div>

        <div class="room-venue-divider">

          <!-- Div Content for both Rooms and Venues -->
          <div class="room-venue-content">
            
            <!-- Rooms Section -->
            <section class="rooms-section">
              <h2 class="section-title">Room</h2>
              <div class="rooms-grid">

              @foreach($rooms as $room)
                <div class="room-card {{ strtolower($room->status) }}">
                  {{ $room->room_number }}

                  <input type="hidden" class="room-details"
                        data-id="{{ $room->id }}"
                        data-name="{{ $room->room_number }}"
                        data-type="{{ $room->room_type }}"
                        data-capacity="{{ $room->capacity }}"
                        data-price="{{ $room->price }}"
                        data-external_price="{{ $room->external_price }}"
                        data-status="{{ $room->status }}"
                        data-description="{{ $room->description }}">
                </div>
              @endforeach

                @if($rooms->isEmpty())
                  <p style="color: #666; font-style: italic;">No rooms searched or added yet.</p>
                @endif
              </div>
            </section>

            

            <!-- Venues Section -->
            <section class="venues-section">
              <h2 class="section-title">Venue</h2>
              <div class="venue-grid">
              @foreach($venues as $venue)
                <div class="venue-card {{ strtolower($venue->status) }}">
                  {{ $venue->name }}

                  <input type="hidden" class="venue-details"
                        data-id="{{ $venue->id }}"
                        data-name="{{ $venue->name }}"
                        data-capacity="{{ $venue->capacity }}"
                        data-price="{{ $venue->price }}"
                        data-external_price="{{ $venue->external_price }}"
                        data-status="{{ $venue->status }}"
                        data-description="{{ $venue->description }}">
                </div>
              @endforeach

                  @if($venues->isEmpty())
                    <p>No venues searched or added yet.</p>
                  @endif
              </div>
            </section>
          </div> 
          </div>
      </div>
  <!-- Add Room Venue Modal -->
      <!-- Modal Content -->
      <x-add_room_venue/>
      <x-create_reservation/>
      <x-employee_rv_viewing_modal/>
      <x-employee_food :foods="$foods" />

@endsection
