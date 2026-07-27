import React from 'react';
import { Head, router } from '@inertiajs/react';
import ControlLayout from '@/layouts/control/ControlLayout';
import ControlEntityLayout from '@/layouts/control/ControlEntityLayout';
import { Settings, Ban, LogOut } from 'lucide-react';

export default function TenantProfile({ tenant }: { tenant: any }) {
    const breadcrumbs = [
        { label: 'Identity' },
        { label: 'Tenants', url: '/platform/tenants' },
        { label: tenant.name }
    ];

    const badges = [
        <span key="status" className={`px-2 py-0.5 text-xs font-bold uppercase tracking-wider ${tenant.status === 'active' ? 'bg-green-500/10 text-green-500' : 'bg-destructive/10 text-destructive'}`}>
            {tenant.status}
        </span>,
        <span key="plan" className="px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider bg-primary/10 text-primary border border-primary/20">
            {tenant.plan}
        </span>
    ];

    const actions = [
        { label: 'Edit Tenant', icon: <Settings className="h-4 w-4" />, onClick: () => alert('Edit tenant coming soon') },
        { label: 'Suspend All', icon: <Ban className="h-4 w-4" />, destructive: true, onClick: () => alert('Suspend tenant coming soon') },
    ];

    const [currentTab, setCurrentTab] = React.useState('overview');

    const tabs = [
        { label: 'Overview', id: 'overview' },
        { label: 'Workspaces', id: 'workspaces' },
        { label: 'Users', id: 'users' },
        { label: 'Billing', id: 'billing' }
    ];

    return (
        <ControlLayout>
            <ControlEntityLayout
                title={tenant.name}
                breadcrumbs={breadcrumbs}
                badges={badges}
                actions={actions}
                tabs={tabs}
                currentTab={currentTab}
                onTabChange={setCurrentTab}
            >
                {currentTab === 'overview' && (
                    <div id="overview" className="space-y-6 animate-in fade-in slide-in-from-bottom-2 duration-300">
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                            
                            {/* Summary */}
                            <div className="p-6 bg-surface border border-almost-black/10 flex flex-col gap-4">
                                <h3 className="text-xs font-bold uppercase tracking-wider text-on-background/50">Details</h3>
                                <div className="flex flex-col gap-1 mt-2">
                                    <span className="text-[10px] font-bold uppercase tracking-wider text-on-background/50">Slug</span>
                                    <span className="text-sm font-mono text-on-background">{tenant.slug}</span>
                                </div>
                                <div className="flex flex-col gap-1 mt-2">
                                    <span className="text-[10px] font-bold uppercase tracking-wider text-on-background/50">Tenant ID (UUID)</span>
                                    <span className="text-xs font-mono text-on-background/80 bg-almost-black/5 p-1">{tenant.id}</span>
                                </div>
                                <div className="flex flex-col gap-1 mt-2">
                                    <span className="text-[10px] font-bold uppercase tracking-wider text-on-background/50">Created</span>
                                    <span className="text-sm font-mono text-on-background">{tenant.created_at}</span>
                                </div>
                            </div>

                            {/* Metrics */}
                            <div className="p-6 bg-surface border border-almost-black/10 flex flex-col gap-4">
                                <h3 className="text-xs font-bold uppercase tracking-wider text-on-background/50">Usage Metrics</h3>
                                <div className="grid grid-cols-2 gap-4 h-full content-center">
                                    <div className="flex flex-col items-center">
                                        <span className="text-4xl font-black font-heading text-primary">{tenant.metrics.users}</span>
                                        <span className="text-[10px] font-bold uppercase tracking-wider text-on-background/50 mt-1">Total Users</span>
                                    </div>
                                    <div className="flex flex-col items-center">
                                        <span className="text-4xl font-black font-heading text-primary">{tenant.metrics.workspaces}</span>
                                        <span className="text-[10px] font-bold uppercase tracking-wider text-on-background/50 mt-1">Workspaces</span>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                )}

                {currentTab === 'workspaces' && (
                    <div id="workspaces" className="space-y-6 animate-in fade-in slide-in-from-bottom-2 duration-300">
                        {/* Workspaces List */}
                        <div className="w-full bg-surface border border-almost-black/10">
                            <div className="px-6 py-4 border-b border-almost-black/10 flex items-center justify-between">
                                <h3 className="text-sm font-bold uppercase tracking-wider text-on-background">Workspaces</h3>
                                <button className="text-xs font-bold uppercase tracking-wider text-primary hover:underline">Create Workspace</button>
                            </div>
                            <div className="divide-y divide-almost-black/5">
                                {tenant.workspaces.map((ws: any) => (
                                    <div key={ws.id} className="p-4 flex items-center justify-between group">
                                        <div className="flex flex-col">
                                            <a href={`/platform/workspaces/${tenant.slug}/${ws.slug}`} className="text-sm font-bold text-on-background group-hover:text-primary transition-colors">
                                                {ws.name}
                                            </a>
                                            <span className="text-xs text-on-background/50 font-mono">{ws.slug}</span>
                                        </div>
                                        <div className="flex items-center gap-3">
                                            <span className="text-xs text-on-background/50 font-mono">{ws.user_count} members</span>
                                            <span className={`px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider ${ws.status === 'active' ? 'bg-green-500/10 text-green-500' : 'bg-almost-black/5 text-on-background/70'}`}>
                                                {ws.status}
                                            </span>
                                        </div>
                                    </div>
                                ))}
                                {tenant.workspaces.length === 0 && (
                                    <div className="p-8 text-center text-on-background/50 text-sm">
                                        No workspaces found.
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>
                )}

                {currentTab === 'users' && (
                    <div id="users" className="space-y-6 animate-in fade-in slide-in-from-bottom-2 duration-300">
                        {/* Users List */}
                        <div className="w-full bg-surface border border-almost-black/10">
                            <div className="px-6 py-4 border-b border-almost-black/10 flex items-center justify-between">
                                <h3 className="text-sm font-bold uppercase tracking-wider text-on-background">Users</h3>
                                <button className="text-xs font-bold uppercase tracking-wider text-primary hover:underline">Invite User</button>
                            </div>
                            <div className="divide-y divide-almost-black/5">
                                {tenant.recent_users.map((user: any) => (
                                    <div key={user.id} className="p-4 flex items-center justify-between group">
                                        <div className="flex flex-col">
                                            <a href={`/platform/users/${user.uuid}`} className="text-sm font-bold text-on-background group-hover:text-primary transition-colors">
                                                {user.name}
                                            </a>
                                            <span className="text-xs text-on-background/50 font-mono">{user.email}</span>
                                        </div>
                                    </div>
                                ))}
                                {tenant.recent_users.length === 0 && (
                                    <div className="p-8 text-center text-on-background/50 text-sm">
                                        No users found.
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>
                )}

                {currentTab === 'billing' && (
                    <div id="billing" className="space-y-6 animate-in fade-in slide-in-from-bottom-2 duration-300">
                        <div className="w-full bg-surface border border-almost-black/10 p-8 text-center flex flex-col items-center justify-center text-on-background/50 gap-2">
                            <h3 className="text-sm font-bold uppercase tracking-wider text-on-background/70">Billing Information</h3>
                            <p className="text-xs">Invoices, payment methods, and subscription details will appear here.</p>
                        </div>
                    </div>
                )}
            </ControlEntityLayout>
        </ControlLayout>
    );
}
