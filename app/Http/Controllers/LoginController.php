<?php

namespace App\Http\Controllers;



use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash; // Needed for password checking
use App\Models\User;

class LoginController extends Controller
{
    /**
     * Show the login form.
     */
    public function showLoginForm()
    {
        return view('pages/login'); // This matches your login.blade.php
    }

    /**
     * Handle the login request.
     */
    public function login(Request $request)
    {
        // 1. Validate Input
        $request->validate([
            'username' => ['required'],
            'password' => ['required'],
        ]);

        // 2. Find the user
        $user = User::where('username', $request->username)->first();

        // 3. CHECK: Does the username exist?
        if (!$user) {
            return back()->withErrors([
                'username' => 'This username does not exist.',
            ])->onlyInput('username');
        }

        // 4. CHECK: Is the password correct?
        if (!Hash::check($request->password, $user->password)) {
            return back()->withErrors([
                'password' => 'Incorrect password.',
            ])->onlyInput('username');
        }

        if (strtolower($user->role) === 'client' && $user->status !== 'approved') {
            return back()->with('error', 'Your account is pending admin approval. Please check your email for updates.')
                        ->withInput($request->only('username'));
        }
        // --- NEW FEATURE END ---

        // 5. If all checks pass, log the user in
        Auth::login($user);
        $request->session()->regenerate();
        $role = strtolower($user->role);

        // 6. Redirect based on role
        if (in_array($role, ['admin', 'staff'])) {
            return redirect()->route('employee.dashboard');
        }
        
        if ($role === 'client') {
            return redirect()->route('client.room_venue');
        }
        
        return redirect()->route('index');
    }

    /**
     * Log the user out.
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}