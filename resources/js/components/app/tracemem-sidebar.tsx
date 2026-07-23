import { Link, usePage } from '@inertiajs/react';
import {
    LayoutGrid,
    KeyRound,
    Settings2,
    CreditCard,
    BookOpen,
    ArrowLeftFromLine,
    LogOut,
    ChevronUp,
    Activity,
    Building2,
} from 'lucide-react';
import { useState } from 'react';
import AppLogo from '@/components/app-logo';
import { WorkspaceSwitcher } from '@/components/workspace-switcher';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarTrigger,
    useSidebar,
} from '@/components/ui/sidebar';
import type { AccountContext } from '@/types';

/* ── Nav definition ─────────────────────────────────────── */
const mainNav = [
    { title: 'Dashboard', href: '/dashboard',  icon: LayoutGrid },
    { title: 'API Keys',  href: '/api-keys',   icon: KeyRound   },
    { title: 'Logs',      href: '/logs',        icon: Activity   },
    { title: 'Billing',   href: '/billing',     icon: CreditCard },
    { title: 'Settings',  href: '/settings',    icon: Settings2  },
];

// Company-only nav items (hidden for Individual accounts)
const companyNav = [
    { title: 'Workspaces', href: '/workspaces', icon: Building2 },
];

const footerNav = [
    { title: 'Documentation', href: '/docs', icon: BookOpen, external: true },
];

/* ── Single nav item ─────────────────────────────────────── */
function NavItem({
    href,
    icon: Icon,
    title,
    active,
    external = false,
}: {
    href: string;
    icon: React.ElementType;
    title: string;
    active: boolean;
    external?: boolean;
}) {
    const { state } = useSidebar();
    const collapsed = state === 'collapsed';

    const inner = (
        <>
            <Icon size={16} strokeWidth={1.8} />
            {!collapsed && <span>{title}</span>}
        </>
    );

    const cls = [
        'tracemem-nav-item',
        active ? 'tracemem-nav-item--active' : '',
    ].join(' ');

    if (external) {
        return (
            <a href={href} target="_blank" rel="noopener noreferrer" className={cls} title={collapsed ? title : undefined}>
                {inner}
            </a>
        );
    }

    return (
        <Link href={href} className={cls} title={collapsed ? title : undefined} prefetch>
            {inner}
        </Link>
    );
}

/* ── User dropdown in sidebar footer ─────────────────────── */
function SidebarUser() {
    const { auth } = usePage().props;
    const user = auth?.user;
    const [open, setOpen] = useState(false);
    const { state } = useSidebar();
    const collapsed = state === 'collapsed';

    if (!user) return null;

    const initials = user.name
        ? user.name.split(' ').map((p: string) => p[0]).join('').slice(0, 2).toUpperCase()
        : user.email?.[0]?.toUpperCase() ?? 'U';

    return (
        <div className="tracemem-sidebar-user" style={{ position: 'relative' }}>
            <button
                type="button"
                className={`tracemem-user-btn${collapsed ? ' tracemem-user-btn--collapsed' : ''}`}
                onClick={() => setOpen((v) => !v)}
                aria-expanded={open}
                title={collapsed ? (user.name || user.email) : undefined}
            >
                <span className="tracemem-user-avatar">{initials}</span>
                {!collapsed && (
                    <>
                        <span className="tracemem-user-info">
                            <span className="tracemem-user-name">{user.name || 'Account'}</span>
                            <span className="tracemem-user-email">{user.email}</span>
                        </span>
                        <ChevronUp
                            size={14}
                            className="tracemem-user-chevron"
                            style={{ transform: open ? 'rotate(0deg)' : 'rotate(180deg)', transition: 'transform 0.2s ease' }}
                        />
                    </>
                )}
            </button>

            {open && (
                <div className="tracemem-user-dropdown">
                    <Link
                        href="/settings"
                        className="tracemem-user-dd-item"
                        onClick={() => setOpen(false)}
                    >
                        <Settings2 size={13} />
                        Profile Settings
                    </Link>
                    <div className="tracemem-user-dd-sep" />
                    <Link
                        href="/logout"
                        method="post"
                        as="button"
                        className="tracemem-user-dd-item tracemem-user-dd-item--danger"
                        onClick={() => setOpen(false)}
                    >
                        <LogOut size={13} />
                        Sign Out
                    </Link>
                </div>
            )}
        </div>
    );
}

/* ── Main Sidebar ────────────────────────────────────────── */
export function TracememAppSidebar() {
    const { url } = usePage();
    const { state } = useSidebar();
    const collapsed = state === 'collapsed';
    const account = usePage().props.account as AccountContext | null;
    const isCompany = account?.isCompany ?? false;
    const hasWorkspaceUI = !!usePage().props.workspace;

    const isActive = (href: string) => {
        if (href === '/dashboard') return url === '/dashboard';
        return url.startsWith(href);
    };

    return (
        <Sidebar collapsible="icon" variant="inset" className="tracemem-sidebar">
            {/* ── Logo header ── */}
            <SidebarHeader className="tracemem-sidebar-header">
                <div className="tracemem-sidebar-logo-row">
                    <Link href="/dashboard" className="tracemem-sidebar-logo" prefetch>
                        <AppLogo />
                    </Link>
                    {/* Collapse trigger: only shown on mobile; desktop uses topbar trigger */}
                    <SidebarTrigger className="tracemem-sidebar-trigger tracemem-sidebar-trigger--mobile" />
                </div>

                {/* Workspace switcher — Company accounts or Individuals with >1 workspaces */}
                {hasWorkspaceUI && (
                    <div className="px-1 pt-1">
                        <WorkspaceSwitcher />
                    </div>
                )}
            </SidebarHeader>

            {/* ── Main nav ── */}
            <SidebarContent className="tracemem-sidebar-content">
                {!collapsed && (
                    <p className="tracemem-nav-group-label">Control Center</p>
                )}
                <nav className="tracemem-nav-list">
                    {mainNav.map((item) => (
                        <NavItem
                            key={item.href}
                            href={item.href}
                            icon={item.icon}
                            title={item.title}
                            active={isActive(item.href)}
                        />
                    ))}

                    {/* Company-only nav items */}
                    {isCompany && companyNav.map((item) => (
                        <NavItem
                            key={item.href}
                            href={item.href}
                            icon={item.icon}
                            title={item.title}
                            active={isActive(item.href)}
                        />
                    ))}
                </nav>

                <div className="tracemem-sidebar-divider" />

                {!collapsed && (
                    <p className="tracemem-nav-group-label">Resources</p>
                )}
                <nav className="tracemem-nav-list">
                    {footerNav.map((item) => (
                        <NavItem
                            key={item.href}
                            href={item.href}
                            icon={item.icon}
                            title={item.title}
                            active={false}
                            external={item.external}
                        />
                    ))}
                </nav>
            </SidebarContent>

            {/* ── Footer: back to website + user ── */}
            <SidebarFooter className="tracemem-sidebar-footer">
                <a href="/" className={`tracemem-back-website${collapsed ? ' collapsed' : ''}`} title={collapsed ? 'Back to website' : undefined}>
                    <ArrowLeftFromLine size={14} strokeWidth={1.8} />
                    {!collapsed && <span>Back to website</span>}
                </a>
                <SidebarUser />
            </SidebarFooter>
        </Sidebar>
    );
}
