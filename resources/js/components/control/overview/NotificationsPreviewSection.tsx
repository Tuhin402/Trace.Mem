import { Bell, ArrowRight } from 'lucide-react';
import OverviewCard from './OverviewCard';
import { Link } from '@inertiajs/react';

export default function NotificationsPreviewSection() {
    const notifications = [
        { id: 1, title: 'New Admin Registered', time: '10m ago', unread: true },
        { id: 2, title: 'Storage Warning: Acme Corp', time: '1h ago', unread: true },
        { id: 3, title: 'System Backup Completed', time: '5h ago', unread: false },
        { id: 4, title: 'API Key Revoked', time: '1d ago', unread: false },
        { id: 5, title: 'Weekly Report Generated', time: '2d ago', unread: false },
    ];

    return (
        <OverviewCard
            id="overview-notifications"
            title="Notifications"
            icon={<Bell className="h-5 w-5" />}
            viewAllHref="/operations/notifications"
        >
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
        </OverviewCard>
    );
}
