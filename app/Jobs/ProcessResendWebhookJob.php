<?php

namespace App\Jobs;

use App\Models\EmailLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ProcessResendWebhookJob — queue: 'emails'
 *
 * Idempotency strategy: INSERT into resend_webhook_events with a UNIQUE
 * constraint on event_id (= svix-id from Resend). Duplicate deliveries
 * produce the same event_id → same INSERT → unique constraint violation
 * → duplicate detected → silent return. Mirrors ProcessRazorpayWebhookJob.
 *
 * Supported Resend webhook events:
 *   email.sent             — Store provider_message_id, mark sent
 *   email.delivered        — Mark delivered_at
 *   email.delivery_delayed — Store delayed_at (NOT failed)
 *   email.bounced          — Mark bounced, store reason in metadata
 *   email.complained       — Mark complained, store metadata
 *   email.opened           — Analytics only — NO business logic
 *   email.clicked          — Analytics only — NO business logic
 *
 * CRITICAL: email.opened and email.clicked are analytics-only.
 * They NEVER trigger account verification, activation, or any business action.
 * Laravel's signed URL verification remains the sole source of truth.
 */
class ProcessResendWebhookJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public int   $tries   = 5;
    public array $backoff = [10, 30, 60, 120];
    public int   $timeout = 30;

    public function __construct(
        public readonly array  $payload,
        public readonly string $eventId,
    ) {
        $this->onQueue('emails');
    }

    /* ══════════════════════════════════════════════════════════════════
     *  ENTRY POINT
     * ══════════════════════════════════════════════════════════════════ */

    public function handle(): void
    {
        $eventType = $this->payload['type'] ?? null;

        if (! $eventType) {
            Log::warning('ProcessResendWebhookJob: missing event type', [
                'event_id'    => $this->eventId,
                'payload_keys' => array_keys($this->payload),
            ]);
            return;
        }

        match ($eventType) {
            'email.sent'             => $this->handleSent(),
            'email.delivered'        => $this->handleDelivered(),
            'email.delivery_delayed' => $this->handleDeliveryDelayed(),
            'email.bounced'          => $this->handleBounced(),
            'email.complained'       => $this->handleComplained(),
            'email.opened'           => $this->handleOpened(),
            'email.clicked'          => $this->handleClicked(),
            default                  => $this->handleUnsupported($eventType),
        };
    }

    /* ══════════════════════════════════════════════════════════════════
     *  IDEMPOTENCY GUARD
     * ══════════════════════════════════════════════════════════════════ */

    private function recordOrSkip(string $eventType): bool
    {
        try {
            DB::table('resend_webhook_events')->insert([
                'event_id'     => $this->eventId,
                'event_type'   => $eventType,
                'processed_at' => now(),
                'payload_hash' => hash('sha256', json_encode($this->payload)),
            ]);
            return true;
        } catch (\Illuminate\Database\UniqueConstraintViolationException) {
            Log::info('ProcessResendWebhookJob: duplicate event — skipping', [
                'event_type' => $eventType,
                'event_id'   => $this->eventId,
            ]);
            return false;
        } catch (\Throwable $e) {
            if (
                str_contains($e->getMessage(), 'UNIQUE constraint failed') ||
                str_contains($e->getMessage(), 'Duplicate entry') ||
                str_contains($e->getMessage(), 'unique_violation')
            ) {
                Log::info('ProcessResendWebhookJob: duplicate event (fallback) — skipping', [
                    'event_type' => $eventType,
                    'event_id'   => $this->eventId,
                ]);
                return false;
            }
            throw $e;
        }
    }

    /** Resolve EmailLog by Resend message ID from payload data */
    private function resolveLog(): ?EmailLog
    {
        $messageId = data_get($this->payload, 'data.email_id');

        if (! $messageId) {
            Log::warning('ProcessResendWebhookJob: no email_id in payload', [
                'event_type' => $this->payload['type'] ?? 'unknown',
                'event_id'   => $this->eventId,
            ]);
            return null;
        }

        $log = EmailLog::where('provider_message_id', $messageId)->first();

        if (! $log) {
            Log::warning('ProcessResendWebhookJob: no EmailLog found for email_id', [
                'email_id'   => $messageId,
                'event_type' => $this->payload['type'] ?? 'unknown',
            ]);
        }

        return $log;
    }

    /* ══════════════════════════════════════════════════════════════════
     *  HANDLER: email.sent
     *  Store the Resend message ID and confirm sent status.
     * ══════════════════════════════════════════════════════════════════ */

    private function handleSent(): void
    {
        DB::transaction(function () {
            if (! $this->recordOrSkip('email.sent')) {
                return;
            }

            $messageId = data_get($this->payload, 'data.email_id');
            if (! $messageId) {
                return;
            }

            // email.sent may arrive before or after the send() call updates the log.
            // We correlate by request_id stored in 'tags' or fall back by latest queued.
            // The most reliable path: we store message_id NOW so other events can find it.
            $log = EmailLog::where('provider_message_id', $messageId)->first();

            if (! $log) {
                // Try to match by to-address + sent_at proximity (best-effort)
                $toEmail = data_get($this->payload, 'data.to.0');
                if ($toEmail) {
                    $log = EmailLog::where('recipient_email', $toEmail)
                        ->whereNull('provider_message_id')
                        ->where('status', EmailLog::STATUS_SENT)
                        ->latest('sent_at')
                        ->first();
                }
            }

            if ($log) {
                $log->update([
                    'provider_message_id' => $messageId,
                    'status'              => EmailLog::STATUS_SENT,
                    'sent_at'             => $log->sent_at ?? now(),
                ]);
            }

            Log::info('ProcessResendWebhookJob [email.sent]: message ID stored', [
                'email_id' => $messageId,
                'log_id'   => $log?->id,
            ]);
        });
    }

    /* ══════════════════════════════════════════════════════════════════
     *  HANDLER: email.delivered
     * ══════════════════════════════════════════════════════════════════ */

    private function handleDelivered(): void
    {
        DB::transaction(function () {
            if (! $this->recordOrSkip('email.delivered')) {
                return;
            }

            $log = $this->resolveLog();
            if (! $log) {
                return;
            }

            $log->update([
                'status'       => EmailLog::STATUS_DELIVERED,
                'delivered_at' => now(),
            ]);

            Log::info('ProcessResendWebhookJob [email.delivered]', [
                'log_id'   => $log->id,
                'email_id' => data_get($this->payload, 'data.email_id'),
            ]);
        });
    }

    /* ══════════════════════════════════════════════════════════════════
     *  HANDLER: email.delivery_delayed
     *  NOT a failure — store timestamp and mark delayed, not failed.
     * ══════════════════════════════════════════════════════════════════ */

    private function handleDeliveryDelayed(): void
    {
        DB::transaction(function () {
            if (! $this->recordOrSkip('email.delivery_delayed')) {
                return;
            }

            $log = $this->resolveLog();
            if (! $log) {
                return;
            }

            $log->update([
                'status'     => EmailLog::STATUS_DELIVERY_DELAYED,
                'delayed_at' => now(),
            ]);

            Log::warning('ProcessResendWebhookJob [email.delivery_delayed]', [
                'log_id'   => $log->id,
                'email_id' => data_get($this->payload, 'data.email_id'),
            ]);
        });
    }

    /* ══════════════════════════════════════════════════════════════════
     *  HANDLER: email.bounced
     *  Mark failed, store bounce reason in metadata.
     * ══════════════════════════════════════════════════════════════════ */

    private function handleBounced(): void
    {
        DB::transaction(function () {
            if (! $this->recordOrSkip('email.bounced')) {
                return;
            }

            $log = $this->resolveLog();
            if (! $log) {
                return;
            }

            $log->update([
                'status'     => EmailLog::STATUS_BOUNCED,
                'bounced_at' => now(),
                'metadata'   => array_merge((array) ($log->metadata ?? []), [
                    'bounce_type'    => data_get($this->payload, 'data.bounce.type'),
                    'bounce_subtype' => data_get($this->payload, 'data.bounce.subType'),
                    'bounce_message' => data_get($this->payload, 'data.bounce.bouncedRecipients.0.diagnosticCode'),
                ]),
            ]);

            Log::warning('ProcessResendWebhookJob [email.bounced]', [
                'log_id'       => $log->id,
                'email_id'     => data_get($this->payload, 'data.email_id'),
                'bounce_type'  => data_get($this->payload, 'data.bounce.type'),
            ]);
        });
    }

    /* ══════════════════════════════════════════════════════════════════
     *  HANDLER: email.complained
     *  Mark complained, store metadata for future suppression logic.
     * ══════════════════════════════════════════════════════════════════ */

    private function handleComplained(): void
    {
        DB::transaction(function () {
            if (! $this->recordOrSkip('email.complained')) {
                return;
            }

            $log = $this->resolveLog();
            if (! $log) {
                return;
            }

            $log->update([
                'status'        => EmailLog::STATUS_COMPLAINED,
                'complained_at' => now(),
                'metadata'      => array_merge((array) ($log->metadata ?? []), [
                    'complaint_feedback_type' => data_get($this->payload, 'data.complaint.complaintFeedbackType'),
                    'complained_recipients'   => data_get($this->payload, 'data.complaint.complainedRecipients'),
                ]),
            ]);

            Log::warning('ProcessResendWebhookJob [email.complained]', [
                'log_id'    => $log->id,
                'email_id'  => data_get($this->payload, 'data.email_id'),
                'recipient' => $log->recipient_email,
            ]);
        });
    }

    /* ══════════════════════════════════════════════════════════════════
     *  HANDLER: email.opened
     *  ANALYTICS ONLY — never triggers business logic.
     * ══════════════════════════════════════════════════════════════════ */

    private function handleOpened(): void
    {
        DB::transaction(function () {
            if (! $this->recordOrSkip('email.opened')) {
                return;
            }

            $log = $this->resolveLog();
            if (! $log) {
                return;
            }

            // Only set opened_at once — track first open
            if (! $log->opened_at) {
                $log->update([
                    'status'    => EmailLog::STATUS_OPENED,
                    'opened_at' => now(),
                    'metadata'  => array_merge((array) ($log->metadata ?? []), [
                        'open_ip'         => data_get($this->payload, 'data.click.ipAddress'),
                        'open_user_agent' => data_get($this->payload, 'data.click.userAgent'),
                    ]),
                ]);
            }

            Log::info('ProcessResendWebhookJob [email.opened]: analytics recorded', [
                'log_id'   => $log->id,
                'email_id' => data_get($this->payload, 'data.email_id'),
            ]);
        });
    }

    /* ══════════════════════════════════════════════════════════════════
     *  HANDLER: email.clicked
     *  ANALYTICS ONLY — never triggers business logic.
     * ══════════════════════════════════════════════════════════════════ */

    private function handleClicked(): void
    {
        DB::transaction(function () {
            if (! $this->recordOrSkip('email.clicked')) {
                return;
            }

            $log = $this->resolveLog();
            if (! $log) {
                return;
            }

            // Only set clicked_at once — track first click
            if (! $log->clicked_at) {
                $log->update([
                    'status'     => EmailLog::STATUS_CLICKED,
                    'clicked_at' => now(),
                    'metadata'   => array_merge((array) ($log->metadata ?? []), [
                        'click_url'        => data_get($this->payload, 'data.click.link'),
                        'click_ip'         => data_get($this->payload, 'data.click.ipAddress'),
                        'click_user_agent' => data_get($this->payload, 'data.click.userAgent'),
                    ]),
                ]);
            }

            Log::info('ProcessResendWebhookJob [email.clicked]: analytics recorded', [
                'log_id'   => $log->id,
                'email_id' => data_get($this->payload, 'data.email_id'),
            ]);
        });
    }

    /* ══════════════════════════════════════════════════════════════════
     *  HANDLER: unsupported event types
     * ══════════════════════════════════════════════════════════════════ */

    private function handleUnsupported(string $eventType): void
    {
        Log::info('ProcessResendWebhookJob: unsupported event type — ignoring', [
            'event_type' => $eventType,
            'event_id'   => $this->eventId,
        ]);
    }
}
