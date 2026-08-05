import type { SubscriberCounts } from '@/types/control/billing';

interface SubscriberBreakdownCardProps {
    counts: SubscriberCounts;
}

export function SubscriberBreakdownCard({ counts }: SubscriberBreakdownCardProps) {
    return (
        <div className="p-4 bg-almost-black/5 flex flex-col gap-2">
            <span className="text-[10px] font-bold uppercase tracking-wider text-on-background/50">
                Subscribers
            </span>
            <div className="flex items-baseline gap-1">
                <span className="text-2xl font-heading font-black text-on-background">
                    {counts.total.toLocaleString()}
                </span>
                <span className="text-xs text-on-background/50 font-medium">total</span>
            </div>
            <div className="space-y-1 pt-1 border-t border-almost-black/10">
                <BreakdownRow label="Monthly" value={counts.monthly} />
                <BreakdownRow label="Quarterly" value={counts.quarterly} />
                <BreakdownRow label="Yearly" value={counts.yearly} />
            </div>
        </div>
    );
}

function BreakdownRow({ label, value }: { label: string; value: number }) {
    return (
        <div className="flex items-center justify-between">
            <span className="text-xs text-on-background/60 font-medium">{label}</span>
            <span className="text-xs font-bold font-mono text-on-background tabular-nums">
                {value.toLocaleString()}
            </span>
        </div>
    );
}
