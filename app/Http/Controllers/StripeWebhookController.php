<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessStripeWebhookJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Stripe\Webhook;

/**
 * StripeWebhookController — simplified to 3 responsibilities:
 *   1. Validate the Stripe webhook signature
 *   2. Dispatch ProcessStripeWebhookJob (job owns all idempotency + business logic)
 *   3. Return 200 immediately to Stripe
 *
 * The controller does NOT pre-check stripe_webhook_events — that check was
 * removed per A5. The job is the sole idempotency authority, using a DB
 * UNIQUE constraint on event_id which is more reliable than a controller-level
 * pre-check (avoids race conditions, handles retries correctly).
 */
class StripeWebhookController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        // 1. Validate Stripe signature — throws if invalid
        $event = Webhook::constructEvent(
            $request->getContent(),
            $request->header('Stripe-Signature'),
            config('services.stripe.webhook_secret')
        );

        // 2. Dispatch job — job owns idempotency and all business logic
        ProcessStripeWebhookJob::dispatch(
            $event->toArray()
        )->onQueue('high');

        // 3. Return 200 immediately — Stripe will retry if we return non-2xx
        return response()->json(['received' => true]);
    }
}