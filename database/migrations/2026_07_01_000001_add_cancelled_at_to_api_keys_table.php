<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add cancelled_at to api_keys.
     *
     * This column tracks soft-cancellation of keys (e.g. when a subscription
     * is cancelled and the associated keys are invalidated). Distinct from
     * revoked_at (manual/rotated) and expires_at (TTL-based expiry).
     */
    public function up(): void
    {
        Schema::table('api_keys', function (Blueprint $table) {
            $table->timestamp('cancelled_at')->nullable()->after('revoked_at');
            $table->index('cancelled_at');
        });
    }

    public function down(): void
    {
        Schema::table('api_keys', function (Blueprint $table) {
            $table->dropIndex(['cancelled_at']);
            $table->dropColumn('cancelled_at');
        });
    }
};
