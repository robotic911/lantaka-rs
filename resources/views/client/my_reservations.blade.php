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

                @php
                    // Compute checkout amount consistently (rate × nights/days + food + fees − discount)
                    $colCheckIn    = \Carbon\Carbon::parse($res->Room_Reservation_Check_In_Time  ?? $res->Venue_Reservation_Check_In_Time);
                    $colCheckOut   = \Carbon\Carbon::parse($res->Room_Reservation_Check_Out_Time ?? $res->Venue_Reservation_Check_Out_Time);
                    $colClientType = auth()->user()->Account_Type ?? 'External';

                    if ($res->type === 'room' && $res->room) {
                        // Room Total_Price already includes extras and discount from all code paths
                        $colAmount = (float) ($res->Room_Reservation_Total_Price ?? 0);
                    } elseif ($res->type === 'venue' && $res->venue) {
                        $colDays      = max(1, $colCheckIn->diffInDays($colCheckOut) + 1); // inclusive
                        $colRate      = ($colClientType === 'Internal')
                            ? (float) ($res->venue->Venue_Internal_Price ?? 0)
                            : (float) ($res->venue->Venue_External_Price ?? 0);
                        $colFoodTotal = ($res->foods ?? collect())->sum('pivot.Food_Reservation_Total_Price')
                                      + ($res->foodSetReservations ?? collect())->sum('Food_Reservation_Total_Price');
                        $colFees      = (float) ($res->Venue_Reservation_Additional_Fees ?? 0);
                        $colDisc      = (float) ($res->Venue_Reservation_Discount ?? 0);
                        $colAmount    = $colRate * $colDays + $colFoodTotal + $colFees - $colDisc;
                    } else {
                        $colAmount = 0;
                    }
                @endphp
                <td class="amount">₱ {{ number_format($colAmount, 2) }}</td>

              <td style="display:flex; flex-direction:column; align-items: center; justify-content:center; width:100%; gap:4px;">
                  {{-- Main reservation status badge --}}
                  @php
                    $status = null;

                    if ($res->type === 'room' && $res->room) {
                        $status = $res->Room_Reservation_Status;
                    } elseif ($res->type === 'venue' && $res->venue) {
                        $status = $res->Venue_Reservation_Status;
                    }
                  @endphp

                  {{-- Cancellation badge --}}
                  

                  {{-- Reservation status badge --}}
                  @if($status)
                    @if($res->cancellation_status === 'pending')
                      <span class="status-badge client-cancel-req-badge">Cancellation Request</span>
                    @elseif($res->change_request_status === 'pending')
                      <span class="status-badge client-change-req-badge">Pending Change Request</span>
                    @else
                      <span class="status-badge {{ strtolower($status) }}">
                        {{ ucfirst($status) }}
                      </span>
                    @endif
                  @endif
                  {{-- Cancellation pending indicator --}}
                  
              </td>

              <td class="action-cell">
                @php
                    $accName = '';
                    if ($res->type === 'room' && $res->room) {
                        $accName  = 'Room ' . $res->room->Room_Number;
                        $res->pax = $res->Room_Reservation_Pax;
                    } elseif ($res->type === 'venue' && $res->venue) {
                        $accName  = $res->venue->Venue_Name;
                        $res->pax = $res->Venue_Reservation_Pax;
                    }

                    // Individual food items (Food_ID-based rows via BelongsToMany)
                    $indivFoodTotal = ($res->type === 'venue' && isset($res->foods) && $res->foods)
                        ? $res->foods->sum('pivot.Food_Reservation_Total_Price')
                        : 0;

                    // Set reservation rows (Food_Set_ID-based, one row per set selection)
                    $setRows      = ($res->type === 'venue') ? ($res->foodSetReservations ?? collect()) : collect();
                    $setFoodTotal = $setRows->sum('Food_Reservation_Total_Price');
                    $resFoodTotal = $indivFoodTotal + $setFoodTotal;

                    // Build food_set_rows for JS display.
                    // Food_Set_ID is now a TEXT field:  "setId",["riceId","drinksId","dessertId","fruitId"]
                    // We parse it here and look up all food names (set definition + customisations).
                    $foodSetRows = $setRows->map(function ($r) {
                        $rawText   = $r->Food_Set_ID ?? '';
                        $setId     = null;
                        $customIds = ['', '', '', ''];

                        // Buffet format: "buffet:350" or "buffet:380"
                        if (preg_match('/^buffet:(\d+)$/i', $rawText, $bm)) {
                            return [
                                'serving_date' => $r->Food_Reservation_Serving_Date,
                                'meal_time'    => $r->Food_Reservation_Meal_time,
                                'total_price'  => (float) $r->Food_Reservation_Total_Price,
                                'set_id'       => null,
                                'set_name'     => 'Buffet',
                                'food_names'   => ['₱' . number_format((int) $bm[1], 2) . ' per pax'],
                            ];
                        }

                        // New format: "5",["12","18","21","9"]
                        if (preg_match('/^"(\d+)",(\[.*\])$/', $rawText, $m)) {
                            $setId     = (int) $m[1];
                            $decoded   = json_decode($m[2], true);
                            $customIds = is_array($decoded) ? $decoded : ['', '', '', ''];
                        } elseif (is_numeric($rawText) && $rawText !== '') {
                            // Legacy: plain integer (records saved before the migration)
                            $setId = (int) $rawText;
                        }

                        $set = $setId ? \App\Models\FoodSet::find($setId) : null;

                        // Base set foods (non-rice, non-drinks — those come from customIds)
                        $setFoodNames = [];
                        if ($set) {
                            $setFoodNames = \App\Models\Food::whereIn('Food_ID', $set->Food_Set_Food_IDs ?? [])
                                ->whereNotIn('Food_Category', ['rice', 'drinks'])
                                ->pluck('Food_Name')
                                ->toArray();
                        }

                        // Customisation food names: [rice, drinks, dessert, fruit]
                        $customFoodNames = [];
                        foreach ($customIds as $fid) {
                            if (!empty($fid) && is_numeric($fid)) {
                                $food = \App\Models\Food::find((int) $fid);
                                if ($food) {
                                    $customFoodNames[] = $food->Food_Name;
                                }
                            }
                        }

                        return [
                            'serving_date' => $r->Food_Reservation_Serving_Date,
                            'meal_time'    => $r->Food_Reservation_Meal_time,
                            'total_price'  => (float) $r->Food_Reservation_Total_Price,
                            'set_id'       => $setId,
                            'set_name'     => $set ? $set->Food_Set_Name : 'Unknown Set',
                            // food_names = set definition foods + user's rice/drinks/dessert/fruit choices
                            'food_names'   => array_merge($setFoodNames, $customFoodNames),
                        ];
                    })->toArray();

                    $resTotalRaw   = $res->Room_Reservation_Total_Price ?? $res->Venue_Reservation_Total_Price ?? 0;

                    // Compute accommodation cost = rate × nights/days (internal or external)
                    $resCheckIn    = \Carbon\Carbon::parse($res->Room_Reservation_Check_In_Time  ?? $res->Venue_Reservation_Check_In_Time);
                    $resCheckOut   = \Carbon\Carbon::parse($res->Room_Reservation_Check_Out_Time ?? $res->Venue_Reservation_Check_Out_Time);
                    // Rooms: exclusive diff (nights). Venues: inclusive (+1 for both check-in and check-out day)
                    $resNights     = ($res->type === 'venue')
                        ? max(1, $resCheckIn->diffInDays($resCheckOut) + 1)
                        : max(1, $resCheckIn->diffInDays($resCheckOut));
                    $resClientType = auth()->user()->Account_Type ?? 'External';
                    if ($res->type === 'room' && $res->room) {
                        $resRate = ($resClientType === 'Internal')
                            ? ($res->room->Room_Internal_Price ?? 0)
                            : ($res->room->Room_External_Price ?? 0);
                    } elseif ($res->type === 'venue' && $res->venue) {
                        $resRate = ($resClientType === 'Internal')
                            ? ($res->venue->Venue_Internal_Price ?? 0)
                            : ($res->venue->Venue_External_Price ?? 0);
                    } else {
                        $resRate = 0;
                    }
                    $resAccommodationTotal = $resRate * $resNights;
                    $resVenueTotal         = $resAccommodationTotal; // rate × days (venue)
                    $resPurpose    = $res->Room_Reservation_Purpose ?? $res->Venue_Reservation_Purpose ?? null;
                @endphp

                @php
                    $resInfoArray = [
                        'real_id'              => $res->type === 'room' ? $res->Room_Reservation_ID : $res->Venue_Reservation_ID,
                        'display_id'           => str_pad($res->type === 'room' ? $res->Room_Reservation_ID : $res->Venue_Reservation_ID, 5, '0', STR_PAD_LEFT),
                        'type'                 => $res->type,
                        'accommodation'        => $accName,
                        'pax'                  => $res->pax,
                        'check_in'             => \Carbon\Carbon::parse($res->Room_Reservation_Check_In_Time  ?? $res->Venue_Reservation_Check_In_Time)->format('F d, Y'),
                        'check_out'            => \Carbon\Carbon::parse($res->Room_Reservation_Check_Out_Time ?? $res->Venue_Reservation_Check_Out_Time)->format('F d, Y'),
                        'check_in_raw'         => \Carbon\Carbon::parse($res->Room_Reservation_Check_In_Time  ?? $res->Venue_Reservation_Check_In_Time)->toDateString(),
                        'check_out_raw'        => \Carbon\Carbon::parse($res->Room_Reservation_Check_Out_Time ?? $res->Venue_Reservation_Check_Out_Time)->toDateString(),
                        'total'                => $res->type === 'room'
                            ? number_format($resTotalRaw, 2)
                            : number_format($resAccommodationTotal + $resFoodTotal + ($res->Venue_Reservation_Additional_Fees ?? 0) - ($res->Venue_Reservation_Discount ?? 0), 2),
                        'food_total'           => number_format($resFoodTotal, 2),
                        'venue_total'          => number_format($resVenueTotal, 2),
                        'accommodation_total'  => number_format($resAccommodationTotal, 2),
                        'rate_per_night'       => number_format($resRate, 2),
                        'nights_or_days'       => $resNights,
                        'payment_status'       => $res->Room_Reservation_Payment_Status ?? $res->Venue_Reservation_Payment_Status ?? null,
                        'foods'                => $res->type === 'venue' ? ($res->foods ?? []) : [],
                        'food_set_rows'        => $foodSetRows ?? [],
                        'purpose'              => $resPurpose,
                        'status'               => $res->status,
                        'cancellation_status'  => $res->cancellation_status,
                        'change_request_status'=> $res->change_request_status,
                        'change_request_type'  => $res->change_request_type,
                    ];
                @endphp
                <button class="expand-button"
                    data-info='@json($resInfoArray)'>
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
