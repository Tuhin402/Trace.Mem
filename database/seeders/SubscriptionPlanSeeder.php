<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use App\Models\SubscriptionPlanFeature;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    public function run(): void
    {
        $semantic = SubscriptionPlan::updateOrCreate(
            ['slug' => 'semantic-starter'],
            [
                'name' => 'Semantic Starter',
                'description' => 'Cheaper plan with semantic-only access.',
                'base_mode' => 'semantic_only',
                'memory_write_limit' => 10000,
                'request_limit' => 50000,
                'api_key_limit' => 1,
                'test_api_key_limit' => 1,
                'live_api_key_limit' => 1,
                'test_key_ttl_days' => 30,
                'live_key_ttl_days' => null,
                'request_rate_limit_max_requests' => 1,
                'request_rate_limit_window_seconds' => 5,
                'test_rate_limit_max_requests' => 1,
                'test_rate_limit_window_seconds' => 5,
                'allow_test_keys' => true,
                'allow_live_keys' => true,
                'price_monthly' => 19.00,
                'price_quarterly' => 49.00,
                'price_yearly' => 149.00,
                'is_active' => true,
            ]
        );

        $ai = SubscriptionPlan::updateOrCreate(
            ['slug' => 'ai-first-pro'],
            [
                'name' => 'AI First Pro',
                'description' => 'Premium plan with AI-first memory extraction.',
                'base_mode' => 'ai_first',
                'memory_write_limit' => 50000,
                'request_limit' => 100000,
                'api_key_limit' => 3,
                'test_api_key_limit' => 1,
                'live_api_key_limit' => 3,
                'test_key_ttl_days' => 30,
                'live_key_ttl_days' => null,
                'request_rate_limit_max_requests' => 1,
                'request_rate_limit_window_seconds' => 2,
                'test_rate_limit_max_requests' => 1,
                'test_rate_limit_window_seconds' => 2,
                'allow_test_keys' => true,
                'allow_live_keys' => true,
                'price_monthly' => 39.00,
                'price_quarterly' => 109.00,
                'price_yearly' => 349.00,
                'is_active' => true,
            ]
        );

        $this->seedPlanFeatures($semantic, [
            [
                'feature_scope' => 'model',
                'model_provider' => 'internal',
                'model_name' => 'semantic-engine',
                'feature_key' => 'memory.semantic.search',
                'feature_value' => [
                    'enabled' => true,
                    'mode' => 'semantic_only',
                    'description' => 'Semantic segmentation and structured memory search are enabled.',
                ],
                'is_enabled' => true,
            ],
            [
                'feature_scope' => 'global',
                'model_provider' => null,
                'model_name' => null,
                'feature_key' => 'memory.recall',
                'feature_value' => [
                    'enabled' => true,
                    'limit' => 'unlimited',
                    'description' => 'Unlimited recall for scoped memories.',
                ],
                'is_enabled' => true,
            ],
            [
                'feature_scope' => 'global',
                'model_provider' => null,
                'model_name' => null,
                'feature_key' => 'memory.context.assemble',
                'feature_value' => [
                    'enabled' => true,
                    'token_budget' => 600,
                    'description' => 'Prompt-ready context assembly with token budgeting.',
                ],
                'is_enabled' => true,
            ],
        ]);

        $this->seedPlanFeatures($ai, [
            [
                'feature_scope' => 'model',
                'model_provider' => 'openai',
                'model_name' => 'gpt-4o-mini',
                'feature_key' => 'memory.ai.extraction',
                'feature_value' => [
                    'enabled' => true,
                    'mode' => 'ai_first',
                    'description' => 'AI extraction enabled for richer memory understanding.',
                ],
                'is_enabled' => true,
            ],
            [
                'feature_scope' => 'global',
                'model_provider' => null,
                'model_name' => null,
                'feature_key' => 'memory.recall',
                'feature_value' => [
                    'enabled' => true,
                    'limit' => 'unlimited',
                    'description' => 'Unlimited recall for scoped memories.',
                ],
                'is_enabled' => true,
            ],
            [
                'feature_scope' => 'global',
                'model_provider' => null,
                'model_name' => null,
                'feature_key' => 'memory.context.assemble',
                'feature_value' => [
                    'enabled' => true,
                    'token_budget' => 1200,
                    'description' => 'Prompt-ready context assembly with a larger token budget.',
                ],
                'is_enabled' => true,
            ],
        ]);
    }

    private function seedPlanFeatures(SubscriptionPlan $plan, array $features): void
    {
        foreach ($features as $feature) {
            SubscriptionPlanFeature::updateOrCreate(
                [
                    'subscription_plan_id' => $plan->id,
                    'feature_scope' => $feature['feature_scope'],
                    'model_provider' => $feature['model_provider'],
                    'model_name' => $feature['model_name'],
                    'feature_key' => $feature['feature_key'],
                ],
                [
                    'feature_value' => $feature['feature_value'],
                    'is_enabled' => $feature['is_enabled'],
                ]
            );
        }
    }
}