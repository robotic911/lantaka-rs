<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // Needed to get the logged-in user
use App\Models\RoomReservation; // Add this
use App\Models\VenueReservation;
use App\Models\Room;
use App\Models\Venue;
use App\Models\User;
use App\Models\Food;
use Carbon\CarbonPeriod;

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
    public function adminIndex(Request $request)
    {
        $search = $request->search;
        $status = $request->status;

        $rooms = Room::query()
            ->when($search, function ($query) use ($search) {
                $query->where('room_number', 'ilike', "%{$search}%")
                    ->orWhere('room_type', 'ilike', "%{$search}%");
            })
            ->when($status, function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->get();

        $venues = Venue::query()
            ->when($search, function ($query) use ($search) {
                $query->where('name', 'ilike', "%{$search}%");
            })
            ->when($status, function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->get();

        // FIX: Group the food items by category and make keys lowercase
        $foods = Food::all()->groupBy(function($item) {
            return strtolower($item->food_category);
        });

        return view('employee.room_venue', compact('rooms', 'venues', 'foods'));
    }
        public function show($category, $id)
    {
        // 1. Find the correct item based on category
        if (strtolower($category) === 'room') {
            $data = Room::findOrFail($id);
            $data->display_name = "Room " . ($data->room_number ?? $id);
            
            // Use RoomReservation model
            $reservations = RoomReservation::where('room_id', $id)
                ->whereIn('status', ['pending', 'confirmed', 'checked-in'])
                ->get();
                
            $dateFieldIn = 'Room_Reservation_Check_In_Time';
            $dateFieldOut = 'Room_Reservation_Check_Out_Time';
        } else {
            $data = Venue::findOrFail($id);
            $data->display_name = $data->name;

            // Use VenueReservation model
            $reservations = VenueReservation::where('venue_id', $id)
                ->whereIn('status', ['pending', 'confirmed', 'checked-in'])
                ->get();
                
            $dateFieldIn = 'Venue_Reservation_Check_In_Time';
            $dateFieldOut = 'Venue_Reservation_Check_Out_Time';
        }

        // 2. Map the occupied dates
        $occupiedDates = [];
        foreach ($reservations as $res) {
            $period = CarbonPeriod::create($res->$dateFieldIn, $res->$dateFieldOut);
            foreach ($period as $date) {
                $occupiedDates[] = $date->format('Y-m-d');
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
            
            // FETCH THE AVAILABLE FOOD HERE
            $foods = Food::where('status', 'available')->get()->groupBy('food_category');

            // PASS BOTH bookingData AND foods TO THE VIEW
            return view('client.food_option', compact('bookingData', 'foods'));
        }
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'id'             => 'required|integer',
            'category'       => 'required|in:Room,Venue',
            'name'           => 'required|string',
            'internal_price' => 'required|numeric',
            'external_price' => 'required|numeric',
            'capacity'       => 'required|integer',
            'status'         => 'required|in:Available,Unavailable',
            'type'           => 'nullable|string',
            'description'    => 'nullable|string',
        ]);
    
        if ($request->category === 'Room') {
            $room = Room::findOrFail($request->id);
    
            $room->update([
                'room_number'    => $request->name,
                'room_type'      => $request->type ?? 'Standard',
                'capacity'       => $request->capacity,
                'price'          => $request->internal_price,
                'external_price' => $request->external_price,
                'status'         => $request->status,
                'description'    => $request->description,
            ]);
        } else {
            $venue = Venue::findOrFail($request->id);
    
            $venue->update([
                'name'           => $request->name,
                'capacity'       => $request->capacity,
                'price'          => $request->internal_price,
                'external_price' => $request->external_price,
                'status'         => $request->status,
                'description'    => $request->description,
            ]);
        }
    
        return back()->with('success', $request->category . ' updated successfully!');
    }

    public function showAssignedAccomodation(Request $request)
    {
        $category = $request->category;
        $id = $request->id;
        $userId = $request->user_id;

        // 1. Fetch the Room or Venue and its specific reservations
        if ($category === 'Room') {
            $data = Room::findOrFail($id);
            $data->display_name = "Room " . $data->room_number . " (" . $data->room_type . ")";
            if ($category === 'Room') {
                $data = Room::findOrFail($id);
                $data->display_name = "Room " . $data->room_number . " (" . $data->room_type . ")";

                $reservations = RoomReservation::where('room_id', $id)
                    ->get([
                        'Room_Reservation_Check_In_Time',
                        'Room_Reservation_Check_Out_Time'
                    ]);

                $occupiedDates = [];

                foreach ($reservations as $res) {
                    $period = CarbonPeriod::create(
                        $res->Room_Reservation_Check_In_Time,
                        $res->Room_Reservation_Check_Out_Time
                    );

                    foreach ($period as $date) {
                        $occupiedDates[] = $date->format('Y-m-d');
                    }
                }

            } else {
                $data = Venue::findOrFail($id);
                $data->display_name = $data->name;

                $reservations = VenueReservation::where('venue_id', $id)
                    ->get([
                        'Venue_Reservation_Check_In_Time',
                        'Venue_Reservation_Check_Out_Time'
                    ]);

                $occupiedDates = [];

                foreach ($reservations as $res) {
                    $period = CarbonPeriod::create(
                        $res->Venue_Reservation_Check_In_Time,
                        $res->Venue_Reservation_Check_Out_Time
                    );

                    foreach ($period as $date) {
                        $occupiedDates[] = $date->format('Y-m-d');
                    }
                }
            }

            // Fetch from RoomReservation model
            $reservations = RoomReservation::where('room_id', $id)
                ->get([
                    'Room_Reservation_Check_In_Time as check_in', 
                    'Room_Reservation_Check_Out_Time as check_out'
                ]);
        } else {
            $data = Venue::findOrFail($id);
            $data->display_name = $data->name;

            // Fetch from VenueReservation model
            $reservations = VenueReservation::where('venue_id', $id)
                ->get([
                    'Venue_Reservation_Check_In_Time as check_in', 
                    'Venue_Reservation_Check_Out_Time as check_out'
                ]);
        }

        $client = User::findOrFail($userId);

        // Prefill data for edit mode (passed via query string from the employee modal)
        $reservationId  = $request->reservation_id;
        $prefillPax     = $request->pax;
        $prefillPurpose = $request->purpose;

        // 2a. If editing, collect dates belonging to THIS reservation so they
        //     can be shown as "current" (blue/selectable) instead of "occupied" (red).
        $currentReservationDates = [];
        if ($reservationId) {
            if ($category === 'Room') {
                $editRes = \App\Models\RoomReservation::find($reservationId);
                if ($editRes) {
                    $editPeriod = CarbonPeriod::create(
                        $editRes->Room_Reservation_Check_In_Time,
                        $editRes->Room_Reservation_Check_Out_Time
                    );
                    foreach ($editPeriod as $d) {
                        $currentReservationDates[] = $d->format('Y-m-d');
                    }
                }
            } else {
                $editRes = \App\Models\VenueReservation::find($reservationId);
                if ($editRes) {
                    $editPeriod = CarbonPeriod::create(
                        $editRes->Venue_Reservation_Check_In_Time,
                        $editRes->Venue_Reservation_Check_Out_Time
                    );
                    foreach ($editPeriod as $d) {
                        $currentReservationDates[] = $d->format('Y-m-d');
                    }
                }
            }
        }

        // 2b. Build occupied dates, excluding the current reservation's own dates
        $occupiedDates = [];
        foreach ($reservations as $res) {
            $period = CarbonPeriod::create($res->check_in, $res->check_out);
            foreach ($period as $date) {
                $dateStr = $date->format('Y-m-d');
                // Skip dates that belong to the reservation being edited
                if (!in_array($dateStr, $currentReservationDates)) {
                    $occupiedDates[] = $dateStr;
                }
            }
        }

        $occupiedDates = array_values(array_unique($occupiedDates));

        return view('employee.create_reservation', compact(
            'data', 'category', 'occupiedDates', 'client',
            'reservationId', 'prefillPax', 'prefillPurpose', 'currentReservationDates'
        ));
    }
}

    