import { Head } from '@inertiajs/react';
import { Helmet } from 'react-helmet-async';
import ApiReferencePage from '@/components/public/api-reference-page';
import { apiRefGroups } from '@/components/public/api-ref-nav';
import { useDomains } from '@/lib/domains';

export default function Overview() {
    const { siteUrl } = useDomains();
    return (
        <>
            <Helmet>
                <title>API Reference Overview | TraceMem</title>
                <meta name="description" content="Official TraceMem API reference. Explore endpoints and integrate persistent, semantic, and AI-assembled memory into your applications using our REST API." />
                <meta name="keywords" content="TraceMem API, memory API, REST API, semantic memory, persistent memory, API docs, LLM memory API, TraceMem developer, AI memory integration" />
                <meta property="og:title" content="API Reference | TraceMem" />
                <meta property="og:description" content="Full documentation for the TraceMem REST API. Learn how to store, recall, and contextually assemble memory for modern AI and LLM apps." />
                <meta property="og:type" content="website" />
                <meta property="og:url" content={`${siteUrl}/api-reference`} />
                <meta property="og:image" content={`${siteUrl}/og-image.png`} />
                <meta property="og:image:width"  content="1200" />
                <meta property="og:image:height" content="630" />
                <meta property="og:image:alt"    content="TraceMem - Long-Term Memory Infrastructure for AI" />
                <meta property="og:site_name"    content="TraceMem" />
                <meta property="og:locale"       content="en_US" />
                <meta name="twitter:card"        content="summary_large_image" />
                <meta name="twitter:title"       content="API Reference | TraceMem" />
                <meta name="twitter:description" content="Full documentation for the TraceMem REST API. Learn how to store, recall, and contextually assemble memory for AI apps." />
                <meta name="twitter:image"       content={`${siteUrl}/og-image.png`} />
                <meta name="twitter:image:alt"   content="TraceMem - Long-Term Memory Infrastructure for AI" />
                <link rel="canonical" href={`${siteUrl}/api-reference`} />
            </Helmet>

            <Head title="API Reference Overview" />

            <ApiReferencePage
                title="TraceMem REST API"
                description="A developer-first memory layer for AI applications. Store structured meaning, recall semantically relevant memories, and assemble prompt-ready context, all over a clean, authenticated REST interface."
                endpoint="/v1"
                method="OVERVIEW"
                auth="Bearer API tokens are required for all protected endpoints. Test keys (cmtest_) use semantic-only mode with rate limits. Live keys (cmlive_) unlock AI-first mode and higher throughput."
                body={[]}
                responses={{
                    ok:         '{ "ok": true }',
                    badRequest: '{ "message": "Missing API key." }',
                }}
                snippets={{
                    python:     '# See individual endpoint pages for full request examples.',
                    javascript: '// See individual endpoint pages for full request examples.',
                    php:        '<?php // See individual endpoint pages for full request examples.',
                    curl:       '# See individual endpoint pages for full request examples.',
                    java:       '// See individual endpoint pages for full request examples.',
                    go:         '// See individual endpoint pages for full request examples.',
                }}
                groups={apiRefGroups}
                next={{ href: '/api-reference/quick-start', label: 'Quick start' }}
            />
        </>
    );
}