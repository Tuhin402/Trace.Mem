/**
 * useDomains — Frontend domain constants hook
 *
 * Reads domain configuration from Inertia shared props (set by HandleInertiaRequests).
 * Falls back to production values so code-snippet strings are always meaningful.
 *
 * Usage:
 *   const { siteUrl, apiUrl, appUrl } = useDomains();
 *
 *   // In JSX:
 *   <meta property="og:url" content={`${siteUrl}/docs`} />
 *
 *   // In code snippet strings:
 *   python: `requests.post("${apiUrl}/v1/remember", ...)`
 *
 * Why Inertia shared props instead of import.meta.env?
 *   Vite VITE_* variables are baked in at build time. Inertia shared props are
 *   injected at request time from PHP config, so a single build works in every
 *   environment (local, staging, production) without rebuilding.
 */

import { usePage } from '@inertiajs/react';

interface DomainProps {
    root: string;
    app:  string;
    api:  string;
}

export function useDomains() {
    const props = usePage<{ domains?: DomainProps }>().props;

    const root = props.domains?.root ?? 'tracemem.one';
    const app  = props.domains?.app  ?? 'app.tracemem.one';
    const api  = props.domains?.api  ?? 'api.tracemem.one';

    return {
        /** Root domain: tracemem.one */
        root,
        /** App subdomain: app.tracemem.one */
        app,
        /** API subdomain: api.tracemem.one */
        api,
        /** Full HTTPS URL for the public site: https://tracemem.one */
        siteUrl: `https://${root}`,
        /** Full HTTPS URL for the dashboard: https://app.tracemem.one */
        appUrl:  `https://${app}`,
        /** Full HTTPS base URL for the API: https://api.tracemem.one */
        apiUrl:  `https://${api}`,
    };
}
