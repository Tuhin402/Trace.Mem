<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            // Stores Razorpay Plan IDs per billing cycle.
            // Structure: {"monthly": "plan_abc", "quarterly": "plan_def", "yearly": "plan_ghi"}
            // Plans are created lazily at first checkout and cached here to avoid re-creation.
            $table->json('razorpay_plan_ids')->nullable()->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->dropColumn(['razorpay_plan_ids']);
        });
    }
};
