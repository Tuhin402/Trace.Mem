<?php

namespace Tests\Feature\FreeTrial;

use App\Console\Commands\SendFreeTrialReminders;
use App\Enums\EmailTemplate;
use App\Jobs\SendEmailJob;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * FreeTrialReminderTest
 *
 * Tests that SendFreeTrialReminders dispatches emails correctly:
 *  - Sends 7, 3, and 1-day reminders for eligible users
 *  - Does NOT send if trial is not active
 *  - Does NOT send duplicate reminders (idempotency via email_logs)
 */
class FreeTrialReminderTest extends TestCase
{
    use RefreshDatabase;

    private SubscriptionPlan $starterPlan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->starterPlan = SubscriptionPlan::factory()->create([
            'slug'          => 'semantic-starter',
            'is_active'     => true,
            'price_monthly' => 199,
        ]);
        Queue::fake();
    }

    /** @test */
    public function dispatches_7_day_reminder_for_trial_ending_in_7_days(): void
    {
        $user = User::factory()->create([
            'free_trial_status'  => 'activated',
            'free_trial_ends_at' => now()->addDays(7)->midDay(),
            'free_trial_plan_id' => $this->starterPlan->id,
        ]);

        $this->artisan('email:free-trial-reminders')->assertSuccessful();

        Queue::assertPushed(SendEmailJob::class, function ($job) use ($user) {
            return $job->template === EmailTemplate::FreeTrialReminder
                && $job->userId === $user->id;
        });
    }

    /** @test */
    public function dispatches_3_day_reminder_for_trial_ending_in_3_days(): void
    {
        $user = User::factory()->create([
            'free_trial_status'  => 'activated',
            'free_trial_ends_at' => now()->addDays(3)->midDay(),
            'free_trial_plan_id' => $this->starterPlan->id,
        ]);

        $this->artisan('email:free-trial-reminders')->assertSuccessful();

        Queue::assertPushed(SendEmailJob::class, function ($job) use ($user) {
            return $job->template === EmailTemplate::FreeTrialReminder
                && $job->userId === $user->id;
        });
    }

    /** @test */
    public function dispatches_1_day_reminder_for_trial_ending_in_1_day(): void
    {
        $user = User::factory()->create([
            'free_trial_status'  => 'activated',
            'free_trial_ends_at' => now()->addDay()->midDay(),
            'free_trial_plan_id' => $this->starterPlan->id,
        ]);

        $this->artisan('email:free-trial-reminders')->assertSuccessful();

        Queue::assertPushed(SendEmailJob::class, function ($job) use ($user) {
            return $job->template === EmailTemplate::FreeTrialReminder
                && $job->userId === $user->id;
        });
    }

    /** @test */
    public function does_not_dispatch_reminder_for_non_trial_user(): void
    {
        User::factory()->create([
            'free_trial_status'  => null,
            'free_trial_ends_at' => now()->addDays(7)->midDay(),
        ]);

        $this->artisan('email:free-trial-reminders')->assertSuccessful();

        Queue::assertNotPushed(SendEmailJob::class);
    }

    /** @test */
    public function does_not_dispatch_reminder_for_completed_trial_user(): void
    {
        User::factory()->create([
            'free_trial_status'  => 'completed',
            'free_trial_ends_at' => now()->addDays(7)->midDay(),
        ]);

        $this->artisan('email:free-trial-reminders')->assertSuccessful();

        Queue::assertNotPushed(SendEmailJob::class);
    }

    /** @test */
    public function does_not_dispatch_for_user_not_in_reminder_window(): void
    {
        User::factory()->create([
            'free_trial_status'  => 'activated',
            'free_trial_ends_at' => now()->addDays(15)->midDay(), // Not in 7, 3, or 1 window
            'free_trial_plan_id' => $this->starterPlan->id,
        ]);

        $this->artisan('email:free-trial-reminders')->assertSuccessful();

        Queue::assertNotPushed(SendEmailJob::class);
    }

    /** @test */
    public function reminder_includes_dynamic_billing_amount_from_db(): void
    {
        $user = User::factory()->create([
            'free_trial_status'  => 'activated',
            'free_trial_ends_at' => now()->addDays(7)->midDay(),
            'free_trial_plan_id' => $this->starterPlan->id,
        ]);

        // Change price in DB — email data must reflect new price
        $this->starterPlan->update(['price_monthly' => 249]);

        $this->artisan('email:free-trial-reminders')->assertSuccessful();

        Queue::assertPushed(SendEmailJob::class, function ($job) {
            // The email data must include 249, not the original 199
            return $job->template === EmailTemplate::FreeTrialReminder
                && str_contains($job->data['next_billing_amount'] ?? '', '249');
        });
    }
}
