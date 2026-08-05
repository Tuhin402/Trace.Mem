import { CreditCard, Ghost, Link as LinkIcon } from 'lucide-react';
import OverviewCard from './OverviewCard';
import { Link } from '@inertiajs/react';
import type { BillingCatalogStats } from '@/types/control/billing';

export default function BillingOverviewSection({ data }: { data: any }) {
    if (data?.error) {
        return (
            <OverviewCard id="overview-billing" title="Billing & Catalog" icon={<CreditCard className="h-5 w-5" />}>
                <div className="flex flex-col items-center justify-center py-8 text-on-background/50">
                    <span className="text-sm font-bold text-destructive uppercase">{data.error}</span>
                </div>
            </OverviewCard>
        );
    }

    // Default stats if empty
    const stats: BillingCatalogStats = {
        active_plans: data?.active_plans ?? 0,
        draft_plans: data?.draft_plans ?? 0,
        archived_plans: data?.archived_plans ?? 0,
        total_subscribers: data?.total_subscribers ?? 0,
        monthly_subscribers: data?.monthly_subscribers ?? 0,
        quarterly_subscribers: data?.quarterly_subscribers ?? 0,
        yearly_subscribers: data?.yearly_subscribers ?? 0,
    };

    const transactions = Array.isArray(data?.recent_transactions) ? data.recent_transactions : [];

    return (
        <OverviewCard
            id="overview-billing"
            title="Billing & Catalog"
            icon={<CreditCard className="h-5 w-5" />}
            viewAllHref="/platform/billing/catalog"
        >
            <div className="space-y-4 mb-6">
                {/* Plan Status */}
                <div className="border border-almost-black/10">
                    <div className="bg-almost-black/5 px-4 py-2 border-b border-almost-black/10 flex justify-between items-center">
                        <span className="text-[10px] font-bold uppercase tracking-wider text-on-background/50">Plan Status</span>
                        <Link href="/platform/billing/catalog" className="text-primary hover:underline">
                            <LinkIcon className="h-3.5 w-3.5" />
                        </Link>
                    </div>
                    <div className="grid grid-cols-3 divide-x divide-almost-black/10">
                        <div className="p-3 flex flex-col items-center text-center">
                            <span className="text-xl font-heading font-black text-on-background">{stats.active_plans}</span>
                            <span className="text-[10px] font-bold uppercase tracking-wider text-emerald-600 mt-1">Active</span>
                        </div>
                        <div className="p-3 flex flex-col items-center text-center">
                            <span className="text-xl font-heading font-black text-on-background/60">{stats.draft_plans}</span>
                            <span className="text-[10px] font-bold uppercase tracking-wider text-on-background/50 mt-1">Draft</span>
                        </div>
                        <div className="p-3 flex flex-col items-center text-center">
                            <span className="text-xl font-heading font-black text-on-background/40">{stats.archived_plans}</span>
                            <span className="text-[10px] font-bold uppercase tracking-wider text-amber-600 mt-1">Archived</span>
                        </div>
                    </div>
                </div>

                {/* Subscribers */}
                <div className="border border-almost-black/10">
                    <div className="bg-almost-black/5 px-4 py-2 border-b border-almost-black/10">
                        <span className="text-[10px] font-bold uppercase tracking-wider text-on-background/50">Subscribers</span>
                    </div>
                    <div className="p-4 flex items-center justify-between border-b border-almost-black/5 bg-primary/[0.02]">
                        <span className="text-sm font-bold text-on-background">Total Active</span>
                        <span className="text-2xl font-heading font-black text-primary">{stats.total_subscribers.toLocaleString()}</span>
                    </div>
                    <div className="grid grid-cols-3 divide-x divide-almost-black/5 text-center">
                        <div className="p-2 flex items-center justify-between px-3 gap-2">
                            <span className="text-[10px] font-bold uppercase tracking-wider text-on-background/50">Monthly</span>
                            <span className="text-sm font-mono font-bold text-on-background">{stats.monthly_subscribers.toLocaleString()}</span>
                        </div>
                        <div className="p-2 flex items-center justify-between px-3 gap-2">
                            <span className="text-[10px] font-bold uppercase tracking-wider text-on-background/50">Quarterly</span>
                            <span className="text-sm font-mono font-bold text-on-background">{stats.quarterly_subscribers.toLocaleString()}</span>
                        </div>
                        <div className="p-2 flex items-center justify-between px-3 gap-2">
                            <span className="text-[10px] font-bold uppercase tracking-wider text-on-background/50">Yearly</span>
                            <span className="text-sm font-mono font-bold text-on-background">{stats.yearly_subscribers.toLocaleString()}</span>
                        </div>
                    </div>
                </div>
            </div>

            <h3 className="text-[10px] font-bold uppercase tracking-wider text-on-background/50 mb-3 border-t border-almost-black/10 pt-4">Recent Transactions</h3>
            
            {transactions.length === 0 ? (
                <div className="flex flex-col items-center justify-center py-6 opacity-60">
                    <Ghost className="h-8 w-8 mb-3 text-primary/40" />
                    <span className="text-xs font-bold uppercase tracking-wider">No Revenue Data</span>
                </div>
            ) : (
                <div className="space-y-0 divide-y divide-almost-black/5 border border-almost-black/5">
                    {transactions.map((tx: any) => (
                        <div key={tx.id} className="p-3 flex items-center justify-between group hover:bg-almost-black/[0.02] transition-colors">
                            <div className="flex items-center gap-3">
                                <div className={`h-8 w-8 flex items-center justify-center border shrink-0 ${tx.status === 'succeeded' ? 'bg-green-500/10 border-green-500/20 text-green-600' : 'bg-destructive/10 border-destructive/20 text-destructive'}`}>
                                    <span className="text-xs font-bold">$</span>
                                </div>
                                <div className="flex flex-col min-w-0">
                                    <span className="text-sm font-bold text-primary group-hover:underline cursor-pointer truncate pr-2">{tx.tenant}</span>
                                    <span className="text-[10px] font-mono text-on-background/50 uppercase tracking-wider">{tx.time}</span>
                                </div>
                            </div>
                            <div className="flex flex-col items-end shrink-0 pl-2">
                                <span className="text-sm font-bold font-mono text-primary">{tx.amount}</span>
                                <span className={`text-[10px] font-bold uppercase tracking-widest ${tx.status === 'succeeded' ? 'text-green-600' : 'text-destructive'}`}>
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
