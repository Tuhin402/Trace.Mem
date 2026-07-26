import { Zap, Ghost } from 'lucide-react';
import OverviewCard from './OverviewCard';
import { Link } from '@inertiajs/react';

export default function WorkspacesSnapshotSection({ data }: { data: any }) {
    if (data?.error) {
        return (
            <OverviewCard id="overview-workspaces-snapshot" title="Workspaces Snapshot" icon={<Zap className="h-5 w-5" />}>
                <div className="flex flex-col items-center justify-center py-8 text-on-background/50">
                    <span className="text-sm font-bold text-destructive uppercase">{data.error}</span>
                </div>
            </OverviewCard>
        );
    }

    const workspaces = Array.isArray(data) ? data : [];

    return (
        <OverviewCard
            id="overview-workspaces-snapshot"
            title="Workspaces Snapshot"
            icon={<Zap className="h-5 w-5" />}
            viewAllHref="/operations/workspaces"
        >
            {workspaces.length === 0 ? (
                <div className="flex flex-col items-center justify-center py-10 opacity-60">
                    <Ghost className="h-8 w-8 mb-3 text-primary/40" />
                    <span className="text-sm font-bold uppercase tracking-wider">No Workspaces</span>
                    <span className="text-xs text-center mt-1">Users haven't created any workspaces yet.</span>
                </div>
            ) : (
                <table className="w-full text-left border-collapse">
                    <thead>
                        <tr className="border-b border-almost-black/10 text-[10px] uppercase font-bold text-on-background/50">
                            <th className="pb-3 font-medium">Workspace</th>
                            <th className="pb-3 font-medium hidden sm:table-cell">Env</th>
                            <th className="pb-3 font-medium text-right">Created</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-almost-black/5">
                        {workspaces.map(ws => (
                            <tr key={ws.id} className="group hover:bg-almost-black/5 transition-colors">
                                <td className="py-3">
                                    <div className="flex flex-col">
                                        <Link href={`/operations/workspaces/${ws.id}`} className="text-sm font-bold text-primary group-hover:underline cursor-pointer">{ws.name}</Link>
                                    </div>
                                </td>
                                <td className="py-3 hidden sm:table-cell">
                                    <span className="text-xs font-bold uppercase text-on-background/70">{ws.environment}</span>
                                </td>
                                <td className="py-3 text-right">
                                    <span className="text-xs font-mono text-on-background/60">{ws.time}</span>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            )}
        </OverviewCard>
    );
}
