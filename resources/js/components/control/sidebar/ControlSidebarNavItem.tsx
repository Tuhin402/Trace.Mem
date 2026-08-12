import { Link, usePage } from '@inertiajs/react';
import { useControlShell } from '@/providers/control/ControlShellProvider';
import { ControlTooltip } from '@/components/control/ui/ControlTooltip';
import { Pin, PinOff } from 'lucide-react';
import * as Icons from 'lucide-react';

interface NavItemProps {
    route_name: string;
    title: string;
    icon: string;
    pinnable?: boolean;
}

export function ControlSidebarNavItem({ route_name, title, icon, pinnable = false }: NavItemProps) {
    const { collapsed, pinnedItems, togglePin } = useControlShell();
    const { url } = usePage();
    
    // Exact match vs partial match based on route (Inertia doesn't give us named route easily in props, 
    // so we parse the URL or pass it down. For now, since we asked for route name match, we can use ziggy if available, 
    // or just match URL paths roughly for active state since we have the config).
    // Assuming Ziggy is available via route().current() in a real app, but we will mock active state by URL path for now:
    // Strip trailing Laravel resource action suffixes (e.g. `.index`, `.create`)
    // so 'control.platform.billing.catalog.index' → 'platform/billing/catalog'
    const actionSuffixes = ['.index', '.show', '.create', '.store', '.edit', '.update', '.destroy'];
    const normalizedName = actionSuffixes.reduce(
        (name, suffix) => name.endsWith(suffix) ? name.slice(0, -suffix.length) : name,
        route_name
    );
    const pathSegment = normalizedName.replace('control.', '').replaceAll('.', '/');
    const isActive = url.includes(`/${pathSegment}`) || (route_name === 'control.overview' && url === '/overview');
    
    const isPinned = pinnedItems.includes(route_name);
    
    // @ts-ignore - Dynamic icon rendering
    const IconComponent = Icons[icon] || Icons.HelpCircle;

    const content = (
        <Link 
            href={`/${pathSegment}`}
            className={`group w-full relative flex items-center py-2 text-sm font-medium transition-colors ${
                collapsed ? 'justify-center pr-1' : 'gap-3 px-3'
            } ${
                isActive 
                    ? 'bg-primary/10 text-primary border-l-4 border-primary rounded-r' 
                    : 'text-on-background/70 hover:bg-almost-black/5 hover:text-on-background border-l-4 border-transparent rounded-r'
            }`}
            aria-current={isActive ? 'page' : undefined}
        >
            <span className="shrink-0">
                <IconComponent className="h-5 w-5" />
            </span>
            
            {!collapsed && (
                <div className="flex flex-1 justify-between items-center overflow-hidden">
                    <span className="truncate">{title}</span>
                    
                    {pinnable && (
                        <button
                            type="button"
                            onClick={(e) => {
                                e.preventDefault();
                                e.stopPropagation();
                                togglePin(route_name);
                            }}
                            className={`opacity-0 group-hover:opacity-100 transition-opacity p-1 rounded hover:bg-almost-black/10 ${
                                isPinned ? 'opacity-100 text-primary' : 'text-on-background/40'
                            }`}
                            aria-label={isPinned ? 'Unpin item' : 'Pin item'}
                        >
                            {isPinned ? <Pin className="h-3 w-3 fill-current" /> : <Pin className="h-3 w-3" />}
                        </button>
                    )}
                </div>
            )}
        </Link>
    );

    if (collapsed) {
        return (
            <li className="list-none w-full">
                <ControlTooltip label={title} delay={100} className="flex w-full">
                    {content}
                </ControlTooltip>
            </li>
        );
    }

    return <li className="list-none">{content}</li>;
}
