<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ControlGuestMiddleware
{
    /**
     * Handle an incoming request.
     *
     * Only redirect to dashboard if the user is fully authenticated
     * WITH an active control panel session (OTP verified).
     * Otherwise, let them access the control login/register pages.
     */
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check() && in_array(Auth::user()->platform_role, ['admin', 'super_admin'])) {
            $lastActivity = $request->session()->get('control_last_activity');
            if ($lastActivity && (time() - $lastActivity <= 30 * 60)) {
                return redirect()->route('control.overview');
            }
        }

        return $next($request);
    }
}
