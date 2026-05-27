<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_subscriptions', function (Blueprint $table) {
            $table->id();
        
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();
        
            $table->foreignId('subscription_plan_id')
                ->constrained()
                ->cascadeOnDelete();
        
            $table->enum('billing_cycle', ['monthly', 'quarterly', 'yearly']);
            $table->enum('status', ['trial', 'active', 'past_due', 'canceled', 'expired'])->default('trial');
        
            $table->timestamp('starts_at');
            $table->timestamp('renews_at')->nullable();
            $table->timestamp('ends_at')->nullable();
        
            $table->boolean('auto_renew')->default(true);
            $table->boolean('overage_enabled')->default(false);
        
            $table->json('quotas_snapshot')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_subscriptions');
    }
};
