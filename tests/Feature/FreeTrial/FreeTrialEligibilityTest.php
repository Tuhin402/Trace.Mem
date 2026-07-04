<?php

namespace Tests\Feature\FreeTrial;

use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\UserSubscription;
use App\Services\Billing\FreeTrialEligibilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * FreeTrialEligibilityTest
 *
 * Verifies the FreeTrialEligibilityService state machine.
 * All tests run in a fresh DB (RefreshDatabase) to ensure isolation.
 */
class FreeTrialEligibilityTest extends TestCase
{
    use RefreshDatabase;

    private FreeTrialEligibilityService $service;
    private SubscriptionPlan $starterPlan;
    private SubscriptionPlan $proPlan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service     = app(FreeTrialEligibilityService::class);
        $this->starterPlan = SubscriptionPlan::factory()->create([
            'slug'          => 'semantic-starter',
            'is_active'     => true,
            'price_monthly' => 199,
        ]);
        $this->proPlan = SubscriptionPlan::factory()->create([
            'slug'          => 'semantic-pro',
            'is_active'     => true,
            'price_monthly' => 499,
        ]);
    }

    /* ═══════════════════════════════════════════════════════════════
     *  ELIGIBILITY CHECKS
     * ═══════════════════════════════════════════════════════════════ */

    /** @test */
    public function fresh_user_with_no_subscriptions_is_eligible(): void
    {
        $user = User::factory()->create(['free_trial_status' => null]);

        $this->assertTrue($this->service->isEligible($user));
    }

    /** @test */
    public function user_with_any_prior_subscription_is_not_eligible(): void
    {
        $user = User::factory()->create(['free_trial_status' => null]);
        UserSubscription::factory()->create([
            'user_id' => $user->id,
            'status'  => 'expired',
        ]);

        $this->assertFalse($this->service->isEligible($user));
    }

    /** @test */
    public function user_with_active_subscription_is_not_eligible(): void
    {
        $user = User::factory()->create(['free_trial_status' => null]);
        UserSubscription::factory()->create([
            'user_id'  => $user->id,
            'status'   => 'active',
            'is_active'=> true,
        ]);

        $this->assertFalse($this->service->isEligible($user));
    }

    /** @test */
    public function user_with_activated_trial_status_is_not_eligible(): void
    {
        $user = User::factory()->create(['free_trial_status' => 'activated']);

        $this->assertFalse($this->service->isEligible($user));
    }

    /** @test */
    public function user_with_cancelled_trial_status_is_not_eligible(): void
    {
        $user = User::factory()->create(['free_trial_status' => 'cancelled']);

        $this->assertFalse($this->service->isEligible($user));
    }

    /** @test */
    public function user_with_completed_trial_status_is_not_eligible(): void
    {
        $user = User::factory()->create(['free_trial_status' => 'completed']);

        $this->assertFalse($this->service->isEligible($user));
    }

    /** @test */
    public function user_with_upgraded_trial_status_is_not_eligible(): void
    {
        $user = User::factory()->create(['free_trial_status' => 'upgraded']);

        $this->assertFalse($this->service->isEligible($user));
    }

    /** @test */
    public function user_with_pending_activation_status_is_not_eligible(): void
    {
        $user = User::factory()->create(['free_trial_status' => 'pending_activation']);

        $this->assertFalse($this->service->isEligible($user));
    }

    /* ═══════════════════════════════════════════════════════════════
     *  CAN ACTIVATE CHECKS (plan + cycle constraints)
     * ═══════════════════════════════════════════════════════════════ */

    /** @test */
    public function can_activate_returns_true_for_eligible_user_semantic_starter_monthly(): void
    {
        $user = User::factory()->create(['free_trial_status' => null]);

        $this->assertTrue($this->service->canActivate($user, 'semantic-starter', 'monthly'));
    }

    /** @test */
    public function can_activate_returns_false_for_non_starter_plan(): void
    {
        $user = User::factory()->create(['free_trial_status' => null]);

        $this->assertFalse($this->service->canActivate($user, 'semantic-pro', 'monthly'));
    }

    /** @test */
    public function can_activate_returns_false_for_quarterly_cycle(): void
    {
        $user = User::factory()->create(['free_trial_status' => null]);

        $this->assertFalse($this->service->canActivate($user, 'semantic-starter', 'quarterly'));
    }

    /** @test */
    public function can_activate_returns_false_for_yearly_cycle(): void
    {
        $user = User::factory()->create(['free_trial_status' => null]);

        $this->assertFalse($this->service->canActivate($user, 'semantic-starter', 'yearly'));
    }

    /* ═══════════════════════════════════════════════════════════════
     *  ACTIVE TRIAL CHECKS
     * ═══════════════════════════════════════════════════════════════ */

    /** @test */
    public function is_in_active_trial_returns_true_for_activated_user_with_future_end(): void
    {
        $user = User::factory()->create([
            'free_trial_status'  => 'activated',
            'free_trial_ends_at' => now()->addDays(15),
        ]);

        $this->assertTrue($this->service->isInActiveTrial($user));
    }

    /** @test */
    public function is_in_active_trial_returns_false_for_expired_trial(): void
    {
        $user = User::factory()->create([
            'free_trial_status'  => 'activated',
            'free_trial_ends_at' => now()->subDay(),
        ]);

        $this->assertFalse($this->service->isInActiveTrial($user));
    }

    /* ═══════════════════════════════════════════════════════════════
     *  PRICE ACCESSOR
     * ═══════════════════════════════════════════════════════════════ */

    /** @test */
    public function get_monthly_price_reads_from_db_not_hardcoded(): void
    {
        $user = User::factory()->create([
            'free_trial_plan_id' => $this->starterPlan->id,
        ]);

        // Change the DB price — the accessor must reflect it
        $this->starterPlan->update(['price_monthly' => 299]);

        $price = $this->service->getMonthlyPrice($user);

        $this->assertStringContainsString('299', $price);
        $this->assertStringNotContainsString('199', $price);
    }
}
