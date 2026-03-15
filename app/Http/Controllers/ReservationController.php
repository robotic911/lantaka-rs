<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RoomReservation;
use App\Models\VenueReservation;
use App\Models\FoodReservation;
use App\Models\Room;
use App\Models\Venue;
use App\Models\Food;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


class ReservationController extends Controller
{
    // 1. Show Checkout Page
    public function checkout(Request $request)
    {
        $allBookings = session('pending_bookings', []);

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

                $price = $model->Room_Pricing ?? $model->price ?? 0;
                $name = "Room " . ($model->room_number ?? $model->Room_Number ?? '');
                $img = $model->Room_Image ?? null;
            } else {
                $model = Venue::find($item['accommodation_id']);

                if (!$model) {
                    continue;
                }

                $price = $model->Venue_Pricing ?? $model->price ?? 0;
                $name = $model->name ?? $model->Venue_Name ?? 'Venue';
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
                    $selectedFoods = Food::whereIn('food_id', $allFoodIds)->get()->keyBy('food_id');

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

                                $foodPrice = $food->food_price ?? $food->Food_Price ?? 0;
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

            \Log::info('Selected items received:', $selectedItems);

            $savedReservations = [];

            DB::transaction(function () use ($selectedItems, &$savedReservations) {
                foreach ($selectedItems as $index => $item) {
                    \Log::info("Processing item #{$index}", $item);

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
                            'room_id' => $item['id'],
                            'Client_ID' => Auth::id(),
                            'Room_Reservation_Date' => now(),
                            'Room_Reservation_Check_In_Time' => $item['check_in'],
                            'Room_Reservation_Check_Out_Time' => $item['check_out'],
                            'pax' => $item['pax'],
                            'purpose' => $item['purpose'] ?? null,
                            'Room_Reservation_Total_Price' => $item['total_amount'],
                            'status' => 'pending',
                        ]);

                        \Log::info("Saved room reservation #{$reservation->getKey()} for item #{$index}");

                        $savedReservations[] = $reservation;
                    }

                    if ($item['type'] === 'venue') {
                        $reservation = VenueReservation::create([
                            'venue_id' => $item['id'],
                            'Client_ID' => Auth::id(),
                            'Venue_Reservation_Date' => now(),
                            'Venue_Reservation_Check_In_Time' => $item['check_in'],
                            'Venue_Reservation_Check_Out_Time' => $item['check_out'],
                            'pax' => $item['pax'],
                            'purpose' => $item['purpose'] ?? null,
                            'Venue_Reservation_Total_Price' => $item['total_amount'],
                            'status' => 'pending',
                        ]);

                        \Log::info("Saved venue reservation #{$reservation->Venue_Reservation_ID} for item #{$index}");

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
                                            $price = $food->Food_Price ?? $food->food_price ?? 0;

                                            FoodReservation::create([
                                                'food_id' => $foodId,
                                                'venue_reservation_id' => $reservation->Venue_Reservation_ID,
                                                'client_id' => Auth::id(),
                                                'serving_time' => $date,
                                                'meal_time' => $mealType,
                                                'total_price' => $price * $item['pax'],
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
                Mail::to(auth()->user()->email)->send(
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
        $roomQuery = \App\Models\RoomReservation::with(['room', 'user'])
            ->where('Client_ID', $user->id);

        $venueQuery = \App\Models\VenueReservation::with(['venue', 'user', 'foods'])
            ->where('Client_ID', $user->id);

        // 3. Apply Filters
        foreach ([$roomQuery, $venueQuery] as $query) {
            // Status Filter
            if ($status) $query->where('status', $status);

            // Client Type Filter
            if ($clientType) {
                $query->whereHas('user', fn($q) => $q->where('usertype', $clientType));
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
                    $dateCol = ($query->getModel() instanceof \App\Models\RoomReservation)
                        ? 'Room_Reservation_Date'
                        : 'Venue_Reservation_Date';
                    $query->where($dateCol, '>=', $date);
                }
            }

            // Search Logic (Matching the Guest page)
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $isRoom = ($q->getModel() instanceof \App\Models\RoomReservation);
                    $idCol = $isRoom ? 'Room_Reservation_ID' : 'Venue_Reservation_ID';

                    $q->where($idCol, $search)
                        ->orWhereHas('user', fn($uq) => $uq->where('name', 'like', "%{$search}%"));

                    if ($isRoom) {
                        $q->orWhereHas('room', fn($rq) => $rq->where('room_number', 'like', "%{$search}%"));
                    } else {
                        $q->orWhereHas('venue', fn($vq) => $vq->where('name', 'like', "%{$search}%"));
                    }
                });
            }
        }

        // 4. Get Data
        $rooms = ($accType === 'venue') ? collect() : $roomQuery->get()->map(function ($item) {
            $item->display_type = 'room';
            $item->type = 'room';
            return $item;
        });

        $venues = ($accType === 'room') ? collect() : $venueQuery->get()->map(function ($item) {
            $item->display_type = 'venue';
            $item->type = 'venue';
            return $item;
        });

        $reservations = $rooms->concat($venues)->sortByDesc('created_at');

        // 5. IMPORTANT: This variable is required for your Status Cards in the blade!
        if ($user && ($user->usertype === 'admin' || $user->usertype === 'staff')) {
            $allForCounts = \App\Models\RoomReservation::select('status')->get()
                ->concat(\App\Models\VenueReservation::select('status')->get());

            return view('employee.reservations', compact('reservations', 'allForCounts'));
        }

        // If not an admin/staff, they are a client. Load the Client UI!
        // Make sure this file is at resources/views/client/my_reservations.blade.php
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
            $roomQuery->whereHas('user', fn($q) => $q->where('usertype', $clientType));
            $venueQuery->whereHas('user', fn($q) => $q->where('usertype', $clientType));
        }

        // 3. Filter by Date
        if ($dateFilter) {
            $days = match ($dateFilter) {
                'last_week' => 7,
                'last_month' => 30,
                'last_year' => 365,
                default => 0
            };
            if ($days > 0) {
                $roomQuery->where('created_at', '>=', now()->subDays($days));
                $venueQuery->where('created_at', '>=', now()->subDays($days));
            }
        }

        // 4. Search & Status (Case-Sensitive Column Names)
        if ($search) {
            // Search Rooms
            $roomQuery->where(function ($q) use ($search) {
                $q->whereHas('user', fn($uq) => $uq->where('name', 'ILIKE', "%$search%"))
                    ->orWhereHas('room', fn($rq) => $rq->where('room_number', 'ILIKE', "%$search%"))
                    // Use whereRaw to cast BigInt to Text for comparison
                    ->orWhereRaw('CAST("Room_Reservation_ID" AS TEXT) ILIKE ?', ["%$search%"]);
            });

            // Search Venues
            $venueQuery->where(function ($q) use ($search) {
                $q->whereHas('user', fn($uq) => $uq->where('name', 'ILIKE', "%$search%"))
                    ->orWhereHas('venue', fn($vq) => $vq->where('name', 'ILIKE', "%$search%"))
                    // Use whereRaw to cast BigInt to Text for comparison
                    ->orWhereRaw('CAST("Venue_Reservation_ID" AS TEXT) ILIKE ?', ["%$search%"]);
            });
        }

        if ($status) {
            $roomQuery->where('status', $status);
            $venueQuery->where('status', $status);
        }

        // 5. Filter by Accommodation Type (The Dropdown choice)
        $rooms = collect();
        $venues = collect();

        if (!$accType || $accType === 'room') {
            $rooms = $roomQuery->get()->map(function ($item) {
                $item->display_type = 'room';
                $item->type = 'room'; // Helper for the Blade
                return $item;
            });
        }

        if (!$accType || $accType === 'venue') {
            $venues = $venueQuery->get()->map(function ($item) {
                $item->display_type = 'venue';
                $item->type = 'venue'; // Helper for the Blade
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
        $allForCounts = RoomReservation::select('status')->get()
            ->concat(VenueReservation::select('status')->get());

        return view('employee.reservations', compact('reservations', 'allForCounts'));
    }
    public function showGuests(\Illuminate\Http\Request $request)
    {
        $status = $request->input('status');
        $search = $request->input('search');
        $clientType = $request->input('client_type');
        $accommodationType = $request->input('accommodation_type');
        $dateFilter = $request->input('date');

        // 1. Initialize Queries
        $roomQuery = \App\Models\RoomReservation::with(['user', 'room']);
        $venueQuery = \App\Models\VenueReservation::with(['user', 'venue', 'foods']);

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
        if ($status) $roomQuery->where('status', $status);
        if ($clientType) {
            $roomQuery->whereHas('user', fn($q) => $q->where('usertype', $clientType));
        }
        if ($search) {
            $roomQuery->where(function ($q) use ($search) {
                $q->whereHas('user', fn($u) => $u->where('name', 'LIKE', "%{$search}%"))
                    ->orWhere('Room_Reservation_ID', 'LIKE', "%{$search}%")
                    ->orWhereHas('room', fn($r) => $r->where('room_number', 'LIKE', "%{$search}%"));
            });
        }

        // 4. Apply Venue Specific Filters
        if ($status) $venueQuery->where('status', $status);
        if ($clientType) {
            $venueQuery->whereHas('user', fn($q) => $q->where('usertype', $clientType));
        }
        if ($search) {
            $venueQuery->where(function ($q) use ($search) {
                $q->whereHas('user', fn($u) => $u->where('name', 'LIKE', "%{$search}%"))
                    ->orWhere('Venue_Reservation_ID', 'LIKE', "%{$search}%")
                    // Using 'name' for the Venue table search
                    ->orWhereHas('venue', fn($v) => $v->where('name', 'LIKE', "%{$search}%"));
            });
        }

        // 5. Execute and Map Data to standard keys for Blade
        $rooms = ($accommodationType === 'venue') ? collect() : $roomQuery->get()->map(function ($item) {
            $item->display_type = 'room';
            $item->type = 'room';
            $item->check_in = $item->Room_Reservation_Check_In_Time;
            $item->check_out = $item->Room_Reservation_Check_Out_Time;
            $item->total_amount = $item->Room_Reservation_Total_Price;
            $item->id = $item->Room_Reservation_ID;
            $item->base_room_price = $item->room->Room_Pricing ?? 0;
            $item->pax = $item->Room_Reservation_Quantity ?? 0;
            $item->additional_fees = $item->additional_fees ?? 0;
            $item->additional_fees_desc = $item->additional_fees_desc ?? '';

            return $item;
        });

        $venues = ($accommodationType === 'room') ? collect() : $venueQuery->get()->map(function ($item) {
            \Log::info('Venue reservation ID ' . $item->Venue_Reservation_ID . ' discount: ' . ($item->discount ?? 'null'));
            $item->display_type = 'venue';
            $item->type = 'venue';
            $item->check_in = $item->Venue_Reservation_Check_In_Time;
            $item->check_out = $item->Venue_Reservation_Check_Out_Time;
            $item->total_amount = $item->Venue_Reservation_Total_Price;
            $item->id = $item->Venue_Reservation_ID;
            // Using the 'pax' column confirmed in your Model fillable
            $item->pax = $item->pax ?? 0;
            $item->discount = $item->Venue_Reservation_Discount ?? 0;
            $item->additional_fees = $item->additional_fees ?? 0;
            $item->additional_fees_desc = $item->additional_fees_desc ?? '';
            $item->food_total = $item->foods->sum('pivot.total_price') ?? 0;
            return $item;
        });

        // 6. Final Merge and Sort
        $reservations = $rooms->concat($venues)->sortByDesc('created_at');

        // Counts for status cards (Fetches all to keep counts accurate even when filtering)
        $allForCounts = \App\Models\RoomReservation::all()->concat(\App\Models\VenueReservation::all());

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

                // 2. Reverse-engineer the TRUE original room cost (Price x Nights)
                $trueBookingCost = $currentTotal - $currentFees;

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

                // 5. Calculate new total strictly using the True Booking Cost
                $reservation->Room_Reservation_Total_Price = ($trueBookingCost + $totalExtra);
                $reservation->save();
            } elseif ($type === 'venue') {
                $reservation = VenueReservation::with(['venue', 'foods'])->findOrFail($resId);

                // 1. Get the current saved numbers before we overwrite them
                $currentTotal = (float) $reservation->Venue_Reservation_Total_Price;
                $currentFees = (float) $reservation->Venue_Reservation_Additional_Fees;
                $currentDiscount = (float) $reservation->Venue_Reservation_Discount;
                $foodTotal = (float) $reservation->foods->sum('pivot.total_price');

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
            return dd("Database Error: " . $e->getMessage());
        }
    }
    public function updateStatus(Request $request, $id)
    {
        $type = $request->query('type');
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
                $reservation = \App\Models\RoomReservation::with('user')->findOrFail($id);
            } else {
                $reservation = \App\Models\VenueReservation::with('user')->findOrFail($id);
            }
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return redirect()->back()->with('error', 'Reservation not found.');
        }

        $reservation->status = $newStatus;
        $reservation->save();

        if (in_array($newStatus, ['checked-out', 'completed'])) {
            if ($reservation->user && $reservation->user->email) {
                try {
                    $foodTotal = 0;
                    if ($type === 'venue') {
                        $foodTotal = \App\Models\FoodReservation::where('venue_reservation_id', $reservation->getKey())
                            ->sum('total_price');
                    }

                    \Illuminate\Support\Facades\Mail::to($reservation->user->email)
                        ->send(new \App\Mail\GuestCheckOutMail($reservation, $type, $foodTotal));
                } catch (\Exception $e) {
                    \Log::error("Email Error for Reservation #{$id}: " . $e->getMessage());
                }
            }
        }

        return redirect()->back()->with('success', 'Status updated to ' . ucfirst($newStatus) . ' successfully.');
    }

    public function showReservationsCalendar()
    {
        $roomRes = RoomReservation::with(['room', 'user'])->get()->map(function ($item) {
            return [
                'id' => $item->Room_Reservation_ID,
                'status' => strtolower($item->status),
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
                'status' => strtolower($item->status),
                'check_in' => \Carbon\Carbon::parse($item->Venue_Reservation_Check_In_Time)->format('Y-m-d'),
                'check_out' => \Carbon\Carbon::parse($item->Venue_Reservation_Check_Out_Time)->format('Y-m-d'),
                'user' => $item->user,
                'venue' => $item->venue,
                'label' => $item->venue ? $item->venue->Venue_Name : "Venue N/A",
                'type' => 'venue'
            ];
        });

        $reservations = $roomRes->concat($venueRes);
        $totalReservations = $reservations->count();

        $roomRevenue = RoomReservation::sum('Room_Reservation_Total_Price');
        $venueRevenue = VenueReservation::sum('Venue_Reservation_Total_Price');
        $totalRevenue = $roomRevenue + $venueRevenue;

        $today = Carbon::now()->format('Y-m-d');


        $activeRoomGuests = RoomReservation::where('status', 'checked-in')
            ->whereDate('Room_Reservation_Check_In_Time', '<=', $today)
            ->whereDate('Room_Reservation_Check_Out_Time', '>=', $today)
            ->sum('pax');

        $activeVenueGuests = VenueReservation::where('status', 'checked-in')
            ->whereDate('Venue_Reservation_Check_In_Time', '<=', $today)
            ->whereDate('Venue_Reservation_Check_Out_Time', '>=', $today)
            ->sum('pax');

        $activeGuests = $activeRoomGuests + $activeVenueGuests;

        $days = 30;

        $totalRooms = Room::count();
        $totalRoomNights = $totalRooms * $days;

        $roomNightsSold = RoomReservation::where('status', 'checked-in')
            ->whereBetween('Room_Reservation_Check_In_Time', [
                Carbon::now()->subDays($days),
                Carbon::now()
            ])
            ->count();

        $occupancyRate = $totalRoomNights > 0
            ? ($roomNightsSold / $totalRoomNights) * 100
            : 0;

        // CHECK-OUTS TODAY
        $roomCheckOutsToday = RoomReservation::with(['room', 'user'])
            ->where('status', 'checked-in')
            ->whereDate('Room_Reservation_Check_Out_Time', $today)
            ->get();
        $venueCheckOutsToday = VenueReservation::with(['venue', 'user'])
            ->where('status', 'checked-in')
            ->whereDate('Venue_Reservation_Check_Out_Time', $today)
            ->get();

        $checkOutsToday = $roomCheckOutsToday->concat($venueCheckOutsToday);

        $checkOutsTodayCount = $checkOutsToday->count();
        return view('employee.dashboard', compact(
            'reservations',
            'totalReservations',
            'totalRevenue',
            'activeGuests',
            'occupancyRate',
            'checkOutsToday',
            'checkOutsTodayCount'
        ));
    }
    public function cancel(Request $request, $id)
    {
        // 1. Ensure we get the type from the JSON body
        $type = $request->input('type');

        if (!$type) {
            return response()->json(['message' => 'Type is required'], 400);
        }

        // 2. Find the reservation
        if ($type === 'room') {
            $reservation = \App\Models\RoomReservation::with('room')->find($id);
            if ($reservation && $reservation->room) {
                // Set the room back to available
                $reservation->room->update(['status' => 'Available']);
            }
        } else {
            $reservation = \App\Models\VenueReservation::with('venue')->find($id);
            if ($reservation && $reservation->venue) {
                // Set the venue back to available
                $reservation->venue->update(['status' => 'Available']);
            }
        }

        // 3. Security Check: Ensure the user owns this reservation
        if (!$reservation) {
            return response()->json(['message' => 'Reservation not found'], 404);
        }

        if ($reservation->Client_ID !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // 4. Update and Save
        $reservation->status = 'cancelled';
        $reservation->save();

        return response()->json(['success' => true, 'message' => 'Success']);
    }
    public function storeReservation(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'accommodation_id' => 'required|integer',
            'type' => 'required|in:room,venue',
            'check_in' => 'required|date',
            'check_out' => 'required|date|after_or_equal:check_in',
            'pax' => 'required|integer|min:1',
        ]);

        $checkIn = \Carbon\Carbon::parse($request->check_in);
        $checkOut = \Carbon\Carbon::parse($request->check_out);
        $days = $checkIn->diffInDays($checkOut) ?: 1;

        if ($request->type === 'room') {
            $room = \App\Models\Room::findOrFail($request->accommodation_id);
            $totalAmount = ($room->Room_Pricing ?? $room->price) * $days;

            $reservation = \App\Models\RoomReservation::create([
                'room_id' => $request->accommodation_id,
                'Client_ID' => $request->user_id,
                'Room_Reservation_Date' => now(),
                'Room_Reservation_Check_In_Time' => $request->check_in,
                'Room_Reservation_Check_Out_Time' => $request->check_out,
                'pax' => $request->pax,
                'purpose' => $request->purpose,
                'Room_Reservation_Total_Price' => $totalAmount,
                'status' => 'pending',
            ]);

            $this->sendConfirmationEmail($reservation);
        } else {
            // Edit mode: reuse existing VenueReservation instead of creating a new one
            if ($request->filled('venue_reservation_id')) {
                $reservation = \App\Models\VenueReservation::findOrFail($request->venue_reservation_id);
            } else {
                $venue = \App\Models\Venue::findOrFail($request->accommodation_id);
                $basePrice = $venue->Venue_Pricing ?? $venue->external_price ?? $venue->price;
                $totalAmount = $basePrice * $days;

                $reservation = \App\Models\VenueReservation::create([
                    'venue_id' => $request->accommodation_id,
                    'Client_ID' => $request->user_id,
                    'Venue_Reservation_Date' => now(),
                    'Venue_Reservation_Check_In_Time' => $request->check_in,
                    'Venue_Reservation_Check_Out_Time' => $request->check_out,
                    'pax' => $request->pax,
                    'Venue_Reservation_Total_Price' => $totalAmount,
                    'status' => 'pending',
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
                            $price = $food->Food_Price ?? $food->food_price ?? 0;

                            \App\Models\FoodReservation::create([
                                'food_id'              => $foodId,
                                'venue_reservation_id' => $reservation->Venue_Reservation_ID,
                                'client_id'            => $reservation->Client_ID,
                                'serving_time'         => $date,
                                'meal_time'            => $mealType,
                                'total_price'          => $price * $request->pax,
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
        return true;
    }

    public function prepareEmployeeBooking(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'accommodation_id' => 'required|integer',
            'type' => 'required|in:room,venue',
            'check_in' => 'required|date',
            'check_out' => 'required|date|after_or_equal:check_in',
            'pax' => 'required|integer|min:1',
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
        $days     = $checkIn->diffInDays($checkOut) ?: 1;

        if ($request->type === 'room') {
            $reservation = \App\Models\RoomReservation::findOrFail($request->reservation_id);
            $room        = \App\Models\Room::findOrFail($request->accommodation_id);
            $totalAmount = ($room->Room_Pricing ?? $room->price ?? 0) * $days;

            $reservation->update([
                'Room_Reservation_Check_In_Time'  => $request->check_in,
                'Room_Reservation_Check_Out_Time' => $request->check_out,
                'pax'                             => $request->pax,
                'Room_Reservation_Total_Price'    => $totalAmount,
            ]);

            return redirect()
                ->route('employee.reservations')
                ->with('success', 'Room reservation updated successfully.');
        }

        if ($request->type === 'venue') {
            $reservation = \App\Models\VenueReservation::findOrFail($request->reservation_id);
            $venue       = \App\Models\Venue::findOrFail($request->accommodation_id);
            $basePrice   = $venue->Venue_Pricing ?? $venue->external_price ?? $venue->price ?? 0;
            $totalAmount = $basePrice * ($days + 1); // venues are day-inclusive

            $reservation->update([
                'Venue_Reservation_Check_In_Time'  => $request->check_in,
                'Venue_Reservation_Check_Out_Time' => $request->check_out,
                'pax'                              => $request->pax,
                'Venue_Reservation_Total_Price'    => $totalAmount,
            ]);

            // ── Snapshot existing food selections BEFORE deleting ──
            $previousFoodSelections = [];
            $previousFoodEnabled    = [];
            $previousMealEnabled    = [];

            // Load foods() relationship (already has withPivot for serving_time, meal_time)
            foreach ($reservation->foods as $food) {
                $date     = $food->pivot->serving_time ?? null;
                $mealType = $food->pivot->meal_time    ?? null;
                $category = $food->food_category       ?? null;
                $foodId   = $food->food_id ?? $food->id ?? null;

                if (!$date || !$mealType || !$category || !$foodId) continue;

                $previousFoodEnabled[$date]                        = '1';
                $previousMealEnabled[$date][$mealType]             = '1';
                $previousFoodSelections[$date][$mealType][$category] = (string) $foodId;
            }

            // Clear old food records so the employee can re-select on the food page
            \App\Models\FoodReservation::where(
                'venue_reservation_id',
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
        $foods = Food::orderBy('food_category')->get();

        return view('employee.create_food_reservation', compact('bookingData', 'foods'));
    }

    public function showSOA($clientId)
    {
        $client = User::findOrFail($clientId);

        $roomReservations = RoomReservation::with('room')
            ->where('Client_ID', $clientId)
            ->where('status', 'checked-in')
            ->get();

        $venueReservations = VenueReservation::with('venue')
            ->where('Client_ID', $clientId)
            ->where('status', 'checked-in')
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
            $baseAmount = (float) ($r->Room_Reservation_Total_Price ?? 0) - $additionalFees;

            $reservations->push([
                'type' => 'room',
                'id' => $r->Room_Reservation_ID,
                'name' => 'Room ' . ($r->room->room_number ?? 'Error'),
                'check_in' => $checkIn->format('m/d/Y'),
                'check_out' => $checkOut->format('m/d/Y'),
                'pax' => $r->pax,
                'days' => $days,
                'base_price' => $baseAmount,
                'total_price' => $r->Room_Reservation_Total_Price ?? 0,
                'additional_fees' => $additionalFees,
                'discount' => 0,
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
                'name' => 'Venue ' . ($v->venue->name ?? 'Error'),
                'check_in' => $checkIn->format('m/d/Y'),
                'check_out' => $checkOut->format('m/d/Y'),
                'pax' => $v->pax,
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

        $client = User::findOrFail($clientId);

        $roomReservations = RoomReservation::with('room')
            ->where('Client_ID', $clientId)
            ->where('status', 'checked-in')
            ->when(!empty($roomIds), function ($query) use ($roomIds) {
                $query->whereIn('Room_Reservation_ID', $roomIds);
            }, function ($query) {
                $query->whereRaw('1 = 0');
            })
            ->get();

        $venueReservations = VenueReservation::with('venue')
            ->where('Client_ID', $clientId)
            ->where('status', 'checked-in')
            ->when(!empty($venueIds), function ($query) use ($venueIds) {
                $query->whereIn('Venue_Reservation_ID', $venueIds);
            }, function ($query) {
                $query->whereRaw('1 = 0');
            })
            ->get();

        $reservations = collect();

        foreach ($roomReservations as $r) {
            $checkIn = Carbon::parse($r->Room_Reservation_Check_In_Time);
            $checkOut = Carbon::parse($r->Room_Reservation_Check_Out_Time);
            $days = $checkIn->diffInDays($checkOut) ?: 1;

            $additionalFees = (float) ($r->Room_Reservation_Additional_Fees ?? 0);
            $baseAmount = (float) ($r->Room_Reservation_Total_Price ?? 0) - $additionalFees;

            $reservations->push([
                'date' => $checkIn->format('d/m/Y'),
                'name' => 'Room ' . ($r->room->Room_Number ?? $r->room->room_number ?? 'Room'),
                'pax' => $r->pax,
                'days' => $days,
                'rate' => $days > 0 ? $baseAmount / $days : $baseAmount,
                'amount' => $baseAmount,
                'is_subitem' => false,
            ]);

            $rawItems = json_decode($r->Room_Reservation_Additional_Fees_Desc ?? '[]', true) ?? [];

            foreach ($rawItems as $item) {
                $parts = explode(':', $item);

                $desc = $parts[0] ?? '';
                $qty = (int) ($parts[1] ?? 1);
                $rate = (float) ($parts[2] ?? 0);
                $chargeDate = !empty($parts[3]) ? Carbon::parse($parts[3])->format('d/m/Y') : '';
                $lineTotal = $qty * $rate;

                $reservations->push([
                    'date' => $chargeDate,
                    'name' => '+ ' . $desc,
                    'pax' => $qty,
                    'days' => '',
                    'rate' => $rate,
                    'amount' => $lineTotal,
                    'is_subitem' => true,
                ]);
            }
        }

        foreach ($venueReservations as $v) {
            $checkIn = Carbon::parse($v->Venue_Reservation_Check_In_Time);
            $checkOut = Carbon::parse($v->Venue_Reservation_Check_Out_Time);
            $days = $checkIn->diffInDays($checkOut) ?: 1;

            $additionalFees = (float) ($v->Venue_Reservation_Additional_Fees ?? 0);
            $discount = (float) ($v->Venue_Reservation_Discount ?? 0);
            $baseAmount = (float) ($v->Venue_Reservation_Total_Price ?? 0) - $additionalFees + $discount;

            $reservations->push([
                'date' => $checkIn->format('d/m/Y'),
                'name' => 'Venue ' . ($v->venue->Venue_Name ?? $v->venue->name ?? 'Venue'),
                'pax' => $v->pax,
                'days' => $days,
                'rate' => $days > 0 ? $baseAmount / $days : $baseAmount,
                'amount' => $baseAmount,
                'is_subitem' => false,
            ]);

            $rawItems = json_decode($v->Venue_Reservation_Additional_Fees_Desc ?? '[]', true) ?? [];

            foreach ($rawItems as $item) {
                $parts = explode(':', $item);

                $desc = $parts[0] ?? '';
                $qty = (int) ($parts[1] ?? 1);
                $rate = (float) ($parts[2] ?? 0);
                $chargeDate = !empty($parts[3]) ? Carbon::parse($parts[3])->format('d/m/Y') : '';
                $lineTotal = $qty * $rate;

                $reservations->push([
                    'date' => $chargeDate,
                    'name' => '+ ' . $desc,
                    'pax' => $qty,
                    'days' => '',
                    'rate' => $rate,
                    'amount' => $lineTotal,
                    'is_subitem' => true,
                ]);
            }

            if ($discount > 0) {
                $reservations->push([
                    'date' => '',
                    'name' => '- Discount',
                    'pax' => 1,
                    'days' => '',
                    'rate' => $discount,
                    'amount' => $discount,
                    'is_subitem' => true,
                    'is_discount' => true,
                ]);
            }
        }

        /*
                ===============================
                LOAD EXCEL TEMPLATE
                ===============================
                */

        $templatePath = resource_path('templates/SOA_Template_Final.xlsx');

        if (!file_exists($templatePath)) {
            abort(500, 'SOA template not found.');
        }

        $spreadsheet = IOFactory::load($templatePath);
        $sheet = $spreadsheet->getActiveSheet();

        /*
                ===============================
                HEADER INFORMATION
                ===============================
                */

        $sheet->setCellValue('A15', 'Date: ' . now()->format('d/m/Y'));
        $sheet->setCellValue('A17', 'To:');
        $sheet->setCellValue('A18', $client->name);

        /*
                ===============================
                INSERT RESERVATIONS
                TABLE STARTS AT ROW 25
                ===============================
                */

        $startRow = 25;
        $currentRow = $startRow;

        $startRow = 25;
        $currentRow = $startRow;

        $subtotal = 0;
        $totalAdditionalFees = 0;
        $totalDiscounts = 0;
        $lastExportDate = null;

        foreach ($reservations as $r) {
            $date = $r['date'] ?? '';
            $name = $r['name'] ?? '';
            $qty = $r['pax'] ?? '';
            $days = $r['days'] ?? '';
            $rate = $r['rate'] ?? 0;
            $amount = (float) ($r['amount'] ?? 0);

            // Only print the date when it changes (dedup same-date rows)
            $printDate = ($date !== '' && $date !== $lastExportDate) ? $date : '';
            if ($date !== '') $lastExportDate = $date;

            $sheet->setCellValue("A{$currentRow}", $printDate);
            $sheet->setCellValue("B{$currentRow}", $name);
            $sheet->setCellValue("C{$currentRow}", $qty);
            $sheet->setCellValue("D{$currentRow}", $days !== '' ? $days . ' day' : '');
            $sheet->setCellValue("E{$currentRow}", $rate);
            $sheet->setCellValue("F{$currentRow}", $amount);

            if (($r['is_subitem'] ?? false) === true) {
                if (($r['is_discount'] ?? false) === true) {
                    $totalDiscounts += $amount;
                } else {
                    $totalAdditionalFees += $amount;
                }
            } else {
                $subtotal += $amount;
            }

            $currentRow++;
        }

        /*
                ===============================
                SUMMARY BOX
                Column E = labels
                Column F = values
                ===============================
                */

        $sheet->setCellValue('F15', $subtotal);
        $sheet->setCellValue('F16', $totalAdditionalFees);
        $sheet->setCellValue('F17', $totalDiscounts);
        $sheet->setCellValue('F18', $subtotal + $totalAdditionalFees - $totalDiscounts);
        /*
                ===============================
                CURRENCY FORMAT
                ===============================
                */

        for ($row = $startRow; $row < $currentRow; $row++) {
            $sheet->getStyle("E{$row}")
                ->getNumberFormat()
                ->setFormatCode('"₱"#,##0.00');

            $sheet->getStyle("F{$row}")
                ->getNumberFormat()
                ->setFormatCode('"₱"#,##0.00');
        }

        $sheet->getStyle('F15:F18')
            ->getNumberFormat()
            ->setFormatCode('"₱"#,##0.00');

        /*
                ===============================
                EXPORT FILE
                ===============================
                */

        $fileName = 'SOA_' . str_replace(' ', '_', $client->name) . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), 'soa');

        $writer = new Xlsx($spreadsheet);
        $writer->save($tempFile);

        return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);
    }


    public function fetchUpdatedCalendarData()
    {
        $roomRes = RoomReservation::with(['room', 'user'])->get()->map(function ($item) {
            return [
                'id' => $item->Room_Reservation_ID,
                'status' => strtolower($item->status),
                'check_in' => Carbon::parse($item->Room_Reservation_Check_In_Time)->format('Y-m-d'),
                'check_out' => Carbon::parse($item->Room_Reservation_Check_Out_Time)->format('Y-m-d'),
                'user' => $item->user ? [
                    'name' => $item->user->name
                ] : null,
                'label' => $item->room ? 'Room ' . $item->room->Room_Number : 'Room N/A',
                'type' => 'room',
            ];
        });

        $venueRes = VenueReservation::with(['venue', 'user'])->get()->map(function ($item) {
            return [
                'id' => $item->Venue_Reservation_ID,
                'status' => strtolower($item->status),
                'check_in' => Carbon::parse($item->Venue_Reservation_Check_In_Time)->format('Y-m-d'),
                'check_out' => Carbon::parse($item->Venue_Reservation_Check_Out_Time)->format('Y-m-d'),
                'user' => $item->user ? [
                    'name' => $item->user->name
                ] : null,
                'label' => $item->venue ? $item->venue->Venue_Name : 'Venue N/A',
                'type' => 'venue',
            ];
        });

        $reservations = $roomRes->concat($venueRes)->values();

        $totalReservations = $reservations->count();

        $roomRevenue = RoomReservation::sum('Room_Reservation_Total_Price');
        $venueRevenue = VenueReservation::sum('Venue_Reservation_Total_Price');
        $totalRevenue = $roomRevenue + $venueRevenue;

        $today = Carbon::today();

        $activeRoomGuests = RoomReservation::where('status', 'checked-in')
            ->whereDate('Room_Reservation_Check_In_Time', '<=', $today)
            ->whereDate('Room_Reservation_Check_Out_Time', '>=', $today)
            ->sum('pax');

        $activeVenueGuests = VenueReservation::where('status', 'checked-in')
            ->whereDate('Venue_Reservation_Check_In_Time', '<=', $today)
            ->whereDate('Venue_Reservation_Check_Out_Time', '>=', $today)
            ->sum('pax');

        $activeGuests = $activeRoomGuests + $activeVenueGuests;

        $days = 30;
        $totalRooms = Room::count();
        $totalRoomNights = $totalRooms * $days;

        $roomNightsSold = RoomReservation::where('status', 'checked-in')
            ->whereBetween('Room_Reservation_Check_In_Time', [
                Carbon::now()->subDays($days),
                Carbon::now()
            ])
            ->count();

        $occupancyRate = $totalRoomNights > 0
            ? ($roomNightsSold / $totalRoomNights) * 100
            : 0;

        $checkOutsTodayRooms = RoomReservation::whereDate('Room_Reservation_Check_Out_Time', $today)
            ->where('status', 'checked-out')
            ->count();

        $checkOutsTodayVenues = VenueReservation::whereDate('Venue_Reservation_Check_Out_Time', $today)
            ->where('status', 'checked-out')
            ->count();

        $checkOutsTodayCount = $checkOutsTodayRooms + $checkOutsTodayVenues;

        return response()->json([
            'reservations' => $reservations,
            'stats' => [
                'totalReservations' => $totalReservations,
                'totalRevenue' => $totalRevenue,
                'activeGuests' => $activeGuests,
                'occupancyRate' => round($occupancyRate, 1),
                'checkOutsTodayCount' => $checkOutsTodayCount,
            ]
        ]);
    }
}
