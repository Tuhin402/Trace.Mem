import { Link } from '@inertiajs/react';
import { Activity, Ghost, Info } from 'lucide-react';

export default function SystemActivitySection({ data }: { data: any }) {
    if (data?.error) {
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
                <div className="flex flex-col items-center justify-center py-8 text-on-background/50">
                    <span className="text-sm font-bold text-destructive uppercase">{data.error}</span>
                </div>
            </section>
        );
    }

    const activities = Array.isArray(data) ? data : [];

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
                {activities.length === 0 ? (
                    <div className="flex flex-col items-center justify-center py-10 opacity-60 w-full">
                        <Ghost className="h-8 w-8 mb-3 text-primary/40" />
                        <span className="text-sm font-bold uppercase tracking-wider">No Recent Activity</span>
                        <span className="text-xs text-center mt-1">The system is currently quiet.</span>
                    </div>
                ) : (
                    <div className="flex items-center gap-4 p-4 min-w-max">
                        {activities.map((activity, index) => (
                            <div key={activity.id || index} className="flex items-center gap-4">
                                <Link href="/operations/activity" className="group flex items-center gap-3 py-2 px-4 hover:bg-almost-black/5 border border-transparent hover:border-almost-black/10 transition-colors">
                                    <div className="flex items-center justify-center h-8 w-8 bg-background border border-almost-black/10 shrink-0 text-primary">
                                        <Info className="h-4 w-4" />
                                    </div>
                                    <div className="flex flex-col">
                                        <span className="text-sm font-medium text-on-background group-hover:text-primary transition-colors">
                                            <span className="font-bold">{activity.actor}</span> {activity.description}
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
                )}
            </div>
        </section>
    );
}
