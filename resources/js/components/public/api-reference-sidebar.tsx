import { Link, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { ChevronDown } from 'lucide-react';
import type { SidebarGroup } from './api-ref-nav';

type Props = {
    groups: SidebarGroup[];
};

export default function ApiReferenceSidebar({ groups }: Props) {
    const { url } = usePage();
    const [mobileOpen, setMobileOpen] = useState(false);

    const isActive = (href: string) => url === href || url.startsWith(`${href}/`);

    /* Find the currently active item label for the mobile pill */
    const activeLabel = groups
        .flatMap((g) => g.items)
        .find((item) => isActive(item.href))?.label ?? 'Navigate';

    return (
        <>
            {/* ── Mobile nav strip ────────────────────────────────────── */}
            <div className="api-sidebar-mobile">
                <button
                    type="button"
                    className="api-sidebar-mobile-trigger"
                    onClick={() => setMobileOpen((v) => !v)}
                    aria-expanded={mobileOpen}
                >
                    <span className="api-sidebar-mobile-label">{activeLabel}</span>
                    <ChevronDown
                        size={14}
                        style={{
                            transform: mobileOpen ? 'rotate(180deg)' : 'none',
                            transition: 'transform 0.2s ease',
                            flexShrink: 0,
                        }}
                    />
                </button>

                {mobileOpen && (
                    <div className="api-sidebar-mobile-panel">
                        {groups.map((group) => (
                            <div key={group.title} className="api-sidebar-mobile-group">
                                <div className="api-sidebar-mobile-group-title">{group.title}</div>
                                {group.items.map((item) => (
                                    <Link
                                        key={item.href}
                                        href={item.href}
                                        className={`api-sidebar-mobile-link ${isActive(item.href) ? 'active' : ''}`}
                                        onClick={() => setMobileOpen(false)}
                                    >
                                        <span className="api-sidebar-link-label">{item.label}</span>
                                        {item.subtitle && (
                                            <span className="api-sidebar-link-sub">{item.subtitle}</span>
                                        )}
                                    </Link>
                                ))}
                            </div>
                        ))}
                    </div>
                )}
            </div>

            {/* ── Desktop sidebar ─────────────────────────────────────── */}
            <aside className="api-ref-sidebar" aria-label="API Reference navigation">
                <div className="api-sidebar-brand">
                    <span className="api-sidebar-brand-label">API Reference</span>
                    <span className="api-sidebar-brand-version">v1</span>
                </div>

                {groups.map((group) => (
                    <div className="api-ref-group" key={group.title}>
                        <div className="api-ref-group-title">{group.title}</div>

                        <div className="api-ref-links">
                            {group.items.map((item) => (
                                <Link
                                    key={item.href}
                                    href={item.href}
                                    className={`api-ref-link ${isActive(item.href) ? 'active' : ''}`}
                                >
                                    <div className="api-ref-link-title">{item.label}</div>
                                    {item.subtitle && (
                                        <div className="api-ref-link-sub">{item.subtitle}</div>
                                    )}
                                </Link>
                            ))}
                        </div>
                    </div>
                ))}
            </aside>
        </>
    );
}