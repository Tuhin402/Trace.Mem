import { Head } from '@inertiajs/react';
import ApiReferencePage from '@/components/public/api-reference-page';
import { apiRefGroups } from '@/components/public/api-ref-nav';

export default function Remember() {
    return (
        <>
            <Head title="Remember | TraceMem API" />

            <ApiReferencePage
                title="Remember"
                description="Store an atomic memory item scoped to the caller's tenant and user. TraceMem extracts semantic meaning, detects duplicates, and persists structured memory units ready for recall and context assembly."
                endpoint="/api/v1/remember"
                method="POST"
                auth="Authorization: Bearer <api_key>"
                body={[
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
    "https://tracemem.io/api/v1/remember",
    headers={"Authorization": "Bearer cmlive_xxx"},
    json={"content": "User likes React"}
)
print(response.json())
# {"message": "Memory saved", "memory": {"id": "mem_abc123", ...}}`,
                    javascript: `import axios from "axios";

const { data } = await axios.post(
  "https://tracemem.io/api/v1/remember",
  { content: "User likes React" },
  { headers: { Authorization: "Bearer cmlive_xxx" } }
);
console.log(data.memory.id); // "mem_abc123"`,
                    php: `$response = Http::withToken('cmlive_xxx')
    ->post('https://tracemem.io/api/v1/remember', [
        'content' => 'User likes React',
    ]);

$memory = $response->json('memory');`,
                    curl: `curl -X POST "https://tracemem.io/api/v1/remember" \\
  -H "Authorization: Bearer cmlive_xxx" \\
  -H "Content-Type: application/json" \\
  -d '{ "content": "User likes React" }'`,
                    java: `String body = "{\"content\":\"User likes React\"}";

HttpRequest request = HttpRequest.newBuilder()
    .uri(URI.create("https://tracemem.io/api/v1/remember"))
    .header("Authorization", "Bearer cmlive_xxx")
    .header("Content-Type", "application/json")
    .POST(HttpRequest.BodyPublishers.ofString(body))
    .build();

HttpResponse<String> response = HttpClient.newHttpClient()
    .send(request, HttpResponse.BodyHandlers.ofString());`,
                    go: `reqBody := strings.NewReader(\`{"content":"User likes React"}\`)

req, _ := http.NewRequest("POST", "https://tracemem.io/api/v1/remember", reqBody)
req.Header.Set("Authorization", "Bearer cmlive_xxx")
req.Header.Set("Content-Type", "application/json")

resp, _ := (&http.Client{}).Do(req)
defer resp.Body.Close()`,
                }}
                groups={apiRefGroups}
                prev={{ href: '/api-reference/health', label: 'Health' }}
                next={{ href: '/api-reference/recall', label: 'Recall' }}
            />
        </>
    );
}