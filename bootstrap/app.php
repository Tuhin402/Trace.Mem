<?php

use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Middleware\HandleCors;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        // api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('api')
                ->group(base_path('routes/api.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        // Stripe webhook is exempt from CSRF — it is authenticated by Stripe's
        // HMAC-SHA256 signature (Webhook::constructEvent), which provides
        // equivalent or stronger integrity protection than a CSRF token.
        // All other web routes remain CSRF-protected automatically.
        $middleware->validateCsrfTokens(except: [
            '/stripe/webhook',
            '/resend/webhook',
            '/razorpay/webhook',
        ]);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
            SecurityHeaders::class,
        ]);

        // CORS headers on all API routes — allowed origins defined in config/cors.php.
        // Local: all origins allowed. Production: tracemem.one + app.tracemem.one only.
        $middleware->api(prepend: [
            HandleCors::class,
        ]);

        $middleware->alias([
            'api.key.auth' => \App\Http\Middleware\ApiKeyAuthMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
