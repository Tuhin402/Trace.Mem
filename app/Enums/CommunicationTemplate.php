<?php

namespace App\Enums;

enum CommunicationTemplate: string
{
    case Welcome            = 'welcome';
    case ThankYou           = 'thank_you';
    case MaintenanceNotice  = 'maintenance_notice';
    case AccountReminder    = 'account_reminder';
    case SubscriptionReminder = 'subscription_reminder';
    case BillingReminder    = 'billing_reminder';
    case PricingChangeNotice = 'pricing_change_notice';
    case VerificationReminder = 'verification_reminder';
    case SecurityAdvisory   = 'security_advisory';
    case GeneralAnnouncement = 'general_announcement';
    case CustomMessage      = 'custom_message';

    public function label(): string
    {
        return match ($this) {
            self::Welcome => 'Welcome',
            self::ThankYou => 'Thank You',
            self::MaintenanceNotice => 'Maintenance Notice',
            self::AccountReminder => 'Account Reminder',
            self::SubscriptionReminder => 'Subscription Reminder',
            self::BillingReminder => 'Billing Reminder',
            self::PricingChangeNotice => 'Pricing Update Notice',
            self::VerificationReminder => 'Verification Reminder',
            self::SecurityAdvisory => 'Security Advisory',
            self::GeneralAnnouncement => 'General Announcement',
            self::CustomMessage => 'Custom Message',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Welcome => 'Send a personalized welcome message to a new user or tenant.',
            self::ThankYou => 'Express gratitude for using our platform or completing an action.',
            self::MaintenanceNotice => 'Notify users about upcoming scheduled maintenance.',
            self::AccountReminder => 'Send a generic reminder regarding their account.',
            self::SubscriptionReminder => 'Remind users about an upcoming subscription renewal or expiration.',
            self::BillingReminder => 'Remind users about a pending or failed payment.',
            self::PricingChangeNotice => 'Notify subscribers about a future catalog price change.',
            self::VerificationReminder => 'Remind users to verify their email address.',
            self::SecurityAdvisory => 'Alert users about a critical security update or incident.',
            self::GeneralAnnouncement => 'Send a general platform-wide announcement.',
            self::CustomMessage => 'Draft a completely custom email message from scratch.',
        };
    }

    public function defaultSubject(): string
    {
        return match ($this) {
            self::Welcome => 'Welcome to TraceMem!',
            self::ThankYou => 'Thank you for choosing TraceMem',
            self::MaintenanceNotice => 'Scheduled Maintenance Notice - TraceMem',
            self::AccountReminder => 'Action Required: Update your TraceMem Account',
            self::SubscriptionReminder => 'Important: Your TraceMem Subscription',
            self::BillingReminder => 'Action Required: Update your billing information',
            self::PricingChangeNotice => 'Important update to your TraceMem subscription pricing',
            self::VerificationReminder => 'Reminder: Please verify your TraceMem email address',
            self::SecurityAdvisory => 'Security Advisory - Please read',
            self::GeneralAnnouncement => 'TraceMem Update',
            self::CustomMessage => '',
        };
    }

    public function defaultBody(): string
    {
        return match ($this) {
            self::Welcome => "Hi there,\n\nWelcome to TraceMem! We're excited to have you on board. If you have any questions, feel free to reply to this email.\n\nBest regards,\nThe TraceMem Team",
            self::ThankYou => "Hi there,\n\nThank you for your continued support of TraceMem. We really appreciate having you with us.\n\nBest regards,\nThe TraceMem Team",
            self::MaintenanceNotice => "Hello,\n\nPlease be advised that TraceMem will be undergoing scheduled maintenance soon. During this time, the platform may be temporarily unavailable. We apologize for any inconvenience.\n\nBest regards,\nThe TraceMem Team",
            self::AccountReminder => "Hello,\n\nWe noticed your account requires some attention. Please log in to review your current settings.\n\nBest regards,\nThe TraceMem Team",
            self::SubscriptionReminder => "Hello,\n\nThis is a friendly reminder regarding your TraceMem subscription. Please ensure your payment methods are up to date.\n\nBest regards,\nThe TraceMem Team",
            self::BillingReminder => "Hello,\n\nWe were unable to process your recent payment. Please update your billing details to avoid service interruption.\n\nBest regards,\nThe TraceMem Team",
            self::PricingChangeNotice => "Hello,\n\nWe are writing to let you know about an upcoming TraceMem subscription pricing update. Your current subscription remains grandfathered at its existing price unless we contact you about a future migration.\n\nWe are making this change to support continued investment in TraceMem. If you have questions, please reply to this email.\n\nBest regards,\nThe TraceMem Team",
            self::VerificationReminder => "Hello,\n\nWe noticed you haven't verified your email address yet. Please log in and complete the verification process.\n\nBest regards,\nThe TraceMem Team",
            self::SecurityAdvisory => "Hello,\n\nWe are reaching out to inform you of a recent security update. Please review your account activity to ensure everything is secure.\n\nBest regards,\nThe TraceMem Team",
            self::GeneralAnnouncement => "Hello,\n\nWe have some exciting news to share regarding the TraceMem platform.\n\nBest regards,\nThe TraceMem Team",
            self::CustomMessage => "",
        };
    }

    public function category(): string
    {
        return match ($this) {
            self::Welcome, self::ThankYou, self::GeneralAnnouncement, self::CustomMessage => 'General',
            self::MaintenanceNotice, self::SecurityAdvisory => 'System',
            self::AccountReminder, self::VerificationReminder => 'Account',
            self::SubscriptionReminder, self::BillingReminder, self::PricingChangeNotice => 'Billing',
        };
    }
}
