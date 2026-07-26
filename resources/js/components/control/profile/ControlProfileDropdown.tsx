import { useControlShell } from '@/providers/control/ControlShellProvider';
import { useProfile } from '@/providers/control/ProfileProvider';
import { Link } from '@inertiajs/react';
import { User as UserIcon, LogOut, Settings } from 'lucide-react';
import { ControlTooltip } from '../ui/ControlTooltip';
import { useState, useRef, useEffect } from 'react';

export default function ControlProfileDropdown() {
    const { collapsed } = useControlShell();
    const { user } = useProfile();
    const [isOpen, setIsOpen] = useState(false);
    const dropdownRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        const handleClickOutside = (event: MouseEvent) => {
            if (dropdownRef.current && !dropdownRef.current.contains(event.target as Node)) {
                setIsOpen(false);
            }
        };
        document.addEventListener('mousedown', handleClickOutside);
        return () => document.removeEventListener('mousedown', handleClickOutside);
    }, []);

    const trigger = (
        <div
            className={`flex w-full items-center p-4 cursor-pointer hover:bg-almost-black/5 transition-colors relative ${isOpen ? 'bg-almost-black/5' : ''} ${collapsed ? 'justify-center' : ''}`}
            onClick={() => setIsOpen(!isOpen)}
            aria-expanded={isOpen}
            aria-haspopup="menu"
        >
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

    return (
        <div className="relative" ref={dropdownRef}>
            {isOpen && (
                <div className="absolute bottom-full left-2 mb-2 w-56 rounded bg-background border border-almost-black shadow-sm py-1 z-50">
                    {/* Header in widget */}
                    <div className="px-4 py-3 border-b border-almost-black/10">
                        <p className="text-sm font-semibold font-heading text-on-background truncate">
                            {user?.name || 'Administrator'}
                        </p>
                        <p className="text-xs text-on-background/60 truncate mt-0.5">
                            {user?.email || 'admin@tracemem.one'}
                        </p>
                    </div>

                    {/* Actions */}
                    <div className="py-1">
                        <Link
                            href="/configuration/settings"
                            className="flex items-center w-full px-4 py-2 text-sm font-medium text-on-background hover:bg-almost-black/5 transition-colors"
                            onClick={() => setIsOpen(false)}
                        >
                            <Settings className="mr-3 h-4 w-4 text-on-background/70" />
                            Platform Settings
                        </Link>

                        <Link
                            href="/logout"
                            method="post"
                            as="button"
                            className="flex items-center w-full px-4 py-2 text-sm font-medium text-destructive hover:bg-destructive/10 transition-colors text-left"
                        >
                            <LogOut className="mr-3 h-4 w-4" />
                            Logout
                        </Link>
                    </div>
                </div>
            )}

            {collapsed ? (
                <ControlTooltip label="Profile & Settings" delay={100} className="flex w-full">
                    {trigger}
                </ControlTooltip>
            ) : (
                trigger
            )}
        </div>
    );
}
