<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_transactions', function (Blueprint $table) {
            $table->id();
        
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscription_plan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_subscription_id')->nullable()->constrained()->nullOnDelete();
        
            $table->string('provider')->default('stripe');
            $table->string('provider_checkout_session_id')->nullable()->unique();
            $table->string('provider_payment_intent_id')->nullable()->index();
            $table->string('provider_subscription_id')->nullable()->index();
            $table->string('provider_invoice_id')->nullable()->index();
        
            $table->enum('billing_cycle', ['monthly', 'quarterly', 'yearly']);
            $table->string('currency', 8)->default('usd');
            $table->unsignedInteger('amount_total')->default(0);
        
            $table->enum('status', ['pending', 'paid', 'failed', 'refunded', 'canceled'])->default('pending');
        
            $table->json('raw_payload')->nullable();
            $table->json('metadata')->nullable();
        
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_transactions');
    }
};