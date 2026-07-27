import React from 'react';
import { Head, router } from '@inertiajs/react';
import ControlLayout from '@/layouts/control/ControlLayout';
import { DataTable, ColumnDef, RowAction } from '@/components/control/ui/DataTable';

interface TenantsIndexProps {
    tenants: any;
    filters: any;
}

export default function TenantsIndex({ tenants, filters }: TenantsIndexProps) {
    const columns: ColumnDef<any>[] = [
        { key: 'name', header: 'Tenant Name', sortable: true, render: (row) => (
            <div className="flex flex-col">
                <span className="font-bold">{row.name}</span>
                <span className="text-xs text-on-background/50 font-mono">{row.slug}</span>
            </div>
        ) },
        { key: 'plan', header: 'Plan', sortable: true, render: (row) => (
            <span className="px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider bg-primary/10 text-primary border border-primary/20">
                {row.plan}
            </span>
        ) },
        { key: 'user_count', header: 'Users', sortable: false },
        { key: 'workspace_count', header: 'Workspaces', sortable: false },
        { key: 'status', header: 'Status', sortable: true, render: (row) => (
            <span className={`px-2 py-0.5 text-xs font-bold uppercase tracking-wider ${row.status === 'active' ? 'bg-green-500/10 text-green-500' : 'bg-destructive/10 text-destructive'}`}>
                {row.status}
            </span>
        ) },
        { key: 'created_at', header: 'Created', sortable: true, render: (row) => <span className="text-on-background/60 text-xs font-mono">{row.created_at}</span> },
    ];

    const actions: RowAction<any>[] = [
        { label: 'View Tenant', onClick: (row) => router.visit(`/platform/tenants/${row.slug}`) },
    ];

    return (
        <ControlLayout>
            <Head title="Tenants | Platform Identity" />
            
            <div className="w-full pb-24 space-y-8">
                <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <h1 className="text-3xl font-heading font-black tracking-tight text-on-background">
                            Tenants
                        </h1>
                        <p className="text-on-background/60 mt-1">
                            Manage organizations, workspaces, and isolation boundaries.
                        </p>
                    </div>
                </div>

                <DataTable
                    data={tenants.data}
                    columns={columns}
                    actions={actions}
                    pagination={{
                        current_page: tenants.current_page,
                        last_page: tenants.last_page,
                        per_page: tenants.per_page,
                        total: tenants.total,
                        links: tenants.links,
                    }}
                    currentSort={filters.sort}
                    currentDirection={filters.direction}
                    currentSearch={filters.search}
                    onRowClick={(row) => router.visit(`/platform/tenants/${row.slug}`)}
                />
            </div>
        </ControlLayout>
    );
}
