<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * resend_webhook_events — idempotency table for Resend webhook processing.
     *
     * Mirrors the razorpay_webhook_events pattern.
     * The UNIQUE constraint on event_id is the sole idempotency guard:
     * if Resend retries a webhook, the same event_id → same INSERT → unique
     * constraint violation → duplicate detected → silent skip in job.
     */
    public function up(): void
    {
        Schema::create('resend_webhook_events', function (Blueprint $table) {
            $table->id();

            // Resend webhook event ID — used as the idempotency key
            $table->string('event_id')->unique();

            // The Resend event type, e.g. 'email.delivered', 'email.bounced'
            $table->string('event_type')->index();

            // SHA-256 of the raw JSON payload — useful for debugging replay differences
            $table->string('payload_hash', 64)->nullable();

            $table->timestamp('processed_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resend_webhook_events');
    }
};
