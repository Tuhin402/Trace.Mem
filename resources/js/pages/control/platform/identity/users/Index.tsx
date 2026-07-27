import React from 'react';
import { Head, router } from '@inertiajs/react';
import ControlLayout from '@/layouts/control/ControlLayout';
import { DataTable, ColumnDef, RowAction } from '@/components/control/ui/DataTable';

interface UsersIndexProps {
    users: any;
    filters: any;
}

export default function UsersIndex({ users, filters }: UsersIndexProps) {
    const columns: ColumnDef<any>[] = [
        { key: 'name', header: 'Name', sortable: true, render: (row) => <span className="font-bold">{row.name}</span> },
        { key: 'email', header: 'Email', sortable: true },
        { key: 'tenant_name', header: 'Tenant', sortable: false, render: (row) => (
            <div className="flex flex-col">
                <span className="font-medium text-primary">{row.tenant_name}</span>
                <span className="text-xs text-on-background/50 font-mono">{row.tenant_slug}</span>
            </div>
        ) },
        { key: 'workspace_count', header: 'Workspaces', sortable: false },
        { key: 'status', header: 'Status', sortable: false, render: (row) => (
            <span className={`px-2 py-0.5 text-xs font-bold uppercase tracking-wider ${row.status === 'active' ? 'bg-green-500/10 text-green-500' : 'bg-destructive/10 text-destructive'}`}>
                {row.status}
            </span>
        ) },
        { key: 'last_active_at', header: 'Last Active', sortable: true, render: (row) => <span className="text-on-background/60 text-xs font-mono">{row.last_active_at}</span> },
    ];

    const actions: RowAction<any>[] = [
        { label: 'View Profile', onClick: (row) => router.visit(`/control/platform/identity/users/${row.uuid}`) },
    ];

    return (
        <ControlLayout>
            <Head title="Users | Platform Identity" />
            
            <div className="w-full pb-24 space-y-8">
                <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <h1 className="text-3xl font-heading font-black tracking-tight text-on-background">
                            Users
                        </h1>
                        <p className="text-on-background/60 mt-1">
                            Manage all user identities across the platform.
                        </p>
                    </div>
                </div>

                <DataTable
                    data={users.data}
                    columns={columns}
                    actions={actions}
                    pagination={{
                        current_page: users.current_page,
                        last_page: users.last_page,
                        per_page: users.per_page,
                        total: users.total,
                        links: users.links,
                    }}
                    currentSort={filters.sort}
                    currentDirection={filters.direction}
                    currentSearch={filters.search}
                    onRowClick={(row) => router.visit(`/control/platform/identity/users/${row.uuid}`)}
                />
            </div>
        </ControlLayout>
    );
}
