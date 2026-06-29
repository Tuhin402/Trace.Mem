<?php

namespace App\Providers;

use App\Contracts\Email\EmailService;
use App\Listeners\Email\SendPasswordChangedEmailListener;
use App\Models\ApiKey;
use App\Observers\ApiKeyObserver;
use App\Services\Email\EmailTheme;
use App\Services\Email\ResendEmailService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

/**
 * EmailServiceProvider
 *
 * Centralises all email system wiring:
 *   - Binds EmailService interface to ResendEmailService (swappable)
 *   - Registers event → listener mappings
 *   - Registers model observers
 *   - Shares EmailTheme as $theme to all emails.* Blade views
 *
 * To switch providers (e.g. SES, Postmark):
 *   1. Create SesEmailService implementing EmailService
 *   2. Change the binding below — zero other files change
 */
class EmailServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Provider-agnostic binding — all callers depend on EmailService, not Resend
        $this->app->singleton(EmailService::class, ResendEmailService::class);
    }

    public function boot(): void
    {
        $this->registerEventListeners();
        $this->registerObservers();
        $this->shareThemeWithViews();
    }

    private function registerEventListeners(): void
    {
        // Fired by Laravel/Fortify after successful password reset
        // → sends "your password was changed" confirmation email
        Event::listen(PasswordReset::class, SendPasswordChangedEmailListener::class);
    }

    private function registerObservers(): void
    {
        // Dispatches ApiKeyCreated email when a new key is persisted
        // ApiKeyService remains clean — no email coupling in business logic
        ApiKey::observe(ApiKeyObserver::class);
    }

    private function shareThemeWithViews(): void
    {
        // Share EmailTheme with every emails.* Blade template
        // Templates access brand tokens via $theme::background(), etc.
        View::composer('emails.*', function ($view) {
            $view->with('theme', new EmailTheme());
        });
    }
}
