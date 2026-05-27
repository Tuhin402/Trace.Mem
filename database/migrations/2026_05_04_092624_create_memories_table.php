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
        Schema::create('memories', function (Blueprint $table) {
            $table->id();

            $table->string('tenant_id', 64);
            $table->string('user_id', 64);

            $table->string('type', 32); // preference, fact, rule, skill
            $table->text('content');    // normalized memory text

            $table->decimal('importance', 5, 4)->default(0.5000);
            $table->decimal('confidence', 5, 4)->default(0.5000);
            $table->decimal('decay_score', 5, 4)->default(1.0000);

            $table->timestamp('last_accessed_at')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index(['tenant_id', 'user_id'], 'memories_tenant_user_index');
            $table->index('type', 'memories_type_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('memories');
    }
};