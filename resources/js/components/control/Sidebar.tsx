import { useState } from 'react';
import { Link } from '@inertiajs/react';
import { 
    LayoutDashboard, 
    Users, 
    Settings, 
    Menu, 
    ChevronLeft, 
    Search, 
    Database, 
    Shield,
    CreditCard
} from 'lucide-react';

export default function Sidebar() {
    const [collapsed, setCollapsed] = useState(false);

    return (
        <aside 
            className={`fixed inset-y-0 left-0 z-50 flex flex-col bg-background border-r border-almost-black/10 transition-all duration-300 ${
                collapsed ? 'w-16' : 'w-64'
            }`}
        >
            {/* Branding Block - Darkest Theme Color */}
            <div className="flex h-16 shrink-0 items-center justify-between px-4 bg-primary text-on-primary">
                <div className="flex items-center gap-2 overflow-hidden whitespace-nowrap">
                    {/* Placeholder for Logo */}
                    <div className="h-8 w-8 shrink-0 rounded-none bg-white text-primary flex items-center justify-center font-bold font-mono">
                        TM
                    </div>
                    {!collapsed && (
                        <span className="text-lg font-bold font-heading tracking-tight">
                            Operations
                        </span>
                    )}
                </div>
                {!collapsed && (
                    <button 
                        onClick={() => setCollapsed(true)}
                        className="p-1 hover:bg-white/10 rounded-none transition-colors"
                    >
                        <ChevronLeft className="h-5 w-5" />
                    </button>
                )}
            </div>

            {collapsed && (
                <button 
                    onClick={() => setCollapsed(false)}
                    className="flex h-12 w-full items-center justify-center border-b border-almost-black/10 hover:bg-almost-black/5"
                >
                    <Menu className="h-5 w-5" />
                </button>
            )}

            {/* Navigation */}
            <nav className="flex-1 overflow-y-auto py-4 no-scrollbar">
                <ul className="space-y-1 px-2">
                    <NavItem 
                        href="/dashboard" 
                        icon={<LayoutDashboard className="h-5 w-5" />} 
                        label="Overview" 
                        collapsed={collapsed} 
                        active={true}
                    />
                    <NavItem 
                        href="/users" 
                        icon={<Users className="h-5 w-5" />} 
                        label="Users" 
                        collapsed={collapsed} 
                    />
                    <NavItem 
                        href="/tenants" 
                        icon={<Database className="h-5 w-5" />} 
                        label="Tenants" 
                        collapsed={collapsed} 
                    />
                    <NavItem 
                        href="/billing" 
                        icon={<CreditCard className="h-5 w-5" />} 
                        label="Billing" 
                        collapsed={collapsed} 
                    />
                    <NavItem 
                        href="/platform" 
                        icon={<Shield className="h-5 w-5" />} 
                        label="Platform Jobs" 
                        collapsed={collapsed} 
                    />
                    <NavItem 
                        href="/settings" 
                        icon={<Settings className="h-5 w-5" />} 
                        label="Settings" 
                        collapsed={collapsed} 
                    />
                </ul>
            </nav>
        </aside>
    );
}

function NavItem({ href, icon, label, collapsed, active = false }: any) {
    return (
        <li>
            <Link 
                href={`/control${href}`}
                className={`group flex items-center gap-3 px-3 py-2 text-sm font-medium transition-colors ${
                    active 
                        ? 'bg-almost-black/5 text-primary border-l-4 border-primary' 
                        : 'text-on-background/70 hover:bg-almost-black/5 hover:text-on-background border-l-4 border-transparent'
                }`}
                title={collapsed ? label : undefined}
            >
                <span className="shrink-0">{icon}</span>
                {!collapsed && <span>{label}</span>}
            </Link>
        </li>
    );
}
