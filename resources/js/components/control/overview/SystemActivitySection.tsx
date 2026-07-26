import { Link } from '@inertiajs/react';
import { Activity, Plus, Trash2, Key, ArrowUpCircle, AlertTriangle, LogIn } from 'lucide-react';

export default function SystemActivitySection() {
    const activities = [
        { id: 1, type: 'tenant_created', message: 'Tenant "Acme Corp" created', time: '2 mins ago', icon: <Plus className="h-4 w-4" />, color: 'text-green-500' },
        { id: 2, type: 'api_key', message: 'API Key generated for Workspace "Production"', time: '15 mins ago', icon: <Key className="h-4 w-4" />, color: 'text-primary' },
        { id: 3, type: 'subscription', message: 'Subscription upgraded to Pro for "Globex"', time: '1 hour ago', icon: <ArrowUpCircle className="h-4 w-4" />, color: 'text-blue-500' },
        { id: 4, type: 'admin_login', message: 'Admin login from new IP (192.168.1.1)', time: '3 hours ago', icon: <LogIn className="h-4 w-4" />, color: 'text-amber-500' },
        { id: 5, type: 'workspace_deleted', message: 'Workspace "Sandbox" deleted by user', time: '5 hours ago', icon: <Trash2 className="h-4 w-4" />, color: 'text-destructive' },
        { id: 6, type: 'job_failed', message: 'Background job "ProcessMemory" failed after 3 retries', time: '6 hours ago', icon: <AlertTriangle className="h-4 w-4" />, color: 'text-destructive' },
    ];

    return (
        <section id="overview-system-activity" className="w-full bg-surface border border-almost-black scroll-mt-24">
            <div className="flex items-center justify-between px-6 py-4 border-b border-almost-black/10 shrink-0">
                <div className="flex items-center gap-3">
                    <div className="text-primary/80">
                        <Activity className="h-5 w-5" />
                    </div>
                    <h2 className="text-lg font-bold font-heading text-primary tracking-tight">
                        Platform Activity Feed
                    </h2>
                </div>
            </div>
            <div className="p-0 overflow-x-auto no-scrollbar">
                <div className="flex items-center gap-4 p-4 min-w-max">
                    {activities.map((activity, index) => (
                        <div key={activity.id} className="flex items-center gap-4">
                            <Link href="/operations/activity" className="group flex items-center gap-3 py-2 px-4 hover:bg-almost-black/5 border border-transparent hover:border-almost-black/10 transition-colors">
                                <div className={`flex items-center justify-center h-8 w-8 bg-background border border-almost-black/10 shrink-0 ${activity.color}`}>
                                    {activity.icon}
                                </div>
                                <div className="flex flex-col">
                                    <span className="text-sm font-medium text-on-background group-hover:text-primary transition-colors">
                                        {activity.message}
                                    </span>
                                    <span className="text-xs font-mono text-on-background/50">
                                        {activity.time}
                                    </span>
                                </div>
                            </Link>
                            {index < activities.length - 1 && (
                                <div className="h-px w-8 bg-almost-black/10"></div>
                            )}
                        </div>
                    ))}
                </div>
            </div>
        </section>
    );
}
