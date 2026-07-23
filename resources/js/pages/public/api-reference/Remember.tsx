import { Head } from '@inertiajs/react';
import { Helmet } from 'react-helmet-async';
import ApiReferencePage from '@/components/public/api-reference-page';
import { apiRefGroups } from '@/components/public/api-ref-nav';
import { useDomains } from '@/lib/domains';

export default function Remember() {
    const { apiUrl, siteUrl } = useDomains();

    return (
        <>
            <Helmet>
                <title>Remember | TraceMem API</title>
                <meta name="description" content="Store an atomic memory item with TraceMem. Semantic extraction, deduplication, and tenant isolation built in." />
                <meta property="og:title" content="Remember | TraceMem API" />
                <meta property="og:description" content="Store an atomic memory item scoped to the caller's tenant and user. TraceMem extracts semantic meaning, detects duplicates, and persists structured memory units." />
                <meta property="og:type" content="website" />
                <meta property="og:url" content={`${siteUrl}/api-reference/remember`} />
                <meta property="og:image" content={`${siteUrl}/og-image.png`} />
                <meta property="og:image:width"  content="1200" />
                <meta property="og:image:height" content="630" />
                <meta property="og:image:alt"    content="TraceMem - Long-Term Memory Infrastructure for AI" />
                <meta property="og:site_name"    content="TraceMem" />
                <meta property="og:locale"       content="en_US" />
                <meta name="twitter:card"        content="summary_large_image" />
                <meta name="twitter:title"       content="Remember | TraceMem API" />
                <meta name="twitter:description" content="Store an atomic memory item scoped to the caller's tenant and user. Semantic extraction and deduplication built in." />
                <meta name="twitter:image"       content={`${siteUrl}/og-image.png`} />
                <meta name="twitter:image:alt"   content="TraceMem - Long-Term Memory Infrastructure for AI" />
                <link rel="canonical" href={`${siteUrl}/api-reference/remember`} />
            </Helmet>

            <Head title="Remember | TraceMem API" />

            <ApiReferencePage
                title="Remember"
                description="Store an atomic memory item scoped to the caller's tenant and user. TraceMem extracts semantic meaning, detects duplicates, and persists structured memory units ready for recall and context assembly."
                endpoint="/v1/remember"
                method="POST"
                auth="Authorization: Bearer <api_key>"
                body={[
                    {
                        key:         'user_id',
                        type:        'string',
                        required:    true,
                        description: 'A stable identifier representing your downstream end-user (e.g., customer_8472). TraceMem groups memories exclusively by this ID.',
                    },
                    {
                        key:         'content',
                        type:        'string',
                        required:    true,
                        description: 'The raw user message, note, or statement to ingest. TraceMem extracts semantic structure and stores it as a ranked memory unit.',
                    },
                ]}
                responses={{
                    ok:         '{ "message": "Memory saved", "memory": { "id": "mem_abc123", "content": "User likes React", "score": 0.94, "mode": "live" } }',
                    badRequest: '{ "message": "Missing API key." }',
                }}
                snippets={{
                    python: `import requests

response = requests.post(
    "${apiUrl}/v1/remember",
    headers={"Authorization": "Bearer cmlive_xxx"},
    json={"user_id": "customer_8472", "content": "User likes React"}
)
print(response.json())
# {"message": "Memory saved", "memory": {"id": "mem_abc123", ...}}`,
                    javascript: `import axios from "axios";

const { data } = await axios.post(
  "${apiUrl}/v1/remember",
  { user_id: "customer_8472", content: "User likes React" },
  { headers: { Authorization: "Bearer cmlive_xxx" } }
);
console.log(data.memory.id); // "mem_abc123"`,
                    php: `$response = Http::withToken('cmlive_xxx')
    ->post('${apiUrl}/v1/remember', [
        'user_id' => 'customer_8472',
        'content' => 'User likes React',
    ]);

$memory = $response->json('memory');`,
                    curl: `curl -X POST "${apiUrl}/v1/remember" \\
  -H "Authorization: Bearer cmlive_xxx" \\
  -H "Content-Type: application/json" \\
  -d '{ "user_id": "customer_8472", "content": "User likes React" }'`,
                    java: `String body = "{\"user_id\":\"customer_8472\",\"content\":\"User likes React\"}";

HttpRequest request = HttpRequest.newBuilder()
    .uri(URI.create("${apiUrl}/v1/remember"))
    .header("Authorization", "Bearer cmlive_xxx")
    .header("Content-Type", "application/json")
    .POST(HttpRequest.BodyPublishers.ofString(body))
    .build();

HttpResponse<String> response = HttpClient.newHttpClient()
    .send(request, HttpResponse.BodyHandlers.ofString());`,
                    go: `reqBody := strings.NewReader(\`{"user_id":"customer_8472","content":"User likes React"}\`)

req, _ := http.NewRequest("POST", "${apiUrl}/v1/remember", reqBody)
req.Header.Set("Authorization", "Bearer cmlive_xxx")
req.Header.Set("Content-Type", "application/json")

resp, _ := (&http.Client{}).Do(req)
defer resp.Body.Close()`,
                }}
                groups={apiRefGroups}
                prev={{ href: '/api-reference/health', label: 'Health' }}
                next={{ href: '/api-reference/recall', label: 'Recall' }}
            >
                <div className="pt-4 border-t border-zinc-800/50 mt-8 space-y-4">
                    <h2 className="text-white text-lg font-semibold mb-3">Identity & Isolation</h2>
                    <ul className="list-disc pl-5 space-y-2 text-zinc-400">
                        <li><strong>Workspace Isolation:</strong> TraceMem automatically determines the <code>tenant_scope_id</code> and <code>workspace_id</code> from your authenticated <code>X-API-Key</code>.</li>
                        <li><strong>User Isolation:</strong> The <code>user_id</code> is strictly required. This memory will be permanently attached to this downstream human/entity.</li>
                        <li><strong>Stability:</strong> It must be a stable identifier. Changing it creates a completely separate, strictly partitioned memory graph.</li>
                    </ul>
                </div>
            </ApiReferencePage>
        </>
    );
}