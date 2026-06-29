<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * email_logs — source of truth for every transactional email lifecycle.
     *
     * Correlates with Resend via provider_message_id.
     * Correlates with queue jobs via request_id (X-TraceMem-Request-ID header).
     * Status lifecycle: queued → sent → delivered (happy path)
     *                   queued → sent → bounced / complained (failure path)
     *                   queued → failed (send-side error)
     */
    public function up(): void
    {
        Schema::create('email_logs', function (Blueprint $table) {
            $table->id();

            // Nullable: system emails (e.g. admin alerts) may not have a user
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Provider identity
            $table->string('provider')->default('resend');

            // Resend assigns this after the API call succeeds — null until then
            $table->string('provider_message_id')->nullable()->unique();

            // Template identity + versioning
            $table->string('template_name');        // e.g. 'verification'
            $table->string('template_version')->default('v1'); // e.g. 'v1', 'v2'

            // Email metadata
            $table->string('subject');
            $table->string('recipient_email');
            $table->string('sender_email');

            // Traceability: links queue job → email_log → Resend message → webhook
            $table->string('request_id')->nullable()->index();

            // Lifecycle status
            // Allowed: queued | sent | delivered | delivery_delayed | bounced
            //          complained | opened | clicked | failed
            $table->string('status')->default('queued')->index();

            // Lifecycle timestamps — each set once, never overwritten
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('clicked_at')->nullable();
            $table->timestamp('delayed_at')->nullable();
            $table->timestamp('bounced_at')->nullable();
            $table->timestamp('complained_at')->nullable();
            $table->timestamp('failed_at')->nullable();

            // Structured metadata: bounce reason, click URL, open IP, etc.
            $table->json('metadata')->nullable();

            $table->timestamps();

            // Analytics + admin queries
            $table->index('user_id');
            $table->index('template_name');
            $table->index('recipient_email');
            $table->index(['template_name', 'status']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_logs');
    }
};
