<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RoomReservation;
use App\Models\VenueReservation;
use App\Models\Room;
use App\Models\Venue;
use App\Models\Food;
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
                'total_price' => $request->total_amount,
                'Venue_Reservation_Date' => now(),
                'Venue_Reservation_Check_In_Time' => $request->check_in,
                'Venue_Reservation_Check_Out_Time' => $request->check_out,
                'pax' => $request->pax,
                'Venue_Reservation_Total_Price' => $request->total_amount,
                'status' => 'pending'
            ]);
            try {
                Mail::to(auth()->user()->email)->send(new \App\Mail\ReservationConfirmationMail($reservation));
            } catch (\Exception $e) {
                // Log the error if mail fails so the whole app doesn't crash
                \Log::error("Mail failed: " . $e->getMessage());
            }
            // Handle Food Pivot for Venues
            $uniqueKey = 'venue_' . $request->id;
            $allBookings = session('pending_bookings', []);
            $bookingData = $allBookings[$uniqueKey] ?? null;

            if ($bookingData && !empty($bookingData['selected_foods'])) {
                $attachData = [];
                foreach ($bookingData['selected_foods'] as $fId) {
                    $food = Food::find($fId);
                    $attachData[$fId] = [
                        // Match the lowercase names from your migration
                        'serving_time' => now(), 
                        'total_price'  => $food->food_price * $request->pax,
                        'status'       => 'pending'
                    ];
                }
                $reservation->foods()->attach($attachData);
            }
        }

        // Clear only this item from session
        $allBookings = session('pending_bookings', []);
        unset($allBookings[$request->type . '_' . $request->id]);
        session(['pending_bookings' => $allBookings]);

        return redirect()->route('client.my_reservations')->with('success', 'Reservation confirmed!');
    }

    // 3. Client List Page
    public function index(Request $request)
    {
        $clientId = auth()->id();
        $search = $request->input('search');
        $status = $request->input('status');
        $type = $request->input('type');

        // --- 1. Query Room Reservations ---
        $roomQuery = \App\Models\RoomReservation::where('Client_ID', $clientId)->with('room');

        if ($status && $status !== 'Status') {
            $roomQuery->where('status', $status);
        }

        if ($search) {
            $roomQuery->where(function($q) use ($search) {
                $q->where('Room_Reservation_ID', 'like', "%{$search}%")
                ->orWhereHas('room', function($rq) use ($search) {
                    $rq->where('room_number', 'like', "%{$search}%");
                });
            });
        }

        // --- 2. Query Venue Reservations ---
        $venueQuery = \App\Models\VenueReservation::where('Client_ID', $clientId)->with(['venue', 'foods']);

        if ($status && $status !== 'Status') {
            $venueQuery->where('status', $status);
        }

        if ($search) {
            $venueQuery->where(function($q) use ($search) {
                $q->where('Venue_Reservation_ID', 'like', "%{$search}%")
                ->orWhereHas('venue', function($vq) use ($search) {
                    $vq->where('name', 'like', "%{$search}%");
                });
            });
        }

        // --- 3. Combine and Apply Type Filter ---
        $rooms = ($type === 'venue') ? collect() : $roomQuery->get()->map(function($item) {
            $item->type = 'room';
            // Map custom PK to a standard property for easier sorting/handling if needed
            $item->id = $item->Room_Reservation_ID; 
            return $item;
        });

        $venues = ($type === 'room') ? collect() : $venueQuery->get()->map(function($item) {
            $item->type = 'venue';
            $item->id = $item->Venue_Reservation_ID;
            return $item;
        });

        // Merge and sort by newest first
        $reservations = $rooms->concat($venues)->sortByDesc('created_at');

        return view('client.my_reservations', compact('reservations'));
    }
    // 4. Admin List Page
    public function adminIndex(Request $request)
    {
        $search = $request->input('search');
        $status = $request->input('status');
        $roomQuery = RoomReservation::with(['user', 'room']);
        $venueQuery = VenueReservation::with(['user', 'venue']);

        if ($search) {
            $roomQuery->whereHas('user', fn($q) => $q->where('name', 'LIKE', "%$search%"));
            $venueQuery->whereHas('user', fn($q) => $q->where('name', 'LIKE', "%$search%"));
        }

        if ($status) {
            $roomQuery->where('status', $status);
            $venueQuery->where('status', $status);
        }

        $rooms = $roomQuery->get()->map(function($item) { $item->display_type = 'room'; return $item; });
        $venues = $venueQuery->get()->map(function($item) { $item->display_type = 'venue'; return $item; });

        $reservations = $rooms->concat($venues)->sortByDesc('created_at');
        $allForCounts = RoomReservation::select('status')->get()->concat(VenueReservation::select('status')->get());

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
        // 1. Capture type from query string (sent via JS) and status from form body
        $type = $request->query('type'); 
        $newStatus = strtolower($request->input('status'));

        // 2. Validate Type to prevent errors
        if ($id === 'null' || !$id) {
            return redirect()->back()->with('error', 'Critical Error: Reservation ID was not passed correctly.');
        }

        // 3. Find the correct model based on type
        try {
            if ($type === 'room') {
                $reservation = \App\Models\RoomReservation::with('user')->findOrFail($id);
            } else {
                $reservation = \App\Models\VenueReservation::with('user')->findOrFail($id);
            }
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return redirect()->back()->with('error', 'Reservation not found.');
        }

        // 4. Update the status
        $reservation->update(['status' => $newStatus]);

        // 5. Trigger Email if Check-out
        if (in_array($newStatus, ['checked-out', 'completed'])) {
            if ($reservation->user && $reservation->user->email) {
                try {
                    \Illuminate\Support\Facades\Mail::to($reservation->user->email)
                        ->send(new \App\Mail\GuestCheckOutMail($reservation));
                } catch (\Exception $e) {
                    \Log::error("Email Error for Reservation #{$id}: " . $e->getMessage());
                    // We don't return error here so the status update still "saves" even if mail fails
                }
            }
        }

        return redirect()->back()->with('success', "Status updated to " . ucfirst($newStatus) . " successfully.");
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
        $roomRes = RoomReservation::with(['room', 'user'])->get();
        $venueRes = VenueReservation::with(['venue', 'user'])->get();

        // Merge for the calendar view
        $reservations = $roomRes->concat($venueRes);
        $totalReservations = $reservations->count();

        return view('employee.dashboard', [
            'reservations' => $reservations,
            'totalReservations' => $totalReservations
        ]);
    }
    public function cancel(Request $request, $id)
{
    $type = $request->input('type');

    if ($type === 'room') {
        $reservation = \App\Models\RoomReservation::findOrFail($id);
        // Also set the room back to available
        $reservation->room->update(['status' => 'Available']); 
    } else {
        $reservation = \App\Models\VenueReservation::findOrFail($id);
        // Also set the venue back to available
        $reservation->venue->update(['status' => 'Available']);
    }

    if ($reservation->Client_ID !== auth()->id()) {
        return response()->json(['message' => 'Unauthorized'], 403);
    }

    $reservation->status = 'cancelled';
    $reservation->save();

    return response()->json(['message' => 'Success']);
}
    
}


