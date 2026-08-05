import React, { useState } from 'react';
import { router } from '@inertiajs/react';
import ControlEntityLayout from '@/layouts/control/ControlEntityLayout';
import type { CatalogPlan, BillingCatalogStats } from '@/types/control/billing';
import { PlanCard } from '@/components/control/billing/catalog/PlanCard';
import { PlanEditModal } from '@/components/control/billing/catalog/PlanEditModal';
import { Plus } from 'lucide-react';

interface IndexProps {
    plans: CatalogPlan[];
    stats: BillingCatalogStats;
    filters: { status: string };
    can_manage: boolean;
}

export default function Index({ plans, stats, filters, can_manage }: IndexProps) {
    const [createModalOpen, setCreateModalOpen] = useState(false);
    const [currentFilter, setCurrentFilter] = useState(filters.status || 'all');

    const handleFilterChange = (status: string) => {
        setCurrentFilter(status);
        router.get('/platform/billing/catalog', { status }, { preserveState: true });
    };

    const breadcrumbs = [
        { label: 'Billing', url: '/platform/billing/catalog' },
        { label: 'Catalog' }
    ];

    const actions = can_manage ? [
        {
            label: 'Create Plan',
            icon: <Plus className="h-4 w-4" />,
            primary: true,
            onClick: () => setCreateModalOpen(true)
        }
    ] : [];

    return (
        <ControlEntityLayout
            title="Billing Catalog"
            breadcrumbs={breadcrumbs}
            actions={actions}
        >
            <div className="space-y-6">
                {/* Stats Row */}
                <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div className="p-4 bg-surface border border-almost-black/10">
                        <p className="text-[10px] font-bold uppercase tracking-wider text-on-background/50 mb-1">Active Plans</p>
                        <p className="text-2xl font-heading font-black text-on-background">{stats.active_plans}</p>
                    </div>
                    <div className="p-4 bg-surface border border-almost-black/10">
                        <p className="text-[10px] font-bold uppercase tracking-wider text-on-background/50 mb-1">Draft Plans</p>
                        <p className="text-2xl font-heading font-black text-on-background">{stats.draft_plans}</p>
                    </div>
                    <div className="p-4 bg-surface border border-almost-black/10">
                        <p className="text-[10px] font-bold uppercase tracking-wider text-on-background/50 mb-1">Archived Plans</p>
                        <p className="text-2xl font-heading font-black text-on-background">{stats.archived_plans}</p>
                    </div>
                    <div className="p-4 bg-surface border border-almost-black/10 bg-primary/5 border-primary/20">
                        <p className="text-[10px] font-bold uppercase tracking-wider text-primary mb-1">Total Subscribers</p>
                        <p className="text-2xl font-heading font-black text-primary">{stats.total_subscribers.toLocaleString()}</p>
                    </div>
                </div>

                {/* Filter Tabs */}
                <div className="flex items-center gap-1 border-b border-almost-black/10">
                    {['all', 'active', 'draft', 'archived'].map((status) => (
                        <button
                            key={status}
                            onClick={() => handleFilterChange(status)}
                            className={`px-4 py-2.5 text-sm font-bold uppercase tracking-wider transition-colors border-b-2 -mb-px ${
                                currentFilter === status
                                    ? 'border-primary text-primary'
                                    : 'border-transparent text-on-background/60 hover:text-on-background hover:border-almost-black/20'
                            }`}
                        >
                            {status}
                        </button>
                    ))}
                </div>

                {/* Plan Grid */}
                {plans.length === 0 ? (
                    <div className="py-16 text-center bg-surface border border-almost-black/10">
                        <p className="text-on-background/50 font-medium">No plans found matching the current filter.</p>
                        {can_manage && (
                            <button
                                onClick={() => setCreateModalOpen(true)}
                                className="mt-4 px-4 py-2 bg-primary text-white text-xs font-bold uppercase tracking-wider hover:bg-primary/90 transition-colors"
                            >
                                Create Your First Plan
                            </button>
                        )}
                    </div>
                ) : (
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 auto-rows-[1fr]">
                        {plans.map((plan) => (
                            <PlanCard key={plan.id} plan={plan} canManage={can_manage} />
                        ))}
                    </div>
                )}
            </div>

            <PlanEditModal
                open={createModalOpen}
                onClose={() => setCreateModalOpen(false)}
                canManage={can_manage}
            />
        </ControlEntityLayout>
    );
}
