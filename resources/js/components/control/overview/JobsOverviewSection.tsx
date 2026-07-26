import { Cpu, CheckCircle2, XCircle, RotateCcw } from 'lucide-react';
import OverviewCard from './OverviewCard';

export default function JobsOverviewSection() {
    const jobs = [
        { id: 'job-9382', name: 'App\\Jobs\\ProcessTenantMemory', queue: 'high', status: 'processing', duration: '45s' },
        { id: 'job-9381', name: 'App\\Jobs\\GenerateWeeklyReport', queue: 'default', status: 'failed', duration: '2m 14s' },
        { id: 'job-9380', name: 'App\\Jobs\\CleanupOrphanedNodes', queue: 'low', status: 'completed', duration: '12s' },
        { id: 'job-9379', name: 'App\\Jobs\\ProcessWebhookDelivery', queue: 'default', status: 'completed', duration: '1s' },
        { id: 'job-9378', name: 'App\\Jobs\\ProcessWebhookDelivery', queue: 'default', status: 'completed', duration: '1s' },
    ];

    const queueColors: Record<string, string> = {
        high: 'bg-destructive/10 text-destructive border-destructive/20',
        default: 'bg-primary/10 text-primary border-primary/20',
        low: 'bg-almost-black/5 text-on-background/70 border-almost-black/10'
    };

    return (
        <OverviewCard
            id="overview-jobs"
            title="Background Jobs & Queues"
            icon={<Cpu className="h-5 w-5" />}
            viewAllHref="/operations/jobs"
        >
            <div className="grid grid-cols-3 gap-4 mb-6">
                <div className="p-3 border border-almost-black/10 flex flex-col items-center justify-center text-center">
                    <span className="text-2xl font-bold font-heading text-primary">14</span>
                    <span className="text-[10px] font-bold uppercase tracking-wider text-on-background/60 mt-1">Processing</span>
                </div>
                <div className="p-3 border border-almost-black/10 flex flex-col items-center justify-center text-center">
                    <span className="text-2xl font-bold font-heading text-on-background">3,492</span>
                    <span className="text-[10px] font-bold uppercase tracking-wider text-on-background/60 mt-1">Queued</span>
                </div>
                <div className="p-3 border border-destructive/20 bg-destructive/5 flex flex-col items-center justify-center text-center">
                    <span className="text-2xl font-bold font-heading text-destructive">2</span>
                    <span className="text-[10px] font-bold uppercase tracking-wider text-destructive/80 mt-1">Failed</span>
                </div>
            </div>

            <div className="overflow-x-auto no-scrollbar">
                <table className="w-full text-left text-sm">
                    <thead>
                        <tr className="border-b border-almost-black/10 text-xs uppercase tracking-wider text-on-background/50">
                            <th className="pb-3 font-medium">Job Class</th>
                            <th className="pb-3 font-medium">Queue</th>
                            <th className="pb-3 font-medium text-right">Status</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-almost-black/5">
                        {jobs.map((job) => (
                            <tr key={job.id} className="group hover:bg-almost-black/5 transition-colors">
                                <td className="py-3 pr-4">
                                    <div className="font-mono text-xs text-on-background/80 group-hover:text-primary transition-colors">
                                        {job.name.split('\\').pop()}
                                    </div>
                                    <div className="text-[10px] text-on-background/40 font-mono mt-0.5">
                                        {job.id}
                                    </div>
                                </td>
                                <td className="py-3 pr-4">
                                    <span className={`text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 border ${queueColors[job.queue]}`}>
                                        {job.queue}
                                    </span>
                                </td>
                                <td className="py-3 text-right">
                                    <div className="flex items-center justify-end gap-2">
                                        <span className="text-xs font-mono text-on-background/50">{job.duration}</span>
                                        {job.status === 'processing' && <RotateCcw className="h-4 w-4 text-primary animate-spin" />}
                                        {job.status === 'completed' && <CheckCircle2 className="h-4 w-4 text-green-500" />}
                                        {job.status === 'failed' && <XCircle className="h-4 w-4 text-destructive" />}
                                    </div>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </OverviewCard>
    );
}
