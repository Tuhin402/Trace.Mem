<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiUsageLog extends Model
{
    protected $fillable = [
        'api_key_id',

        'endpoint',
        'method',
        'status_code',
        'latency_ms',
        'tokens_used',

        'ip_address',
        'request_host',
        'request_origin',
        'is_sandbox',
        'is_localhost',
        'user_agent',
        'request_id',

        'requested_at',
        'metadata',
    ];

    protected $casts = [
        'api_key_id' => 'integer',

        'endpoint' => 'string',
        'method' => 'string',

        'status_code' => 'integer',
        'latency_ms' => 'integer',
        'tokens_used' => 'integer',

        'ip_address' => 'string',
        'request_host' => 'string',
        'request_origin' => 'string',
        'is_sandbox' => 'boolean',
        'is_localhost' => 'boolean',
        'user_agent' => 'string',
        'request_id' => 'string',

        'requested_at' => 'datetime',
        'metadata' => 'array',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function apiKey(): BelongsTo
    {
        return $this->belongsTo(ApiKey::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function isSuccess(): bool
    {
        return $this->status_code >= 200
            && $this->status_code < 300;
    }

    public function isClientError(): bool
    {
        return $this->status_code >= 400
            && $this->status_code < 500;
    }

    public function isServerError(): bool
    {
        return $this->status_code >= 500;
    }

    public function isSlowRequest(int $thresholdMs = 2000): bool
    {
        return $this->latency_ms !== null
            && $this->latency_ms >= $thresholdMs;
    }

    public function isHighTokenUsage(int $threshold = 4000): bool
    {
        return $this->tokens_used >= $threshold;
    }

    public function isExpensiveRequest(): bool
    {
        return $this->isHighTokenUsage()
            || $this->isSlowRequest(3000);
    }

    public function isTestEnvironment(): bool
    {
        return $this->apiKey?->environment === 'test';
    }

    public function isLiveEnvironment(): bool
    {
        return $this->apiKey?->environment === 'live';
    }

    public function usedAiFirstMode(): bool
    {
        return $this->apiKey?->mode === 'ai_first';
    }

    public function usedSemanticOnlyMode(): bool
    {
        return $this->apiKey?->mode === 'semantic_only';
    }

    public function hasRequestId(): bool
    {
        return filled($this->request_id);
    }

    public function meta(string $key, mixed $default = null): mixed
    {
        return data_get($this->metadata, $key, $default);
    }
}