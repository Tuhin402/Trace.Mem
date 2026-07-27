<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('operational_communication_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            
            // Recipient Details (Immutable)
            $table->string('recipient_uuid')->index();
            $table->string('recipient_type'); // e.g., 'user', 'tenant'
            $table->string('recipient_email');
            $table->string('recipient_name');
            
            // Sender Details (Immutable)
            $table->unsignedBigInteger('sender_id')->nullable()->index();
            $table->string('sender_name')->nullable();
            
            // Template & Content
            $table->string('channel')->default('email');
            $table->string('template_category')->nullable();
            $table->string('template_name')->nullable();
            
            // Rendered Content (Exactly what was sent)
            $table->string('rendered_subject', 255)->nullable();
            $table->longText('rendered_body')->nullable();
            
            // Status & Lifecycle
            $table->string('status')->default('queued')->index(); // queued, processing, sent, failed, cancelled
            $table->string('queue_job_id')->nullable()->index();
            $table->string('resend_message_id')->nullable()->index();
            $table->longText('error_message')->nullable();
            
            // Timestamps
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('operational_communication_logs');
    }
};
