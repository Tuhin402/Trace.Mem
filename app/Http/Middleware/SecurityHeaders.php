<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * SecurityHeaders
 *
 * Adds hardened HTTP security headers to every web response.
 * All headers are conservative defaults that do not break the existing
 * Inertia/React frontend, Stripe checkout redirect, or Vite dev server.
 *
 * Headers added:
 *   - Content-Security-Policy       → restricts script/style/connect sources
 *   - Strict-Transport-Security     → HTTPS enforcement (production only)
 *   - X-Frame-Options               → clickjacking protection
 *   - X-Content-Type-Options        → MIME-sniffing protection
 *   - Referrer-Policy               → leaks less referrer info to third parties
 *   - Permissions-Policy            → disables unused browser APIs
 *   - Cross-Origin-Resource-Policy  → modern SaaS standard
 *   - Cross-Origin-Opener-Policy    → modern SaaS standard
 *   - Cross-Origin-Embedder-Policy  → modern SaaS standard (permissive for Stripe compat)
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        $this->applyHeaders($request, $response);

        return $response;
    }

    private function applyHeaders(Request $request, Response $response): void
    {
        $isLocal = app()->isLocal() || app()->environment('testing');

        // ── Content-Security-Policy ───────────────────────────────────────────
        // Allows:
        //   default-src 'self'
        //   script-src  'self' + 'unsafe-inline' (Inertia hydration uses inline scripts)
        //               + localhost:5173 (Vite dev HMR)
        //               + js.stripe.com (Stripe.js checkout)
        //   style-src   'self' 'unsafe-inline' (Inertia/React inline styles)
        //   img-src     'self' data: blob:
        //   font-src    'self' data:
        //   connect-src 'self' + localhost:5173 (Vite WS) + api.stripe.com
        //   frame-src   js.stripe.com (Stripe hosted fields/checkout)
        //   object-src  'none'
        //   base-uri    'self'
        //   form-action 'self' + checkout.stripe.com (billing redirect)
        //   Razorpay Checkout uses checkout.razorpay.com for the JS bundle and the
        //   payment modal iframe, and api.razorpay.com for API calls.
        $csp = implode('; ', array_filter([
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' https://checkout.razorpay.com" . ($isLocal ? ' http://localhost:5173' : ''),
            "style-src 'self' 'unsafe-inline'",
            "img-src 'self' data: blob: https://checkout.razorpay.com",
            "font-src 'self' data:",
            "connect-src 'self' https://api.razorpay.com" . ($isLocal ? ' ws://localhost:5173 http://localhost:5173' : ''),
            "frame-src https://api.razorpay.com https://checkout.razorpay.com",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
        ]));

        // ── Strict-Transport-Security ─────────────────────────────────────────
        // ONLY applied over HTTPS and never on local/dev environments.
        // Avoids the "localhost is permanently HTTPS-only" browser pain.
        if (! $isLocal && $request->isSecure()) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains'
            );
        }

        // ── Core headers (always applied) ─────────────────────────────────────
        $response->headers->set('Content-Security-Policy', $csp);
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Disable rarely-used browser APIs that TraceMem does not need.
        $response->headers->set(
            'Permissions-Policy',
            'camera=(), microphone=(), geolocation=(), payment=(), usb=(), interest-cohort=()'
        );

        // ── Cross-Origin isolation headers ────────────────────────────────────
        // Cross-Origin-Resource-Policy: prevents other origins from reading
        //   resources loaded from this server (e.g., API responses).
        $response->headers->set('Cross-Origin-Resource-Policy', 'same-origin');

        // Cross-Origin-Opener-Policy: prevents cross-origin windows from
        //   getting a reference to this window (popup attacks).
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin-allow-popups');

        // Cross-Origin-Embedder-Policy: "unsafe-none" keeps Stripe iframes
        //   working. "require-corp" would break Stripe checkout embeds.
        // This is intentionally permissive to preserve Stripe compatibility.
        $response->headers->set('Cross-Origin-Embedder-Policy', 'unsafe-none');
    }
}
