import { useControlShell } from '@/providers/control/ControlShellProvider';
import { Menu } from 'lucide-react';
import ControlSearch from '../search/ControlSearch';
import ControlNotificationDropdown from '../notifications/ControlNotificationDropdown';

export default function ControlTopbar() {
    const { collapsed, setMobileDrawerOpen } = useControlShell();

    return (
        <header className={`fixed top-0 right-0 left-0 z-40 flex h-16 shrink-0 items-center gap-x-4 border-b border-almost-black/10 bg-background px-4 shadow-sm sm:gap-x-6 sm:px-6 lg:px-8 transition-all duration-300 ${collapsed ? 'md:left-[var(--control-sidebar-collapsed)]' : 'md:left-[var(--control-sidebar-expanded)]'}`}>
            
            {/* Mobile Hamburger */}
            <button 
                type="button"
                className="md:hidden -m-2.5 p-2.5 text-on-background/70 hover:text-on-background"
                onClick={() => setMobileDrawerOpen(true)}
            >
                <span className="sr-only">Open sidebar</span>
                <Menu className="h-6 w-6" aria-hidden="true" />
            </button>

            <div className="flex flex-1 gap-x-4 self-stretch lg:gap-x-6 relative">
                {/* Global Search Component */}
                <ControlSearch />

                <div className="flex items-center gap-x-4 lg:gap-x-6">
                    {/* Notifications Component */}
                    <ControlNotificationDropdown />
                </div>
            </div>
        </header>
    );
}
