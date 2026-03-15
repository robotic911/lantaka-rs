<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\RoomReservation;
use App\Models\VenueReservation;
use App\Models\Room;
use App\Models\Venue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail; // REQUIRED for email
use App\Mail\AccountStatusMail;      // REQUIRED for email

class AccountController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status');
        $role = $request->query('role');

        $query = User::query();

        if ($role === 'employee') {
            $query->whereIn('role', ['admin', 'staff', 'Admin', 'Staff']);
        } elseif ($status) {
            $query->where('status', $status);
        }

        $users = $query->orderBy('created_at', 'desc')->get();
        $allForCounts = $users;
        return view('employee.accounts', compact('users'));
    }
    public function updateStatus(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $status = $request->input('status'); // 'approved' or 'declined'

        if ($status === 'approved') {
            $user->status = 'approved'; // Matches your "Approved" badge in the UI
        } else {
            $user->status = 'declined';
        }
        
        $user->save();
        $user = User::findOrFail($id);
        // Send the email with the dynamic data
        Mail::to($user->email)->send(new AccountStatusMail($user, $status));

        return response()->json([
            'success' => true,
            'message' => 'Account has been ' . $status . ' and the client has been notified.'
        ]);
    }
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        // 1. Handle Deactivation
        if ($request->action === 'deactivate') {
            $user->status = 'deactivate';
            $user->save();
            return redirect()->back()->with('success', 'Account deactivated.');
        }

        // 2. Validation
        $request->validate([
            'username'   => 'required|string|max:255',
            'first_name' => 'required|string|max:255',
            'last_name'  => 'required|string|max:255',
            'email'      => 'required|email|unique:users,email,' . $id,
            'phone_no'   => 'nullable|string', // Matches your Modal "name" attribute
        ]);

        // 3. Mapping Data to DB Columns
        // Combine names into the 'name' column
        $user->name = trim($request->first_name . ' ' . $request->last_name);
        
        $user->username = $request->username;
        $user->email = $request->email;
        
        // VERY IMPORTANT: Map 'phone_no' (HTML) to 'phone' (DB)
        $user->phone = $request->phone_no; 

        // Only update if you have the 'id_info' column in your DB
        // $user->id_info = $request->id_info; 

        if ($request->filled('password')) {
            $user->password = bcrypt($request->password);
        }

        $user->save();

        return redirect()->back()->with('success', 'Account updated successfully.');
    }

        public function searchAccounts(Request $request)
        {
            $search = $request->search;

            $users = User::where('name','ilike',"%{$search}%")
                ->orWhere('email','ilike',"%{$search}%")
                ->limit(10)
                ->get(['id','name','email']);

            return response()->json($users);
        }

    /* ────────────────────────────────────────────────
     *  Client Account Page
     * ──────────────────────────────────────────────── */
    public function showClientAccount()
    {
        $user = Auth::user();

        // Reservation counts
        $roomReservations  = RoomReservation::where('Client_ID', $user->id)->get();
        $venueReservations = VenueReservation::where('Client_ID', $user->id)->get();

        $allStatuses = $roomReservations->merge($venueReservations);

        $totalCount     = $allStatuses->count();
        $pendingCount   = $allStatuses->whereIn('status', ['pending'])->count();
        $confirmedCount = $allStatuses->whereIn('status', ['confirmed', 'approved'])->count();
        $completedCount = $allStatuses->whereIn('status', ['completed', 'checked-out'])->count();

        // Build recent reservations (latest 5 across both types)
        $recentRoom = $roomReservations->sortByDesc('created_at')->take(5)->map(function ($r) {
            $room = Room::find($r->room_id);
            return [
                'id'        => $r->getKey(),
                'name'      => $room ? 'Room ' . ($room->room_number ?? $room->id) : 'Room',
                'type'      => 'room',
                'check_in'  => $r->Room_Reservation_Check_In_Time,
                'check_out' => $r->Room_Reservation_Check_Out_Time,
                'status'    => $r->status,
                'created_at'=> $r->created_at,
            ];
        });

        $recentVenue = $venueReservations->sortByDesc('created_at')->take(5)->map(function ($r) {
            $venue = Venue::find($r->venue_id);
            return [
                'id'        => $r->Venue_Reservation_ID,
                'name'      => $venue ? ($venue->name ?? 'Venue') : 'Venue',
                'type'      => 'venue',
                'check_in'  => $r->Venue_Reservation_Check_In_Time,
                'check_out' => $r->Venue_Reservation_Check_Out_Time,
                'status'    => $r->status,
                'created_at'=> $r->created_at,
            ];
        });

        $recentReservations = $recentRoom->merge($recentVenue)
            ->sortByDesc('created_at')
            ->take(5)
            ->values();

        return view('client.account', compact(
            'user',
            'totalCount',
            'pendingCount',
            'confirmedCount',
            'completedCount',
            'recentReservations'
        ));
    }

    public function updateClientAccount(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'name'                  => 'required|string|max:255',
            'username'              => 'required|string|max:255|unique:users,username,' . $user->id,
            'email'                 => 'required|email|unique:users,email,' . $user->id,
            'phone'                 => 'nullable|string|max:20',
            'password'              => 'nullable|string|min:8|confirmed',
        ]);

        $user->name     = $request->name;
        $user->username = $request->username;
        $user->email    = $request->email;
        $user->phone    = $request->phone;

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        return redirect()->route('client.account')
            ->with('success', 'Your profile has been updated successfully.');
    }
}