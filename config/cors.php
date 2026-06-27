<?php

/**
 * CORS Configuration
 *
 * Applies to API routes only (registered via the 'api' middleware group).
 * The web routes (Inertia/React) are served from the same origin — they
 * do not require CORS headers.
 *
 * Production origins:
 *   https://tracemem.one        Public site (may call /api/v1/health)
 *   https://app.tracemem.one    Dashboard (calls API endpoints)
 *
 * Local development:
 *   In local/testing environments all origins are allowed so developers
 *   do not need to configure virtual hosts to test CORS locally.
 *
 * Security note:
 *   api.tracemem.one is the SERVER — it is never an origin in CORS terms.
 *   Only the two HTTPS origins above need to be allowed.
 */

$isLocal = in_array(env('APP_ENV', 'production'), ['local', 'testing']);

$allowedOrigins = $isLocal
    ? ['*']
    : [
        'https://' . env('APP_DOMAIN',     'tracemem.one'),
        'https://' . env('APP_APP_DOMAIN', 'app.tracemem.one'),
    ];

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => $allowedOrigins,

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['Content-Type', 'Authorization', 'Accept', 'X-Requested-With'],

    'exposed_headers' => [],

    'max_age' => 86400,

    // Credentials (cookies/auth headers) are NOT exposed cross-origin.
    // The API uses bearer tokens, not cookies — this must stay false.
    'supports_credentials' => false,

];
