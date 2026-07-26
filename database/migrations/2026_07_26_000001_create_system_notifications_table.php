<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('message')->nullable();
            $table->string('type'); // info, warning, critical
            $table->string('component')->nullable(); // e.g., 'billing', 'database'
            $table->boolean('is_read')->default(false);
            $table->timestamps();
            
            $table->index('is_read');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_notifications');
    }
};
