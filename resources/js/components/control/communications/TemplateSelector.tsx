import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectGroup, SelectItem, SelectLabel, SelectTrigger, SelectValue } from '@/components/ui/select';

export const EMAIL_TEMPLATES = [
    { value: 'welcome', label: 'Welcome', category: 'General', defaultSubject: 'Welcome to TraceMem!', defaultBody: "Hi there,\n\nWelcome to TraceMem! We're excited to have you on board. If you have any questions, feel free to reply to this email.\n\nBest regards,\nThe TraceMem Team" },
    { value: 'thank_you', label: 'Thank You', category: 'General', defaultSubject: 'Thank you for choosing TraceMem', defaultBody: "Hi there,\n\nThank you for your continued support of TraceMem. We really appreciate having you with us.\n\nBest regards,\nThe TraceMem Team" },
    { value: 'general_announcement', label: 'General Announcement', category: 'General', defaultSubject: 'TraceMem Update', defaultBody: "Hello,\n\nWe have some exciting news to share regarding the TraceMem platform.\n\nBest regards,\nThe TraceMem Team" },
    { value: 'custom_message', label: 'Custom Message', category: 'General', defaultSubject: '', defaultBody: '' },
    
    { value: 'account_reminder', label: 'Account Reminder', category: 'Account', defaultSubject: 'Action Required: Update your TraceMem Account', defaultBody: "Hello,\n\nWe noticed your account requires some attention. Please log in to review your current settings.\n\nBest regards,\nThe TraceMem Team" },
    { value: 'verification_reminder', label: 'Verification Reminder', category: 'Account', defaultSubject: 'Reminder: Please verify your TraceMem email address', defaultBody: "Hello,\n\nWe noticed you haven't verified your email address yet. Please log in and complete the verification process.\n\nBest regards,\nThe TraceMem Team" },
    
    { value: 'subscription_reminder', label: 'Subscription Reminder', category: 'Billing', defaultSubject: 'Important: Your TraceMem Subscription', defaultBody: "Hello,\n\nThis is a friendly reminder regarding your TraceMem subscription. Please ensure your payment methods are up to date.\n\nBest regards,\nThe TraceMem Team" },
    { value: 'billing_reminder', label: 'Billing Reminder', category: 'Billing', defaultSubject: 'Action Required: Update your billing information', defaultBody: "Hello,\n\nWe were unable to process your recent payment. Please update your billing details to avoid service interruption.\n\nBest regards,\nThe TraceMem Team" },
    
    { value: 'maintenance_notice', label: 'Maintenance Notice', category: 'System', defaultSubject: 'Scheduled Maintenance Notice - TraceMem', defaultBody: "Hello,\n\nPlease be advised that TraceMem will be undergoing scheduled maintenance soon. During this time, the platform may be temporarily unavailable. We apologize for any inconvenience.\n\nBest regards,\nThe TraceMem Team" },
    { value: 'security_advisory', label: 'Security Advisory', category: 'System', defaultSubject: 'Security Advisory - Please read', defaultBody: "Hello,\n\nWe are reaching out to inform you of a recent security update. Please review your account activity to ensure everything is secure.\n\nBest regards,\nThe TraceMem Team" },
];

interface TemplateSelectorProps {
    value: string;
    onValueChange: (value: string, defaultSubject: string, defaultBody: string) => void;
}

export function TemplateSelector({ value, onValueChange }: TemplateSelectorProps) {
    const categories = Array.from(new Set(EMAIL_TEMPLATES.map(t => t.category)));

    const handleChange = (val: string) => {
        const template = EMAIL_TEMPLATES.find(t => t.value === val);
        if (template) {
            onValueChange(val, template.defaultSubject, template.defaultBody);
        }
    };

    return (
        <div className="space-y-2">
            <Label>Email Template</Label>
            <Select value={value} onValueChange={handleChange}>
                <SelectTrigger className="text-black">
                    <SelectValue placeholder="Select a template..." />
                </SelectTrigger>
                <SelectContent>
                    {categories.map(category => (
                        <SelectGroup key={category}>
                            <SelectLabel>{category}</SelectLabel>
                            {EMAIL_TEMPLATES.filter(t => t.category === category).map(template => (
                                <SelectItem key={template.value} value={template.value}>
                                    {template.label}
                                </SelectItem>
                            ))}
                        </SelectGroup>
                    ))}
                </SelectContent>
            </Select>
        </div>
    );
}
