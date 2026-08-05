import React, { useState } from 'react';
import { router } from '@inertiajs/react';
import { AlertTriangle, Trash2 } from 'lucide-react';
import type { CatalogPlan } from '@/types/control/billing';
import { ConfirmationDialog } from './ConfirmationDialog';

interface DangerZoneProps {
    plan: CatalogPlan;
    isSuperAdmin: boolean;
}

export function DangerZone({ plan, isSuperAdmin }: DangerZoneProps) {
    const [archiveOpen, setArchiveOpen] = useState(false);
    const [deleteConfirmName, setDeleteConfirmName] = useState('');
    const [deleteLoading, setDeleteLoading] = useState(false);
    const [archiveLoading, setArchiveLoading] = useState(false);
    const [deleteOpen, setDeleteOpen] = useState(false);

    const nameMatches = deleteConfirmName.trim() === plan.name.trim();

    function handleArchive() {
        setArchiveLoading(true);
        router.post(`/platform/billing/catalog/${plan.id}/archive`, {}, {
            onFinish: () => { setArchiveLoading(false); setArchiveOpen(false); },
        });
    }

    function handleDelete() {
        if (!nameMatches) return;
        setDeleteLoading(true);
        router.delete(`/platform/billing/catalog/${plan.id}`, {
            onFinish: () => { setDeleteLoading(false); setDeleteOpen(false); },
        });
    }

    return (
        <div className="border border-red-200 bg-red-50/50 p-6 space-y-4">
            {/* Header */}
            <div className="flex items-center gap-2">
                <AlertTriangle className="h-5 w-5 text-red-500" />
                <h3 className="text-sm font-bold uppercase tracking-wider text-red-700">Danger Zone</h3>
            </div>

            <div className="space-y-4 divide-y divide-red-100">
                {/* Archive */}
                {plan.status !== 'archived' && (
                    <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-4 first:pt-0">
                        <div>
                            <p className="text-sm font-bold text-on-background">Archive this plan</p>
                            <p className="text-xs text-on-background/50 mt-0.5">
                                Stops new subscriptions. Existing subscribers remain unaffected. Reversible.
                            </p>
                        </div>
                        <button
                            onClick={() => setArchiveOpen(true)}
                            className="shrink-0 px-4 py-2 border border-amber-400 text-amber-700 bg-white text-xs font-bold uppercase tracking-wider hover:bg-amber-50 transition-colors"
                        >
                            Archive Plan
                        </button>
                    </div>
                )}

                {/* Physical Delete — only shown if eligible and super_admin */}
                {isSuperAdmin && plan.can_be_deleted && (
                    <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-4">
                        <div>
                            <p className="text-sm font-bold text-red-700">Permanently delete this plan</p>
                            <p className="text-xs text-on-background/50 mt-0.5">
                                Irreversible. Only possible because this plan has never been purchased and has no pricing history.
                            </p>
                        </div>
                        <button
                            onClick={() => setDeleteOpen(true)}
                            className="shrink-0 flex items-center gap-2 px-4 py-2 border border-red-400 bg-red-600 text-white text-xs font-bold uppercase tracking-wider hover:bg-red-700 transition-colors"
                        >
                            <Trash2 className="h-3.5 w-3.5" />
                            Delete Plan
                        </button>
                    </div>
                )}
            </div>

            {/* Archive Dialog */}
            <ConfirmationDialog
                open={archiveOpen}
                onClose={() => setArchiveOpen(false)}
                onConfirm={handleArchive}
                title={`Archive "${plan.name}"?`}
                description="The plan will be hidden from new purchases. Existing subscribers are unaffected. You can restore it at any time."
                confirmLabel="Archive Plan"
                confirmVariant="danger"
                loading={archiveLoading}
            />

            {/* Delete Dialog (manual name confirmation) */}
            {deleteOpen && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div className="absolute inset-0 bg-black/60 backdrop-blur-sm" onClick={() => !deleteLoading && setDeleteOpen(false)} />
                    <div className="relative z-10 w-full max-w-md bg-background border border-red-300 shadow-2xl">
                        <div className="p-6 border-b border-red-100">
                            <div className="flex items-center gap-2 mb-2">
                                <Trash2 className="h-5 w-5 text-red-600" />
                                <h2 className="text-base font-heading font-black text-red-700">Permanently Delete Plan</h2>
                            </div>
                            <p className="text-sm text-on-background/60">
                                This action is irreversible. Type <strong>{plan.name}</strong> to confirm.
                            </p>
                        </div>
                        <div className="p-6 space-y-4">
                            <input
                                type="text"
                                value={deleteConfirmName}
                                onChange={e => setDeleteConfirmName(e.target.value)}
                                placeholder={plan.name}
                                className="w-full px-3 py-2 border border-almost-black/20 text-sm focus:outline-none focus:border-red-400 transition-colors"
                            />
                            <div className="flex items-center justify-end gap-3">
                                <button
                                    onClick={() => { setDeleteOpen(false); setDeleteConfirmName(''); }}
                                    disabled={deleteLoading}
                                    className="px-4 py-2 bg-white border border-almost-black text-xs font-bold uppercase tracking-wider hover:bg-almost-black/5 transition-colors disabled:opacity-50"
                                >
                                    Cancel
                                </button>
                                <button
                                    onClick={handleDelete}
                                    disabled={!nameMatches || deleteLoading}
                                    className="flex items-center gap-2 px-4 py-2 bg-red-600 border border-red-600 text-white text-xs font-bold uppercase tracking-wider hover:bg-red-700 transition-colors disabled:opacity-40 disabled:cursor-not-allowed"
                                >
                                    {deleteLoading && (
                                        <svg className="animate-spin h-3.5 w-3.5" fill="none" viewBox="0 0 24 24">
                                            <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                                            <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                                        </svg>
                                    )}
                                    Delete Permanently
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
}
