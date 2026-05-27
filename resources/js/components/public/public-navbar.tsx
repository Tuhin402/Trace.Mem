import { Link, usePage } from '@inertiajs/react';
import { useEffect, useMemo, useRef, useState } from 'react';
import { ChevronDown, ChevronRight, Plus, X } from 'lucide-react';
import AppLogo from '@/components/app-logo';
import CtaButton from './cta-button';

type AuthUser = { id: number; name: string };
type PageProps  = { auth?: { user?: AuthUser | null } };

const devLinks = [
    {
        label: 'Docs',
        subtitle: 'Guides, tutorials & onboarding',
        href: '/docs',
    },
    {
        label: 'API Reference',
        subtitle: 'Endpoints, auth & examples',
        href: '/api-reference',
    },
];

const navLinks = [
    { label: 'Pricing',   href: '/pricing' },
    { label: 'Use Cases', href: '/usecases' },
    { label: 'Docs',      href: '/docs' },
];

export default function PublicNavbar() {
    const { url, props } = usePage<PageProps>();

    const [menuOpen, setMenuOpen] = useState(false);
    const [devOpen,  setDevOpen]  = useState(false);
    const [mobDevOpen, setMobDevOpen] = useState(false);

    /* Ref for click-outside detection on the Developers dropdown */
    const devGroupRef = useRef<HTMLDivElement>(null);

    /* Close dropdown when clicking anywhere outside the dev-group */
    useEffect(() => {
        if (!devOpen) return;
        const handler = (e: MouseEvent) => {
            if (devGroupRef.current && !devGroupRef.current.contains(e.target as Node)) {
                setDevOpen(false);
            }
        };
        document.addEventListener('mousedown', handler);
        return () => document.removeEventListener('mousedown', handler);
    }, [devOpen]);
    // navigation state controls menu state
    useEffect(() => {
        setDevOpen(false);
        setMenuOpen(false);
        setMobDevOpen(false);
    }, [url]);

    const isLoggedIn     = !!props.auth?.user;
    const getStartedHref = useMemo(() => (isLoggedIn ? '/dashboard' : '/register'), [isLoggedIn]);

    const active = (href: string) => url === href || url.startsWith(`${href}/`);

    return (
        <header className="pub-nav-shell">
            <div className="pub-nav-inner">
                {/* Logo */}
                <Link href="/" className="pub-nav-logo" aria-label="TraceMem home">
                    <AppLogo />
                </Link>

                {/* Desktop center nav */}
                <nav className="pub-nav-links" aria-label="Main navigation">
                    {/* Developers dropdown — click to toggle, click-outside to close */}
                    <div className="dev-group" ref={devGroupRef}>
                        <button
                            type="button"
                            className={`pub-link ${active('/api-reference') || active('/docs') ? 'active' : ''}`}
                            aria-haspopup="true"
                            aria-expanded={devOpen}
                            onClick={() => setDevOpen((v) => !v)}
                        >
                            Developers
                            <ChevronDown
                                size={13}
                                style={{
                                    transform: devOpen ? 'rotate(180deg)' : 'none',
                                    transition: 'transform 0.2s ease',
                                }}
                            />
                        </button>

                        {devOpen && (
                            <div className="dev-dropdown" role="menu">
                                <div className="dev-dropdown-links">
                                    {devLinks.map((item) => (
                                        <Link
                                            key={item.href}
                                            href={item.href}
                                            className="dev-drop-item"
                                            role="menuitem"
                                        >
                                            <div className="dev-drop-title">{item.label}</div>
                                            <div className="dev-drop-sub">{item.subtitle}</div>
                                        </Link>
                                    ))}
                                </div>

                                <div className="dev-dropdown-preview" aria-hidden="true">
                                    <div className="dev-preview-label">Developer Docs</div>
                                    <div className="dev-preview-text">
                                        TraceMem API references, starter guides, and integration examples.
                                    </div>
                                </div>
                            </div>
                        )}
                    </div>

                    {/* Remaining links */}
                    {navLinks.map((item) => (
                        <Link
                            key={item.href}
                            href={item.href}
                            className={`pub-link ${active(item.href) ? 'active' : ''}`}
                        >
                            {item.label}
                        </Link>
                    ))}
                </nav>

                {/* Desktop CTA */}
                <div className="pub-nav-right">
                    <CtaButton href={getStartedHref} label="Get Started" />
                </div>

                {/* Mobile toggle */}
                <button
                    type="button"
                    className="nav-mobile-toggle"
                    onClick={() => setMenuOpen((v) => !v)}
                    aria-label={menuOpen ? 'Close menu' : 'Open menu'}
                    aria-expanded={menuOpen}
                >
                    {menuOpen ? <X size={16} /> : <Plus size={16} />}
                    <span>{menuOpen ? 'Close' : 'Menu'}</span>
                </button>
            </div>

            {/* Mobile slide-down panel */}
            {menuOpen && (
                <div className="mob-panel" role="dialog" aria-label="Mobile navigation">
                    {/* Developers group */}
                    <div>
                        <button
                            type="button"
                            className={`mob-group-btn ${mobDevOpen ? 'open' : ''}`}
                            onClick={() => setMobDevOpen((v) => !v)}
                        >
                            Developers
                            <ChevronRight size={15} className="chevron" />
                        </button>

                        {mobDevOpen && (
                            <div className="mob-sublinks">
                                {devLinks.map((item) => (
                                    <Link
                                        key={item.href}
                                        href={item.href}
                                        className="mob-sub-link"
                                    >
                                        <div className="mob-sub-title">{item.label}</div>
                                        <div className="mob-sub-sub">{item.subtitle}</div>
                                    </Link>
                                ))}
                            </div>
                        )}
                    </div>

                    {/* Other links */}
                    {navLinks.map((item) => (
                        <Link
                            key={item.href}
                            href={item.href}
                            className={`mob-link ${active(item.href) ? 'active' : ''}`}
                        >
                            {item.label}
                        </Link>
                    ))}

                    {/* CTA */}
                    <div className="mob-cta">
                        <CtaButton href={getStartedHref} label="Get Started" />
                    </div>
                </div>
            )}
        </header>
    );
}