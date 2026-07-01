import { Head } from '@inertiajs/react';
import { Helmet } from 'react-helmet-async';
import ApiReferencePage from '@/components/public/api-reference-page';
import { apiRefGroups } from '@/components/public/api-ref-nav';
import { useDomains } from '@/lib/domains';

export default function Health() {
    const { apiUrl, siteUrl } = useDomains();

    return (
        <>
            <Helmet>
                <title>Health | TraceMem API</title>
                <meta name="description" content="TraceMem health check endpoint for uptime monitoring, deployment readiness, and load balancer health probes. No API key required." />
                <meta property="og:title" content="Health Check | TraceMem API" />
                <meta property="og:description" content="A lightweight, unauthenticated endpoint for uptime monitoring, deployment readiness checks, and load balancer health probes." />
                <meta property="og:type" content="website" />
                <meta property="og:url" content={`${siteUrl}/api-reference/health`} />
                <meta property="og:image" content={`${siteUrl}/og-image.png`} />
                <meta property="og:image:width"  content="1200" />
                <meta property="og:image:height" content="630" />
                <meta property="og:image:alt"    content="TraceMem - Long-Term Memory Infrastructure for AI" />
                <meta property="og:site_name"    content="TraceMem" />
                <meta property="og:locale"       content="en_US" />
                <meta name="twitter:card"        content="summary_large_image" />
                <meta name="twitter:title"       content="Health Check | TraceMem API" />
                <meta name="twitter:description" content="A lightweight, unauthenticated endpoint for uptime monitoring, deployment readiness checks, and load balancer health probes." />
                <meta name="twitter:image"       content={`${siteUrl}/og-image.png`} />
                <meta name="twitter:image:alt"   content="TraceMem - Long-Term Memory Infrastructure for AI" />
                <link rel="canonical" href={`${siteUrl}/api-reference/health`} />
            </Helmet>

            <Head title="Health | TraceMem API" />

            <ApiReferencePage
                title="Health"
                description="A lightweight, unauthenticated endpoint for uptime monitoring, deployment readiness checks, and load balancer health probes. No API key required."
                endpoint="/v1/health"
                method="GET"
                auth="No authorization required for health checks."
                body={[]}
                responses={{
                    ok:         '{ "ok": true, "service": "memory-layer", "version": "1.0.0" }',
                    badRequest: '{ "ok": false, "service": "memory-layer" }',
                }}
                snippets={{
                    python: `import requests

response = requests.get("${apiUrl}/v1/health")
print(response.json())
# {"ok": true, "service": "memory-layer", "version": "1.0.0"}`,
                    javascript: `const res  = await fetch("${apiUrl}/v1/health");
const data = await res.json();
console.log(data);
// { ok: true, service: "memory-layer", version: "1.0.0" }`,
                    php: `$response = Http::get('${apiUrl}/v1/health')->json();
// ["ok" => true, "service" => "memory-layer", "version" => "1.0.0"]`,
                    curl: `curl "${apiUrl}/v1/health"
# {"ok":true,"service":"memory-layer","version":"1.0.0"}`,
                    java: `HttpRequest request = HttpRequest.newBuilder()
    .uri(URI.create("${apiUrl}/v1/health"))
    .GET()
    .build();

HttpResponse<String> response = HttpClient.newHttpClient()
    .send(request, HttpResponse.BodyHandlers.ofString());
System.out.println(response.body());`,
                    go: `resp, _ := http.Get("${apiUrl}/v1/health")
defer resp.Body.Close()

body, _ := io.ReadAll(resp.Body)
fmt.Println(string(body))
// {"ok":true,"service":"memory-layer","version":"1.0.0"}`,
                }}
                groups={apiRefGroups}
                prev={{ href: '/api-reference/authentication', label: 'Auth & authorization' }}
                next={{ href: '/api-reference/remember',       label: 'Remember'              }}
            />
        </>
    );
}