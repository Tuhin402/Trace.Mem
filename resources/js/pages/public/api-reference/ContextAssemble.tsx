import { Head } from '@inertiajs/react';
import { Helmet } from 'react-helmet-async';
import ApiReferencePage from '@/components/public/api-reference-page';
import { apiRefGroups } from '@/components/public/api-ref-nav';
import { useDomains } from '@/lib/domains';

export default function ContextAssemble() {
    const { apiUrl, siteUrl } = useDomains();

    return (
        <>
            <Helmet>
                <title>Context Assemble | TraceMem API</title>
                <meta name="description" content="Assemble prompt-ready context from stored memories. Pass a query and token budget, TraceMem returns a ranked, structured context block for your LLM prompts." />
                <meta property="og:title" content="Context Assemble | TraceMem API" />
                <meta property="og:description" content="Selects, ranks, and formats the most relevant memory units into a prompt-ready context window. Inject directly into your LLM prompt with a configurable token budget." />
                <meta property="og:type" content="website" />
                <meta property="og:url" content={`${siteUrl}/api-reference/context-assemble`} />
                <meta property="og:image" content={`${siteUrl}/og-image.png`} />
                <meta property="og:image:width"  content="1200" />
                <meta property="og:image:height" content="630" />
                <meta property="og:image:alt"    content="TraceMem - Long-Term Memory Infrastructure for AI" />
                <meta property="og:site_name"    content="TraceMem" />
                <meta property="og:locale"       content="en_US" />
                <meta name="twitter:card"        content="summary_large_image" />
                <meta name="twitter:title"       content="Context Assemble | TraceMem API" />
                <meta name="twitter:description" content="Assemble prompt-ready context from stored memories. Ranked, structured, and token-budget aware, built for LLM prompts." />
                <meta name="twitter:image"       content={`${siteUrl}/og-image.png`} />
                <meta name="twitter:image:alt"   content="TraceMem - Long-Term Memory Infrastructure for AI" />
                <link rel="canonical" href={`${siteUrl}/api-reference/context-assemble`} />
            </Helmet>

            <Head title="Context Assemble | TraceMem API" />

            <ApiReferencePage
                title="Context Assemble"
                description="Selects, ranks, and formats the most relevant memory units into a prompt-ready context window. Pass the current user query and an optional token budget, TraceMem returns a structured context block you can inject directly into your LLM prompt."
                endpoint="/v1/context/assemble"
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
    "${apiUrl}/v1/context/assemble",
    headers={"Authorization": "Bearer cmlive_xxx"},
    json={
        "user_id":      "customer_8472",
        "query":        "Help me answer this question about frameworks",
        "token_budget": 1200,
    }
)

result  = response.json()
context = result["context"]   # inject directly into your LLM prompt
print(context)`,
                    javascript: `import axios from "axios";

const { data } = await axios.post(
  "${apiUrl}/v1/context/assemble",
  {
    user_id:      "customer_8472",
    query:        "Help me answer this question about frameworks",
    token_budget: 1200,
  },
  { headers: { Authorization: "Bearer cmlive_xxx" } }
);

// Inject into your LLM prompt
const systemPrompt = \`Context:\n\${data.context}\n\nUser: ...\`;`,
                    php: `$response = Http::withToken('cmlive_xxx')
    ->post('${apiUrl}/v1/context/assemble', [
        'user_id'      => 'customer_8472',
        'query'        => 'Help me answer this question about frameworks',
        'token_budget' => 1200,
    ]);

$context = $response->json('context');
// Inject $context into your LLM system prompt`,
                    curl: `curl -X POST "${apiUrl}/v1/context/assemble" \\
  -H "Authorization: Bearer cmlive_xxx" \\
  -H "Content-Type: application/json" \\
  -d '{
    "user_id": "customer_8472",
    "query": "Help me answer this question about frameworks",
    "token_budget": 1200
  }'`,
                    java: `String body = """
    {
        "user_id": "customer_8472",
        "query": "Help me answer this question about frameworks",
        "token_budget": 1200
    }""";

HttpRequest request = HttpRequest.newBuilder()
    .uri(URI.create("${apiUrl}/v1/context/assemble"))
    .header("Authorization", "Bearer cmlive_xxx")
    .header("Content-Type", "application/json")
    .POST(HttpRequest.BodyPublishers.ofString(body))
    .build();

HttpResponse<String> response = HttpClient.newHttpClient()
    .send(request, HttpResponse.BodyHandlers.ofString());`,
                    go: `payload := \`{
    "user_id":      "customer_8472",
    "query":        "Help me answer this question about frameworks",
    "token_budget": 1200
}\`

req, _ := http.NewRequest(
    "POST",
    "${apiUrl}/v1/context/assemble",
    strings.NewReader(payload),
)
req.Header.Set("Authorization", "Bearer cmlive_xxx")
req.Header.Set("Content-Type", "application/json")

resp, _ := (&http.Client{}).Do(req)
defer resp.Body.Close()`,
                }}
                groups={apiRefGroups}
                prev={{ href: '/api-reference/recall', label: 'Recall' }}
                next={{ href: '/api-reference/chat', label: 'Chat' }}
            >
                <div className="pt-4 border-t border-zinc-800/50 mt-8 space-y-4">
                    <h2 className="text-white text-lg font-semibold mb-3">Identity & Isolation</h2>
                    <ul className="list-disc pl-5 space-y-2 text-zinc-400">
                        <li><strong>Workspace Isolation:</strong> TraceMem enforces workspace boundaries at the semantic search level via the API key. Cross-contamination is structurally impossible.</li>
                        <li><strong>User Isolation:</strong> Required. The <code>user_id</code> ensures that context assembly strictly draws upon the exact memory graph representing <em>only</em> this downstream end-user.</li>
                    </ul>
                </div>
            </ApiReferencePage>
        </>
    );
}