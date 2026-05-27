<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->unsignedInteger('request_rate_limit_max_requests')->default(1)->after('request_limit');
            $table->unsignedInteger('request_rate_limit_window_seconds')->default(30)->after('request_rate_limit_max_requests');
        });
    }

    public function down(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->dropColumn([
                'request_rate_limit_max_requests',
                'request_rate_limit_window_seconds',
            ]);
        });
    }
};
