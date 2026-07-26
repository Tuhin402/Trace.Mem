<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            // The UUID acts as the primary key and maps directly to the legacy `tenant_scope_id`
            $table->uuid('id')->primary();
            
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('status')->default('active');
            $table->string('plan')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
        });
        
        // Zero schema changes made to `users` or `teams` or `api_keys`.
        // They continue to use their existing `tenant_scope_id` columns unmodified.
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
