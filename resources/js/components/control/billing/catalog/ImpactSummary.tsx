import type { CustomerImpact } from '@/types/control/billing';
import { Users, ArrowRight, Mail, Lock } from 'lucide-react';

const PERIOD_LABELS: Record<string, string> = {
    monthly: 'Monthly',
    quarterly: 'Quarterly',
    yearly: 'Yearly',
};

interface ImpactSummaryProps {
    impact: CustomerImpact;
}

export function ImpactSummary({ impact }: ImpactSummaryProps) {
    return (
        <div className="space-y-4">
            {/* Price Change Row */}
            <div className="p-4 bg-almost-black/5 border border-almost-black/10">
                <p className="text-[10px] font-bold uppercase tracking-wider text-on-background/50 mb-3">
                    Price Change — {PERIOD_LABELS[impact.period] ?? impact.period}
                </p>
                <div className="flex items-center gap-4">
                    <div className="text-center">
                        <p className="text-[10px] uppercase tracking-wider text-on-background/40 mb-1">Current</p>
                        <p className="text-xl font-heading font-black line-through text-on-background/40">
                            ₹{impact.current_price}
                        </p>
                    </div>
                    <ArrowRight className="h-5 w-5 text-primary shrink-0" />
                    <div className="text-center">
                        <p className="text-[10px] uppercase tracking-wider text-on-background/40 mb-1">New</p>
                        <p className="text-xl font-heading font-black text-primary">
                            ₹{impact.new_price}
                        </p>
                    </div>
                </div>
            </div>

            {/* Subscriber Impact */}
            <div className="flex items-start gap-3 p-4 border border-almost-black/10">
                <Users className="h-5 w-5 text-on-background/40 mt-0.5 shrink-0" />
                <div>
                    <p className="text-sm font-bold text-on-background">
                        {impact.affected_count.toLocaleString()} Active Subscriber
                        {impact.affected_count !== 1 ? 's' : ''} — {PERIOD_LABELS[impact.period] ?? impact.period}
                    </p>
                    <p className="text-xs text-on-background/50 mt-1 leading-relaxed">
                        {impact.grandfathering_note}
                    </p>
                </div>
            </div>

            {/* Notification status */}
            <div className={`flex items-start gap-3 p-4 border ${impact.will_notify ? 'border-primary/20 bg-primary/5' : 'border-almost-black/10'}`}>
                {impact.will_notify ? (
                    <Mail className="h-5 w-5 text-primary mt-0.5 shrink-0" />
                ) : (
                    <Lock className="h-5 w-5 text-on-background/30 mt-0.5 shrink-0" />
                )}
                <div>
                    <p className="text-sm font-bold text-on-background">
                        {impact.will_notify
                            ? `Email notification will be queued for ${impact.affected_count.toLocaleString()} subscriber${impact.affected_count !== 1 ? 's' : ''}`
                            : 'No email notification will be sent'}
                    </p>
                    {impact.will_notify && (
                        <p className="text-xs text-on-background/50 mt-1">
                            Dispatched via TraceMem branded email on the billing queue.
                        </p>
                    )}
                </div>
            </div>
        </div>
    );
}
