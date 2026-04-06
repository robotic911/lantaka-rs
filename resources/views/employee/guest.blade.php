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
                      : route('employee.guest', array_merge(request()->except('status'), ['status' => 'confirmed'])) }}"
                style="text-decoration:none;color:inherit;">
                <div class="status-card pending {{ request('status') == 'confirmed' ? 'active' : '' }}">
                    <div class="status-label">Pending</div>
                    <div class="status-number">{{ $allForCounts->where('status','confirmed')->count() }}</div>
                </div>
              </a>


              <a href="{{ request('status') == 'checked-in'
                      ? route('employee.guest', request()->except('status'))
                      : route('employee.guest', array_merge(request()->except('status'), ['status' => 'checked-in'])) }}"
                style="text-decoration:none;color:inherit;">
                <div class="status-card confirmed {{ request('status') == 'checked-in' ? 'active' : '' }}">
                    <div class="status-label">Checked-in</div>
                    <div class="status-number">{{ $allForCounts->where('status','checked-in')->count() }}</div>
                </div>
              </a>


              <a href="{{ request('status') == 'checked-out'
                      ? route('employee.guest', request()->except('status'))
                      : route('employee.guest', array_merge(request()->except('status'), ['status' => 'checked-out'])) }}"
                style="text-decoration:none;color:inherit;">
                <div class="status-card completed {{ request('status') == 'checked-out' ? 'active' : '' }}">
                    <div class="status-label">Checked-out</div>
                    <div class="status-number">{{ $allForCounts->where('status','checked-out')->count() }}</div>
                </div>
              </a>


              <a href="{{ request('status') == 'cancelled'
                      ? route('employee.guest', request()->except('status'))
                      : route('employee.guest', array_merge(request()->except('status'), ['status' => 'cancelled'])) }}"
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
                  <option value="">Date  </option>
                  <option value="last_week" {{ request('date') == 'last_week' ? 'selected' : '' }}>Last 7 Days</option>
                  <option value="last_month" {{ request('date') == 'last_month' ? 'selected' : '' }}>Last 30 Days</option>
                  <option value="last_year" {{ request('date') == 'last_year' ? 'selected' : '' }}>This Year</option>
                </select>
              </div>
              
              <div class="filter-group">
                <select name="client_type" class="filter-select" onchange="this.form.submit()">
                  <option value="">Client Type  </option>
                  <option value="Internal" {{ request('client_type') == 'Internal' ? 'selected' : '' }}>Internal</option>
                  <option value="External" {{ request('client_type') == 'External' ? 'selected' : '' }}>External</option>
                </select>
              </div>
              
              <div class="filter-group">
                <select name="accommodation_type" class="filter-select" onchange="this.form.submit()">
                  <option value="">Accommodation Type  </option>
                  <option value="room" {{ request('accommodation_type') == 'room' ? 'selected' : '' }}>Room</option>
                  <option value="venue" {{ request('accommodation_type') == 'venue' ? 'selected' : '' }}>Venue</option>
                </select>
              </div>

              @if(request()->hasAny(['search', 'date', 'client_type', 'accommodation_type']))
                  <a href="{{ route('employee.guest') }}" style="text-decoration: none; color: #e74c3c; font-size: 14px; font-weight: bold; margin-left: 10px;">✕ Clear</a>
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
                    <span>{{ $res->user->Account_Name }}</span>
                  </td>

                  <td>{{ $res->user->Account_Type ?? 'External' }}</td>

                  <td>
                    @if($res->type === 'room' && $res->room)
                        Room: <strong>{{ $res->room->Room_Number }}</strong>
                    @elseif($res->type === 'venue' && $res->venue)
                        Venue: <strong>{{ $res->venue->Venue_Name }}</strong>
                    @else
                        <span style="color: #e74c3c;">Not Found</span>
                    @endif
                  </td>

                  <td>{{ \Carbon\Carbon::parse($res->check_in)->format('m/d/Y') }}</td>
                  <td>{{ \Carbon\Carbon::parse($res->check_out)->format('m/d/Y') }}</td>
                  <td>{{ $res->pax }}</td>

                  <td>
                    @if($res->status === 'confirmed')
                      <span class="badge confirmed-badge">Confirmed</span>
                    @else
                      <span class="badge {{ strtolower($res->status) }}-badge">
                        {{ ucfirst($res->status) }}
                      </span>
                    @endif
                  </td>

                  @php
                        $basePrice = 0;
                        $accName = '';
                        $discount = 0;
                        $extraFees = 0;
                        $foodTotal = 0;

                        if($res->type === 'room' && $res->room) {
                            $accName = 'Room ' . $res->room->Room_Number;
                            // Use the actual room price from the rooms table
                            if($res->user->Account_Type == 'Internal'){
                              $basePrice = $res->room->Room_Internal_Price?? 0; 
                            }else{
                              $basePrice = $res->room->Room_External_Price ?? 0; 
                            }
                            $extraFees = $res->Room_Reservation_Additional_Fees ?? 0;
                            $discount = $res->Room_Reservation_Discount ?? 0;
                        } 
                        elseif($res->type === 'venue' && $res->venue) {
                            $accName = 'Venue: ' . $res->venue->Venue_Name;
                            // Use the actual venue price from the venues table
                            if($res->user->Account_Type == 'Internal'){
                              $basePrice = $res->venue->Venue_Internal_Price?? 0;
                            }else{
                              $basePrice = $res->venue->Venue_External_Price ?? 0;
                            }
                            $extraFees = $res->Venue_Reservation_Additional_Fees ?? 0;
                            $discount = $res->Venue_Reservation_Discount ?? 0;
                            $foodTotal = ($res->foods ? $res->foods->sum('pivot.Food_Reservation_Total_Price') : 0)
                                       + ($res->foodSetReservations ? $res->foodSetReservations->sum('Food_Reservation_Total_Price') : 0);

                            // Pre-process set reservations into card-display data (mirrors reservations.blade.php)
                            $_customPosLabels = ['Rice', 'Drink', 'Dessert', 'Fruit'];
                            $foodSets = $res->foodSetReservations ? $res->foodSetReservations->map(function($r) use ($_customPosLabels) {
                                $raw = $r->Food_Set_ID ?? '';
                                $setId = null; $customIds = [];
                                if (preg_match('/^"(\d+)",(\[.*\])$/', $raw, $m)) {
                                    $setId = (int)$m[1];
                                    $customIds = json_decode($m[2], true) ?: [];
                                } elseif (is_numeric($raw) && $raw !== '') {
                                    $setId = (int)$raw;
                                }
                                $set = $setId ? \App\Models\FoodSet::find($setId) : null;

                                $setFoods = [];
                                if ($set && !empty($set->Food_Set_Food_IDs)) {
                                    $setFoods = \App\Models\Food::whereIn('Food_ID', $set->Food_Set_Food_IDs)
                                        ->get()
                                        ->map(fn($f) => [
                                            'name'     => $f->Food_Name,
                                            'category' => ucfirst(strtolower($f->Food_Category ?? 'Food')),
                                        ])->toArray();
                                }

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

                        $userId = $res->Client_ID;
                        $reservationType = $res->type == 'room' ? 'Room' : 'Venue';
                        $foodSets = $foodSets ?? [];
                  @endphp
                  
                  <td class="action-cell">
                      {{-- We are removing the Check-in form/button from here --}}
                      <button class="expand-btn" data-info="{{
                        json_encode([
                            'id' => $res->id,
                            'idx' => $res->type == 'venue' ? $res->Venue_ID : $res->Room_ID,
                            'status' => strtolower($res->status),
                            'name' => $res->user->Account_Name ?? 'Unknown',
                            'accommodation' => $accName,
                            'phone' => $res->user->Account_Phone ?? 'Error phone',
                            'email' => $res->user->Account_Email ?? 'Error email',
                            'type' => $res->user->Account_Type,
                            'res_type' => $res->type,
                            'accommodationType' => $reservationType ?? 'Error accomodation type',
                            'price' => $basePrice,
                            'food_total' => $foodTotal, // Pass the pre-calculated food total
                            'pax' => $res->pax,
                            'check_in'      => \Carbon\Carbon::parse($res->check_in)->format('F d, Y'),
                            'check_out'     => \Carbon\Carbon::parse($res->check_out)->format('F d, Y'),
                            'check_in_raw'  => \Carbon\Carbon::parse($res->check_in)->format('Y-m-d'),
                            'check_out_raw' => \Carbon\Carbon::parse($res->check_out)->format('Y-m-d'),
                            'foods'     => $res->foods,
                            'food_sets' => $foodSets,
                            'userId' => $userId,
                            'discount' => $discount,
        
                            'additional_fees' => ($res->type === 'room') 
                                ? ($res->Room_Reservation_Additional_Fees ?? 0) 
                                : ($res->Venue_Reservation_Additional_Fees ?? 0),
                                
                            'additional_fees_desc' => ($res->type === 'room')
                                ? ($res->Room_Reservation_Additional_Fees_Desc ?? '')
                                : ($res->Venue_Reservation_Additional_Fees_Desc ?? ''),

                            'payment_status' => $res->type === 'room' ? ($res->Room_Reservation_Payment_Status ?? null) : ($res->Venue_Reservation_Payment_Status ?? null),
                            'purpose' => $res->type === 'room' ? ($res->Room_Reservation_Purpose ?? '') : ($res->Venue_Reservation_Purpose ?? '')
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
          <div style="margin-top: 16px;">
            {{ $reservations->links('vendor.pagination.simple') }}
          </div>
        </div>
      </div>

      <x-modal_e_reservations/>
</main>
@endsection