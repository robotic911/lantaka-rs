@extends('layouts.employee')
    <title>Guest Management Dashboard</title>
    <link rel="stylesheet" href="{{ asset('css/employee_reservations.css') }}">
    @vite('resources/js/employee_reservations.js')

@section('content')
<main class="main-content">
      <div class="page-content">
        <h1 class="page-title">Guest</h1>

        <form method="GET" action="{{ route('employee.guest') }}">
            
            <div class="search-container">
              <input type="text" name="search" class="search-input" placeholder="Search names, rooms, or venues" value="{{ request('search') }}">
              <button type="submit" class="search-icon" style="background:none; border:none;">🔍</button>
            </div>

            <div class="status-cards">

              <a href="{{ request('status') == 'confirmed'
                      ? route('employee.guest', request()->except('status'))
                      : request()->fullUrlWithQuery(['status' => 'confirmed']) }}"
                style="text-decoration:none;color:inherit;">
                <div class="status-card pending {{ request('status') == 'confirmed' ? 'active' : '' }}">
                    <div class="status-label">Pending</div>
                    <div class="status-number">{{ $allForCounts->where('status','confirmed')->count() }}</div>
                </div>
              </a>


              <a href="{{ request('status') == 'checked-in'
                      ? route('employee.guest', request()->except('status'))
                      : request()->fullUrlWithQuery(['status' => 'checked-in']) }}"
                style="text-decoration:none;color:inherit;">
                <div class="status-card confirmed {{ request('status') == 'checked-in' ? 'active' : '' }}">
                    <div class="status-label">Checked-in</div>
                    <div class="status-number">{{ $allForCounts->where('status','checked-in')->count() }}</div>
                </div>
              </a>


              <a href="{{ request('status') == 'checked-out'
                      ? route('employee.guest', request()->except('status'))
                      : request()->fullUrlWithQuery(['status' => 'checked-out']) }}"
                style="text-decoration:none;color:inherit;">
                <div class="status-card completed {{ request('status') == 'checked-out' ? 'active' : '' }}">
                    <div class="status-label">Checked-out</div>
                    <div class="status-number">{{ $allForCounts->where('status','checked-out')->count() }}</div>
                </div>
              </a>


              <a href="{{ request('status') == 'cancelled'
                      ? route('employee.guest', request()->except('status'))
                      : request()->fullUrlWithQuery(['status' => 'cancelled']) }}"
                style="text-decoration:none;color:inherit;">
                <div class="status-card cancelled {{ request('status') == 'cancelled' ? 'active' : '' }}">
                    <div class="status-label">Cancelled</div>
                    <div class="status-number">{{ $allForCounts->where('status','cancelled')->count() }}</div>
                </div>
              </a>

              </div>

            <div class="filter-section" style="display: flex; align-items: center; gap: 15px;">
              <div class="filter-group">
                <select name="date" class="filter-select" onchange="this.form.submit()">
                  <option value="">Date ▼</option>
                  <option value="last_week " {{ request('date') == 'last_week' ? 'selected' : '' }}>Last 7 Days</option>
                  <option value="last_month" {{ request('date') == 'last_month' ? 'selected' : '' }}>Last 30 Days</option>
                  <option value="last_year" {{ request('date') == 'last_year' ? 'selected' : '' }}>This Year</option>
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

              @if(request()->hasAny(['search', 'date', 'client_type', 'accommodation_type']))
                  <a href="{{ url()->current() }}" style="text-decoration: none; color: #e74c3c; font-size: 14px; font-weight: bold; margin-left: 10px;">✕ Clear</a>
              @endif
            </div>
            
        </form>

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
              @forelse($reservations as $res)
              @if(in_array($res->status, ['confirmed','checked-in','checked-out','cancelled']))

                <tr>
                  <td class="name-cell">
                    <span class="user-icon">
                      <img src="{{ asset(path: 'images/logo/topnav/user-avatar.svg') }}" alt="reservations">
                    </span>
                    <span>{{ $res->user->name ?? $res->user->first_name ?? 'Unknown' }}</span>
                  </td>

                  <td>{{ $res->user->usertype ?? 'External' }}</td>

                  <td>
                    @if($res->type === 'room' && $res->room)
                        Room: <strong>{{ $res->room->room_number }}</strong>
                    @elseif($res->type === 'venue' && $res->venue)
                        Venue: <strong>{{ $res->venue->name }}</strong>
                    @else
                        <span style="color: #e74c3c;">Not Found</span>
                    @endif
                  </td>

                  <td>{{ \Carbon\Carbon::parse($res->check_in)->format('m/d/Y') }}</td>
                  <td>{{ \Carbon\Carbon::parse($res->check_out)->format('m/d/Y') }}</td>
                  <td>{{ $res->pax }}</td>

                  <td>
                    @if($res->status === 'confirmed')
                      <span class="badge pending-badge">Pending</span>
                    @else
                      <span class="badge {{ strtolower($res->status) }}-badge">
                        {{ ucfirst($res->status) }}
                      </span>
                    @endif
                  </td>

                  @php
                      $price=$res->total_amount;
                      $accName = '';
                      if($res->type === 'room' && $res->room) {
                          $accName = 'Room ' . $res->room->room_number;
                      } elseif($res->type === 'venue' && $res->venue) {
                          $accName = 'Venue: ' . $res->venue->name;
                      }
                      $userId = $res->Client_ID;
                      $reservationType = $res->type == 'room' 
                              ? 'Room': 'Venue';
                  @endphp
                  
                  <td class="action-cell">
                      {{-- We are removing the Check-in form/button from here --}}
                      <button class="expand-btn" data-info="{{
                        json_encode([
                            'id' => $res->id,
                            'status' => strtolower($res->status),
                            'name' => $res->user->name ?? $res->user->first_name ?? 'Unknown',
                            'accommodation' => $accName,
                            'phone' => $res->user->phone ?? 'Error phone',
                            'email' => $res->user->email ?? 'Error email',
                            'type' => $res->user->usertype,
                            'res_type' => $res->type,
                            'accommodationType' => $reservationType ?? 'Error accomodation type',
                            'price' => $price,
                            'pax' => $res->pax,
                            'check_in' => \Carbon\Carbon::parse($res->check_in)->format('F d, Y'),
                            'check_out' => \Carbon\Carbon::parse($res->check_out)->format('F d, Y'),
                            'foods' => $res->foods,
                            'userId' => $userId
                        ]) }}">
                          ⤢
                      </button>
                  </td>
                </tr>
                @endif
              @empty
                <tr>
                    <td colspan="8" style="text-align: center; padding: 30px; color: #7f8c8d;">
                        No guest reservations found matching your filters.
                    </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
      
      <x-modal_e_reservations/>
</main>
@endsection