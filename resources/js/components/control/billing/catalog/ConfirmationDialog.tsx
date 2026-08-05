import React from 'react';
import { X, AlertTriangle } from 'lucide-react';
import type { CustomerImpact } from '@/types/control/billing';
import { ImpactSummary } from './ImpactSummary';

interface ConfirmationDialogProps {
    open: boolean;
    onClose: () => void;
    onConfirm: () => void;
    title: string;
    description?: string;
    impact?: CustomerImpact | null;
    confirmLabel?: string;
    confirmVariant?: 'primary' | 'danger';
    loading?: boolean;
}

export function ConfirmationDialog({
    open,
    onClose,
    onConfirm,
    title,
    description,
    impact,
    confirmLabel = 'Confirm',
    confirmVariant = 'primary',
    loading = false,
}: ConfirmationDialogProps) {
    if (!open) return null;

    const confirmStyles =
        confirmVariant === 'danger'
            ? 'bg-red-600 border-red-600 text-white hover:bg-red-700 disabled:opacity-50'
            : 'bg-primary border-primary text-white hover:bg-primary/90 disabled:opacity-50';

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
            {/* Backdrop */}
            <div
                className="absolute inset-0 bg-black/60 backdrop-blur-sm"
                onClick={loading ? undefined : onClose}
            />

            {/* Dialog */}
            <div className="relative z-10 w-full max-w-lg bg-background border border-almost-black/20 shadow-2xl">
                {/* Header */}
                <div className="flex items-start justify-between p-6 border-b border-almost-black/10">
                    <div className="flex items-start gap-3">
                        <AlertTriangle className="h-5 w-5 text-amber-500 shrink-0 mt-0.5" />
                        <div>
                            <h2 className="text-base font-heading font-black tracking-tight text-on-background">
                                {title}
                            </h2>
                            {description && (
                                <p className="text-sm text-on-background/60 mt-1">{description}</p>
                            )}
                        </div>
                    </div>
                    {!loading && (
                        <button
                            onClick={onClose}
                            className="p-1 hover:bg-almost-black/5 transition-colors shrink-0"
                        >
                            <X className="h-4 w-4 text-on-background/50" />
                        </button>
                    )}
                </div>

                {/* Impact Body */}
                {impact && (
                    <div className="p-6 border-b border-almost-black/10">
                        <ImpactSummary impact={impact} />
                    </div>
                )}

                {/* Footer */}
                <div className="flex items-center justify-end gap-3 p-6">
                    <button
                        type="button"
                        onClick={onClose}
                        disabled={loading}
                        className="px-5 py-2.5 bg-white border border-almost-black text-sm font-bold uppercase tracking-wider text-on-background hover:bg-almost-black/5 transition-colors disabled:opacity-50"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        onClick={onConfirm}
                        disabled={loading}
                        className={`px-5 py-2.5 border text-sm font-bold uppercase tracking-wider transition-colors flex items-center gap-2 ${confirmStyles}`}
                    >
                        {loading && (
                            <svg className="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                                <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                            </svg>
                        )}
                        {confirmLabel}
                    </button>
                </div>
            </div>
        </div>
    );
}
