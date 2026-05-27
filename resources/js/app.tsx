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

const appName = import.meta.env.VITE_APP_NAME || 'TraceMem';
const appSlug = import.meta.env.VITE_APP_SLUG || 'tracemem';
// const appDomain = import.meta.env.VITE_APP_DOMAIN || 'tracemem.io';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    // layout: (name) => {
    //     switch (true) {
    //         case name === 'welcome':
    //             return null;

    //         case name.startsWith('auth/'):
    //             return AuthLayout;

    //         case name.startsWith('public/api-reference'):
    //             return ApiReferenceLayout;

    //         case name === 'public/Docs':
    //             return ApiReferenceLayout;

    //         case name === 'public/Pricing':
    //             return ApiReferenceLayout;

    //         case name.startsWith('public/'):
    //             return PublicLayout;

    //         case name.startsWith('settings/'):
    //         case name.startsWith('teams/'):
    //             return [AppLayout, SettingsLayout];

    //         default:
    //             return AppLayout;
    //     }
    // },
    resolve: async (name) => {
        const pages = import.meta.glob('./pages/**/*.tsx');
        const page = await pages[`./pages/${name}.tsx`]();
        const component = page.default;
    
        if (name.startsWith('auth/')) {
            component.layout = (page: React.ReactNode) => (
                <AuthLayout>{page}</AuthLayout>
            );
        } else if (name.startsWith('public/api-reference') || name === 'public/Docs' || name === 'public/Pricing') {
            component.layout = (page: React.ReactNode) => (
                <ApiReferenceLayout>{page}</ApiReferenceLayout>
            );
        } else if (name.startsWith('public/')) {
            component.layout = (page: React.ReactNode) => (
                <PublicLayout>{page}</PublicLayout>
            );
        } else if (name.startsWith('settings/')) {
            component.layout = (page: React.ReactNode) => (
                <AppLayout>
                    <SettingsLayout>{page}</SettingsLayout>
                </AppLayout>
            );
        } else {
            component.layout = (page: React.ReactNode) => (
                <AppLayout>{page}</AppLayout>
            );
        }
    
        return component;
    },
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