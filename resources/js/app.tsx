import { createRoot } from 'react-dom/client';
import { createInertiaApp } from '@inertiajs/react';

declare global {
    interface Window {
        __REACT_ROOT__?: any;
    }
}
import { TooltipProvider } from '@/components/ui/tooltip';
import { initializeTheme } from '@/hooks/use-appearance';
import AppLayout from '@/layouts/app-layout';
import AuthLayout from '@/layouts/auth-layout';
import SettingsLayout from '@/layouts/settings/layout';
import PublicLayout from '@/layouts/public-layout';
import ApiReferenceLayout from '@/layouts/api-reference-layout';
import ErrorBoundary from '@/components/error-boundary';
import { useEffect, type ReactNode } from 'react';

import '../css/public/api-reference.css';
import '../css/public/public.css';
import '../css/pages/landing.css';
import '../css/pages/playground.css';
import '../css/pages/status.css';
import '../css/pages/docs.css';
import '../css/pages/pricing.css';
import '../css/pages/usecases.css';
import '../css/pages/legal.css';

// App shell design system
import '../css/app/app-shell.css';
import '../css/app/toast.css';
import '../css/app/sidebar.css';
import '../css/app/loader.css';
import '../css/app/skeletons.css';
import '../css/app/floating-video.css';

// App page styles
import '../css/pages/dashboard.css';
import '../css/pages/apikeys.css';
import '../css/pages/settings.css';
import '../css/pages/billing.css';
import '../css/pages/logs.css';
import '../css/pages/observability.css';
import '../css/pages/memory-inspector.css';



// for SEO
import { HelmetProvider } from 'react-helmet-async';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';

const appName = import.meta.env.VITE_APP_NAME || 'TraceMem';
// ── Persistent layout wrappers ─────────────────────────────
const withPublicLayout = (page: ReactNode) => <PublicLayout>{page}</PublicLayout>;
const withApiRefLayout = (page: ReactNode) => <ApiReferenceLayout>{page}</ApiReferenceLayout>;
const withAuthLayout   = (page: ReactNode) => <AuthLayout>{page}</AuthLayout>;
const withAppLayout    = (page: ReactNode) => <AppLayout>{page}</AppLayout>;
const withSettingsLayout = (page: ReactNode) => (
    <AppLayout><SettingsLayout>{page}</SettingsLayout></AppLayout>
);

// ── FOUC prevention: marks #app as hydrated once React mounts ──
function HydrationGate({ children }: { children: ReactNode }) {
    useEffect(() => {
        const el = document.getElementById('app');
        if (el) el.classList.add('hydrated');
    }, []);
    return <>{children}</>;
}

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) => {
        return resolvePageComponent(
            `./pages/${name}.tsx`,
            import.meta.glob('./pages/**/*.tsx'),
        ).then((page: any) => {
            const component = page.default || page;

            if (component.layout === undefined) {
                switch (true) {
                    case name === 'welcome':
                        break; // Landing page manages its own chrome

                    case name.startsWith('auth/'):
                        component.layout = withAuthLayout;
                        break;

                    case name.startsWith('public/api-reference'):
                    case name === 'public/Docs':
                    case name === 'public/Pricing':
                        component.layout = withApiRefLayout;
                        break;

                    case name.startsWith('public/'):
                        component.layout = withPublicLayout;
                        break;

                    case name.startsWith('settings/'):
                    case name.startsWith('teams/'):
                        component.layout = withSettingsLayout;
                        break;

                    default:
                        component.layout = withAppLayout;
                        break;
                }
            }

            return page;
        });
    },
    setup({ el, App, props }) {
        const appElement = (
            <HelmetProvider>
                <ErrorBoundary>
                    <TooltipProvider delayDuration={0}>
                        <HydrationGate>
                            <App {...props} />
                        </HydrationGate>
                    </TooltipProvider>
                </ErrorBoundary>
            </HelmetProvider>
        );

        if (!window.__REACT_ROOT__ && el) {
            window.__REACT_ROOT__ = createRoot(el);
        }
        window.__REACT_ROOT__!.render(appElement);
    },
    progress: {
        color: '#de91efff',
    },
});

initializeTheme();