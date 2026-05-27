import { Link, usePage } from '@inertiajs/react';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { AppContent } from '@/components/app-content';
import { AppShell } from '@/components/app-shell';
import { TracememAppSidebar } from '@/components/app/tracemem-sidebar';
import type { AppLayoutProps } from '@/types';
import type { BreadcrumbItem } from '@/types';

/* ── Minimal top bar ─────────────────────────────────────── */
function AppTopBar({ breadcrumbs = [] }: { breadcrumbs?: BreadcrumbItem[] }) {
    const { url } = usePage();

    // Derive page title from URL as a fallback
    const pageLabel = (() => {
        if (url.startsWith('/dashboard')) return 'Dashboard';
        if (url.startsWith('/api-keys'))  return 'API Keys';
        if (url.startsWith('/billing'))   return 'Billing';
        if (url.startsWith('/settings'))  return 'Settings';
        if (url.startsWith('/teams'))     return 'Teams';
        return breadcrumbs?.[breadcrumbs.length - 1]?.title ?? '';
    })();

    return (
        <header className="app-topbar">
            <div className="app-topbar-left">
                <SidebarTrigger className="app-topbar-trigger" aria-label="Toggle sidebar" />
                {pageLabel && (
                    <span className="app-topbar-page-label">{pageLabel}</span>
                )}
            </div>

            <div className="app-topbar-right">
                <Link href="/" className="app-back-btn">
                    <svg width="12" height="12" viewBox="0 0 12 12" fill="none" aria-hidden="true">
                        <path d="M7.5 2L3.5 6L7.5 10" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"/>
                    </svg>
                    Back to website
                </Link>
            </div>
        </header>
    );
}

/* ── Layout ──────────────────────────────────────────────── */
export default function AppSidebarLayout({
    children,
    breadcrumbs = [],
}: AppLayoutProps) {
    return (
        <AppShell variant="sidebar">
            <TracememAppSidebar />
            <AppContent variant="sidebar" className="overflow-x-hidden">
                <AppTopBar breadcrumbs={breadcrumbs} />
                {children}
            </AppContent>
        </AppShell>
    );
}
