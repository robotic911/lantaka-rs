<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RoomReservation;
use App\Models\VenueReservation;
use App\Models\Room;
use App\Models\Venue;
use App\Models\Food;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class ReservationController extends Controller
{
    // 1. Show Checkout Page
    public function checkout(Request $request)
    {
        $allBookings = session('pending_bookings', []);

        if ($request->has('accommodation_id')) {
            $newEntry = $request->all();
            $uniqueKey = $newEntry['type'] . '_' . $newEntry['accommodation_id'];
            $allBookings[$uniqueKey] = $newEntry;
            session(['pending_bookings' => $allBookings]);
        }

        $processedItems = [];
        $grandTotal = 0;

        foreach ($allBookings as $key => $item) {
            $checkIn = Carbon::parse($item['check_in']);
            $checkOut = Carbon::parse($item['check_out']);
            $days = $checkIn->diffInDays($checkOut) ?: 1;

            if ($item['type'] === 'room') {
                $model = Room::find($item['accommodation_id']);
                // Use the pricing field from your Room model
                $price = $model->Room_Pricing ?? $model->price;
                $name = "Room " . $model->Room_Number;
                $img = $model->Room_Image;
            } else {
                $model = Venue::find($item['accommodation_id']);
                $price = $model->Venue_Pricing ?? $model->price;
                $name = $model->Venue_Name;
                $img = $model->Venue_Image;
            }

            if ($model) {
                $accommodationTotal = $price * $days;
                $foodTotal = 0;
                $selectedFoods = [];

                if ($item['type'] === 'venue' && !empty($item['selected_foods'])) {
                    $selectedFoods = Food::whereIn('food_id', $item['selected_foods'])->get();
                    $foodTotal = $selectedFoods->sum('Food_Price') * $item['pax'];
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
                    'pax' => $item['pax'], // Ensure pax is passed
                    'check_in' => $checkIn->format('F d, Y'),
                    'check_out' => $checkOut->format('F d, Y'),
                    
                    // ADD THESE TWO LINES
                    'check_in_raw' => $checkIn->toDateString(), 
                    'check_out_raw' => $checkOut->toDateString(),
                    'days' => $days > 0 ? $days : 1,
                    'total' => $itemTotal,
                    'selected_foods' => $selectedFoods ?? []
                ];
            }
        }
        return view('client.my_bookings', compact('processedItems', 'grandTotal'));
    }

    // 2. Store Reservation (Confirm)
    public function store(Request $request)
    {
        $request->validate([
            'id' => 'required',
            'type' => 'required|in:room,venue',
            'check_in' => 'required|date',
            'check_out' => 'required|date',
            'pax' => 'required|integer',
            'total_amount' => 'required|numeric',
        ]);

        try {
            if ($request->type === 'room') {
                $reservation = RoomReservation::create([
                    'room_id' => $request->id,
                    'Client_ID' => Auth::id(),
                    'Room_Reservation_Date' => now(),
                    'Room_Reservation_Check_In_Time' => $request->check_in,
                    'Room_Reservation_Check_Out_Time' => $request->check_out,
                    'pax' => $request->pax,
                    'Room_Reservation_Total_Price' => $request->total_amount,
                    'status' => 'pending'
                ]);
            } else {
                $reservation = VenueReservation::create([
                    'venue_id' => $request->id,
                    'Client_ID' => Auth::id(),
                    'Venue_Reservation_Date' => now(),
                    'Venue_Reservation_Check_In_Time' => $request->check_in,
                    'Venue_Reservation_Check_Out_Time' => $request->check_out,
                    'pax' => $request->pax,
                    'Venue_Reservation_Total_Price' => $request->total_amount,
                    'status' => 'pending'
                ]);

                // Handle Food Pivot for Venues
                $uniqueKey = 'venue_' . $request->id;
                $allBookings = session('pending_bookings', []);
                $bookingData = $allBookings[$uniqueKey] ?? null;

                if ($bookingData && !empty($bookingData['selected_foods'])) {
                    $attachData = [];
                    foreach ($bookingData['selected_foods'] as $fId) {
                        $food = Food::find($fId);
                        if ($food) {
                            $attachData[$fId] = [
                                'serving_time' => now(), 
                                // Check if your column is Food_Price or food_price
                                'total_price'  => ($food->Food_Price ?? $food->food_price) * $request->pax,
                                'status'       => 'pending'
                            ];
                        }
                    }
                    $reservation->foods()->attach($attachData);
                }
            }

            // Send Email (Wrapped in try-catch so failure doesn't stop the redirect)
            try {
                Mail::to(auth()->user()->email)->send(new \App\Mail\ReservationConfirmationMail($reservation));
            } catch (\Exception $e) {
                \Log::error("Mail failed: " . $e->getMessage());
            }

            // Clear only this item from session
            $allBookings = session('pending_bookings', []);
            unset($allBookings[$request->type . '_' . $request->id]);
            session(['pending_bookings' => $allBookings]);

            return redirect()->route('client.my_reservations')->with('success', 'Reservation confirmed!');

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
        $roomQuery = \App\Models\RoomReservation::with(['room', 'user']);

        // 2. Query Venue Reservations (Global for Employees)
        $venueQuery = \App\Models\VenueReservation::with(['venue', 'user', 'foods']);

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
                $date = match($dateFilter) {
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
                $query->where(function($q) use ($search) {
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
        $rooms = ($accType === 'venue') ? collect() : $roomQuery->get()->map(function($item) {
            $item->display_type = 'room';
            $item->type = 'room';
            return $item;
        });

        $venues = ($accType === 'room') ? collect() : $venueQuery->get()->map(function($item) {
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
            $days = match($dateFilter) {
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
            $roomQuery->where(function($q) use ($search) {
                $q->whereHas('user', fn($uq) => $uq->where('name', 'ILIKE', "%$search%"))
                ->orWhereHas('room', fn($rq) => $rq->where('room_number', 'ILIKE', "%$search%"))
                // Use whereRaw to cast BigInt to Text for comparison
                ->orWhereRaw('CAST("Room_Reservation_ID" AS TEXT) ILIKE ?', ["%$search%"]);
            });

            // Search Venues
            $venueQuery->where(function($q) use ($search) {
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
            $rooms = $roomQuery->get()->map(function($item) { 
                $item->display_type = 'room'; 
                $item->type = 'room'; // Helper for the Blade
                return $item; 
            });
        }

        if (!$accType || $accType === 'venue') {
            $venues = $venueQuery->get()->map(function($item) { 
                $item->display_type = 'venue'; 
                $item->type = 'venue'; // Helper for the Blade
                return $item; 
            });
        }

        $reservations = $rooms->concat($venues)->sortByDesc('created_at');
        
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
            $dateThreshold = match($dateFilter) {
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
            $roomQuery->where(function($q) use ($search) {
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
            $venueQuery->where(function($q) use ($search) {
                $q->whereHas('user', fn($u) => $u->where('name', 'LIKE', "%{$search}%"))
                ->orWhere('Venue_Reservation_ID', 'LIKE', "%{$search}%")
                // Using 'name' for the Venue table search
                ->orWhereHas('venue', fn($v) => $v->where('name', 'LIKE', "%{$search}%"));
            });
        }

        // 5. Execute and Map Data to standard keys for Blade
        $rooms = ($accommodationType === 'venue') ? collect() : $roomQuery->get()->map(function($item) {
            $item->display_type = 'room';
            $item->type = 'room';
            $item->check_in = $item->Room_Reservation_Check_In_Time;
            $item->check_out = $item->Room_Reservation_Check_Out_Time;
            $item->total_amount = $item->Room_Reservation_Total_Price;
            $item->id = $item->Room_Reservation_ID;
            // Adjust this if your Room table uses a different quantity column
            $item->pax = $item->Room_Reservation_Quantity ?? 0; 
            return $item;
        });

        $venues = ($accommodationType === 'room') ? collect() : $venueQuery->get()->map(function($item) {
            $item->display_type = 'venue';
            $item->type = 'venue';
            $item->check_in = $item->Venue_Reservation_Check_In_Time;
            $item->check_out = $item->Venue_Reservation_Check_Out_Time;
            $item->total_amount = $item->Venue_Reservation_Total_Price;
            $item->id = $item->Venue_Reservation_ID;
            // Using the 'pax' column confirmed in your Model fillable
            $item->pax = $item->pax ?? 0; 
            return $item;
        });

        // 6. Final Merge and Sort
        $reservations = $rooms->concat($venues)->sortByDesc('created_at');

        // Counts for status cards (Fetches all to keep counts accurate even when filtering)
        $allForCounts = \App\Models\RoomReservation::all()->concat(\App\Models\VenueReservation::all());

        return view('employee.guest', compact('reservations', 'allForCounts'));
    }

    public function updateGuests(){

        return view('employee.guest');
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
                    \Illuminate\Support\Facades\Mail::to($reservation->user->email)
                        ->send(new \App\Mail\GuestCheckOutMail($reservation));
                } catch (\Exception $e) {
                    \Log::error("Email Error for Reservation #{$id}: " . $e->getMessage());
                }
            }
        }

        return redirect()->back()->with('success', 'Status updated to ' . ucfirst($newStatus) . ' successfully.');
    }
    public function displayStatistics()
    {
        $roomCount = RoomReservation::count();
        $venueCount = VenueReservation::count();
        $totalReservations = $roomCount + $venueCount;

        return view('employee.dashboard', compact('totalReservations'));
    }

    public function showReservationsCalendar()
    {
        // Load with relationships
        $roomRes = RoomReservation::with(['room', 'user'])->get()->map(function($item) {
            return [
                'id' => $item->Room_Reservation_ID,
                'status' => strtolower($item->status),
                'check_in' => \Carbon\Carbon::parse($item->Room_Reservation_Check_In_Time)->format('Y-m-d'), 
                'check_out' => \Carbon\Carbon::parse($item->Room_Reservation_Check_Out_Time)->format('Y-m-d'),
                'user' => $item->user,
                'room' => $item->room, // Ensure this isn't null
                'label' => $item->room ? "Room " . $item->room->Room_Number : "Room N/A",
                'type' => 'room'
            ];
        });

        $venueRes = VenueReservation::with(['venue', 'user'])->get()->map(function($item) {
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

        return view('employee.dashboard', compact('reservations', 'totalReservations'));
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
                'Room_Reservation_Total_Price' => $totalAmount,
                'status' => 'pending',
            ]);

            $this->sendConfirmationEmail($reservation);
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

            if ($request->filled('selected_foods')) {
                $attachData = [];

                foreach ($request->selected_foods as $fId) {
                    $food = \App\Models\Food::find($fId);

                    if ($food) {
                        $attachData[$fId] = [
                            'serving_time' => now(),
                            'total_price' => ($food->Food_Price ?? $food->food_price) * $request->pax,
                            'status' => 'pending',
                        ];
                    }
                }

                $reservation->foods()->attach($attachData);
            }

            // clear session booking after venue save
            $allBookings = session('employee_pending_bookings', []);
            $uniqueKey = $request->type . '_' . $request->accommodation_id;

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

            $foods = \App\Models\Food::where('status', 'available')
                ->get()
                ->groupBy('food_category');

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

                    $reservations->push([
                        'name' => 'Room: ' . ($r->room->Room_Number ?? 'Room'),
                        'check_in' => $checkIn->format('m/d/Y'),
                        'check_out' => $checkOut->format('m/d/Y'),
                        'pax' => $r->pax,
                        'days' => $days,
                        'price' => $r->Room_Reservation_Total_Price ?? 0
                    ]);
                }

                foreach ($venueReservations as $v) {

                    $checkIn = \Carbon\Carbon::parse($v->Venue_Reservation_Check_In_Time);
                    $checkOut = \Carbon\Carbon::parse($v->Venue_Reservation_Check_Out_Time);
                    $days = $checkIn->diffInDays($checkOut) ?: 1;

                    $reservations->push([
                        'name' => 'Venue: ' . ($v->venue->Venue_Name ?? 'Venue'),
                        'check_in' => $checkIn->format('m/d/Y'),
                        'check_out' => $checkOut->format('m/d/Y'),
                        'pax' => $v->pax,
                        'days' => $days,
                        'price' => $v->Venue_Reservation_Total_Price ?? 0
                    ]);
                }

                return view('employee.SOA', compact('client', 'reservations'));
            }

}


