import { ActivitySquare, Ghost } from 'lucide-react';
import OverviewCard from './OverviewCard';
import { Link } from '@inertiajs/react';

export default function ActivityFeedSection({ data }: { data: any }) {
    if (data?.error) {
        return (
            <OverviewCard id="overview-activity-feed" title="Platform Activity Feed" icon={<ActivitySquare className="h-5 w-5" />}>
                <div className="flex flex-col items-center justify-center py-8 text-on-background/50">
                    <span className="text-sm font-bold text-destructive uppercase">{data.error}</span>
                </div>
            </OverviewCard>
        );
    }

    const activities = Array.isArray(data) ? data : [];

    return (
        <OverviewCard
            id="overview-activity-feed"
            title="Platform Activity Feed"
            icon={<ActivitySquare className="h-5 w-5" />}
            viewAllHref="/operations/activity"
        >
            {activities.length === 0 ? (
                <div className="flex flex-col items-center justify-center py-10 opacity-60">
                    <Ghost className="h-8 w-8 mb-3 text-primary/40" />
                    <span className="text-sm font-bold uppercase tracking-wider">Silent Platform</span>
                    <span className="text-xs text-center mt-1">No system activity recorded recently.</span>
                </div>
            ) : (
                <div className="relative border-l border-almost-black/10 ml-2 space-y-6 pb-4 pt-2">
                    {activities.map((item: any, i: number) => (
                        <div key={item.id || i} className="relative pl-6">
                            <div className="absolute -left-1.5 top-1.5 h-3 w-3 bg-surface border-2 border-primary rounded-full" />
                            <div className="flex flex-col">
                                <span className="text-sm font-medium text-on-background/80">
                                    <span className="font-bold text-primary">{item.actor}</span> {item.description}
                                </span>
                                <span className="text-xs font-mono text-on-background/50 mt-1">{item.time}</span>
                            </div>
                        </div>
                    ))}
                </div>
            )}
        </OverviewCard>
    );
}
