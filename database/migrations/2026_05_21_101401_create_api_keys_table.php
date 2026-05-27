<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_keys', function (Blueprint $table) {
            $table->id();
        
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();
        
            $table->uuid('tenant_scope_id');
            $table->foreignId('subscription_plan_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();
        
            $table->string('name');
            $table->string('key_prefix', 16);   // cmtest_ / cmlive_
            $table->string('key_hash')->unique();
            $table->string('key_last4', 4)->nullable();
        
            $table->enum('environment', ['test', 'live']);
            $table->enum('mode', ['semantic_only', 'ai_first']);
        
            $table->unsignedInteger('rate_limit_max_requests')->default(1);
            $table->unsignedInteger('rate_limit_window_seconds')->default(30);
        
            $table->unsignedBigInteger('usage_count')->default(0);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
        
            $table->json('scopes')->nullable();
            $table->json('metadata')->nullable();
        
            $table->timestamps();
        
            $table->index(['tenant_scope_id', 'environment']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_keys');
    }
};
