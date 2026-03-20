<?php

namespace App\Http\Controllers;

use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SignupController extends Controller
{
    public function showSignupForm()
    {
        return view('pages/signup');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'firstName'   => 'required|string|max:100',
            'lastName'    => 'required|string|max:100',
            'username'    => 'required|string|max:50|unique:Account,Account_Username',
            'email'       => 'required|email|unique:Account,Account_Email',
            'phone'       => ['required', 'regex:/^0[0-9]{10}$/'],
            'affiliation' => 'required|string',
            'validId'     => 'required|image|max:2048',
        ]);

        $path = $request->file('validId')->store('ids', 'public');

        $mappedUserType = match($request->affiliation) {
            'student', 'faculty', 'staff' => 'Internal',
            default                        => 'External',
        };

        Account::create([
            'Account_Name'        => $validated['firstName'] . ' ' . $validated['lastName'],
            'Account_Username'    => $validated['username'],
            'Account_Email'       => $validated['email'],
            'Account_Phone'       => $validated['phone'], // only if this column exists
            'Account_Affiliation' => $validated['affiliation'],
            'Account_Type'        => $mappedUserType,
            'valid_id_path'       => $path,
            'Account_Role'        => 'client',
            'Account_Status'      => 'pending',
        ]);

        return redirect()->route('login')
            ->with('success', 'Registration submitted! Your account is under review. Once approved, your login credentials will be sent to your registered email.');
    }
}
