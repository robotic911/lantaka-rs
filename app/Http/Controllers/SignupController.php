<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class SignupController extends Controller
{
    public function showSignupForm()
    {
        return view('pages/signup');
    }

    public function store(Request $request)
    {
        $request->validate([
            'username' => 'required|unique:users',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8|same:confirmPassword',
            'validId' => 'required|image|max:2048',
            'phone' => ['required', 'regex:/^0[0-9]{10}$/'],
            'affiliation' => 'required|string',
        ]);

        $path = $request->file('validId')->store('ids', 'public');

        // --- NEW LOGIC START ---
        // Map the affiliation to either 'Internal' or 'External'
        $mappedUserType = match($request->affiliation) {
            'student', 'faculty', 'staff' => 'Internal',
            'external' => 'External',
            default => 'External', // Fallback just in case
        };
        // --- NEW LOGIC END ---

        User::create([
            'name' => $request->firstName . ' ' . $request->lastName,
            'username' => $request->username,
            'email' => $request->email,
            'password' => \Illuminate\Support\Facades\Hash::make($request->password),
            'phone' => $request->phone,
            'affiliation' => $request->affiliation,
            'usertype' => $mappedUserType, // <--- Add the mapped variable here!
            'valid_id_path' => $path,
            'role' => 'client',
            'status' => 'pending',
        ]);

        return redirect()->route('login')->with('success', 'Registration successful! Please wait for admin approval.');
    }
}