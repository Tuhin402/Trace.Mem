<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessResendWebhookJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * ResendWebhookController — 3 responsibilities only:
 *   1. Read raw body and verify Resend webhook signature (Svix-based)
 *   2. Dispatch ProcessResendWebhookJob (job owns idempotency + business logic)
 *   3. Return HTTP 200 immediately so Resend does not retry
 *
 * Resend uses Svix for webhook delivery. The signature is in the
 * 'svix-signature' header and verified using HMAC-SHA256 with the
 * webhook signing secret.
 *
 * Signature format: "v1,<base64-encoded-signature>"
 * Payload for verification: "<svix-id>.<svix-timestamp>.<raw-body>"
 */
class ResendWebhookController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        $rawBody       = $request->getContent();
        $webhookSecret = config('services.resend.webhook_secret', '');

        // ── 1. Verify webhook signature ────────────────────────────────────────
        $svixId        = (string) $request->header('svix-id', '');
        $svixTimestamp = (string) $request->header('svix-timestamp', '');
        $svixSignature = (string) $request->header('svix-signature', '');

        if (! $this->verifySignature($rawBody, $svixId, $svixTimestamp, $svixSignature, $webhookSecret)) {
            Log::warning('ResendWebhookController: invalid signature — request rejected', [
                'ip'        => $request->ip(),
                'svix_id'   => $svixId,
                'event_type' => $request->json('type'),
            ]);

            return response()->json(['error' => 'Invalid signature.'], 400);
        }

        $payload   = $request->json()->all();
        $eventType = $payload['type'] ?? null;

        // Use svix-id as the idempotency key — identical re-deliveries from
        // Resend produce the same svix-id → same DB INSERT → unique constraint
        // violation → duplicate detected → safe skip (handled in job).
        $eventId = $svixId ?: hash('sha256', $rawBody);

        Log::info('ResendWebhookController: webhook received', [
            'event_type' => $eventType,
            'event_id'   => $eventId,
            'ip'         => $request->ip(),
        ]);

        // ── 2. Dispatch job — job owns idempotency and all business logic ────
        ProcessResendWebhookJob::dispatch($payload, $eventId)->onQueue('emails');

        // ── 3. Return 200 immediately ─────────────────────────────────────────
        return response()->json(['received' => true]);
    }

    /**
     * Verify the Resend/Svix webhook signature.
     *
     * Svix signs: "<svix-id>.<svix-timestamp>.<raw-body>"
     * The svix-signature header contains one or more "v1,<base64>" signatures.
     *
     * @see https://docs.svix.com/receiving/verifying-payloads/how
     */
    private function verifySignature(
        string $rawBody,
        string $svixId,
        string $svixTimestamp,
        string $svixSignature,
        string $secret,
    ): bool {
        // Strip the "whsec_" prefix that Resend adds to the raw base64 secret
        $secretBase64 = str_starts_with($secret, 'whsec_')
            ? substr($secret, 6)
            : $secret;

        if (empty($secretBase64)) {
            Log::warning('ResendWebhookController: RESEND_WEBHOOK_SECRET is not configured');
            return false;
        }

        try {
            $decodedSecret = base64_decode($secretBase64, strict: true);
        } catch (\Throwable) {
            return false;
        }

        if ($decodedSecret === false) {
            return false;
        }

        $toSign   = "{$svixId}.{$svixTimestamp}.{$rawBody}";
        $computed = base64_encode(hash_hmac('sha256', $toSign, $decodedSecret, binary: true));

        // svix-signature may contain multiple "v1,<base64>" entries separated by spaces
        foreach (explode(' ', $svixSignature) as $sig) {
            $parts = explode(',', $sig, 2);
            if (count($parts) === 2 && $parts[0] === 'v1') {
                if (hash_equals($computed, $parts[1])) {
                    return true;
                }
            }
        }

        return false;
    }
}
