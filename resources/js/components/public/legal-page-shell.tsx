import { ReactNode, useEffect, useRef, useState } from 'react';
import { Link } from '@inertiajs/react';
import { ChevronDown, Link2 } from 'lucide-react';

/* ─── Types ──────────────────────────────────────────────── */
export type TocEntry = {
    id: string;
    num: string;
    label: string;
};

/* ─── Reading Progress Bar ───────────────────────────────── */
export function ReadingProgressBar() {
    const [pct, setPct] = useState(0);

    useEffect(() => {
        const update = () => {
            const el = document.documentElement;
            const scrolled = el.scrollTop;
            const total = el.scrollHeight - el.clientHeight;
            setPct(total > 0 ? (scrolled / total) * 100 : 0);
        };
        window.addEventListener('scroll', update, { passive: true });
        return () => window.removeEventListener('scroll', update);
    }, []);

    return (
        <div
            className="legal-progress-bar"
            style={{ width: `${pct}%` }}
            role="progressbar"
            aria-valuenow={Math.round(pct)}
            aria-valuemin={0}
            aria-valuemax={100}
            aria-label="Reading progress"
        />
    );
}

/* ─── Desktop Sticky TOC ──────────────────────────────────── */
export function DesktopToc({
    entries,
    activeId,
}: {
    entries: TocEntry[];
    activeId: string;
}) {
    return (
        <nav className="legal-toc" aria-label="Table of contents">
            <span className="legal-toc-label">Contents</span>
            <ul className="legal-toc-list" role="list">
                {entries.map((e) => (
                    <li key={e.id} className="legal-toc-item">
                        <a
                            href={`#${e.id}`}
                            className={`legal-toc-link ${activeId === e.id ? 'active' : ''}`}
                            aria-current={activeId === e.id ? 'true' : undefined}
                        >
                            <span className="legal-toc-num">{e.num}</span>
                            {e.label}
                        </a>
                    </li>
                ))}
            </ul>
        </nav>
    );
}

/* ─── Mobile TOC Accordion ────────────────────────────────── */
export function MobileToc({ entries }: { entries: TocEntry[] }) {
    const [open, setOpen] = useState(false);

    return (
        <div className="legal-mob-toc" role="navigation" aria-label="Table of contents">
            <button
                type="button"
                className={`legal-mob-toc-btn ${open ? 'open' : ''}`}
                onClick={() => setOpen((v) => !v)}
                aria-expanded={open}
                aria-controls="legal-mob-toc-body"
            >
                <span>Contents</span>
                <ChevronDown size={14} className="legal-mob-toc-chevron" />
            </button>
            <div
                id="legal-mob-toc-body"
                className={`legal-mob-toc-body ${open ? 'open' : ''}`}
            >
                <ul className="legal-mob-toc-list" role="list">
                    {entries.map((e) => (
                        <li key={e.id}>
                            <a
                                href={`#${e.id}`}
                                className="legal-mob-toc-link"
                                onClick={() => setOpen(false)}
                            >
                                <span className="legal-mob-toc-num">{e.num}</span>
                                {e.label}
                            </a>
                        </li>
                    ))}
                </ul>
            </div>
        </div>
    );
}

/* ─── Legal Hero ──────────────────────────────────────────── */
export function LegalHero({
    eyebrow,
    title,
    subtitle,
    lastUpdated,
    readingTime,
}: {
    eyebrow: string;
    title: string;
    subtitle: string;
    lastUpdated: string;
    readingTime: string;
}) {
    return (
        <section className="legal-hero" aria-label={title}>
            <span className="legal-hero-eyebrow">{eyebrow}</span>
            <h1 className="legal-hero-title">{title}</h1>
            <p className="legal-hero-subtitle">{subtitle}</p>
            <div className="legal-hero-meta" role="contentinfo" aria-label="Document metadata">
                <div className="legal-meta-item">
                    <span className="legal-meta-label">Last updated</span>
                    <span className="legal-meta-value">{lastUpdated}</span>
                </div>
                <div className="legal-meta-item">
                    <span className="legal-meta-label">Reading time</span>
                    <span className="legal-meta-value">{readingTime}</span>
                </div>
            </div>
        </section>
    );
}

/* ─── Legal Section ───────────────────────────────────────── */
export function LegalSection({
    id,
    num,
    title,
    children,
}: {
    id: string;
    num: string;
    title: string;
    children: ReactNode;
}) {
    const ref = useRef<HTMLElement>(null);

    useEffect(() => {
        const el = ref.current;
        if (!el) return;
        const observer = new IntersectionObserver(
            ([entry]) => {
                if (entry.isIntersecting) {
                    el.classList.add('visible');
                    observer.disconnect();
                }
            },
            { threshold: 0.07, rootMargin: '0px 0px -60px 0px' },
        );
        observer.observe(el);
        return () => observer.disconnect();
    }, []);

    const handlePermalink = () => {
        const url = `${window.location.pathname}#${id}`;
        history.pushState(null, '', url);
        navigator.clipboard?.writeText(window.location.href).catch(() => {});
    };

    return (
        <section id={id} className="legal-section" ref={ref}>
            <div className="legal-section-heading">
                <span className="legal-heading-num">{num}</span>
                <h2 className="legal-h2">{title}</h2>
                <button
                    type="button"
                    className="legal-permalink"
                    onClick={handlePermalink}
                    aria-label={`Copy link to ${title} section`}
                    title="Copy section link"
                >
                    <Link2 size={13} />
                </button>
            </div>
            {children}
        </section>
    );
}

/* ─── Legal Page Shell (full layout) ─────────────────────── */
export function LegalPageShell({
    entries,
    hero,
    children,
}: {
    entries: TocEntry[];
    hero: ReactNode;
    children: ReactNode;
}) {
    const [activeId, setActiveId] = useState('');

    /* Intersection observer to track active TOC item */
    useEffect(() => {
        const ids = entries.map((e) => e.id);
        const observers: IntersectionObserver[] = [];

        const callback: IntersectionObserverCallback = (entriesObs) => {
            for (const entry of entriesObs) {
                if (entry.isIntersecting) {
                    setActiveId(entry.target.id);
                }
            }
        };

        ids.forEach((id) => {
            const el = document.getElementById(id);
            if (!el) return;
            const obs = new IntersectionObserver(callback, {
                rootMargin: '-20% 0px -70% 0px',
            });
            obs.observe(el);
            observers.push(obs);
        });

        return () => observers.forEach((o) => o.disconnect());
    }, [entries]);

    return (
        <div className="legal-page">
            <ReadingProgressBar />
            {hero}

            {/* Mobile TOC rendered inside body wrapper but above content */}
            <div className="legal-body">
                {/* Desktop sticky TOC */}
                <DesktopToc entries={entries} activeId={activeId} />

                {/* Content */}
                <div className="legal-content">
                    {/* Mobile TOC sits at top of content area */}
                    <MobileToc entries={entries} />
                    {children}
                </div>
            </div>
        </div>
    );
}
