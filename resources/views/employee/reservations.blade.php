@extends('layouts.employee')
  <title>Reservation - Lantaka System</title>
  <link rel="stylesheet" href="{{ asset('css/employee_reservations.css') }}">
  @vite('resources/js/employee_reservations.js')

@section('content')
    <main class="main-content">
      <div class="page-content">
        <h1 class="page-title">Reservation</h1>

        {{-- START OF FILTER FORM --}}
        <form method="GET" action="{{ route('employee.reservations') }}">
            <div class="search-container">
              <input type="text" name="search" class="search-input" placeholder="Search by name, room, or venue..." value="{{ request('search') }}">
              <button type="submit" class="search-icon" style="background:none; border:none;">🔍</button>
              </div>

            <div class="status-cards">
              <a href="{{ request()->fullUrlWithQuery(['status' => 'pending']) }}" style="text-decoration: none; color: inherit;">
                <div class="status-card pending {{ request('status') == 'pending' ? 'active' : '' }}">
                  <div class="status-label">Pending</div>
                  <div class="status-number">{{ $reservations->where('status', 'pending')->count() }}</div>
                </div>
              </a>
              
              <a href="{{ request()->fullUrlWithQuery(['status' => 'confirmed']) }}" style="text-decoration: none; color: inherit;">
                <div class="status-card confirmed {{ request('status') == 'confirmed' ? 'active' : '' }}">
                  <div class="status-label">Confirmed</div>
                  <div class="status-number">{{ $reservations->where('status', 'confirmed')->count() }}</div>
                </div>
              </a>

              <a href="{{ request()->fullUrlWithQuery(['status' => 'completed']) }}" style="text-decoration: none; color: inherit;">
                <div class="status-card completed {{ request('status') == 'completed' ? 'active' : '' }}">
                  <div class="status-label">Completed</div>
                  <div class="status-number">{{ $reservations->where('status', 'completed')->count() }}</div>
                </div>
              </a>

              <a href="{{ request()->fullUrlWithQuery(['status' => 'cancelled']) }}" style="text-decoration: none; color: inherit;">
                <div class="status-card cancelled {{ request('status') == 'cancelled' ? 'active' : '' }}">
                    <div class="status-label">Cancelled</div>
                    <div class="status-number">
                        {{-- Count cancelled, declined, and rejected together --}}
                        {{ $reservations->whereIn('status', ['cancelled', 'declined', 'rejected'])->count() }}
                    </div>
                </div>
              </a>
            </div>

            <div class="filter-section">
              <div class="filter-group">
                {{-- Added name="date" and onchange to auto-submit --}}
                <select name="date" class="filter-select" onchange="this.form.submit()">
                  <option value="">Date ▼</option>
                  <option value="last_week" {{ request('date') == 'last_week' ? 'selected' : '' }}>Last week</option>
                  <option value="last_month" {{ request('date') == 'last_month' ? 'selected' : '' }}>Last month</option>
                  <option value="last_year" {{ request('date') == 'last_year' ? 'selected' : '' }}>Last year</option>
                </select>
              </div>
              <div class="filter-group">
                <select name="client_type" class="filter-select" onchange="this.form.submit()">
                  <option value="">Client Type ▼</option>
                  <option value="Internal" {{ request('client_type') == 'Internal' ? 'selected' : '' }}>Internal</option>
                  <option value="External" {{ request('client_type') == 'External' ? 'selected' : '' }}>External</option>
                </select>
              </div>
              <div class="filter-group">
                <select name="accommodation_type" class="filter-select" onchange="this.form.submit()">
                  <option value="">Accommodation Type ▼</option>
                  <option value="room" {{ request('accommodation_type') == 'room' ? 'selected' : '' }}>Room</option>
                  <option value="venue" {{ request('accommodation_type') == 'venue' ? 'selected' : '' }}>Venue</option>
                </select>
              </div>

              {{-- Clear Filters Button --}}
              @if(request()->anyFilled(['search', 'date', 'client_type', 'accommodation_type', 'status']))
                <a href="{{ route('employee.reservations') }}" style="text-decoration:none; color:#d9534f; margin-left:15px; font-weight:bold;">✕ Clear All Filters</a>
              @endif
            </div>
        </form>
        {{-- END OF FILTER FORM --}}

        <div class="table-wrapper">
          <table class="reservation-table">
            <thead>
              <tr>
                <th>Name</th>
                <th>Client Type</th>
                <th>Accommodation</th>
                <th>Check-in</th>
                <th>Check-out</th>
                <th>No. of Pax</th>
                <th style="display: flex; width: 150px; justify-content: center;">
                  Status
                </th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              
              {{-- DYNAMIC LOOP STARTS HERE --}}
              @forelse($reservations as $reservation)
                  <tr>
                    <td class="name-cell">
                      <span class="user-icon">👤</span>
                      <span>{{ $reservation->user->name ?? 'Unknown User' }}</span>
                    </td>
                    
                    <td>{{ $reservation->user->usertype ?? 'External' }}</td> 

                    <td>
                        @if($reservation->type == 'room' && $reservation->room)
                            Room: <strong>{{ $reservation->room->room_number }}</strong>
                        @elseif($reservation->type == 'venue' && $reservation->venue)
                            Venue: <strong>{{ $reservation->venue->Venue_Name ?? $reservation->venue->name }}</strong>
                        @else
                            <span style="color: #d9534f;">Item Missing</span>
                        @endif
                    </td>
                    
                    <td>{{ \Carbon\Carbon::parse($reservation->check_in)->format('m/d/Y') }}</td>
                    <td>{{ \Carbon\Carbon::parse($reservation->check_out)->format('m/d/Y') }}</td>
                    <td>{{ $reservation->pax }}</td>
                    
                    <td>
                        <span class="badge {{ strtolower($reservation->status) }}-badge">
                            {{ ucfirst($reservation->status) }}
                        </span>
                    </td>
                    
                    <td class="action-cell">
                      @php
                        $accName = $reservation->type == 'room' 
                            ? 'Room: ' . ($reservation->room->room_number ?? 'N/A') 
                            : 'Venue: ' . ($reservation->venue->Venue_Name ?? $reservation->venue->name ?? 'N/A');

                        $foodItems = isset($reservation->foods) ? $reservation->foods->groupBy('food_category') : [];
                      @endphp

                      <button class="expand-btn"
                              data-info="{{ json_encode([
                                  'id' => str_pad($reservation->id, 5, '0', STR_PAD_LEFT),
                                  'status' => strtolower($reservation->status), // Changed $res to $reservation
                                  'name' => $reservation->user->name ?? 'Unknown',
                                  'accommodation' => $accName,
                                  'pax' => $reservation->pax,
                                  'check_in' => \Carbon\Carbon::parse($reservation->check_in)->format('F d, Y'),
                                  'check_out' => \Carbon\Carbon::parse($reservation->check_out)->format('F d, Y'),
                                  'foods' => $reservation->foods
                              ]) }}">
                        ⤢
                      </button>
                    </td>
                  </tr>
              @empty
                  <tr>
                      <td colspan="8" style="text-align: center; padding: 20px;">
                          No reservations found matching your filters.
                      </td>
                  </tr>
              @endforelse
              {{-- DYNAMIC LOOP ENDS HERE --}}

            </tbody>
          </table>
        </div>
      </div>
      <x-modal_e_reservations/>

    </main>
@endsection