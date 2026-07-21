<?php

namespace App\Models;

use App\Concerns\HasTeams;
use App\Enums\EmailTemplate;
use App\Jobs\SendEmailJob;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, HasTeams, Notifiable;

    protected $fillable = [
        'current_team_id',          // managed by HasTeams::switchTeam()
        'tenant_scope_id',
        'stripe_customer_id',       // kept for historical Stripe billing records
        'razorpay_customer_id',     // Razorpay customer ID (set on first subscription)
        'name',
        'email',
        'password',
        'account_type',
        'company_name',
        'last_login_at',
        // ── Free trial (Founding Offer) ──────────────────────────────────────
        'free_trial_status',        // null | pending_activation | activated | completed | cancelled | upgraded
        'free_trial_activated_at',
        'free_trial_ends_at',
        'free_trial_plan_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at'      => 'datetime',
        'last_login_at'          => 'datetime',
        'password'               => 'hashed',
        // ── Free trial casts ────────────────────────────────────────────────
        'free_trial_activated_at' => 'datetime',
        'free_trial_ends_at'      => 'datetime',
        'free_trial_plan_id'      => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (User $user) {
            $user->tenant_scope_id ??= (string) Str::uuid();
        });

        static::saving(function (User $user) {
            $user->email = Str::lower(trim((string) $user->email));
        });
    }

    
    // ---------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------
    public function apiKeys(): HasMany
    {
        return $this->hasMany(ApiKey::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(UserSubscription::class);
    }

    public function freeTrialEvents(): HasMany
    {
        return $this->hasMany(FreeTrialEvent::class);
    }

    public function currentSubscription(): HasOne
    {
        return $this->hasOne(UserSubscription::class)
            ->where('is_active', true)
            ->whereNull('cancelled_at')     
            ->latestOfMany('starts_at');
    }

    // ── Email notification overrides ─────────────────────────────────────────
    // These replace Fortify's default notifications so verification and password
    // reset emails route through the unified SendEmailJob pipeline.
    // The signed URL logic remains 100% Laravel — only delivery changes.

    /**
     * Override Fortify's email verification notification.
     * Generates Laravel's signed verification URL and delivers via SendEmailJob.
     * Prevents duplicate emails (Fortify's listener is bypassed by this override).
     */
    public function sendEmailVerificationNotification(): void
    {
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(config('auth.verification.expire', 60)),
            ['id' => $this->getKey(), 'hash' => sha1($this->getEmailForVerification())],
        );

        SendEmailJob::dispatch(
            template:       EmailTemplate::Verification,
            data:           [
                'user_name'        => $this->name,
                'verification_url' => $verificationUrl,
            ],
            recipientEmail: $this->email,
            userId:         $this->id,
            requestId:      Str::uuid()->toString(),
        );
    }

    /**
     * Override Fortify's password reset link notification.
     * Builds the reset URL and delivers via SendEmailJob.
     * Ensures password reset emails participate in logging, retry, and analytics.
     *
     * @param  string  $token  The password reset token from PasswordBroker
     */
    public function sendPasswordResetNotification($token): void
    {
        $resetUrl = url(route(
            'password.reset',
            ['token' => $token, 'email' => $this->email],
            absolute: false,
        ));

        SendEmailJob::dispatch(
            template:       EmailTemplate::PasswordReset,
            data:           [
                'user_name' => $this->name,
                'reset_url' => $resetUrl,
            ],
            recipientEmail: $this->email,
            userId:         $this->id,
            requestId:      Str::uuid()->toString(),
        );
    }
}