<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
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
            $user->status = 'declined';
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
}