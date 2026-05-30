import { Head } from '@inertiajs/react';
import ApiReferencePage from '@/components/public/api-reference-page';
import { apiRefGroups } from '@/components/public/api-ref-nav';

export default function Health() {
    return (
        <>
            <Head title="Health | TraceMem API" />

            <ApiReferencePage
                title="Health"
                description="A lightweight, unauthenticated endpoint for uptime monitoring, deployment readiness checks, and load balancer health probes. No API key required."
                endpoint="/api/v1/health"
                method="GET"
                auth="No authorization required for health checks."
                body={[]}
                responses={{
                    ok:         '{ "ok": true, "service": "memory-layer", "version": "1.0.0" }',
                    badRequest: '{ "ok": false, "service": "memory-layer" }',
                }}
                snippets={{
                    python: `import requests

response = requests.get("https://tracemem.one/api/v1/health")
print(response.json())
# {"ok": true, "service": "memory-layer", "version": "1.0.0"}`,
                    javascript: `const res  = await fetch("https://tracemem.one/api/v1/health");
const data = await res.json();
console.log(data);
// { ok: true, service: "memory-layer", version: "1.0.0" }`,
                    php: `$response = Http::get('https://tracemem.one/api/v1/health')->json();
// ["ok" => true, "service" => "memory-layer", "version" => "1.0.0"]`,
                    curl: `curl "https://tracemem.one/api/v1/health"
# {"ok":true,"service":"memory-layer","version":"1.0.0"}`,
                    java: `HttpRequest request = HttpRequest.newBuilder()
    .uri(URI.create("https://tracemem.one/api/v1/health"))
    .GET()
    .build();

HttpResponse<String> response = HttpClient.newHttpClient()
    .send(request, HttpResponse.BodyHandlers.ofString());
System.out.println(response.body());`,
                    go: `resp, _ := http.Get("https://tracemem.one/api/v1/health")
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