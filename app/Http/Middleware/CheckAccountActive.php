<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckAccountActive
{
    /**
     * Force-logout any authenticated user whose account has been deactivated.
     * Runs on every web request so a session that was valid at login
     * is invalidated as soon as the account status changes to 'deactivate'.
     */
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check() && Auth::user()->Account_Status === 'deactivate') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->with('error', 'Your account has been deactivated. Please contact the administrator.');
        }

        return $next($request);
    }
}
