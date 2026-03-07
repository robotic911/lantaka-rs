<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Reservation;
use App\Models\Accommodation;
use Illuminate\Support\Facades\Auth;
use App\Models\Room;   
use App\Models\Venue;
use Carbon\Carbon;
use App\Mail\ReservationConfirmationMail;
use Illuminate\Support\Facades\Mail;


class ReservationController extends Controller
{
    // 1. Show Checkout Page (Calculates Price)
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
            $checkIn = \Carbon\Carbon::parse($item['check_in']);
            $checkOut = \Carbon\Carbon::parse($item['check_out']);
            $days = $checkIn->diffInDays($checkOut) ?: 1;

            // Determine Model
            $model = ($item['type'] === 'room') 
                ? \App\Models\Room::find($item['accommodation_id']) 
                : \App\Models\Venue::find($item['accommodation_id']);

            if ($model) {
                $price = ($item['type'] === 'room') ? $model->price : ($model->Venue_Pricing ?? $model->price);
                $img = ($item['type'] === 'room') ? $model->image : ($model->Venue_Image ?? $model->image);
                $name = ($item['type'] === 'room') ? $model->room_number : ($model->Venue_Name ?? $model->name);

                // Calculate Base Total
                $accommodationTotal = $price * $days;

                // NEW: Handle Food Data for the Summary
                $selectedFoods = [];
                $foodTotal = 0;
                if (!empty($item['selected_foods'])) {
                    $selectedFoods = \App\Models\Food::whereIn('food_id', $item['selected_foods'])->get();
                    // Food price is usually per person (pax)
                    $foodTotal = $selectedFoods->sum('food_price') * $item['pax'];
                }

                $itemTotal = $accommodationTotal + $foodTotal;
                $grandTotal += $itemTotal;

                $processedItems[] = [
                    'key' => $key,
                    'id' => $model->id,
                    'name' => $name,
                    'type' => $item['type'],
                    'price' => $price,
                    'img' => $img,
                    'check_in' => $checkIn->format('F d, Y'),
                    'check_out' => $checkOut->format('F d, Y'),
                    'check_in_raw' => $checkIn->format('Y-m-d'),
                    'check_out_raw' => $checkOut->format('Y-m-d'),
                    'days' => $days,
                    'pax' => $item['pax'],
                    'selected_foods' => $selectedFoods, // Passed to Blade
                    'total' => $itemTotal
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

        try {
        // Load relationships so the email can show the Room/Venue name
        $reservation->load(['room', 'venue', 'user']);
        
        \Illuminate\Support\Facades\Mail::to(auth()->user()->email)
            ->send(new \App\Mail\ReservationConfirmationMail($reservation));
            
        } catch (\Exception $e) {
            // Log error so the checkout still finishes even if Gmail fails
            \Log::error("Reservation Email Error: " . $e->getMessage());
        }

        // 2. Retrieve the booking data from the session
        $uniqueKey = $request->type . '_' . $request->id;
        $allBookings = session('pending_bookings', []);
        $bookingData = $allBookings[$uniqueKey] ?? null;

        if ($bookingData && !empty($bookingData['selected_foods'])) {
            
            // 1. Get the actual Food models for the IDs the client selected
            $foods = \App\Models\Food::whereIn('food_id', $bookingData['selected_foods'])->get();
            
            $attachData = [];
            
            // 2. Loop through them to build an array with the extra 'total_price' column
            foreach ($foods as $food) {
                $attachData[$food->food_id] = [
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
        // 1. Start the base query
        $query = Reservation::with(['user', 'room', 'venue', 'foods']);

        // 2. Apply Search and Dropdown Filters (Date, Type, etc.)
        // We apply these FIRST so the card numbers reflect your search results\      
        if ($request->filled('search')) {

            $raw = trim($request->search);
            $normalized = mb_strtolower($raw);
        
            // Replace separators like "-" with space
            $normalized = preg_replace('/[-_]+/', ' ', $normalized);
            $normalized = preg_replace('/\s+/', ' ', $normalized);
        
            $keywords = array_values(array_filter(explode(' ', $normalized)));
        
            $query->where(function ($q) use ($keywords) {
        
                foreach ($keywords as $word) {
        
                    $q->where(function ($sub) use ($word) {
        
                        // Search reservation ID only if numeric
                        if (is_numeric($word)) {
                            $sub->orWhere('id', (int) $word);
                        }
        
                        // Search guest name
                        $sub->orWhereHas('user', function ($userQ) use ($word) {
                            $userQ->whereRaw('LOWER(name) LIKE ?', ["%{$word}%"]);
                        })
        
                        // Search room number
                        ->orWhereHas('room', function ($roomQ) use ($word) {
                            $roomQ->whereRaw('LOWER(room_number) LIKE ?', ["%{$word}%"]);
                        })
        
                        // Search venue name
                        ->orWhereHas('venue', function ($venueQ) use ($word) {
                            $venueQ->whereRaw('LOWER(name) LIKE ?', ["%{$word}%"]);
                        })
        
                        // Search reservation status
                        ->orWhereRaw('LOWER(status) LIKE ?', ["%{$word}%"]);
                    });
                }
            });
        }

        if ($request->filled('date')) {
            $now = \Carbon\Carbon::now();
            if ($request->date === 'last_week') $query->where('created_at', '>=', $now->subWeek());
            elseif ($request->date === 'last_month') $query->where('created_at', '>=', $now->subMonth());
            elseif ($request->date === 'last_year') $query->where('created_at', '>=', $now->subYear());
        }

        if ($request->filled('client_type')) {
            $query->whereHas('user', fn($q) => $q->where('usertype', $request->client_type));
        }

        if ($request->filled('accommodation_type')) {
            $query->where('type', $request->accommodation_type);
        }

        // --- STEP 3: SNAPSHOT FOR COUNTS ---
        // This variable contains all items matching your filters above.
        // Use this in your Blade file for the card numbers.
        $allForCounts = Reservation::get();

        // --- STEP 4: APPLY TABLE-SPECIFIC STATUS FILTER ---
        if ($request->filled('status')) {
            $status = $request->status;
            if ($status === 'cancelled') {
                // Grouping cancelled, declined, and rejected for the "Cancelled" card/view
                $query->whereIn('status', ['cancelled', 'declined', 'rejected']);
            } else {
                $query->where('status', $status);
            }
        }

        // 5. Final execution for the Table rows
        $reservations = $query->orderBy('created_at', 'desc')->get();

        return view('employee.reservations', compact('reservations', 'allForCounts'));
    }
    public function showGuests(\Illuminate\Http\Request $request)
    {
        // 1. Define the base guest statuses
        $validStatuses = ['confirmed', 'checked-in', 'checked-out', 'cancelled', 'declined', 'completed', 'approved', 'rejected'];

        // 2. Start the query
        $query = \App\Models\Reservation::with(['user', 'room', 'venue', 'foods']);

        // 3. APPLY GENERAL FILTERS FIRST (Affects both Table and Cards)
        
        // Search Logic
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function($q) use ($searchTerm) {
                
                $q->whereHas('user', fn($u) => $u->where('name', 'LIKE', "%{$searchTerm}%"))
                ->orWhereHas('room', fn($r) => $r->where('room_number', 'LIKE', "%{$searchTerm}%"))
                ->orWhereHas('venue', fn($v) => $v->where('name', 'LIKE', "%{$searchTerm}%"))
                ->orWhere('id', $searchTerm);
            });
        }

        // Date Logic
        if ($request->filled('date')) {
            $now = \Carbon\Carbon::now();
            if ($request->date === 'last_week') $query->where('created_at', '>=', $now->subDays(7));
            elseif ($request->date === 'last_month') $query->where('created_at', '>=', $now->subDays(30));
            elseif ($request->date === 'last_year') $query->where('created_at', '>=', $now->startOfYear());
        }

        // Client & Accommodation Type Logic
        if ($request->filled('client_type')) {
            $query->whereHas('user', fn($q) => $q->where('usertype', $request->client_type));
        }

        if ($request->filled('accommodation_type')) {
            $query->where('type', $request->accommodation_type);
        }

        // --- CRITICAL SNAPSHOT: GET ALL MATCHING GUESTS FOR COUNTS ---
        // We filter by $validStatuses here so 'pending' items never show up on the Guest page
        $allForCounts = Reservation::get();

        // 4. APPLY STATUS CARD FILTERING (Affects ONLY the Table)
        if ($request->filled('status')) {
            $status = strtolower($request->status);
            if ($status === 'checked-in') {
                $query->whereIn('status', ['checked-in', 'approved']);
            } elseif ($status === 'cancelled') {
                $query->whereIn('status', ['cancelled', 'declined', 'rejected']);
            } elseif ($status === 'checked-out') {
                $query->whereIn('status', ['checked-out', 'completed']);
            } else {
                $query->where('status', $status);
            }
        } else {
            // Default view: Show everything except 'pending'
            $query->whereIn('status', $validStatuses);
        }

        // 5. Execute and return
        $reservations = $query->orderBy('created_at', 'desc')->get();

        return view('employee.guest', compact('reservations', 'allForCounts'));
    }
    public function updateStatus(Request $request, $id)
    {
        // 1. Find the reservation and LOAD relationships
        $reservation = Reservation::with(['user', 'room', 'venue'])->findOrFail($id);

        $newStatus = strtolower($request->status); 

        // 2. Perform the update in the database
        $reservation->update([
            'status' => $newStatus
        ]);

        // 3. TRIGGER EMAIL ONLY IF STATUS IS 'CHECKED-OUT'
        if ($newStatus === 'checked-out' || $newStatus === 'completed') {
            try {
                \Illuminate\Support\Facades\Mail::to($reservation->user->email)
                    ->send(new \App\Mail\GuestCheckOutMail($reservation));
            } catch (\Exception $e) {
                // Log error so the admin isn't blocked if the email fails
                \Log::error("Check-out Email Error: " . $e->getMessage());
            }
        }

        return redirect()->back()->with('success', 'Guest ' . $newStatus . ' successfully.');
    }

    public function displayStatistics(){
        $totalReservations = Reservation::count();
        return view('employee.dashboard', compact('totalReservations'));
    }

    public function showReservationsCalendar(){
        $reservations = Reservation::with(['room','venue','user'])->get();
        $totalReservations = Reservation::count();

        return view('employee.dashboard', [
            'reservations' => $reservations,
            'totalReservations' => $totalReservations
        ]);
    }
    public function cancel($id)
    {
        try {
            // Find reservation and ensure it belongs to the current user
            // We cast $id to (int) in case it comes in as "00030"
            $reservation = \App\Models\Reservation::where('user_id', auth()->id())
                            ->findOrFail((int)$id);

            $reservation->update(['status' => 'cancelled']);

            return response()->json(['message' => 'Reservation cancelled successfully.']);
        } catch (\Exception $e) {
            // This will tell us the EXACT error in the Network Tab
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
    
}


