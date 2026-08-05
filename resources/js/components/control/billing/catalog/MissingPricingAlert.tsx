import { AlertTriangle } from 'lucide-react';
import type { BillingPeriod } from '@/types/control/billing';

const PERIOD_LABELS: Record<BillingPeriod, string> = {
    monthly: 'Monthly',
    quarterly: 'Quarterly',
    yearly: 'Yearly',
};

interface MissingPricingAlertProps {
    periods: BillingPeriod[];
}

export function MissingPricingAlert({ periods }: MissingPricingAlertProps) {
    if (periods.length === 0) return null;

    return (
        <div className="flex items-start gap-2 px-3 py-2 bg-amber-50 border border-amber-200">
            <AlertTriangle className="h-4 w-4 text-amber-500 shrink-0 mt-0.5" />
            <div className="text-xs font-medium text-amber-700">
                Pricing not set:{' '}
                <span className="font-bold">
                    {periods.map(p => PERIOD_LABELS[p]).join(', ')}
                </span>
            </div>
        </div>
    );
}
