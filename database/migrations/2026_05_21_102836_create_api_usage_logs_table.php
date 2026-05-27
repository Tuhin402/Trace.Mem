<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_usage_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('api_key_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('endpoint');
            $table->string('method', 10);

            $table->unsignedInteger('status_code');

            $table->unsignedInteger('latency_ms')->nullable();
            $table->unsignedInteger('tokens_used')->default(0);


            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent', 1024)->nullable();
            $table->uuid('request_id')->nullable();


            $table->json('metadata')->nullable();


            $table->timestamp('requested_at');
            $table->timestamps();


            $table->index('requested_at');
            $table->index('status_code');

            $table->index([
                'api_key_id',
                'requested_at',
            ], 'api_usage_logs_key_requested_idx');

            $table->index([
                'endpoint',
                'method',
            ], 'api_usage_logs_endpoint_method_idx');

            $table->index('request_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_usage_logs');
    }
};