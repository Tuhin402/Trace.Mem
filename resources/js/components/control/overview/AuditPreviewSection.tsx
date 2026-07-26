import { Shield, ArrowRight, Ghost } from 'lucide-react';
import OverviewCard from './OverviewCard';
import { Link } from '@inertiajs/react';

export default function AuditPreviewSection({ data }: { data: any }) {
    if (data?.error) {
        return (
            <OverviewCard id="overview-audit" title="Audit Log Preview" icon={<Shield className="h-5 w-5" />}>
                <div className="flex flex-col items-center justify-center py-8 text-on-background/50">
                    <span className="text-sm font-bold text-destructive uppercase">{data.error}</span>
                </div>
            </OverviewCard>
        );
    }

    const audits = Array.isArray(data) ? data : [];

    return (
        <OverviewCard
            id="overview-audit"
            title="Audit Log Preview"
            icon={<Shield className="h-5 w-5" />}
            viewAllHref="/operations/audit"
        >
            {audits.length === 0 ? (
                <div className="flex flex-col items-center justify-center py-10 opacity-60">
                    <Ghost className="h-8 w-8 mb-3 text-primary/40" />
                    <span className="text-sm font-bold uppercase tracking-wider">No Audit Logs</span>
                    <span className="text-xs text-center mt-1">No administrative actions have been recorded yet.</span>
                </div>
            ) : (
                <div className="space-y-0 divide-y divide-almost-black/5">
                    {audits.map((log: any, i: number) => (
                        <div key={log.id || i} className="py-3 flex items-start gap-3 group">
                            <div className="mt-1 h-2 w-2 rounded-full bg-almost-black/20" />
                            <div className="flex-1 flex flex-col">
                                <span className="text-sm font-medium text-on-background/80">
                                    <span className="font-bold text-primary cursor-pointer hover:underline">{log.actor}</span> performed action <span className="font-mono text-xs font-bold bg-almost-black/5 px-1 py-0.5">{log.action}</span>
                                </span>
                                <span className="text-xs font-mono text-on-background/50 mt-1">{log.time}</span>
                            </div>
                            <Link href="/operations/audit" className="opacity-0 group-hover:opacity-100 transition-opacity">
                                <ArrowRight className="h-4 w-4 text-primary" />
                            </Link>
                        </div>
                    ))}
                </div>
            )}
        </OverviewCard>
    );
}
