<?php

/**
 * Domain Configuration
 *
 * Central registry for all TraceMem hostnames.
 * Values are read from .env — never hardcoded here.
 *
 * Usage:
 *   config('domains.root')  → tracemem.one
 *   config('domains.app')   → app.tracemem.one
 *   config('domains.api')   → api.tracemem.one
 *
 * Routing note:
 *   Laravel routing does NOT depend on these values.
 *   Nginx resolves subdomains and routes all traffic to the same
 *   Laravel application. These config values are used only for:
 *     - CORS allowed-origin lists  (config/cors.php)
 *     - Inertia shared props       (HandleInertiaRequests)
 *     - Frontend domain constants  (resources/js/lib/domains.ts)
 */
return [

    // Public marketing / documentation website
    'root' => env('APP_DOMAIN', 'tracemem.one'),

    // Authenticated dashboard (app.tracemem.one)
    'app'  => env('APP_APP_DOMAIN', 'app.tracemem.one'),

    // Developer API (api.tracemem.one)
    'api'  => env('APP_API_DOMAIN', 'api.tracemem.one'),

];
