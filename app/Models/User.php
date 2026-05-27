<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'tenant_scope_id',
        'stripe_customer_id',
        'name',
        'email',
        'password',
        'account_type',
        'company_name',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'password' => 'hashed',
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

    public function currentSubscription(): HasOne
    {
        return $this->hasOne(UserSubscription::class)
            ->where('is_active', true)
            ->whereNull('cancelled_at')     
            ->latestOfMany('starts_at');
    }
}