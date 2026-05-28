import { Link } from '@inertiajs/react';
import { home } from '@/routes';
import type { AuthLayoutProps } from '@/types';
import AppLogo from '@/components/app-logo';
import AuthPromoPanel from '@/components/public/auth-promo-panel';
import '../../../css/pages/auth.css';
import { useEffect, useState } from 'react';
import { router } from '@inertiajs/react';
import { AuthSkeleton } from '@/components/skeletons/PublicSkeletons';

export default function AuthSimpleLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    const [isLoading, setIsLoading] = useState(false);

    useEffect(() => {
        const removeStart = router.on('start', (e) => {
            if (e.detail.visit.url.pathname.startsWith('/auth')) {
                setIsLoading(true);
            }
        });

        const removeFinish = router.on('finish', () => {
            setIsLoading(false);
        });

        return () => {
            removeStart();
            removeFinish();
        };
    }, []);

    return (
        <div className="auth-shell">
            {/* ── Left: Form Panel ── */}
            <div className="auth-left">
                {/* Logo */}
                <Link href={home()} className="auth-logo-wrap" aria-label="TraceMem home">
                    <AppLogo />
                </Link>

                {/* Form card */}
                <div className="auth-card">
                    {title && (
                        <h1 className="auth-page-title">{title}</h1>
                    )}
                    {description && (
                        <p className="auth-page-desc">{description}</p>
                    )}

                    <div style={{ display: isLoading ? 'none' : 'block' }}>
                        {children}
                    </div>
                    {isLoading && <AuthSkeleton />}
                </div>
            </div>

            {/* ── Right: Promo Panel (hidden on mobile) ── */}
            <AuthPromoPanel />
        </div>
    );
}
