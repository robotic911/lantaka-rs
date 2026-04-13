<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\RoomReservation;
use App\Models\VenueReservation;
use App\Models\Room;
use App\Models\Venue;
use App\Models\Account;
use App\Models\Food;
use Carbon\CarbonPeriod;

class RoomVenueController extends Controller
{
    /**
     * Resize + compress an uploaded image with GD, then store it on the
     * configured media disk (local 'public' disk or S3 in production).
     *
     * Always outputs JPEG, max 1200×900 px at 82% quality, never upscales.
     * Returns the stored path (relative to the disk root), e.g. "rooms/abc123.jpg".
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

        // If GD can't decode it, fall back to storing the original file as-is.
        if (! $src) {
            $name = uniqid() . '.jpg';
            $path = $folder . '/' . $name;
            Storage::disk(media_disk())->putFileAs($folder, $file, $name, 'public');
            return $path;
        }

        // Resize (never upscale)
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

        // Write to a PHP temp file, then stream it to the media disk.
        $tmpPath = tempnam(sys_get_temp_dir(), 'lrs_img_') . '.jpg';
        imagejpeg($dst, $tmpPath, 82);
        imagedestroy($dst);

        $storedName = uniqid() . '.jpg';
        $storedPath = $folder . '/' . $storedName;
        Storage::disk(media_disk())->putFileAs($folder, new \Illuminate\Http\File($tmpPath), $storedName, 'public');

        @unlink($tmpPath); // clean up the local temp file

        return $storedPath;
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
        $dateFrom = $request->date_from;
        $dateTo   = $request->date_to;

        // When a date range is given, exclude rooms/venues with overlapping reservations
        $bookedRoomIds = collect();
        $bookedVenueIds = collect();

        if ($dateFrom && $dateTo) {
            $checkFrom = \Carbon\Carbon::parse($dateFrom)->startOfDay();
            $checkTo   = \Carbon\Carbon::parse($dateTo)->endOfDay();

            $bookedRoomIds = RoomReservation::whereIn('Room_Reservation_Status', ['pending', 'confirmed', 'checked-in'])
                ->where('Room_Reservation_Check_In_Time',  '<', $checkTo)
                ->where('Room_Reservation_Check_Out_Time', '>', $checkFrom)
                ->pluck('Room_ID')->unique();

            $bookedVenueIds = VenueReservation::whereIn('Venue_Reservation_Status', ['pending', 'confirmed', 'checked-in'])
                ->where('Venue_Reservation_Check_In_Time',  '<', $checkTo)
                ->where('Venue_Reservation_Check_Out_Time', '>', $checkFrom)
                ->pluck('Venue_ID')->unique();
        }

        // Distinct room types for the dynamic filter buttons (only from non-maintenance rooms)
        $roomTypes = Room::query()
            ->whereNotNull('Room_Type')
            ->where('Room_Type', '!=', '')
            ->where('Room_Status', '!=', 'UnderMaintenance')
            ->distinct()
            ->orderBy('Room_Type')
            ->pluck('Room_Type');

        // 1. Get filtered Rooms (always exclude under-maintenance)
        $rooms = Room::query()
            ->where('Room_Status', '!=', 'UnderMaintenance')
            ->when($request->capacity, function ($query, $capacity) {
                if ($capacity == '50+') return $query->where('Room_Capacity', '>=', 50);
                return $query->where('Room_Capacity', '>=', (int)$capacity);
            })
            ->when($request->room_type, function ($query, $roomType) {
                $query->where('Room_Type', $roomType);
            })
            ->when($dateFrom && $dateTo, function ($query) use ($bookedRoomIds) {
                $query->whereNotIn('Room_ID', $bookedRoomIds);
            })
            ->get()
            ->map(function ($room) {
                $room->category      = 'Room';
                $room->id            = $room->Room_ID;
                $room->display_name  = "Room " . $room->Room_Number . " (" . $room->Room_Type . ")";
                $room->capacity      = $room->Room_Capacity;
                $room->internal_price = $room->Room_Internal_Price;
                $room->external_price = $room->Room_External_Price;
                $room->image         = $room->Room_Image;
                return $room;
            });

        // 2. Get filtered Venues (always exclude under-maintenance)
        $venues = Venue::query()
            ->where('Venue_Status', '!=', 'UnderMaintenance')
            ->when($request->capacity, function ($query, $capacity) {
                if ($capacity == '50+') return $query->where('Venue_Capacity', '>=', 50);
                return $query->where('Venue_Capacity', '>=', (int)$capacity);
            })
            ->when($dateFrom && $dateTo, function ($query) use ($bookedVenueIds) {
                $query->whereNotIn('Venue_ID', $bookedVenueIds);
            })
            ->get()
            ->map(function ($venue) {
                $venue->category      = 'Venue';
                $venue->id            = $venue->Venue_ID;
                $venue->display_name  = $venue->Venue_Name;
                $venue->capacity      = $venue->Venue_Capacity;
                $venue->internal_price = $venue->Venue_Internal_Price;
                $venue->external_price = $venue->Venue_External_Price;
                $venue->image         = $venue->Venue_Image;
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

        return view('client.room_venue', compact('all_accommodations', 'dateFrom', 'dateTo', 'roomTypes'));
    }
    public function adminIndex(Request $request)
    {
        $search       = $request->search;
        $statusFilter = $request->status;
        $dateFrom     = $request->date_from;
        $dateTo       = $request->date_to;

        // Window to compute effective status — defaults to today
        $checkFrom = $dateFrom
            ? \Carbon\Carbon::parse($dateFrom)->startOfDay()
            : \Carbon\Carbon::now()->startOfDay();
        $checkTo   = $dateTo
            ? \Carbon\Carbon::parse($dateTo)->endOfDay()
            : \Carbon\Carbon::now()->endOfDay();

        // ── Rooms ──────────────────────────────────────────────────────
        $rooms = Room::query()
            ->when($search, function ($q) use ($search) {
                $q->where('Room_Number', 'ilike', "%{$search}%")
                  ->orWhere('Room_Type', 'ilike', "%{$search}%");
            })
            ->get();

        // IDs that are checked-in during the window
        $occupiedRoomIds = RoomReservation::where('Room_Reservation_Status', 'checked-in')
            ->where('Room_Reservation_Check_In_Time',  '<', $checkTo)
            ->where('Room_Reservation_Check_Out_Time', '>', $checkFrom)
            ->pluck('Room_ID')->unique();

        // IDs that are pending/confirmed during the window
        $reservedRoomIds = RoomReservation::whereIn('Room_Reservation_Status', ['pending', 'confirmed'])
            ->where('Room_Reservation_Check_In_Time',  '<', $checkTo)
            ->where('Room_Reservation_Check_Out_Time', '>', $checkFrom)
            ->pluck('Room_ID')->unique();

        $rooms = $rooms->map(function ($room) use ($occupiedRoomIds, $reservedRoomIds) {
            $raw = strtolower(str_replace([' ', '_'], '', $room->Room_Status));
            if ($raw === 'undermaintenance') {
                // Admin-set maintenance always wins
                $room->effective_status = 'undermaintenance';
            } elseif ($occupiedRoomIds->contains($room->Room_ID)) {
                // Active checked-in reservation takes priority
                $room->effective_status = 'occupied';
            } elseif ($reservedRoomIds->contains($room->Room_ID)) {
                // Pending/confirmed reservation takes priority
                $room->effective_status = 'reserved';
            } elseif ($raw === 'occupied') {
                // Admin manually marked as occupied (no reservation record)
                $room->effective_status = 'occupied';
            } elseif ($raw === 'reserved') {
                // Admin manually marked as reserved (no reservation record)
                $room->effective_status = 'reserved';
            } else {
                $room->effective_status = 'available';
            }
            return $room;
        });

        if ($statusFilter) {
            $rooms = $rooms->filter(fn ($r) => $r->effective_status === strtolower($statusFilter));
        }

        // ── Venues ─────────────────────────────────────────────────────
        $venues = Venue::query()
            ->when($search, function ($q) use ($search) {
                $q->where('Venue_Name', 'ilike', "%{$search}%");
            })
            ->get();

        $occupiedVenueIds = VenueReservation::where('Venue_Reservation_Status', 'checked-in')
            ->where('Venue_Reservation_Check_In_Time',  '<', $checkTo)
            ->where('Venue_Reservation_Check_Out_Time', '>', $checkFrom)
            ->pluck('Venue_ID')->unique();

        $reservedVenueIds = VenueReservation::whereIn('Venue_Reservation_Status', ['pending', 'confirmed'])
            ->where('Venue_Reservation_Check_In_Time',  '<', $checkTo)
            ->where('Venue_Reservation_Check_Out_Time', '>', $checkFrom)
            ->pluck('Venue_ID')->unique();

        $venues = $venues->map(function ($venue) use ($occupiedVenueIds, $reservedVenueIds) {
            $raw = strtolower(str_replace([' ', '_'], '', $venue->Venue_Status));
            if ($raw === 'undermaintenance') {
                $venue->effective_status = 'undermaintenance';
            } elseif ($occupiedVenueIds->contains($venue->Venue_ID)) {
                $venue->effective_status = 'occupied';
            } elseif ($reservedVenueIds->contains($venue->Venue_ID)) {
                $venue->effective_status = 'reserved';
            } elseif ($raw === 'occupied') {
                $venue->effective_status = 'occupied';
            } elseif ($raw === 'reserved') {
                $venue->effective_status = 'reserved';
            } else {
                $venue->effective_status = 'available';
            }
            return $venue;
        });

        if ($statusFilter) {
            $venues = $venues->filter(fn ($v) => $v->effective_status === strtolower($statusFilter));
        }

        return view('employee.room_venue', compact('rooms', 'venues', 'dateFrom', 'dateTo'));
        }
        public function show($category, $id)
        {
            // 1. Find the correct item based on category
            if (strtolower($category) === 'room') {
                $data = Room::findOrFail($id);

                // Block access to rooms under maintenance
                if ($data->Room_Status === 'UnderMaintenance') {
                    return redirect()->route('client.index')
                        ->with('error', 'This room is currently unavailable.');
                }

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

                // Block access to venues under maintenance
                if ($data->Venue_Status === 'UnderMaintenance') {
                    return redirect()->route('client.index')
                        ->with('error', 'This venue is currently unavailable.');
                }

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

            // 2a. Check if this is a change-request redirect — if so, the client's own
            //     reservation dates should be selectable (blue), not blocked (red).
            $currentReservationDates = [];
            $changeResId   = session('change_request_reservation_id');
            $changeResType = session('change_request_reservation_type');

            if ($changeResId && $changeResType) {
                try {
                    $origRes = ($changeResType === 'room')
                        ? \App\Models\RoomReservation::findOrFail($changeResId)
                        : \App\Models\VenueReservation::findOrFail($changeResId);

                    // Only use dates from THIS room/venue so other calendars aren't affected
                    $origAccId  = $changeResType === 'room' ? $origRes->Room_ID  : $origRes->Venue_ID;
                    $origIn     = $changeResType === 'room'
                        ? $origRes->Room_Reservation_Check_In_Time
                        : $origRes->Venue_Reservation_Check_In_Time;
                    $origOut    = $changeResType === 'room'
                        ? $origRes->Room_Reservation_Check_Out_Time
                        : $origRes->Venue_Reservation_Check_Out_Time;

                    if ((int) $origAccId === (int) $data->id) {
                        $period = CarbonPeriod::create($origIn, $origOut);
                        foreach ($period as $d) {
                            $currentReservationDates[] = $d->format('Y-m-d');
                        }
                        $currentReservationDates = array_values(array_unique($currentReservationDates));
                    }
                } catch (\Throwable $e) {
                    // Silently ignore — worst case the dates stay blocked
                }
            }

            // 2b. Map the occupied dates, excluding the client's own reservation dates
            $occupiedDates = [];
            foreach ($reservations as $res) {
                $period = CarbonPeriod::create($res->$dateFieldIn, $res->$dateFieldOut);
                foreach ($period as $date) {
                    $dateStr = $date->format('Y-m-d');
                    if (!in_array($dateStr, $currentReservationDates)) {
                        $occupiedDates[] = $dateStr;
                    }
                }
            }
            // Remove duplicate dates just in case, and reset array keys
            $occupiedDates = array_values(array_unique($occupiedDates));

            // 3. Pass the data, occupiedDates, and currentReservationDates to the view
            return view('client.room_venue_viewing', compact('data', 'category', 'occupiedDates', 'currentReservationDates'));
        }
    public function prepareBooking(Request $request)
    {
        // Grab all the data the user just submitted (dates, pax, accommodation_id, type)
        $bookingData = $request->all();
        // dd($bookingData);

        $isChangeRequest = ($request->input('change_request') == '1');

        // 1. If it's a Room, skip food
        if ($request->type === 'room') {
            // Change request: show confirmation page instead of adding to cart
            if ($isChangeRequest) {
                return view('client.change_request_confirm', compact('bookingData'));
            }
            return redirect()->route('checkout', $bookingData);
        }

        // 2. If it's a Venue, fetch the food and go to the Food Options page
        if ($request->type === 'venue') {

            // If the user chose to skip food (pax below minimum)
            if ($request->input('skip_food') == '1') {
                // Change request: show confirmation page (no food step)
                if ($isChangeRequest) {
                    return view('client.change_request_confirm', compact('bookingData'));
                }
                return redirect()->route('checkout', $bookingData);
            }

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
            'status'         => 'nullable|in:Available,Occupied,UnderMaintenance,Reserved',
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
                'Room_Status'         => $request->status ?? $room->Room_Status,
                'Room_Description'    => $request->description,
            ];

            if ($request->hasFile('image') && $request->file('image')->isValid()) {
                // Delete old image from the media disk (works for both local and S3)
                if ($room->Room_Image) {
                    Storage::disk(media_disk())->delete($room->Room_Image);
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
                'Venue_Status'         => $request->status ?? $venue->Venue_Status,
                'Venue_Description'    => $request->description,
            ];

            if ($request->hasFile('image') && $request->file('image')->isValid()) {
                if ($venue->Venue_Image) {
                    Storage::disk(media_disk())->delete($venue->Venue_Image);
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
            $data->internal_price = $data->Room_Internal_Price;
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
            $data->internal_price = $data->Venue_Internal_Price;
            $data->external_price = $data->Venue_External_Price;
            $data->description  = $data->Venue_Description;
            $data->image        = $data->Venue_Image;

            $reservations = VenueReservation::where('Venue_ID', $id)
                ->get(['Venue_Reservation_Check_In_Time', 'Venue_Reservation_Check_Out_Time']);
        }

        $client = Account::findOrFail($userId);

        // Prefill data for edit mode (passed via query string from the employee modal).
        // Guard against the literal string "null" being sent by JS when no reservation
        // is being edited — treat it as PHP null so find() is never called with it.
        $rawResId      = $request->reservation_id;
        $reservationId = ($rawResId !== null && $rawResId !== '' && strtolower($rawResId) !== 'null')
            ? (int) $rawResId
            : null;

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

