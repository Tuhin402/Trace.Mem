<?php

namespace App\Services\Billing;

use App\Models\SubscriptionPlan;
use App\Models\User;
use Carbon\CarbonImmutable;

/**
 * FreeTrialEligibilityService — single source of truth for all "Founding Offer" logic.
 *
 * Rules:
 *   - Offer is only for semantic-starter plan, monthly cycle.
 *   - A user is eligible ONLY if they have never had ANY subscription (any plan, any status)
 *     AND their free_trial_status is null.
 *   - Once a trial is consumed (activated, cancelled, upgraded, or completed),
 *     it can NEVER be undone — even after cancellation or expiry.
 *   - All monetary values are read from the SubscriptionPlan DB row — never hardcoded.
 */
class FreeTrialEligibilityService
{
    /** The plan slug and cycle that qualify for the free trial. */
    public const TRIAL_PLAN_SLUG = 'semantic-starter';
    public const TRIAL_CYCLE     = 'monthly';

    /** Terminal states — offer permanently consumed. */
    private const CONSUMED_STATES = ['activated', 'completed', 'cancelled', 'upgraded', 'pending_activation'];

    /* ═══════════════════════════════════════════════════════════════
     *  ELIGIBILITY CHECKS
     * ═══════════════════════════════════════════════════════════════ */

    /**
     * Can this user ever receive the Founding Offer?
     *
     * Returns true ONLY when:
     *   - free_trial_status is null (offer never touched)
     *   - User has zero subscription rows of any kind
     */
    public function isEligible(User $user): bool
    {
        // Freshly loaded check — avoid stale model cache
        if ($user->free_trial_status !== null) {
            return false;
        }

        // Check if user has EVER had ANY subscription (any plan, any status)
        return ! $user->subscriptions()->exists();
    }

    /**
     * Can this user activate the trial for this specific plan + cycle?
     * This is the guard called at checkout time.
     */
    public function canActivate(User $user, string $planSlug, string $cycle): bool
    {
        return $this->isEligible($user)
            && $planSlug === self::TRIAL_PLAN_SLUG
            && $cycle    === self::TRIAL_CYCLE;
    }

    /**
     * Is the user currently inside an active free trial?
     * "Active" means the trial has been confirmed (authenticated) AND has not expired.
     */
    public function isInActiveTrial(User $user): bool
    {
        return $user->free_trial_status === 'activated'
            && $user->free_trial_ends_at !== null
            && $user->free_trial_ends_at->isFuture();
    }

    /**
     * Has the trial offer ever been consumed for this user?
     * Covers: activated, completed, cancelled, upgraded (and pending_activation).
     */
    public function isConsumed(User $user): bool
    {
        return in_array($user->free_trial_status, self::CONSUMED_STATES, strict: true);
    }

    /* ═══════════════════════════════════════════════════════════════
     *  TRIAL DATA ACCESSORS
     * ═══════════════════════════════════════════════════════════════ */

    /**
     * Days remaining in the free trial (0 if expired or not in trial).
     */
    public function daysRemaining(User $user): int
    {
        if (! $this->isInActiveTrial($user)) {
            return 0;
        }

        return (int) max(0, now()->diffInDays($user->free_trial_ends_at, absolute: false));
    }

    /**
     * The monthly price for the trial plan, read from the DB.
     * Returns a formatted string like "₹199". Never hardcoded.
     */
    public function getMonthlyPrice(User $user): string
    {
        // Prefer the plan stored at trial activation; fall back to slug lookup
        $planId = $user->free_trial_plan_id;

        $plan = $planId
            ? SubscriptionPlan::find($planId)
            : SubscriptionPlan::where('slug', self::TRIAL_PLAN_SLUG)->first();

        if (! $plan) {
            return '₹0';
        }

        return '₹' . number_format((float) $plan->price_monthly, 0);
    }

    /**
     * The raw decimal monthly price from DB (for Razorpay amount calculation).
     */
    public function getMonthlyPriceDecimal(?int $planId = null): float
    {
        $plan = $planId
            ? SubscriptionPlan::find($planId)
            : SubscriptionPlan::where('slug', self::TRIAL_PLAN_SLUG)->first();

        return (float) ($plan?->price_monthly ?? 0);
    }

    /**
     * Returns all trial info for controllers to pass to the frontend.
     * All amounts come from DB — no hardcoding.
     */
    public function getTrialInfo(User $user): array
    {
        return [
            'is_eligible'         => $this->isEligible($user),
            'is_in_trial'         => $this->isInActiveTrial($user),
            'is_consumed'         => $this->isConsumed($user),
            'trial_status'        => $user->free_trial_status,
            'days_remaining'      => $this->daysRemaining($user),
            'trial_ends_at'       => $user->free_trial_ends_at?->format('M j, Y'),
            'autopay_begins_at'   => $user->free_trial_ends_at?->format('M j, Y'),
            'next_billing_amount' => $this->getMonthlyPrice($user),
        ];
    }
}
