import { Zap, LayoutGrid, Clock, Hash } from 'lucide-react';
import OverviewCard from './OverviewCard';

export default function WorkspacesSnapshotSection() {
    const workspaces = [
        { id: 1, name: 'Production DB', env: 'prod', tenant: 'Acme Corp', requests: '1.2M', lastActive: '2 mins ago' },
        { id: 2, name: 'Staging Area', env: 'staging', tenant: 'Acme Corp', requests: '45K', lastActive: '1 hr ago' },
        { id: 3, name: 'Customer Support', env: 'prod', tenant: 'Globex', requests: '890K', lastActive: '5 mins ago' },
        { id: 4, name: 'Dev Sandbox', env: 'dev', tenant: 'Initech', requests: '124', lastActive: '3 days ago' },
    ];

    const envColors: Record<string, string> = {
        prod: 'bg-destructive/10 text-destructive border-destructive/20',
        staging: 'bg-amber-500/10 text-amber-600 border-amber-500/20',
        dev: 'bg-blue-500/10 text-blue-600 border-blue-500/20'
    };

    return (
        <OverviewCard
            id="overview-workspaces"
            title="Active Workspaces"
            icon={<LayoutGrid className="h-5 w-5" />}
            viewAllHref="/platform/workspaces"
        >
            <div className="space-y-4">
                {workspaces.map(ws => (
                    <div key={ws.id} className="flex flex-col p-3 border border-almost-black/10 hover:border-primary/30 transition-colors group">
                        <div className="flex items-center justify-between mb-2">
                            <span className="text-sm font-bold text-on-background group-hover:text-primary transition-colors">
                                {ws.name}
                            </span>
                            <span className={`text-[10px] uppercase tracking-wider font-bold px-2 py-0.5 border ${envColors[ws.env] || 'bg-almost-black/5 text-on-background'}`}>
                                {ws.env}
                            </span>
                        </div>
                        <div className="text-xs text-on-background/60 mb-2">
                            Tenant: {ws.tenant}
                        </div>
                        <div className="flex items-center justify-between border-t border-almost-black/5 pt-2 mt-1">
                            <div className="flex items-center gap-1.5 text-xs text-on-background/70 font-mono">
                                <Zap className="h-3.5 w-3.5" />
                                {ws.requests} reqs
                            </div>
                            <div className="flex items-center gap-1.5 text-xs text-on-background/50 font-mono">
                                <Clock className="h-3.5 w-3.5" />
                                {ws.lastActive}
                            </div>
                        </div>
                    </div>
                ))}
            </div>
        </OverviewCard>
    );
}
