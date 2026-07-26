import { useMemo } from 'react';
import { useControlShell } from '@/providers/control/ControlShellProvider';
import { ChevronLeft, Menu, Pin } from 'lucide-react';
import { navigationGroups, navigationItems } from '@/control/navigation.config';
import { ControlSidebarNavItem } from './ControlSidebarNavItem';
import { ControlSidebarGroup } from './ControlSidebarGroup';
import ControlProfileDropdown from '../profile/ControlProfileDropdown';
import AppLogoIcon from '@/components/app-logo-icon';

export default function ControlSidebar() {
    const { collapsed, setCollapsed, mobileDrawerOpen, setMobileDrawerOpen, pinnedItems } = useControlShell();

    const pinnedNavItems = useMemo(() => {
        return pinnedItems
            .map(pinRoute => navigationItems.find(item => item.route_name === pinRoute))
            .filter(Boolean) as typeof navigationItems;
    }, [pinnedItems]);

    const groupedItems = useMemo(() => {
        const result: Record<string, typeof navigationItems> = {
            _none: []
        };
        
        navigationGroups.forEach(g => {
            result[g.id] = [];
        });

        navigationItems.forEach(item => {
            if (!item.group) {
                result._none.push(item);
            } else if (result[item.group]) {
                result[item.group].push(item);
            }
        });

        return result;
    }, []);

    // Combine classes for responsive & drawer states
    const sidebarClasses = `
        fixed inset-y-0 left-0 z-50 flex flex-col bg-background border-r border-almost-black/10 transition-all duration-300 ease-in-out transform
        ${mobileDrawerOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0'}
        ${collapsed ? 'w-[var(--control-sidebar-collapsed)]' : 'w-[var(--control-sidebar-expanded)]'}
    `;

    return (
        <aside className={sidebarClasses} aria-label="Sidebar">
            {/* Branding Block */}
            <div className={`flex h-16 shrink-0 items-center px-4 bg-[#2c0133] text-white shadow-sm z-10 relative ${collapsed ? 'justify-center' : 'justify-between'}`}>
                <div className="flex items-center gap-3 overflow-hidden whitespace-nowrap">
                    <div className="shrink-0 flex items-center justify-center">
                        <AppLogoIcon className="h-7 w-auto" />
                    </div>
                    {!collapsed && (
                        <span className="text-lg font-bold font-heading tracking-tight text-white">
                            TraceMem
                        </span>
                    )}
                </div>
                
                {/* Desktop Collapse Toggle */}
                {!collapsed && (
                    <button 
                        onClick={() => setCollapsed(true)}
                        className="hidden md:flex p-1 hover:bg-white/20 rounded text-white/80 hover:text-white transition-colors"
                        aria-label="Collapse sidebar"
                    >
                        <ChevronLeft className="h-5 w-5" />
                    </button>
                )}
            </div>

            {/* Desktop Expand Toggle */}
            {collapsed && (
                <button 
                    onClick={() => setCollapsed(false)}
                    className="hidden md:flex h-12 w-full items-center justify-center border-b border-almost-black/10 hover:bg-almost-black/5 text-on-background/50 hover:text-on-background"
                    aria-label="Expand sidebar"
                >
                    <Menu className="h-5 w-5" />
                </button>
            )}

            {/* Navigation Scrolling Area */}
            <nav className="flex-1 overflow-y-auto py-4 no-scrollbar">
                
                {/* Pinned Section */}
                {pinnedNavItems.length > 0 && (
                    <ControlSidebarGroup id="pinned" label="Pinned" forceExpanded>
                        {pinnedNavItems.map(item => (
                            <ControlSidebarNavItem key={`pinned-${item.route_name}`} {...item} pinnable />
                        ))}
                    </ControlSidebarGroup>
                )}

                {/* Ungrouped Items */}
                {groupedItems._none.length > 0 && (
                    <div className={`mb-6 space-y-1 ${collapsed ? '' : 'px-2'}`}>
                        {groupedItems._none.map(item => (
                            <ControlSidebarNavItem key={item.route_name} {...item} pinnable />
                        ))}
                    </div>
                )}

                {/* Dynamic Groups */}
                {navigationGroups.map(group => {
                    const items = groupedItems[group.id];
                    if (!items || items.length === 0) return null;
                    
                    return (
                        <ControlSidebarGroup key={group.id} id={group.id} label={group.label}>
                            {items.map(item => (
                                <ControlSidebarNavItem key={item.route_name} {...item} pinnable />
                            ))}
                        </ControlSidebarGroup>
                    );
                })}
            </nav>

            {/* Profile Block - Fixed at Bottom */}
            <div className="shrink-0 border-t border-almost-black/10">
                <ControlProfileDropdown />
            </div>
        </aside>
    );
}
