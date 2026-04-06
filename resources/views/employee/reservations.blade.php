@extends('layouts.employee')
  <title>Reservation - Lantaka System</title>
  <link rel="stylesheet" href="{{ asset('css/employee_reservations.css') }}">
  @vite('resources/js/employee_reservations.js')

@section('content')
    <main class="main-content">
      <div class="page-content">
        <h1 class="page-title">Reservation</h1>

        {{-- START OF FILTER FORM --}}
        <form method="GET" action="{{ route('employee.reservations') }}" id="filterForm">
          <input type="hidden" name="status" value="{{ request('status') }}">
            <div class="search-container">
              <input type="text" name="search" class="search-input" placeholder="Search by name, room, or venue..." value="{{ request('search') }}">
              <button type="submit" class="search-icon" style="background:none; border:none;">🔍</button>
            </div>

            <div class="status-cards">
              {{-- ── Priority card: Cancel Requested (shown first, distinct orange-red) ── --}}
              <a href="{{ request('status') == 'cancel_requested' ? route('employee.reservations', request()->except('status')) : route('employee.reservations', array_merge(request()->except('status'), ['status' => 'cancel_requested'])) }}"
                style="text-decoration:none;color:inherit;">
                <div class="status-card cancel-requested {{ request('status') == 'cancel_requested' ? 'active' : '' }}">
                  <div class="status-label">
                    ⚠ Cancel Requested
                    @if(($cancelRequestedCount ?? 0) > 0)
                      <span class="cancel-req-dot"></span>
                    @endif
                  </div>
                  <div class="status-number">{{ $cancelRequestedCount ?? 0 }}</div>
                </div>
              </a>

              <a href="{{ request('status') == 'pending' ? route('employee.reservations', request()->except('status')) : route('employee.reservations', array_merge(request()->except('status'), ['status' => 'pending'])) }}"
                style="text-decoration:none;color:inherit;">
                <div class="status-card pending {{ request('status') == 'pending' ? 'active' : '' }}">
                  <div class="status-label">Pending</div>
                  <div class="status-number">{{ $allForCounts->where('status','pending')->count() }}</div>
                </div>
              </a>

              <a href="{{ request('status') == 'confirmed' ? route('employee.reservations', request()->except('status')) : route('employee.reservations', array_merge(request()->except('status'), ['status' => 'confirmed'])) }}"
                style="text-decoration:none;color:inherit;">
                <div class="status-card confirmed {{ request('status') == 'confirmed' ? 'active' : '' }}">
                  <div class="status-label">Confirmed</div>
                  <div class="status-number">{{ $allForCounts->where('status','confirmed')->count() }}</div>
                </div>
              </a>

              <a href="{{ request('status') == 'checked-in' ? route('employee.reservations', request()->except('status')) : route('employee.reservations', array_merge(request()->except('status'), ['status' => 'checked-in'])) }}"
                style="text-decoration:none;color:inherit;">
                <div class="status-card completed {{ request('status') == 'checked-in' ? 'active' : '' }}">
                  <div class="status-label">Completed</div>
                  <div class="status-number">{{ $allForCounts->where('status','checked-in')->count() }}</div>
                </div>
              </a>

              <a href="{{ request('status') == 'rejected' ? route('employee.reservations', request()->except('status')) : route('employee.reservations', array_merge(request()->except('status'), ['status' => 'rejected'])) }}"
                style="text-decoration:none;color:inherit;">
                <div class="status-card cancelled {{ request('status') == 'rejected' ? 'active' : '' }}">
                  <div class="status-label">Rejected</div>
                  <div class="status-number">{{ $allForCounts->where('status','rejected')->count() }}</div>
                </div>
              </a>
            </div>

            <div class="filter-section">
              <div class="filter-group">
                {{-- Added name="date" and onchange to auto-submit --}}
                <select name="date" class="filter-select" onchange="this.form.submit()">
                  <option value="">Date</option>
                  <option value="last_week" {{ request('date') == 'last_week' ? 'selected' : '' }}>Last week</option>
                  <option value="last_month" {{ request('date') == 'last_month' ? 'selected' : '' }}>Last month</option>
                  <option value="last_year" {{ request('date') == 'last_year' ? 'selected' : '' }}>Last year</option>
                </select>
              </div>
              <div class="filter-group">
                <select name="client_type" class="filter-select" onchange="this.form.submit()">
                  <option value="">Client Type</option>
                  <option value="Internal" {{ request('client_type') == 'Internal' ? 'selected' : '' }}>Internal</option>
                  <option value="External" {{ request('client_type') == 'External' ? 'selected' : '' }}>External</option>
                </select>
              </div>
              <div class="filter-group">
                <select name="accommodation_type" class="filter-select" onchange="this.form.submit()">
                  <option value="">Accommodation Type</option>
                  <option value="room" {{ request('accommodation_type') == 'room' ? 'selected' : '' }}>Room</option>
                  <option value="venue" {{ request('accommodation_type') == 'venue' ? 'selected' : '' }}>Venue</option>
                </select>
              </div>

              {{-- Clear Filters Button --}}
              @if(request()->anyFilled(['search', 'date', 'client_type', 'accommodation_type', 'status']))
                <a href="{{ route('employee.reservations') }}" style="display:flex; align-items :center; text-decoration: none; color: #e74c3c; font-size: 14px; font-weight: bold; margin-left: 10px;">✕ Clear All Filters</a>
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
                  @if(in_array($reservation->status, ['pending','confirmed','checked-in','rejected']))
                  @php
                      // 1. IDENTIFY TYPE FIRST! (This fixes the undefined variable error)
                      $isRoom = ($reservation->display_type === 'room');

                      // 2. Map Database Columns
                      $dbId = $isRoom ? $reservation->Room_Reservation_ID : $reservation->Venue_Reservation_ID;
                      $dbCheckIn = $isRoom ? $reservation->Room_Reservation_Check_In_Time : $reservation->Venue_Reservation_Check_In_Time;
                      $dbCheckOut = $isRoom ? $reservation->Room_Reservation_Check_Out_Time : $reservation->Venue_Reservation_Check_Out_Time;
                      $dbTotal = $isRoom ? $reservation->Room_Reservation_Total_Price : $reservation->Venue_Reservation_Total_Price;

                      // 3. Setup Accommodation Name & Type
                      $accName = $isRoom
                          ? 'Room: ' . ($reservation->room->Room_Number ?? 'N/A')
                          : 'Venue: ' . ($reservation->venue->Venue_Name ?? 'N/A');
                      $reservationType = $isRoom ? 'Room' : 'Venue';

                      // 4. Setup Pricing Variables for the JavaScript
                      $basePrice = 0;
                      $discount = 0;
                      $extraFees = 0;
                      $extraFeesDesc = '';
                      $foodTotal = 0;


                      $checkIn = \Carbon\Carbon::parse($dbCheckIn);
                      $checkOut = \Carbon\Carbon::parse($dbCheckOut);

                      // Rooms bill per night (Mar 25–26 = 1 night)
                      // Venues bill per day inclusive (Mar 25–26 = 2 days)
                      $nights = $isRoom
                          ? ($checkIn->diffInDays($checkOut) ?: 1)
                          : ($checkIn->diffInDays($checkOut) + 1);


                      if($isRoom) {
                          $basePrice = ($reservation->user && $reservation->user->Account_Type === 'Internal')
                              ? ($reservation->room->Room_Internal_Price ?? 0)
                              : ($reservation->room->Room_External_Price ?? 0);
                          $discount = $reservation->Room_Reservation_Discount ?? 0;
                          $extraFees = $reservation->Room_Reservation_Additional_Fees ?? 0;
                          $extraFeesDesc = $reservation->Room_Reservation_Additional_Fees_Desc ?? '';
                      } else {
                          $basePrice = ($reservation->user && $reservation->user->Account_Type === 'Internal')
                              ? ($reservation->venue->Venue_Internal_Price ?? 0)
                              : ($reservation->venue->Venue_External_Price ?? 0);
                          $discount = $reservation->Venue_Reservation_Discount ?? 0;
                          $extraFees = $reservation->Venue_Reservation_Additional_Fees ?? 0;
                          $extraFeesDesc = $reservation->Venue_Reservation_Additional_Fees_Desc ?? '';
                          $foodTotal = $reservation->foods ? $reservation->foods->sum('pivot.Food_Reservation_Total_Price') : 0;
                          $foodTotal += $reservation->foodSetReservations ? $reservation->foodSetReservations->sum('Food_Reservation_Total_Price') : 0;

                          // Pre-process set reservations into ready-to-render data for the modal
                          // custom_ids positions: 0=Rice, 1=Drink, 2=Dessert, 3=Fruit
                          $_customPosLabels = ['Rice', 'Drink', 'Dessert', 'Fruit'];
                          $foodSets = $reservation->foodSetReservations ? $reservation->foodSetReservations->map(function($r) use ($_customPosLabels) {
                              $raw = $r->Food_Set_ID ?? '';
                              $setId = null; $customIds = [];
                              if (preg_match('/^"(\d+)",(\[.*\])$/', $raw, $m)) {
                                  $setId = (int)$m[1];
                                  $customIds = json_decode($m[2], true) ?: [];
                              } elseif (is_numeric($raw) && $raw !== '') {
                                  $setId = (int)$raw;
                              }
                              $set = $setId ? \App\Models\FoodSet::find($setId) : null;

                              // All base foods in the set definition, each with their category
                              $setFoods = [];
                              if ($set && !empty($set->Food_Set_Food_IDs)) {
                                  $setFoods = \App\Models\Food::whereIn('Food_ID', $set->Food_Set_Food_IDs)
                                      ->get()
                                      ->map(fn($f) => [
                                          'name'     => $f->Food_Name,
                                          'category' => ucfirst(strtolower($f->Food_Category ?? 'Food')),
                                      ])->toArray();
                              }

                              // Customized items (rice, drink, dessert, fruit) with real category labels
                              $customItems = [];
                              foreach ($customIds as $i => $cid) {
                                  if (!empty($cid) && is_numeric($cid)) {
                                      $food = \App\Models\Food::find((int)$cid);
                                      if ($food) {
                                          $customItems[] = [
                                              'name'     => $food->Food_Name,
                                              'category' => $_customPosLabels[$i]
                                                  ?? ucfirst(strtolower($food->Food_Category ?? 'Custom')),
                                          ];
                                      }
                                  }
                              }

                              return [
                                  'date'         => $r->Food_Reservation_Serving_Date,
                                  'meal_time'    => $r->Food_Reservation_Meal_time,
                                  'total_price'  => (float)($r->Food_Reservation_Total_Price ?? 0),
                                  'set_name'     => $set?->Food_Set_Name ?? 'Unknown Set',
                                  'set_price'    => (float)($set?->Food_Set_Price ?? 0),
                                  'set_foods'    => $setFoods,
                                  'custom_items' => $customItems,
                              ];
                          })->toArray() : [];
                      }
                  @endphp

                  <tr class="{{ $reservation->cancellation_status === 'pending' ? 'row-cancel-requested' : '' }}">
                      <td class="name-cell">
                          <span class="user-icon">
                            <img src="{{ asset('images/logo/topnav/user-avatar.svg') }}" alt="reservations">
                          </span>
                          <span>{{ $reservation->user->Account_Name ?? 'Unknown Account' }}</span>
                      </td>

                      <td>{{ $reservation->user->Account_Type ?? 'External' }}</td>

                      <td>
                          <strong>{{ $accName }}</strong>
                      </td>

                      <td>{{ \Carbon\Carbon::parse($dbCheckIn)->format('m/d/Y') }}</td>
                      <td>{{ \Carbon\Carbon::parse($dbCheckOut)->format('m/d/Y') }}</td>
                      <td>{{ $isRoom ? $reservation->Room_Reservation_Pax : $reservation->Venue_Reservation_Pax }}</td>

                      <td>
                      @php
                        $status = $reservation->status;
                        $isCheckedIn = $status === 'checked-in';
                      @endphp

                     

                      {{-- Cancellation request badge --}}
                      @if($reservation->cancellation_status === 'pending')
                        <span class="badge cancel-req-badge">Cancel Request</span>
                        @else
                        {{-- Main status badge --}}
                        <span class="badge
                          {{ $isCheckedIn ? 'checked-in-badge' : strtolower($status) . '-badge' }}">
                          {{ $isCheckedIn ? 'Checked-in' : ucfirst($status) }}
                        </span>
                      @endif
                      </td>

                      <td class="action-cell">
                          <button class="expand-btn"
                                  data-info="{{ json_encode([
                                      'nights' => $nights,
                                      'id' => $dbId,
                                      'idx' => $reservation->display_type == 'venue' ? $reservation->Venue_ID : $reservation->Room_ID,
                                      'db_id_display' => str_pad($dbId, 5, '0', STR_PAD_LEFT),
                                      'status' => strtolower($reservation->status),
                                      'res_type' => $reservation->display_type,
                                      'client_type' => $reservation->user->Account_Type ?? 'External',
                                      'type' => $reservation->user->Account_Type ?? 'External',
                                      'phone' => $reservation->user->Account_Phone ?? 'Error phone',
                                      'email' => $reservation->user->Account_Email ?? 'Error email',
                                      'name' => $reservation->user->Account_Name ?? 'Unknown Account',
                                      'accommodation' => $accName,
                                      'accommodationType' => $reservationType,

                                      'price' => $basePrice,
                                      'food_total' => $foodTotal,
                                      'discount' => $discount,
                                      'additional_fees' => $extraFees,
                                      'additional_fees_desc' => $extraFeesDesc,

                                      'pax' => $isRoom ? $reservation->Room_Reservation_Pax : $reservation->Venue_Reservation_Pax,
                                      'check_in' => \Carbon\Carbon::parse($dbCheckIn)->format('F d, Y'),
                                      'check_out' => \Carbon\Carbon::parse($dbCheckOut)->format('F d, Y'),
                                      'check_in_raw' => \Carbon\Carbon::parse($dbCheckIn)->format('Y-m-d'),
                                      'check_out_raw' => \Carbon\Carbon::parse($dbCheckOut)->format('Y-m-d'),
                                      'accommodation_id' => $isRoom ? $reservation->Room_ID : $reservation->Venue_ID,
                                      'userId' => $reservation->Client_ID,
                                      'purpose' => $isRoom ? ($reservation->Room_Reservation_Purpose ?? 'Error: Purpose Missing')
                                                            :($reservation->Venue_Reservation_Purpose ?? 'Error: Purpose Missing'),
                                      'foods'     => $reservation->foods ?? [],
                                      'food_sets' => $foodSets ?? [],
                                      'payment_status' => $isRoom ? ($reservation->Room_Reservation_Payment_Status ?? null) : ($reservation->Venue_Reservation_Payment_Status ?? null),
                                      'cancellation_status' => $reservation->cancellation_status,
                                  ]) }}">
                              ⤢
                          </button>
                      </td>
                  </tr>
                  @endif
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

          {{-- Pagination --}}
          @if($reservations->hasPages())
            <div style="padding: 4px 20px;">
              {{ $reservations->links('vendor.pagination.simple') }}
            </div>
          @endif

        </div>
      </div>
      <x-modal_e_reservations/>

    </main>
@endsection
