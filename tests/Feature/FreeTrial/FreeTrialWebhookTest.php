<?php

namespace Tests\Feature\FreeTrial;

use App\Enums\EmailTemplate;
use App\Jobs\ProcessRazorpayWebhookJob;
use App\Jobs\SendEmailJob;
use App\Models\BillingTransaction;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\UserSubscription;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * FreeTrialWebhookTest
 *
 * Tests that ProcessRazorpayWebhookJob handles free trial lifecycle events correctly:
 *  - subscription.activated with is_trial=1 → FreeTrialStarted email, not SubscriptionPurchased
 *  - subscription.charged when trial active → free_trial_status = 'completed'
 *  - subscription.cancelled when trial active → free_trial_status = 'cancelled'
 *  - Duplicate webhook delivery → idempotent (no double state change)
 *  - payment.failed during trial → past_due but free_trial_status unchanged
 */
class FreeTrialWebhookTest extends TestCase
{
    use RefreshDatabase;

    private SubscriptionPlan $starterPlan;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->starterPlan = SubscriptionPlan::factory()->create([
            'slug'          => 'semantic-starter',
            'is_active'     => true,
            'price_monthly' => 199,
        ]);
        $this->user = User::factory()->create([
            'free_trial_status'  => 'activated',
            'free_trial_ends_at' => now()->addDays(20),
            'free_trial_plan_id' => $this->starterPlan->id,
        ]);
        Queue::fake();
    }

    private function buildSubscriptionPayload(string $rzpSubId, array $notes = [], bool $isTrial = false): array
    {
        return [
            'event'   => 'subscription.activated',
            'payload' => [
                'subscription' => [
                    'entity' => array_merge([
                        'id'          => $rzpSubId,
                        'status'      => 'active',
                        'current_end' => now()->addMonth()->timestamp,
                        'customer_id' => 'cust_test',
                        'notes'       => array_merge([
                            'user_id'       => (string) $this->user->id,
                            'plan_id'       => (string) $this->starterPlan->id,
                            'billing_cycle' => 'monthly',
                        ], $notes),
                    ], $isTrial ? ['notes' => ['is_trial' => '1', 'user_id' => (string) $this->user->id, 'plan_id' => (string) $this->starterPlan->id, 'billing_cycle' => 'monthly', 'trial_ends_at' => (string) now()->addMonth()->timestamp]] : []),
                ],
            ],
        ];
    }

    /** @test */
    public function subscription_activated_with_trial_flag_sends_free_trial_started_email(): void
    {
        $payload = [
            'event'   => 'subscription.activated',
            'payload' => [
                'subscription' => [
                    'entity' => [
                        'id'          => 'sub_trial_001',
                        'status'      => 'active',
                        'current_end' => now()->addMonth()->timestamp,
                        'customer_id' => 'cust_test',
                        'notes'       => [
                            'user_id'       => (string) $this->user->id,
                            'plan_id'       => (string) $this->starterPlan->id,
                            'billing_cycle' => 'monthly',
                            'is_trial'      => '1',
                            'trial_ends_at' => (string) now()->addMonth()->timestamp,
                        ],
                    ],
                ],
            ],
        ];

        // Update user to pending (webhook backup path)
        $this->user->update(['free_trial_status' => 'pending_activation']);

        ProcessRazorpayWebhookJob::dispatchSync($payload, 'unique_event_id_trial_001');

        // Should have dispatched FreeTrialStarted, not SubscriptionPurchased
        Queue::assertPushed(SendEmailJob::class, function ($job) {
            return $job->template === EmailTemplate::FreeTrialStarted;
        });
        Queue::assertNotPushed(SendEmailJob::class, function ($job) {
            return $job->template === EmailTemplate::SubscriptionPurchased;
        });

        // Trial should be activated via webhook backup path
        $this->assertDatabaseHas('users', [
            'id'                => $this->user->id,
            'free_trial_status' => 'activated',
        ]);
    }

    /** @test */
    public function subscription_charged_when_trial_active_marks_trial_completed(): void
    {
        UserSubscription::factory()->create([
            'user_id'  => $this->user->id,
            'is_active'=> true,
            'status'   => 'active',
        ]);

        $payload = [
            'event'   => 'subscription.charged',
            'payload' => [
                'subscription' => [
                    'entity' => [
                        'id'          => 'sub_trial_001',
                        'current_end' => now()->addMonth()->timestamp,
                        'notes'       => [
                            'user_id'       => (string) $this->user->id,
                            'plan_id'       => (string) $this->starterPlan->id,
                            'billing_cycle' => 'monthly',
                        ],
                    ],
                ],
                'payment' => [
                    'entity' => [
                        'id'       => 'pay_charge_001',
                        'amount'   => 19900,
                        'currency' => 'INR',
                    ],
                ],
            ],
        ];

        ProcessRazorpayWebhookJob::dispatchSync($payload, 'unique_event_id_charged_001');

        $this->assertDatabaseHas('users', [
            'id'                => $this->user->id,
            'free_trial_status' => 'completed',
        ]);
    }

    /** @test */
    public function subscription_cancelled_when_trial_active_marks_trial_cancelled(): void
    {
        UserSubscription::factory()->create([
            'user_id'  => $this->user->id,
            'is_active'=> true,
            'status'   => 'active',
        ]);

        $payload = [
            'event'   => 'subscription.cancelled',
            'payload' => [
                'subscription' => [
                    'entity' => [
                        'id'    => 'sub_trial_001',
                        'notes' => [
                            'user_id'       => (string) $this->user->id,
                            'plan_id'       => (string) $this->starterPlan->id,
                            'billing_cycle' => 'monthly',
                        ],
                    ],
                ],
            ],
        ];

        ProcessRazorpayWebhookJob::dispatchSync($payload, 'unique_event_id_cancelled_001');

        $this->assertDatabaseHas('users', [
            'id'                => $this->user->id,
            'free_trial_status' => 'cancelled',
        ]);
    }

    /** @test */
    public function duplicate_webhook_delivery_is_idempotent(): void
    {
        $payload = [
            'event'   => 'subscription.activated',
            'payload' => [
                'subscription' => [
                    'entity' => [
                        'id'          => 'sub_dup_001',
                        'current_end' => now()->addMonth()->timestamp,
                        'customer_id' => 'cust_test',
                        'notes'       => [
                            'user_id'       => (string) $this->user->id,
                            'plan_id'       => (string) $this->starterPlan->id,
                            'billing_cycle' => 'monthly',
                        ],
                    ],
                ],
            ],
        ];

        // Same event_id = duplicate
        ProcessRazorpayWebhookJob::dispatchSync($payload, 'duplicate_event_id');
        ProcessRazorpayWebhookJob::dispatchSync($payload, 'duplicate_event_id');

        // Only ONE record in webhook events table
        $this->assertDatabaseCount('razorpay_webhook_events', 1);
    }

    /** @test */
    public function payment_failed_during_trial_sets_past_due_but_does_not_change_trial_status(): void
    {
        $sub = UserSubscription::factory()->create([
            'user_id'  => $this->user->id,
            'is_active'=> true,
            'status'   => 'active',
        ]);

        $tx = BillingTransaction::factory()->create([
            'user_id'                  => $this->user->id,
            'subscription_plan_id'     => $this->starterPlan->id,
            'provider_subscription_id' => 'sub_trial_001',
            'status'                   => 'pending',
        ]);

        $payload = [
            'event'   => 'payment.failed',
            'payload' => [
                'payment' => [
                    'entity' => [
                        'id'                  => 'pay_failed_001',
                        'subscription_id'     => 'sub_trial_001',
                        'error_code'          => 'BAD_REQUEST_ERROR',
                        'error_description'   => 'Insufficient funds',
                    ],
                ],
            ],
        ];

        ProcessRazorpayWebhookJob::dispatchSync($payload, 'unique_event_id_fail_001');

        // Subscription should be past_due
        $this->assertDatabaseHas('user_subscriptions', [
            'id'     => $sub->id,
            'status' => 'past_due',
        ]);

        // Trial status must be UNCHANGED — still 'activated'
        $this->assertDatabaseHas('users', [
            'id'                => $this->user->id,
            'free_trial_status' => 'activated',
        ]);
    }
}
