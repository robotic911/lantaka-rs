<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\RoomReservation;
use App\Models\VenueReservation;
use App\Models\Room;
use App\Models\Venue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use App\Mail\AccountApprovedMail;
use App\Mail\AccountDeclinedMail;
use App\Mail\AccountReactivatedMail;
use App\Mail\AccountUpdatedMail;
use App\Http\Controllers\EventLogController;

class AccountController extends Controller
{
    /**
     * Delete approved accounts that have NEVER logged in
     * and whose password has been sitting unused for more than 7 days.
     * Silently skipped if the tracking columns don't exist yet.
     */
    private function cleanupExpiredAccounts(): void
    {
        if (! Schema::hasColumn('Account', 'password_set_at')) {
            return; // Migration not run yet — skip silently
        }

        Account::where('Account_Status', 'approved')
            ->whereNull('last_login_at')
            ->where('password_set_at', '<', now()->subDays(7))
            ->delete();
    }

    public function index(Request $request)
    {
        $this->cleanupExpiredAccounts();

        $status = $request->query('status');
        $role   = $request->query('Account_Role');
        $search = $request->query('search');

        $query = Account::query();

        // Search by name or email
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('Account_Name',  'ILIKE', "%{$search}%")
                  ->orWhere('Account_Email', 'ILIKE', "%{$search}%");
            });
        }

        if ($role === 'employee') {
            $query->whereIn('Account_Role', ['admin', 'staff', 'Admin', 'Staff']);
        } elseif ($status) {
            $query->where('Account_Status', $status);
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(15)->withQueryString();

        return view('employee.accounts', compact('users'));
    }
    public function updateStatus(Request $request, $id)
    {
        $user   = Account::findOrFail($id);
        $status = $request->input('status'); // 'approved' or 'declined'

        if ($status === 'approved') {
            // Generate a secure password: prefix "lrs" + 9 random alphanumeric chars
            $plainPassword = 'lrs' . Str::random(9);

            $user->Account_Status   = 'approved';
            $user->Account_Password = Hash::make($plainPassword);

            // Only set tracking columns if the migration has been run
            if (Schema::hasColumn('Account', 'password_set_at')) {
                $user->password_set_at = now();
                $user->last_login_at   = null; // ensure clean state
            }

            $user->save();

            try {
                Mail::to($user->Account_Email)->send(new AccountApprovedMail($user, $plainPassword));
            } catch (\Exception $e) {
                \Log::error('AccountApprovedMail failed: ' . $e->getMessage(), [
                    'account_id' => $user->Account_ID,
                ]);
            }

            EventLogController::log(
                'account_approved',
                "Account approved for {$user->Account_Name} ({$user->Account_Email}). Credentials sent via email."
            );

            return response()->json([
                'success' => true,
                'message' => 'Account approved. Login credentials have been sent to the user\'s email.',
            ]);
        }

        // Declined
        $user->Account_Status = 'declined';
        $user->save();

        try {
            Mail::to($user->Account_Email)->send(new AccountDeclinedMail($user));
        } catch (\Exception $e) {
            \Log::error('AccountDeclinedMail failed: ' . $e->getMessage(), [
                'account_id' => $user->Account_ID,
            ]);
        }

        EventLogController::log(
            'account_declined',
            "Account declined for {$user->Account_Name} ({$user->Account_Email})."
        );

        return response()->json([
            'success' => true,
            'message' => 'Account declined. The user has been notified via email.',
        ]);
    }
    public function update(Request $request, $id)
    {
        $user = Account::findOrFail($id);

        // 1a. Handle Deactivation
        if ($request->action === 'deactivate') {
            $user->Account_Status = 'deactivate';
            $user->save();
            EventLogController::log(
                'account_deactivated',
                "Account deactivated for {$user->Account_Name} ({$user->Account_Email})."
            );
            return redirect()->back()->with('success', 'Account deactivated.');
        }

        // 1b. Handle Reactivation — generate a fresh password and email it
        if ($request->action === 'reactivate') {
            $plainPassword = 'lrs' . Str::random(9);

            $user->Account_Status   = 'approved';
            $user->Account_Password = Hash::make($plainPassword);

            if (Schema::hasColumn('Account', 'password_set_at')) {
                $user->password_set_at = now();
                $user->last_login_at   = null; // reset so cleanup timer restarts
            }

            $user->save();

            try {
                Mail::to($user->Account_Email)->send(new AccountReactivatedMail($user, $plainPassword));
            } catch (\Throwable $e) {
                // Mail failure should not block the response
            }

            EventLogController::log(
                'account_reactivated',
                "Account reactivated for {$user->Account_Name} ({$user->Account_Email}). New credentials sent via email."
            );

            return redirect()->back()->with('success', 'Account reactivated. New login credentials have been sent to the user\'s email.')->with('email_sent', true);
        }

        // 2. Validation
        $request->validate([
            'username'   => 'required|string|max:255',
            'first_name' => 'required|string|max:255',
            'last_name'  => 'required|string|max:255',
            'email'      => 'required|email|unique:Account,Account_Email,' . $id . ',Account_ID',
            'phone_no'   => 'nullable|string',
        ]);

        // 3. Track what changed (for email notification)
        $changedFields = [];
        if ($user->Account_Name     !== trim($request->first_name . ' ' . $request->last_name)) $changedFields[] = 'Name';
        if ($user->Account_Username !== $request->username)   $changedFields[] = 'Username';
        if ($user->Account_Email    !== $request->email)      $changedFields[] = 'Email';
        if ($user->Account_Phone    !== $request->phone_no)   $changedFields[] = 'Phone Number';
        if ($request->hasFile('valid_id'))                     $changedFields[] = 'Valid ID';
        if ($request->filled('password'))                      $changedFields[] = 'Password';

        // 4. Mapping Data to DB Columns
        $user->Account_Name     = trim($request->first_name . ' ' . $request->last_name);
        $user->Account_Username = $request->username;
        $user->Account_Email    = $request->email;
        $user->Account_Phone    = $request->phone_no;

        if ($request->hasFile('valid_id')) {
            $path = $request->file('valid_id')->store('ids', media_disk());
            $user->valid_id_path = $path;
        }

        if ($request->filled('password')) {
            $user->Account_Password = bcrypt($request->password);
        }

        $user->save();

        // Notify the user that their account details were changed
        try {
            Mail::to($user->Account_Email)->send(new AccountUpdatedMail($user, $changedFields));
        } catch (\Throwable $e) {
            // Mail failure should not block the response
        }

        EventLogController::log(
            'account_updated',
            "Account details updated for {$user->Account_Name} ({$user->Account_Email})."
        );

        return redirect()->back()->with('success', 'Account updated successfully.')->with('email_sent', true);
    }

        public function searchAccounts(Request $request)
        {
            $search = $request->search;

            $users = Account::where('Account_Role', 'client')
                ->where('Account_Status', 'approved')
                ->where(function ($q) use ($search) {
                    $q->where('Account_Name', 'ilike', "%{$search}%")
                      ->orWhere('Account_Email', 'ilike', "%{$search}%");
                })
                ->limit(10)
                ->get(['Account_ID','Account_Name','Account_Email'])
                ->map(fn($u) => [
                    'id'    => $u->Account_ID,
                    'name'  => $u->Account_Name,
                    'email' => $u->Account_Email,
                ]);

            return response()->json($users);
        }

    /* ────────────────────────────────────────────────
     *  Admin: Manually Create Account
     * ──────────────────────────────────────────────── */
    public function adminCreateAccount(Request $request)
    {
        $request->validate([
            'firstName'   => 'required|string|max:100',
            'lastName'    => 'required|string|max:100',
            'username'    => 'required|string|max:50|unique:Account,Account_Username',
            'email'       => 'required|email|unique:Account,Account_Email',
            'phone'       => ['required', 'regex:/^0[0-9]{10}$/'],
            'affiliation' => 'required|string|in:student,faculty,organization,external,staff',
            'password'    => 'required|string|min:8|confirmed',
            'validId'     => 'nullable|image|max:4096',
        ]);

        $isStaff = $request->affiliation === 'staff';
        $role    = $isStaff ? 'staff' : 'client';
        $type    = in_array($request->affiliation, ['student', 'faculty', 'organization', 'staff'])
                     ? 'Internal'
                     : 'External';

        $fullName = trim($request->firstName . ' ' . $request->lastName);

        $idPath = $request->hasFile('validId')
            ? $request->file('validId')->store('ids', media_disk())
            : null;

        Account::create([
            'Account_Name'        => $fullName,
            'Account_Username'    => $request->username,
            'Account_Email'       => $request->email,
            'Account_Phone'       => $request->phone,
            'Account_Password'    => Hash::make($request->password),
            'Account_Affiliation' => $request->affiliation,
            'Account_Type'        => $type,
            'Account_Role'        => $role,
            'Account_Status'      => 'approved',
            'valid_id_path'       => $idPath,
        ]);

        EventLogController::log(
            'account_created',
            "Account manually created by admin for {$fullName} ({$request->email}) as {$role}."
        );

        return response()->json([
            'success' => true,
            'message' => "Account for {$fullName} created successfully.",
        ]);
    }

    /* ────────────────────────────────────────────────
     *  Client Account Page
     * ──────────────────────────────────────────────── */
    public function showClientAccount()
    {
        $user = Auth::user();

        // Reservation counts
        $roomReservations  = RoomReservation::where('Client_ID', $user->Account_ID)->get();
        $venueReservations = VenueReservation::where('Client_ID', $user->Account_ID)->get();

        $allStatuses = $roomReservations->merge($venueReservations);

        $totalCount     = $allStatuses->count();
        $pendingCount   = $allStatuses->filter(fn($s) => in_array($s->Room_Reservation_Status ?? $s->Venue_Reservation_Status ?? '', ['pending']))->count();
        $confirmedCount = $allStatuses->filter(fn($s) => in_array($s->Room_Reservation_Status ?? $s->Venue_Reservation_Status ?? '', ['confirmed', 'approved']))->count();
        $completedCount = $allStatuses->filter(fn($s) => in_array($s->Room_Reservation_Status ?? $s->Venue_Reservation_Status ?? '', ['completed', 'checked-out']))->count();

        // Build recent reservations (latest 5 across both types)
        $recentRoom = $roomReservations->sortByDesc('created_at')->take(5)->map(function ($r) {
            $room = Room::find($r->Room_ID);
            return [
                'id'        => $r->getKey(),
                'name'      => $room ? 'Room ' . ($room->Room_Number ?? $room->getKey()) : 'Room',
                'type'      => 'room',
                'check_in'  => $r->Room_Reservation_Check_In_Time,
                'check_out' => $r->Room_Reservation_Check_Out_Time,
                'status'    => $r->Room_Reservation_Status,
                'created_at'=> $r->created_at,
            ];
        });

        $recentVenue = $venueReservations->sortByDesc('created_at')->take(5)->map(function ($r) {
            $venue = Venue::find($r->Venue_ID);
            return [
                'id'        => $r->Venue_Reservation_ID,
                'name'      => $venue ? ($venue->Venue_Name ?? 'Venue') : 'Venue',
                'type'      => 'venue',
                'check_in'  => $r->Venue_Reservation_Check_In_Time,
                'check_out' => $r->Venue_Reservation_Check_Out_Time,
                'status'    => $r->Venue_Reservation_Status,
                'created_at'=> $r->created_at,
            ];
        });

        $recentReservations = $recentRoom->merge($recentVenue)
            ->sortByDesc('created_at')
            ->take(5)
            ->values();

        // Compute initials from the account name (e.g. "Juan dela Cruz" → "JD")
        $nameParts = array_filter(explode(' ', $user->Account_Name));
        $initials   = strtoupper(
            (isset($nameParts[0]) ? $nameParts[0][0] : '') .
            (count($nameParts) > 1 ? end($nameParts)[0] : '')
        );

        return view('client.account', compact(
            'user',
            'initials',
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
            'name'     => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:Account,Account_Username,' . $user->Account_ID . ',Account_ID',
            'email'    => 'required|email|unique:Account,Account_Email,' . $user->Account_ID . ',Account_ID',
            'phone'    => 'nullable|string|max:20',
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        $user->Account_Name     = $request->name;
        $user->Account_Username = $request->username;
        $user->Account_Email    = $request->email;
        $user->Account_Phone    = $request->phone;

        if ($request->filled('password')) {
            $user->Account_Password = Hash::make($request->password);
        }

        $user->save();

        return redirect()->route('client.account')
            ->with('success', 'Your profile has been updated successfully.');
    }
}
