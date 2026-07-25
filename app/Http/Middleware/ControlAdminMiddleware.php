<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ControlAdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return redirect()->route('control.login');
        }

        $user = Auth::user();

        // 1. Authorization Boundary Check
        if (! in_array($user->platform_role, ['admin', 'super_admin'])) {
            // Log out the user if they try to access console without permissions
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            abort(403, 'Unauthorized access to Operations Console.');
        }

        // 2. Strict 30-minute inactivity timeout for Operations Console
        $lastActivity = $request->session()->get('control_last_activity');
        $timeout = 30 * 60; // 30 minutes in seconds

        if ($lastActivity && (time() - $lastActivity > $timeout)) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            
            return redirect()->route('control.login')->withErrors([
                'email' => 'Your session has expired due to 30 minutes of inactivity.',
            ]);
        }

        // Update last activity
        $request->session()->put('control_last_activity', time());

        return $next($request);
    }
}
