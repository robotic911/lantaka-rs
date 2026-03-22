<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // Needed to get the logged-in user
use App\Models\RoomReservation; // Add this
use App\Models\VenueReservation;
use App\Models\Room;
use App\Models\Venue;
use App\Models\Account;
use App\Models\Food;
use Carbon\CarbonPeriod;

class RoomVenueController extends Controller
{
    /**
     * Resize + compress an uploaded image with GD. Always saves as JPEG.
     * Max output 1200×900 px, 82% quality. Never upscales.
     */
    private function processAndStoreImage($file, string $folder): string
    {
        $ext        = strtolower($file->getClientOriginalExtension());
        $sourcePath = $file->getPathname();

        $src = match ($ext) {
            'png'  => imagecreatefrompng($sourcePath),
            'webp' => imagecreatefromwebp($sourcePath),
            default => imagecreatefromjpeg($sourcePath),
        };

        if (!$src) {
            $name = uniqid() . '.jpg';
            $file->storeAs('public/' . $folder, $name);
            return $folder . '/' . $name;
        }

        $origW = imagesx($src);
        $origH = imagesy($src);
        $ratio = min(1200 / $origW, 900 / $origH, 1.0);
        $newW  = (int) round($origW * $ratio);
        $newH  = (int) round($origH * $ratio);

        $dst   = imagecreatetruecolor($newW, $newH);
        $white = imagecolorallocate($dst, 255, 255, 255);
        imagefill($dst, 0, 0, $white);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $origW, $origH);
        imagedestroy($src);

        $dir = storage_path('app/public/' . $folder);
        if (!file_exists($dir)) mkdir($dir, 0755, true);

        $filename = $folder . '/' . uniqid() . '.jpg';
        imagejpeg($dst, storage_path('app/public/' . $filename), 82);
        imagedestroy($dst);

        return $filename;
    }

    public function store(Request $request)
    {
        $request->validate([
            'category'       => 'required|in:Room,Venue',
            'name'           => 'required|string',
            'internal_price' => 'required|numeric',
            'external_price' => 'required|numeric',
            'capacity'       => 'required|integer',
            'type'           => 'nullable|string',
            'description'    => 'nullable|string',
            'image'          => 'nullable|image|mimes:jpeg,jpg,png,webp|max:5120',
        ]);

        $imagePath = null;
        if ($request->hasFile('image') && $request->file('image')->isValid()) {
            $folder    = $request->category === 'Room' ? 'rooms' : 'venues';
            $imagePath = $this->processAndStoreImage($request->file('image'), $folder);
        }

        if ($request->category === 'Room') {
            Room::create([
                'user_id'              => Auth::id(),
                'Room_Number'          => $request->name,
                'Room_Type'            => $request->type ?? 'Standard',
                'Room_Capacity'        => $request->capacity,
                'Room_Internal_Price'  => $request->internal_price,
                'Room_External_Price'  => $request->external_price,
                'Room_Status'          => 'Available',
                'Room_Description'     => $request->description,
                'Room_Image'           => $imagePath,
            ]);
        } else {
            Venue::create([
                'user_id'              => Auth::id(),
                'Venue_Name'           => $request->name,
                'Venue_Capacity'       => $request->capacity,
                'Venue_Internal_Price' => $request->internal_price,
                'Venue_External_Price' => $request->external_price,
                'Venue_Status'         => 'Available',
                'Venue_Description'    => $request->description,
                'Venue_Image'          => $imagePath,
            ]);
        }

        return redirect()->back()->with('success', $request->category . ' added successfully!');
    }

    public function index(Request $request)
    {
        // 1. Get filtered Rooms
        $rooms = Room::query()
            ->when($request->capacity, function ($query, $capacity) {
                if ($capacity == '50+') return $query->where('Room_Capacity', '>=', 50);
                return $query->where('Room_Capacity', '>=', (int)$capacity);
            })
            ->when($request->availability == 'Available Now', function ($query) {
                return $query->where('Room_Status', 'Available');
            })
            ->get()
            ->map(function($room) {
                $room->category = 'Room';
                $room->id = $room->Room_ID;
                $room->display_name = "Room " . $room->Room_Number . " (" . $room->Room_Type . ")";
                $room->capacity = $room->Room_Capacity;
                $room->internal_price = $room->Room_Internal_Price;
                $room->external_price = $room->Room_External_Price;
                $room->image = $room->Room_Image;

                return $room;
            });

        // 2. Get filtered Venues
        $venues = Venue::query()
            ->when($request->capacity, function ($query, $capacity) {
                if ($capacity == '50+') return $query->where('Venue_Capacity', '>=', 50);
                return $query->where('Venue_Capacity', '>=', (int)$capacity);
            })
            ->when($request->availability == 'Available Now', function ($query) {
                return $query->where('Venue_Status', 'Available');
            })
            ->get()
            ->map(function($venue) {
                $venue->category = 'Venue';
                $venue->id = $venue->Venue_ID;
                $venue->display_name = $venue->Venue_Name;
                $venue->capacity = $venue->Venue_Capacity;
                $venue->internal_price = $venue->Venue_Internal_Price;
                $venue->external_price = $venue->Venue_External_Price;
                $venue->image = $venue->Venue_Image;

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
        //  dd($all_accommodations);

        return view('client.room_venue', compact('all_accommodations'));
    }
    public function adminIndex(Request $request)
    {
        $search = $request->search;
        $status = $request->status;

        $rooms = Room::query()
            ->when($search, function ($query) use ($search) {
                $query->where('Room_Number', 'ilike', "%{$search}%")
                    ->orWhere('Room_Type', 'ilike', "%{$search}%");
            })
            ->when($status, function ($query) use ($status) {
                $query->where('Room_Status', $status);
            })
            ->get();

        $venues = Venue::query()
            ->when($search, function ($query) use ($search) {
                $query->where('Venue_Name', 'ilike', "%{$search}%");
            })
            ->when($status, function ($query) use ($status) {
                $query->where('Venue_Status', $status);
            })
            ->get();

        // FIX: Group the food items by category and make keys lowercase
        $foods = Food::all()->groupBy(function($item) {
            return strtolower($item->Food_Category);
        });

        return view('employee.room_venue', compact('rooms', 'venues', 'foods'));
        }
        public function show($category, $id)
        {
            // 1. Find the correct item based on category
            if (strtolower($category) === 'room') {
                $data = Room::findOrFail($id);
                $data->id = $data->Room_ID;
                $data->display_name = "Room " . ($data->Room_Number ?? $id);
                $data->capacity= $data->Room_Capacity;
                $data->internal_price = $data->Room_Internal_Price;
                $data->external_price = $data->Room_External_Price;
                $data->status = $data->Room_Status;
                $data->description = $data->Room_Description;
                $data->image = $data->Room_Image;

                // Use RoomReservation model
                $reservations = RoomReservation::where('Room_ID', $id)
                    ->whereIn('Room_Reservation_Status', ['pending', 'confirmed', 'checked-in'])
                    ->get();

                $dateFieldIn = 'Room_Reservation_Check_In_Time';
                $dateFieldOut = 'Room_Reservation_Check_Out_Time';
            } else {
                $data = Venue::findOrFail($id); 
                $data->id = $data->Venue_ID;
                $data->display_name = $data->Venue_Name;
                $data->capacity= $data->Venue_Capacity;
                $data->internal_price = $data->Venue_Internal_Price;
                $data->external_price = $data->Venue_External_Price;
                $data->status = $data->Venue_Status;
                $data->description = $data->Venue_Description;
                $data->image = $data->Venue_Image;

                // Use VenueReservation model
                $reservations = VenueReservation::where('Venue_ID', $id)
                    ->whereIn('Venue_Reservation_Status', ['pending', 'confirmed', 'checked-in'])
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
    public function prepareBooking(Request $request)
    {
        // Grab all the data the user just submitted (dates, pax, accommodation_id, type)
        $bookingData = $request->all();
        // dd($bookingData);

        // 1. If it's a Room, skip food and go straight to Checkout
        if ($request->type === 'room') {
            return redirect()->route('checkout', $bookingData);
        }

        // 2. If it's a Venue, fetch the food and go to the Food Options page
        if ($request->type === 'venue') {

            // FETCH THE AVAILABLE FOOD HERE
//            $foods = Food::where('status', 'available')->get()->groupBy('Food_Category');
                $foods = Food::where('Food_Status', 'available')->get()->groupBy('Food_Category');

            // PASS BOTH bookingData AND foods TO THE VIEW
            return view('client.food_option', compact('bookingData', 'foods'));
        }
    }

    public function update(Request $request)
    {
        $request->validate([
            'id'             => 'required|integer',
            'category'       => 'required|in:Room,Venue',
            'name'           => 'required|string',
            'internal_price' => 'required|numeric',
            'external_price' => 'required|numeric',
            'capacity'       => 'required|integer',
            'status'         => 'required|in:Available,Unavailable',
            'type'           => 'nullable|string',
            'description'    => 'nullable|string',
            'image'          => 'nullable|image|mimes:jpeg,jpg,png,webp|max:5120',
        ]);

        if ($request->category === 'Room') {
            $room = Room::findOrFail($request->id);

            $data = [
                'Room_Number'         => $request->name,
                'Room_Type'           => $request->type ?? 'Standard',
                'Room_Capacity'       => $request->capacity,
                'Room_Internal_Price' => $request->internal_price,
                'Room_External_Price' => $request->external_price,
                'Room_Status'         => $request->status,
                'Room_Description'    => $request->description,
            ];

            if ($request->hasFile('image') && $request->file('image')->isValid()) {
                // Delete old image file if it exists
                if ($room->Room_Image) {
                    $old = storage_path('app/public/' . $room->Room_Image);
                    if (file_exists($old)) @unlink($old);
                }
                $data['Room_Image'] = $this->processAndStoreImage($request->file('image'), 'rooms');
            }

            $room->update($data);

        } else {
            $venue = Venue::findOrFail($request->id);

            $data = [
                'Venue_Name'           => $request->name,
                'Venue_Capacity'       => $request->capacity,
                'Venue_Internal_Price' => $request->internal_price,
                'Venue_External_Price' => $request->external_price,
                'Venue_Status'         => $request->status,
                'Venue_Description'    => $request->description,
            ];

            if ($request->hasFile('image') && $request->file('image')->isValid()) {
                if ($venue->Venue_Image) {
                    $old = storage_path('app/public/' . $venue->Venue_Image);
                    if (file_exists($old)) @unlink($old);
                }
                $data['Venue_Image'] = $this->processAndStoreImage($request->file('image'), 'venues');
            }

            $venue->update($data);
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
            $data->id           = $data->Room_ID;
            $data->display_name = "Room " . $data->Room_Number . " (" . $data->Room_Type . ")";
            $data->capacity     = $data->Room_Capacity;
            $data->status       = $data->Room_Status;
            $data->external_price = $data->Room_External_Price;
            $data->description  = $data->Room_Description;
            $data->image        = $data->Room_Image;

            $reservations = RoomReservation::where('Room_ID', $id)
                ->get(['Room_Reservation_Check_In_Time', 'Room_Reservation_Check_Out_Time']);

        } else {
            $data = Venue::findOrFail($id);
            $data->id           = $data->Venue_ID;
            $data->display_name = $data->Venue_Name;
            $data->capacity     = $data->Venue_Capacity;
            $data->status       = $data->Venue_Status;
            $data->external_price = $data->Venue_External_Price;
            $data->description  = $data->Venue_Description;
            $data->image        = $data->Venue_Image;

            $reservations = VenueReservation::where('Venue_ID', $id)
                ->get(['Venue_Reservation_Check_In_Time', 'Venue_Reservation_Check_Out_Time']);
        }

        $client = Account::findOrFail($userId);

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
            $checkIn  = $category === 'Room'
                ? $res->Room_Reservation_Check_In_Time
                : $res->Venue_Reservation_Check_In_Time;
            $checkOut = $category === 'Room'
                ? $res->Room_Reservation_Check_Out_Time
                : $res->Venue_Reservation_Check_Out_Time;

            if (!$checkIn || !$checkOut) continue;

            $period = CarbonPeriod::create($checkIn, $checkOut);
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

