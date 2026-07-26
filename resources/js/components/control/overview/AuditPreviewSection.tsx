import { FileText, Shield } from 'lucide-react';
import OverviewCard from './OverviewCard';

export default function AuditPreviewSection() {
    const logs = [
        { id: 'al-491', actor: 'alice@example.com', action: 'updated_settings', target: 'Platform Settings', time: '10 mins ago' },
        { id: 'al-490', actor: 'alice@example.com', action: 'revoked_key', target: 'pk_live_acme...8f2a', time: '2 hrs ago' },
        { id: 'al-489', actor: 'bob@acmecorp.com', action: 'impersonated_tenant', target: 'Tenant: Initech', time: '4 hrs ago' },
        { id: 'al-488', actor: 'system', action: 'auto_scaled_nodes', target: 'Worker Group A', time: '12 hrs ago' },
    ];

    return (
        <OverviewCard
            id="overview-audit"
            title="Audit Log"
            icon={<FileText className="h-5 w-5" />}
            viewAllHref="/operations/audit-logs"
        >
            <div className="overflow-x-auto no-scrollbar">
                <table className="w-full text-left text-sm">
                    <thead>
                        <tr className="border-b border-almost-black/10 text-xs uppercase tracking-wider text-on-background/50">
                            <th className="pb-3 font-medium">Actor</th>
                            <th className="pb-3 font-medium">Action</th>
                            <th className="pb-3 font-medium text-right">Time</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-almost-black/5">
                        {logs.map((log) => (
                            <tr key={log.id} className="group hover:bg-almost-black/5 transition-colors">
                                <td className="py-3 pr-4">
                                    <div className="flex items-center gap-2">
                                        {log.actor === 'system' ? (
                                            <Shield className="h-3 w-3 text-primary" />
                                        ) : (
                                            <div className="h-4 w-4 bg-primary/10 border border-primary/20 flex items-center justify-center text-[8px] text-primary font-bold">
                                                {log.actor.charAt(0).toUpperCase()}
                                            </div>
                                        )}
                                        <span className="text-xs font-mono truncate max-w-[120px]">{log.actor}</span>
                                    </div>
                                </td>
                                <td className="py-3 pr-4">
                                    <div className="text-sm font-medium text-on-background/90 group-hover:text-primary transition-colors">
                                        {log.action}
                                    </div>
                                    <div className="text-xs text-on-background/50 mt-0.5">
                                        {log.target}
                                    </div>
                                </td>
                                <td className="py-3 text-right">
                                    <span className="text-xs font-mono text-on-background/50">{log.time}</span>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </OverviewCard>
    );
}
