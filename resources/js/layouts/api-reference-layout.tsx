import { ReactNode, useState, useEffect } from 'react';
import { router } from '@inertiajs/react';
import PublicNavbar from '@/components/public/public-navbar';
import PublicFooter from '@/components/public/public-footer';
import { ContentSectionSkeleton } from '@/components/skeletons/PublicSkeletons';

export default function ApiReferenceLayout({ children }: { children: ReactNode }) {
    const [isLoading, setIsLoading] = useState(false);

    useEffect(() => {
        const removeStart = router.on('start', (e) => {
            const path = e.detail.visit.url.pathname;
            if (path.startsWith('/public') || path === '/docs' || path === '/Docs' || path.startsWith('/api-reference')) {
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
        <div className="public-shell">
            <PublicNavbar />
            <main className="public-main">
                <div style={{ display: isLoading ? 'none' : 'block' }}>
                    {children}
                </div>
                {isLoading && <ContentSectionSkeleton />}
            </main>
            <PublicFooter />
        </div>
    );
}
