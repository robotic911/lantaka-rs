<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RoomReservation;
use App\Models\VenueReservation;
use App\Models\FoodReservation;
use App\Models\Room;
use App\Models\Venue;
use App\Models\Food;
use App\Models\FoodSet;
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
use App\Mail\CancellationApprovedMail;
use App\Mail\CancellationRejectedMail;
use App\Mail\CancellationRequestedMail;
use App\Mail\ChangeRequestProcessedMail;
use App\Mail\ChangeRequestSubmittedMail;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
// CancellationRequest model removed — cancellation data lives on the reservation rows


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
                    $price = $model->Room_Internal_Price ?? 0;
                }else{
                    $price = $model->Room_External_Price ?? 0;
                }
                $name = "Room " . ($model->Room_Number ?? '');
                $img = $model->Room_Image ?? null;
            } else {
                $model = Venue::find($item['accommodation_id']);

                if (!$model) {
                    continue;
                }

                if($account->Account_Type == 'Internal'){
                    $price = $model->Venue_Internal_Price ?? 0;
                }else{
                    $price = $model->Venue_External_Price ?? 0;
                }
                $name = $model->Venue_Name ?? 'Venue';
                $img = $model->Venue_Image ?? null;
            }
            $accommodationTotal = (float) $price * $days;
            
            $foodTotal = 0;

            $selectedFoods    = collect();
            $selectedSets     = collect();
            $foodSelections   = $item['food_selections']    ?? [];
            $foodEnabled      = $item['food_enabled']       ?? [];
            $mealEnabled      = $item['meal_enabled']       ?? [];
            $foodSetSelection = $item['food_set_selection'] ?? [];
            $mealMode         = $item['meal_mode']          ?? [];

            if ($item['type'] === 'venue' && !empty($foodSelections)) {
                $allFoodIds = [];

                // Recursively collect all numeric food IDs (handles scalar fields like
                // rice/viand1/viand2/drink as well as array fields like extra_viands[]/desserts[]).
                $collectFoodIds = function ($data) use (&$collectFoodIds): array {
                    $ids = [];
                    if (is_array($data)) {
                        foreach ($data as $k => $v) {
                            if ($k === 'drink_choice') continue; // legacy text value, not a Food_ID
                            if ($k === '_tier') continue;        // buffet tier price, not a Food_ID
                            $ids = array_merge($ids, $collectFoodIds($v));
                        }
                    } elseif (!empty($data) && is_numeric($data)) {
                        $ids[] = (int) $data;
                    }
                    return $ids;
                };

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

                        $allFoodIds = array_merge($allFoodIds, $collectFoodIds($categories));
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

                            // Buffet meal: flat-rate per pax (350 or 380), not individual food prices
                            if (($mealMode[$date][$mealType] ?? '') === 'buffet') {
                                $tier = (int)($categories['_tier'] ?? 350);
                                $foodTotal += $tier * ($item['pax'] ?? 1);
                                continue;
                            }

                            // Use recursive collector so extra_viands[], desserts[] arrays
                            // and the new drink field are all counted in foodTotal.
                            foreach ($collectFoodIds($categories) as $foodId) {
                                $food = $selectedFoods->get($foodId);
                                if (!$food) continue;
                                $foodTotal += ($food->Food_Price ?? 0) * ($item['pax'] ?? 1);
                            }
                        }
                    }
                }
            }

            // Fetch food set models so checkout can display set names AND sum their prices
            if ($item['type'] === 'venue' && !empty($foodSetSelection)) {
                $allSetIds = [];
                foreach ($foodSetSelection as $dateSets) {
                    foreach ((array) $dateSets as $setIdOrIds) {
                        if (is_array($setIdOrIds)) {
                            foreach ($setIdOrIds as $sid) {
                                if (!empty($sid)) $allSetIds[] = (int) $sid;
                            }
                        } else {
                            if (!empty($setIdOrIds)) $allSetIds[] = (int) $setIdOrIds;
                        }
                    }
                }
                $allSetIds = array_values(array_unique($allSetIds));
                if (!empty($allSetIds)) {
                    $selectedSets = FoodSet::whereIn('Food_Set_ID', $allSetIds)->get()->keyBy('Food_Set_ID');

                    // Add set prices to the food total
                    foreach ($foodSetSelection as $date => $meals) {
                        if (($foodEnabled[$date] ?? '1') != '1') continue;
                        foreach ((array) $meals as $mealKey => $setIdOrIds) {
                            $ids = is_array($setIdOrIds) ? $setIdOrIds : [$setIdOrIds];
                            foreach ($ids as $sid) {
                                if (empty($sid)) continue;
                                $set = $selectedSets->get((int) $sid);
                                if ($set) {
                                    $foodTotal += ($set->Food_Set_Price ?? 0) * ($item['pax'] ?? 1);
                                }
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
                'notes' => $item['notes'] ?? '',
                'check_in' => $checkIn->format('F d, Y'),
                'check_out' => $checkOut->format('F d, Y'),
                'check_in_raw' => $checkIn->toDateString(),
                'check_out_raw' => $checkOut->toDateString(),
                'days' => $days > 0 ? $days : 1,
                'total' => $itemTotal,

                // new grouped structure
                'food_enabled'       => $foodEnabled,
                'meal_enabled'       => $mealEnabled,
                'food_selections'    => $foodSelections,
                'food_set_selection' => $foodSetSelection,
                'meal_mode'          => $mealMode,

                // selected food/set models
                'selected_foods' => $selectedFoods->values(),
                'selected_sets'  => $selectedSets->values(),
                'base_total'     => $accommodationTotal,
                'food_total'     => $foodTotal,
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

        // Venue: stash food selections + booking meta in session, then redirect to venue view
        // so the user can optionally change dates before proceeding to food selection.
        if ($type === 'venue') {
            session([
                'edit_food_selections'    => $item['food_selections']    ?? [],
                'edit_food_enabled'       => $item['food_enabled']       ?? [],
                'edit_meal_enabled'       => $item['meal_enabled']       ?? [],
                'edit_set_selections'     => $item['food_set_selection'] ?? [],
                'edit_meal_mode'          => $item['meal_mode']          ?? [],
            ]);

            // Redirect back to the venue detail page with dates pre-filled so the user
            // can reschedule if needed, then re-proceed through the food selection step.
            return redirect()->to(
                route('client.show', ['category' => 'venue', 'id' => $id])
                    . '?' . http_build_query([
                        'check_in'  => $item['check_in'],
                        'check_out' => $item['check_out'],
                        'pax'       => $item['pax'] ?? 1,
                        'purpose'   => $item['purpose'] ?? '',
                        'edit'      => '1',
                    ])
            );
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
                            'Room_Reservation_Notes' => $item['notes'] ?? null,
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
                            'Venue_Reservation_Notes' => $item['notes'] ?? null,
                            'Venue_Reservation_Total_Price' => $item['total_amount'],
                            'Venue_Reservation_Status' => 'pending',
                        ]);

                        $foodSelections   = $item['food_selections']    ?? [];
                        $foodSetSelection = $item['food_set_selection'] ?? [];
                        $foodEnabledMap   = $item['food_enabled']       ?? [];
                        $mealModeMap      = $item['meal_mode']          ?? [];

                        // ─────────────────────────────────────────────────────────────
                        // STEP A – Build a per-date skip-list of mealKeys that belong
                        // to set selections.  Their customisation values (rice, drinks,
                        // dessert, fruit) are embedded directly in the Food_Set_ID text
                        // column, so we must NOT also create separate individual rows.
                        //
                        //  Spiritual sets:  food_set_selection[date][mealKey] = "setId"
                        //                   → skip mealKey (e.g. 'breakfast')
                        //  General sets:    food_set_selection[date][mealKey][] = setId
                        //                   → skip "gen_{setId}" (the customisation slot)
                        // ─────────────────────────────────────────────────────────────
                        $skipMealKeys = [];   // [$date][$mealKey] = true

                        foreach ($foodSetSelection as $_d => $_meals) {
                            foreach ((array) $_meals as $_mk => $_ids) {
                                $isArray = is_array($_ids);
                                $rawIds  = $isArray ? $_ids : [$_ids];

                                foreach ($rawIds as $_id) {
                                    if (!empty($_id)) {
                                        // General set: skip the gen_XX customisation slot
                                        $skipMealKeys[$_d]["gen_{$_id}"] = true;
                                    }
                                }

                                // Spiritual set: the mealKey itself is a set meal
                                if (!$isArray && !empty($_ids)) {
                                    $skipMealKeys[$_d][$_mk] = true;
                                }
                            }
                        }

                        // ─────────────────────────────────────────────────────────────
                        // STEP B – Individual food selections
                        // Only save mealKeys that are NOT in the skip-list (i.e. pure
                        // individual-order meals and snack items).
                        // ─────────────────────────────────────────────────────────────
                        if (!empty($foodSelections)) {
                            /**
                             * Recursively extract valid numeric food IDs from a meal's
                             * selection data.  Skips non-numeric strings (drink_choice
                             * holds "softdrinks"/"juice", not a Food_ID).
                             */
                            $extractFoodIds = function ($data) use (&$extractFoodIds) {
                                $ids = [];
                                if (is_array($data)) {
                                    foreach ($data as $k => $v) {
                                        if ($k === 'drink_choice') continue; // text, not an ID
                                        if ($k === '_tier') continue;        // buffet tier price, not a Food_ID
                                        $ids = array_merge($ids, $extractFoodIds($v));
                                    }
                                } elseif (is_numeric($data) && !empty($data)) {
                                    $ids[] = (int) $data;
                                }
                                return $ids;
                            };

                            foreach ($foodSelections as $date => $meals) {
                                foreach ($meals as $mealType => $mealData) {
                                    if (empty($mealData)) continue;

                                    // Skip set-meal customisation slots
                                    if (!empty($skipMealKeys[$date][$mealType])) continue;

                                    $foodIds = $extractFoodIds($mealData);

                                    // ── Buffet meal: flat-rate pricing ────────────────────────
                                    if (($mealModeMap[$date][$mealType] ?? '') === 'buffet') {
                                        $tier = (int)(is_array($mealData) ? ($mealData['_tier'] ?? 350) : 350);
                                        $pax  = (int)($item['pax'] ?? 1);

                                        // Individual food items stored at ₱0 (display only)
                                        foreach ($foodIds as $foodId) {
                                            $food = Food::find($foodId);
                                            if ($food) {
                                                FoodReservation::create([
                                                    'Food_ID'                       => $foodId,
                                                    'Venue_Reservation_ID'          => $reservation->Venue_Reservation_ID,
                                                    'Client_ID'                     => Auth::id(),
                                                    'Food_Reservation_Serving_Date' => $date,
                                                    'Food_Reservation_Meal_time'    => $mealType,
                                                    'Food_Reservation_Total_Price'  => 0,
                                                ]);
                                            }
                                        }

                                        // One price record encodes the buffet tier and holds the actual price
                                        FoodReservation::create([
                                            'Food_ID'                       => null,
                                            'Food_Set_ID'                   => "buffet:{$tier}",
                                            'Venue_Reservation_ID'          => $reservation->Venue_Reservation_ID,
                                            'Client_ID'                     => Auth::id(),
                                            'Food_Reservation_Serving_Date' => $date,
                                            'Food_Reservation_Meal_time'    => $mealType,
                                            'Food_Reservation_Total_Price'  => $tier * $pax,
                                        ]);
                                        continue;
                                    }

                                    // ── Normal meal: individual food pricing ─────────────────
                                    foreach ($foodIds as $foodId) {
                                        $food = Food::find($foodId);
                                        if ($food) {
                                            FoodReservation::create([
                                                'Food_ID'                       => $foodId,
                                                'Venue_Reservation_ID'          => $reservation->Venue_Reservation_ID,
                                                'Client_ID'                     => Auth::id(),
                                                'Food_Reservation_Serving_Date' => $date,
                                                'Food_Reservation_Meal_time'    => $mealType,
                                                'Food_Reservation_Total_Price'  => ($food->Food_Price ?? 0) * (int) ($item['pax'] ?? 1),
                                            ]);
                                        }
                                    }
                                }
                            }
                        }

                        // ─────────────────────────────────────────────────────────────
                        // STEP C – Food SET selections
                        // One FoodReservation row per set.  Food_Set_ID is stored as a
                        // TEXT string that encodes both the set ID and the user's chosen
                        // customisations:
                        //
                        //   "setId",["riceId","drinksId","dessertId","fruitId"]
                        //
                        // Positions:  0=rice  1=drinks  2=dessert  3=fruit
                        //
                        // Spiritual:  customisations come from food_selections[date][mealKey]
                        // General:    customisations come from food_selections[date][gen_setId]
                        //             drink is stored as text → resolved to Food_ID here.
                        // ─────────────────────────────────────────────────────────────
                        if (!empty($foodSetSelection)) {
                            foreach ($foodSetSelection as $date => $meals) {
                                if (($foodEnabledMap[$date] ?? '1') != '1') continue;

                                foreach ((array) $meals as $mealKey => $setIdOrIds) {
                                    $isGeneralSet = is_array($setIdOrIds);
                                    $setIds       = $isGeneralSet ? $setIdOrIds : [$setIdOrIds];

                                    foreach ($setIds as $setId) {
                                        if (empty($setId)) continue;

                                        $set = FoodSet::find((int) $setId);
                                        if (!$set) continue;

                                        // ── Gather customisation IDs ───────────────────────
                                        if ($isGeneralSet) {
                                            // General event: customisations stored under gen_setId slot
                                            $genKey    = "gen_{$setId}";
                                            $genSel    = $foodSelections[$date][$genKey] ?? [];
                                            $riceId    = (string) ($genSel['rice']    ?? '');
                                            $dessertId = (string) ($genSel['dessert'] ?? '');
                                            $fruitId   = '';   // not applicable for general events

                                            // Drink is submitted as Food_ID from searchable-select (with legacy text fallback)
                                            $drinkVal = $genSel['drink'] ?? ($genSel['drink_choice'] ?? '');
                                            if (is_numeric($drinkVal) && !empty($drinkVal)) {
                                                $drinksId = (string) $drinkVal;
                                            } elseif (!empty($drinkVal)) {
                                                $drinkTxt  = strtolower(trim((string) $drinkVal));
                                                $drinkFood = Food::where(function ($q) use ($drinkTxt) {
                                                    $q->where('Food_Name', 'ILIKE', $drinkTxt . '%')
                                                      ->orWhere('Food_Name', 'ILIKE', '%' . $drinkTxt . '%');
                                                })->first();
                                                $drinksId = $drinkFood ? (string) $drinkFood->Food_ID : '';
                                            } else {
                                                $drinksId = '';
                                            }
                                        } else {
                                            // Spiritual event: customisations stored under the meal key
                                            $mealSel   = $foodSelections[$date][$mealKey] ?? [];
                                            $riceId    = (string) ($mealSel['rice_type']  ?? '');
                                            $drinksId  = (string) ($mealKey === 'breakfast'
                                                ? ($mealSel['hot_drink']   ?? '')
                                                : ($mealSel['softdrinks']  ?? ''));
                                            $dessertId = (string) ($mealSel['dessert']    ?? '');
                                            $fruitId   = (string) ($mealSel['fruits']     ?? '');
                                        }

                                        // ── Build text format ──────────────────────────────
                                        $customIds     = [$riceId, $drinksId, $dessertId, $fruitId];
                                        $foodSetIdText = '"' . $setId . '",' . json_encode($customIds);

                                        // ── Persist ───────────────────────────────────────
                                        FoodReservation::create([
                                            'Food_ID'                       => null,
                                            'Food_Set_ID'                   => $foodSetIdText,
                                            'Venue_Reservation_ID'          => $reservation->Venue_Reservation_ID,
                                            'Client_ID'                     => Auth::id(),
                                            'Food_Reservation_Serving_Date' => $date,
                                            'Food_Reservation_Meal_time'    => $mealKey,
                                            'Food_Reservation_Total_Price'  => (float) ($set->Food_Set_Price ?? 0) * (int) ($item['pax'] ?? 1),
                                        ]);
                                    }
                                }
                            }
                        }

                        $savedReservations[] = $reservation;
                    }
                }
            });

            // Send one confirmation email per reservation so the mail
            // class always receives a single model, never an array.
            $clientEmail = auth()->user()?->Account_Email;
            if ($clientEmail) {
                foreach ($savedReservations as $savedRes) {
                    try {
                        Mail::to($clientEmail)->send(
                            new \App\Mail\ReservationConfirmationMail($savedRes)
                        );
                    } catch (\Exception $e) {
                        \Log::error('ReservationConfirmationMail failed: ' . $e->getMessage());
                    }
                }
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
            return back()->with('error', 'Something went wrong while processing your reservation. Please try again or contact support.');
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
        $roomQuery  = RoomReservation::with(['room', 'user']);
        $venueQuery = VenueReservation::with(['venue', 'user', 'foods', 'foodSetReservations']);

        if ($user->Account_Role === 'client') {
            $roomQuery->where('Client_ID',  $user->Account_ID);
            $venueQuery->where('Client_ID', $user->Account_ID);
        }
          
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
                $query->whereHas('user', fn($q) => $q->where('Account_Type', $clientType));
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
        $venueQuery = VenueReservation::with(['user', 'venue', 'foods', 'foodSetReservations']);

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

        // Virtual filters — not real status column values
        if ($status === 'cancel_requested') {
            $roomQuery->where('cancellation_status', 'pending');
            $venueQuery->where('cancellation_status', 'pending');
        } elseif ($status === 'change_requested') {
            $roomQuery->where('change_request_status', 'pending');
            $venueQuery->where('change_request_status', 'pending');
        } elseif ($status) {
            $roomQuery->where('Room_Reservation_Status', $status);
            $venueQuery->where('Venue_Reservation_Status', $status);
            // Exclude reservations that have a pending cancel/change request —
            // those already appear under their own dedicated tabs.
            if ($status === 'pending') {
                $roomQuery->where(function ($q) {
                    $q->where('cancellation_status', '!=', 'pending')
                      ->orWhereNull('cancellation_status');
                })->where(function ($q) {
                    $q->where('change_request_status', '!=', 'pending')
                      ->orWhereNull('change_request_status');
                });
                $venueQuery->where(function ($q) {
                    $q->where('cancellation_status', '!=', 'pending')
                      ->orWhereNull('cancellation_status');
                })->where(function ($q) {
                    $q->where('change_request_status', '!=', 'pending')
                      ->orWhereNull('change_request_status');
                });
            }
        }

        // 5. Filter by Accommodation Type (The Dropdown choice)
        $rooms = collect();
        $venues = collect();

        if (!$accType || $accType === 'room') {
            $rooms = $roomQuery->get()->map(function ($item) {
                $item->display_type = 'room';
                $item->type = 'room';
                $item->status = $item->Room_Reservation_Status;
                return $item;
            });
        }

        if (!$accType || $accType === 'venue') {
            $venues = $venueQuery->get()->map(function ($item) {
                $item->display_type = 'venue';
                $item->type = 'venue';
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

        // Status counts for the regular status cards.
        // Reservations with a pending cancel/change request are counted under their own tabs,
        // so exclude them from the main status counts (particularly 'pending').
        $allForCounts = RoomReservation::select('Room_Reservation_Status as status')
                ->where(function ($q) {
                    $q->where('cancellation_status', '!=', 'pending')
                      ->orWhereNull('cancellation_status');
                })->where(function ($q) {
                    $q->where('change_request_status', '!=', 'pending')
                      ->orWhereNull('change_request_status');
                })->get()
            ->concat(
                VenueReservation::select('Venue_Reservation_Status as status')
                ->where(function ($q) {
                    $q->where('cancellation_status', '!=', 'pending')
                      ->orWhereNull('cancellation_status');
                })->where(function ($q) {
                    $q->where('change_request_status', '!=', 'pending')
                      ->orWhereNull('change_request_status');
                })->get()
            );

        // Count of pending cancellation requests (shown as its own priority card)
        $cancelRequestedCount = RoomReservation::where('cancellation_status', 'pending')->count()
                              + VenueReservation::where('cancellation_status', 'pending')->count();

        // Count of pending change requests (shown as its own priority card)
        $changeRequestedCount = RoomReservation::where('change_request_status', 'pending')->count()
                              + VenueReservation::where('change_request_status', 'pending')->count();

        return view('employee.reservations', compact('reservations', 'allForCounts', 'cancelRequestedCount', 'changeRequestedCount'));
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

            // Map to the same standard keys the guest blade and JS expect
            $room->display_type      = 'room';
            $room->type              = 'room';
            $room->id                = $room->Room_Reservation_ID;
            $room->status            = $room->Room_Reservation_Status;
            $room->check_in          = $room->Room_Reservation_Check_In_Time;
            $room->check_out         = $room->Room_Reservation_Check_Out_Time;
            $room->total_amount      = $room->Room_Reservation_Total_Price;
            $room->pax               = $room->Room_Reservation_Pax ?? 0;
            $room->discount          = $room->Room_Reservation_Discount ?? 0;
            $room->additional_fees   = $room->Room_Reservation_Additional_Fees ?? 0;
            $room->additional_fees_desc = $room->Room_Reservation_Additional_Fees_Desc ?? '';
            $room->base_room_price   = ($room->user && $room->user->Account_Type === 'Internal')
                ? ($room->room?->Room_Internal_Price ?? 0)
                : ($room->room?->Room_External_Price ?? 0);
            $room->food_total        = 0;

            $rooms = collect([$room]);

        } elseif ($type === 'venue') {
            $venue = VenueReservation::with(['user', 'venue', 'foods', 'foodSetReservations'])
                ->where('Venue_Reservation_ID', $id)
                ->firstOrFail();

            // Map to the same standard keys the guest blade and JS expect
            $venue->display_type      = 'venue';
            $venue->type              = 'venue';
            $venue->id                = $venue->Venue_Reservation_ID;
            $venue->status            = $venue->Venue_Reservation_Status;
            $venue->check_in          = $venue->Venue_Reservation_Check_In_Time;
            $venue->check_out         = $venue->Venue_Reservation_Check_Out_Time;
            $venue->total_amount      = $venue->Venue_Reservation_Total_Price;
            $venue->pax               = $venue->Venue_Reservation_Pax ?? 0;
            $venue->discount          = $venue->Venue_Reservation_Discount ?? 0;
            $venue->additional_fees   = $venue->Venue_Reservation_Additional_Fees ?? 0;
            $venue->additional_fees_desc = $venue->Venue_Reservation_Additional_Fees_Desc ?? '';
            $venue->food_total        = ($venue->foods->sum('pivot.Food_Reservation_Total_Price') ?? 0)
                + ($venue->foodSetReservations->sum('Food_Reservation_Total_Price') ?? 0);

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
        $venueQuery = VenueReservation::with(['user', 'venue', 'foods', 'foodSetReservations']);

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
                    ->orWhereHas('venue', fn($v) => $v->where('Venue_Name', 'ILIKE', "%{$search}%"));
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
                ? ($item->room?->Room_Internal_Price ?? 0)
                : ($item->room?->Room_External_Price ?? 0);
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
            $item->food_total = ($item->foods->sum('pivot.Food_Reservation_Total_Price') ?? 0)
                + ($item->foodSetReservations->sum('Food_Reservation_Total_Price') ?? 0);
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
                $reservation->Room_Reservation_Notes = $request->input('notes') ?? $reservation->Room_Reservation_Notes;

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
                $reservation->Venue_Reservation_Notes = $request->input('notes') ?? $reservation->Venue_Reservation_Notes;

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
                    ? 'Room ' . ($reservation->room?->Room_Number ?? $id)
                    : ($reservation->venue?->Venue_Name ?? 'Venue');

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
            ? 'Room ' . ($reservation->room?->Room_Number ?? $reservation->getKey())
            : ($reservation->venue?->Venue_Name ?? 'Venue');

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


        $allRooms = Room::orderBy('Room_Number')->get()->map(fn($r) => [
            'type'  => 'room',
            'label' => 'Room ' . $r->Room_Number,
            'meta'  => $r->Room_Type,
        ]);

        $allVenues = Venue::orderBy('Venue_Name')->get()->map(fn($v) => [
            'type'  => 'venue',
            'label' => $v->Venue_Name,
            'meta'  => null,
        ]);

        return view('employee.dashboard', compact(
            'reservations',
            'totalReservations',
            'totalRevenue',
            'activeGuests',
            'occupancyRate',
            'checkOutsToday',
            'checkOutsTodayCount',
            'changes',
            'allRooms',
            'allVenues'
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
    // ─────────────────────────────────────────────────────────────────
    //  CALENDAR EXCEL EXPORT
    // ─────────────────────────────────────────────────────────────────
    // public function exportCalendar(Request $request)
    // {
    //     $monthStart = (int) $request->query('start', now()->month);
    //     $monthEnd   = (int) $request->query('end', now()->month);
    //     $year       = (int) $request->query('year', now()->year);

    //     $start = max(1, min(12, $monthStart));
    //     $end   = max(1, min(12, $monthEnd));
    //     $year  = max(2020, min(2100, $year));

    //     $export = new \App\Exports\ReservationCalendarExport($start, $end, $year);
    //     return $export->download();
    // }

    // ─────────────────────────────────────────────────────────────────
    //  CALENDAR PDF EXPORT
    // ─────────────────────────────────────────────────────────────────
    /**
     * Export reservation calendar as a multi-month PDF.
     * Each month occupies its own page; all events are fully expanded.
     * Bar labels: Purpose · Guest · Room(s)/Venue
     */
    public function exportCalendarPDF(Request $request)
    {
        $month       = max(1,    min(12,   (int) $request->query('month', now()->month)));
        $year        = max(2020, min(2100, (int) $request->query('year',  now()->year)));
        $typeFilter  = $request->query('reservation_type', 'all');
        $granularity = $request->query('granularity', 'month'); // 'month' | 'week'
        $weekNum     = max(1, min(5, (int) $request->query('week', 1)));

        $preparedBy = auth()->user()?->Account_Name ?? 'N/A';

        $monthData = $this->buildCalendarPDFData($month, $year, $typeFilter);

        if ($granularity === 'week') {
            // Week N of month: day ranges 1–7, 8–14, 15–21, 22–28, 29–end
            $dayStart = ($weekNum - 1) * 7 + 1;
            $dayEnd   = ($weekNum === 5)
                ? Carbon::create($year, $month)->daysInMonth
                : $weekNum * 7;

            // Keep only calendar-grid week rows that overlap with [$dayStart, $dayEnd]
            $monthData['weeks'] = array_values(array_filter(
                $monthData['weeks'],
                function ($week) use ($month, $dayStart, $dayEnd) {
                    foreach ($week['days'] as $day) {
                        if ($day['in_month'] && $day['num'] >= $dayStart && $day['num'] <= $dayEnd) {
                            return true;
                        }
                    }
                    return false;
                }
            ));

            $weekLabel   = "Week {$weekNum}";
            $periodLabel = Carbon::create($year, $month)->format('F Y') . " — {$weekLabel}";
            $filenameTag = Carbon::create($year, $month)->format('F_Y') . "_Week{$weekNum}";
        } else {
            $periodLabel = Carbon::create($year, $month)->format('F Y');
            $filenameTag = $monthData['month_label_filename'];
        }

        $months = [$monthData];

        $maxHeight = 0;
        foreach ($months as $md) {
            $weekCount = count($md['weeks']);
            $h = 12 + 7 + 7; // header-table + legend-row + DOW header (mm)
            foreach ($md['weeks'] as $week) {
                $h += 14 + max(1, count($week['lanes'])) * 7;
            }
            $h += ($weekCount - 1) * 1.5;
            $h += 12;
            $h += 25.4;
            $maxHeight = max($maxHeight, $h);
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'employee.pdf.reservation_calendar',
            [
                'months'      => $months,
                'generated_at'=> now()->format('M d, Y  H:i'),
                'pageHeight'  => round($maxHeight, 2),
                'preparedBy'  => $preparedBy,
                'periodLabel' => $periodLabel,
            ]
        );

        return $pdf->download("reservation_calendar_{$filenameTag}.pdf");
    }

    /**
     * Export reservation data as a CSV file.
     * Columns: Type, Resource, Guest, Purpose, Check-In, Check-Out, Status
     */
    public function exportCalendarCSV(Request $request)
    {
        $startMonth  = max(1,    min(12,   (int) $request->query('month',            now()->month)));
        $endMonth    = max(1,    min(12,   (int) $request->query('end_month',         $startMonth)));
        $year        = max(2020, min(2100, (int) $request->query('year',             now()->year)));
        $typeFilter  = $request->query('reservation_type', 'all'); // 'all' | 'room' | 'venue'
        if ($endMonth < $startMonth) $endMonth = $startMonth;

        $rangeStart = Carbon::create($year, $startMonth, 1)->startOfDay();
        $rangeEnd   = Carbon::create($year, $endMonth)->endOfMonth()->endOfDay();

        $rows = collect();

        if ($typeFilter !== 'venue') {
            $roomRows = RoomReservation::with(['room', 'user'])
                ->where('Room_Reservation_Check_In_Time',  '<=', $rangeEnd)
                ->where('Room_Reservation_Check_Out_Time', '>=', $rangeStart)
                ->whereNotIn('Room_Reservation_Status', ['cancelled', 'rejected'])
                ->get()
                ->map(fn($r) => [
                    'Check-In'  => Carbon::parse($r->Room_Reservation_Check_In_Time)->format('Y-m-d'),
                    'Check-Out' => Carbon::parse($r->Room_Reservation_Check_Out_Time)->format('Y-m-d'),
                    'Guest'     => optional($r->user)->Account_Name ?? 'Guest',
                    'Resource'  => $r->room ? 'Room ' . $r->room->Room_Number : 'Room N/A',
                    'Type'      => 'Room',
                    'Status'    => $r->Room_Reservation_Status,
                    'Purpose'   => $r->Room_Reservation_Purpose ?? 'N/A',
                ]);
            $rows = $rows->concat($roomRows);
        }

        if ($typeFilter !== 'room') {
            $venueRows = VenueReservation::with(['venue', 'user'])
                ->where('Venue_Reservation_Check_In_Time',  '<=', $rangeEnd)
                ->where('Venue_Reservation_Check_Out_Time', '>=', $rangeStart)
                ->whereNotIn('Venue_Reservation_Status', ['cancelled', 'rejected'])
                ->get()
                ->map(fn($r) => [
                    'Check-In'  => Carbon::parse($r->Venue_Reservation_Check_In_Time)->format('Y-m-d'),
                    'Check-Out' => Carbon::parse($r->Venue_Reservation_Check_Out_Time)->format('Y-m-d'),
                    'Guest'     => optional($r->user)->Account_Name ?? 'Guest',
                    'Resource'  => $r->venue ? $r->venue->Venue_Name : 'Venue N/A',
                    'Type'      => 'Venue',
                    'Status'    => $r->Venue_Reservation_Status,
                    'Purpose'   => $r->Venue_Reservation_Purpose ?? 'N/A',
                ]);
            $rows = $rows->concat($venueRows);
        }

        $all = $rows->sortBy('Check-In')->values();

        $tag      = ($startMonth === $endMonth)
            ? Carbon::create($year, $startMonth)->format('F_Y')
            : Carbon::create($year, $startMonth)->format('M') . '_to_' .
              Carbon::create($year, $endMonth)->format('M_Y');
        $filename = "reservations_{$tag}.csv";

        $periodLabel = ($startMonth === $endMonth)
            ? Carbon::create($year, $startMonth)->format('F Y')
            : Carbon::create($year, $startMonth)->format('F') . ' to ' .
              Carbon::create($year, $endMonth)->format('F Y');

        $preparedBy  = auth()->user()?->Account_Name ?? 'N/A';
        $generatedAt = now()->format('F j, Y g:i A');

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control'       => 'no-cache, no-store, must-revalidate',
        ];

        $callback = function () use ($all, $periodLabel, $preparedBy, $generatedAt) {
            $out = fopen('php://output', 'w');
            // UTF-8 BOM so Excel opens it correctly
            fputs($out, "\xEF\xBB\xBF");
            // Document header block
            fputcsv($out, ['AdZU Lantaka Reservation System']);
            fputcsv($out, ['Prepared by:', $preparedBy]);
            fputcsv($out, ['Period:', $periodLabel]);
            fputcsv($out, ['Generated:', $generatedAt]);
            fputcsv($out, []); // blank spacer
            // Column headers + data rows
            fputcsv($out, ['Check-In', 'Check-Out', 'Guest', 'Resource', 'Type', 'Status', 'Purpose']);
            foreach ($all as $row) {
                fputcsv($out, array_values($row));
            }
            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }

    /** Build the week/lane data structure for one month's PDF page. */
    private function buildCalendarPDFData(int $month, int $year, string $typeFilter = 'all'): array
    {
        $monthStart = Carbon::create($year, $month, 1)->startOfDay();
        $monthEnd   = $monthStart->copy()->endOfMonth()->endOfDay();

        // Colors match STATUS_CFG in dashboard_calendar.js exactly.
        // 'solid' is the saturated accent used for the left border strip on bars and legend dots.
        $statusColors = [
            'pending'     => ['bg' => '#FEF3C7', 'fg' => '#92400E', 'border' => '#FBBF24', 'solid' => '#F59E0B'],
            'confirmed'   => ['bg' => '#DBEAFE', 'fg' => '#1E40AF', 'border' => '#60A5FA', 'solid' => '#3B82F6'],
            'checked-in'  => ['bg' => '#DCFCE7', 'fg' => '#166534', 'border' => '#4ADE80', 'solid' => '#22C55E'],
            'checked-out' => ['bg' => '#F3F4F6', 'fg' => '#374151', 'border' => '#9CA3AF', 'solid' => '#9CA3AF'],
            'completed'   => ['bg' => '#EDE9FE', 'fg' => '#5B21B6', 'border' => '#A78BFA', 'solid' => '#8B5CF6'],
            'cancelled'   => ['bg' => '#FEE2E2', 'fg' => '#991B1B', 'border' => '#F87171', 'solid' => '#EF4444'],
            'rejected'    => ['bg' => '#FEE2E2', 'fg' => '#991B1B', 'border' => '#F87171', 'solid' => '#EF4444'],
        ];

        $legend = [
            ['label' => 'Pending',     'solid' => '#F59E0B'],
            ['label' => 'Confirmed',   'solid' => '#3B82F6'],
            ['label' => 'Checked-In',  'solid' => '#22C55E'],
            ['label' => 'Checked-Out', 'solid' => '#9CA3AF'],
            ['label' => 'Completed',   'solid' => '#8B5CF6'],
        ];

        $allReservations = $this->loadReservationsForPDF($monthStart, $monthEnd, $typeFilter);

        $calStart = $monthStart->copy()->startOfWeek(Carbon::SUNDAY);
        $calEnd   = $monthEnd->copy()->endOfWeek(Carbon::SATURDAY);
        $dayAbbr  = ['SUN', 'MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT'];

        $weeks   = [];
        $current = $calStart->copy();

        while ($current->lte($calEnd)) {
            $wStart = $current->copy();
            $wEnd   = $current->copy()->addDays(6);

            $days = [];
            for ($d = 0; $d < 7; $d++) {
                $day    = $wStart->copy()->addDays($d);
                $days[] = [
                    'num'        => $day->day,
                    'name'       => $dayAbbr[$d],
                    'in_month'   => (int) $day->month === $month,
                    'is_today'   => $day->isToday(),
                    'is_weekend' => ($d === 0 || $d === 6),
                ];
            }

            $weeks[] = [
                'days'  => $days,
                'lanes' => $this->buildWeekLanes($allReservations, $wStart, $wEnd),
            ];

            $current->addDays(7);
        }

        return [
            'month_label'          => $monthStart->format('F Y'),
            'month_label_filename' => $monthStart->format('F_Y'),
            'weeks'                => $weeks,
            'statusColors'         => $statusColors,
            'legend'               => $legend,
        ];
    }

    /**
     * Fetch room + venue reservations for the PDF.
     * Rooms with identical dates + guest + purpose are grouped into one record.
     * Returns [ check_in, check_out, status, purpose, guest, resource, type ]
     */
    private function loadReservationsForPDF(Carbon $monthStart, Carbon $monthEnd, string $typeFilter = 'all'): array
    {
        // ── Rooms ──────────────────────────────────────────────────────────
        $grouped = [];
        if ($typeFilter !== 'venue') {
            $roomRaw = RoomReservation::with(['room', 'user'])
                ->where('Room_Reservation_Check_In_Time',  '<=', $monthEnd)
                ->where('Room_Reservation_Check_Out_Time', '>=', $monthStart)
                ->whereNotIn('Room_Reservation_Status', ['cancelled', 'rejected'])
                ->get()
                ->map(fn($r) => [
                    'id'        => $r->Room_Reservation_ID,
                    'check_in'  => Carbon::parse($r->Room_Reservation_Check_In_Time)->format('Y-m-d'),
                    'check_out' => Carbon::parse($r->Room_Reservation_Check_Out_Time)->format('Y-m-d'),
                    'status'    => strtolower($r->Room_Reservation_Status),
                    'purpose'   => $r->Room_Reservation_Purpose  ?? 'N/A',
                    'guest'     => optional($r->user)->Account_Name ?? 'Guest',
                    'resource'  => $r->room ? 'Room ' . $r->room->Room_Number : 'Room N/A',
                    'type'      => 'room',
                ])
                ->toArray();

            // Group rooms: same (check_in, check_out, guest, purpose) → one bar
            $processed = [];
            foreach ($roomRaw as $r) {
                if (in_array($r['id'], $processed)) continue;
                $siblings = array_values(array_filter($roomRaw, fn($o) =>
                    !in_array($o['id'], $processed) &&
                    $o['check_in']  === $r['check_in']  &&
                    $o['check_out'] === $r['check_out'] &&
                    $o['guest']     === $r['guest']     &&
                    $o['purpose']   === $r['purpose']
                ));
                foreach ($siblings as $s) $processed[] = $s['id'];
                $grouped[] = [
                    'check_in'  => $r['check_in'],
                    'check_out' => $r['check_out'],
                    'status'    => $r['status'],
                    'purpose'   => $r['purpose'],
                    'guest'     => $r['guest'],
                    'resource'  => implode(', ', array_map(fn($s) => $s['resource'], $siblings)),
                    'type'      => 'room',
                ];
            }
        }

        // ── Venues ─────────────────────────────────────────────────────────
        $venueRes = [];
        if ($typeFilter !== 'room') {
            $venueRes = VenueReservation::with(['venue', 'user'])
                ->where('Venue_Reservation_Check_In_Time',  '<=', $monthEnd)
                ->where('Venue_Reservation_Check_Out_Time', '>=', $monthStart)
                ->whereNotIn('Venue_Reservation_Status', ['cancelled', 'rejected'])
                ->get()
                ->map(fn($r) => [
                    'check_in'  => Carbon::parse($r->Venue_Reservation_Check_In_Time)->format('Y-m-d'),
                    'check_out' => Carbon::parse($r->Venue_Reservation_Check_Out_Time)->format('Y-m-d'),
                    'status'    => strtolower($r->Venue_Reservation_Status),
                    'purpose'   => $r->Venue_Reservation_Purpose  ?? 'N/A',
                    'guest'     => optional($r->user)->Account_Name ?? 'Guest',
                    'resource'  => $r->venue ? $r->venue->Venue_Name : 'Venue N/A',
                    'type'      => 'venue',
                ])
                ->toArray();
        }

        return array_merge($grouped, $venueRes);
    }

    /**
     * Greedy lane assignment for one week row.
     * No lane cap — all reservations are always visible (expanded).
     * Bar label: Purpose · Guest · Resource
     */
    private function buildWeekLanes(array $reservations, Carbon $weekStart, Carbon $weekEnd): array
    {
        $wStartStr = $weekStart->format('Y-m-d');
        $wEndStr   = $weekEnd->format('Y-m-d');

        $visible = array_values(array_filter(
            $reservations,
            fn($r) => $r['check_in'] <= $wEndStr && $r['check_out'] > $wStartStr
        ));

        if (empty($visible)) return [];

        // Longest span first, then by start date (mirrors JS behaviour)
        usort($visible, function ($a, $b) {
            $aLen = (int) Carbon::parse($a['check_in'])->diffInDays(Carbon::parse($a['check_out']));
            $bLen = (int) Carbon::parse($b['check_in'])->diffInDays(Carbon::parse($b['check_out']));
            if ($bLen !== $aLen) return $bLen - $aLen;
            return strcmp($a['check_in'], $b['check_in']);
        });

        $laneEnds = [];
        $lanes    = [];

        foreach ($visible as $res) {
            // Greedy lane pick.
            // Use strict > (not >=) because the colSpan formula adds +1 to visually
            // include the check-out day in the bar.  If check_in == previous check_out,
            // the bars would overlap at that column, causing DomPDF to receive a table
            // row whose colspan sum exceeds 7 and crash on table-layout:fixed.
            $lane = -1;
            foreach ($laneEnds as $i => $end) {
                if ($res['check_in'] > $end) { $lane = $i; break; }
            }
            if ($lane === -1) {
                $lane       = count($laneEnds);
                $laneEnds[] = $res['check_out'];
            } else {
                $laneEnds[$lane] = $res['check_out'];
            }

            // Clamp bar to the week window
            $barStart = max($res['check_in'], $wStartStr);
            $barEnd   = min($res['check_out'], $wEndStr);

            $colStart = (int) $weekStart->diffInDays(Carbon::parse($barStart));
            $colEnd   = (int) $weekStart->diffInDays(Carbon::parse($barEnd));
            // +1 so check-out day is visually included (matches JS fix)
            $colSpan  = max(1, min($colEnd - $colStart + 1, 7 - max(0, $colStart)));

            if (!isset($lanes[$lane])) $lanes[$lane] = [];

            $purpose = trim($res['purpose'] ?? '');

            $label = ($purpose && strtolower($purpose) !== 'n/a')
                ? "{$purpose} · {$res['guest']} | {$res['resource']}"
                : "{$res['guest']} | {$res['resource']}";

            $lanes[$lane][] = [
                'label'     => $label,
                'status'    => $res['status'],
                'col_start' => max(0, $colStart),
                'col_span'  => $colSpan,
                'clips_l'   => $res['check_in'] < $wStartStr,
                'clips_r'   => $res['check_out'] > $wEndStr,
            ];
        }

        ksort($lanes);
        return array_values($lanes);
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
                'Room_Reservation_Notes' => $request->notes ?? null,
                'Room_Reservation_Total_Price' => $totalAmount,
                'Room_Reservation_Status' => 'pending',
            ]);

            $this->sendConfirmationEmail($reservation);
        } else {
            // Edit mode: reuse existing VenueReservation instead of creating a new one
            if ($request->filled('venue_reservation_id')) {
                $reservation = VenueReservation::with(['venue', 'foods'])->findOrFail($request->venue_reservation_id);
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
                    'Venue_Reservation_Notes' => $request->notes ?? null,
                    'Venue_Reservation_Total_Price' => $totalAmount,
                    'Venue_Reservation_Status' => 'pending',
                ]);

                $this->sendConfirmationEmail($reservation);
            }

            // ── Food saving: mirrors the three-step logic in the client store() ────
            $foodSelections   = $request->input('food_selections',    []);
            $foodSetSelection = $request->input('food_set_selection', []);
            $foodEnabledMap   = $request->input('food_enabled',       []);
            $pax              = (int) $request->pax;

            // ── STEP A: build skip-list of meal keys that belong to set selections ──
            // Set customisations are embedded directly in Food_Set_ID text, so we
            // must NOT also create separate individual rows for those slots.
            //   Spiritual sets:  food_set_selection[date][mealKey] = "setId"
            //                    → skip mealKey itself
            //   General sets:    food_set_selection[date][mealKey][] = setId
            //                    → skip "gen_{setId}" (the customisation slot)
            $skipMealKeys = [];  // [$date][$mealKey] = true
            foreach ($foodSetSelection as $_d => $_meals) {
                foreach ((array) $_meals as $_mk => $_ids) {
                    $isArray = is_array($_ids);
                    $rawIds  = $isArray ? $_ids : [$_ids];
                    foreach ($rawIds as $_id) {
                        if (!empty($_id)) {
                            $skipMealKeys[$_d]["gen_{$_id}"] = true;
                        }
                    }
                    if (!$isArray && !empty($_ids)) {
                        $skipMealKeys[$_d][$_mk] = true;
                    }
                }
            }

            // ── STEP B: individual food selections ───────────────────────────────
            if (!empty($foodSelections)) {
                // Recursively extract valid numeric food IDs; skip drink_choice (text).
                $extractFoodIds = function ($data) use (&$extractFoodIds) {
                    $ids = [];
                    if (is_array($data)) {
                        foreach ($data as $k => $v) {
                            if ($k === 'drink_choice') continue;
                            $ids = array_merge($ids, $extractFoodIds($v));
                        }
                    } elseif (is_numeric($data) && !empty($data)) {
                        $ids[] = (int) $data;
                    }
                    return $ids;
                };

                foreach ($foodSelections as $date => $meals) {
                    foreach ($meals as $mealType => $mealData) {
                        if (empty($mealData)) continue;
                        if (!empty($skipMealKeys[$date][$mealType])) continue;

                        $foodIds = $extractFoodIds($mealData);
                        foreach ($foodIds as $foodId) {
                            $food = Food::find($foodId);
                            if ($food) {
                                FoodReservation::create([
                                    'Food_ID'                       => $foodId,
                                    'Venue_Reservation_ID'          => $reservation->Venue_Reservation_ID,
                                    'Client_ID'                     => $reservation->Client_ID,
                                    'Food_Reservation_Serving_Date' => $date,
                                    'Food_Reservation_Meal_time'    => $mealType,
                                    'Food_Reservation_Total_Price'  => ($food->Food_Price ?? 0) * $pax,
                                ]);
                            }
                        }
                    }
                }
            }

            // ── STEP C: food SET selections ──────────────────────────────────────
            // One FoodReservation row per set. Food_Set_ID stores set ID + custom
            // choices as:  "setId",["riceId","drinksId","dessertId","fruitId"]
            if (!empty($foodSetSelection)) {
                foreach ($foodSetSelection as $date => $meals) {
                    if (($foodEnabledMap[$date] ?? '1') != '1') continue;

                    foreach ((array) $meals as $mealKey => $setIdOrIds) {
                        $isGeneralSet = is_array($setIdOrIds);
                        $setIds       = $isGeneralSet ? $setIdOrIds : [$setIdOrIds];

                        foreach ($setIds as $setId) {
                            if (empty($setId)) continue;

                            $set = FoodSet::find((int) $setId);
                            if (!$set) continue;

                            if ($isGeneralSet) {
                                // General event: customisations stored under gen_setId slot
                                $genKey    = "gen_{$setId}";
                                $genSel    = $foodSelections[$date][$genKey] ?? [];
                                $riceId    = (string) ($genSel['rice']    ?? '');
                                $dessertId = (string) ($genSel['dessert'] ?? '');
                                $fruitId   = '';

                                // Drink: Food_ID from searchable-select (or legacy text fallback)
                                $drinkVal = $genSel['drink'] ?? ($genSel['drink_choice'] ?? '');
                                if (is_numeric($drinkVal) && !empty($drinkVal)) {
                                    $drinksId = (string) $drinkVal;
                                } elseif (!empty($drinkVal)) {
                                    $drinkTxt  = strtolower(trim((string) $drinkVal));
                                    $drinkFood = Food::where(function ($q) use ($drinkTxt) {
                                        $q->where('Food_Name', 'ILIKE', $drinkTxt . '%')
                                          ->orWhere('Food_Name', 'ILIKE', '%' . $drinkTxt . '%');
                                    })->first();
                                    $drinksId = $drinkFood ? (string) $drinkFood->Food_ID : '';
                                } else {
                                    $drinksId = '';
                                }
                            } else {
                                // Spiritual event: customisations stored under the meal key
                                $mealSel   = $foodSelections[$date][$mealKey] ?? [];
                                $riceId    = (string) ($mealSel['rice_type']  ?? '');
                                $drinksId  = (string) ($mealKey === 'breakfast'
                                    ? ($mealSel['hot_drink']  ?? '')
                                    : ($mealSel['softdrinks'] ?? ''));
                                $dessertId = (string) ($mealSel['dessert'] ?? '');
                                $fruitId   = (string) ($mealSel['fruits']  ?? '');
                            }

                            $customIds     = [$riceId, $drinksId, $dessertId, $fruitId];
                            $foodSetIdText = '"' . $setId . '",' . json_encode($customIds);

                            FoodReservation::create([
                                'Food_ID'                       => null,
                                'Food_Set_ID'                   => $foodSetIdText,
                                'Venue_Reservation_ID'          => $reservation->Venue_Reservation_ID,
                                'Client_ID'                     => $reservation->Client_ID,
                                'Food_Reservation_Serving_Date' => $date,
                                'Food_Reservation_Meal_time'    => $mealKey,
                                'Food_Reservation_Total_Price'  => (float) ($set->Food_Set_Price ?? 0) * $pax,
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
                    new \App\Mail\ReservationConfirmationMail($reservation)
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
            // Propagate skip_food flag so the food page auto-disables food when pax < minimum
            if ($request->input('skip_food') == '1') {
                $bookingData['skip_food'] = '1';
            }
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
            // max(1, ...) mirrors storeReservation(): same-day check-in/check-out
            // must still bill at least 1 night instead of producing a ₱0 total.
            $totalAmount = $price * max(1, $days);

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
            $previousMealMode       = [];
            $previousSetSelections  = [];

            $isSpiritual = in_array(
                strtolower($request->purpose ?? ''),
                ['retreat', 'recollection']
            );

            // ── 1. Snapshot individual food rows ─────────────────────────────────
            // Map DB Food_Category values to the exact form field keys used by
            // client_food_option.js so the JS restore functions can pre-fill them.
            $viandCountSnap = [];  // [$date][$mealType] = int — track viand position

            // Snack slots are always individual-style regardless of the date's
            // set/individual mode toggle, so they must NOT influence cardModes[date].
            $snackMealTypes = ['am_snack', 'pm_snack', 'snacks'];

            foreach ($reservation->foods as $food) {
                $date     = $food->pivot->Food_Reservation_Serving_Date ?? null;
                $mealType = $food->pivot->Food_Reservation_Meal_time    ?? null;
                $category = strtolower($food->Food_Category ?? '');
                $foodId   = $food->Food_ID ?? null;

                if (!$date || !$mealType || !$category || !$foodId) continue;

                $previousFoodEnabled[$date]            = '1';
                $previousMealEnabled[$date][$mealType] = '1';
                // Only mark main meal slots as 'individual' — snack slots are
                // always individually-selectable and must not flip the mode toggle.
                if (!in_array($mealType, $snackMealTypes)) {
                    $previousMealMode[$date][$mealType] = 'individual';
                }

                if ($category === 'rice') {
                    $previousFoodSelections[$date][$mealType]['rice'] = (string) $foodId;

                } elseif (in_array($category, ['viand', 'viands'])) {
                    // Assign to viand1, viand2 in order (extra viands use chip UI — not restorable here)
                    $cnt = $viandCountSnap[$date][$mealType] ?? 0;
                    if ($cnt === 0) {
                        $previousFoodSelections[$date][$mealType]['viand1'] = (string) $foodId;
                    } elseif ($cnt === 1) {
                        $previousFoodSelections[$date][$mealType]['viand2'] = (string) $foodId;
                    }
                    // 3rd+ viands stored as chips — skip (no simple prefill)
                    $viandCountSnap[$date][$mealType] = $cnt + 1;

                } elseif (in_array($category, ['drink', 'drinks'])) {
                    $previousFoodSelections[$date][$mealType]['drink'] = (string) $foodId;

                } elseif ($mealType === 'snacks') {
                    // Multi-select snacks — append to array so ALL selections are preserved
                    $previousFoodSelections[$date]['snacks'][] = (string) $foodId;

                } else {
                    // dessert, desserts, fruits, etc. — pass through as-is
                    $previousFoodSelections[$date][$mealType][$category] = (string) $foodId;
                }
            }

            // ── 2. Snapshot food SET rows ─────────────────────────────────────────
            $reservation->load('foodSetReservations');
            foreach ($reservation->foodSetReservations as $setRes) {
                $date    = $setRes->Food_Reservation_Serving_Date ?? null;
                $mealKey = $setRes->Food_Reservation_Meal_time    ?? null;
                if (!$date || !$mealKey) continue;

                $parsed    = $setRes->parseFoodSetId();
                $setId     = $parsed['set_id'];
                $customIds = $parsed['custom_ids'];  // [riceId, drinksId, dessertId, fruitId]
                if (!$setId) continue;

                $previousFoodEnabled[$date]          = '1';
                $previousMealEnabled[$date][$mealKey] = '1';
                $previousMealMode[$date][$mealKey]    = 'set';

                if ($isSpiritual) {
                    // Spiritual: scalar set ID per meal key
                    $previousSetSelections[$date][$mealKey] = (string) $setId;

                    // Customisations stored under mealKey in food_selections
                    $previousFoodSelections[$date][$mealKey]['rice_type'] = (string) ($customIds[0] ?? '');
                    if ($mealKey === 'breakfast') {
                        $previousFoodSelections[$date][$mealKey]['hot_drink']  = (string) ($customIds[1] ?? '');
                    } else {
                        $previousFoodSelections[$date][$mealKey]['softdrinks'] = (string) ($customIds[1] ?? '');
                    }
                    $previousFoodSelections[$date][$mealKey]['dessert'] = (string) ($customIds[2] ?? '');
                    $previousFoodSelections[$date][$mealKey]['fruits']  = (string) ($customIds[3] ?? '');
                } else {
                    // General: array of set IDs per meal key
                    $previousSetSelections[$date][$mealKey][] = (string) $setId;

                    // Customisations stored under gen_setId in food_selections
                    $genKey = "gen_{$setId}";
                    $previousFoodSelections[$date][$genKey]['rice']    = (string) ($customIds[0] ?? '');
                    $previousFoodSelections[$date][$genKey]['drink']   = (string) ($customIds[1] ?? '');
                    $previousFoodSelections[$date][$genKey]['dessert'] = (string) ($customIds[2] ?? '');
                }
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
                'prefill_meal_mode'       => $previousMealMode,
                'prefill_set_selections'  => $previousSetSelections,
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

        // Guard: only client accounts have SOAs. Prevent accessing staff/admin billing pages.
        if ($client->Account_Role !== 'client') {
            abort(403, 'SOA is only available for client accounts.');
        }

        $roomReservations = RoomReservation::with('room')
            ->where('Client_ID', $clientId)
            ->where('Room_Reservation_Status', 'checked-in')
            ->get();

        $venueReservations = VenueReservation::with('venue', 'foodReservations')
            ->where('Client_ID', $clientId)
            ->where('Venue_Reservation_Status', 'checked-in')
            ->get();

        $reservations = collect();

        foreach ($roomReservations as $r) {
            $checkIn = \Carbon\Carbon::parse($r->Room_Reservation_Check_In_Time);
            $checkOut = \Carbon\Carbon::parse($r->Room_Reservation_Check_Out_Time);
            $nights = max(1, $checkIn->diffInDays($checkOut));

            $rawItems = json_decode($r->Room_Reservation_Additional_Fees_Desc ?? '[]', true) ?? [];
            $parsedItems = [];

            foreach ($rawItems as $item) {
                $parts = explode(':', $item);

                $desc = trim($parts[0] ?? '');
                if ($desc === '') continue; // skip blank entries

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
            $discount       = (float) ($r->Room_Reservation_Discount ?? 0);
            $roomRate       = ($client->Account_Type === 'Internal')
                ? (float) ($r->room?->Room_Internal_Price ?? 0)
                : (float) ($r->room?->Room_External_Price ?? 0);
            $baseAmount     = $roomRate * $nights;

            $reservations->push([
                'type' => 'room',
                'id' => $r->Room_Reservation_ID,
                'name' => 'Room ' . ($r->room?->Room_Number ?? 'Unknown'),
                'check_in' => $checkIn->format('m/d/Y'),
                'check_out' => $checkOut->format('m/d/Y'),
                'pax' => $r->Room_Reservation_Pax,
                'days' => $nights,
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
            $days = max(1, $checkIn->diffInDays($checkOut) + 1); // venues billed inclusive of both check-in and check-out day

            $rawItems = json_decode($v->Venue_Reservation_Additional_Fees_Desc ?? '[]', true) ?? [];
            $parsedItems = [];

            foreach ($rawItems as $item) {
                $parts = explode(':', $item);

                $desc = trim($parts[0] ?? '');
                if ($desc === '') continue; // skip blank entries

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
            $discount       = (float) ($v->Venue_Reservation_Discount ?? 0);
            $foodTotal      = (float) $v->foodReservations->sum('Food_Reservation_Total_Price');
            $pax            = (int) ($v->Venue_Reservation_Pax ?? 1);
            $foodPerPax     = $pax > 0 ? round($foodTotal / $pax, 2) : 0;
            $venueRate      = ($client->Account_Type === 'Internal')
                ? (float) ($v->venue?->Venue_Internal_Price ?? 0)
                : (float) ($v->venue?->Venue_External_Price ?? 0);
            $baseAmount     = $venueRate * $days;

            $reservations->push([
                'type' => 'venue',
                'id' => $v->Venue_Reservation_ID,
                'name' => 'Venue ' . ($v->venue?->Venue_Name ?? 'Unknown'),
                'check_in' => $checkIn->format('m/d/Y'),
                'check_out' => $checkOut->format('m/d/Y'),
                'pax' => $pax,
                'days' => $days,
                'base_price' => $baseAmount,
                'total_price' => $v->Venue_Reservation_Total_Price ?? 0,
                'additional_fees' => $additionalFees,
                'discount' => $discount,
                'additional_fee_items' => $parsedItems,
                'food_total' => $foodTotal,
                'food_per_pax' => $foodPerPax,
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

        // Guard: only client accounts have SOAs. Prevent accessing staff/admin billing data.
        if ($client->Account_Role !== 'client') {
            abort(403, 'SOA export is only available for client accounts.');
        }

        $roomReservations = RoomReservation::with('room')
            ->where('Client_ID', $clientId)
            ->where('Room_Reservation_Status', 'checked-in')
            ->when(!empty($roomIds), function ($query) use ($roomIds) {
                $query->whereIn('Room_Reservation_ID', $roomIds);
            }, function ($query) {
                $query->whereRaw('1 = 0');
            })
            ->get();

        $venueReservations = VenueReservation::with('venue', 'foodReservations')
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

            $roomRate   = ($client->Account_Type === 'Internal')
                ? (float) ($r->room?->Room_Internal_Price ?? 0)
                : (float) ($r->room?->Room_External_Price ?? 0);
            $baseAmount = $roomRate * $nights;
            $discount   = (float) ($r->Room_Reservation_Discount ?? 0);

            $reservations->push([
                'date'        => $checkIn->format('F d, Y'),
                'particulars' => 'Room ' . ($r->room->Room_Number ?? 'Room'),
                'qty'         => $nights,
                'unit'        => 'night',
                'rate'        => $roomRate,
                'amount'      => $baseAmount,
                'is_subitem'  => false,
                'is_discount' => false,
            ]);

            $rawItems = json_decode($r->Room_Reservation_Additional_Fees_Desc ?? '[]', true) ?? [];
            foreach ($rawItems as $item) {
                $parts     = explode(':', $item);
                $desc      = trim($parts[0] ?? '');
                if ($desc === '') continue; // skip blank entries

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

        foreach ($venueReservations as $v) {
            $checkIn  = Carbon::parse($v->Venue_Reservation_Check_In_Time);
            $checkOut = Carbon::parse($v->Venue_Reservation_Check_Out_Time);
            $days     = max(1, $checkIn->diffInDays($checkOut) + 1); // venues billed inclusive of both check-in and check-out day

            $discount   = (float) ($v->Venue_Reservation_Discount ?? 0);
            $foodTotal  = (float) $v->foodReservations->sum('Food_Reservation_Total_Price');
            $venuePax   = (int) ($v->Venue_Reservation_Pax ?? 1);
            $foodPerPax = $venuePax > 0 ? round($foodTotal / $venuePax, 2) : 0;
            $venueRate  = ($client->Account_Type === 'Internal')
                ? (float) ($v->venue->Venue_Internal_Price ?? 0)
                : (float) ($v->venue->Venue_External_Price ?? 0);
            $baseAmount = $venueRate * $days;
            $ratePerDay = $venueRate;

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

            // Food sub-item (only when food was ordered)
            if ($foodTotal > 0) {
                $reservations->push([
                    'date'        => '',
                    'particulars' => '* Food',
                    'qty'         => $venuePax,
                    'unit'        => 'pax',
                    'rate'        => $foodPerPax,
                    'amount'      => $foodTotal,
                    'is_subitem'  => true,
                    'is_discount' => false,
                ]);
            }

            $rawItems = json_decode($v->Venue_Reservation_Additional_Fees_Desc ?? '[]', true) ?? [];
            foreach ($rawItems as $item) {
                $parts    = explode(':', $item);
                $desc     = trim($parts[0] ?? '');
                if ($desc === '') continue; // skip blank entries

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

        // ── Summary box border — all 4 rows inside one black border ─────────
        // First clear any stale borders that the template may have only partially
        // placed (e.g. only on the last 2 rows), then draw a clean box.
        // $sheet->getStyle("G{$summaryBase}:J{$r4}")->applyFromArray([
        //     'borders' => [
        //         // Thick outer box
        //         'outline' => [
        //             'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM,
        //             'color'       => ['argb' => 'FF000000'],
        //         ],
        //         // Thin inner dividers between rows / label+value columns
        //         'inside' => [
        //             'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
        //             'color'       => ['argb' => 'FF000000'],
        //         ],
        //     ],
        // ]);

        // Align labels (G–I merged area) right and values (J) right inside the box
        $sheet->getStyle("G{$summaryBase}:I{$r4}")->getAlignment()
            ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("J{$summaryBase}:J{$r4}")->getAlignment()
            ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);

        // Auto-fit row heights for the summary block so nothing is clipped
        for ($sr = $summaryBase; $sr <= $r4; $sr++) {
            $sheet->getRowDimension($sr)->setRowHeight(-1); // -1 = auto
        }

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
                'purpose' => $item->Room_Reservation_Purpose,
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
                'purpose' => $item->Venue_Reservation_Purpose,
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
                'today'              => $today,
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

        // ── Room Type Breakdown (checked-out only; groups by Room_Type) ──
        $roomTypeRows = RoomReservation::with('room')
            ->where('Room_Reservation_Status', 'checked-out')
            ->whereBetween('created_at', [$start, $end])
            ->get()
            ->groupBy(fn($r) => $r->room?->Room_Type ?? 'Unknown')
            ->map(fn($group, $type) => [
                'type'     => $type,
                'bookings' => $group->count(),
                'revenue'  => (float) $group->filter(fn($r) => $r->Room_Reservation_Payment_Status === 'paid')
                                             ->sum('Room_Reservation_Total_Price'),
            ])->values();

        // ── Venue count for the comparison chart ──
        $venueTypeRow = [
            'type'     => 'Venue',
            'bookings' => $venueCount,
            'revenue'  => (float) $venueRevThis,
        ];

        // ── Top 10 Clients (checked-out; revenue = paid; all reservation types) ──
        $roomClientRevenue = RoomReservation::where('Room_Reservation_Status', 'checked-out')
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('"Client_ID", count(*) as bookings, sum(CASE WHEN "Room_Reservation_Payment_Status" = \'paid\' THEN "Room_Reservation_Total_Price" ELSE 0 END) as revenue')
            ->groupBy('Client_ID')
            ->get()
            ->keyBy('Client_ID');

        $venueClientRevenue = VenueReservation::where('Venue_Reservation_Status', 'checked-out')
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('"Client_ID", count(*) as bookings, sum(CASE WHEN "Venue_Reservation_Payment_Status" = \'paid\' THEN "Venue_Reservation_Total_Price" ELSE 0 END) as revenue')
            ->groupBy('Client_ID')
            ->get()
            ->keyBy('Client_ID');

        $allClientIds = collect($roomClientRevenue->keys())->merge($venueClientRevenue->keys())->unique();
        $clients      = Account::whereIn('Account_ID', $allClientIds)->get()->keyBy('Account_ID');

        $topClients = $allClientIds->map(function ($clientId) use ($roomClientRevenue, $venueClientRevenue, $clients) {
            $roomRow  = $roomClientRevenue[$clientId]  ?? null;
            $venueRow = $venueClientRevenue[$clientId] ?? null;
            $client   = $clients[$clientId] ?? null;

            return [
                'name'     => $client?->Account_Name ?? 'Client #' . $clientId,
                'bookings' => (int)(($roomRow->bookings ?? 0) + ($venueRow->bookings ?? 0)),
                'revenue'  => (float)(($roomRow->revenue ?? 0) + ($venueRow->revenue ?? 0)),
            ];
        })->sortByDesc('revenue')->values()->take(10);

        // ── Top Rooms / Top Venues (kept for backward compat) ──
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
            'roomTypeBreakdown' => $roomTypeRows,
            'venueTypeRow'      => $venueTypeRow,
            'topClients'        => $topClients,
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
            'activeGuests'      => $pct((float) $activeGuests,  (float) $activeGuestsLast),
            'checkOuts'         => $pct($checkOutsThis,       $checkOutsLast),
        ];
    }

    /* ════════════════════════════════════════════════════════════════════
     |  CANCELLATION REQUEST FLOW
     |  Client submits a request → admin approves or rejects it.
     ╚═══════════════════════════════════════════════════════════════════ */

    /**
     * CLIENT: Submit a cancellation request for a pending or confirmed reservation.
     *
     * POST /reservations/{id}/request-cancellation?type=room|venue
     * Cancellation data is stored directly on the reservation row — no separate table.
     */
    public function requestCancellation(Request $request, $id)
    {
        $type = $request->query('type');

        if (!in_array($type, ['room', 'venue'])) {
            return response()->json(['success' => false, 'message' => 'Invalid reservation type.'], 422);
        }

        $request->validate([
            'reason' => 'required|string|min:10|max:1000',
        ]);

        // ── Server-side time cutoff: reject if current time is 4:00 PM or later ──
        $now = Carbon::now();
        if ($now->hour >= 16) {
            return response()->json([
                'success' => false,
                'message' => 'Cancellation requests can no longer be submitted for today. Our cut-off time is 4:00 PM. Please try again tomorrow.',
            ], 422);
        }

        // Use a DB transaction — nothing is saved if any validation check below fails
        return DB::transaction(function () use ($request, $id, $type, $now) {

            try {
                $reservation = ($type === 'room')
                    ? \App\Models\RoomReservation::lockForUpdate()->findOrFail($id)
                    : \App\Models\VenueReservation::lockForUpdate()->findOrFail($id);
            } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
                // Throw an HTTP exception — Laravel will roll back the transaction
                abort(404, 'Reservation not found.');
            }

            // Verify the reservation belongs to the authenticated client
            if ($reservation->Client_ID !== auth()->id()) {
                abort(403, 'Unauthorised.');
            }

            $statusCol     = $type === 'room' ? 'Room_Reservation_Status' : 'Venue_Reservation_Status';
            $currentStatus = strtolower($reservation->$statusCol);

            if (!in_array($currentStatus, ['pending', 'confirmed'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cancellation requests can only be made for pending or confirmed reservations.',
                ], 422);
            }

            // Reject duplicate pending request
            if ($reservation->cancellation_status === 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'You already have a pending cancellation request for this reservation.',
                ], 422);
            }

            // ── 3-day rule: check-in must be at least 3 calendar days from today ──
            $checkInCol  = $type === 'room' ? 'Room_Reservation_Check_In_Time' : 'Venue_Reservation_Check_In_Time';
            $checkInDate = Carbon::parse($reservation->$checkInCol)->startOfDay();
            $today       = $now->copy()->startOfDay();
            // Compute signed day difference explicitly to avoid relying on Carbon
            // version-specific behaviour of diffInDays($date, false).
            // Positive  → check-in is in the future (cancellation may be allowed).
            // Zero/neg  → check-in is today or already past (always blocked).
            $daysUntil = $today->lt($checkInDate)
                ? $today->diffInDays($checkInDate)   // future:  returns positive absolute
                : -$today->diffInDays($checkInDate); // past/today: force negative/zero

            if ($daysUntil < 3) {
                return response()->json([
                    'success' => false,
                    'message' => 'You can no longer cancel your stay because your check-in date is less than 3 working days.',
                ], 422);
            }

            // All validations passed — save the cancellation request
            $reservation->cancellation_status       = 'pending';
            $reservation->cancellation_reason       = trim($request->input('reason'));
            $reservation->cancellation_admin_note   = null;
            $reservation->cancellation_processed_by = null;
            $reservation->cancellation_requested_at = $now;
            $reservation->cancellation_processed_at = null;
            $reservation->save();

            // ── Notify all admin and staff (in-system + email to staff only) ────
            $clientName = auth()->user()?->Account_Name ?? 'A client';
            $accName    = $type === 'room'
                ? 'Room ' . ($reservation->room?->Room_Number ?? $id)
                : ($reservation->venue?->Venue_Name ?? 'Venue');

            $staffAccounts = Account::whereIn('Account_Role', ['admin', 'staff'])->get();

            $staffAccounts->each(function ($staff) use ($reservation, $clientName, $accName) {
                // In-system bell notification for all admin + staff
                EventLog::create([
                    'user_id'                       => auth()->id(),
                    'Event_Logs_Notifiable_User_ID' => $staff->Account_ID,
                    'Event_Logs_Action'             => 'cancellation_requested',
                    'Event_Logs_Title'              => 'Cancellation Request Submitted',
                    'Event_Logs_Message'            => "{$clientName} has submitted a cancellation request for {$accName}.",
                    'Event_Logs_Type'               => 'warning',
                    'Event_Logs_Link'               => '/employee/reservations',
                    'Event_Logs_isRead'             => false,
                ]);
            });

            // Email notification to staff only
            $staffAccounts->where('Account_Role', 'staff')->each(function ($staff) use ($reservation, $type, $clientName, $accName) {
                if (!$staff->Account_Email) {
                    Log::warning('CancellationRequestedMail skipped: staff has no email', ['staff_id' => $staff->Account_ID]);
                    return;
                }
                try {
                    Mail::to($staff->Account_Email)
                        ->send(new CancellationRequestedMail($reservation, $type, $clientName, $accName));
                } catch (\Exception $e) {
                    Log::error('CancellationRequestedMail to staff failed', [
                        'staff_id' => $staff->Account_ID,
                        'error'    => $e->getMessage(),
                    ]);
                }
            });

            return response()->json([
                'success'      => true,
                'message'      => 'Your cancellation request has been submitted. We will get back to you shortly.',
                'requested_at' => $now->format('M d, Y h:i A'),
            ]);
        });
    }

    /* ════════════════════════════════════════════════════════════════════
     |  REQUEST FOR CHANGES FLOW  (reschedule + food modification)
     |  Client submits a change request → admin approves or rejects it.
     |  Changes are stored on the reservation row — no separate table.
     ╚═══════════════════════════════════════════════════════════════════ */

    /**
     * CLIENT: Initiate a Request for Changes by redirecting to the booking flow.
     *
     * POST /client/reservations/{id}/initiate-change?type=room|venue
     * Sets session context and reconstructs food selections (for venues), then
     * redirects to the room/venue viewing page with existing reservation data
     * pre-filled — mirroring the checkout "Edit" button flow.
     */
    public function initiateChangeRequest(Request $request, $id)
    {
        $type = $request->query('type');

        if (!in_array($type, ['room', 'venue'])) {
            return redirect()->route('client.my_reservations')
                ->with('error', 'Invalid reservation type.');
        }

        try {
            $reservation = ($type === 'room')
                ? \App\Models\RoomReservation::findOrFail($id)
                : \App\Models\VenueReservation::findOrFail($id);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return redirect()->route('client.my_reservations')
                ->with('error', 'Reservation not found.');
        }

        if ($reservation->Client_ID !== auth()->id()) {
            abort(403, 'Unauthorised.');
        }

        $statusCol     = $type === 'room' ? 'Room_Reservation_Status' : 'Venue_Reservation_Status';
        $currentStatus = strtolower($reservation->$statusCol);

        if (!in_array($currentStatus, ['pending', 'confirmed'])) {
            return redirect()->route('client.my_reservations')
                ->with('error', 'Change requests can only be made for pending or confirmed reservations.');
        }

        if ($reservation->change_request_status === 'pending') {
            return redirect()->route('client.my_reservations')
                ->with('error', 'You already have a pending Request for Changes for this reservation.');
        }

        // Store session context so storeChangeRequest knows which reservation this is for
        session([
            'change_request_reservation_id'   => (int) $id,
            'change_request_reservation_type' => $type,
        ]);

        $checkIn  = $type === 'room'
            ? $reservation->Room_Reservation_Check_In_Time
            : $reservation->Venue_Reservation_Check_In_Time;
        $checkOut = $type === 'room'
            ? $reservation->Room_Reservation_Check_Out_Time
            : $reservation->Venue_Reservation_Check_Out_Time;
        $pax      = $type === 'room'
            ? ($reservation->Room_Reservation_Pax ?? 1)
            : ($reservation->Venue_Reservation_Pax ?? 1);
        $purpose  = $type === 'room'
            ? ($reservation->Room_Reservation_Purpose ?? '')
            : ($reservation->Venue_Reservation_Purpose ?? '');
        $notes    = $type === 'room'
            ? ($reservation->Room_Reservation_Notes ?? '')
            : ($reservation->Venue_Reservation_Notes ?? '');
        $accId    = $type === 'room'
            ? $reservation->Room_ID
            : $reservation->Venue_ID;

        // For venues, reconstruct food selections in session so food_option.blade.php pre-fills
        if ($type === 'venue') {
            $this->buildFoodEditSession($reservation, $purpose);
        }

        return redirect()->to(
            route('client.show', ['category' => $type, 'id' => $accId])
            . '?' . http_build_query([
                'check_in'       => Carbon::parse($checkIn)->toDateString(),
                'check_out'      => Carbon::parse($checkOut)->toDateString(),
                'pax'            => $pax,
                'purpose'        => $purpose,
                'notes'          => $notes,
                'edit'           => '1',
                'change_request' => '1',
            ])
        );
    }

    /**
     * CLIENT: Store a submitted Request for Changes.
     *
     * POST /client/reservations/store-change-request
     * Receives full booking form data (same format as booking.prepare / food_option)
     * then saves it as a pending change request on the reservation row.
     */
    public function storeChangeRequest(Request $request)
    {
        $id   = session('change_request_reservation_id');
        $type = session('change_request_reservation_type');

        if (!$id || !in_array($type, ['room', 'venue'])) {
            return redirect()->route('client.my_reservations')
                ->with('error', 'Invalid change request session. Please try again from My Reservations.');
        }

        try {
            $reservation = ($type === 'room')
                ? \App\Models\RoomReservation::findOrFail($id)
                : \App\Models\VenueReservation::findOrFail($id);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            session()->forget(['change_request_reservation_id', 'change_request_reservation_type']);
            return redirect()->route('client.my_reservations')
                ->with('error', 'Reservation not found.');
        }

        if ($reservation->Client_ID !== auth()->id()) {
            session()->forget(['change_request_reservation_id', 'change_request_reservation_type']);
            abort(403, 'Unauthorised.');
        }

        $statusCol     = $type === 'room' ? 'Room_Reservation_Status' : 'Venue_Reservation_Status';
        $currentStatus = strtolower($reservation->$statusCol);

        if (!in_array($currentStatus, ['pending', 'confirmed'])) {
            session()->forget(['change_request_reservation_id', 'change_request_reservation_type']);
            return redirect()->route('client.my_reservations')
                ->with('error', 'Change requests can only be made for pending or confirmed reservations.');
        }

        if ($reservation->change_request_status === 'pending') {
            session()->forget(['change_request_reservation_id', 'change_request_reservation_type']);
            return redirect()->route('client.my_reservations')
                ->with('error', 'You already have a pending Request for Changes for this reservation.');
        }

        // Determine what kind of change is being requested
        $origCheckIn  = Carbon::parse($type === 'room'
            ? $reservation->Room_Reservation_Check_In_Time
            : $reservation->Venue_Reservation_Check_In_Time)->toDateString();
        $origCheckOut = Carbon::parse($type === 'room'
            ? $reservation->Room_Reservation_Check_Out_Time
            : $reservation->Venue_Reservation_Check_Out_Time)->toDateString();

        $newCheckIn  = $request->input('check_in');
        $newCheckOut = $request->input('check_out');

        $datesChanged = ($newCheckIn  && $newCheckIn  !== $origCheckIn)
                     || ($newCheckOut && $newCheckOut !== $origCheckOut);

        $hasFood = $type === 'venue' && (
            !empty($request->input('food_selections',    []))  ||
            !empty($request->input('food_set_selection', []))
        );

        if ($type === 'room') {
            $reqType = 'reschedule';
        } elseif ($datesChanged && $hasFood) {
            $reqType = 'reschedule_and_food';
        } elseif ($datesChanged) {
            $reqType = 'reschedule';
        } else {
            $reqType = 'food_modification';
        }

        // Build the full details payload that admin will review
        $details = [
            'check_in'          => $newCheckIn,
            'check_out'         => $newCheckOut,
            'original_check_in' => $origCheckIn,   // saved so rejection can revert the pre-applied dates
            'original_check_out'=> $origCheckOut,
            'pax'               => $request->input('pax'),
            'purpose'           => $request->input('purpose'),
            'notes'             => $request->input('notes'),
            'accommodation_id'  => $request->input('accommodation_id'),
            'type'              => $type,
        ];

        if ($type === 'venue') {
            $details['food_selections']    = $request->input('food_selections',    []);
            $details['food_set_selection'] = $request->input('food_set_selection', []);
            $details['food_enabled']       = $request->input('food_enabled',       []);
            $details['meal_enabled']       = $request->input('meal_enabled',       []);
            $details['meal_mode']          = $request->input('meal_mode',          []);
        }

        // Capture food inputs for use inside the closure
        $foodSelections   = $request->input('food_selections',    []);
        $foodSetSelection = $request->input('food_set_selection', []);
        $foodEnabledMap   = $request->input('food_enabled',       []);
        $mealModeMap      = $request->input('meal_mode',          []);

        return DB::transaction(function () use (
            $reservation, $type, $reqType, $details,
            $newCheckIn, $newCheckOut,
            $foodSelections, $foodSetSelection, $foodEnabledMap, $mealModeMap
        ) {
            // Re-check inside transaction to prevent races
            $reservation->refresh();

            if ($reservation->change_request_status === 'pending') {
                return redirect()->route('client.my_reservations')
                    ->with('error', 'You already have a pending Request for Changes for this reservation.');
            }

            // ── Apply date changes immediately ──────────────────────────────────
            if ($newCheckIn && $newCheckOut) {
                if ($type === 'room') {
                    $reservation->Room_Reservation_Check_In_Time  = $newCheckIn;
                    $reservation->Room_Reservation_Check_Out_Time = $newCheckOut;
                } else {
                    $reservation->Venue_Reservation_Check_In_Time  = $newCheckIn;
                    $reservation->Venue_Reservation_Check_Out_Time = $newCheckOut;
                }
            }

            // ── Apply food changes immediately (venue only) ─────────────────────
            if ($type === 'venue') {
                $pax = (int) ($reservation->Venue_Reservation_Pax ?? 1);

                // Delete old food records
                \App\Models\FoodReservation::where(
                    'Venue_Reservation_ID', $reservation->Venue_Reservation_ID
                )->delete();

                // Build skip-list: gen_{setId} slots and spiritual meal keys are
                // handled by the set loop below, not the individual loop.
                $skipMealKeys = [];
                foreach ($foodSetSelection as $_d => $_meals) {
                    foreach ((array) $_meals as $_mk => $_ids) {
                        $isArr  = is_array($_ids);
                        $rawIds = $isArr ? $_ids : [$_ids];
                        foreach ($rawIds as $_id) {
                            if (!empty($_id)) {
                                $skipMealKeys[$_d]["gen_{$_id}"] = true;
                            }
                        }
                        if (!$isArr && !empty($_ids)) {
                            $skipMealKeys[$_d][$_mk] = true;
                        }
                    }
                }

                // ── STEP B: individual food selections ──────────────────────────
                if (!empty($foodSelections)) {
                    $extractFoodIds = function ($data) use (&$extractFoodIds) {
                        $ids = [];
                        if (is_array($data)) {
                            foreach ($data as $k => $v) {
                                if ($k === 'drink_choice') continue;
                                if ($k === '_tier') continue; // buffet tier price, not a Food_ID
                                $ids = array_merge($ids, $extractFoodIds($v));
                            }
                        } elseif (is_numeric($data) && !empty($data)) {
                            $ids[] = (int) $data;
                        }
                        return $ids;
                    };

                    foreach ($foodSelections as $date => $meals) {
                        foreach ($meals as $mealType => $mealData) {
                            if (empty($mealData)) continue;
                            if (!empty($skipMealKeys[$date][$mealType])) continue;

                            // ── Buffet branch ──────────────────────────────────
                            if (($mealModeMap[$date][$mealType] ?? '') === 'buffet') {
                                $tier    = (int)(is_array($mealData) ? ($mealData['_tier'] ?? 350) : 350);
                                $foodIds = $extractFoodIds($mealData);
                                foreach ($foodIds as $foodId) {
                                    $food = Food::find($foodId);
                                    if ($food) {
                                        FoodReservation::create([
                                            'Food_ID'                       => $foodId,
                                            'Venue_Reservation_ID'          => $reservation->Venue_Reservation_ID,
                                            'Client_ID'                     => $reservation->Client_ID,
                                            'Food_Reservation_Serving_Date' => $date,
                                            'Food_Reservation_Meal_time'    => $mealType,
                                            'Food_Reservation_Total_Price'  => 0,
                                        ]);
                                    }
                                }
                                // One price record carrying the flat-rate tier
                                FoodReservation::create([
                                    'Food_ID'                       => null,
                                    'Food_Set_ID'                   => "buffet:{$tier}",
                                    'Venue_Reservation_ID'          => $reservation->Venue_Reservation_ID,
                                    'Client_ID'                     => $reservation->Client_ID,
                                    'Food_Reservation_Serving_Date' => $date,
                                    'Food_Reservation_Meal_time'    => $mealType,
                                    'Food_Reservation_Total_Price'  => $tier * $pax,
                                ]);
                                continue;
                            }

                            $foodIds = $extractFoodIds($mealData);
                            foreach ($foodIds as $foodId) {
                                $food = Food::find($foodId);
                                if ($food) {
                                    FoodReservation::create([
                                        'Food_ID'                       => $foodId,
                                        'Venue_Reservation_ID'          => $reservation->Venue_Reservation_ID,
                                        'Client_ID'                     => $reservation->Client_ID,
                                        'Food_Reservation_Serving_Date' => $date,
                                        'Food_Reservation_Meal_time'    => $mealType,
                                        'Food_Reservation_Total_Price'  => ($food->Food_Price ?? 0) * $pax,
                                    ]);
                                }
                            }
                        }
                    }
                }

                // ── STEP C: food SET selections ─────────────────────────────────
                if (!empty($foodSetSelection)) {
                    foreach ($foodSetSelection as $date => $meals) {
                        if (($foodEnabledMap[$date] ?? '1') != '1') continue;

                        foreach ((array) $meals as $mealKey => $setIdOrIds) {
                            $isGeneralSet = is_array($setIdOrIds);
                            $setIds       = $isGeneralSet ? $setIdOrIds : [$setIdOrIds];

                            foreach ($setIds as $setId) {
                                if (empty($setId)) continue;

                                $set = FoodSet::find((int) $setId);
                                if (!$set) continue;

                                if ($isGeneralSet) {
                                    $genKey    = "gen_{$setId}";
                                    $genSel    = $foodSelections[$date][$genKey] ?? [];
                                    $riceId    = (string) ($genSel['rice']    ?? '');
                                    $dessertId = (string) ($genSel['dessert'] ?? '');
                                    $fruitId   = '';

                                    $drinkVal = $genSel['drink'] ?? ($genSel['drink_choice'] ?? '');
                                    if (is_numeric($drinkVal) && !empty($drinkVal)) {
                                        $drinksId = (string) $drinkVal;
                                    } elseif (!empty($drinkVal)) {
                                        $drinkTxt  = strtolower(trim((string) $drinkVal));
                                        $drinkFood = Food::where(function ($q) use ($drinkTxt) {
                                            $q->where('Food_Name', 'ILIKE', $drinkTxt . '%')
                                              ->orWhere('Food_Name', 'ILIKE', '%' . $drinkTxt . '%');
                                        })->first();
                                        $drinksId = $drinkFood ? (string) $drinkFood->Food_ID : '';
                                    } else {
                                        $drinksId = '';
                                    }
                                } else {
                                    $mealSel   = $foodSelections[$date][$mealKey] ?? [];
                                    $riceId    = (string) ($mealSel['rice_type']  ?? '');
                                    $drinksId  = (string) ($mealKey === 'breakfast'
                                        ? ($mealSel['hot_drink']  ?? '')
                                        : ($mealSel['softdrinks'] ?? ''));
                                    $dessertId = (string) ($mealSel['dessert'] ?? '');
                                    $fruitId   = (string) ($mealSel['fruits']  ?? '');
                                }

                                $customIds     = [$riceId, $drinksId, $dessertId, $fruitId];
                                $foodSetIdText = '"' . $setId . '",' . json_encode($customIds);

                                FoodReservation::create([
                                    'Food_ID'                       => null,
                                    'Food_Set_ID'                   => $foodSetIdText,
                                    'Venue_Reservation_ID'          => $reservation->Venue_Reservation_ID,
                                    'Client_ID'                     => $reservation->Client_ID,
                                    'Food_Reservation_Serving_Date' => $date,
                                    'Food_Reservation_Meal_time'    => $mealKey,
                                    'Food_Reservation_Total_Price'  => (float) ($set->Food_Set_Price ?? 0) * $pax,
                                ]);
                            }
                        }
                    }
                }
            }

            // ── Mark as pending change request (admin reviews) ──────────────────
            $reservation->change_request_status       = 'pending';
            $reservation->change_request_type         = $reqType;
            $reservation->change_request_reason       = null;
            $reservation->change_request_details      = $details;
            $reservation->change_request_admin_note   = null;
            $reservation->change_request_processed_by = null;
            $reservation->change_request_requested_at = Carbon::now();
            $reservation->change_request_processed_at = null;
            $reservation->save();

            // ── Notify all admin and staff (in-system + email to staff only) ────
            $clientName    = auth()->user()?->Account_Name ?? 'A client';
            $accName       = $type === 'room'
                ? 'Room ' . ($reservation->room?->Room_Number ?? $reservation->getKey())
                : ($reservation->venue?->Venue_Name ?? 'Venue');

            $staffAccounts = Account::whereIn('Account_Role', ['admin', 'staff'])->get();

            $staffAccounts->each(function ($staff) use ($clientName, $accName) {
                // In-system bell notification for all admin + staff
                EventLog::create([
                    'user_id'                       => auth()->id(),
                    'Event_Logs_Notifiable_User_ID' => $staff->Account_ID,
                    'Event_Logs_Action'             => 'change_request_submitted',
                    'Event_Logs_Title'              => 'Request for Changes Submitted',
                    'Event_Logs_Message'            => "{$clientName} has submitted a request for changes for {$accName}.",
                    'Event_Logs_Type'               => 'warning',
                    'Event_Logs_Link'               => '/employee/reservations',
                    'Event_Logs_isRead'             => false,
                ]);
            });

            // Email notification to staff only
            $staffAccounts->where('Account_Role', 'staff')->each(function ($staff) use ($reservation, $type, $clientName, $accName, $reqType) {
                if (!$staff->Account_Email) {
                    Log::warning('ChangeRequestSubmittedMail skipped: staff has no email', ['staff_id' => $staff->Account_ID]);
                    return;
                }
                try {
                    Mail::to($staff->Account_Email)
                        ->send(new ChangeRequestSubmittedMail($reservation, $type, $clientName, $accName, $reqType));
                } catch (\Exception $e) {
                    Log::error('ChangeRequestSubmittedMail to staff failed', [
                        'staff_id' => $staff->Account_ID,
                        'error'    => $e->getMessage(),
                    ]);
                }
            });

            session()->forget(['change_request_reservation_id', 'change_request_reservation_type']);

            return redirect()->route('client.my_reservations')
                ->with('success', 'Your Request for Changes has been submitted. Our team will review it shortly.');
        });
    }

    /**
     * Reconstruct the food-edit session keys from an existing venue reservation's
     * FoodReservation rows so that food_option.blade.php can pre-fill the UI.
     *
     * Session keys written (matching what editCartItem() writes):
     *   edit_food_selections  — food_selections[date][meal][slot] = food_id
     *   edit_food_enabled     — food_enabled[date] = '1'
     *   edit_meal_enabled     — meal_enabled[date][meal] = '1'
     *   edit_set_selections   — food_set_selection[date][meal] = setId (spiritual)
     *                           food_set_selection[date][meal][] = setId (general)
     *   edit_meal_mode        — meal_mode[date][meal] = 'set'|'individual'
     */
    private function buildFoodEditSession(\App\Models\VenueReservation $reservation, string $purpose): void
    {
        $isSpiritual = in_array(strtolower($purpose), ['retreat', 'recollection']);

        $foodSelections   = [];
        $foodEnabled      = [];
        $mealEnabled      = [];
        $foodSetSelection = [];
        $mealMode         = [];

        // ── SET-based food reservations ─────────────────────────────────────────
        foreach ($reservation->foodSetReservations()->get() as $row) {
            $date    = $row->Food_Reservation_Serving_Date;
            $mealKey = $row->Food_Reservation_Meal_time;

            // ── Buffet flat-rate record ──────────────────────────────────────────
            if (preg_match('/^buffet:(\d+)$/', $row->Food_Set_ID ?? '', $bm)) {
                $tier = (int) $bm[1];
                $foodEnabled[$date]           = '1';
                $mealEnabled[$date][$mealKey] = '1';
                $mealMode[$date][$mealKey]    = 'buffet';
                $foodSelections[$date][$mealKey]['_tier'] = (string) $tier;
                continue;
            }

            $parsed    = $row->parseFoodSetId();
            $setId     = $parsed['set_id'];
            $customIds = $parsed['custom_ids']; // [riceId, drinksId, dessertId, fruitId]

            if (!$setId) continue;

            $foodEnabled[$date]           = '1';
            $mealEnabled[$date][$mealKey] = '1';
            $mealMode[$date][$mealKey]    = 'set';

            if ($isSpiritual) {
                // Spiritual: one set per meal, customisations stored under the mealKey
                $foodSetSelection[$date][$mealKey] = (string) $setId;

                $riceId    = $customIds[0] ?? '';
                $drinksId  = $customIds[1] ?? '';
                $dessertId = $customIds[2] ?? '';
                $fruitId   = $customIds[3] ?? '';

                $foodSelections[$date][$mealKey]['rice_type'] = $riceId;
                $foodSelections[$date][$mealKey]['dessert']   = $dessertId;
                $foodSelections[$date][$mealKey]['fruits']    = $fruitId;

                if ($mealKey === 'breakfast') {
                    $foodSelections[$date][$mealKey]['hot_drink']  = $drinksId;
                } else {
                    $foodSelections[$date][$mealKey]['softdrinks'] = $drinksId;
                }
            } else {
                // General: multiple sets per meal, customisations stored under gen_{setId}
                if (!isset($foodSetSelection[$date][$mealKey])) {
                    $foodSetSelection[$date][$mealKey] = [];
                }
                $foodSetSelection[$date][$mealKey][] = (string) $setId;

                $riceId    = $customIds[0] ?? '';
                $drinksId  = $customIds[1] ?? '';
                $dessertId = $customIds[2] ?? '';

                // Store drink as Food_ID directly — matches what restoreGeneralSets() in JS expects
                $genKey = "gen_{$setId}";
                $foodSelections[$date][$genKey]['rice']    = $riceId;
                $foodSelections[$date][$genKey]['dessert'] = $dessertId;
                $foodSelections[$date][$genKey]['drink']   = $drinksId;
            }
        }

        // ── INDIVIDUAL food reservations ────────────────────────────────────────
        foreach ($reservation->foods()->get() as $food) {
            $date    = $food->pivot->Food_Reservation_Serving_Date;
            $mealKey = $food->pivot->Food_Reservation_Meal_time;
            $foodId  = (string) $food->Food_ID;
            $cat     = strtolower($food->Food_Category ?? '');

            $foodEnabled[$date]           = '1';
            $mealEnabled[$date][$mealKey] = '1';
            // Only set to individual if not already marked as set/buffet for this slot
            if (!isset($mealMode[$date][$mealKey])) {
                $mealMode[$date][$mealKey] = 'individual';
            }

            // ── Buffet meal: store viands under category-keyed slots ─────────
            // Buffet viands are stored at ₱0 pivot price; their category identifies
            // which dropdown slot they belong to (meatviand1/2/3/4, noodleviand, veggieviand).
            if (($mealMode[$date][$mealKey] ?? '') === 'buffet') {
                if ($cat === 'meatviand') {
                    // Find next available meatviandN slot
                    $idx = 1;
                    while (!empty($foodSelections[$date][$mealKey]["meatviand{$idx}"])) {
                        $idx++;
                    }
                    $foodSelections[$date][$mealKey]["meatviand{$idx}"] = $foodId;
                } elseif ($cat === 'noodleviand') {
                    $foodSelections[$date][$mealKey]['noodleviand'] = $foodId;
                } elseif ($cat === 'veggieviand') {
                    $foodSelections[$date][$mealKey]['veggieviand'] = $foodId;
                } elseif (in_array($cat, ['dessert', 'desserts', 'fruit', 'fruits'])) {
                    $foodSelections[$date][$mealKey]['dessert'] = $foodId;
                }
                continue;
            }

            // Snack meal keys use a fixed session key regardless of Food_Category
            if (in_array($mealKey, ['am_snack', 'pm_snack'])) {
                // Spiritual snack: single-select stored as food_selections[date][am_snack|pm_snack][snacks]
                $foodSelections[$date][$mealKey]['snacks'] = $foodId;
            } elseif ($mealKey === 'snacks') {
                // General / individual snack: multi-select stored as food_selections[date][snacks][]
                $foodSelections[$date]['snacks'][] = $foodId;
            } elseif ($cat === 'rice') {
                $foodSelections[$date][$mealKey]['rice'] = $foodId;
            } elseif (in_array($cat, ['viand', 'side dish', 'sides', 'main'])) {
                if (empty($foodSelections[$date][$mealKey]['viand1'])) {
                    $foodSelections[$date][$mealKey]['viand1'] = $foodId;
                } elseif (empty($foodSelections[$date][$mealKey]['viand2'])) {
                    $foodSelections[$date][$mealKey]['viand2'] = $foodId;
                } else {
                    $foodSelections[$date][$mealKey]['extra_viands'][] = $foodId;
                }
            } elseif (in_array($cat, ['drinks', 'drink'])) {
                $foodSelections[$date][$mealKey]['drink'] = $foodId;
            } elseif (in_array($cat, ['dessert', 'desserts'])) {
                $foodSelections[$date][$mealKey]['desserts'][] = $foodId;
            } else {
                // Fallback: extra viand
                $foodSelections[$date][$mealKey]['extra_viands'][] = $foodId;
            }
        }

        session([
            'edit_food_selections' => $foodSelections,
            'edit_food_enabled'    => $foodEnabled,
            'edit_meal_enabled'    => $mealEnabled,
            'edit_set_selections'  => $foodSetSelection,
            'edit_meal_mode'       => $mealMode,
        ]);
    }

    /**
     * ADMIN/STAFF: Get the change request data for a specific reservation.
     *
     * GET /employee/reservations/{id}/change-request?type=room|venue
     */
    public function getChangeRequest(Request $request, $id)
    {
        $type = $request->query('type');

        if (!in_array($type, ['room', 'venue'])) {
            return response()->json(['error' => 'Invalid type.'], 422);
        }

        try {
            $reservation = ($type === 'room')
                ? \App\Models\RoomReservation::findOrFail($id)
                : \App\Models\VenueReservation::findOrFail($id);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['request' => null]);
        }

        if (!$reservation->change_request_status) {
            return response()->json(['request' => null]);
        }

        $pkCol = $type === 'room' ? 'Room_Reservation_ID' : 'Venue_Reservation_ID';

        return response()->json([
            'request' => [
                'id'           => $reservation->$pkCol,
                'status'       => $reservation->change_request_status,
                'request_type' => $reservation->change_request_type,
                'reason'       => $reservation->change_request_reason,
                'details'      => $reservation->change_request_details,
                'admin_note'   => $reservation->change_request_admin_note,
                'created_at'   => $reservation->change_request_requested_at
                                      ? Carbon::parse($reservation->change_request_requested_at)->format('M d, Y h:i A')
                                      : null,
            ],
        ]);
    }

    /**
     * ADMIN/STAFF: Approve or reject a Request for Changes.
     *
     * POST /employee/change-requests/{reservationId}/process
     * Body: decision=approved|rejected, res_type=room|venue, admin_note (optional)
     *
     * On approval, the new check-in / check-out dates from change_request_details
     * are applied to the reservation row.  Food changes are noted in the admin_note
     * for manual processing.
     */
    public function processChangeRequest(Request $request, $reservationId)
    {
        $request->validate([
            'decision'   => 'required|in:approved,rejected',
            'res_type'   => 'required|in:room,venue',
            'admin_note' => 'nullable|string|max:500',
        ]);

        $type     = $request->input('res_type');
        $decision = $request->input('decision');

        try {
            $reservation = ($type === 'room')
                ? \App\Models\RoomReservation::findOrFail($reservationId)
                : \App\Models\VenueReservation::findOrFail($reservationId);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Reservation not found.'], 404);
        }

        if ($reservation->change_request_status !== 'pending') {
            return response()->json(['success' => false, 'message' => 'This request has already been processed.'], 422);
        }

        DB::transaction(function () use ($reservation, $type, $decision, $request) {
            $reservation->change_request_status       = $decision;
            $reservation->change_request_admin_note   = $request->input('admin_note');
            $reservation->change_request_processed_by = auth()->id();
            $reservation->change_request_processed_at = Carbon::now();

            $details = $reservation->change_request_details ?? [];
            $reqType = $reservation->change_request_type ?? '';

            if ($decision === 'approved') {
                // Apply the new dates (they were already pre-written by storeChangeRequest,
                // but we re-apply here so approval is the source of truth)
                if (in_array($reqType, ['reschedule', 'reschedule_and_food']) && !empty($details['check_in']) && !empty($details['check_out'])) {
                    $newCheckIn  = Carbon::parse($details['check_in']);
                    $newCheckOut = Carbon::parse($details['check_out']);

                    if ($type === 'room') {
                        $reservation->Room_Reservation_Check_In_Time  = $details['check_in'];
                        $reservation->Room_Reservation_Check_Out_Time = $details['check_out'];

                        // Recalculate accommodation total for the new date range
                        $newNights   = $newCheckIn->diffInDays($newCheckOut) ?: 1;
                        $reservation->load('room', 'user');
                        $clientType  = $reservation->user?->Account_Type ?? 'External';
                        $baseRate    = ($clientType === 'Internal')
                            ? ($reservation->room?->Room_Internal_Price  ?? 0)
                            : ($reservation->room?->Room_External_Price  ?? 0);
                        $reservation->Room_Reservation_Total_Price = $baseRate * $newNights;
                    } else {
                        $reservation->Venue_Reservation_Check_In_Time  = $details['check_in'];
                        $reservation->Venue_Reservation_Check_Out_Time = $details['check_out'];

                        // Recalculate accommodation total for the new date range
                        $newDays     = $newCheckIn->diffInDays($newCheckOut) + 1;
                        $reservation->load('venue', 'user');
                        $clientType  = $reservation->user?->Account_Type ?? 'External';
                        $baseRate    = ($clientType === 'Internal')
                            ? ($reservation->venue?->Venue_Internal_Price ?? 0)
                            : ($reservation->venue?->Venue_External_Price ?? 0);
                        $reservation->Venue_Reservation_Total_Price = $baseRate * $newDays;
                    }
                }
                // Apply updated notes if the client changed them
                if (isset($details['notes'])) {
                    if ($type === 'room') {
                        $reservation->Room_Reservation_Notes = $details['notes'];
                    } else {
                        $reservation->Venue_Reservation_Notes = $details['notes'];
                    }
                }
                // Food modifications are shown in the details for manual admin action
                // (food reservation rows require the full food booking logic to update)
            }

            // On rejection, revert the dates that storeChangeRequest pre-applied
            if ($decision === 'rejected') {
                if (in_array($reqType, ['reschedule', 'reschedule_and_food']) && !empty($details['original_check_in']) && !empty($details['original_check_out'])) {
                    if ($type === 'room') {
                        $reservation->Room_Reservation_Check_In_Time  = $details['original_check_in'];
                        $reservation->Room_Reservation_Check_Out_Time = $details['original_check_out'];
                    } else {
                        $reservation->Venue_Reservation_Check_In_Time  = $details['original_check_in'];
                        $reservation->Venue_Reservation_Check_Out_Time = $details['original_check_out'];
                    }
                }
            }

            $reservation->save();
        });

        // Load relationships needed for email/notification
        $reservation->load('user');
        if ($type === 'room') $reservation->load('room');
        else                  $reservation->load('venue');

        $accName = $type === 'room'
            ? 'Room ' . ($reservation->room?->Room_Number ?? $reservationId)
            : ($reservation->venue?->Venue_Name ?? 'Venue');

        // ── Send email to client ────────────────────────────────────────────
        $clientEmail = $reservation->user?->Account_Email;
        if ($clientEmail) {
            try {
                Mail::to($clientEmail)
                    ->send(new ChangeRequestProcessedMail(
                        $reservation,
                        $type,
                        $decision,
                        $request->input('admin_note')
                    ));
            } catch (\Exception $e) {
                Log::error('ChangeRequestProcessedMail failed', [
                    'reservation_id' => $reservationId,
                    'type'           => $type,
                    'decision'       => $decision,
                    'error'          => $e->getMessage(),
                ]);
            }
        } else {
            Log::warning('ChangeRequestProcessedMail skipped: reservation has no associated user or email', [
                'reservation_id' => $reservationId,
            ]);
        }

        // ── In-system notification to the client ───────────────────────────
        if ($decision === 'approved') {
            $notifTitle   = 'Request for Changes Approved';
            $notifMessage = "Your request for changes for {$accName} has been approved.";
            $notifType    = 'success';
        } else {
            $notifTitle   = 'Request for Changes Rejected';
            $notifMessage = "Your request for changes for {$accName} has been rejected. Your reservation remains as originally confirmed.";
            $notifType    = 'error';
        }

        try {
            EventLog::create([
                'user_id'                       => auth()->id(),
                'Event_Logs_Notifiable_User_ID' => $reservation->Client_ID,
                'Event_Logs_Action'             => 'change_request_' . $decision,
                'Event_Logs_Title'              => $notifTitle,
                'Event_Logs_Message'            => $notifMessage,
                'Event_Logs_Type'               => $notifType,
                'Event_Logs_Link'               => '/client/my_reservations',
                'Event_Logs_isRead'             => false,
            ]);
        } catch (\Exception $e) {
            \Log::error('EventLog::create failed in processChangeRequest: ' . $e->getMessage());
        }

        $label = $decision === 'approved' ? 'approved' : 'rejected';

        return response()->json([
            'success'  => true,
            'message'  => "Request for Changes {$label} successfully.",
            'decision' => $decision,
        ]);
    }

    /**
     * ADMIN/STAFF: Get the cancellation request data for a specific reservation.
     *
     * GET /employee/reservations/{id}/cancellation-request?type=room|venue
     * Reads directly from the reservation row — no separate table.
     */
    public function getCancellationRequest(Request $request, $id)
    {
        $type = $request->query('type');

        if (!in_array($type, ['room', 'venue'])) {
            return response()->json(['error' => 'Invalid type.'], 422);
        }

        try {
            $reservation = ($type === 'room')
                ? \App\Models\RoomReservation::findOrFail($id)
                : \App\Models\VenueReservation::findOrFail($id);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['request' => null]);
        }

        if (!$reservation->cancellation_status) {
            return response()->json(['request' => null]);
        }

        $pkCol = $type === 'room' ? 'Room_Reservation_ID' : 'Venue_Reservation_ID';

        return response()->json([
            'request' => [
                // 'id' here is the reservation ID — used by JS when calling processCancellation
                'id'         => $reservation->$pkCol,
                'status'     => $reservation->cancellation_status,
                'reason'     => $reservation->cancellation_reason,
                'admin_note' => $reservation->cancellation_admin_note,
                'created_at' => $reservation->cancellation_requested_at
                                    ? \Carbon\Carbon::parse($reservation->cancellation_requested_at)->format('M d, Y h:i A')
                                    : null,
            ],
        ]);
    }

    /**
     * CLIENT: Check the cancellation request status for their own reservation.
     *
     * GET /client/reservations/{id}/cancellation-status?type=room|venue
     */
    public function getClientCancellationStatus(Request $request, $id)
    {
        $type = $request->query('type');

        if (!in_array($type, ['room', 'venue'])) {
            return response()->json(['request' => null]);
        }

        try {
            $reservation = ($type === 'room')
                ? \App\Models\RoomReservation::findOrFail($id)
                : \App\Models\VenueReservation::findOrFail($id);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['request' => null]);
        }

        // Only return data for this client's own reservation
        if ($reservation->Client_ID !== auth()->id()) {
            return response()->json(['request' => null]);
        }

        if (!$reservation->cancellation_status) {
            return response()->json(['request' => null]);
        }

        $pkCol = $type === 'room' ? 'Room_Reservation_ID' : 'Venue_Reservation_ID';

        return response()->json([
            'request' => [
                'id'         => $reservation->$pkCol,
                'status'     => $reservation->cancellation_status,
                'admin_note' => $reservation->cancellation_admin_note,
                'created_at' => $reservation->cancellation_requested_at
                                    ? \Carbon\Carbon::parse($reservation->cancellation_requested_at)->format('M d, Y h:i A')
                                    : null,
            ],
        ]);
    }

    /**
     * ADMIN/STAFF: Approve or reject a cancellation request.
     *
     * POST /employee/cancellation-requests/{reservationId}/process
     * Body: decision=approved|rejected, res_type=room|venue, admin_note (optional)
     *
     * {reservationId} is the Room_Reservation_ID or Venue_Reservation_ID.
     * Cancellation state lives on the reservation row — no separate table.
     */
    public function processCancellation(Request $request, $reservationId)
    {
        $request->validate([
            'decision'   => 'required|in:approved,rejected',
            'res_type'   => 'required|in:room,venue',
            'admin_note' => 'nullable|string|max:500',
        ]);

        $type     = $request->input('res_type');
        $decision = $request->input('decision');

        try {
            $reservation = ($type === 'room')
                ? \App\Models\RoomReservation::findOrFail($reservationId)
                : \App\Models\VenueReservation::findOrFail($reservationId);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Reservation not found.'], 404);
        }

        if ($reservation->cancellation_status !== 'pending') {
            return response()->json(['success' => false, 'message' => 'This request has already been processed.'], 422);
        }

        DB::transaction(function () use ($reservation, $type, $decision, $request) {
            $reservation->cancellation_status       = $decision;
            $reservation->cancellation_admin_note   = $request->input('admin_note');
            $reservation->cancellation_processed_by = auth()->id();
            $reservation->cancellation_processed_at = now();

            // If approved, mark the reservation as cancelled
            if ($decision === 'approved') {
                $statusCol = $type === 'room' ? 'Room_Reservation_Status' : 'Venue_Reservation_Status';
                $reservation->$statusCol = 'cancelled';
            }

            $reservation->save();
        });

        // Load relationships needed for email/notification
        $reservation->load('user');
        if ($type === 'room') $reservation->load('room');
        else                  $reservation->load('venue');

        $accName = $type === 'room'
            ? 'Room ' . ($reservation->room?->Room_Number ?? $reservationId)
            : ($reservation->venue?->Venue_Name ?? 'Venue');

        // ── Send email to client ────────────────────────────────────────────
        $clientEmail = $reservation->user?->Account_Email;
        if ($clientEmail) {
            try {
                if ($decision === 'approved') {
                    Mail::to($clientEmail)
                        ->send(new CancellationApprovedMail(
                            $reservation,
                            $type,
                            $request->input('admin_note')
                        ));
                } else {
                    Mail::to($clientEmail)
                        ->send(new CancellationRejectedMail(
                            $reservation,
                            $type,
                            $request->input('admin_note')
                        ));
                }
            } catch (\Exception $e) {
                Log::error('CancellationMail failed', [
                    'reservation_id' => $reservationId,
                    'type'           => $type,
                    'decision'       => $decision,
                    'error'          => $e->getMessage(),
                ]);
            }
        } else {
            Log::warning('CancellationMail skipped: reservation has no associated user or email', [
                'reservation_id' => $reservationId,
            ]);
        }

        // ── In-system notification to the client ───────────────────────────
        if ($decision === 'approved') {
            $notifTitle   = 'Cancellation Request Approved';
            $notifMessage = "Your cancellation request for {$accName} has been approved. Your reservation is now cancelled.";
            $notifType    = 'success';
        } else {
            $notifTitle   = 'Cancellation Request Rejected';
            $notifMessage = "Your cancellation request for {$accName} has been rejected. Your reservation remains active.";
            $notifType    = 'error';
        }

        EventLog::create([
            'user_id'                       => auth()->id(),
            'Event_Logs_Notifiable_User_ID' => $reservation->Client_ID,
            'Event_Logs_Action'             => 'cancellation_' . $decision,
            'Event_Logs_Title'              => $notifTitle,
            'Event_Logs_Message'            => $notifMessage,
            'Event_Logs_Type'               => $notifType,
            'Event_Logs_Link'               => '/client/my_reservations',
            'Event_Logs_isRead'             => false,
        ]);

        $label = $decision === 'approved' ? 'approved and reservation cancelled' : 'rejected';

        return response()->json([
            'success'  => true,
            'message'  => "Cancellation request {$label} successfully.",
            'decision' => $decision,
        ]);
    }
}
