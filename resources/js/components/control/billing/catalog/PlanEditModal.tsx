import React, { useState } from 'react';
import { useForm } from '@inertiajs/react';
import { X } from 'lucide-react';
import type { CatalogPlan } from '@/types/control/billing';
import { PlanStatusBadge } from './PlanStatusBadge';
import { PricingGrid } from './PricingGrid';

interface PlanEditModalProps {
    plan?: CatalogPlan | null;
    open: boolean;
    onClose: () => void;
    canManage: boolean;
}

export function PlanEditModal({ plan, open, onClose, canManage }: PlanEditModalProps) {
    if (!open) return null;

    const isNew = !plan;
    const title = isNew ? 'Create New Plan' : `Edit ${plan.name}`;

    const { data, setData, post, put, processing, errors, reset } = useForm({
        name: plan?.name ?? '',
        slug: plan?.slug ?? '',
        description: plan?.description ?? '',
        status: plan?.status ?? 'draft',
        visibility: plan?.visibility ?? 'public',
        sort_order: plan?.sort_order ?? 0,
        price_monthly: plan?.price_monthly ?? '0.00',
        price_quarterly: plan?.price_quarterly ?? '0.00',
        price_yearly: plan?.price_yearly ?? '0.00',
        notes: plan?.notes ?? '',
    });

    function handleClose() {
        reset();
        onClose();
    }

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        if (isNew) {
            post('/platform/billing/catalog', { onSuccess: handleClose });
        } else {
            put(`/platform/billing/catalog/${plan!.id}`, { onSuccess: handleClose });
        }
    }

    function Field({ label, id, error, children }: { label: string; id: string; error?: string; children: React.ReactNode }) {
        return (
            <div>
                <label htmlFor={id} className="block text-[10px] font-bold uppercase tracking-wider text-on-background/50 mb-1.5">
                    {label}
                </label>
                {children}
                {error && <p className="mt-1 text-xs text-red-600 font-medium">{error}</p>}
            </div>
        );
    }

    const inputCls = "w-full px-3 py-2 border border-almost-black/20 bg-white text-sm focus:outline-none focus:border-primary transition-colors";
    const selectCls = "w-full px-3 py-2 border border-almost-black/20 bg-white text-sm focus:outline-none focus:border-primary transition-colors cursor-pointer";

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 overflow-y-auto">
            {/* Backdrop */}
            <div className="absolute inset-0 bg-black/60 backdrop-blur-sm" onClick={processing ? undefined : handleClose} />

            {/* Modal */}
            <div className="relative z-10 w-full max-w-4xl bg-background border border-almost-black/20 shadow-2xl my-4">
                {/* Header */}
                <div className="flex items-start justify-between p-6 border-b border-almost-black/10">
                    <div>
                        <div className="flex items-center gap-3">
                            <h2 className="text-xl font-heading font-black tracking-tight text-on-background">{title}</h2>
                            {plan && <PlanStatusBadge status={plan.status} />}
                        </div>
                        <p className="text-sm text-on-background/50 mt-1">
                            {isNew
                                ? 'Set up a new subscription plan. Pricing can be configured after creation.'
                                : 'Update plan metadata. Use the pricing grid to change prices.'}
                        </p>
                    </div>
                    {!processing && (
                        <button onClick={handleClose} className="p-1 hover:bg-almost-black/5 transition-colors">
                            <X className="h-5 w-5 text-on-background/50" />
                        </button>
                    )}
                </div>

                <form onSubmit={handleSubmit}>
                    <div className="p-6">
                        <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
                            {/* Left Column — Plan Information */}
                            <div className="space-y-5">
                                <p className="text-xs font-bold uppercase tracking-wider text-on-background/40 border-b border-almost-black/10 pb-2">
                                    Plan Information
                                </p>

                                <Field label="Plan Name *" id="name" error={errors.name}>
                                    <input
                                        id="name"
                                        type="text"
                                        value={data.name}
                                        onChange={e => setData('name', e.target.value)}
                                        className={inputCls}
                                        placeholder="e.g. Professional"
                                        required
                                    />
                                </Field>

                                <Field label="Slug *" id="slug" error={errors.slug}>
                                    <input
                                        id="slug"
                                        type="text"
                                        value={data.slug}
                                        onChange={e => setData('slug', e.target.value.toLowerCase().replace(/[^a-z0-9-]/g, '-'))}
                                        className={`${inputCls} font-mono`}
                                        placeholder="e.g. professional"
                                        required
                                    />
                                </Field>

                                <Field label="Description" id="description" error={errors.description}>
                                    <textarea
                                        id="description"
                                        value={data.description}
                                        onChange={e => setData('description', e.target.value)}
                                        rows={3}
                                        className={`${inputCls} resize-none`}
                                        placeholder="Brief description of this plan"
                                    />
                                </Field>

                                <div className="grid grid-cols-2 gap-4">
                                    <Field label="Status *" id="status" error={errors.status}>
                                        <select id="status" value={data.status} onChange={e => setData('status', e.target.value as any)} className={selectCls}>
                                            <option value="draft">Draft</option>
                                            <option value="active">Active</option>
                                        </select>
                                    </Field>
                                    <Field label="Visibility *" id="visibility" error={errors.visibility}>
                                        <select id="visibility" value={data.visibility} onChange={e => setData('visibility', e.target.value as any)} className={selectCls}>
                                            <option value="public">Public</option>
                                            <option value="private">Private</option>
                                        </select>
                                    </Field>
                                </div>

                                <Field label="Sort Order" id="sort_order" error={errors.sort_order}>
                                    <input
                                        id="sort_order"
                                        type="number"
                                        min="0"
                                        value={data.sort_order}
                                        onChange={e => setData('sort_order', Number(e.target.value))}
                                        className={`${inputCls} w-24`}
                                    />
                                </Field>

                                <Field label="Internal Notes" id="notes" error={errors.notes}>
                                    <textarea
                                        id="notes"
                                        value={data.notes}
                                        onChange={e => setData('notes', e.target.value)}
                                        rows={2}
                                        className={`${inputCls} resize-none`}
                                        placeholder="Internal admin notes — not visible to customers"
                                    />
                                </Field>
                            </div>

                            {/* Right Column — Billing Configuration (create mode only) */}
                            <div className="space-y-5">
                                <p className="text-xs font-bold uppercase tracking-wider text-on-background/40 border-b border-almost-black/10 pb-2">
                                    {isNew ? 'Initial Pricing' : 'Pricing'}
                                </p>

                                {isNew ? (
                                    <div className="space-y-4">
                                        <p className="text-xs text-on-background/50">
                                            Set initial prices. Any future changes will be tracked in the pricing history.
                                        </p>
                                        {[
                                            { label: 'Monthly Price (₹)', key: 'price_monthly' as const, error: errors.price_monthly },
                                            { label: 'Quarterly Price (₹)', key: 'price_quarterly' as const, error: errors.price_quarterly },
                                            { label: 'Yearly Price (₹)', key: 'price_yearly' as const, error: errors.price_yearly },
                                        ].map(({ label, key, error }) => (
                                            <Field key={key} label={label} id={key} error={error}>
                                                <input
                                                    id={key}
                                                    type="number"
                                                    min="0"
                                                    step="0.01"
                                                    value={data[key]}
                                                    onChange={e => setData(key, e.target.value)}
                                                    className={`${inputCls} font-mono w-40`}
                                                    placeholder="0.00"
                                                />
                                            </Field>
                                        ))}
                                    </div>
                                ) : (
                                    plan && (
                                        <div>
                                            <p className="text-xs text-on-background/50 mb-4">
                                                Use the Pricing Grid on the plan detail page to change prices. Every change is tracked with an audit trail.
                                            </p>
                                            <div className="space-y-2 bg-almost-black/5 p-4">
                                                {[
                                                    { label: 'Monthly', price: plan.price_monthly },
                                                    { label: 'Quarterly', price: plan.price_quarterly },
                                                    { label: 'Yearly', price: plan.price_yearly },
                                                ].map(({ label, price }) => (
                                                    <div key={label} className="flex justify-between items-center text-sm">
                                                        <span className="text-on-background/60 font-medium">{label}</span>
                                                        <span className="font-mono font-bold text-on-background">₹{price}</span>
                                                    </div>
                                                ))}
                                            </div>
                                        </div>
                                    )
                                )}
                            </div>
                        </div>
                    </div>

                    {/* Footer */}
                    <div className="flex items-center justify-end gap-3 px-6 py-4 border-t border-almost-black/10 bg-almost-black/[0.02]">
                        <button
                            type="button"
                            onClick={handleClose}
                            disabled={processing}
                            className="px-5 py-2.5 bg-white border border-almost-black text-sm font-bold uppercase tracking-wider text-on-background hover:bg-almost-black/5 transition-colors disabled:opacity-50"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            disabled={processing}
                            className="flex items-center gap-2 px-5 py-2.5 bg-primary border border-primary text-white text-sm font-bold uppercase tracking-wider hover:bg-primary/90 transition-colors disabled:opacity-50"
                        >
                            {processing && (
                                <svg className="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                    <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                                    <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                                </svg>
                            )}
                            {isNew ? 'Create Plan' : 'Save Changes'}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
}
