<?php

namespace App\Http\Controllers\Control;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\PlatformSetting;
use App\Rules\StrictEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use App\Jobs\SendEmailJob;
use App\Enums\EmailTemplate;

class AuthController extends Controller
{
    /**
     * Handle email submission for login (Step 1).
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => ['required', 'string', new StrictEmail()],
        ]);

        $email = Str::lower(trim($request->email));
        $throttleKey = 'control_login_' . $request->ip() . '_' . $email;

        // Strict rate limiting: 5 attempts per 15 minutes
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return back()->withErrors([
                'email' => "Too many attempts. Please try again in {$seconds} seconds.",
            ]);
        }

        RateLimiter::hit($throttleKey, 15 * 60);

        // Verify if user exists and is an admin
        $user = User::where('email', $email)->first();
        if (! $user || ! in_array($user->platform_role, ['admin', 'super_admin'])) {
            // Explicit response to inform the user they lack privileges
            return back()->withErrors([
                'email' => 'Unauthorized access. This account does not have administrative privileges.',
            ]);
        }

        // Generate and store OTP (Cache only, 5 mins, Hashed)
        $otp = (string) random_int(100000, 999999);
        if (app()->environment('local')) {
            $otp = '123456';
            \Illuminate\Support\Facades\Log::info("Control OTP for {$email}: {$otp}");
        }
        Cache::put('control_otp_' . $email, Hash::make($otp), now()->addMinutes(5));

        // Send Email
        SendEmailJob::dispatch(
            template: EmailTemplate::ControlOtp,
            data: [
                'user_name' => $user->name,
                'otp' => $otp,
            ],
            recipientEmail: $email,
            userId: $user->id,
            requestId: Str::uuid()->toString(),
        );

        return redirect()->route('control.verify-otp')->with('email', $email);
    }

    /**
     * Verify OTP (Step 2).
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => ['required', 'string', new StrictEmail()],
            'otp'   => ['required', 'string', 'size:6'],
        ]);

        $email = Str::lower(trim($request->email));
        $throttleKey = 'control_verify_' . $request->ip() . '_' . $email;

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            return back()->withErrors(['otp' => 'Too many attempts. Try again later.']);
        }
        RateLimiter::hit($throttleKey, 15 * 60);

        $hashedOtp = Cache::get('control_otp_' . $email);

        if (! $hashedOtp || ! Hash::check($request->otp, $hashedOtp)) {
            return back()->withErrors(['otp' => 'Invalid or expired OTP.']);
        }

        // OTP is valid. Destroy it immediately (Single use).
        Cache::forget('control_otp_' . $email);
        RateLimiter::clear('control_login_' . $request->ip() . '_' . $email);
        RateLimiter::clear($throttleKey);

        $user = User::where('email', $email)->first();
        if (! $user || ! in_array($user->platform_role, ['admin', 'super_admin'])) {
            return back()->withErrors(['otp' => 'Unauthorized.']);
        }

        // Authenticate
        Auth::login($user);
        
        // Setup control session
        $request->session()->put('control_last_activity', time());
        
        // Log the login audit
        \App\Models\AdminAuditLog::create([
            'user_id' => $user->id,
            'action' => 'login',
            'entity_type' => 'session',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'request_id' => (string) Str::uuid(),
        ]);

        return redirect()->intended(route('control.dashboard'));
    }

    /**
     * Handle registration (if enabled).
     */
    public function register(Request $request)
    {
        $isFirst = User::whereNotNull('platform_role')->count() === 0;

        if (! $isFirst && ! PlatformSetting::getSetting('allow_admin_registration', false)) {
            return back()->withErrors(['email' => 'Control registration is currently disabled.']);
        }

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'unique:users,email', new StrictEmail()],
            // No password required initially as we'll use OTP for auth.
            // But if we want, we can add it. Let's keep it simple.
        ]);

        $email = Str::lower(trim($request->email));

        // Is this the very first admin?
        $isFirst = User::whereNotNull('platform_role')->count() === 0;
        $role = $isFirst ? 'super_admin' : 'admin';

        $user = clone User::create([
            'name' => $request->name,
            'email' => $email,
            'password' => Hash::make(Str::random(32)), // Random password, we use OTP
            'platform_role' => $role,
        ]);

        // Auto grant super_admin to the first admin.
        if ($isFirst) {
            PlatformSetting::setSetting('allow_admin_registration', false);
        }

        // Generate and store OTP (Cache only, 5 mins, Hashed)
        $otp = (string) random_int(100000, 999999);
        if (app()->environment('local')) {
            $otp = '123456';
            \Illuminate\Support\Facades\Log::info("Control OTP for {$email}: {$otp}");
        }
        Cache::put('control_otp_' . $email, Hash::make($otp), now()->addMinutes(5));

        // Send Email
        SendEmailJob::dispatch(
            template: EmailTemplate::ControlOtp,
            data: [
                'user_name' => $user->name,
                'otp' => $otp,
            ],
            recipientEmail: $email,
            userId: $user->id,
            requestId: Str::uuid()->toString(),
        );

        return redirect()->route('control.verify-otp')->with('email', $email)->with('status', 'Registration successful. Check your email for the OTP.');
    }

    /**
     * Log the user out of the control panel.
     */
    public function logout(Request $request)
    {
        // Only destroy the control panel session, preserving the public app session.
        $request->session()->forget('control_last_activity');

        return redirect()->route('control.login');
    }
}
