import { ArrowRight } from 'lucide-react';
import type { PricingHistory } from '@/types/control/billing';

const PERIOD_LABELS: Record<string, string> = {
    monthly: 'Monthly',
    quarterly: 'Quarterly',
    yearly: 'Yearly',
};

interface PricingVersionTimelineProps {
    histories: PricingHistory[];
}

export function PricingVersionTimeline({ histories }: PricingVersionTimelineProps) {
    if (histories.length === 0) {
        return (
            <div className="py-12 text-center">
                <p className="text-sm text-on-background/40 font-medium">No pricing changes recorded.</p>
            </div>
        );
    }

    return (
        <div className="space-y-0 overflow-y-auto max-h-[600px] no-scrollbar">
            {histories.map((entry, idx) => (
                <div
                    key={entry.id}
                    className="relative flex gap-4 pb-6 last:pb-0"
                >
                    {/* Timeline line */}
                    {idx < histories.length - 1 && (
                        <div className="absolute left-[11px] top-5 bottom-0 w-px bg-almost-black/10" />
                    )}

                    {/* Dot */}
                    <div className="relative shrink-0 mt-1">
                        <div className="h-[22px] w-[22px] border-2 border-primary bg-background flex items-center justify-center">
                            <div className="h-2 w-2 bg-primary" />
                        </div>
                    </div>

                    {/* Content */}
                    <div className="flex-1 min-w-0 bg-surface border border-almost-black/10 p-4">
                        <div className="flex flex-wrap items-center gap-3 mb-2">
                            <span className="text-[10px] font-bold uppercase tracking-widest text-primary bg-primary/10 px-2 py-0.5">
                                {PERIOD_LABELS[entry.period] ?? entry.period}
                            </span>
                            <div className="flex items-center gap-2 font-mono text-sm">
                                <span className="line-through text-on-background/40">₹{entry.old_amount}</span>
                                <ArrowRight className="h-3.5 w-3.5 text-on-background/40" />
                                <span className="font-bold text-on-background">₹{entry.new_amount}</span>
                            </div>
                        </div>
                        <div className="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-on-background/50">
                            {entry.changed_by && (
                                <span>By <span className="font-medium text-on-background/70">{entry.changed_by}</span></span>
                            )}
                            <span>{new Date(entry.created_at).toLocaleString()}</span>
                        </div>
                        {entry.reason && (
                            <p className="mt-2 text-xs text-on-background/60 italic border-t border-almost-black/5 pt-2">
                                "{entry.reason}"
                            </p>
                        )}
                    </div>
                </div>
            ))}
        </div>
    );
}
