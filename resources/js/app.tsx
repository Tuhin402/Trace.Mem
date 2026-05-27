import { createInertiaApp } from '@inertiajs/react';
import { TooltipProvider } from '@/components/ui/tooltip';
import { initializeTheme } from '@/hooks/use-appearance';
import AppLayout from '@/layouts/app-layout';
import AuthLayout from '@/layouts/auth-layout';
import SettingsLayout from '@/layouts/settings/layout';
import PublicLayout from '@/layouts/public-layout';
import ApiReferenceLayout from '@/layouts/api-reference-layout';
import ErrorBoundary from '@/components/error-boundary';

import '../css/public/api-reference.css';
import '../css/public/public.css';
import '../css/pages/landing.css';
import '../css/pages/docs.css';
import '../css/pages/pricing.css';
import '../css/pages/usecases.css';

// App shell design system
import '../css/app/app-shell.css';
import '../css/app/toast.css';
import '../css/app/sidebar.css';

// App page styles
import '../css/pages/dashboard.css';
import '../css/pages/apikeys.css';
import '../css/pages/settings.css';
import '../css/pages/billing.css';
import '../css/pages/logs.css';



// for SEO
import { HelmetProvider } from 'react-helmet-async';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';

const appName = import.meta.env.VITE_APP_NAME || 'TraceMem';
const appSlug = import.meta.env.VITE_APP_SLUG || 'tracemem';
// const appDomain = import.meta.env.VITE_APP_DOMAIN || 'tracemem.io';

// const withAuthLayout = (page: React.ReactNode) => <AuthLayout>{page}</AuthLayout>;
// const withApiReferenceLayout = (page: React.ReactNode) => <ApiReferenceLayout>{page}</ApiReferenceLayout>;
// const withPublicLayout = (page: React.ReactNode) => <PublicLayout>{page}</PublicLayout>;
// const withSettingsLayout = (page: React.ReactNode) => (
//     <AppLayout>
//         <SettingsLayout>{page}</SettingsLayout>
//     </AppLayout>
// );
// const withAppLayout = (page: React.ReactNode) => <AppLayout>{page}</AppLayout>;

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout: (name) => {
        switch (true) {
            case name === 'welcome':
                return null;

            case name.startsWith('auth/'):
                return AuthLayout;

            case name.startsWith('public/api-reference'):
                return ApiReferenceLayout;

            case name === 'public/Docs':
                return ApiReferenceLayout;

            case name === 'public/Pricing':
                return ApiReferenceLayout;

            case name.startsWith('public/'):
                return PublicLayout;

            case name.startsWith('settings/'):
            case name.startsWith('teams/'):
                return [AppLayout, SettingsLayout];

            default:
                return AppLayout;
        }
    },
    // resolve: (name) => {
    //     return resolvePageComponent(`./pages/${name}.tsx`, import.meta.glob('./pages/**/*.tsx'))
    //         .then((page: any) => {
    //             const component = page.default || page;
                
    //             if (component.layout === undefined) {
    //                 if (name.startsWith('auth/')) {
    //                     component.layout = withAuthLayout;
    //                 } else if (name.startsWith('public/api-reference') || name === 'public/Docs' || name === 'public/Pricing') {
    //                     component.layout = withApiReferenceLayout;
    //                 } else if (name.startsWith('public/')) {
    //                     component.layout = withPublicLayout;
    //                 } else if (name === 'app/Settings' || name.startsWith('settings/')) {
    //                     component.layout = withSettingsLayout;
    //                 } else {
    //                     component.layout = withAppLayout;
    //                 }
    //             }
                
    //             return page;
    //         });
    // },
    // strictMode: true,
    withApp(app) {
        return (
            <HelmetProvider>
                <ErrorBoundary>
                    <TooltipProvider delayDuration={0}>
                        {app}
                    </TooltipProvider>
                </ErrorBoundary>
            </HelmetProvider>
        );
    },
    progress: {
        color: '#4B5563',
    },
});

initializeTheme();