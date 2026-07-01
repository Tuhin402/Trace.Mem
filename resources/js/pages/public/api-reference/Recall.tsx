import { Head } from '@inertiajs/react';
import { Helmet } from 'react-helmet-async';
import ApiReferencePage from '@/components/public/api-reference-page';
import { apiRefGroups } from '@/components/public/api-ref-nav';
import { useDomains } from '@/lib/domains';

export default function Recall() {
    const { apiUrl, siteUrl } = useDomains();

    return (
        <>
            <Helmet>
                <title>Recall | TraceMem API</title>
                <meta name="description" content="Retrieve semantically relevant memories for a user. Memories are ranked by relevance and recency for use in AI prompt context." />
                <meta property="og:title" content="Recall | TraceMem API" />
                <meta property="og:description" content="Retrieve the most semantically relevant memory units for the caller's tenant and user scope. Use this before generating AI responses to provide grounded, personalized context." />
                <meta property="og:type" content="website" />
                <meta property="og:url" content={`${siteUrl}/api-reference/recall`} />
                <meta property="og:image" content={`${siteUrl}/og-image.png`} />
                <meta property="og:image:width"  content="1200" />
                <meta property="og:image:height" content="630" />
                <meta property="og:image:alt"    content="TraceMem - Long-Term Memory Infrastructure for AI" />
                <meta property="og:site_name"    content="TraceMem" />
                <meta property="og:locale"       content="en_US" />
                <meta name="twitter:card"        content="summary_large_image" />
                <meta name="twitter:title"       content="Recall | TraceMem API" />
                <meta name="twitter:description" content="Retrieve the most semantically relevant memory units for a user. Ranked by relevance and recency for AI prompt context." />
                <meta name="twitter:image"       content={`${siteUrl}/og-image.png`} />
                <meta name="twitter:image:alt"   content="TraceMem - Long-Term Memory Infrastructure for AI" />
                <link rel="canonical" href={`${siteUrl}/api-reference/recall`} />
            </Helmet>

            <Head title="Recall | TraceMem API" />

            <ApiReferencePage
                title="Recall"
                description="Retrieve the most semantically relevant memory units for the caller's tenant and user scope. Memories are ranked by relevance and recency. Use this before generating AI responses to provide grounded, personalized context."
                endpoint="/v1/recall"
                method="POST"
                auth="Authorization: Bearer <api_key>"
                body={[
                    {
                        key:         'limit',
                        type:        'integer',
                        description: 'Optional. Maximum number of memory units to return, ordered by relevance score. Defaults to the plan limit (typically 10).',
                    },
                ]}
                responses={{
                    ok:         '{ "memories": [ { "id": "mem_abc123", "content": "User likes React", "score": 0.94 }, { "id": "mem_def456", "content": "User prefers short answers", "score": 0.87 } ] }',
                    badRequest: '{ "message": "Missing API key." }',
                }}
                snippets={{
                    python: `import requests

response = requests.post(
    "${apiUrl}/v1/recall",
    headers={"Authorization": "Bearer cmlive_xxx"},
    json={"limit": 5}
)

memories = response.json().get("memories", [])
for memory in memories:
    print(memory["content"], memory["score"])`,
                    javascript: `import axios from "axios";

const { data } = await axios.post(
  "${apiUrl}/v1/recall",
  { limit: 5 },
  { headers: { Authorization: "Bearer cmlive_xxx" } }
);

data.memories.forEach((m) => console.log(m.content, m.score));`,
                    php: `$response = Http::withToken('cmlive_xxx')
    ->post('${apiUrl}/v1/recall', [
        'limit' => 5,
    ]);

$memories = $response->json('memories');
foreach ($memories as $memory) {
    echo $memory['content'] . PHP_EOL;
}`,
                    curl: `curl -X POST "${apiUrl}/v1/recall" \\
  -H "Authorization: Bearer cmlive_xxx" \\
  -H "Content-Type: application/json" \\
  -d '{ "limit": 5 }'`,
                    java: `String body = "{\"limit\":5}";

HttpRequest request = HttpRequest.newBuilder()
    .uri(URI.create("${apiUrl}/v1/recall"))
    .header("Authorization", "Bearer cmlive_xxx")
    .header("Content-Type", "application/json")
    .POST(HttpRequest.BodyPublishers.ofString(body))
    .build();

HttpResponse<String> response = HttpClient.newHttpClient()
    .send(request, HttpResponse.BodyHandlers.ofString());`,
                    go: `reqBody := strings.NewReader(\`{"limit":5}\`)

req, _ := http.NewRequest("POST", "${apiUrl}/v1/recall", reqBody)
req.Header.Set("Authorization", "Bearer cmlive_xxx")
req.Header.Set("Content-Type", "application/json")

resp, _ := (&http.Client{}).Do(req)
defer resp.Body.Close()`,
                }}
                groups={apiRefGroups}
                prev={{ href: '/api-reference/remember',         label: 'Remember'         }}
                next={{ href: '/api-reference/context-assemble', label: 'Context assemble' }}
            />
        </>
    );
}