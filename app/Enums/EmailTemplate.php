<?php

namespace App\Enums;

enum EmailTemplate: string
{
    case Verification          = 'verification';
    case PasswordReset         = 'password_reset';
    case PasswordChanged       = 'password_changed';
    case EmailChanged          = 'email_changed';
    case SubscriptionPurchased = 'subscription_purchased';
    case SubscriptionRenewed   = 'subscription_renewed';
    case SubscriptionCancelled = 'subscription_cancelled';
    case PaymentReceived       = 'payment_received';
    case PaymentFailed         = 'payment_failed';
    case RefundProcessed       = 'refund_processed';
    case ApiKeyCreated         = 'api_key_created';
    case ApiKeyRotated         = 'api_key_rotated';
    case ApiKeyExpiryReminder  = 'api_key_expiry_reminder';
    case PlanExpiryReminder    = 'plan_expiry_reminder';
    // ── Free Trial (Founding Offer) ───────────────────────────────────
    case FreeTrialStarted      = 'free_trial_started';
    case FreeTrialReminder     = 'free_trial_reminder';  // shared for 7/3/1-day reminders
    // ── Workspace ─────────────────────────────────────────────────────────
    case WorkspaceInvitation   = 'workspace_invitation';

    // ── Subject lines ─────────────────────────────────────────────────────────

    public function subject(): string
    {
        return match ($this) {
            self::Verification          => 'Confirm your Trace.Mem email address',
            self::PasswordReset         => 'Reset your Trace.Mem password',
            self::PasswordChanged       => 'Your Trace.Mem password was changed',
            self::EmailChanged          => 'Your Trace.Mem email address was updated',
            self::SubscriptionPurchased => 'Your Trace.Mem subscription is active',
            self::SubscriptionRenewed   => 'Your Trace.Mem subscription renewed',
            self::SubscriptionCancelled => 'Your Trace.Mem subscription has been cancelled',
            self::PaymentReceived       => 'Payment received — Trace.Mem',
            self::PaymentFailed         => 'Payment failed — action required',
            self::RefundProcessed       => 'Your refund has been processed',
            self::ApiKeyCreated         => 'New API key created',
            self::ApiKeyRotated         => 'Your API key has been rotated',
            self::ApiKeyExpiryReminder  => 'Your API key expires in 7 days',
            self::PlanExpiryReminder    => 'Your Trace.Mem plan expires in 7 days',
            self::FreeTrialStarted      => 'Your Founding Offer is active — first month of TraceMem is free',
            self::FreeTrialReminder     => 'Your Founding Offer ends soon — TraceMem',
            self::WorkspaceInvitation   => 'You have been invited to a Trace.Mem workspace',
        };
    }

    // ── Template version — bump when a template is redesigned ─────────────────
    // Old email_logs records retain their original version for historical accuracy.

    public function version(): string
    {
        return match ($this) {
            default => 'v1',
        };
    }

    /** Full version string stored in email_logs.template_version */
    public function versionedName(): string
    {
        return $this->value . '_' . $this->version();
    }

    /** PascalCase name for X-TraceMem-Template header */
    public function headerName(): string
    {
        return match ($this) {
            self::Verification          => 'VerificationEmail',
            self::PasswordReset         => 'PasswordResetEmail',
            self::PasswordChanged       => 'PasswordChangedEmail',
            self::EmailChanged          => 'EmailChangedEmail',
            self::SubscriptionPurchased => 'SubscriptionPurchasedEmail',
            self::SubscriptionRenewed   => 'SubscriptionRenewedEmail',
            self::SubscriptionCancelled => 'SubscriptionCancelledEmail',
            self::PaymentReceived       => 'PaymentReceivedEmail',
            self::PaymentFailed         => 'PaymentFailedEmail',
            self::RefundProcessed       => 'RefundProcessedEmail',
            self::ApiKeyCreated         => 'ApiKeyCreatedEmail',
            self::ApiKeyRotated         => 'ApiKeyRotatedEmail',
            self::ApiKeyExpiryReminder  => 'ApiKeyExpiryReminderEmail',
            self::PlanExpiryReminder    => 'PlanExpiryReminderEmail',
            self::FreeTrialStarted      => 'FreeTrialStartedEmail',
            self::FreeTrialReminder     => 'FreeTrialReminderEmail',
            self::WorkspaceInvitation   => 'WorkspaceInvitationEmail',
        };
    }

    /** Blade view path — maps to resources/views/emails/... */
    public function view(): string
    {
        return match ($this) {
            self::Verification          => 'emails.auth.verification',
            self::PasswordReset         => 'emails.auth.password-reset',
            self::PasswordChanged       => 'emails.auth.password-changed',
            self::EmailChanged          => 'emails.auth.email-changed',
            self::SubscriptionPurchased => 'emails.billing.subscription-purchased',
            self::SubscriptionRenewed   => 'emails.billing.subscription-renewed',
            self::SubscriptionCancelled => 'emails.billing.subscription-cancelled',
            self::PaymentReceived       => 'emails.billing.payment-received',
            self::PaymentFailed         => 'emails.billing.payment-failed',
            self::RefundProcessed       => 'emails.billing.refund-processed',
            self::ApiKeyCreated         => 'emails.api.key-created',
            self::ApiKeyRotated         => 'emails.api.key-rotated',
            self::ApiKeyExpiryReminder  => 'emails.api.key-expiry-reminder',
            self::PlanExpiryReminder    => 'emails.billing.plan-expiry-reminder',
            self::FreeTrialStarted      => 'emails.billing.free-trial-started',
            self::FreeTrialReminder     => 'emails.billing.free-trial-reminder',
            self::WorkspaceInvitation   => 'emails.workspace.invitation',
        };
    }
}
