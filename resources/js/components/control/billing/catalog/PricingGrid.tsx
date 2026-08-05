import React, { useState } from 'react';
import { router } from '@inertiajs/react';
import type { CatalogPlan, BillingPeriod, CustomerImpact } from '@/types/control/billing';
import { ImpactSummary } from './ImpactSummary';
import { ConfirmationDialog } from './ConfirmationDialog';
import { MissingPricingAlert } from './MissingPricingAlert';

const PERIOD_LABELS: Record<BillingPeriod, string> = {
    monthly: 'Monthly',
    quarterly: 'Quarterly',
    yearly: 'Yearly',
};

const PERIODS: BillingPeriod[] = ['monthly', 'quarterly', 'yearly'];

const PRICE_KEYS: Record<BillingPeriod, keyof CatalogPlan> = {
    monthly: 'price_monthly',
    quarterly: 'price_quarterly',
    yearly: 'price_yearly',
};

interface PricingGridProps {
    plan: CatalogPlan;
    canManage: boolean;
}

interface EditState {
    period: BillingPeriod;
    newAmount: string;
    reason: string;
    notifySubscribers: boolean;
}

export function PricingGrid({ plan, canManage }: PricingGridProps) {
    const [editState, setEditState] = useState<EditState | null>(null);
    const [impact, setImpact] = useState<CustomerImpact | null>(null);
    const [loadingImpact, setLoadingImpact] = useState(false);
    const [confirmOpen, setConfirmOpen] = useState(false);
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState<string | null>(null);

    function openEdit(period: BillingPeriod) {
        setEditState({
            period,
            newAmount: plan[PRICE_KEYS[period]] as string,
            reason: '',
            notifySubscribers: false,
        });
        setImpact(null);
        setError(null);
    }

    function closeEdit() {
        setEditState(null);
        setImpact(null);
        setError(null);
        setConfirmOpen(false);
    }

    async function fetchImpact() {
        if (!editState) return;
        setLoadingImpact(true);
        setError(null);
        try {
            const params = new URLSearchParams({
                plan_id: String(plan.id),
                period: editState.period,
                new_amount: editState.newAmount,
            });
            const res = await fetch(`/platform/billing/catalog/impact?${params}`, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (!res.ok) throw new Error('Failed to load impact');
            const data = await res.json();
            setImpact(data);
            setConfirmOpen(true);
        } catch {
            setError('Failed to calculate impact. Please try again.');
        } finally {
            setLoadingImpact(false);
        }
    }

    function confirmSave() {
        if (!editState) return;
        setSaving(true);
        router.post(
            '/platform/billing/catalog/pricing',
            {
                plan_id: plan.id,
                period: editState.period,
                new_amount: editState.newAmount,
                reason: editState.reason || null,
                notify_subscribers: editState.notifySubscribers,
            },
            {
                onFinish: () => { setSaving(false); setConfirmOpen(false); closeEdit(); },
                onError: (errors) => {
                    setSaving(false);
                    setError(Object.values(errors).flat().join(' '));
                },
            }
        );
    }

    return (
        <div>
            <div className="border border-almost-black/10 overflow-hidden">
                {/* Header */}
                <div className="grid grid-cols-4 bg-almost-black/5 px-4 py-2">
                    <span className="text-[10px] font-bold uppercase tracking-wider text-on-background/50">Period</span>
                    <span className="text-[10px] font-bold uppercase tracking-wider text-on-background/50">Price</span>
                    <span className="text-[10px] font-bold uppercase tracking-wider text-on-background/50">Subscribers</span>
                    {canManage && <span className="text-[10px] font-bold uppercase tracking-wider text-on-background/50 text-right">Action</span>}
                </div>

                {/* Rows */}
                {PERIODS.map((period) => {
                    const price = plan[PRICE_KEYS[period]] as string;
                    const isMissing = parseFloat(price) === 0;
                    const count = plan.subscriber_counts[period];
                    const isEditing = editState?.period === period;

                    return (
                        <div key={period} className="border-t border-almost-black/10">
                            {/* Row */}
                            <div className={`grid grid-cols-4 items-center px-4 py-3 ${isMissing ? 'bg-amber-50/50' : 'hover:bg-almost-black/[0.02]'}`}>
                                <span className="text-sm font-medium text-on-background">{PERIOD_LABELS[period]}</span>
                                <span className={`text-sm font-mono font-bold ${isMissing ? 'text-amber-600' : 'text-on-background'}`}>
                                    {isMissing ? '⚠ Not set' : `₹${price}`}
                                </span>
                                <span className="text-sm font-mono text-on-background/70 tabular-nums">
                                    {count.toLocaleString()}
                                </span>
                                {canManage && (
                                    <div className="text-right">
                                        <button
                                            onClick={() => openEdit(period)}
                                            className={`text-xs font-bold uppercase tracking-wider px-3 py-1.5 border transition-colors ${
                                                isMissing
                                                    ? 'bg-amber-500 border-amber-500 text-white hover:bg-amber-600'
                                                    : 'bg-surface border-almost-black/20 text-on-background hover:border-primary hover:text-primary'
                                            }`}
                                        >
                                            {isMissing ? 'Set Price' : 'Edit'}
                                        </button>
                                    </div>
                                )}
                            </div>

                            {/* Inline edit sheet */}
                            {isEditing && editState && (
                                <div className="border-t border-primary/20 bg-primary/[0.03] p-4 space-y-4">
                                    <p className="text-xs font-bold uppercase tracking-wider text-primary">
                                        Editing {PERIOD_LABELS[period]} Price
                                    </p>

                                    {error && (
                                        <div className="text-xs text-red-600 font-medium bg-red-50 border border-red-200 px-3 py-2">
                                            {error}
                                        </div>
                                    )}

                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label className="block text-[10px] font-bold uppercase tracking-wider text-on-background/50 mb-1">
                                                New Price (₹)
                                            </label>
                                            <input
                                                type="number"
                                                min="0"
                                                step="0.01"
                                                value={editState.newAmount}
                                                onChange={e => setEditState(prev => prev ? { ...prev, newAmount: e.target.value } : null)}
                                                className="w-full px-3 py-2 border border-almost-black/20 bg-white text-sm font-mono focus:outline-none focus:border-primary transition-colors"
                                            />
                                        </div>
                                        <div>
                                            <label className="block text-[10px] font-bold uppercase tracking-wider text-on-background/50 mb-1">
                                                Reason (optional)
                                            </label>
                                            <input
                                                type="text"
                                                value={editState.reason}
                                                onChange={e => setEditState(prev => prev ? { ...prev, reason: e.target.value } : null)}
                                                placeholder="e.g. Annual pricing revision"
                                                className="w-full px-3 py-2 border border-almost-black/20 bg-white text-sm focus:outline-none focus:border-primary transition-colors"
                                            />
                                        </div>
                                    </div>

                                    <div className="flex items-center gap-2">
                                        <input
                                            type="checkbox"
                                            id={`notify-${period}`}
                                            checked={editState.notifySubscribers}
                                            onChange={e => setEditState(prev => prev ? { ...prev, notifySubscribers: e.target.checked } : null)}
                                            className="h-4 w-4 border-almost-black/30 accent-primary"
                                        />
                                        <label htmlFor={`notify-${period}`} className="text-xs font-medium text-on-background/70 cursor-pointer select-none">
                                            Send email notification to active subscribers
                                        </label>
                                    </div>

                                    <div className="flex items-center gap-3 pt-1">
                                        <button
                                            onClick={fetchImpact}
                                            disabled={loadingImpact || !editState.newAmount}
                                            className="px-4 py-2 bg-primary border border-primary text-white text-xs font-bold uppercase tracking-wider hover:bg-primary/90 transition-colors disabled:opacity-50 flex items-center gap-2"
                                        >
                                            {loadingImpact && (
                                                <svg className="animate-spin h-3.5 w-3.5" fill="none" viewBox="0 0 24 24">
                                                    <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                                                    <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                                                </svg>
                                            )}
                                            Preview Impact & Save
                                        </button>
                                        <button
                                            onClick={closeEdit}
                                            className="px-4 py-2 bg-white border border-almost-black text-xs font-bold uppercase tracking-wider text-on-background hover:bg-almost-black/5 transition-colors"
                                        >
                                            Cancel
                                        </button>
                                    </div>
                                </div>
                            )}
                        </div>
                    );
                })}
            </div>

            {/* Missing pricing alert */}
            {plan.missing_pricings.length > 0 && (
                <div className="mt-3">
                    <MissingPricingAlert periods={plan.missing_pricings} />
                </div>
            )}

            {/* Confirmation Dialog */}
            <ConfirmationDialog
                open={confirmOpen}
                onClose={() => setConfirmOpen(false)}
                onConfirm={confirmSave}
                title="Confirm Price Change"
                description="Review the impact below before saving. Existing subscribers will be grandfathered on their current pricing."
                impact={impact}
                confirmLabel="Save Price Change"
                confirmVariant="primary"
                loading={saving}
            />
        </div>
    );
}
