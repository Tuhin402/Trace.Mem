import type { PlanStatus } from '@/types/control/billing';

interface PlanStatusBadgeProps {
    status: PlanStatus;
    className?: string;
}

export function PlanStatusBadge({ status, className = '' }: PlanStatusBadgeProps) {
    const styles: Record<PlanStatus, string> = {
        active:   'bg-emerald-500/10 text-emerald-600 border border-emerald-500/20',
        draft:    'bg-zinc-100 text-zinc-500 border border-zinc-200',
        archived: 'bg-amber-50 text-amber-600 border border-amber-200',
    };

    return (
        <span
            className={`inline-flex items-center px-2 py-0.5 text-[10px] font-bold uppercase tracking-widest ${styles[status]} ${className}`}
        >
            {status}
        </span>
    );
}
