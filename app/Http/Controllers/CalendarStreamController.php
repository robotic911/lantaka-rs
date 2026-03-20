<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Models\RoomReservation;
use App\Models\VenueReservation;

class CalendarStreamController extends Controller
{
    public function stream(Request $request)
    {
        // ── Critical: release the session lock immediately ──
        // Without this, every other request (client bookings, POSTs, etc.)
        // that shares the same session will block waiting for this SSE
        // connection to finish — which is never, causing timeouts + errors.
        $request->session()->save();

        return response()->stream(function () {
            // Allow the script to run as long as the browser keeps the connection open
            set_time_limit(0);
            // Let PHP detect client disconnects so we can break the loop cleanly
            ignore_user_abort(true);

            while (true) {
                // Stop looping as soon as the browser tab closes / navigates away
                if (connection_aborted()) {
                    break;
                }

                try {
                    $roomRes = RoomReservation::with(['room', 'user'])->get()->map(function ($item) {
                        return [
                            'id'        => $item->Room_Reservation_ID,
                            'status'    => strtolower($item->Room_Reservation_Status),
                            'check_in'  => \Carbon\Carbon::parse($item->Room_Reservation_Check_In_Time)->format('Y-m-d'),
                            'check_out' => \Carbon\Carbon::parse($item->Room_Reservation_Check_Out_Time)->format('Y-m-d'),
                            'user'      => $item->user,
                            'room'      => $item->room,
                            'label'     => $item->room ? "Room " . $item->room->Room_Number : "Room N/A",
                            'type'      => 'room',
                        ];
                    });

                    $venueRes = VenueReservation::with(['venue', 'user'])->get()->map(function ($item) {
                        return [
                            'id'        => $item->Venue_Reservation_ID,
                            'status'    => strtolower($item->Venue_Reservation_Status),
                            'check_in'  => \Carbon\Carbon::parse($item->Venue_Reservation_Check_In_Time)->format('Y-m-d'),
                            'check_out' => \Carbon\Carbon::parse($item->Venue_Reservation_Check_Out_Time)->format('Y-m-d'),
                            'user'      => $item->user,
                            'venue'     => $item->venue,
                            'label'     => $item->venue ? $item->venue->Venue_Name : "Venue N/A",
                            'type'      => 'venue',
                        ];
                    });

                    $reservations = $roomRes->concat($venueRes);

                    echo "event: reservations\n";
                    echo "data: " . json_encode($reservations) . "\n\n";

                } catch (\Throwable $e) {
                    // Don't kill the stream on a DB hiccup — send a comment line
                    // (comments are ignored by the browser but keep the connection alive)
                    echo ": db-error-skipped\n\n";
                }

                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();

                sleep(5);
            }
        }, 200, [
            'Content-Type'      => 'text/event-stream',
            'Cache-Control'     => 'no-cache',
            'Connection'        => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }
}
