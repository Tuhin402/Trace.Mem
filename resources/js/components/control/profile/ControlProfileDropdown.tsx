import { useControlShell } from '@/providers/control/ControlShellProvider';
import { useProfile } from '@/providers/control/ProfileProvider';
import { Link } from '@inertiajs/react';
import { User as UserIcon, LogOut, Settings, Keyboard, Palette } from 'lucide-react';
import { ControlTooltip } from '../ui/ControlTooltip';

export default function ControlProfileDropdown() {
    const { collapsed } = useControlShell();
    const { user } = useProfile();

    const trigger = (
        <div className="flex w-full items-center p-4 cursor-pointer hover:bg-almost-black/5 transition-colors">
            <div className="h-8 w-8 shrink-0 bg-primary/10 border border-primary/20 text-primary flex items-center justify-center font-bold rounded">
                <UserIcon className="h-4 w-4" />
            </div>
            
            {!collapsed && (
                <div className="ml-3 flex-1 overflow-hidden">
                    <p className="text-sm font-semibold leading-5 text-on-background truncate">
                        {user?.name || 'Administrator'}
                    </p>
                    <p className="text-xs text-on-background/60 truncate">
                        {user?.email || 'admin@tracemem.one'}
                    </p>
                </div>
            )}
        </div>
    );

    if (collapsed) {
        return (
            <ControlTooltip label="Profile & Settings" delay={100}>
                {trigger}
            </ControlTooltip>
        );
    }

    return trigger;
}
