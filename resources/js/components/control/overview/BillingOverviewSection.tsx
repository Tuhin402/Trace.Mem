import { CreditCard, ArrowDownRight, ArrowUpRight, Clock } from 'lucide-react';
import OverviewCard from './OverviewCard';

export default function BillingOverviewSection() {
    const invoices = [
        { id: 'INV-2026-042', amount: '$499.00', status: 'paid', customer: 'Acme Corp', date: 'Today' },
        { id: 'INV-2026-041', amount: '$120.00', status: 'paid', customer: 'Globex Inc', date: 'Yesterday' },
        { id: 'INV-2026-040', amount: '$2,450.00', status: 'failed', customer: 'Soylent', date: '2 days ago' },
        { id: 'INV-2026-039', amount: '$99.00', status: 'paid', customer: 'Initech', date: '3 days ago' },
    ];

    return (
        <OverviewCard
            id="overview-billing"
            title="Billing & Revenue"
            icon={<CreditCard className="h-5 w-5" />}
            viewAllHref="/platform/billing"
        >
            <div className="grid grid-cols-2 gap-4 mb-6">
                <div className="p-4 border border-almost-black/10 bg-almost-black/5 flex flex-col">
                    <span className="text-xs font-bold uppercase tracking-wider text-on-background/60 mb-2">MRR</span>
                    <div className="flex items-baseline gap-2">
                        <span className="text-2xl font-bold font-heading text-on-background">$42.8k</span>
                        <span className="flex items-center text-xs font-mono text-green-500">
                            <ArrowUpRight className="h-3 w-3" /> 12%
                        </span>
                    </div>
                </div>
                <div className="p-4 border border-almost-black/10 bg-almost-black/5 flex flex-col">
                    <span className="text-xs font-bold uppercase tracking-wider text-on-background/60 mb-2">Failed Payments</span>
                    <div className="flex items-baseline gap-2">
                        <span className="text-2xl font-bold font-heading text-destructive">$2.4k</span>
                        <span className="flex items-center text-xs font-mono text-destructive">
                            <ArrowDownRight className="h-3 w-3" /> 3%
                        </span>
                    </div>
                </div>
            </div>

            <h4 className="text-xs font-bold uppercase tracking-wider text-on-background/50 mb-3 border-b border-almost-black/10 pb-2">
                Recent Invoices
            </h4>
            <div className="space-y-3">
                {invoices.map(invoice => (
                    <div key={invoice.id} className="flex flex-col sm:flex-row sm:items-center justify-between p-3 border border-almost-black/10 hover:border-primary/30 transition-colors group">
                        <div className="flex flex-col">
                            <span className="text-sm font-bold text-on-background group-hover:text-primary transition-colors">{invoice.customer}</span>
                            <span className="text-xs font-mono text-on-background/60">{invoice.id}</span>
                        </div>
                        <div className="flex items-center gap-4 mt-2 sm:mt-0">
                            <span className="text-xs font-mono text-on-background/50 flex items-center gap-1">
                                <Clock className="h-3 w-3" /> {invoice.date}
                            </span>
                            <div className="flex items-center gap-3 min-w-[100px] justify-end">
                                <span className="font-mono font-bold text-sm">{invoice.amount}</span>
                                <span className={`h-2 w-2 rounded-full border shrink-0 ${
                                    invoice.status === 'paid' ? 'bg-green-500/20 border-green-500' : 'bg-destructive/20 border-destructive'
                                }`} title={invoice.status} />
                            </div>
                        </div>
                    </div>
                ))}
            </div>
        </OverviewCard>
    );
}
