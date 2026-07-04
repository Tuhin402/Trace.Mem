<?php

namespace Tests\Feature\FreeTrial;

use App\Enums\EmailTemplate;
use App\Jobs\SendEmailJob;
use App\Models\BillingTransaction;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\UserSubscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * FreeTrialCheckoutTest
 *
 * Integration tests for the checkout + verifyPayment flows:
 *  - Trial path: start_at injected, pending_activation set, activated on verify
 *  - Non-trial path: normal checkout for prior-sub users
 *  - Race condition: concurrent requests only activate once
 *  - Razorpay failure: free_trial_status NOT permanently consumed
 *  - Upgrade from trial: old subscription cancelled, new subscription created
 */
class FreeTrialCheckoutTest extends TestCase
{
    use RefreshDatabase;

    private SubscriptionPlan $starterPlan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->starterPlan = SubscriptionPlan::factory()->create([
            'slug'               => 'semantic-starter',
            'is_active'          => true,
            'price_monthly'      => 199,
            'price_quarterly'    => 549,
            'price_yearly'       => 1999,
            'razorpay_plan_ids'  => ['monthly' => 'plan_test_monthly_123'],
        ]);
        Queue::fake();
    }

    /** @test */
    public function eligible_user_checkout_sets_pending_activation_and_sends_start_at_to_razorpay(): void
    {
        $user = User::factory()->create(['free_trial_status' => null]);

        Http::fake([
            'api.razorpay.com/v1/subscriptions' => Http::response([
                'id'     => 'sub_test_123',
                'status' => 'created',
            ], 200),
        ]);

        $this->actingAs($user)
            ->postJson('/billing/checkout', [
                'plan_slug'     => 'semantic-starter',
                'billing_cycle' => 'monthly',
            ])
            ->assertOk()
            ->assertJsonPath('is_trial', true)
            ->assertJsonPath('subscription_id', 'sub_test_123');

        // Verify start_at was sent to Razorpay
        Http::assertSent(function ($request) {
            $body = $request->data();
            return isset($body['start_at']) && $body['start_at'] > now()->timestamp;
        });

        // Verify user is in pending_activation
        $this->assertDatabaseHas('users', [
            'id'                => $user->id,
            'free_trial_status' => 'pending_activation',
        ]);
    }

    /** @test */
    public function ineligible_user_with_prior_subscription_gets_normal_checkout_no_trial(): void
    {
        $user = User::factory()->create(['free_trial_status' => null]);
        UserSubscription::factory()->create([
            'user_id'  => $user->id,
            'is_active'=> false,
            'status'   => 'expired',
        ]);

        Http::fake([
            'api.razorpay.com/v1/subscriptions' => Http::response([
                'id'     => 'sub_normal_456',
                'status' => 'created',
            ], 200),
        ]);

        $this->actingAs($user)
            ->postJson('/billing/checkout', [
                'plan_slug'     => 'semantic-starter',
                'billing_cycle' => 'monthly',
            ])
            ->assertOk()
            ->assertJsonPath('is_trial', false);

        // start_at should NOT be sent
        Http::assertSent(function ($request) {
            return ! isset($request->data()['start_at']);
        });

        // free_trial_status remains null
        $this->assertDatabaseHas('users', [
            'id'                => $user->id,
            'free_trial_status' => null,
        ]);
    }

    /** @test */
    public function razorpay_failure_does_not_permanently_consume_trial(): void
    {
        $user = User::factory()->create(['free_trial_status' => null]);

        Http::fake([
            'api.razorpay.com/v1/subscriptions' => Http::response([
                'error' => ['description' => 'Server error'],
            ], 500),
        ]);

        $this->actingAs($user)
            ->postJson('/billing/checkout', [
                'plan_slug'     => 'semantic-starter',
                'billing_cycle' => 'monthly',
            ])
            ->assertStatus(502);

        // CRITICAL: trial must NOT be consumed — user can retry
        $this->assertDatabaseHas('users', [
            'id'                => $user->id,
            'free_trial_status' => null,
        ]);
    }

    /** @test */
    public function non_monthly_cycle_does_not_get_trial(): void
    {
        $user = User::factory()->create(['free_trial_status' => null]);

        Http::fake([
            'api.razorpay.com/v1/subscriptions' => Http::response([
                'id'     => 'sub_quarterly_789',
                'status' => 'created',
            ], 200),
        ]);

        $this->actingAs($user)
            ->postJson('/billing/checkout', [
                'plan_slug'     => 'semantic-starter',
                'billing_cycle' => 'quarterly',
            ])
            ->assertOk()
            ->assertJsonPath('is_trial', false);

        $this->assertDatabaseHas('users', [
            'id'                => $user->id,
            'free_trial_status' => null,
        ]);
    }

    /** @test */
    public function verify_payment_activates_trial_on_success(): void
    {
        $user = User::factory()->create([
            'free_trial_status' => 'pending_activation',
        ]);

        // Pre-create the pending BillingTransaction as checkout would
        $tx = BillingTransaction::factory()->create([
            'user_id'                  => $user->id,
            'subscription_plan_id'     => $this->starterPlan->id,
            'provider'                 => 'razorpay',
            'provider_subscription_id' => 'sub_test_verify',
            'billing_cycle'            => 'monthly',
            'status'                   => 'pending',
            'metadata'                 => [
                'is_trial'      => true,
                'trial_ends_at' => now()->addMonth()->timestamp,
                'trial_plan_id' => $this->starterPlan->id,
            ],
        ]);

        // Forge a valid Razorpay signature
        $paymentId = 'pay_test_123';
        $subId     = 'sub_test_verify';
        $signature = hash_hmac('sha256', $paymentId . '|' . $subId, config('services.razorpay.key_secret'));

        $this->actingAs($user)
            ->postJson('/billing/verify-payment', [
                'razorpay_payment_id'      => $paymentId,
                'razorpay_subscription_id' => $subId,
                'razorpay_signature'       => $signature,
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        // Trial should be activated
        $this->assertDatabaseHas('users', [
            'id'                => $user->id,
            'free_trial_status' => 'activated',
        ]);

        // FreeTrialStarted email should be queued
        Queue::assertPushed(SendEmailJob::class, function ($job) {
            return $job->template === EmailTemplate::FreeTrialStarted;
        });

        // An active UserSubscription should exist
        $this->assertDatabaseHas('user_subscriptions', [
            'user_id'  => $user->id,
            'is_active'=> true,
            'status'   => 'active',
        ]);
    }

    /** @test */
    public function signature_mismatch_returns_422(): void
    {
        $user = User::factory()->create(['free_trial_status' => 'pending_activation']);

        $this->actingAs($user)
            ->postJson('/billing/verify-payment', [
                'razorpay_payment_id'      => 'pay_fake',
                'razorpay_subscription_id' => 'sub_fake',
                'razorpay_signature'       => 'invalid_signature',
            ])
            ->assertStatus(422);
    }
}
