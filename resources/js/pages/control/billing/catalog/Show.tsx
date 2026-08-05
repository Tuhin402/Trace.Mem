import React, { useState } from 'react';
import { router } from '@inertiajs/react';
import ControlEntityLayout from '@/layouts/control/ControlEntityLayout';
import type { CatalogPlan, CatalogAuditEntry } from '@/types/control/billing';
import { PlanStatusBadge } from '@/components/control/billing/catalog/PlanStatusBadge';
import { PricingGrid } from '@/components/control/billing/catalog/PricingGrid';
import { PricingVersionTimeline } from '@/components/control/billing/catalog/PricingVersionTimeline';
import { SubscriberBreakdownCard } from '@/components/control/billing/catalog/SubscriberBreakdownCard';
import { DangerZone } from '@/components/control/billing/catalog/DangerZone';
import { PlanEditModal } from '@/components/control/billing/catalog/PlanEditModal';
import { Edit, Archive, RotateCcw } from 'lucide-react';
import { ConfirmationDialog } from '@/components/control/billing/catalog/ConfirmationDialog';

interface ShowProps {
    plan: CatalogPlan;
    can_manage: boolean;
    is_super_admin: boolean;
    audit_logs?: CatalogAuditEntry[]; // Optional: injected via separate API or passed down
}

export default function Show({ plan, can_manage, is_super_admin, audit_logs = [] }: ShowProps) {
    const [currentTab, setCurrentTab] = useState('overview');
    const [editModalOpen, setEditModalOpen] = useState(false);
    const [archiveOpen, setArchiveOpen] = useState(false);
    const [restoreOpen, setRestoreOpen] = useState(false);
    const [loading, setLoading] = useState(false);

    const breadcrumbs = [
        { label: 'Billing', url: '/platform/billing/catalog' },
        { label: 'Catalog', url: '/platform/billing/catalog' },
        { label: plan.name }
    ];

    const badges = [
        <PlanStatusBadge key="status" status={plan.status} />,
        plan.visibility === 'private' ? (
            <span key="visibility" className="px-2 py-0.5 text-[10px] font-bold uppercase tracking-widest bg-zinc-100 text-zinc-600 border border-zinc-200">
                PRIVATE
            </span>
        ) : null
    ].filter(Boolean);

    const actions = can_manage ? [
        {
            label: 'Edit Plan',
            icon: <Edit className="h-4 w-4" />,
            onClick: () => setEditModalOpen(true)
        }
    ] : [];

    const tabs = [
        { label: 'Overview', id: 'overview' },
        { label: 'Pricing History', id: 'history' },
        { label: 'Subscribers', id: 'subscribers' },
        { label: 'Audit Log', id: 'audit' }
    ];

    function handleArchive() {
        setLoading(true);
        router.post(`/platform/billing/catalog/${plan.id}/archive`, {}, {
            onFinish: () => { setLoading(false); setArchiveOpen(false); },
        });
    }

    function handleRestore() {
        setLoading(true);
        router.post(`/platform/billing/catalog/${plan.id}/restore`, {}, {
            onFinish: () => { setLoading(false); setRestoreOpen(false); },
        });
    }

    return (
        <ControlEntityLayout
            title={plan.name}
            breadcrumbs={breadcrumbs}
            badges={badges}
            actions={actions}
            tabs={tabs}
            currentTab={currentTab}
            onTabChange={setCurrentTab}
        >
            <div className="space-y-8 pb-12">
                {currentTab === 'overview' && (
                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        {/* Left: Plan Info */}
                        <div className="lg:col-span-1 space-y-6">
                            <div className="bg-surface border border-almost-black/10 p-5">
                                <h3 className="text-xs font-bold uppercase tracking-wider text-on-background/50 border-b border-almost-black/10 pb-2 mb-4">
                                    Plan Information
                                </h3>
                                <div className="space-y-4">
                                    <div>
                                        <p className="text-[10px] font-bold uppercase tracking-wider text-on-background/40">Slug</p>
                                        <p className="text-sm font-mono text-on-background mt-0.5">{plan.slug}</p>
                                    </div>
                                    <div>
                                        <p className="text-[10px] font-bold uppercase tracking-wider text-on-background/40">Description</p>
                                        <p className="text-sm text-on-background mt-0.5 whitespace-pre-wrap">{plan.description || 'No description provided.'}</p>
                                    </div>
                                    <div>
                                        <p className="text-[10px] font-bold uppercase tracking-wider text-on-background/40">Sort Order</p>
                                        <p className="text-sm text-on-background mt-0.5">{plan.sort_order}</p>
                                    </div>
                                    {plan.notes && (
                                        <div>
                                            <p className="text-[10px] font-bold uppercase tracking-wider text-on-background/40">Internal Notes</p>
                                            <p className="text-sm text-on-background mt-0.5 whitespace-pre-wrap italic">{plan.notes}</p>
                                        </div>
                                    )}
                                </div>
                            </div>

                            <div className="bg-surface border border-almost-black/10 p-5">
                                <h3 className="text-xs font-bold uppercase tracking-wider text-on-background/50 border-b border-almost-black/10 pb-2 mb-4">
                                    Included Features
                                </h3>
                                {plan.features && plan.features.length > 0 ? (
                                    <ul className="space-y-2">
                                        {plan.features.map(f => (
                                            <li key={f.id} className="text-sm text-on-background flex items-start gap-2">
                                                <span className="text-primary mt-0.5">•</span>
                                                <div>
                                                    <span className="font-medium">{f.name}</span>
                                                    {f.description && <p className="text-xs text-on-background/50">{f.description}</p>}
                                                </div>
                                            </li>
                                        ))}
                                    </ul>
                                ) : (
                                    <p className="text-sm text-on-background/50 italic">No features defined.</p>
                                )}
                            </div>
                        </div>

                        {/* Right: Pricing Grid & Actions */}
                        <div className="lg:col-span-2 space-y-6">
                            <div className="bg-surface border border-almost-black/10 p-5">
                                <h3 className="text-xs font-bold uppercase tracking-wider text-on-background/50 border-b border-almost-black/10 pb-2 mb-4">
                                    Billing Configuration
                                </h3>
                                <PricingGrid plan={plan} canManage={can_manage} />
                            </div>

                            {/* State actions for Active/Archived outside danger zone */}
                            <div className="bg-surface border border-almost-black/10 p-5">
                                <h3 className="text-xs font-bold uppercase tracking-wider text-on-background/50 border-b border-almost-black/10 pb-2 mb-4">
                                    Lifecycle
                                </h3>
                                <div className="flex items-center gap-4">
                                    {plan.status === 'active' && can_manage && (
                                        <button onClick={() => setArchiveOpen(true)} className="flex items-center gap-2 px-4 py-2 bg-white border border-almost-black/20 text-xs font-bold uppercase tracking-wider text-amber-600 hover:border-amber-400 hover:bg-amber-50 transition-colors">
                                            <Archive className="h-4 w-4" />
                                            Archive Plan
                                        </button>
                                    )}
                                    {plan.status === 'archived' && can_manage && (
                                        <button onClick={() => setRestoreOpen(true)} className="flex items-center gap-2 px-4 py-2 bg-white border border-emerald-400 text-xs font-bold uppercase tracking-wider text-emerald-600 hover:bg-emerald-50 transition-colors">
                                            <RotateCcw className="h-4 w-4" />
                                            Restore Plan
                                        </button>
                                    )}
                                    <span className="text-xs text-on-background/40 font-medium">
                                        Status: <span className="font-bold text-on-background/60">{plan.status.toUpperCase()}</span>
                                    </span>
                                </div>
                            </div>

                            {is_super_admin && (
                                <DangerZone plan={plan} isSuperAdmin={is_super_admin} />
                            )}
                        </div>
                    </div>
                )}

                {currentTab === 'history' && (
                    <div className="bg-surface border border-almost-black/10 p-6">
                        <PricingVersionTimeline histories={plan.pricing_histories || []} />
                    </div>
                )}

                {currentTab === 'subscribers' && (
                    <div className="max-w-md">
                        <SubscriberBreakdownCard counts={plan.subscriber_counts} />
                    </div>
                )}

                {currentTab === 'audit' && (
                    <div className="bg-surface border border-almost-black/10 overflow-x-auto p-6 text-center text-sm text-on-background/50 italic">
                        <table className="w-full text-left text-sm whitespace-nowrap">
                            <thead className="bg-almost-black/5 border-b border-almost-black/10 text-[10px] font-bold uppercase tracking-wider text-on-background/50">
                                <tr>
                                    <th className="px-4 py-3 font-medium">Action</th>
                                    <th className="px-4 py-3 font-medium">Performed By</th>
                                    <th className="px-4 py-3 font-medium">Date</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-almost-black/5">
                                {plan.audit_logs && plan.audit_logs.length > 0 ? (
                                    plan.audit_logs.map((log) => (
                                        <tr key={log.id} className="hover:bg-almost-black/[0.02]">
                                            <td className="px-4 py-3 font-mono text-xs">{log.action}</td>
                                            <td className="px-4 py-3">{log.performed_by}</td>
                                            <td className="px-4 py-3 text-on-background/60">{new Date(log.created_at).toLocaleString()}</td>
                                        </tr>
                                    ))
                                ) : (
                                    <tr>
                                        <td colSpan={3} className="px-4 py-8 text-center text-on-background/50 italic">
                                            No audit logs found for this plan.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>

            <PlanEditModal
                plan={plan}
                open={editModalOpen}
                onClose={() => setEditModalOpen(false)}
                canManage={can_manage}
            />

            <ConfirmationDialog
                open={archiveOpen}
                onClose={() => setArchiveOpen(false)}
                onConfirm={handleArchive}
                title="Archive Plan"
                description="This will hide the plan from new subscriptions. Existing users are unaffected."
                confirmLabel="Archive"
                confirmVariant="danger"
                loading={loading}
            />

            <ConfirmationDialog
                open={restoreOpen}
                onClose={() => setRestoreOpen(false)}
                onConfirm={handleRestore}
                title="Restore Plan"
                description="This will make the plan available for new subscriptions again."
                confirmLabel="Restore"
                confirmVariant="primary"
                loading={loading}
            />
        </ControlEntityLayout>
    );
}
