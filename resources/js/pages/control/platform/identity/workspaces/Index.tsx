import React from 'react';
import { Head, router } from '@inertiajs/react';
import ControlLayout from '@/layouts/control/ControlLayout';
import { DataTable, ColumnDef, RowAction } from '@/components/control/ui/DataTable';

interface WorkspacesIndexProps {
    workspaces: any;
    filters: any;
}

export default function WorkspacesIndex({ workspaces, filters }: WorkspacesIndexProps) {
    const columns: ColumnDef<any>[] = [
        { key: 'name', header: 'Workspace', sortable: true, render: (row) => (
            <div className="flex flex-col">
                <span className="font-bold">{row.name}</span>
                <span className="text-xs text-on-background/50 font-mono">{row.slug}</span>
            </div>
        ) },
        { key: 'tenant_name', header: 'Tenant', sortable: false, render: (row) => (
            <div className="flex flex-col">
                <span className="font-medium text-primary">{row.tenant_name}</span>
                <span className="text-xs text-on-background/50 font-mono">{row.tenant_slug}</span>
            </div>
        ) },
        { key: 'environment', header: 'Environment', sortable: true, render: (row) => (
            <span className={`px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider border ${
                row.environment === 'production' 
                    ? 'bg-primary/10 text-primary border-primary/20' 
                    : 'bg-almost-black/5 text-on-background/70 border-almost-black/10'
            }`}>
                {row.environment}
            </span>
        ) },
        { key: 'user_count', header: 'Members', sortable: false },
        { key: 'status', header: 'Status', sortable: true, render: (row) => (
            <span className={`px-2 py-0.5 text-xs font-bold uppercase tracking-wider ${row.status === 'active' ? 'bg-green-500/10 text-green-500' : 'bg-destructive/10 text-destructive'}`}>
                {row.status}
            </span>
        ) },
        { key: 'created_at', header: 'Created', sortable: true, render: (row) => <span className="text-on-background/60 text-xs font-mono">{row.created_at}</span> },
    ];

    const actions: RowAction<any>[] = [
        { label: 'View Workspace', onClick: (row) => router.visit(`/control/platform/identity/workspaces/${row.tenant_slug}/${row.slug}`) },
    ];

    return (
        <ControlLayout>
            <Head title="Workspaces | Platform Identity" />
            
            <div className="w-full pb-24 space-y-8">
                <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <h1 className="text-3xl font-heading font-black tracking-tight text-on-background">
                            Workspaces
                        </h1>
                        <p className="text-on-background/60 mt-1">
                            Manage isolated environments within tenants.
                        </p>
                    </div>
                </div>

                <DataTable
                    data={workspaces.data}
                    columns={columns}
                    actions={actions}
                    pagination={{
                        current_page: workspaces.current_page,
                        last_page: workspaces.last_page,
                        per_page: workspaces.per_page,
                        total: workspaces.total,
                        links: workspaces.links,
                    }}
                    currentSort={filters.sort}
                    currentDirection={filters.direction}
                    currentSearch={filters.search}
                    onRowClick={(row) => router.visit(`/control/platform/identity/workspaces/${row.tenant_slug}/${row.slug}`)}
                />
            </div>
        </ControlLayout>
    );
}
