import { Head } from '@inertiajs/react';
import ApiReferencePage from '@/components/public/api-reference-page';
import { apiRefGroups } from '@/components/public/api-ref-nav';
import { useDomains } from '@/lib/domains';

export default function ContextAssemble() {
    const { apiUrl } = useDomains();

    return (
        <>
            <Head title="Context Assemble | TraceMem API" />

            <ApiReferencePage
                title="Context Assemble"
                description="Selects, ranks, and formats the most relevant memory units into a prompt-ready context window. Pass the current user query and an optional token budget, TraceMem returns a structured context block you can inject directly into your LLM prompt."
                endpoint="/api/v1/context/assemble"
                method="POST"
                auth="Authorization: Bearer <api_key>"
                body={[
                    {
                        key:         'query',
                        type:        'string',
                        required:    true,
                        description: 'The current user message or assistant prompt. Used to semantically rank and filter memories by relevance to this query.',
                    },
                    {
                        key:         'token_budget',
                        type:        'integer',
                        description: 'Optional. Maximum number of tokens the assembled context should occupy. TraceMem trims and ranks memories to fit within budget.',
                    },
                    {
                        key:         'candidate_limit',
                        type:        'integer',
                        description: 'Optional. Maximum number of candidate memories to consider before ranking. Defaults to the plan limit.',
                    },
                    {
                        key:         'debug',
                        type:        'boolean',
                        description: 'Optional. When true, returns diagnostic metadata including per-item relevance scores and trim decisions.',
                    },
                ]}
                responses={{
                    ok:         '{ "query": "Help me answer this", "context": "User likes React. User prefers short answers.", "items": [ { "id": "mem_abc123", "content": "User likes React", "score": 0.94 } ], "token_count": 42 }',
                    badRequest: '{ "message": "Missing API key." }',
                }}
                snippets={{
                    python: `import requests

response = requests.post(
    "${apiUrl}/api/v1/context/assemble",
    headers={"Authorization": "Bearer cmlive_xxx"},
    json={
        "query":        "Help me answer this question about frameworks",
        "token_budget": 1200,
    }
)

result  = response.json()
context = result["context"]   # inject directly into your LLM prompt
print(context)`,
                    javascript: `import axios from "axios";

const { data } = await axios.post(
  "${apiUrl}/api/v1/context/assemble",
  {
    query:        "Help me answer this question about frameworks",
    token_budget: 1200,
  },
  { headers: { Authorization: "Bearer cmlive_xxx" } }
);

// Inject into your LLM prompt
const systemPrompt = \`Context:\n\${data.context}\n\nUser: ...\`;`,
                    php: `$response = Http::withToken('cmlive_xxx')
    ->post('${apiUrl}/api/v1/context/assemble', [
        'query'        => 'Help me answer this question about frameworks',
        'token_budget' => 1200,
    ]);

$context = $response->json('context');
// Inject $context into your LLM system prompt`,
                    curl: `curl -X POST "${apiUrl}/api/v1/context/assemble" \\
  -H "Authorization: Bearer cmlive_xxx" \\
  -H "Content-Type: application/json" \\
  -d '{
    "query": "Help me answer this question about frameworks",
    "token_budget": 1200
  }'`,
                    java: `String body = """
    {
        "query": "Help me answer this question about frameworks",
        "token_budget": 1200
    }""";

HttpRequest request = HttpRequest.newBuilder()
    .uri(URI.create("${apiUrl}/api/v1/context/assemble"))
    .header("Authorization", "Bearer cmlive_xxx")
    .header("Content-Type", "application/json")
    .POST(HttpRequest.BodyPublishers.ofString(body))
    .build();

HttpResponse<String> response = HttpClient.newHttpClient()
    .send(request, HttpResponse.BodyHandlers.ofString());`,
                    go: `payload := \`{
    "query":        "Help me answer this question about frameworks",
    "token_budget": 1200
}\`

req, _ := http.NewRequest(
    "POST",
    "${apiUrl}/api/v1/context/assemble",
    strings.NewReader(payload),
)
req.Header.Set("Authorization", "Bearer cmlive_xxx")
req.Header.Set("Content-Type", "application/json")

resp, _ := (&http.Client{}).Do(req)
defer resp.Body.Close()`,
                }}
                groups={apiRefGroups}
                prev={{ href: '/api-reference/recall', label: 'Recall' }}
            />
        </>
    );
}