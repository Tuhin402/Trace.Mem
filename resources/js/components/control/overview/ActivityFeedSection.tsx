import { Activity } from 'lucide-react';
import OverviewCard from './OverviewCard';

export default function ActivityFeedSection() {
    const feed = [
        { id: 1, text: 'Memory injected by Globex', time: 'Just now', type: 'info' },
        { id: 2, text: 'Webhook to Acme Corp failed', time: '2m ago', type: 'error' },
        { id: 3, text: 'New tenant registered: Initech', time: '15m ago', type: 'success' },
        { id: 4, text: 'Database backup completed', time: '1h ago', type: 'info' },
        { id: 5, text: 'High CPU on worker node 4', time: '3h ago', type: 'warning' },
        { id: 6, text: 'API rate limit exceeded by Soylent', time: '4h ago', type: 'warning' },
        { id: 7, text: 'Admin "Alice" updated settings', time: '5h ago', type: 'info' },
        { id: 8, text: 'SSL certificate renewed automatically', time: '1d ago', type: 'success' },
    ];

    const typeColors: Record<string, string> = {
        info: 'bg-primary/20',
        error: 'bg-destructive',
        success: 'bg-green-500',
        warning: 'bg-amber-500'
    };

    return (
        <OverviewCard
            id="overview-activity-feed"
            title="Live Feed"
            icon={<Activity className="h-5 w-5" />}
            viewAllHref="/operations/activity"
            className="xl:max-h-[600px]" // Limit height on large screens to fit sidebar properly
        >
            <div className="relative border-l border-almost-black/10 ml-2 space-y-6">
                {feed.map(item => (
                    <div key={item.id} className="relative pl-6">
                        <div className={`absolute -left-1.5 top-1.5 h-3 w-3 rounded-full border-2 border-surface ${typeColors[item.type]}`} />
                        <div className="flex flex-col">
                            <span className="text-sm font-medium text-on-background/80 leading-tight">
                                {item.text}
                            </span>
                            <span className="text-xs font-mono text-on-background/40 mt-1">
                                {item.time}
                            </span>
                        </div>
                    </div>
                ))}
            </div>
        </OverviewCard>
    );
}
