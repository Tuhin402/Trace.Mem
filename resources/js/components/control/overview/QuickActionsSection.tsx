import { Command, UserPlus, FileText, Settings, Flag } from 'lucide-react';
import OverviewCard from './OverviewCard';
import { Link } from '@inertiajs/react';

export default function QuickActionsSection() {
    const actions = [
        { name: 'Invite Admin', icon: <UserPlus className="h-4 w-4" />, href: '/security/admins' },
        { name: 'System Settings', icon: <Settings className="h-4 w-4" />, href: '/configuration/settings' },
        { name: 'Feature Flags', icon: <Flag className="h-4 w-4" />, href: '/configuration/feature-flags' },
        { name: 'View Error Logs', icon: <FileText className="h-4 w-4" />, href: '/developer/logs' },
    ];

    return (
        <OverviewCard
            id="overview-quick-actions"
            title="Quick Actions"
            icon={<Command className="h-5 w-5" />}
        >
            <div className="grid grid-cols-2 gap-3">
                {actions.map((action, i) => (
                    <Link
                        key={i}
                        href={action.href}
                        className="flex flex-col items-center justify-center gap-2 p-4 border border-almost-black/10 hover:border-primary/50 hover:bg-almost-black/5 transition-all group text-center"
                    >
                        <div className="text-on-background/50 group-hover:text-primary transition-colors">
                            {action.icon}
                        </div>
                        <span className="text-xs font-bold uppercase tracking-wider text-on-background/70 group-hover:text-primary transition-colors">
                            {action.name}
                        </span>
                    </Link>
                ))}
            </div>
        </OverviewCard>
    );
}
