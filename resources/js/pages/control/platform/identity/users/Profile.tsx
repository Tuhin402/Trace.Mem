import React from 'react';
import { Head } from '@inertiajs/react';
import ControlLayout from '@/layouts/control/ControlLayout';
import ControlEntityLayout from '@/layouts/control/ControlEntityLayout';
import { Mail, Ban, KeySquare, LogOut, CheckCircle2 } from 'lucide-react';

export default function UserProfile({ user }: { user: any }) {
    const breadcrumbs = [
        { label: 'Identity' },
        { label: 'Users', url: '/platform/users' },
        { label: user.name }
    ];

    const badges = [
        <span key="status" className={`px-2 py-0.5 text-xs font-bold uppercase tracking-wider ${user.status === 'active' ? 'bg-green-500/10 text-green-500' : 'bg-destructive/10 text-destructive'}`}>
            {user.status}
        </span>,
        user.is_verified && (
            <span key="verified" className="flex items-center gap-1 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider bg-blue-500/10 text-blue-500 border border-blue-500/20">
                <CheckCircle2 className="h-3 w-3" /> Verified
            </span>
        )
    ];

    const actions = [
        { label: 'Email User', icon: <Mail className="h-4 w-4" />, onClick: () => alert('Email functionality coming soon') },
        { label: 'Reset Sessions', icon: <LogOut className="h-4 w-4" />, onClick: () => alert('Session reset coming soon') },
        { label: 'Suspend', icon: <Ban className="h-4 w-4" />, destructive: true, onClick: () => alert('Suspend user coming soon') },
    ];

    const tabs = [
        { label: 'Overview', id: 'overview', isActive: true },
        { label: 'Security', id: 'security' },
        { label: 'Activity', id: 'activity' },
        { label: 'Communications', id: 'communications' }
    ];

    return (
        <ControlLayout>
            <ControlEntityLayout
                title={user.name}
                breadcrumbs={breadcrumbs}
                badges={badges}
                actions={actions}
                tabs={tabs}
            >
                {/* Overview Tab Content */}
                <div id="overview" className="space-y-6">
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                        
                        {/* Identity Card */}
                        <div className="p-6 bg-surface border border-almost-black/10 flex flex-col gap-4">
                            <h3 className="text-xs font-bold uppercase tracking-wider text-on-background/50">Identity</h3>
                            <div className="flex flex-col gap-1">
                                <span className="text-sm font-bold text-on-background">{user.name}</span>
                                <span className="text-sm text-on-background/70">{user.email}</span>
                            </div>
                            <div className="flex flex-col gap-1 mt-2">
                                <span className="text-[10px] font-bold uppercase tracking-wider text-on-background/50">UUID</span>
                                <span className="text-xs font-mono text-on-background/80 bg-almost-black/5 p-1">{user.uuid}</span>
                            </div>
                        </div>

                        {/* Tenant Relationship */}
                        <div className="p-6 bg-surface border border-almost-black/10 flex flex-col gap-4">
                            <h3 className="text-xs font-bold uppercase tracking-wider text-on-background/50">Organization</h3>
                            <div className="flex flex-col gap-1">
                                <a href={`/platform/tenants/${user.tenant.slug}`} className="text-sm font-bold text-primary hover:underline">
                                    {user.tenant.name}
                                </a>
                                <span className="text-xs text-on-background/50 font-mono">Tenant ID: {user.tenant.id}</span>
                            </div>
                            <div className="flex flex-col gap-1 mt-2">
                                <span className="text-[10px] font-bold uppercase tracking-wider text-on-background/50">Subscription</span>
                                <span className="text-sm font-bold text-on-background">{user.subscription.plan}</span>
                            </div>
                        </div>

                        {/* Metrics Summary */}
                        <div className="p-6 bg-surface border border-almost-black/10 flex flex-col gap-4">
                            <h3 className="text-xs font-bold uppercase tracking-wider text-on-background/50">Usage Metrics</h3>
                            <div className="grid grid-cols-2 gap-4">
                                <div className="flex flex-col">
                                    <span className="text-2xl font-black font-heading text-primary">{user.metrics.workspaces}</span>
                                    <span className="text-[10px] font-bold uppercase tracking-wider text-on-background/50">Workspaces</span>
                                </div>
                                <div className="flex flex-col">
                                    <span className="text-2xl font-black font-heading text-primary">{user.metrics.api_keys}</span>
                                    <span className="text-[10px] font-bold uppercase tracking-wider text-on-background/50">API Keys</span>
                                </div>
                                <div className="flex flex-col">
                                    <span className="text-2xl font-black font-heading text-primary">{user.metrics.memories}</span>
                                    <span className="text-[10px] font-bold uppercase tracking-wider text-on-background/50">Memories</span>
                                </div>
                            </div>
                        </div>

                    </div>

                    {/* Workspaces List (Subset) */}
                    <div className="w-full bg-surface border border-almost-black/10">
                        <div className="px-6 py-4 border-b border-almost-black/10">
                            <h3 className="text-sm font-bold uppercase tracking-wider text-on-background">Workspace Memberships</h3>
                        </div>
                        <div className="divide-y divide-almost-black/5">
                            {user.workspaces.map((ws: any) => (
                                <div key={ws.id} className="p-4 flex items-center justify-between group">
                                    <div className="flex flex-col">
                                        <a href={`/platform/workspaces/${user.tenant.slug}/${ws.slug}`} className="text-sm font-bold text-on-background group-hover:text-primary transition-colors">
                                            {ws.name}
                                        </a>
                                        <span className="text-xs text-on-background/50 font-mono">{ws.slug}</span>
                                    </div>
                                    <span className="px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider bg-almost-black/5 text-on-background/70 border border-almost-black/10">
                                        {ws.role}
                                    </span>
                                </div>
                            ))}
                            {user.workspaces.length === 0 && (
                                <div className="p-8 text-center text-on-background/50 text-sm">
                                    Not a member of any workspaces.
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </ControlEntityLayout>
        </ControlLayout>
    );
}
