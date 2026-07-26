import { Bell, ArrowRight, Ghost } from 'lucide-react';
import OverviewCard from './OverviewCard';
import { Link } from '@inertiajs/react';

export default function NotificationsPreviewSection({ data }: { data: any }) {
    if (data?.error) {
        return (
            <OverviewCard id="overview-notifications" title="Notifications" icon={<Bell className="h-5 w-5" />}>
                <div className="flex flex-col items-center justify-center py-8 text-on-background/50">
                    <span className="text-sm font-bold text-destructive uppercase">{data.error}</span>
                </div>
            </OverviewCard>
        );
    }

    const notifications = Array.isArray(data) ? data : [];

    return (
        <OverviewCard
            id="overview-notifications"
            title="Notifications"
            icon={<Bell className="h-5 w-5" />}
            viewAllHref="/operations/notifications"
            className="max-h-96" // Fixed height for notifications as requested
        >
            {notifications.length === 0 ? (
                <div className="flex flex-col items-center justify-center py-10 opacity-60">
                    <Ghost className="h-8 w-8 mb-3 text-primary/40" />
                    <span className="text-sm font-bold uppercase tracking-wider">All Clear</span>
                    <span className="text-xs text-center mt-1">No operational notifications right now.</span>
                </div>
            ) : (
                <div className="space-y-0 divide-y divide-almost-black/5">
                    {notifications.map(notification => (
                        <div key={notification.id} className="py-3 flex items-start gap-3 group">
                            <div className="mt-1">
                                {notification.unread ? (
                                    <div className="h-2 w-2 rounded-full bg-primary" />
                                ) : (
                                    <div className="h-2 w-2 rounded-full border border-almost-black/20" />
                                )}
                            </div>
                            <div className="flex-1 flex flex-col">
                                <span className={`text-sm ${notification.unread ? 'font-bold text-on-background' : 'font-medium text-on-background/70'}`}>
                                    {notification.title}
                                </span>
                                <span className="text-xs font-mono text-on-background/50 mt-0.5">
                                    {notification.time}
                                </span>
                            </div>
                            <Link href="/operations/notifications" className="opacity-0 group-hover:opacity-100 transition-opacity">
                                <ArrowRight className="h-4 w-4 text-primary" />
                            </Link>
                        </div>
                    ))}
                </div>
            )}
        </OverviewCard>
    );
}
