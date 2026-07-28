<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_pricing_histories', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('subscription_plan_id')
                ->constrained()
                ->cascadeOnDelete();
                
            $table->enum('period', ['monthly', 'quarterly', 'yearly']);
            
            $table->decimal('old_amount', 10, 2);
            $table->decimal('new_amount', 10, 2);
            
            $table->foreignId('changed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
                
            $table->text('reason')->nullable();
            
            $table->string('razorpay_old_plan_id')->nullable();
            $table->string('razorpay_new_plan_id')->nullable();
            
            $table->timestamp('created_at')->useCurrent();
            
            $table->index(['subscription_plan_id', 'period']);
            $table->index(['subscription_plan_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_pricing_histories');
    }
};
