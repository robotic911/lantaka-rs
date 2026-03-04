<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Reservation;
use App\Models\Accommodation;
use Illuminate\Support\Facades\Auth;
use App\Models\Room;   
use App\Models\Venue;
use Carbon\Carbon;


class ReservationController extends Controller
{
    // 1. Show Checkout Page (Calculates Price)
    public function checkout(Request $request)
    {
        // 1. Get the current list of bookings from session (or an empty array if none exist)
        $allBookings = session('pending_bookings', []);

        // 2. If coming from the "Proceed" button, add the new selection to the list
        if ($request->has('accommodation_id')) {
            $newEntry = $request->all();
            
            // Use the ID and Type as a unique key to prevent duplicate entries of the SAME room
            $uniqueKey = $newEntry['type'] . '_' . $newEntry['accommodation_id'];
            $allBookings[$uniqueKey] = $newEntry;
            
            session(['pending_bookings' => $allBookings]);
        }

        $processedItems = [];
        $grandTotal = 0;

        foreach ($allBookings as $key => $item) {
            $checkIn = \Carbon\Carbon::parse($item['check_in']);
            $checkOut = \Carbon\Carbon::parse($item['check_out']);
            $days = $checkIn->diffInDays($checkOut) ?: 1;

            if ($item['type'] === 'room') {
                $model = \App\Models\Room::find($item['accommodation_id']);
                $name = $model->room_number;
                $price = $model->price;
                $img = $model->image;
            } else {
                $model = \App\Models\Venue::find($item['accommodation_id']);
                $name = $model->Venue_Name ?? $model->name;
                $price = $model->Venue_Pricing ?? $model->price;
                $img = $model->Venue_Image ?? $model->image;
            }

            if ($model) {
                $total = $price * $days;
                $grandTotal += $total;
                
                $processedItems[] = [
                    'key' => $key,
                    'id' => $model->id,
                    'name' => $name,
                    'type' => $item['type'],
                    'price' => $price,
                    'img' => $img,
                    'check_in' => $checkIn->format('F d, Y'), // For display
                    'check_out' => $checkOut->format('F d, Y'), // For display
                    'check_in_raw' => $checkIn->format('Y-m-d'), // For JavaScript/Database
                    'check_out_raw' => $checkOut->format('Y-m-d'), // For JavaScript/Database
                    'days' => $days,
                    'pax' => $item['pax'],
                    'total' => $total
                ];
            }
        }

        return view('client.my_bookings', compact('processedItems', 'grandTotal'));
    }

    // 2. Store the Reservation (Confirm Button)
    public function store(Request $request)
    {
        $request->validate([
            'id' => 'required',
            'type' => 'required',
            'check_in' => 'required|date',
            'check_out' => 'required|date',
            'pax' => 'required|integer',
            'total_amount' => 'required|numeric',
        ]);

        // 1. Create the main reservation
        $reservation = Reservation::create([
            'user_id' => auth()->id(),
            'accommodation_id' => $request->id,
            'type' => $request->type,
            'check_in' => $request->check_in,
            'check_out' => $request->check_out,
            'pax' => $request->pax,
            'total_amount' => $request->total_amount,
            'status' => 'pending'
        ]);

        // 2. Retrieve the booking data from the session
        $uniqueKey = $request->type . '_' . $request->id;
        $allBookings = session('pending_bookings', []);
        $bookingData = $allBookings[$uniqueKey] ?? null;

        // --- THE FIX: Save the food to the pivot table ---
        // Check if there is food associated with this specific booking in the session
        if ($bookingData && !empty($bookingData['selected_foods'])) {
            
            // 1. Get the actual Food models for the IDs the client selected
            $foods = \App\Models\Food::whereIn('food_id', $bookingData['selected_foods'])->get();
            
            $attachData = [];
            
            // 2. Loop through them to build an array with the extra 'total_price' column
            foreach ($foods as $food) {
                $attachData[$food->food_id] = [
                    // Assuming catering is priced per head (Food Price x Number of Pax)
                    // Note: If food is a flat rate, just remove the " * $request->pax"
                    'total_price' => $food->food_price * $request->pax
                ];
            }

            // 3. Attach the foods WITH their calculated total prices!
            $reservation->foods()->attach($attachData);
        }

        // 3. Clear the session data
        if (isset($allBookings[$uniqueKey])) {
            unset($allBookings[$uniqueKey]);
            session(['pending_bookings' => $allBookings]);
        }

        return redirect()->route('client.my_reservations')->with('success', 'Reservation confirmed!');
    }
    public function showMyBookings()
    {
        $booking = session('pending_booking');

        if (!$booking) {
            return redirect()->route('client.room_venue')->with('error', 'No active booking found.');
        }
    }

    // 3. Client: My Reservations Page
    public function index()
    {
        // Added 'foods' here!
        $reservations = Reservation::where('user_id', Auth::id())
                        ->with(['room', 'venue', 'foods']) 
                        ->orderBy('created_at', 'desc')
                        ->get();

        return view('client.my_reservations', compact('reservations'));
    }

    // 4. Admin Page
    // 4. Admin Page
    public function adminIndex(Request $request)
    {
        // 1. Start the query without getting the results yet
        $query = Reservation::with(['user', 'room', 'venue', 'foods']);

        // 2. LOGIC: Search Bar (Searches User Name, Room Number, or Venue Name)
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            
            $query->where(function($q) use ($searchTerm) {
                $q->whereHas('user', function($userQuery) use ($searchTerm) {
                    $userQuery->where('name', 'LIKE', "%{$searchTerm}%");
                })
                ->orWhereHas('room', function($roomQuery) use ($searchTerm) {
                    $roomQuery->where('room_number', 'LIKE', "%{$searchTerm}%");
                })
                ->orWhereHas('venue', function($venueQuery) use ($searchTerm) {
                    // THE PROBLEM IS RIGHT HERE:
                    $venueQuery->where('name', 'LIKE', "%{$searchTerm}%") 
                            ->orWhere('name', 'LIKE', "%{$searchTerm}%");
                });
            });
        }

        // 3. LOGIC: Date Filter
        if ($request->filled('date')) {
            $now = \Carbon\Carbon::now();
            if ($request->date === 'last_week') {
                $query->where('created_at', '>=', $now->subWeek());
            } elseif ($request->date === 'last_month') {
                $query->where('created_at', '>=', $now->subMonth());
            } elseif ($request->date === 'last_year') {
                $query->where('created_at', '>=', $now->subYear());
            }
        }

        // 4. LOGIC: Accommodation Type Filter
        if ($request->filled('accommodation_type')) {
            $query->where('type', $request->accommodation_type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->get('status') === 'cancelled') {
            // This ensures any of these database statuses show up when "Cancelled" is clicked
            $query->whereIn('status', ['cancelled', 'declined', 'rejected']);
        }

        // 5. LOGIC: Client Type Filter (Assuming usertype is in your users table)
        if ($request->filled('client_type')) {
            $clientType = $request->client_type;
            $query->whereHas('user', function($q) use ($clientType) {
                $q->where('usertype', $clientType); 
            });
        }

        // 6. Finally, execute the query and get the results
        $reservations = $query->orderBy('created_at', 'desc')->get();

        return view('employee.reservations', compact('reservations'));
    }
    public function showGuests(\Illuminate\Http\Request $request) 
    {
        // 1. Define the base guest statuses you want to track
        $validStatuses = ['confirmed', 'checked-in', 'checked-out', 'cancelled', 'declined', 'completed'];

        // 2. Start the query
        $query = \App\Models\Reservation::with(['user', 'room', 'venue', 'foods']);

        // 3. LOGIC: Status Card Filtering
        if ($request->filled('status')) {
            $status = strtolower($request->status);
            
            // Handle grouped statuses for specific cards
            if ($status === 'checked-in') {
                $query->whereIn('status', ['checked-in', 'approved']);
            } elseif ($status === 'cancelled') {
                $query->whereIn('status', ['cancelled', 'declined']);
            } else {
                $query->where('status', $status);
            }
        } else {
            // Default view: Show everything except 'pending' (which stays on the Reservations page)
            $query->whereIn('status', $validStatuses);
        }

        // 4. SEARCH LOGIC (Existing)
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function($q) use ($searchTerm) {
                $q->whereHas('user', function($userQ) use ($searchTerm) {
                    $userQ->where('name', 'LIKE', "%{$searchTerm}%");
                })
                ->orWhereHas('room', function($roomQ) use ($searchTerm) {
                    $roomQ->where('room_number', 'LIKE', "%{$searchTerm}%");
                })
                ->orWhereHas('venue', function($venueQ) use ($searchTerm) {
                    $venueQ->where('name', 'LIKE', "%{$searchTerm}%"); 
                });
            });
        }

        // 5. DATE LOGIC (Existing)
        if ($request->filled('date')) {
            $now = \Carbon\Carbon::now();
            if ($request->date === 'last_week') {
                $query->where('created_at', '>=', $now->subDays(7));
            } elseif ($request->date === 'last_month') {
                $query->where('created_at', '>=', $now->subDays(30));
            } elseif ($request->date === 'last_year') {
                $query->where('created_at', '>=', $now->startOfYear());
            }
        }

        // 6. CLIENT & ACCOMMODATION TYPE LOGIC (Existing)
        if ($request->filled('client_type')) {
            $query->whereHas('user', function($q) use ($request) {
                $q->where('usertype', $request->client_type);
            });
        }

        if ($request->filled('accommodation_type')) {
            $query->where('type', $request->accommodation_type);
        }

        // 7. Execute and return
        $reservations = $query->orderBy('created_at', 'desc')->get();

        return view('employee.guest', compact('reservations'));
    }
    public function updateStatus(Request $request, $id)
    {
        $reservation = Reservation::findOrFail($id);

        // Force lowercase to ensure consistency with your Blade counts
        $newStatus = strtolower($request->status); 

        $reservation->update([
            'status' => $newStatus
        ]);

        return redirect()->back()->with('success', 'Reservation ' . $newStatus);
    }

    public function displayStatistics(){
        $totalReservations = Reservation::count();
        return view('employee.dashboard', compact('totalReservations'));
    }
}
