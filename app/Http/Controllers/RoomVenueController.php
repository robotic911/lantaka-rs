<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Room;
use App\Models\Venue;
use Illuminate\Support\Facades\Auth; // Needed to get the logged-in user
use App\Models\Reservation;
use Carbon\CarbonPeriod;
use App\Models\Food;

class RoomVenueController extends Controller
{
   public function store(Request $request)
    {
        $validated = $request->validate([
            'category'       => 'required|in:Room,Venue',
            'name'           => 'required|string',
            'internal_price' => 'required|numeric',
            'external_price' => 'required|numeric',
            'capacity'       => 'required|integer',
            'type'           => 'nullable|string', // Room specific
            'description'    => 'nullable|string',
        ]);

        // Common data shared by both models
        $commonData = [
            'user_id'        => Auth::id(),
            'capacity'       => $request->capacity,
            'price'          => $request->internal_price,
            'external_price' => $request->external_price,
            'status'         => 'Available',
            'description'    => $request->description,
        ];

        if ($request->category === 'Room') {
            Room::create(array_merge($commonData, [
                'room_number' => $request->name,
                'room_type'   => $request->type ?? 'Standard',
            ]));
        } else {
            Venue::create(array_merge($commonData, [
                'name' => $request->name,
            ]));
        }

        return redirect()->back()->with('success', $request->category . ' added successfully!');
    }

    public function index(Request $request)
    {
        // 1. Get filtered Rooms
        $rooms = Room::query()
            ->when($request->capacity, function ($query, $capacity) {
                if ($capacity == '50+') return $query->where('capacity', '>=', 50);
                return $query->where('capacity', '>=', (int)$capacity);
            })
            ->when($request->availability == 'Available Now', function ($query) {
                return $query->where('status', 'Available');
            })
            ->get()
            ->map(function($room) {
                $room->category = 'Room';
                $room->display_name = "Room " . $room->room_number . " (" . $room->room_type . ")";
                return $room;
            });

        // 2. Get filtered Venues
        $venues = Venue::query()
            ->when($request->capacity, function ($query, $capacity) {
                if ($capacity == '50+') return $query->where('capacity', '>=', 50);
                return $query->where('capacity', '>=', (int)$capacity);
            })
            ->when($request->availability == 'Available Now', function ($query) {
                return $query->where('status', 'Available');
            })
            ->get()
            ->map(function($venue) {
                $venue->category = 'Venue';
                $venue->display_name = $venue->name;
                return $venue;
            });

        // 3. Filter by Category Tab (All, Rooms, or Venue)
        $type = $request->type ?? 'All';
        if ($type === 'Rooms') {
            $all_accommodations = $rooms;
        } elseif ($type === 'Venue') {
            $all_accommodations = $venues;
        } else {
            $all_accommodations = $rooms->concat($venues);
        }

        return view('client.room_venue', compact('all_accommodations'));
    }
     public function adminIndex()
    {
        $rooms = Room::all();
        $venues = Venue::all();
        $foods = \App\Models\Food::all()->groupBy('food_category');

        return view('employee.room_venue', compact('rooms', 'venues', 'foods'));
    }
        public function show($category, $id)
    {
        // 1. Find the correct item based on category
        if ($category === 'Room') {
            $data = Room::findOrFail($id);
            // Standardize the name for the view
            $data->display_name = "Room " . $data->room_number . " (" . $data->room_type . ")"; 
        } else {
            $data = Venue::findOrFail($id);
            $data->display_name = $data->name;
        }

        // 2. Fetch occupied dates from the Reservations table
        // We use strtolower($category) because your DB saves it as 'room' or 'venue' (lowercase)
        $reservations = Reservation::where('accommodation_id', $id)
            ->where('type', strtolower($category))
            // ->where('status', 'Approved') // OPTIONAL: Uncomment this if you only want to block 'Approved' bookings, not 'Pending' ones
            ->get(['check_in', 'check_out']);

        $occupiedDates = [];
        foreach ($reservations as $res) {
            // CarbonPeriod automatically gets every single day between check-in and check-out
            $period = CarbonPeriod::create($res->check_in, $res->check_out);
            foreach ($period as $date) {
                $occupiedDates[] = $date->format('Y-m-d'); // Format for Day.js
            }
        }
        
        // Remove duplicate dates just in case, and reset array keys
        $occupiedDates = array_values(array_unique($occupiedDates));

        // 3. Pass the data AND the occupiedDates to the view
        return view('client.room_venue_viewing', compact('data', 'category', 'occupiedDates'));
    }
    public function prepareBooking(\Illuminate\Http\Request $request)
    {
        // Grab all the data the user just submitted (dates, pax, accommodation_id, type)
        $bookingData = $request->all();

        // 1. If it's a Room, skip food and go straight to Checkout
        if ($request->type === 'room') {
            return redirect()->route('checkout', $bookingData);
        }

        // 2. If it's a Venue, fetch the food and go to the Food Options page
        if ($request->type === 'venue') {
            
            // ⭐ FETCH THE AVAILABLE FOOD HERE
            $foods = Food::where('status', 'available')->get()->groupBy('food_category');

            // ⭐ PASS BOTH bookingData AND foods TO THE VIEW
            return view('client.food_option', compact('bookingData', 'foods'));
        }
    }
    
}