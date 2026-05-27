<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->unsignedInteger('test_api_key_limit')->default(1)->after('api_key_limit');
            $table->unsignedInteger('live_api_key_limit')->default(1)->after('test_api_key_limit');

            $table->unsignedInteger('test_key_ttl_days')->default(30)->after('live_api_key_limit');
            $table->unsignedInteger('live_key_ttl_days')->nullable()->after('test_key_ttl_days');
        });

        Schema::table('api_keys', function (Blueprint $table) {
            $table->boolean('sandbox_only')->default(false)->after('environment');
            $table->unsignedInteger('key_version')->default(1)->after('key_last4');

            $table->timestamp('issued_at')->nullable()->after('key_version');
            $table->timestamp('last_rotated_at')->nullable()->after('issued_at');

            $table->json('allowed_origins')->nullable()->after('metadata');
            $table->json('allowed_ips')->nullable()->after('allowed_origins');

            $table->index(['user_id', 'environment', 'revoked_at']);
            $table->index(['subscription_plan_id', 'environment']);
        });

        Schema::table('api_usage_logs', function (Blueprint $table) {
            $table->string('request_host', 255)->nullable()->after('ip_address');
            $table->string('request_origin', 255)->nullable()->after('request_host');
            $table->boolean('is_sandbox')->default(false)->after('request_origin');
            $table->boolean('is_localhost')->default(false)->after('is_sandbox');
        });

        Schema::create('api_key_rotations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('api_key_id')->constrained('api_keys')->cascadeOnDelete();
            $table->foreignId('replaced_by_api_key_id')->nullable()->constrained('api_keys')->nullOnDelete();
            $table->string('reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('rotated_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_key_rotations');

        Schema::table('api_usage_logs', function (Blueprint $table) {
            $table->dropColumn([
                'request_host',
                'request_origin',
                'is_sandbox',
                'is_localhost',
            ]);
        });

        Schema::table('api_keys', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'environment', 'revoked_at']);
            $table->dropIndex(['subscription_plan_id', 'environment']);

            $table->dropColumn([
                'sandbox_only',
                'key_version',
                'issued_at',
                'last_rotated_at',
                'allowed_origins',
                'allowed_ips',
            ]);
        });

        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->dropColumn([
                'test_api_key_limit',
                'live_api_key_limit',
                'test_key_ttl_days',
                'live_key_ttl_days',
            ]);
        });
    }
};