import { Head } from '@inertiajs/react';
import { Helmet } from 'react-helmet-async';
import ApiReferencePage from '@/components/public/api-reference-page';
import { apiRefGroups } from '@/components/public/api-ref-nav';
import { useDomains } from '@/lib/domains';

export default function Authentication() {
    const { apiUrl, siteUrl } = useDomains();

    return (
        <>
            <Helmet>
                <title>Authentication | TraceMem API</title>
                <meta name="description" content="How to authenticate with the TraceMem API using bearer API keys. Test keys (cmtest_) and live keys (cmlive_) explained." />
                <meta property="og:title" content="Authentication | TraceMem API" />
                <meta property="og:description" content="TraceMem uses bearer API keys to identify callers, resolve tenant scope, determine plan mode, and enforce rate limits." />
                <meta property="og:type" content="website" />
                <meta property="og:url" content={`${siteUrl}/api-reference/authentication`} />
                <meta property="og:image" content={`${siteUrl}/og-image.png`} />
                <meta property="og:image:width"  content="1200" />
                <meta property="og:image:height" content="630" />
                <meta property="og:image:alt"    content="TraceMem - Long-Term Memory Infrastructure for AI" />
                <meta property="og:site_name"    content="TraceMem" />
                <meta property="og:locale"       content="en_US" />
                <meta name="twitter:card"        content="summary_large_image" />
                <meta name="twitter:title"       content="Authentication | TraceMem API" />
                <meta name="twitter:description" content="TraceMem uses bearer API keys to identify callers, resolve tenant scope, determine plan mode, and enforce rate limits." />
                <meta name="twitter:image"       content={`${siteUrl}/og-image.png`} />
                <meta name="twitter:image:alt"   content="TraceMem - Long-Term Memory Infrastructure for AI" />
                <link rel="canonical" href={`${siteUrl}/api-reference/authentication`} />
            </Helmet>

            <Head title="Authentication | TraceMem API" />

            <ApiReferencePage
                title="Authentication & Authorization"
                description="TraceMem uses bearer API keys to identify callers, resolve tenant scope, determine plan mode, and enforce rate limits. Test keys (cmtest_) are semantic-only and rate limited. Live keys (cmlive_) unlock AI-first memory processing."
                endpoint="Authorization header"
                method="GUIDE"
                auth="Authorization: Bearer <api_key>"
                body={[
                    {
                        key:         'Authorization',
                        type:        'header',
                        required:    true,
                        description: 'Bearer token that identifies the caller. Determines tenant scope, plan mode (test vs live), and rate limits. Format: "Bearer cmtest_xxx" or "Bearer cmlive_xxx".',
                    },
                ]}
                responses={{
                    ok:         '{ "message": "Authenticated", "mode": "live", "tenant": "acme-corp" }',
                    badRequest: '{ "message": "Missing API key." }',
                }}
                snippets={{
                    python: `import requests

# Test key - semantic-only mode
requests.get(
    "${apiUrl}/v1/health",
    headers={"Authorization": "Bearer cmtest_xxx"}
)

# Live key - AI-first mode
requests.get(
    "${apiUrl}/v1/health",
    headers={"Authorization": "Bearer cmlive_xxx"}
)`,
                    javascript: `// Test key - semantic-only mode
fetch("${apiUrl}/v1/health", {
  headers: { Authorization: "Bearer cmtest_xxx" }
});

// Live key - AI-first mode
fetch("${apiUrl}/v1/health", {
  headers: { Authorization: "Bearer cmlive_xxx" }
});`,
                    php: `// Test key
Http::withToken('cmtest_xxx')->get('${apiUrl}/v1/health');

// Live key
Http::withToken('cmlive_xxx')->get('${apiUrl}/v1/health');`,
                    curl: `# Test key - semantic-only mode
curl -H "Authorization: Bearer cmtest_xxx" \\
  ${apiUrl}/v1/health

# Live key - AI-first mode
curl -H "Authorization: Bearer cmlive_xxx" \\
  ${apiUrl}/v1/health`,
                    java: `HttpRequest req = HttpRequest.newBuilder()
    .uri(URI.create("${apiUrl}/v1/health"))
    .header("Authorization", "Bearer cmtest_xxx")
    .GET()
    .build();

HttpResponse<String> res = HttpClient.newHttpClient()
    .send(req, HttpResponse.BodyHandlers.ofString());`,
                    go: `req, _ := http.NewRequest("GET", "${apiUrl}/v1/health", nil)
req.Header.Set("Authorization", "Bearer cmtest_xxx")

client := &http.Client{}
resp, _ := client.Do(req)
defer resp.Body.Close()`,
                }}
                groups={apiRefGroups}
                prev={{ href: '/api-reference/core-operations', label: 'Core operations' }}
                next={{ href: '/api-reference/health',           label: 'Health'          }}
            />
        </>
    );
}