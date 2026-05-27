<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_plan_features', function (Blueprint $table) {
            $table->id();
        
            $table->foreignId('subscription_plan_id')
                ->constrained()
                ->cascadeOnDelete();
        
            $table->enum('feature_scope', ['global', 'model'])->default('global');
            $table->string('model_provider')->nullable(); 
            $table->string('model_name')->nullable();     
        
            $table->string('feature_key');
            $table->json('feature_value')->nullable();
            $table->boolean('is_enabled')->default(true);
        
            $table->timestamps();
        
            $table->unique([
                'subscription_plan_id',
                'feature_scope',
                'model_provider',
                'model_name',
                'feature_key',
            ], 'plan_feature_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_plan_features');
    }
};
