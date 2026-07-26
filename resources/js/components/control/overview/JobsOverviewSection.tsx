import { Activity, Play, AlertCircle } from 'lucide-react';
import OverviewCard from './OverviewCard';
import { Link } from '@inertiajs/react';

export default function JobsOverviewSection({ data }: { data: any }) {
    if (data?.error) {
        return (
            <OverviewCard id="overview-jobs-queues" title="Jobs & Queues" icon={<Activity className="h-5 w-5" />}>
                <div className="flex flex-col items-center justify-center py-8 text-on-background/50">
                    <span className="text-sm font-bold text-destructive uppercase">{data.error}</span>
                </div>
            </OverviewCard>
        );
    }

    const pending = data?.pending || 0;
    const failed = data?.failed || 0;
    const status = data?.status || 'Healthy';

    return (
        <OverviewCard
            id="overview-jobs-queues"
            title="Jobs & Queues"
            icon={<Activity className="h-5 w-5" />}
            viewAllHref="/operations/jobs"
        >
            <div className="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
                <div className="flex flex-col">
                    <span className="text-xs font-bold uppercase tracking-wider text-on-background/50 mb-1">Status</span>
                    <span className={`text-sm font-bold uppercase ${status === 'Healthy' ? 'text-green-600' : 'text-destructive'}`}>
                        {status}
                    </span>
                </div>
                <div className="flex flex-col">
                    <span className="text-xs font-bold uppercase tracking-wider text-on-background/50 mb-1">Pending</span>
                    <span className="text-xl font-heading font-bold text-primary">{pending}</span>
                </div>
                <div className="flex flex-col">
                    <span className="text-xs font-bold uppercase tracking-wider text-on-background/50 mb-1">Failed</span>
                    <span className={`text-xl font-heading font-bold ${failed > 0 ? 'text-destructive' : 'text-primary'}`}>{failed}</span>
                </div>
                <div className="flex flex-col">
                    <span className="text-xs font-bold uppercase tracking-wider text-on-background/50 mb-1">Longest</span>
                    <span className="text-xl font-heading font-bold text-primary">{data?.longestRunning || '0m'}</span>
                </div>
            </div>

            {failed > 0 && (
                <div className="p-4 bg-destructive/5 border border-destructive/20 flex items-start gap-3">
                    <AlertCircle className="h-5 w-5 text-destructive shrink-0 mt-0.5" />
                    <div className="flex flex-col">
                        <span className="text-sm font-bold text-destructive">Failed Jobs Require Attention</span>
                        <span className="text-xs text-on-background/70 mt-1">
                            There are currently {failed} failed background jobs. This may impact email delivery or background processing.
                        </span>
                        <div className="mt-3 flex items-center gap-4">
                            <Link href="/operations/jobs/failed" className="text-xs font-bold text-destructive hover:underline uppercase tracking-wider">
                                View Failed Jobs
                            </Link>
                            <button className="text-xs font-bold text-on-background/50 hover:text-on-background uppercase tracking-wider flex items-center gap-1">
                                <Play className="h-3 w-3" /> Retry All
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </OverviewCard>
    );
}
