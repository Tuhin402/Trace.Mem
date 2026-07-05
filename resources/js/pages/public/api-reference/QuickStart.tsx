import { Head } from '@inertiajs/react';
import { Helmet } from 'react-helmet-async';
import ApiReferencePage from '@/components/public/api-reference-page';
import { apiRefGroups } from '@/components/public/api-ref-nav';
import { useDomains } from '@/lib/domains';

export default function QuickStart() {
    const { apiUrl, siteUrl } = useDomains();

    return (
        <>
            <Helmet>
                <title>Quick Start | TraceMem API</title>
                <meta name="description" content="Make your first TraceMem API request in under two minutes. Store a memory, recall it, and assemble prompt-ready context." />
                <meta property="og:title" content="Quick Start | TraceMem API" />
                <meta property="og:description" content="Make your first TraceMem request in under two minutes. Store a memory, recall it, and assemble prompt-ready context, all with a single Bearer token." />
                <meta property="og:type" content="website" />
                <meta property="og:url" content={`${siteUrl}/api-reference/quick-start`} />
                <meta property="og:image" content={`${siteUrl}/og-image.png`} />
                <meta property="og:image:width"  content="1200" />
                <meta property="og:image:height" content="630" />
                <meta property="og:image:alt"    content="TraceMem - Long-Term Memory Infrastructure for AI" />
                <meta property="og:site_name"    content="TraceMem" />
                <meta property="og:locale"       content="en_US" />
                <meta name="twitter:card"        content="summary_large_image" />
                <meta name="twitter:title"       content="Quick Start | TraceMem API" />
                <meta name="twitter:description" content="Make your first TraceMem request in under two minutes. Store a memory, recall it, and assemble prompt-ready context." />
                <meta name="twitter:image"       content={`${siteUrl}/og-image.png`} />
                <meta name="twitter:image:alt"   content="TraceMem - Long-Term Memory Infrastructure for AI" />
                <link rel="canonical" href={`${siteUrl}/api-reference/quick-start`} />
            </Helmet>

            <Head title="Quick Start | TraceMem API" />

            <ApiReferencePage
                title="Quick Start"
                description="Make your first TraceMem request in under two minutes. The fastest path is POST /chat - one call handles memory storage, context assembly, and AI reply generation automatically. For fine-grained control over each step, see Core Operations which documents POST /remember, POST /recall, and POST /context/assemble individually."
                endpoint="/v1/chat"
                method="POST"
                auth="Authorization: Bearer <api_key>  -  Test keys (cmtest_) use semantic-only mode."
                body={[
                    {
                        key:         'message',
                        type:        'string',
                        required:    true,
                        description: 'The user message. TraceMem classifies it, optionally stores it as memory, assembles personalised context, and returns an AI reply in a single call.',
                    },
                    {
                        key:         'memory_mode',
                        type:        'string',
                        description: 'Optional. "auto" (default), "force", or "off". Controls whether the message is stored as memory.',
                    },
                ]}
                responses={{
                    ok: `{
  "request_id": "tm_chat_01K1E6G9XY",
  "reply": "Got it! I'll remember that you prefer short answers.",
  "memory": { "saved": true, "type": "preference", "via": "heuristic" },
  "context": { "used": true, "memories": 3, "tokens": 198 },
  "provider": "nvidia",
  "model": "openai/gpt-oss-20b",
  "latency_ms": { "classifier": 0, "llm": 541, "total": 612 }
}`,
                    badRequest: '{ "message": "The message field is required." }',
                }}
                snippets={{
                    python: `import requests

response = requests.post(
    "${apiUrl}/v1/chat",
    headers={"Authorization": "Bearer cmlive_xxx"},
    json={"message": "I prefer short answers"}
)

data = response.json()
print(data["reply"])          # AI reply
print(data["memory"]["saved"]) # True if stored`,
                    javascript: `import axios from "axios";

const { data } = await axios.post(
  "${apiUrl}/v1/chat",
  { message: "I prefer short answers" },
  { headers: { Authorization: "Bearer cmlive_xxx" } }
);

console.log(data.reply);           // AI reply
console.log(data.memory.saved);    // true if stored
console.log(data.request_id);      // tm_chat_...`,
                    php: `$response = Http::withToken('cmlive_xxx')
    ->post('${apiUrl}/v1/chat', [
        'message' => 'I prefer short answers',
    ]);

$data     = $response->json();
$reply    = $data['reply'];
$saved    = $data['memory']['saved'];`,
                    curl: `curl -X POST "${apiUrl}/v1/chat" \\
  -H "Authorization: Bearer cmlive_xxx" \\
  -H "Content-Type: application/json" \\
  -d '{ "message": "I prefer short answers" }'`,
                    java: `String body = "{\"message\":\"I prefer short answers\"}";

HttpRequest request = HttpRequest.newBuilder()
    .uri(URI.create("${apiUrl}/v1/chat"))
    .header("Authorization", "Bearer cmlive_xxx")
    .header("Content-Type", "application/json")
    .POST(HttpRequest.BodyPublishers.ofString(body))
    .build();

HttpResponse<String> resp = HttpClient.newHttpClient()
    .send(request, HttpResponse.BodyHandlers.ofString());
// resp.headers().firstValue("X-Request-ID") → "tm_chat_..."`,
                    go: `reqBody := strings.NewReader(`+"`"+`{"message":"I prefer short answers"}`+"`"+`)

req, _ := http.NewRequest("POST", "${apiUrl}/v1/chat", reqBody)
req.Header.Set("Authorization", "Bearer cmlive_xxx")
req.Header.Set("Content-Type", "application/json")

resp, _ := (&http.Client{}).Do(req)
defer resp.Body.Close()
// resp.Header.Get("X-Request-ID") → "tm_chat_..."`,
                }}
                groups={apiRefGroups}
                prev={{ href: '/api-reference',                 label: 'Overview'         }}
                next={{ href: '/api-reference/core-operations', label: 'Core operations'  }}
            />
        </>
    );
}