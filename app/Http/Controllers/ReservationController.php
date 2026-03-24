<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RoomReservation;
use App\Models\VenueReservation;
use App\Models\FoodReservation;
use App\Models\Room;
use App\Models\Venue;
use App\Models\Food;
use App\Models\Account;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;
use App\Models\EventLog;
use App\Http\Controllers\EventLogController;
use App\Mail\ReservationConfirmedMail;
use App\Mail\ReservationCheckedInMail;
use App\Mail\ReservationCancelledMail;
use App\Mail\ReservationRejectedMail;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


class ReservationController extends Controller
{
    // 1. Show Checkout Page
    public function checkout(Request $request)
    {
        $account = auth()->user();
        $allBookings = session('pending_bookings', []);

        //  
        if ($request->has('accommodation_id')) {
            $newEntry = $request->all();    

            $uniqueKey = $newEntry['type'] . '_' . $newEntry['accommodation_id'] . '_' . $newEntry['check_in'] . '_' . $newEntry['check_out'];

            $allBookings[$uniqueKey] = $newEntry;
            session(['pending_bookings' => $allBookings]);
        }

        $processedItems = [];
        $grandTotal = 0;

        foreach ($allBookings as $key => $item) {
            $checkIn = Carbon::parse($item['check_in']);
            $checkOut = Carbon::parse($item['check_out']);

            // Rooms bill per night (Mar 25–26 = 1 night)
            // Venues bill per day inclusive (Mar 25–26 = 2 days)
            if ($item['type'] === 'venue') {
                $days = $checkIn->diffInDays($checkOut) + 1;
            } else {
                $days = $checkIn->diffInDays($checkOut) ?: 1;
            }

            if ($item['type'] === 'room') {
                $model = Room::find($item['accommodation_id']);
                
                if (!$model) {
                    continue;
                }
                if($account->Account_Type == 'Internal'){
                    $price = $model->Room_Internal_Price;
                }else{
                    $price = $model->Room_External_Price;
                }
                $name = "Room " . ($model->Room_Number ?? '');
                $img = $model->Room_Image ?? null;
            } else {
                $model = Venue::find($item['accommodation_id']);
            
                if (!$model) {
                    continue;
                }
              
                if($account->Account_Type == 'Internal'){
                    $price = $model->Venue_Internal_Price;
                }else{
                    $price = $model->Venue_External_Price;
                }
                $name = $model->Venue_Name ?? 'Venue';
                $img = $model->Venue_Image ?? null;
            }
            $accommodationTotal = $price * $days;
            
            $foodTotal = 0;

            $selectedFoods = collect();
            $foodSelections = $item['food_selections'] ?? [];
            $foodEnabled = $item['food_enabled'] ?? [];
            $mealEnabled = $item['meal_enabled'] ?? [];

            if ($item['type'] === 'venue' && !empty($foodSelections)) {
                $allFoodIds = [];

                foreach ($foodSelections as $date => $meals) {
                    // skip whole date if food is disabled for that date
                    if (($foodEnabled[$date] ?? '1') != '1') {
                        continue;
                    }

                    if (!is_array($meals)) {
                        continue;
                    }

                    foreach ($meals as $mealType => $categories) {
                        // skip meal if disabled
                        if (($mealEnabled[$date][$mealType] ?? '1') != '1') {
                            continue;
                        }

                        if (!is_array($categories)) {
                            continue;
                        }

                        foreach ($categories as $category => $foodId) {
                            if (!empty($foodId)) {
                                $allFoodIds[] = $foodId;
                            }
                        }
                    }
                }

                $allFoodIds = array_values(array_unique($allFoodIds));

                if (!empty($allFoodIds)) {
                    $selectedFoods = Food::whereIn('Food_ID', $allFoodIds)->get()->keyBy('Food_ID');

                    foreach ($foodSelections as $date => $meals) {
                        if (($foodEnabled[$date] ?? '1') != '1') {
                            continue;
                        }

                        if (!is_array($meals)) {
                            continue;
                        }

                        foreach ($meals as $mealType => $categories) {
                            if (($mealEnabled[$date][$mealType] ?? '1') != '1') {
                                continue;
                            }

                            if (!is_array($categories)) {
                                continue;
                            }

                            foreach ($categories as $category => $foodId) {
                                if (empty($foodId)) {
                                    continue;
                                }

                                $food = $selectedFoods->get((int) $foodId);

                                if (!$food) {
                                    continue;
                                }

                                $foodPrice = $food->Food_Price ?? 0;
                                $foodTotal += $foodPrice * ($item['pax'] ?? 1);
                            }
                        }
                    }
                }
            }

            $itemTotal = $accommodationTotal + $foodTotal;
            $grandTotal += $itemTotal;

            $processedItems[] = [
                'key' => $key,
                'id' => $model->getKey(),
                'name' => $name,
                'type' => $item['type'],
                'price' => $price,
                'img' => $img,
                'pax' => $item['pax'] ?? 1,
                'purpose' => $item['purpose'] ?? '',
                'check_in' => $checkIn->format('F d, Y'),
                'check_out' => $checkOut->format('F d, Y'),
                'check_in_raw' => $checkIn->toDateString(),
                'check_out_raw' => $checkOut->toDateString(),
                'days' => $days > 0 ? $days : 1,
                'total' => $itemTotal,

                // new grouped structure
                'food_enabled' => $foodEnabled,
                'meal_enabled' => $mealEnabled,
                'food_selections' => $foodSelections,

                // selected food models
                'selected_foods' => $selectedFoods->values(),
                'base_total' => $accommodationTotal,
                'food_total' => $foodTotal,
            ];
        }

        // dd($processedItems);

        return view('client.my_bookings', compact('processedItems', 'grandTotal'));
    }


    // 2a. Remove a single item from the session cart
    public function removeFromCart(Request $request)
    {
        $key = $request->input('key');
        $allBookings = session('pending_bookings', []);
        unset($allBookings[$key]);
        session(['pending_bookings' => $allBookings]);

        return redirect()->route('checkout');
    }

    // 2b. Edit a cart item: remove it from session, redirect back to booking page
    public function editCartItem(Request $request)
    {
        $key = $request->input('key');
        $allBookings = session('pending_bookings', []);
        $item = $allBookings[$key] ?? null;

        unset($allBookings[$key]);
        session(['pending_bookings' => $allBookings]);

        if (!$item) {
            return redirect()->route('checkout');
        }

        $id = $item['accommodation_id'];
        $type = $item['type']; // 'room' or 'venue'

        // Venue: stash old food selections in session, then go back to food option page
        if ($type === 'venue') {
            session([
                'edit_food_selections' => $item['food_selections'] ?? [],
                'edit_food_enabled'    => $item['food_enabled']    ?? [],
                'edit_meal_enabled'    => $item['meal_enabled']    ?? [],
            ]);

            return redirect()->route('booking.prepare', [
                'accommodation_id' => $id,
                'type'             => 'venue',
                'res_name'         => $item['res_name'] ?? '',
                'check_in'         => $item['check_in'],
                'check_out'        => $item['check_out'],
                'pax'              => $item['pax'] ?? 1,
                'purpose'          => $item['purpose'] ?? '',
            ]);
        }

        // Room: go back to the room/venue viewing page with dates pre-filled
        return redirect()->to(
            route('client.show', ['category' => 'room', 'id' => $id])
                . '?' . http_build_query([
                    'check_in'  => $item['check_in'],
                    'check_out' => $item['check_out'],
                ])
        );
    }

    // 2. Store Reservation (Confirm)
    public function store(Request $request)
    {
        $request->validate([
            'selected_items' => 'required|string',
        ]);

        try {
            $selectedItems = json_decode($request->selected_items, true);

            if (!is_array($selectedItems) || empty($selectedItems)) {
                return back()->with('error', 'No selected items found.');
            }

            $savedReservations = [];

            DB::transaction(function () use ($selectedItems, &$savedReservations) {
                foreach ($selectedItems as $index => $item) {

                    if (
                        empty($item['id']) ||
                        empty($item['type']) ||
                        empty($item['check_in']) ||
                        empty($item['check_out']) ||
                        !isset($item['pax']) ||
                        !isset($item['total_amount'])
                    ) {
                        throw new \Exception("Selected item #{$index} is incomplete.");
                    }

                    if ($item['type'] === 'room') {
                        $reservation = RoomReservation::create([
                            'Room_ID' => $item['id'],
                            'Client_ID' => Auth::id(),
                            'Room_Reservation_Date' => now(),
                            'Room_Reservation_Check_In_Time' => $item['check_in'],
                            'Room_Reservation_Check_Out_Time' => $item['check_out'],
                            'Room_Reservation_Pax' => $item['pax'],
                            'Room_Reservation_Purpose' => $item['purpose'] ?? null,
                            'Room_Reservation_Total_Price' => $item['total_amount'],
                            'Room_Reservation_Status' => 'pending',
                        ]);

                        $savedReservations[] = $reservation;
                    }

                    if ($item['type'] === 'venue') {
                        $reservation = VenueReservation::create([
                            'Venue_ID' => $item['id'],
                            'Client_ID' => Auth::id(),
                            'Venue_Reservation_Date' => now(),
                            'Venue_Reservation_Check_In_Time' => $item['check_in'],
                            'Venue_Reservation_Check_Out_Time' => $item['check_out'],
                            'Venue_Reservation_Pax' => $item['pax'],
                            'Venue_Reservation_Purpose' => $item['purpose'] ?? null,
                            'Venue_Reservation_Total_Price' => $item['total_amount'],
                            'Venue_Reservation_Status' => 'pending',
                        ]);

                        $foodSelections = $item['food_selections'] ?? [];

                        if (!empty($foodSelections)) {
                            foreach ($foodSelections as $date => $meals) {
                                foreach ($meals as $mealType => $foodIds) {
                                    if (!is_array($foodIds) || empty($foodIds)) {
                                        continue;
                                    }

                                    foreach ($foodIds as $foodId) {
                                        $food = Food::find($foodId);

                                        if ($food) {
                                            $price = $food->Food_Price ?? 0;

                                            FoodReservation::create([
                                                'Food_ID' => $foodId,
                                                'Venue_Reservation_ID' => $reservation->Venue_Reservation_ID,
                                                'Client_ID' => Auth::id(),
                                                'Food_Reservation_Serving_Date' => $date,
                                                'Food_Reservation_Meal_time' => $mealType,
                                                'Food_Reservation_Total_Price' => $price * $item['pax'],
                                            ]);
                                        }
                                    }
                                }
                            }
                        }

                        $savedReservations[] = $reservation;
                    }
                }
            });

            try {
                Mail::to(auth()->user()->Account_Email)->send(
                    new \App\Mail\ReservationConfirmationMail($savedReservations)
                );
            } catch (\Exception $e) {
                \Log::error("Mail failed: " . $e->getMessage());
            }

            $allBookings = session('pending_bookings', []);

            foreach ($selectedItems as $item) {
                $uniqueKey = $item['type'] . '_' . $item['id'] . '_' . $item['check_in'] . '_' . $item['check_out'];
                unset($allBookings[$uniqueKey]);
            }

            session(['pending_bookings' => $allBookings]);

            return redirect()->route('client.my_reservations')
                ->with('success', 'Reservations confirmed successfully!');
        } catch (\Exception $e) {
            \Log::error("Reservation Store Error: " . $e->getMessage());
            return back()->with('error', 'Something went wrong: ' . $e->getMessage());
        }
    }
    // 3. Client List Page
    public function index(Request $request)
    {
        $user = auth()->user();
        $search = $request->input('search');
        $status = $request->input('status');
        $dateFilter = $request->input('date');
        $clientType = $request->input('client_type');
        $accType = $request->input('accommodation_type');

        // 1. Query Room Reservations (Global for Employees)
        $roomQuery = RoomReservation::with(['room', 'user'])
            ->where('Client_ID', $user->Account_ID);
        $venueQuery = VenueReservation::with(['venue', 'user', 'foods'])
            ->where('Client_ID', $user->Account_ID);
           
          
        // 3. Apply Filters
        foreach ([$roomQuery, $venueQuery] as $query) {

            // Status Filter
            if ($status){
                $isRoom = $query->getModel() instanceof RoomReservation;
                $statusColumn = $isRoom ? 'Room_Reservation_Status' : 'Venue_Reservation_Status';
                $query->where($statusColumn, $status);
            }

            // Client Type Filter
            if ($clientType) {
                $query->whereHas('Account', fn($q) => $q->where('Account_Type', $clientType));
            }

            // Date Filter (This was missing!)
            if ($dateFilter) {
                $date = match ($dateFilter) {
                    'last_week' => now()->subDays(7),
                    'last_month' => now()->subDays(30),
                    'last_year' => now()->startOfYear(),
                    default => null
                };
                if ($date) {
                    $dateCol = ($query->getModel() instanceof RoomReservation)
                        ? 'Room_Reservation_Date'
                        : 'Venue_Reservation_Date';
                    $query->where($dateCol, '>=', $date);
                }
            }

            // Search Logic (Matching the Guest page)
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $isRoom = ($q->getModel() instanceof RoomReservation);
            
                    $idCol = $isRoom ? 'Room_Reservation_ID' : 'Venue_Reservation_ID';
            
                    $q->orWhereRaw("CAST(\"{$idCol}\" AS TEXT) ILIKE ?", ["%{$search}%"]);
            
                    if ($isRoom) {
                        $q->orWhere('Room_Reservation_Status', 'ILIKE', "%{$search}%");
            
                        $q->orWhereHas('user', function ($uq) use ($search) {
                            $uq->where('Account_Name', 'ILIKE', "%{$search}%");
                        });
            
                        $q->orWhereHas('room', function ($rq) use ($search) {
                            $rq->where('Room_Number', 'ILIKE', "%{$search}%")
                               ->orWhereRaw(
                                   'CONCAT(\'Room \', COALESCE("Room_Number"::text, \'\')) ILIKE ?',
                                   ["%{$search}%"]
                               );
                        });
            
                        $q->orWhereRaw('EXISTS (
                            SELECT 1
                            FROM "Account" u 
                            JOIN "Room" r ON r."Room_ID" = "Room_Reservation"."Room_ID"
                            WHERE u."Account_ID" = "Room_Reservation"."Client_ID"
                            AND CONCAT(
                                COALESCE(u."Account_Name", \'\'),
                                \' \',
                                \'Room \',
                                COALESCE(r."Room_Number"::text, \'\'),
                                \' \',
                                COALESCE("Room_Reservation"."Room_Reservation_Status", \'\')
                            ) ILIKE ?
                        )', ["%{$search}%"]);
            
                    } else {
                        $q->orWhere('Venue_Reservation_Status', 'ILIKE', "%{$search}%");
            
                        $q->orWhereHas('user', function ($uq) use ($search) {
                            $uq->where('Account_Name', 'ILIKE', "%{$search}%");
                        });
            
                        $q->orWhereHas('venue', function ($vq) use ($search) {
                            $vq->where('Venue_Name', 'ILIKE', "%{$search}%");
                        });
            
                        $q->orWhereRaw('EXISTS (
                            SELECT 1
                            FROM "Account" u
                            JOIN "Venue" v ON v."Venue_ID" = "Venue_Reservation"."Venue_ID"
                            WHERE u."Account_ID" = "Venue_Reservation"."Client_ID"
                            AND CONCAT(
                                COALESCE(u."Account_Name", \'\'),
                                \' \',
                                COALESCE(v."Venue_Name", \'\'),
                                \' \',
                                COALESCE("Venue_Reservation"."Venue_Reservation_Status", \'\')
                            ) ILIKE ?
                        )', ["%{$search}%"]);
                    }
                });
            }
        }

        // 4. Get Data
        $rooms = ($accType === 'venue') ? collect() : $roomQuery->get()->map(function ($item) {
            $item->display_type = 'room';
            $item->type = 'room';
            $item->status = $item->Room_Reservation_Status;
            return $item;
        });

        $venues = ($accType === 'room') ? collect() : $venueQuery->get()->map(function ($item) {
            $item->display_type = 'venue';
            $item->type = 'venue';
            $item->status = $item->Venue_Reservation_Status;
            return $item;
        });

        // Priority order: pending > confirmed > checked-in > completed/checked-out > rejected/cancelled
        $clientStatusOrder = [
            'pending'     => 0,
            'confirmed'   => 1,
            'checked-in'  => 2,
            'completed'   => 3,
            'checked-out' => 3,
            'rejected'    => 4,
            'cancelled'   => 4,
        ];

        $allReservations = $rooms->concat($venues)
            ->sortBy(function ($r) use ($clientStatusOrder) {
                $priority = $clientStatusOrder[strtolower($r->status ?? '')] ?? 99;
                $ts = optional($r->created_at)->timestamp ?? 0;
                return [$priority, -$ts];
            })
            ->values();

        
        // 5. IMPORTANT: This variable is required for your Status Cards in the blade!
        if ($user && ($user->Account_Role === 'admin' || $user->Account_Role === 'staff')) {
            $allForCounts = RoomReservation::select('Room_Reservation_Status as status')->get()
                ->concat(VenueReservation::select('Venue_Reservation_Status as status')->get());

            // Paginate for employee view too
            $perPage     = 15;
            $currentPage = $request->input('page', 1);
            $reservations = new \Illuminate\Pagination\LengthAwarePaginator(
                $allReservations->forPage($currentPage, $perPage),
                $allReservations->count(),
                $perPage,
                $currentPage,
                ['path' => $request->url(), 'query' => $request->query()]
            );
            return view('employee.reservations', compact('reservations', 'allForCounts'));
        }

            // Client: paginate with priority order
            $perPage     = 15;
            $currentPage = $request->input('page', 1);
            $reservations = new \Illuminate\Pagination\LengthAwarePaginator(
                $allReservations->forPage($currentPage, $perPage),
                $allReservations->count(),
                $perPage,
                $currentPage,
                ['path' => $request->url(), 'query' => $request->query()]
            );  
            return view('client.my_reservations', compact('reservations'));
    }
    // 4. Admin List Page
    public function adminIndex(Request $request)
    {
        // 1. Capture ALL dropdown inputs
        $search = $request->input('search');
        $status = $request->input('status');
        $dateFilter = $request->input('date');
        $clientType = $request->input('client_type');
        $accType = $request->input('accommodation_type');

        $roomQuery = RoomReservation::with(['user', 'room']);
        $venueQuery = VenueReservation::with(['user', 'venue']);

        // 2. Filter by Client Type (Internal/External)
        if ($clientType) {
            $roomQuery->whereHas('user', fn($q) => $q->where('Account_Type', $clientType));
            $venueQuery->whereHas('user', fn($q) => $q->where('Account_Type', $clientType));
        }

        // 3. Filter by Date
        if ($dateFilter) {
            $threshold = match ($dateFilter) {
                'last_week'  => now()->subDays(7),
                'last_month' => now()->subDays(30),
                'last_year'  => now()->startOfYear(),
                default      => null,
            };
            if ($threshold) {
                $roomQuery->where('created_at', '>=', $threshold);
                $venueQuery->where('created_at', '>=', $threshold);
            }
        }

        // 4. Search & Status (Case-Sensitive Column Names)
        if ($search) {
            // Search Rooms
            $roomQuery->where(function ($q) use ($search) {
                $q->whereHas('user', fn($uq) => $uq->where('Account_Name', 'ILIKE', "%$search%"))
                    ->orWhereHas('room', fn($rq) => $rq->where('Room_Number', 'ILIKE', "%$search%"))
                    // Use whereRaw to cast BigInt to Text for comparison
                    ->orWhereRaw('CAST("Room_Reservation_ID" AS TEXT) ILIKE ?', ["%$search%"]);
            });

            // Search Venues
            $venueQuery->where(function ($q) use ($search) {
                $q->whereHas('user', fn($uq) => $uq->where('Account_Name', 'ILIKE', "%$search%"))
                    ->orWhereHas('venue', fn($vq) => $vq->where('Venue_Name', 'ILIKE', "%$search%"))
                    // Use whereRaw to cast BigInt to Text for comparison
                    ->orWhereRaw('CAST("Venue_Reservation_ID" AS TEXT) ILIKE ?', ["%$search%"]);
            });
        }

        if ($status) {
            $roomQuery->where('Room_Reservation_Status', $status);
            $venueQuery->where('Venue_Reservation_Status', $status);
        }

        // 5. Filter by Accommodation Type (The Dropdown choice)
        $rooms = collect();
        $venues = collect();

        if (!$accType || $accType === 'room') {
            $rooms = $roomQuery->get()->map(function ($item) {
                $item->display_type = 'room';
                $item->type = 'room'; // Helper for the Blade
                $item->status = $item->Room_Reservation_Status;
                return $item;
            });
        }

        if (!$accType || $accType === 'venue') {
            $venues = $venueQuery->get()->map(function ($item) {
                $item->display_type = 'venue';
                $item->type = 'venue'; // Helper for the Blade
                $item->status = $item->Venue_Reservation_Status;
                return $item;
            });
        }

        $allReservations = $rooms->concat($venues)->sortByDesc('created_at')->values();

        // Manual pagination (two collections can't use ->paginate() directly)
        $perPage     = 15;
        $currentPage = $request->input('page', 1);
        $reservations = new \Illuminate\Pagination\LengthAwarePaginator(
            $allReservations->forPage($currentPage, $perPage),
            $allReservations->count(),
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        // Status counts for the cards
        $allForCounts = RoomReservation::select('Room_Reservation_Status as status')->get()
            ->concat(VenueReservation::select('Venue_Reservation_Status as status')->get());

        return view('employee.reservations', compact('reservations', 'allForCounts'));
    }

    public function adminIndexSpecificId(Request $request)
    {
        $id = $request->route('id');
        $type = $request->input('type');
    
        $rooms = collect();
        $venues = collect();
    
        if ($type === 'room') {
            $room = RoomReservation::with(['user', 'room'])
                ->where('Room_Reservation_ID', $id)
                ->firstOrFail();

    
            $room->display_type = 'room';
            $room->type = 'room';
            $room->status = $room->Room_Reservation_Status;
            $room->pax = $room->Room_Reservation_Pax;

            $rooms = collect([$room]);
    
        } elseif ($type === 'venue') {
            $venue = VenueReservation::with(['user', 'venue'])
                ->where('Venue_Reservation_ID', $id)
                ->firstOrFail();
    
            $venue->display_type = 'venue';
            $venue->type = 'venue';
            $venue->status = $venue->Venue_Reservation_Status;
            $venue->pax = $venue->Venue_Reservation_Pax;

            $venues = collect([$venue]);
    
        } else {
            abort(404, 'Invalid reservation type.');
        }
    
        // Combine (only 1 item anyway)
        $allReservations = $rooms->concat($venues)->values();
    
        // Pagination (kept for Blade compatibility)
        $perPage = 15;
        $currentPage = $request->input('page', 1);
    
        $reservations = new \Illuminate\Pagination\LengthAwarePaginator(
            $allReservations->forPage($currentPage, $perPage),
            $allReservations->count(),
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );
    
        // ✅ KEEP THIS GLOBAL (DO NOT FILTER BY ID)
        $allForCounts = RoomReservation::select('Room_Reservation_Status as status')->get()
            ->concat(
                VenueReservation::select('Venue_Reservation_Status as status')->get()
            );

        $firstRes = $allReservations->first();
        if($firstRes && ($firstRes->status == 'pending' || $firstRes->status == 'confirmed')){
            return view('employee.reservations', compact('reservations', 'allForCounts'));
        }else{
            return view('employee.guest', compact('reservations', 'allForCounts'));
        }
    }
    public function showGuests(Request $request)
    {
        $status = $request->input('status');
        $search = $request->input('search');
        $clientType = $request->input('client_type');
        $accommodationType = $request->input('accommodation_type');
        $dateFilter = $request->input('date');

        // 1. Initialize Queries
        $roomQuery = RoomReservation::with(['user', 'room']);
        $venueQuery = VenueReservation::with(['user', 'venue', 'foods']);

        // 2. Apply Date Filter
        if ($dateFilter) {
            $dateThreshold = match ($dateFilter) {
                'last_week' => now()->subDays(7),
                'last_month' => now()->subDays(30),
                'last_year' => now()->startOfYear(),
                default => null,
            };

            if ($dateThreshold) {
                $roomQuery->where('created_at', '>=', $dateThreshold);
                $venueQuery->where('created_at', '>=', $dateThreshold);
            }
        }

        // 3. Apply Room Specific Filters
        if ($status) $roomQuery->where('Room_Reservation_Status', $status);
        if ($clientType) {
            $roomQuery->whereHas('user', fn($q) => $q->where('Account_Type', $clientType));
        }
        if ($search) {
            
            $roomQuery->where(function ($q) use ($search) {
                $q->whereHas('user', fn($u) => $u->where('Account_Name', 'ILIKE', "%{$search}%"))
                    ->orWhereRaw('CAST("Room_Reservation_ID" AS TEXT) ILIKE ?', ["%{$search}%"])
                    ->orWhereHas('room', fn($r) => $r->where('Room_Number', 'ILIKE', "%{$search}%"));
            });

            
        }

        // 4. Apply Venue Specific Filters
        if ($status) $venueQuery->where('Venue_Reservation_Status', $status);
        if ($clientType) {
            $venueQuery->whereHas('user', fn($q) => $q->where('Account_Type', $clientType));
        }
        if ($search) {
            $venueQuery->where(function ($q) use ($search) {
                $q->whereHas('user', fn($u) => $u->where('Account_Name', 'LIKE', "%{$search}%"))
                    ->orWhereRaw('CAST("Venue_Reservation_ID" AS TEXT) ILIKE ?', ["%{$search}%"])
                // Using 'Venue_Name' for the Venue table search
                    ->orWhereHas('venue', fn($v) => $v->where('Venue_Name', 'LIKE', "%{$search}%"));
            });
        }

        // 5. Execute and Map Data to standard keys for Blade
        $rooms = ($accommodationType === 'venue') ? collect() : $roomQuery->get()->map(function ($item) {
            $item->display_type = 'room';
            $item->type = 'room';
            $item->status = $item->Room_Reservation_Status;
            $item->check_in = $item->Room_Reservation_Check_In_Time;
            $item->check_out = $item->Room_Reservation_Check_Out_Time;
            $item->total_amount = $item->Room_Reservation_Total_Price;
            $item->id = $item->Room_Reservation_ID;
            $item->base_room_price = ($item->user && $item->user->Account_Type === 'Internal')
                ? ($item->room->Room_Internal_Price ?? 0)
                : ($item->room->Room_External_Price ?? 0);
            $item->pax = $item->Room_Reservation_Pax ?? 0;
            $item->discount = $item->Room_Reservation_Discount ?? 0;
            $item->additional_fees = $item->Room_Reservation_Additional_Fees ?? 0;
            $item->additional_fees_desc = $item->Room_Reservation_Additional_Fees_Desc ?? '';

            return $item;
        });

        $venues = ($accommodationType === 'room') ? collect() : $venueQuery->get()->map(function ($item) {
            $item->display_type = 'venue';
            $item->type = 'venue';
            $item->status = $item->Venue_Reservation_Status;
            $item->check_in = $item->Venue_Reservation_Check_In_Time;
            $item->check_out = $item->Venue_Reservation_Check_Out_Time;
            $item->total_amount = $item->Venue_Reservation_Total_Price;
            $item->id = $item->Venue_Reservation_ID;
            $item->pax = $item->Venue_Reservation_Pax ?? 0;
            $item->discount = $item->Venue_Reservation_Discount ?? 0;
            $item->additional_fees = $item->Venue_Reservation_Additional_Fees ?? 0;
            $item->additional_fees_desc = $item->Venue_Reservation_Additional_Fees_Desc ?? '';
            $item->food_total = $item->foods->sum('pivot.Food_Reservation_Total_Price') ?? 0;
            return $item;
        });

        // 6. Final Merge — priority order: confirmed(Pending) > checked-in > checked-out > cancelled
        $statusOrder = [
            'confirmed'   => 0,
            'checked-in'  => 1,
            'checked-out' => 2,
            'cancelled'   => 3,
        ];

        $allReservations = $rooms->concat($venues)
            ->sortBy(function ($r) use ($statusOrder) {
                $priority = $statusOrder[strtolower($r->status ?? '')] ?? 99;
                $ts = optional($r->created_at)->timestamp ?? 0;
                return [$priority, -$ts]; // status priority asc, date desc within same group
            })
            ->values();


           

        // Manual pagination (two merged collections can't use ->paginate() directly)
        $perPage     = 15;
        $currentPage = $request->input('page', 1);
        $reservations = new \Illuminate\Pagination\LengthAwarePaginator(
            $allReservations->forPage($currentPage, $perPage),
            $allReservations->count(),
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        // Counts for status cards (always fetches ALL to keep numbers accurate even when filtering)
        $allForCounts = \App\Models\RoomReservation::select('Room_Reservation_Status as status')->get()
            ->concat(\App\Models\VenueReservation::select('Venue_Reservation_Status as status')->get());

        return view('employee.guest', compact('reservations', 'allForCounts'));
    }

    public function updateGuests(Request $request)
    {
        //dd($request->all());
        $resId = $request->reservation_id;
        $type = strtolower($request->res_type);

        try {
            if ($type === 'room') {
                $reservation = RoomReservation::with('room')->findOrFail($resId);

                // 1. Get the current saved numbers before we overwrite them
                $currentTotal = (float) $reservation->Room_Reservation_Total_Price;
                $currentFees = (float) $reservation->Room_Reservation_Additional_Fees;
                $currentDiscount = (float) $reservation->Room_Reservation_Discount;

                // 2. Reverse-engineer the TRUE original room cost (Price x Nights)
                $trueBookingCost = $currentTotal - $currentFees + $currentDiscount;

                // 3. Get the new values from the form
                $totalExtra = array_sum($request->input('additional_fees', [0]));
                $discount = (float) ($request->discount ?? 0);

                // 4. Update the Extra Fees in the DB
                $descs = $request->input('additional_fees_desc', []);
                $amounts = $request->input('additional_fees', []);
                $qtys = $request->input('additional_fees_qty', []);
                $dates = $request->input('additional_fees_date', []);

                $combined = [];
                $totalExtra = 0;

                foreach ($descs as $index => $desc) {
                    $amount = $amounts[$index] ?? 0;
                    $qty = $qtys[$index] ?? 1;
                    $date = $dates[$index] ?? '';

                    $lineTotal = $amount * $qty;
                    $totalExtra += $lineTotal;

                    $combined[] = $desc . ':' . $qty . ':' . $amount . ':' . $date;
                }

                $reservation->Room_Reservation_Additional_Fees = $totalExtra;
                $reservation->Room_Reservation_Additional_Fees_Desc = json_encode($combined);
                $reservation->Room_Reservation_Discount = $discount;

                // 5. Calculate new total strictly using the True Booking Cost
                $reservation->Room_Reservation_Total_Price = ($trueBookingCost + $totalExtra) - $discount;
                $reservation->save();
            } elseif ($type === 'venue') {
                $reservation = VenueReservation::with(['venue', 'foods'])->findOrFail($resId);

                // 1. Get the current saved numbers before we overwrite them
                $currentTotal = (float) $reservation->Venue_Reservation_Total_Price;
                $currentFees = (float) $reservation->Venue_Reservation_Additional_Fees;
                $currentDiscount = (float) $reservation->Venue_Reservation_Discount;
                $foodTotal = (float) $reservation->foods->sum('pivot.Food_Reservation_Total_Price');

                // 2. Reverse-engineer the TRUE original venue cost
                $trueBookingCost = $currentTotal - $foodTotal - $currentFees + $currentDiscount;

                // 3. Get the new values from the form (FIXED to match JS snake_case)
                $totalExtra = array_sum($request->input('additional_fees', [0]));
                $discount = (float) ($request->discount ?? 0);

                // 4. Assign new values directly (Removed the Schema check that was blocking it)
                $descs = $request->input('additional_fees_desc', []);
                $amounts = $request->input('additional_fees', []);
                $qtys = $request->input('additional_fees_qty', []);
                $dates = $request->input('additional_fees_date', []);

                $combined = [];
                $totalExtra = 0;

                foreach ($descs as $index => $desc) {
                    $amount = $amounts[$index] ?? 0;
                    $qty = $qtys[$index] ?? 1;
                    $date = $dates[$index] ?? '';

                    $lineTotal = $amount * $qty;
                    $totalExtra += $lineTotal;

                    $combined[] = $desc . ':' . $qty . ':' . $amount . ':' . $date;
                }

                $reservation->Venue_Reservation_Additional_Fees = $totalExtra;
                $reservation->Venue_Reservation_Additional_Fees_Desc = json_encode($combined);
                $reservation->Venue_Reservation_Discount = $discount;

                // 5. Calculate new total strictly using the True Booking Cost
                $reservation->Venue_Reservation_Total_Price = ($trueBookingCost + $foodTotal + $totalExtra) - $discount;

                $reservation->save();
            } else {
                return redirect()->back()->with('error', 'Invalid reservation type detected.');
            }

            return redirect()->back()->with('success', 'Modifications saved successfully!');
        } catch (\Exception $e) {
                return redirect()->back()->with('error', 'Something went wrong. Please try again.');
            //return dd("Database Error: " . $e->getMessage());
        }
    }
    public function updateStatus(Request $request, $id)
    {
        $type      = $request->query('type');
        $newStatus = strtolower($request->input('status'));

        if (!$id || $id === 'null') {
            return redirect()->back()->with('error', 'Critical Error: Reservation ID was not passed correctly.');
        }

        if (!in_array($type, ['room', 'venue'])) {
            return redirect()->back()->with('error', 'Invalid or missing reservation type.');
        }

        if (!in_array($newStatus, ['pending', 'confirmed', 'checked-in', 'checked-out', 'cancelled', 'rejected', 'completed'])) {
            return redirect()->back()->with('error', 'Invalid status value.');
        }

        try {
            if ($type === 'room') {
                $reservation = RoomReservation::with(['user', 'room'])->findOrFail($id);
            } else {
                $reservation = VenueReservation::with(['user', 'venue'])->findOrFail($id);
            }
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return redirect()->back()->with('error', 'Reservation not found.');
        }

        $statusColumn  = $type === 'room' ? 'Room_Reservation_Status'        : 'Venue_Reservation_Status';
        $paymentColumn = $type === 'room' ? 'Room_Reservation_Payment_Status' : 'Venue_Reservation_Payment_Status';

        // ── Double-booking guard ──────────────────────────────────────────────
        // Before confirming, check that no other confirmed/checked-in reservation
        // already occupies the same room/venue on an overlapping date range.
        // Wrapped in a DB transaction with a row-level lock so two simultaneous
        // approvals for the same slot cannot both slip through.
        if ($newStatus === 'confirmed') {
            $conflict = DB::transaction(function () use ($type, $reservation, $statusColumn, $paymentColumn, $newStatus) {

                if ($type === 'room') {
                    $checkIn  = $reservation->Room_Reservation_Check_In_Time;
                    $checkOut = $reservation->Room_Reservation_Check_Out_Time;
                    $roomId   = $reservation->Room_ID;
                    $resId    = $reservation->getKey();

                    // Lock this row so a concurrent request waits
                    RoomReservation::where($reservation->getKeyName(), $resId)->lockForUpdate()->first();

                    $overlap = RoomReservation::where('Room_ID', $roomId)
                        ->where($reservation->getKeyName(), '!=', $resId)
                        ->whereIn('Room_Reservation_Status', ['confirmed', 'checked-in'])
                        ->where('Room_Reservation_Check_In_Time',  '<', $checkOut)
                        ->where('Room_Reservation_Check_Out_Time', '>', $checkIn)
                        ->with('user')
                        ->first();

                } else {
                    $checkIn  = $reservation->Venue_Reservation_Check_In_Time;
                    $checkOut = $reservation->Venue_Reservation_Check_Out_Time;
                    $venueId  = $reservation->Venue_ID;
                    $resId    = $reservation->getKey();

                    VenueReservation::where($reservation->getKeyName(), $resId)->lockForUpdate()->first();

                    $overlap = VenueReservation::where('Venue_ID', $venueId)
                        ->where($reservation->getKeyName(), '!=', $resId)
                        ->whereIn('Venue_Reservation_Status', ['confirmed', 'checked-in'])
                        ->where('Venue_Reservation_Check_In_Time',  '<', $checkOut)
                        ->where('Venue_Reservation_Check_Out_Time', '>', $checkIn)
                        ->with('user')
                        ->first();
                }

                if ($overlap) {
                    return $overlap; // signal: conflict found
                }

                // No conflict — safe to approve
                $reservation->$statusColumn = $newStatus;
                $reservation->save();

                return null; // signal: all good
            });

            if ($conflict) {
                $conflictClient = optional($conflict->user)->Account_Name ?? 'another client';
                $accName = $type === 'room'
                    ? 'Room ' . ($reservation->room->Room_Number ?? $id)
                    : ($reservation->venue->Venue_Name ?? 'Venue');

                return redirect()->back()->with(
                    'error',
                    "Cannot confirm: {$accName} is already confirmed for {$conflictClient} on an overlapping date. " .
                    "Please reject or reschedule one of the reservations first."
                );
            }

            // Already saved inside the transaction — skip the save below
            $this->notifyClientOnStatusChange($reservation, $type, $newStatus);

            return redirect()->back()
                ->with('success', 'Status updated to Confirmed successfully.')
                ->with('email_sent', true);
        }
        // ── End double-booking guard ──────────────────────────────────────────

        $reservation->$statusColumn = $newStatus;

        // Checkout always starts as unpaid — payment is confirmed separately
        if (in_array($newStatus, ['checked-out', 'completed'])) {
            $currentPayment = $reservation->$paymentColumn;
            if ($currentPayment !== 'paid') {
                $reservation->$paymentColumn = 'unpaid';
            }
        }

        $reservation->save();
        // ── Notify client via email + in-system notification ──
        $this->notifyClientOnStatusChange($reservation, $type, $newStatus);

        $emailStatuses = ['confirmed', 'checked-in', 'checked-out', 'completed', 'cancelled', 'rejected'];
        $emailSent = in_array($newStatus, $emailStatuses);

        return redirect()->back()
            ->with('success', 'Status updated to ' . ucfirst($newStatus) . ' successfully.')
            ->with('email_sent', $emailSent);
    }

    /**
     * Send email + create in-system notification when a reservation status changes.
     */
    private function notifyClientOnStatusChange($reservation, string $type, string $status): void
    {
        $user = $reservation->user;
        if (!$user) return;

        // Build a human-readable accommodation label
        $accName = $type === 'room'
            ? 'Room ' . ($reservation->room->Room_Number ?? $reservation->getKey())
            : ($reservation->venue->Venue_Name ?? 'Venue');

        $notificationMap = [
            'confirmed'   => [
                'title' => 'Reservation Confirmed',
                'msg'   => "Your reservation for {$accName} has been confirmed. Please arrive on time for check-in.",
            ],
            'checked-in'  => [
                'title' => 'Checked In Successfully',
                'msg'   => "You are now checked in to {$accName}. Enjoy your stay!",
            ],
            'checked-out' => [
                'title' => 'Checked Out',
                'msg'   => "Your stay at {$accName} has ended. Thank you for choosing Lantaka!",
            ],
            'completed'   => [
                'title' => 'Stay Completed',
                'msg'   => "Your stay at {$accName} is now complete. Thank you for choosing Lantaka!",
            ],
            'cancelled'   => [
                'title' => 'Reservation Cancelled',
                'msg'   => "Your reservation for {$accName} has been cancelled. Contact us if you have questions.",
            ],
            'rejected'    => [
                'title' => 'Reservation Not Approved',
                'msg'   => "Your reservation request for {$accName} was not approved. Please contact Lantaka for details.",
            ],
        ];

        if (!isset($notificationMap[$status])) return;

        $title = $notificationMap[$status]['title'];
        $msg   = $notificationMap[$status]['msg'];

        // 1. Audit log (admin actor, no notifiable_user)
        EventLogController::log(
            "reservation_{$status}",
            "[Admin] {$title} — {$accName} (reservation #{$reservation->getKey()}) for {$user->Account_Name}",
            Auth::id(),
            null,
            ['title' => $title, 'type' => $status]
        );

        // 2. Client notification (surfaced in the bell)
        try {
            EventLog::create([
                'user_id'            => Auth::id(),
                'Event_Logs_Notifiable_User_ID' => $user->Account_ID,
                'Event_Logs_Action'             => "reservation_{$status}",
                'Event_Logs_Title'              => $title,
                'Event_Logs_Message'            => $msg,
                'Event_Logs_Type'               => $status,
                'Event_Logs_Link'               => '/client/my_reservations',
                'Event_Logs_isRead'            => false,
            ]);
        } catch (\Throwable $e) {
            Log::error("EventLog notification create failed: " . $e->getMessage());
        }

        // 3. Send email
        if (!$user->Account_Email) return;
        try {
            switch ($status) {
                case 'confirmed':
                    Mail::to($user->Account_Email)->send(new ReservationConfirmedMail($reservation, $type));
                    break;
                case 'checked-in':
                    Mail::to($user->Account_Email)->send(new ReservationCheckedInMail($reservation, $type));
                    break;
                case 'checked-out':
                case 'completed':
                    $foodTotal = 0;
                    if ($type === 'venue') {
                        $foodTotal = \App\Models\FoodReservation::where('Venue_Reservation_ID', $reservation->getKey())
                            ->sum('Food_Reservation_Total_Price');
                    }
                    Mail::to($user->Account_Email)->send(new \App\Mail\GuestCheckOutMail($reservation, $type, $foodTotal));
                    break;
                case 'cancelled':
                    Mail::to($user->Account_Email)->send(new ReservationCancelledMail($reservation, $type));
                    break;
                case 'rejected':
                    Mail::to($user->Account_Email)->send(new ReservationRejectedMail($reservation, $type));
                    break;
            }
        } catch (\Exception $e) {
            Log::error("Email failed for reservation #{$reservation->getKey()}: " . $e->getMessage());
        }
    }

    /**
     * Mark an already-checked-out reservation as paid.
     */
    public function markAsPaid(\Illuminate\Http\Request $request, $id)
    {
        $type = $request->query('type');

        if (!in_array($type, ['room', 'venue'])) {
            return redirect()->back()->with('error', 'Invalid reservation type.');
        }

        try {
            $reservation = ($type === 'room') ? RoomReservation::findOrFail($id) : VenueReservation::findOrFail($id);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return redirect()->back()->with('error', 'Reservation not found.');
        }
        if($type=='room'){
            $reservation->Room_Reservation_Payment_Status = 'paid';
        }elseif(($type=='venue')){
            $reservation->Venue_Reservation_Payment_Status = 'paid';
        }
        
        $reservation->save();
        return redirect()->back()->with('success', 'Payment recorded — reservation marked as Paid.');
    }

    /**
     * Admin-only: revert a paid reservation back to unpaid.
     */
    public function markAsUnpaid(\Illuminate\Http\Request $request, $id)
    {
        $type = $request->query('type');

        if (!in_array($type, ['room', 'venue'])) {
            return redirect()->back()->with('error', 'Invalid reservation type.');
        }

        try {
            $reservation = ($type === 'room')
                ? \App\Models\RoomReservation::findOrFail($id)
                : \App\Models\VenueReservation::findOrFail($id);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return redirect()->back()->with('error', 'Reservation not found.');
        }
        if($type=='room'){
            $reservation->Room_Reservation_Payment_Status = 'unpaid';
        }elseif(($type=='venue')){
            $reservation->Venue_Reservation_Payment_Status = 'unpaid';
        }
        
        $reservation->save();

        return redirect()->back()->with('success', 'Payment status reverted to Unpaid.');
    }

    public function showReservationsCalendar()
    {
        $roomRes = RoomReservation::with(['room', 'user'])->get()->map(function ($item) {
            return [
                'id' => $item->Room_Reservation_ID,
                'status' => strtolower($item->Room_Reservation_Status),
                'check_in' => \Carbon\Carbon::parse($item->Room_Reservation_Check_In_Time)->format('Y-m-d'),
                'check_out' => \Carbon\Carbon::parse($item->Room_Reservation_Check_Out_Time)->format('Y-m-d'),
                'user' => $item->user,
                'room' => $item->room,
                'label' => $item->room ? "Room " . $item->room->Room_Number : "Room N/A",
                'type' => 'room'
            ];
        });

        $venueRes = VenueReservation::with(['venue', 'user'])->get()->map(function ($item) {
            return [
                'id' => $item->Venue_Reservation_ID,
                'status' => strtolower($item->Venue_Reservation_Status),
                'check_in' => \Carbon\Carbon::parse($item->Venue_Reservation_Check_In_Time)->format('Y-m-d'),
                'check_out' => \Carbon\Carbon::parse($item->Venue_Reservation_Check_Out_Time)->format('Y-m-d'),
                'user' => $item->user,
                'venue' => $item->venue,
                'label' => $item->venue ? $item->venue->Venue_Name : "Venue N/A",
                'type' => 'venue'
            ];
        });

        $reservations = $roomRes->concat($venueRes);

        $thisMonthStart = Carbon::now()->startOfMonth();
        $thisMonthEnd   = Carbon::now()->endOfMonth();
        $totalReservations = RoomReservation::where('Room_Reservation_Status', 'checked-out')
                                ->whereBetween('created_at', [$thisMonthStart, $thisMonthEnd])->count()
                           + VenueReservation::where('Venue_Reservation_Status', 'checked-out')
                                ->whereBetween('created_at', [$thisMonthStart, $thisMonthEnd])->count();

        $roomRevenue = RoomReservation::where('Room_Reservation_Status', 'checked-out')
            ->where('Room_Reservation_Payment_Status', 'paid')
            ->sum('Room_Reservation_Total_Price');

        $venueRevenue = VenueReservation::where('Venue_Reservation_Status', 'checked-out')
            ->where('Venue_Reservation_Payment_Status', 'paid')
            ->sum('Venue_Reservation_Total_Price');

        $totalRevenue = $roomRevenue + $venueRevenue;
        $today = Carbon::now()->format('Y-m-d');


        $activeRoomGuests = RoomReservation::where('Room_Reservation_Status', 'checked-in')
            ->whereDate('Room_Reservation_Check_In_Time', '<=', $today)
            ->whereDate('Room_Reservation_Check_Out_Time', '>=', $today)
            ->sum('Room_Reservation_Pax');

        $activeVenueGuests = VenueReservation::where('Venue_Reservation_Status', 'checked-in')
            ->whereDate('Venue_Reservation_Check_In_Time', '<=', $today)
            ->whereDate('Venue_Reservation_Check_Out_Time', '>=', $today)
            ->sum('Venue_Reservation_Pax');

        $activeGuests = $activeRoomGuests + $activeVenueGuests;
        $days = 30;

        $totalRooms = Room::count();
        $totalRoomNights = $totalRooms * $days;

        $roomNightsSold = RoomReservation::where('Room_Reservation_Status', 'checked-in')
            ->whereBetween('Room_Reservation_Check_In_Time', [
                Carbon::now()->subDays($days),
                Carbon::now()
            ])
            ->get()
            ->sum(function ($r) {
                $in  = Carbon::parse($r->Room_Reservation_Check_In_Time);
                $out = Carbon::parse($r->Room_Reservation_Check_Out_Time);
                return max(1, $in->diffInDays($out));
            });

        $occupancyRate = $totalRoomNights > 0
            ? ($roomNightsSold / $totalRoomNights) * 100
            : 0;

        // CHECK-OUTS TODAY
        $roomCheckOutsToday = RoomReservation::with(['room', 'user'])
            ->where('Room_Reservation_Status', 'checked-in')
            ->whereDate('Room_Reservation_Check_Out_Time', $today)
            ->get();
        $venueCheckOutsToday = VenueReservation::with(['venue', 'user'])
            ->where('Venue_Reservation_Status', 'checked-in')
            ->whereDate('Venue_Reservation_Check_Out_Time', $today)
            ->get();

        $checkOutsToday = $roomCheckOutsToday->concat($venueCheckOutsToday);

        $checkOutsTodayCount = $checkOutsToday->count();

        $changes = $this->computeStatChanges($occupancyRate, $activeGuests);


        return view('employee.dashboard', compact(
            'reservations',
            'totalReservations',
            'totalRevenue',
            'activeGuests',
            'occupancyRate',
            'checkOutsToday',
            'checkOutsTodayCount',
            'changes'
        ));
    }
    public function cancel(Request $request, $id)
    {
        // Cancellations must be handled by Lantaka staff directly.
        // Clients are directed to contact Lantaka; this endpoint is no longer
        // used for direct client-side cancellation.
        return response()->json([
            'contact' => true,
            'message' => 'To cancel your reservation, please contact Lantaka directly at lantaka@adzu.edu.ph.',
        ], 200);
    }
    public function storeReservation(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:Account,Account_ID',
            'accommodation_id' => 'required|integer',
            'type' => 'required|in:room,venue',
            'check_in' => 'required|date',
            'check_out' => 'required|date|after_or_equal:check_in',
            'pax' => 'required|integer|min:1',
        ]);

        $checkIn  = Carbon::parse($request->check_in);
        $checkOut = Carbon::parse($request->check_out);
        
        if ($request->type === 'venue') {
            $days = $checkIn->diffInDays($checkOut) + 1;
        } else {
            $days = $checkIn->diffInDays($checkOut) ?: 1;
        }
        

        if ($request->type === 'room') {
            $room = Room::findOrFail($request->accommodation_id);
            $client = Account::find($request->user_id);
            $price = ($client && $client->Account_Type === 'Internal')
                ? ($room->Room_Internal_Price ?? 0)
                : ($room->Room_External_Price ?? 0);
            $totalAmount = $price * $days;

            $reservation = RoomReservation::create([
                'Room_ID' => $request->accommodation_id,
                'Client_ID' => $request->user_id,
                'Room_Reservation_Date' => now(),
                'Room_Reservation_Check_In_Time' => $request->check_in,
                'Room_Reservation_Check_Out_Time' => $request->check_out,
                'Room_Reservation_Pax' => $request->pax,
                'Room_Reservation_Purpose' => $request->purpose,
                'Room_Reservation_Total_Price' => $totalAmount,
                'Room_Reservation_Status' => 'pending',
            ]);

            $this->sendConfirmationEmail($reservation);
        } else {
            // Edit mode: reuse existing VenueReservation instead of creating a new one
            if ($request->filled('venue_reservation_id')) {
                $reservation = VenueReservation::findOrFail($request->venue_reservation_id);
            } else {
                $venue = Venue::findOrFail($request->accommodation_id);
                $venueClient = Account::find($request->user_id);
                $basePrice = ($venueClient && $venueClient->Account_Type === 'Internal')
                    ? ($venue->Venue_Internal_Price ?? 0)
                    : ($venue->Venue_External_Price ?? 0);
                $totalAmount = $basePrice * $days;

                $reservation = VenueReservation::create([
                    'Venue_ID' => $request->accommodation_id,
                    'Client_ID' => $request->user_id,
                    'Venue_Reservation_Date' => now(),
                    'Venue_Reservation_Check_In_Time' => $request->check_in,
                    'Venue_Reservation_Check_Out_Time' => $request->check_out,
                    'Venue_Reservation_Pax' => $request->pax,
                    'Venue_Reservation_Purpose' => $request->purpose,
                    'Venue_Reservation_Total_Price' => $totalAmount,
                    'Venue_Reservation_Status' => 'pending',
                ]);

                $this->sendConfirmationEmail($reservation);
            }

            // Process food_selections[date][mealType][category] from the food page
            $foodSelections = $request->input('food_selections', []);

            foreach ($foodSelections as $date => $meals) {
                foreach ($meals as $mealType => $foodIds) {
                    if (!is_array($foodIds) || empty($foodIds)) {
                        continue;
                    }

                    foreach ($foodIds as $foodId) {
                        if (!$foodId) continue;

                        $food = \App\Models\Food::find($foodId);

                        if ($food) {
                            $price = $food->Food_Price ?? 0;

                            \App\Models\FoodReservation::create([
                                'Food_ID'                       => $foodId,
                                'Venue_Reservation_ID'          => $reservation->Venue_Reservation_ID,
                                'Client_ID'                     => $reservation->Client_ID,
                                'Food_Reservation_Serving_Date' => $date,
                                'Food_Reservation_Meal_time'    => $mealType,
                                'Food_Reservation_Total_Price'  => $price * $request->pax,
                            ]);
                        }
                    }
                }
            }

            // Clear session booking after venue save
            $allBookings = session('employee_pending_bookings', []);
            $uniqueKey   = $request->type . '_' . $request->accommodation_id;

            unset($allBookings[$uniqueKey]);
            session(['employee_pending_bookings' => $allBookings]);
        }

        return redirect()
            ->route('employee.reservations')
            ->with('success', 'Reservation created successfully.');
    }

    private function sendConfirmationEmail($reservation)
{
    try {
        $user = Account::find($reservation->Client_ID);
        if ($user && $user->Account_Email) {
            Mail::to($user->Account_Email)->send(
                new \App\Mail\ReservationConfirmationMail([$reservation])
            );
        }
    } catch (\Exception $e) {
        Log::error('sendConfirmationEmail failed: ' . $e->getMessage());
    }
}

    public function prepareEmployeeBooking(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:Account,Account_ID',
            'accommodation_id' => 'required|integer',
            'type' => 'required|in:room,venue',
            'check_in' => 'required|date',
            'check_out' => 'required|date|after_or_equal:check_in',
            'pax' => 'required|integer|min:1',
            'purpose' => 'required|string'
        ]);

        // ── Edit mode: reservation_id is present ──
        if ($request->filled('reservation_id')) {
            return $this->updateExistingReservation($request);
        }

        $bookingData = $request->all();

        // ROOM: save immediately
        if ($request->type === 'room') {
            return $this->storeReservation($request);
        }

        // VENUE: keep booking data in session and go to employee food page
        if ($request->type === 'venue') {
            $allBookings = session('employee_pending_bookings', []);

            $uniqueKey = $request->type . '_' . $request->accommodation_id;
            $allBookings[$uniqueKey] = $bookingData;

            session(['employee_pending_bookings' => $allBookings]);

            return redirect()->route('employee.create_food_reservation', [
                'accommodation_id' => $request->accommodation_id,
                'type' => $request->type,
            ]);
        }
    }

    /**
     * Update an existing room or venue reservation's dates, pax, and total price.
     * For venue reservations, clears food selections and redirects to the food page
     * so the employee can re-select meals for the new date range.
     */
    private function updateExistingReservation(Request $request)
    {
        $checkIn  = \Carbon\Carbon::parse($request->check_in);
        $checkOut = \Carbon\Carbon::parse($request->check_out);
        $days     = $checkIn->diffInDays($checkOut);

        if ($request->type === 'room') {
            $reservation = \App\Models\RoomReservation::findOrFail($request->reservation_id);
            $room        = \App\Models\Room::findOrFail($request->accommodation_id);
            $client = \App\Models\Account::find($reservation->Client_ID);
            $price = ($client && $client->Account_Type === 'Internal')
                ? ($room->Room_Internal_Price ?? 0)
                : ($room->Room_External_Price ?? 0);
            $totalAmount = $price * $days;

            $reservation->update([
                'Room_Reservation_Check_In_Time'  => $request->check_in,
                'Room_Reservation_Check_Out_Time' => $request->check_out,
                'Room_Reservation_Pax'            => $request->pax,
                'Room_Reservation_Total_Price'    => $totalAmount,
                'Room_Reservation_Purpose'        => $request->purpose,
            ]);

            return redirect()
                ->route('employee.reservations')
                ->with('success', 'Room reservation updated successfully.');
        }

        if ($request->type === 'venue') {
            $reservation = \App\Models\VenueReservation::findOrFail($request->reservation_id);
            $venue       = \App\Models\Venue::findOrFail($request->accommodation_id);
            $venueClient = \App\Models\Account::find($reservation->Client_ID);
            $basePrice   = ($venueClient && $venueClient->Account_Type === 'Internal')
                ? ($venue->Venue_Internal_Price ?? 0)
                : ($venue->Venue_External_Price ?? 0);
            $totalAmount = $basePrice * ($days + 1); // venues are day-inclusive

            $reservation->update([
                'Venue_Reservation_Check_In_Time'  => $request->check_in,
                'Venue_Reservation_Check_Out_Time' => $request->check_out,
                'Venue_Reservation_Pax'            => $request->pax,
                'Venue_Reservation_Total_Price'    => $totalAmount,
                'Venue_Reservation_Purpose'        => $request->purpose,
            ]);

            // ── Snapshot existing food selections BEFORE deleting ──
            $previousFoodSelections = [];
            $previousFoodEnabled    = [];
            $previousMealEnabled    = [];

            // Load foods() relationship (withPivot for Food_Reservation_Serving_Date, Food_Reservation_Meal_time)
            foreach ($reservation->foods as $food) {
                $date     = $food->pivot->Food_Reservation_Serving_Date ?? null;
                $mealType = $food->pivot->Food_Reservation_Meal_time    ?? null;
                $category = $food->Food_Category       ?? null;
                $foodId   = $food->Food_ID ?? $food->Food_ID ?? null;

                if (!$date || !$mealType || !$category || !$foodId) continue;

                $previousFoodEnabled[$date]                        = '1';
                $previousMealEnabled[$date][$mealType]             = '1';
                $previousFoodSelections[$date][$mealType][$category] = (string) $foodId;
            }

            // Clear old food records so the employee can re-select on the food page
            \App\Models\FoodReservation::where(
                'Venue_Reservation_ID',
                $reservation->Venue_Reservation_ID
            )->delete();

            // Store booking in session (with venue_reservation_id + prefill data)
            $allBookings  = session('employee_pending_bookings', []);
            $uniqueKey    = $request->type . '_' . $request->accommodation_id;
            $allBookings[$uniqueKey] = array_merge($request->all(), [
                'mode'                    => 'edit',
                'venue_reservation_id'    => $reservation->Venue_Reservation_ID,
                'prefill_food_selections' => $previousFoodSelections,
                'prefill_food_enabled'    => $previousFoodEnabled,
                'prefill_meal_enabled'    => $previousMealEnabled,
            ]);
            session(['employee_pending_bookings' => $allBookings]);

            return redirect()->route('employee.create_food_reservation', [
                'accommodation_id' => $request->accommodation_id,
                'type'             => $request->type,
            ]);
        }
    }

    public function showEmployeeFoodReservation(Request $request)
    {
        $accommodationId = $request->accommodation_id;
        $type = $request->type;

        $allBookings = session('employee_pending_bookings', []);
        $uniqueKey = $type . '_' . $accommodationId;

        $bookingData = $allBookings[$uniqueKey] ?? null;

        if (!$bookingData) {
            return redirect()->route('employee.room_venue')
                ->with('error', 'No pending booking found.');
        }
        $foods = Food::orderBy('Food_Category')->get();

        return view('employee.create_food_reservation', compact('bookingData', 'foods'));
    }

    public function showSOA($clientId)
    {
        $client = Account::findOrFail($clientId);

        $roomReservations = RoomReservation::with('room')
            ->where('Client_ID', $clientId)
            ->where('Room_Reservation_Status', 'checked-in')
            ->get();

        $venueReservations = VenueReservation::with('venue')
            ->where('Client_ID', $clientId)
            ->where('Venue_Reservation_Status', 'checked-in')
            ->get();

        $reservations = collect();

        foreach ($roomReservations as $r) {
            $checkIn = \Carbon\Carbon::parse($r->Room_Reservation_Check_In_Time);
            $checkOut = \Carbon\Carbon::parse($r->Room_Reservation_Check_Out_Time);
            $days = $checkIn->diffInDays($checkOut) ?: 1;

            $rawItems = json_decode($r->Room_Reservation_Additional_Fees_Desc ?? '[]', true) ?? [];
            $parsedItems = [];

            foreach ($rawItems as $item) {
                $parts = explode(':', $item);

                $desc = $parts[0] ?? '';
                $qty = (int) ($parts[1] ?? 1);
                $amount = (float) ($parts[2] ?? 0);
                $date = $parts[3] ?? '';

                $parsedItems[] = [
                    'desc' => $desc,
                    'qty' => $qty,
                    'amount' => $amount,
                    'line_total' => $qty * $amount,
                    'date' => $date,
                ];
            }

            $additionalFees = (float) ($r->Room_Reservation_Additional_Fees ?? 0);
            $discount = (float) ($r->Room_Reservation_Discount ?? 0);
            $baseAmount = (float) ($r->Room_Reservation_Total_Price ?? 0) - $additionalFees + $discount;

            $reservations->push([
                'type' => 'room',
                'id' => $r->Room_Reservation_ID,
                'name' => 'Room ' . ($r->room->Room_Number ?? 'Error'),
                'check_in' => $checkIn->format('m/d/Y'),
                'check_out' => $checkOut->format('m/d/Y'),
                'pax' => $r->Room_Reservation_Pax,
                'days' => $days,
                'base_price' => $baseAmount,
                'total_price' => $r->Room_Reservation_Total_Price ?? 0,
                'additional_fees' => $additionalFees,
                'discount' => $discount,
                'additional_fee_items' => $parsedItems,
            ]);
        }

        foreach ($venueReservations as $v) {
            $checkIn = \Carbon\Carbon::parse($v->Venue_Reservation_Check_In_Time);
            $checkOut = \Carbon\Carbon::parse($v->Venue_Reservation_Check_Out_Time);
            $days = $checkIn->diffInDays($checkOut) ?: 1;

            $rawItems = json_decode($v->Venue_Reservation_Additional_Fees_Desc ?? '[]', true) ?? [];
            $parsedItems = [];

            foreach ($rawItems as $item) {
                $parts = explode(':', $item);

                $desc = $parts[0] ?? '';
                $qty = (int) ($parts[1] ?? 1);
                $amount = (float) ($parts[2] ?? 0);
                $date = $parts[3] ?? '';

                $parsedItems[] = [
                    'desc' => $desc,
                    'qty' => $qty,
                    'amount' => $amount,
                    'line_total' => $qty * $amount,
                    'date' => $date,
                ];
            }

            $additionalFees = (float) ($v->Venue_Reservation_Additional_Fees ?? 0);
            $discount = (float) ($v->Venue_Reservation_Discount ?? 0);
            $baseAmount = (float) ($v->Venue_Reservation_Total_Price ?? 0) - $additionalFees + $discount;

            $reservations->push([
                'type' => 'venue',
                'id' => $v->Venue_Reservation_ID,
                'name' => 'Venue ' . ($v->venue->Venue_Name ?? 'Error'),
                'check_in' => $checkIn->format('m/d/Y'),
                'check_out' => $checkOut->format('m/d/Y'),
                'pax' => $v->Venue_Reservation_Pax,
                'days' => $days,
                'base_price' => $baseAmount,
                'total_price' => $v->Venue_Reservation_Total_Price ?? 0,
                'additional_fees' => $additionalFees,
                'discount' => $discount,
                'additional_fee_items' => $parsedItems,
            ]);
        }

        return view('employee.SOA', compact('client', 'reservations'));
    }

    public function exportSOA(Request $request, $clientId)
    {
        $selectedItems = json_decode($request->input('selected_items', '[]'), true) ?? [];

        $roomIds = collect($selectedItems)
            ->where('type', 'room')
            ->pluck('id')
            ->map(fn($id) => (int) $id)
            ->toArray();

        $venueIds = collect($selectedItems)
            ->where('type', 'venue')
            ->pluck('id')
            ->map(fn($id) => (int) $id)
            ->toArray();

        $client = Account::findOrFail($clientId);

        $roomReservations = RoomReservation::with('room')
            ->where('Client_ID', $clientId)
            ->where('Room_Reservation_Status', 'checked-in')
            ->when(!empty($roomIds), function ($query) use ($roomIds) {
                $query->whereIn('Room_Reservation_ID', $roomIds);
            }, function ($query) {
                $query->whereRaw('1 = 0');
            })
            ->get();

        $venueReservations = VenueReservation::with('venue')
            ->where('Client_ID', $clientId)
            ->where('Venue_Reservation_Status', 'checked-in')
            ->when(!empty($venueIds), function ($query) use ($venueIds) {
                $query->whereIn('Venue_Reservation_ID', $venueIds);
            }, function ($query) {
                $query->whereRaw('1 = 0');
            })
            ->get();

        $reservations = collect();

        // ── Build line items ──────────────────────────────────────────────────
        foreach ($roomReservations as $r) {
            $checkIn  = Carbon::parse($r->Room_Reservation_Check_In_Time);
            $checkOut = Carbon::parse($r->Room_Reservation_Check_Out_Time);
            $nights   = max(1, $checkIn->diffInDays($checkOut));

            $addFees    = (float) ($r->Room_Reservation_Additional_Fees ?? 0);
            $baseAmount = (float) ($r->Room_Reservation_Total_Price ?? 0) - $addFees;
            $ratePerNight = $nights > 0 ? $baseAmount / $nights : $baseAmount;

            $reservations->push([
                'date'        => $checkIn->format('F d, Y'),
                'particulars' => 'Room ' . ($r->room->Room_Number ?? 'Room'),
                'qty'         => $nights,
                'unit'        => 'night',
                'rate'        => $ratePerNight,
                'amount'      => $baseAmount,
                'is_subitem'  => false,
                'is_discount' => false,
            ]);

            $rawItems = json_decode($r->Room_Reservation_Additional_Fees_Desc ?? '[]', true) ?? [];
            foreach ($rawItems as $item) {
                $parts     = explode(':', $item);
                $desc      = trim($parts[0] ?? '');
                $qty       = (int) ($parts[1] ?? 1);
                $unitRate  = (float) ($parts[2] ?? 0);
                $chDate    = !empty($parts[3]) ? Carbon::parse($parts[3])->format('F d, Y') : '';
                $reservations->push([
                    'date'        => $chDate,
                    'particulars' => $desc,
                    'qty'         => $qty,
                    'unit'        => 'lot',
                    'rate'        => $unitRate,
                    'amount'      => $qty * $unitRate,
                    'is_subitem'  => true,
                    'is_discount' => false,
                ]);
            }
        }

        foreach ($venueReservations as $v) {
            $checkIn  = Carbon::parse($v->Venue_Reservation_Check_In_Time);
            $checkOut = Carbon::parse($v->Venue_Reservation_Check_Out_Time);
            $days     = max(1, $checkIn->diffInDays($checkOut));

            $addFees    = (float) ($v->Venue_Reservation_Additional_Fees ?? 0);
            $discount   = (float) ($v->Venue_Reservation_Discount ?? 0);
            $baseAmount = (float) ($v->Venue_Reservation_Total_Price ?? 0) - $addFees + $discount;
            $ratePerDay = $days > 0 ? $baseAmount / $days : $baseAmount;

            $reservations->push([
                'date'        => $checkIn->format('F d, Y'),
                'particulars' => 'Venue: ' . ($v->venue->Venue_Name ?? 'Venue'),
                'qty'         => $days,
                'unit'        => 'day',
                'rate'        => $ratePerDay,
                'amount'      => $baseAmount,
                'is_subitem'  => false,
                'is_discount' => false,
            ]);

            $rawItems = json_decode($v->Venue_Reservation_Additional_Fees_Desc ?? '[]', true) ?? [];
            foreach ($rawItems as $item) {
                $parts    = explode(':', $item);
                $desc     = trim($parts[0] ?? '');
                $qty      = (int) ($parts[1] ?? 1);
                $unitRate = (float) ($parts[2] ?? 0);
                $chDate   = !empty($parts[3]) ? Carbon::parse($parts[3])->format('F d, Y') : '';
                $reservations->push([
                    'date'        => $chDate,
                    'particulars' => $desc,
                    'qty'         => $qty,
                    'unit'        => 'lot',
                    'rate'        => $unitRate,
                    'amount'      => $qty * $unitRate,
                    'is_subitem'  => true,
                    'is_discount' => false,
                ]);
            }

            if ($discount > 0) {
                $reservations->push([
                    'date'        => '',
                    'particulars' => 'Discount',
                    'qty'         => 1,
                    'unit'        => 'lot',
                    'rate'        => $discount,
                    'amount'      => $discount,
                    'is_subitem'  => true,
                    'is_discount' => true,
                ]);
            }
        }

        // ── Load template ────────────────────────────────────────────────────
        $templatePath = resource_path('templates/SOA_Template_Final.xlsx');
        if (!file_exists($templatePath)) {
            abort(500, 'SOA template not found.');
        }

        $spreadsheet = IOFactory::load($templatePath);
        $sheet       = $spreadsheet->getActiveSheet();

        // ── Template constants ───────────────────────────────────────────────
        // The uploaded template uses columns C–J for the data table:
        //   C = DATE | D = PARTICULARS | E = QTY | F = UNIT | G = RATE | J = AMOUNT
        // Static header rows in the template:
        //   C11 = Date line       C13 = "Statement of Account"
        //   C15 = "To:"           C16 = client name
        //   C22 = table header row (DATE, PARTICULARS …)
        //   Row 23 = first data row  (template has 4 data rows: 23–26)
        //   Row 29 = Total row    Row 32 = Total Amount Due
        //   Row 39 = Prepared by  Row 42 = Approved by
        $DATA_HEADER_ROW   = 22;
        $DATA_START_ROW    = 23;
        $TEMPLATE_DATA_ROWS = 4;   // rows 23-26 pre-filled in template

        // ── Header ───────────────────────────────────────────────────────────
        $sheet->setCellValue('C11', 'Date: ' . now()->format('F d, Y'));
        $sheet->setCellValue('C15', 'To:');
        $sheet->setCellValue('C16', $client->Account_Name);

        // ── Dynamic row insertion ────────────────────────────────────────────
        $numItems  = $reservations->count();
        $extraRows = max(0, $numItems - $TEMPLATE_DATA_ROWS);

        if ($extraRows > 0) {
            // Insert after the last pre-built data row so template rows shift down
            $sheet->insertNewRowBefore($DATA_START_ROW + $TEMPLATE_DATA_ROWS, $extraRows);
        }

        // ── Write data rows ──────────────────────────────────────────────────
        $subtotal          = 0.0;
        $totalAdditionalFees = 0.0;
        $totalDiscounts    = 0.0;
        $pesoFmt           = '"₱"#,##0.00';

        foreach ($reservations as $i => $r) {
            $row    = $DATA_START_ROW + $i;
            $amount = (float) ($r['amount'] ?? 0);

            $sheet->setCellValue("C{$row}", $r['date'] ?? '');
            $sheet->setCellValue("D{$row}", $r['particulars'] ?? '');
            $sheet->setCellValue("E{$row}", $r['qty'] ?? '');
            $sheet->setCellValue("F{$row}", $r['unit'] ?? '');
            $sheet->setCellValue("G{$row}", (float) ($r['rate'] ?? 0));
            $sheet->setCellValue("J{$row}", $amount);

            // Currency formatting
            $sheet->getStyle("G{$row}")->getNumberFormat()->setFormatCode($pesoFmt);
            $sheet->getStyle("J{$row}")->getNumberFormat()->setFormatCode($pesoFmt);

            // Apply basic border to the row (matching template style)
            $borderStyle = [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color'       => ['argb' => 'FFD9D9D9'],
                    ],
                ],
            ];
            $sheet->getStyle("C{$row}:J{$row}")->applyFromArray($borderStyle);

            // Accumulate totals
            if ($r['is_discount'] ?? false) {
                $totalDiscounts += $amount;
            } elseif ($r['is_subitem'] ?? false) {
                $totalAdditionalFees += $amount;
            } else {
                $subtotal += $amount;
            }
        }

        // Clear any unused template data rows (when numItems < TEMPLATE_DATA_ROWS)
        for ($i = $numItems; $i < $TEMPLATE_DATA_ROWS; $i++) {
            $row = $DATA_START_ROW + $i;
            foreach (['C', 'D', 'E', 'F', 'G', 'H', 'J'] as $col) {
                $sheet->setCellValue("{$col}{$row}", '');
            }
        }

        // ── Summary section ──────────────────────────────────────────────────
        // After row insertion the original template rows shift by $extraRows.
        // Template: Total=29, blank=30, blank=31, TotalAmtDue=32
        // We repurpose those rows for a 4-row summary.
        $dataEndRow      = $DATA_START_ROW + $numItems - 1;
        $summaryBase     = 29 + $extraRows;  // first summary row

        $boldFont = ['font' => ['bold' => true, 'size' => 11]];
        $totalFont = ['font' => ['bold' => true, 'size' => 12]];

        // Row 1 – Subtotal
        $sheet->setCellValue("G{$summaryBase}", 'Subtotal:');
        $sheet->setCellValue("J{$summaryBase}", $subtotal);
        $sheet->getStyle("G{$summaryBase}")->applyFromArray($boldFont);
        $sheet->getStyle("J{$summaryBase}")->getNumberFormat()->setFormatCode($pesoFmt);
        $sheet->getStyle("J{$summaryBase}")->applyFromArray($boldFont);

        // Row 2 – Additional Fees
        $r2 = $summaryBase + 1;
        $sheet->setCellValue("G{$r2}", 'Additional Fees:');
        $sheet->setCellValue("J{$r2}", $totalAdditionalFees);
        $sheet->getStyle("G{$r2}")->applyFromArray($boldFont);
        $sheet->getStyle("J{$r2}")->getNumberFormat()->setFormatCode($pesoFmt);

        // Row 3 – Discounts
        $r3 = $summaryBase + 2;
        $sheet->setCellValue("G{$r3}", 'Discounts:');
        $sheet->setCellValue("J{$r3}", $totalDiscounts);
        $sheet->getStyle("G{$r3}")->applyFromArray($boldFont);
        $sheet->getStyle("J{$r3}")->getNumberFormat()->setFormatCode($pesoFmt);

        // Row 4 – Total Amount Due
        $r4 = $summaryBase + 3;
        $total = $subtotal + $totalAdditionalFees - $totalDiscounts;
        $sheet->setCellValue("G{$r4}", 'Total Amount Due:');
        $sheet->setCellValue("J{$r4}", $total);
        $sheet->getStyle("G{$r4}")->applyFromArray($totalFont);
        $sheet->getStyle("J{$r4}")->getNumberFormat()->setFormatCode($pesoFmt);
        $sheet->getStyle("J{$r4}")->applyFromArray($totalFont);

        // ── Export ───────────────────────────────────────────────────────────
        $fileName = 'SOA_' . str_replace(' ', '_', $client->Account_Name) . '_' . now()->format('Ymd') . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), 'soa');

        $writer = new Xlsx($spreadsheet);
        $writer->save($tempFile);

        // ── Restore DrawingML shapes (text boxes, logo) ───────────────────────
        // PhpSpreadsheet silently discards all DrawingML shapes when loading an
        // .xlsx template. We re-inject them at the ZIP level after saving so the
        // AdZU letterhead (text boxes + logo image) is preserved in the output.
        try {
            $tplZip = new \ZipArchive();
            $outZip = new \ZipArchive();

            if ($tplZip->open($templatePath) === true && $outZip->open($tempFile) === true) {

                // 1. Copy every drawing / media file verbatim from the template
                $drawingAssets = [
                    'xl/drawings/drawing1.xml',
                    'xl/drawings/_rels/drawing1.xml.rels',
                    'xl/media/image1.png',
                ];
                foreach ($drawingAssets as $asset) {
                    $bytes = $tplZip->getFromName($asset);
                    if ($bytes !== false) {
                        $outZip->deleteName($asset); // remove if PhpSpreadsheet wrote a stub
                        $outZip->addFromString($asset, $bytes);
                    }
                }

                // 2. Add the drawing Override to [Content_Types].xml if missing
                $ctName = '[Content_Types].xml';
                $ct = $outZip->getFromName($ctName);
                if ($ct && strpos($ct, '/xl/drawings/drawing1.xml') === false) {
                    $drawingCt = '<Override PartName="/xl/drawings/drawing1.xml"'
                               . ' ContentType="application/vnd.openxmlformats-officedocument.drawing+xml"/>';
                    $ct = str_replace('</Types>', $drawingCt . '</Types>', $ct);
                    $outZip->deleteName($ctName);
                    $outZip->addFromString($ctName, $ct);
                }

                // 3. Add drawing relationship to xl/worksheets/_rels/sheet1.xml.rels
                $relsFile = 'xl/worksheets/_rels/sheet1.xml.rels';
                $rels = $outZip->getFromName($relsFile);
                $drawingRelId = 'rId_soa_drw';
                $drawingRel   = '<Relationship Id="' . $drawingRelId . '"'
                              . ' Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/drawing"'
                              . ' Target="../drawings/drawing1.xml"/>';

                if ($rels) {
                    if (strpos($rels, 'drawings/drawing1.xml') === false) {
                        $rels = str_replace('</Relationships>', $drawingRel . '</Relationships>', $rels);
                        $outZip->deleteName($relsFile);
                        $outZip->addFromString($relsFile, $rels);
                    }
                } else {
                    $newRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n"
                             . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
                             . $drawingRel . '</Relationships>';
                    $outZip->addFromString($relsFile, $newRels);
                    $drawingRelId = $drawingRelId; // still correct
                }

                // 4. Add <drawing r:id="..."/> to the worksheet XML before </worksheet>
                // (the xmlns:r prefix is already declared on the root <worksheet> element)
                $sheetFile = 'xl/worksheets/sheet1.xml';
                $sheetXml  = $outZip->getFromName($sheetFile);
                if ($sheetXml && strpos($sheetXml, '<drawing') === false) {
                    $drawingTag = '<drawing r:id="' . $drawingRelId . '"/>';
                    $sheetXml = str_replace('</worksheet>', $drawingTag . '</worksheet>', $sheetXml);
                    $outZip->deleteName($sheetFile);
                    $outZip->addFromString($sheetFile, $sheetXml);
                }

                $tplZip->close();
                $outZip->close();
            }
        } catch (\Throwable $e) {
            // Non-fatal: export still works, just without shapes
            \Illuminate\Support\Facades\Log::warning('SOA drawing restore failed: ' . $e->getMessage());
        }

        return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);
    }


    public function fetchUpdatedCalendarData()
    {
        $roomRes = RoomReservation::with(['room', 'user'])->get()->map(function ($item) {
            return [
                'id' => $item->Room_Reservation_ID,
                'status' => strtolower($item->Room_Reservation_Status),
                'check_in' => Carbon::parse($item->Room_Reservation_Check_In_Time)->format('Y-m-d'),
                'check_out' => Carbon::parse($item->Room_Reservation_Check_Out_Time)->format('Y-m-d'),
                'user' => $item->user ? [
                    'name' => $item->user->Account_Name
                    ] : null,
                'label' => $item->room ? 'Room ' . $item->room->Room_Number : 'Room N/A',
                'type' => 'room',
            ];
        });

        $venueRes = VenueReservation::with(['venue', 'user'])->get()->map(function ($item) {
            return [
                'id' => $item->Venue_Reservation_ID,
                'status' => strtolower($item->Venue_Reservation_Status),
                'check_in' => Carbon::parse($item->Venue_Reservation_Check_In_Time)->format('Y-m-d'),
                'check_out' => Carbon::parse($item->Venue_Reservation_Check_Out_Time)->format('Y-m-d'),
                'user' => $item->user ? [
                    'name' => $item->user->Account_Name
                ] : null,
                'label' => $item->venue ? $item->venue->Venue_Name : 'Venue N/A',
                'type' => 'venue',
            ];
        });

        $reservations = $roomRes->concat($venueRes)->values();

        $thisMonthStart = Carbon::now()->startOfMonth();
        $thisMonthEnd   = Carbon::now()->endOfMonth();
        $totalReservations = RoomReservation::where('Room_Reservation_Status', 'checked-out')
                                ->whereBetween('created_at', [$thisMonthStart, $thisMonthEnd])->count()
                           + VenueReservation::where('Venue_Reservation_Status', 'checked-out')
                                ->whereBetween('created_at', [$thisMonthStart, $thisMonthEnd])->count();

        $roomRevenue = RoomReservation::where('Room_Reservation_Status', 'checked-out')
            ->where('Room_Reservation_Payment_Status', 'paid')
            ->sum('Room_Reservation_Total_Price');

        $venueRevenue = VenueReservation::where('Venue_Reservation_Status', 'checked-out')
            ->where('Venue_Reservation_Payment_Status', 'paid')
            ->sum('Venue_Reservation_Total_Price');

        $totalRevenue = $roomRevenue + $venueRevenue;

        $today = Carbon::today()->toDateString();

        $activeRoomGuests = RoomReservation::where('Room_Reservation_Status', 'checked-in')
            ->whereDate('Room_Reservation_Check_In_Time', '<=', $today)
            ->whereDate('Room_Reservation_Check_Out_Time', '>=', $today)
            ->sum('Room_Reservation_Pax');

        $activeVenueGuests = VenueReservation::where('Venue_Reservation_Status', 'checked-in')
            ->whereDate('Venue_Reservation_Check_In_Time', '<=', $today)
            ->whereDate('Venue_Reservation_Check_Out_Time', '>=', $today)
            ->sum('Venue_Reservation_Pax');

        $activeGuests = $activeRoomGuests + $activeVenueGuests;

        $days = 30;
        $totalRooms = Room::count();
        $totalRoomNights = $totalRooms * $days;

        $roomNightsSold = RoomReservation::where('Room_Reservation_Status', 'checked-in')
            ->whereBetween('Room_Reservation_Check_In_Time', [
                Carbon::now()->subDays($days),
                Carbon::now()
            ])
            ->get()
            ->sum(function ($r) {
                $in  = Carbon::parse($r->Room_Reservation_Check_In_Time);
                $out = Carbon::parse($r->Room_Reservation_Check_Out_Time);
                return max(1, $in->diffInDays($out));
            });

        $occupancyRate = $totalRoomNights > 0
            ? ($roomNightsSold / $totalRoomNights) * 100
            : 0;

        // Guests due to check out today (checked-in status with today's checkout date)
        $checkOutsTodayRooms = RoomReservation::whereDate('Room_Reservation_Check_Out_Time', $today)
            ->where('Room_Reservation_Status', 'checked-in')
            ->count();

        $checkOutsTodayVenues = VenueReservation::whereDate('Venue_Reservation_Check_Out_Time', $today)
            ->where('Venue_Reservation_Status', 'checked-in')
            ->count();

        $checkOutsTodayCount = $checkOutsTodayRooms + $checkOutsTodayVenues;

        $changes = $this->computeStatChanges($occupancyRate, $activeGuests);

        return response()->json([
            'reservations' => $reservations,
            'stats' => [
                'totalReservations'  => $totalReservations,
                'totalRevenue'       => $totalRevenue,
                'activeGuests'       => $activeGuests,
                'occupancyRate'      => round($occupancyRate, 1),
                'checkOutsTodayCount'=> $checkOutsTodayCount,
            ],
            'changes' => $changes,
        ]);
    }

    /* ─────────────────────────────────────────────────────────────
     * Compute month-over-month % change for each dashboard stat.
     * "This month"  = current calendar month (1st → today)
     * "Last month"  = full previous calendar month
     * Occupancy     = current 30-day window vs previous 30-60 days
     * ───────────────────────────────────────────────────────────── */
    public function analyticsReportData(Request $request)
    {
        $month = (int) $request->input('month', Carbon::now()->month);
        $year  = (int) $request->input('year',  Carbon::now()->year);

        $start     = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $end       = Carbon::createFromDate($year, $month, 1)->endOfMonth();
        $prevStart = Carbon::createFromDate($year, $month, 1)->subMonth()->startOfMonth();
        $prevEnd   = Carbon::createFromDate($year, $month, 1)->subMonth()->endOfMonth();

        $pct = function (float $cur, float $prev): float {
            if ($prev == 0) return $cur > 0 ? 100.0 : 0.0;
            return round((($cur - $prev) / $prev) * 100, 1);
        };

        // ── Summary (checked-out only) ──
        $roomCount      = RoomReservation::where('Room_Reservation_Status', 'checked-out')->whereBetween('created_at', [$start, $end])->count();
        $venueCount     = VenueReservation::where('Venue_Reservation_Status', 'checked-out')->whereBetween('created_at', [$start, $end])->count();
        $prevRoomCount  = RoomReservation::where('Room_Reservation_Status', 'checked-out')->whereBetween('created_at', [$prevStart, $prevEnd])->count();
        $prevVenueCount = VenueReservation::where('Venue_Reservation_Status', 'checked-out')->whereBetween('created_at', [$prevStart, $prevEnd])->count();
        $totalRes       = $roomCount + $venueCount;
        $prevTotalRes   = $prevRoomCount + $prevVenueCount;

        // ── Revenue (checked-out + paid) ──
        $roomRevThis    = (float) RoomReservation::where('Room_Reservation_Status', 'checked-out')->where('Room_Reservation_Payment_Status', 'paid')->whereBetween('created_at', [$start, $end])->sum('Room_Reservation_Total_Price');
        $venueRevThis   = (float) VenueReservation::where('Venue_Reservation_Status', 'checked-out')->where('Venue_Reservation_Payment_Status', 'paid')->whereBetween('created_at', [$start, $end])->sum('Venue_Reservation_Total_Price');
        $roomRevPrev    = (float) RoomReservation::where('Room_Reservation_Status', 'checked-out')->where('Room_Reservation_Payment_Status', 'paid')->whereBetween('created_at', [$prevStart, $prevEnd])->sum('Room_Reservation_Total_Price');
        $venueRevPrev   = (float) VenueReservation::where('Venue_Reservation_Status', 'checked-out')->where('Venue_Reservation_Payment_Status', 'paid')->whereBetween('created_at', [$prevStart, $prevEnd])->sum('Venue_Reservation_Total_Price');
        $totalRevenue   = $roomRevThis + $venueRevThis;
        $prevRevenue    = $roomRevPrev + $venueRevPrev;

        // ── Status Breakdown ──
        $statuses      = ['pending', 'confirmed', 'checked-in', 'checked-out', 'completed', 'cancelled'];
        $roomStatuses  = RoomReservation::whereBetween('created_at', [$start, $end])
                            ->selectRaw('"Room_Reservation_Status" as status, count(*) as cnt')->groupBy('Room_Reservation_Status')
                            ->pluck('cnt', 'status');
        $venueStatuses = VenueReservation::whereBetween('created_at', [$start, $end])
                            ->selectRaw('"Venue_Reservation_Status" as status, count(*) as cnt')->groupBy('Venue_Reservation_Status')
                            ->pluck('cnt', 'status');
        $statusBreakdown = [];
        foreach ($statuses as $s) {
            $statusBreakdown[$s] = ($roomStatuses[$s] ?? 0) + ($venueStatuses[$s] ?? 0);
        }

        // ── Daily Breakdown (checked-out only; revenue = checked-out + paid) ──
        $daysInMonth = $end->day;
        $roomDaily   = RoomReservation::where('Room_Reservation_Status', 'checked-out')
                        ->whereBetween('created_at', [$start, $end])
                        ->selectRaw('EXTRACT(DAY FROM created_at)::int as day, count(*) as cnt, sum(CASE WHEN "Room_Reservation_Payment_Status" = \'paid\' THEN "Room_Reservation_Total_Price" ELSE 0 END) as rev')
                        ->groupByRaw('EXTRACT(DAY FROM created_at)')->get()->keyBy('day');
        $venueDaily  = VenueReservation::where('Venue_Reservation_Status', 'checked-out')
                        ->whereBetween('created_at', [$start, $end])
                        ->selectRaw('EXTRACT(DAY FROM created_at)::int as day, count(*) as cnt, sum(CASE WHEN "Venue_Reservation_Payment_Status" = \'paid\' THEN "Venue_Reservation_Total_Price" ELSE 0 END) as rev')
                        ->groupByRaw('EXTRACT(DAY FROM created_at)')->get()->keyBy('day');

        $dailyData = [];
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $dailyData[] = [
                'day'          => $d,
                'reservations' => (int)(($roomDaily[$d]->cnt ?? 0) + ($venueDaily[$d]->cnt ?? 0)),
                'revenue'      => (float)(($roomDaily[$d]->rev ?? 0) + ($venueDaily[$d]->rev ?? 0)),
            ];
        }

        // ── Top Rooms (checked-out only; revenue = paid only) ──
        $topRooms = RoomReservation::with('room')
            ->where('Room_Reservation_Status', 'checked-out')
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('"Room_ID", count(*) as bookings, sum(CASE WHEN "Room_Reservation_Payment_Status" = \'paid\' THEN "Room_Reservation_Total_Price" ELSE 0 END) as revenue')
            ->groupBy('Room_ID')->orderByDesc('bookings')->limit(5)->get()
            ->map(fn($r) => [
                'name'     => $r->room ? 'Room ' . $r->room->Room_Number : 'Room #' . $r->Room_ID,
                'bookings' => (int)$r->bookings,
                'revenue'  => (float)$r->revenue,
            ]);

        // ── Top Venues (checked-out only; revenue = paid only) ──
        $topVenues = VenueReservation::with('venue')
            ->where('Venue_Reservation_Status', 'checked-out')
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('"Venue_ID", count(*) as bookings, sum(CASE WHEN "Venue_Reservation_Payment_Status" = \'paid\' THEN "Venue_Reservation_Total_Price" ELSE 0 END) as revenue')
            ->groupBy('Venue_ID')->orderByDesc('bookings')->limit(5)->get()
            ->map(fn($r) => [
                'name'     => $r->venue ? $r->venue->Venue_Name : 'Venue #' . $r->Venue_ID,
                'bookings' => (int)$r->bookings,
                'revenue'  => (float)$r->revenue,
            ]);

        return response()->json([
            'monthLabel'        => Carbon::createFromDate($year, $month, 1)->format('F Y'),
            'prevMonthLabel'    => Carbon::createFromDate($year, $month, 1)->subMonth()->format('F Y'),
            'totalReservations' => $totalRes,
            'totalRevenue'      => $totalRevenue,
            'prevTotalRes'      => $prevTotalRes,
            'prevRevenue'       => $prevRevenue,
            'resPctChange'      => $pct((float)$totalRes, (float)$prevTotalRes),
            'revPctChange'      => $pct($totalRevenue, $prevRevenue),
            'statusBreakdown'   => $statusBreakdown,
            'dailyData'         => $dailyData,
            'roomCount'         => $roomCount,
            'venueCount'        => $venueCount,
            'roomRevenue'       => $roomRevThis,
            'venueRevenue'      => $venueRevThis,
            'topRooms'          => $topRooms,
            'topVenues'         => $topVenues,
        ]);
    }

    private function computeStatChanges(float $currentOccupancy, int $activeGuests): array
    {
        $thisStart = Carbon::now()->startOfMonth();
        $thisEnd   = Carbon::now()->endOfMonth();
        $lastStart = Carbon::now()->subMonth()->startOfMonth();
        $lastEnd   = Carbon::now()->subMonth()->endOfMonth();

        // ── Checked-out reservations this month vs last month ──
        $resThis = RoomReservation::where('Room_Reservation_Status', 'checked-out')
                        ->whereBetween('created_at', [$thisStart, $thisEnd])->count()
                 + VenueReservation::where('Venue_Reservation_Status', 'checked-out')
                        ->whereBetween('created_at', [$thisStart, $thisEnd])->count();

        $resLast = RoomReservation::where('Room_Reservation_Status', 'checked-out')
                        ->whereBetween('created_at', [$lastStart, $lastEnd])->count()
                 + VenueReservation::where('Venue_Reservation_Status', 'checked-out')
                        ->whereBetween('created_at', [$lastStart, $lastEnd])->count();

        // ── Revenue (checked-out + paid) this month vs last month ──
        $revThis = RoomReservation::where('Room_Reservation_Status', 'checked-out')
                        ->where('Room_Reservation_Payment_Status', 'paid')
                        ->whereBetween('created_at', [$thisStart, $thisEnd])->sum('Room_Reservation_Total_Price')
                 + VenueReservation::where('Venue_Reservation_Status', 'checked-out')
                        ->where('Venue_Reservation_Payment_Status', 'paid')
                        ->whereBetween('created_at', [$thisStart, $thisEnd])->sum('Venue_Reservation_Total_Price');

        $revLast = RoomReservation::where('Room_Reservation_Status', 'checked-out')
                        ->where('Room_Reservation_Payment_Status', 'paid')
                        ->whereBetween('created_at', [$lastStart, $lastEnd])->sum('Room_Reservation_Total_Price')
                 + VenueReservation::where('Venue_Reservation_Status', 'checked-out')
                        ->where('Venue_Reservation_Payment_Status', 'paid')
                        ->whereBetween('created_at', [$lastStart, $lastEnd])->sum('Venue_Reservation_Total_Price');

        // ── Occupancy: previous 30-60 day rolling window (night-sum, same method as current) ──
        $totalRooms = Room::count();
        $prevRoomNights = $totalRooms * 30;
        $prevRoomNightsSold = RoomReservation::where('Room_Reservation_Status', 'checked-in')
            ->whereBetween('Room_Reservation_Check_In_Time', [
                Carbon::now()->subDays(60),
                Carbon::now()->subDays(30),
            ])
            ->get()
            ->sum(function ($r) {
                $in  = Carbon::parse($r->Room_Reservation_Check_In_Time);
                $out = Carbon::parse($r->Room_Reservation_Check_Out_Time);
                return max(1, $in->diffInDays($out));
            });
        $prevOccupancy = $prevRoomNights > 0
            ? ($prevRoomNightsSold / $prevRoomNights) * 100
            : 0;

        // ── Active guests: pax checked-in during last month (volume proxy) ──
        $activeGuestsLast = RoomReservation::whereBetween('Room_Reservation_Check_In_Time', [$lastStart, $lastEnd])->sum('Room_Reservation_Pax')
            + VenueReservation::whereBetween('Venue_Reservation_Check_In_Time', [$lastStart, $lastEnd])->sum('Venue_Reservation_Pax');

        // ── Check-outs: this month vs last month ──
        $checkOutsThis = RoomReservation::whereDate('Room_Reservation_Check_Out_Time', '>=', $thisStart)
                ->whereDate('Room_Reservation_Check_Out_Time', '<=', $thisEnd)->count()
            + VenueReservation::whereDate('Venue_Reservation_Check_Out_Time', '>=', $thisStart)
                ->whereDate('Venue_Reservation_Check_Out_Time', '<=', $thisEnd)->count();

        $checkOutsLast = RoomReservation::whereDate('Room_Reservation_Check_Out_Time', '>=', $lastStart)
                ->whereDate('Room_Reservation_Check_Out_Time', '<=', $lastEnd)->count()
            + VenueReservation::whereDate('Venue_Reservation_Check_Out_Time', '>=', $lastStart)
                ->whereDate('Venue_Reservation_Check_Out_Time', '<=', $lastEnd)->count();

        // ── Percent-change helper ──
        $pct = function (float $cur, float $prev): float {
            if ($prev == 0) return $cur > 0 ? 100.0 : 0.0;
            return round((($cur - $prev) / $prev) * 100, 1);
        };

        return [
            'totalReservations' => $pct($resThis,            $resLast),
            'revenue'           => $pct($revThis,            $revLast),
            'occupancyRate'     => $pct($currentOccupancy,   $prevOccupancy),
            'activeGuests'      => $pct((float)$activeGuests, (float)$activeGuestsLast),
            'checkOutsToday'    => $pct($checkOutsThis,      $checkOutsLast),
            'lastMonthLabel'    => Carbon::now()->subMonth()->format('M Y'),
        ];
    }
}   
