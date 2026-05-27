<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->text('description')->nullable();
        
            $table->enum('base_mode', ['semantic_only', 'ai_first'])->default('semantic_only');
        
            $table->unsignedInteger('memory_write_limit')->default(200);
            $table->unsignedInteger('request_limit')->default(1000);
            $table->unsignedInteger('api_key_limit')->default(1);
        
            $table->unsignedInteger('test_rate_limit_max_requests')->default(1);
            $table->unsignedInteger('test_rate_limit_window_seconds')->default(30);
        
            $table->boolean('allow_test_keys')->default(true);
            $table->boolean('allow_live_keys')->default(false);
        
            $table->decimal('price_monthly', 10, 2)->default(0);
            $table->decimal('price_quarterly', 10, 2)->default(0);
            $table->decimal('price_yearly', 10, 2)->default(0);
        
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_plans');
    }
};
