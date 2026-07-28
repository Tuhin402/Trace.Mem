<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_catalog_audit_logs', function (Blueprint $table) {
            $table->id();
            
            $table->string('action');
            $table->string('entity_type');
            $table->string('entity_id');
            
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            
            $table->foreignId('performed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
                
            $table->string('ip_address')->nullable();
            $table->string('request_id')->nullable();
            
            $table->timestamp('created_at')->useCurrent();
            
            $table->index(['entity_type', 'entity_id']);
            $table->index('action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_catalog_audit_logs');
    }
};
