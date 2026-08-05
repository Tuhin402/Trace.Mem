import React, { useState } from 'react';
import { Link, router } from '@inertiajs/react';
import { Eye, Archive, RotateCcw, MoreHorizontal } from 'lucide-react';
import type { CatalogPlan } from '@/types/control/billing';
import { PlanStatusBadge } from './PlanStatusBadge';
import { MissingPricingAlert } from './MissingPricingAlert';
import { ConfirmationDialog } from './ConfirmationDialog';

interface PlanCardProps {
    plan: CatalogPlan;
    canManage: boolean;
}

export function PlanCard({ plan, canManage }: PlanCardProps) {
    const [archiveOpen, setArchiveOpen] = useState(false);
    const [restoreOpen, setRestoreOpen] = useState(false);
    const [loading, setLoading] = useState(false);

    function handleArchive() {
        setLoading(true);
        router.post(
            `/platform/billing/catalog/${plan.id}/archive`,
            {},
            {
                onFinish: () => { setLoading(false); setArchiveOpen(false); },
            }
        );
    }

    function handleRestore() {
        setLoading(true);
        router.post(
            `/platform/billing/catalog/${plan.id}/restore`,
            {},
            {
                onFinish: () => { setLoading(false); setRestoreOpen(false); },
            }
        );
    }

    return (
        <div className="flex flex-col h-full bg-surface border border-almost-black/10 hover:border-almost-black/20 transition-colors group">
            {/* Card Header */}
            <div className="flex items-start justify-between p-5 border-b border-almost-black/10">
                <div className="flex flex-col gap-1.5 min-w-0">
                    <h3 className="text-base font-heading font-black tracking-tight text-on-background truncate pr-2">
                        {plan.name}
                    </h3>
                    {plan.description && (
                        <p className="text-xs text-on-background/50 line-clamp-2 leading-relaxed">
                            {plan.description}
                        </p>
                    )}
                </div>
                <PlanStatusBadge status={plan.status} className="shrink-0 ml-2 mt-0.5" />
            </div>

            {/* Pricing + Subscribers */}
            <div className="flex-1 p-5 space-y-3">
                <p className="text-[10px] font-bold uppercase tracking-wider text-on-background/40">Pricing</p>
                <div className="space-y-2">
                    {[
                        { label: 'Monthly', price: plan.price_monthly, count: plan.subscriber_counts.monthly },
                        { label: 'Quarterly', price: plan.price_quarterly, count: plan.subscriber_counts.quarterly },
                        { label: 'Yearly', price: plan.price_yearly, count: plan.subscriber_counts.yearly },
                    ].map(({ label, price, count }) => {
                        const isMissing = parseFloat(price) === 0;
                        return (
                            <div key={label} className="flex items-center justify-between">
                                <span className="text-xs font-medium text-on-background/60 w-20">{label}</span>
                                <span className={`text-xs font-mono font-bold flex-1 ${isMissing ? 'text-amber-500' : 'text-on-background'}`}>
                                    {isMissing ? '⚠ Not set' : `₹${price}`}
                                </span>
                                <span className="text-xs font-mono text-on-background/40 tabular-nums">
                                    {count.toLocaleString()} sub{count !== 1 ? 's' : ''}
                                </span>
                            </div>
                        );
                    })}
                </div>

                {/* Missing pricing alert */}
                {plan.missing_pricings.length > 0 && (
                    <MissingPricingAlert periods={plan.missing_pricings} />
                )}
            </div>

            {/* Card Footer */}
            <div className="flex items-center justify-between p-4 pt-0 border-t border-almost-black/10 mt-auto gap-2 flex-wrap">
                <span className="text-[10px] text-on-background/30 font-medium">
                    Updated {new Date(plan.updated_at).toLocaleDateString()}
                </span>

                <div className="flex items-center gap-2">
                    <Link
                        href={`/platform/billing/catalog/${plan.id}`}
                        className="flex items-center gap-1.5 px-3 py-1.5 border border-almost-black/20 bg-white text-xs font-bold uppercase tracking-wider text-on-background hover:border-primary hover:text-primary transition-colors"
                    >
                        <Eye className="h-3.5 w-3.5" />
                        View
                    </Link>

                    {canManage && plan.status === 'active' && (
                        <button
                            onClick={() => setArchiveOpen(true)}
                            className="flex items-center gap-1.5 px-3 py-1.5 border border-almost-black/20 bg-white text-xs font-bold uppercase tracking-wider text-on-background/60 hover:border-amber-400 hover:text-amber-600 transition-colors"
                        >
                            <Archive className="h-3.5 w-3.5" />
                            Archive
                        </button>
                    )}

                    {canManage && plan.status === 'archived' && (
                        <button
                            onClick={() => setRestoreOpen(true)}
                            className="flex items-center gap-1.5 px-3 py-1.5 border border-emerald-400 bg-white text-xs font-bold uppercase tracking-wider text-emerald-600 hover:bg-emerald-50 transition-colors"
                        >
                            <RotateCcw className="h-3.5 w-3.5" />
                            Restore
                        </button>
                    )}
                </div>
            </div>

            {/* Archive Confirmation */}
            <ConfirmationDialog
                open={archiveOpen}
                onClose={() => setArchiveOpen(false)}
                onConfirm={handleArchive}
                title={`Archive "${plan.name}"?`}
                description="Archived plans cannot be purchased by new subscribers. Existing subscribers remain unaffected and continue with full access."
                confirmLabel="Archive Plan"
                confirmVariant="danger"
                loading={loading}
            />

            {/* Restore Confirmation */}
            <ConfirmationDialog
                open={restoreOpen}
                onClose={() => setRestoreOpen(false)}
                onConfirm={handleRestore}
                title={`Restore "${plan.name}"?`}
                description="The plan will be available for new subscriptions again."
                confirmLabel="Restore Plan"
                confirmVariant="primary"
                loading={loading}
            />
        </div>
    );
}
