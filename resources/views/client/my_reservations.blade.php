@extends('layouts.client')
  <title>My Reservations - Lantaka Portal</title>
  <link rel="stylesheet" href="{{asset('css/client_my_reservations.css') }}">
  <link href="https://fonts.googleapis.com/css2?family=Alexandria:wght@200;300;400;500;600;700;800;900&family=Arsenal:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">
  @vite('resources/js/client_my_reservations.js')

@section('content')
  <h1 class="page-title">My Reservations</h1>

    <form action="{{ route('client.my_reservations') }}" method="GET" class="search-filters">
    <div class="search-box">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <circle cx="11" cy="11" r="8"></circle>
            <path d="m21 21-4.35-4.35"></path>
        </svg>
        {{-- Added name="search" and value persistence --}}
        <input type="text" name="search" placeholder="Search ID or Name" value="{{ request('search') }}" onchange="this.form.submit()">
    </div>

    <div class="filter-dropdowns">
        {{-- Reservation Type Filter --}}
        <select name="accommodation_type" class="filter-select" onchange="this.form.submit()">
            <option value="">Reservation Type</option>
            <option value="room" {{ request('accommodation_type') == 'room' ? 'selected' : '' }}>Room</option>
            <option value="venue" {{ request('accommodation_type') == 'venue' ? 'selected' : '' }}>Venue</option>
        </select>
        {{-- Status Filter --}}
        <select name="status" class="filter-select" onchange="this.form.submit()">
            <option value="">Status</option>
            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
            <option value="confirmed" {{ request('status') == 'confirmed' ? 'selected' : '' }}>Confirmed</option>
            <option value="checked-in" {{ request('status') == 'checked-in' ? 'selected' : '' }}>Checked-in</option>
            <option value="checked-out" {{ request('status') == 'checked-out' ? 'selected' : '' }}>Checked-out</option>
            <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
            <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
        </select>
    </div>
</form>

    <div class="table-container">
      <table class="reservations-table">
        <thead>
          <tr>
            <th>Reservation</th>
            <th>Check-in</th>
            <th>Check-out</th>
            <th>No. of Pax</th>
            <th>Checkout Amount</th>
            <th style="display:flex; align-items: center; justify-content:center;">Status</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          {{-- DYNAMIC LOOP STARTS HERE --}}
          @forelse($reservations as $res)
            <tr>
              <td class="reservation-name">
                  @if($res->type === 'room' && $res->room)
                      <strong style="font-size: 1.1em;">
                        Room {{ $res->room->Room_Number}}
                      </strong>
                      <small style="display:block; color: #666;">Accommodation Room</small>
                  @elseif($res->type === 'venue' && $res->venue)
                      <strong style="font-size: 1.1em;">
                      {{ $res->venue->Venue_Name }}
                      </strong>
                      <small style="display:block; color: #666;">Event Venue</small>
                  @else
                      <span style="color: #e74c3c;">Item Not Found</span>
                  @endif
              </td>

              {{-- Date Formatting --}}
                <td>
                    {{ \Carbon\Carbon::parse(
                        $res->Room_Reservation_Check_In_Time ??
                        $res->Venue_Reservation_Check_In_Time ??
                        now()
                    )->format('m/d/Y') }}
                </td>

                {{-- Check-out Date --}}
                <td>
                    {{ \Carbon\Carbon::parse(
                        $res->Room_Reservation_Check_Out_Time ??
                        $res->Venue_Reservation_Check_Out_Time ??
                        now()
                    )->format('m/d/Y') }}
                </td>

                <td>{{ $res->Room_Reservation_Pax ?? $res->Venue_Reservation_Pax }}</td>
                <td class="amount">
                    ₱ {{ number_format(
                        $res->Room_Reservation_Total_Price ??
                        $res->Venue_Reservation_Total_Price ??
                        0, 2)
                    }}
                </td>

              <td style="display:flex; flex-direction:column; align-items: center; justify-content:center; width:100%; gap:4px;">
                  {{-- Main reservation status badge --}}
                  @if($res->type === 'room' && $res->room)
                    <span class="status-badge {{ strtolower($res->Room_Reservation_Status) }}">
                        {{ ucfirst($res->Room_Reservation_Status) }}
                    </span>
                  @elseif($res->type === 'venue' && $res->venue)
                    <span class="status-badge {{ strtolower($res->Venue_Reservation_Status) }}">
                        {{ ucfirst($res->Venue_Reservation_Status) }}
                    </span>
                  @endif
                  {{-- Cancellation pending indicator --}}
                  @if($res->cancellation_status === 'pending')
                    <span class="status-badge client-cancel-req-badge">⏳ Cancel Req.</span>
                  @endif
              </td>

              <td class="action-cell">
                @php
                    $accName = '';
                    if($res->type === 'room' && $res->room){
                      $accName = 'Room' . ' ' . $res->room->Room_Number;
                      $res->pax = $res->Room_Reservation_Pax;
                    }

                    elseif($res->type === 'venue' && $res->venue){
                      $accName = $res->venue->Venue_Name;
                      $res->pax = $res->Venue_Reservation_Pax;
                    }
                @endphp

                <button class="expand-button"
                    data-info="{{ json_encode([
                        'real_id'             => $res->type === 'room' ? $res->Room_Reservation_ID : $res->Venue_Reservation_ID,
                        'display_id'          => str_pad($res->type === 'room' ? $res->Room_Reservation_ID : $res->Venue_Reservation_ID, 5, '0', STR_PAD_LEFT),
                        'type'                => $res->type,
                        'accommodation'       => $accName,
                        'pax'                 => $res->pax,
                        'check_in'            => \Carbon\Carbon::parse($res->Room_Reservation_Check_In_Time  ?? $res->Venue_Reservation_Check_In_Time)->format('F d, Y'),
                        'check_out'           => \Carbon\Carbon::parse($res->Room_Reservation_Check_Out_Time ?? $res->Venue_Reservation_Check_Out_Time)->format('F d, Y'),
                        'check_in_raw'        => \Carbon\Carbon::parse($res->Room_Reservation_Check_In_Time  ?? $res->Venue_Reservation_Check_In_Time)->toDateString(),
                        'check_out_raw'       => \Carbon\Carbon::parse($res->Room_Reservation_Check_Out_Time ?? $res->Venue_Reservation_Check_Out_Time)->toDateString(),
                        'total'               => number_format($res->Room_Reservation_Total_Price ?? $res->Venue_Reservation_Total_Price ?? 0, 2),
                        'payment_status'      => $res->Room_Reservation_Payment_Status ?? $res->Venue_Reservation_Payment_Status ?? null,
                        'foods'               => $res->foods,
                        'status'              => $res->status,
                        'cancellation_status' => $res->cancellation_status,
                    ]) }}">
                    ⤡
                </button>
              </td>
            </tr>
          @empty
            {{-- This row shows if there are NO reservations --}}
            <tr>
                <td colspan="7" style="text-align: center; padding: 20px;">
                    You have no reservations yet.
                </td>
            </tr>
          @endforelse
        </tbody>
      </table>
      <div style="margin-top: 16px; padding: 0 4px;">
        {{ $reservations->links('vendor.pagination.simple') }}
      </div>
    </div>

  <x-my_reservations_modal/>
@endsection
