<?php

namespace App\Http\Controllers;



use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use App\Models\Account;
use App\Mail\ForgotPasswordMail;

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
            'Account_Username' => ['required'],
            'Account_Password' => ['required'],
        ]);

        // 2. Find the user
        $user = Account::where('Account_Username', $request->Account_Username)->first();

        // 3. CHECK: Does the username exist?
       // 1. USER NOT FOUND
        if (!$user) {
            return back()->withErrors([
                'Account_Username' => 'Invalid username or password. Please try again.',
            ])->onlyInput('Account_Username');
        }

        // 2. WRONG PASSWORD
        if (!Hash::check($request->Account_Password, $user->Account_Password)) {
            return back()->withErrors([
                'Account_Password' => 'Invalid username or password. Please try again.',
            ])->onlyInput('Account_Username');
        }

        // 3. ACCOUNT PENDING
        if (strtolower($user->Account_Role) === 'client' && $user->Account_Status === 'pending') {
            return back()->with('error', 'Your account is currently pending approval. Please wait for the administrator to review your registration.')
                        ->withInput($request->only('Account_Username'));
        }

        // 4. ACCOUNT DEACTIVATED
        if (strtolower($user->Account_Role) === 'client' && $user->Account_Status === 'deactivate') {
            return back()->with('error', 'Your account has been deactivated. Please contact support or the administrator for assistance.')
                        ->withInput($request->only('Account_Username'));
        }

        // 5. ACCOUNT DECLINED
        if (strtolower($user->Account_Role) === 'client' && $user->Account_Status === 'declined') {
            return back()->with('error', 'Your account registration was not approved. Please contact the administrator for more information.')
                        ->withInput($request->only('Account_Username'));
        }
        // --- NEW FEATURE END ---

        // 5. If all checks pass, log the user in
        Auth::login($user);
        $request->session()->regenerate();

        // Track first-ever login (enables cleanup of never-logged-in approved accounts)
        if (Schema::hasColumn('Account', 'last_login_at') && empty($user->last_login_at)) {
            $user->last_login_at = now();
            $user->save();
        }

        $role = strtolower($user->Account_Role);

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
     * Show the forgot password form.
     */
    public function showForgotPassword()
    {
        return view('pages.forgot_password');
    }

    /**
     * Handle forgot password request.
     * Finds the client by email, generates a new password using the same
     * format as account creation (lrs + 9 random chars), saves it, and emails it.
     */
    public function sendForgotPassword(Request $request)
    {
        $request->validate([
            'Account_Email' => ['required', 'email'],
        ]);

        $user = Account::where('Account_Email', $request->Account_Email)
                       ->where('Account_Role', 'client')
                       ->first();

        // Always return the same message to prevent email enumeration
        if (!$user) {
            return back()->with('success', 'If that email is registered to a client account, a new password has been sent to it.');
        }

        // Generate a new password using the same pattern as account creation
        $plainPassword = 'lrs' . Str::random(9);

        $user->Account_Password = Hash::make($plainPassword);
        $user->save();

        try {
            Mail::to($user->Account_Email)
                ->send(new ForgotPasswordMail($user, $plainPassword));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('ForgotPasswordMail failed', [
                'email' => $request->Account_Email,
                'error' => $e->getMessage(),
            ]);
        }

        return back()->with('success', 'If that email is registered to a client account, a new password has been sent to it.');
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
