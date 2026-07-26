import { useNotifications } from '@/providers/control/NotificationProvider';
import { Bell } from 'lucide-react';

export default function ControlNotificationDropdown() {
    const { hasUnread, unreadCount } = useNotifications();

    return (
        <button type="button" className="-m-2.5 p-2.5 text-on-background/70 hover:text-on-background relative transition-colors">
            <span className="sr-only">View notifications</span>
            <Bell className="h-6 w-6" aria-hidden="true" />
            {hasUnread && (
                <span className="absolute top-2 right-2 h-2 w-2 rounded-full bg-primary ring-2 ring-background"></span>
            )}
        </button>
    );
}
