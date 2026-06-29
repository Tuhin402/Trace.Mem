<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessRazorpayWebhookJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * RazorpayWebhookController — 3 responsibilities only:
 *   1. Read raw body and verify X-Razorpay-Signature
 *   2. Dispatch ProcessRazorpayWebhookJob (job owns all idempotency + business logic)
 *   3. Return HTTP 200 immediately so Razorpay does not retry
 *
 * Razorpay retries webhooks up to 15 times over 24 hours on non-2xx responses.
 * Heavy work must never happen here — always in the job.
 */
class RazorpayWebhookController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        $rawBody       = $request->getContent();
        $signature     = (string) $request->header('X-Razorpay-Signature', '');
        $webhookSecret = config('services.razorpay.webhook_secret');

        // ── 1. Verify webhook signature ────────────────────────────────────
        // Razorpay signs with HMAC-SHA256(raw_body, webhook_secret)
        $expectedSignature = hash_hmac('sha256', $rawBody, $webhookSecret);

        if (! hash_equals($expectedSignature, $signature)) {
            Log::warning('RazorpayWebhookController: invalid signature — request rejected', [
                'ip'             => $request->ip(),
                'event_type'     => $request->json('event'),
                'sig_received'   => substr($signature, 0, 16) . '…',
            ]);

            // Return 400 so Razorpay knows the request was rejected (not a transient failure)
            return response()->json(['error' => 'Invalid signature.'], 400);
        }

        $payload   = $request->json()->all();
        $eventType = $payload['event'] ?? null;

        // Use the signature as the idempotency key — identical re-deliveries from
        // Razorpay produce the same signature → same DB INSERT → unique constraint
        // violation → duplicate detected → safe skip (handled in job).
        $eventId = $signature;

        Log::info('RazorpayWebhookController: webhook received', [
            'event_type' => $eventType,
            'event_id'   => substr($eventId, 0, 32) . '…',
            'ip'         => $request->ip(),
        ]);

        // ── 2. Dispatch job — job owns idempotency and all business logic ──
        ProcessRazorpayWebhookJob::dispatch(
            $payload,
            $eventId,
        )->onQueue('high');

        // ── 3. Return 200 immediately ──────────────────────────────────────
        return response()->json(['received' => true]);
    }
}
