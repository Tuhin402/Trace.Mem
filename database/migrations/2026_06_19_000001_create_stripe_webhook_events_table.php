<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stripe_webhook_events', function (Blueprint $table) {
            $table->id();

            // Stripe evt_xxx ID — the idempotency guard
            $table->string('event_id', 255)->unique()->notNull();
            $table->string('event_type', 100)->notNull();
            $table->timestamp('processed_at')->notNull();

            // Optional SHA-256 hash of the raw payload for integrity checks
            $table->string('payload_hash', 64)->nullable();

            // No timestamps() — processed_at is the single authoritative timestamp
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stripe_webhook_events');
    }
};
