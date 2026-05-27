import { Head } from '@inertiajs/react';
import ApiReferencePage from '@/components/public/api-reference-page';
import { apiRefGroups } from '@/components/public/api-ref-nav';

export default function Authentication() {
    return (
        <>
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

# Test key — semantic-only mode
requests.get(
    "https://tracemem.io/api/v1/health",
    headers={"Authorization": "Bearer cmtest_xxx"}
)

# Live key — AI-first mode
requests.get(
    "https://tracemem.io/api/v1/health",
    headers={"Authorization": "Bearer cmlive_xxx"}
)`,
                    javascript: `// Test key — semantic-only mode
fetch("https://tracemem.io/api/v1/health", {
  headers: { Authorization: "Bearer cmtest_xxx" }
});

// Live key — AI-first mode
fetch("https://tracemem.io/api/v1/health", {
  headers: { Authorization: "Bearer cmlive_xxx" }
});`,
                    php: `// Test key
Http::withToken('cmtest_xxx')->get('https://tracemem.io/api/v1/health');

// Live key
Http::withToken('cmlive_xxx')->get('https://tracemem.io/api/v1/health');`,
                    curl: `# Test key — semantic-only mode
curl -H "Authorization: Bearer cmtest_xxx" \\
  https://tracemem.io/api/v1/health

# Live key — AI-first mode
curl -H "Authorization: Bearer cmlive_xxx" \\
  https://tracemem.io/api/v1/health`,
                    java: `HttpRequest req = HttpRequest.newBuilder()
    .uri(URI.create("https://tracemem.io/api/v1/health"))
    .header("Authorization", "Bearer cmtest_xxx")
    .GET()
    .build();

HttpResponse<String> res = HttpClient.newHttpClient()
    .send(req, HttpResponse.BodyHandlers.ofString());`,
                    go: `req, _ := http.NewRequest("GET", "https://tracemem.io/api/v1/health", nil)
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