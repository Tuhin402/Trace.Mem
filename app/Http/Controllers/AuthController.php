<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Auth\EmailBloomFilterService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    private const LOGIN_MAX_ATTEMPTS = 5;
    private const LOGIN_DECAY_SECONDS = 60;

    private const REGISTER_MAX_ATTEMPTS = 3;
    private const REGISTER_DECAY_SECONDS = 300;

    public function register(Request $request, EmailBloomFilterService $bloom)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'account_type' => ['required', 'in:individual,tenant'],
            'company_name' => ['nullable', 'string', 'max:255'],
        ]);

        $email = strtolower(trim($data['email']));
        $rateKey = $this->throttleKey('register', $request, $email);

        $this->ensureNotRateLimited(
            $rateKey,
            self::REGISTER_MAX_ATTEMPTS,
            self::REGISTER_DECAY_SECONDS,
            'email',
            'Too many registration attempts. Please try again in :seconds seconds.'
        );

        if ($data['account_type'] === 'tenant' && blank($data['company_name'])) {
            throw ValidationException::withMessages([
                'company_name' => 'Company name is required for tenant accounts.',
            ]);
        }

        if ($bloom->maybeContains($email) && User::where('email', $email)->exists()) {
            $this->clearRateLimit($rateKey);

            throw ValidationException::withMessages([
                'email' => 'That email is already registered.',
            ]);
        }

        try {
            $user = DB::transaction(function () use ($data, $email) {
                return User::create([
                    'tenant_scope_id' => (string) Str::uuid(),
                    'name' => $data['name'],
                    'email' => $email,
                    'password' => Hash::make($data['password']),
                    'account_type' => $data['account_type'],
                    'company_name' => $data['account_type'] === 'tenant'
                        ? $data['company_name']
                        : null,
                ]);
            });
        } catch (QueryException $e) {
            if ($this->isDuplicateEmailException($e)) {
                $this->hitRateLimit($rateKey, self::REGISTER_DECAY_SECONDS);

                throw ValidationException::withMessages([
                    'email' => 'That email is already registered.',
                ]);
            }

            throw $e;
        }

        try {
            $bloom->add($email);
        } catch (\Throwable $e) {
            Log::warning('Bloom filter update failed after user registration.', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
        }

        event(new Registered($user));

        Auth::login($user);
        $request->session()->regenerate();

        $this->clearRateLimit($rateKey);

        return redirect()->route('verification.notice');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $email = strtolower(trim($data['email']));
        $rateKey = $this->throttleKey('login', $request, $email);

        $this->ensureNotRateLimited(
            $rateKey,
            self::LOGIN_MAX_ATTEMPTS,
            self::LOGIN_DECAY_SECONDS,
            'email',
            'Too many login attempts. Please try again in :seconds seconds.'
        );

        if (! Auth::attempt(['email' => $email, 'password' => $data['password']])) {
            $this->hitRateLimit($rateKey, self::LOGIN_DECAY_SECONDS);

            throw ValidationException::withMessages([
                'email' => 'The provided credentials are incorrect.',
            ]);
        }

        $request->session()->regenerate();

        $user = $request->user();

        if ($user && method_exists($user, 'hasVerifiedEmail') && ! $user->hasVerifiedEmail()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'email' => 'Please verify your email before logging in.',
            ]);
        }

        if ($user) {
            $user->forceFill(['last_login_at' => now()])->save();
        }

        $this->clearRateLimit($rateKey);

        return redirect()->route('dashboard');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    private function isDuplicateEmailException(QueryException $e): bool
    {
        $sqlState = $e->errorInfo[0] ?? null;
        $message = strtolower($e->getMessage());

        return in_array($sqlState, ['23000', '23505'], true)
            || str_contains($message, 'unique constraint failed')
            || str_contains($message, 'duplicate entry');
    }

    private function throttleKey(string $action, Request $request, string $email): string
    {
        return Str::lower($action . '|' . $email . '|' . ($request->ip() ?? 'unknown'));
    }

    private function ensureNotRateLimited(
        string $key,
        int $maxAttempts,
        int $decaySeconds,
        string $field,
        string $message
    ): void {
        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($key);

            throw ValidationException::withMessages([
                $field => str_replace(':seconds', (string) $seconds, $message),
            ]);
        }
    }

    private function hitRateLimit(string $key, int $decaySeconds): void
    {
        RateLimiter::hit($key, $decaySeconds);
    }

    private function clearRateLimit(string $key): void
    {
        RateLimiter::clear($key);
    }
}