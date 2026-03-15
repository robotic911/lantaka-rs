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
       // 1. USER NOT FOUND
        if (!$user) {
            return back()->withErrors([
                'username' => 'Invalid username or password. Please try again.',
            ])->onlyInput('username');
        }

        // 2. WRONG PASSWORD
        if (!Hash::check($request->password, $user->password)) {
            return back()->withErrors([
                'password' => 'Invalid username or password. Please try again.',
            ])->onlyInput('username');
        }

        // 3. ACCOUNT PENDING
        if (strtolower($user->role) === 'client' && $user->status === 'pending') {
            return back()->with('error', 'Your account is currently pending approval. Please wait for the administrator to review your registration.')
                        ->withInput($request->only('username'));
        }

        // 4. ACCOUNT DEACTIVATED
        if (strtolower($user->role) === 'client' && $user->status === 'deactivate') {
            return back()->with('error', 'Your account has been deactivated. Please contact support or the administrator for assistance.')
                        ->withInput($request->only('username'));
        }

        // 5. ACCOUNT DECLINED
        if (strtolower($user->role) === 'client' && $user->status === 'declined') {
            return back()->with('error', 'Your account registration was not approved. Please contact the administrator for more information.')
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