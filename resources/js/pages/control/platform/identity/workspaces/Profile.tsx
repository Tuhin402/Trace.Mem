import React from 'react';
import { Head, router } from '@inertiajs/react';
import ControlLayout from '@/layouts/control/ControlLayout';
import ControlEntityLayout from '@/layouts/control/ControlEntityLayout';
import { Settings, Ban, Key } from 'lucide-react';

export default function WorkspaceProfile({ workspace }: { workspace: any }) {
    const breadcrumbs = [
        { label: 'Identity' },
        { label: 'Workspaces', url: '/platform/workspaces' },
        { label: workspace.tenant.name, url: `/platform/tenants/${workspace.tenant.slug}` },
        { label: workspace.name }
    ];

    const badges = [
        <span key="status" className={`px-2 py-0.5 text-xs font-bold uppercase tracking-wider ${workspace.status === 'active' ? 'bg-green-500/10 text-green-500' : 'bg-destructive/10 text-destructive'}`}>
            {workspace.status}
        </span>,
        <span key="env" className={`px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider border ${
            workspace.environment === 'production' 
                ? 'bg-primary/10 text-primary border-primary/20' 
                : 'bg-almost-black/5 text-on-background/70 border-almost-black/10'
        }`}>
            {workspace.environment}
        </span>
    ];

    const actions = [
        { label: 'Settings', icon: <Settings className="h-4 w-4" />, onClick: () => alert('Workspace settings coming soon') },
        { label: 'Suspend', icon: <Ban className="h-4 w-4" />, destructive: true, onClick: () => alert('Suspend workspace coming soon') },
    ];

    const tabs = [
        { label: 'Overview', id: 'overview', isActive: true },
        { label: 'Members', id: 'members' },
        { label: 'API Keys', id: 'api-keys' },
        { label: 'Activity', id: 'activity' }
    ];

    return (
        <ControlLayout>
            <ControlEntityLayout
                title={workspace.name}
                breadcrumbs={breadcrumbs}
                badges={badges}
                actions={actions}
                tabs={tabs}
            >
                <div id="overview" className="space-y-6">
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                        
                        {/* Summary */}
                        <div className="p-6 bg-surface border border-almost-black/10 flex flex-col gap-4">
                            <h3 className="text-xs font-bold uppercase tracking-wider text-on-background/50">Details</h3>
                            <div className="flex flex-col gap-1 mt-2">
                                <span className="text-[10px] font-bold uppercase tracking-wider text-on-background/50">Tenant</span>
                                <a href={`/platform/tenants/${workspace.tenant.slug}`} className="text-sm font-bold text-primary hover:underline">
                                    {workspace.tenant.name}
                                </a>
                            </div>
                            <div className="flex flex-col gap-1 mt-2">
                                <span className="text-[10px] font-bold uppercase tracking-wider text-on-background/50">Slug</span>
                                <span className="text-sm font-mono text-on-background">{workspace.slug}</span>
                            </div>
                            <div className="flex flex-col gap-1 mt-2">
                                <span className="text-[10px] font-bold uppercase tracking-wider text-on-background/50">Created</span>
                                <span className="text-sm font-mono text-on-background">{workspace.created_at}</span>
                            </div>
                        </div>

                        {/* Metrics */}
                        <div className="p-6 bg-surface border border-almost-black/10 flex flex-col gap-4">
                            <h3 className="text-xs font-bold uppercase tracking-wider text-on-background/50">Usage</h3>
                            <div className="grid grid-cols-2 md:grid-cols-3 gap-4 h-full content-center">
                                <div className="flex flex-col items-center">
                                    <span className="text-4xl font-black font-heading text-primary">{workspace.metrics.members}</span>
                                    <span className="text-[10px] font-bold uppercase tracking-wider text-on-background/50 mt-1">Members</span>
                                </div>
                                <div className="flex flex-col items-center">
                                    <span className="text-4xl font-black font-heading text-primary">{workspace.metrics.api_keys}</span>
                                    <span className="text-[10px] font-bold uppercase tracking-wider text-on-background/50 mt-1">API Keys</span>
                                </div>
                                <div className="flex flex-col items-center">
                                    <span className="text-4xl font-black font-heading text-primary">{workspace.metrics.memories}</span>
                                    <span className="text-[10px] font-bold uppercase tracking-wider text-on-background/50 mt-1">Memories</span>
                                </div>
                            </div>
                        </div>

                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                        {/* Members List */}
                        <div className="w-full bg-surface border border-almost-black/10">
                            <div className="px-6 py-4 border-b border-almost-black/10 flex items-center justify-between">
                                <h3 className="text-sm font-bold uppercase tracking-wider text-on-background">Members</h3>
                                <button className="text-xs font-bold uppercase tracking-wider text-primary hover:underline">View All</button>
                            </div>
                            <div className="divide-y divide-almost-black/5">
                                {workspace.members.map((member: any) => (
                                    <div key={member.id} className="p-4 flex items-center justify-between group">
                                        <div className="flex flex-col">
                                            <a href={`/platform/users/${member.uuid}`} className="text-sm font-bold text-on-background group-hover:text-primary transition-colors">
                                                {member.name}
                                            </a>
                                            <span className="text-xs text-on-background/50 font-mono">{member.email}</span>
                                        </div>
                                        <span className="px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider bg-almost-black/5 text-on-background/70 border border-almost-black/10">
                                            {member.role}
                                        </span>
                                    </div>
                                ))}
                                {workspace.members.length === 0 && (
                                    <div className="p-8 text-center text-on-background/50 text-sm">
                                        No members found.
                                    </div>
                                )}
                            </div>
                        </div>

                        {/* Recent API Keys */}
                        <div className="w-full bg-surface border border-almost-black/10">
                            <div className="px-6 py-4 border-b border-almost-black/10 flex items-center justify-between">
                                <h3 className="text-sm font-bold uppercase tracking-wider text-on-background">Recent API Keys</h3>
                                <button className="text-xs font-bold uppercase tracking-wider text-primary hover:underline">View All</button>
                            </div>
                            <div className="divide-y divide-almost-black/5">
                                {workspace.recent_api_keys.map((key: any) => (
                                    <div key={key.id} className="p-4 flex items-center justify-between group">
                                        <div className="flex items-center gap-3">
                                            <Key className="h-4 w-4 text-on-background/50" />
                                            <div className="flex flex-col">
                                                <span className="text-sm font-bold text-on-background">{key.name}</span>
                                                <span className="text-xs text-on-background/50 font-mono">Used: {key.last_used_at}</span>
                                            </div>
                                        </div>
                                        <span className={`px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider ${key.is_active ? 'bg-green-500/10 text-green-500' : 'bg-destructive/10 text-destructive'}`}>
                                            {key.is_active ? 'Active' : 'Revoked'}
                                        </span>
                                    </div>
                                ))}
                                {workspace.recent_api_keys.length === 0 && (
                                    <div className="p-8 text-center text-on-background/50 text-sm">
                                        No API Keys generated yet.
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>
                </div>
            </ControlEntityLayout>
        </ControlLayout>
    );
}
