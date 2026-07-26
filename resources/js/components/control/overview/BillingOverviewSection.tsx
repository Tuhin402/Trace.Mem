import { CreditCard, Ghost } from 'lucide-react';
import OverviewCard from './OverviewCard';
import { Link } from '@inertiajs/react';

export default function BillingOverviewSection({ data }: { data: any }) {
    if (data?.error) {
        return (
            <OverviewCard id="overview-billing" title="Billing Snapshot" icon={<CreditCard className="h-5 w-5" />}>
                <div className="flex flex-col items-center justify-center py-8 text-on-background/50">
                    <span className="text-sm font-bold text-destructive uppercase">{data.error}</span>
                </div>
            </OverviewCard>
        );
    }

    const transactions = Array.isArray(data) ? data : [];

    return (
        <OverviewCard
            id="overview-billing"
            title="Billing Snapshot"
            icon={<CreditCard className="h-5 w-5" />}
            viewAllHref="/operations/billing"
        >
            <div className="grid grid-cols-2 gap-4 mb-6">
                <div className="p-4 bg-almost-black/5 flex flex-col">
                    <span className="text-[10px] font-bold uppercase tracking-wider text-on-background/50 mb-1">MRR (Estimated)</span>
                    <span className="text-2xl font-heading font-bold text-primary">--</span>
                </div>
                <div className="p-4 bg-almost-black/5 flex flex-col">
                    <span className="text-[10px] font-bold uppercase tracking-wider text-on-background/50 mb-1">Active Trials</span>
                    <span className="text-2xl font-heading font-bold text-primary">--</span>
                </div>
            </div>

            <h3 className="text-xs font-bold uppercase tracking-wider text-on-background/50 mb-4">Recent Transactions</h3>
            
            {transactions.length === 0 ? (
                <div className="flex flex-col items-center justify-center py-6 opacity-60">
                    <Ghost className="h-8 w-8 mb-3 text-primary/40" />
                    <span className="text-xs font-bold uppercase tracking-wider">No Revenue Data</span>
                </div>
            ) : (
                <div className="space-y-0 divide-y divide-almost-black/5">
                    {transactions.map(tx => (
                        <div key={tx.id} className="py-3 flex items-center justify-between group">
                            <div className="flex items-center gap-3">
                                <div className={`h-8 w-8 rounded-full flex items-center justify-center border ${tx.status === 'succeeded' ? 'bg-green-500/10 border-green-500/20 text-green-600' : 'bg-destructive/10 border-destructive/20 text-destructive'}`}>
                                    <span className="text-xs font-bold">$</span>
                                </div>
                                <div className="flex flex-col">
                                    <span className="text-sm font-bold text-primary group-hover:underline cursor-pointer">{tx.tenant}</span>
                                    <span className="text-xs font-mono text-on-background/50">{tx.time}</span>
                                </div>
                            </div>
                            <div className="flex flex-col items-end">
                                <span className="text-sm font-bold font-mono text-primary">{tx.amount}</span>
                                <span className={`text-[10px] font-bold uppercase ${tx.status === 'succeeded' ? 'text-green-600' : 'text-destructive'}`}>
                                    {tx.status}
                                </span>
                            </div>
                        </div>
                    ))}
                </div>
            )}
        </OverviewCard>
    );
}
