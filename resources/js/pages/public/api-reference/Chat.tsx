import { Head } from '@inertiajs/react';
import { Helmet } from 'react-helmet-async';
import ApiReferencePage from '@/components/public/api-reference-page';
import { apiRefGroups } from '@/components/public/api-ref-nav';
import { useDomains } from '@/lib/domains';

export default function Chat() {
    const { apiUrl, siteUrl } = useDomains();

    return (
        <>
            <Helmet>
                <title>Chat (Beta) | TraceMem API</title>
                <meta name="description" content="POST /chat is TraceMem's one-call convenience endpoint. It automatically classifies, remembers, assembles context, and generates an AI reply in a single request." />
                <meta property="og:title" content="Chat (Beta) | TraceMem API" />
                <meta property="og:description" content="The easiest way to use TraceMem. One API call handles the full pipeline: memory classification, storage, context assembly, and an NVIDIA NIM-powered AI reply." />
                <meta property="og:type" content="website" />
                <meta property="og:url" content={`${siteUrl}/api-reference/chat`} />
                <meta property="og:image" content={`${siteUrl}/og-image.png`} />
                <meta property="og:image:width"  content="1200" />
                <meta property="og:image:height" content="630" />
                <meta property="og:image:alt"    content="TraceMem - Long-Term Memory Infrastructure for AI" />
                <meta property="og:site_name"    content="TraceMem" />
                <meta property="og:locale"       content="en_US" />
                <meta name="twitter:card"        content="summary_large_image" />
                <meta name="twitter:title"       content="Chat (Beta) | TraceMem API" />
                <meta name="twitter:description" content="One API call. TraceMem decides what to remember, assembles personalised context, and generates an AI reply using NVIDIA NIM." />
                <meta name="twitter:image"       content={`${siteUrl}/og-image.png`} />
                <meta name="twitter:image:alt"   content="TraceMem - Long-Term Memory Infrastructure for AI" />
                <link rel="canonical" href={`${siteUrl}/api-reference/chat`} />
            </Helmet>

            <Head title="Chat (Beta) | TraceMem API" />

            <ApiReferencePage
                title="Chat (Beta)"
                description={`POST /chat is a high-level convenience endpoint that orchestrates the full TraceMem pipeline in one call. It classifies your message to decide whether to store it as memory, assembles personalised context from existing memories, and calls NVIDIA NIM to generate a reply — all automatically. Advanced users who need precise control can still call POST /remember, POST /context/assemble, and POST /recall independently; those endpoints are completely unchanged. Think of /chat as the automatic mode and the lower-level endpoints as the manual mode.\n\nmemory_mode values: "auto" (default) — TraceMem decides whether to remember. "force" — always store before replying. "off" — never store; context is still assembled and injected. The dry_run field lets you preview what would be remembered without storing anything or calling the AI model.`}
                endpoint="/v1/chat"
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
                        key:         'message',
                        type:        'string',
                        required:    true,
                        description: 'The user message to process. TraceMem classifies it, optionally stores it, assembles personalised context, and generates an AI reply. Maximum 10,000 characters.',
                    },
                    {
                        key:         'memory_mode',
                        type:        'string',
                        description: 'Controls memory storage behaviour. "auto" (default) — the hybrid classifier decides. "force" — always store the message before replying. "off" — never store; context is still assembled and the AI still replies.',
                    },
                    {
                        key:         'context',
                        type:        'boolean',
                        description: 'Optional. Default true. When false, no memory context is assembled or injected into the AI prompt. The AI replies without personalisation.',
                    },
                    {
                        key:         'dry_run',
                        type:        'boolean',
                        description: 'Optional. Default false. When true, only the memory classifier runs. No memory is stored, no context is assembled, and no AI call is made. Returns the classifier decision only. Useful for debugging what would be remembered.',
                    },
                    {
                        key:         'debug',
                        type:        'boolean',
                        description: 'Optional. Default false. When true, adds a debug block to the response containing prompt_version, classifier_confidence, context_segments, circuit_breaker state, and any pipeline warnings.',
                    },
                    {
                        key:         'idempotency_key',
                        type:        'string',
                        description: 'Optional. Max 128 characters. If the same key is received again within 5 minutes, the original response is returned immediately with no additional AI calls or memory writes. Also accepted as the Idempotency-Key HTTP header (header takes priority).',
                    },
                ]}
                responses={{
                    ok: `{
  "request_id": "tm_chat_01K1E6G9XYZABCDE",
  "reply": "Great choice! I'll remember that you prefer React over Vue.",
  "memory": {
    "saved": true,
    "type": "preference",
    "reason": "Matched personal preference pattern",
    "via": "heuristic"
  },
  "context": {
    "used": true,
    "memories": 5,
    "tokens": 312,
    "candidate_count": 18,
    "returned_count": 5,
    "assembled_from": ["preference", "fact"]
  },
  "provider": "nvidia",
  "model": "openai/gpt-oss-20b",
  "latency_ms": {
    "classifier": 0,
    "memory": 44,
    "context": 28,
    "llm": 587,
    "total": 659
  }
}`,
                    badRequest: `{
  "message": "The message field is required."
}`,
                }}
                snippets={{
                    curl: `# Standard chat call (auto mode)
curl -X POST "${apiUrl}/v1/chat" \\
  -H "Authorization: Bearer cmlive_xxx" \\
  -H "Content-Type: application/json" \\
  -d '{
    "user_id": "customer_8472",
    "message": "I prefer React over Vue."
  }'

# Dry-run: preview memory decision without storing
curl -X POST "${apiUrl}/v1/chat" \\
  -H "Authorization: Bearer cmlive_xxx" \\
  -H "Content-Type: application/json" \\
  -d '{
    "user_id": "customer_8472",
    "message": "I prefer React over Vue.",
    "dry_run": true
  }'

# Force memory storage (always remember)
curl -X POST "${apiUrl}/v1/chat" \\
  -H "Authorization: Bearer cmlive_xxx" \\
  -H "Content-Type: application/json" \\
  -d '{
    "user_id": "customer_8472",
    "message": "My name is Alex.",
    "memory_mode": "force"
  }'`,
                    python: `import requests

headers = {"Authorization": "Bearer cmlive_xxx"}

# Standard chat call
response = requests.post(
    "${apiUrl}/v1/chat",
    headers=headers,
    json={"user_id": "customer_8472", "message": "I prefer React over Vue."}
)
data = response.json()
print(data["reply"])
print("Memory saved:", data["memory"]["saved"])

# Dry-run: preview memory decision only
preview = requests.post(
    "${apiUrl}/v1/chat",
    headers=headers,
    json={
        "user_id": "customer_8472",
        "message": "I prefer React over Vue.",
        "dry_run": True
    }
)
print(preview.json())
# {"dry_run": true, "would_remember": true, "type": "preference", ...}`,
                    javascript: `import axios from "axios";

const headers = { Authorization: "Bearer cmlive_xxx" };

// Standard chat call
const { data } = await axios.post(
  "${apiUrl}/v1/chat",
  { user_id: "customer_8472", message: "I prefer React over Vue." },
  { headers }
);
console.log(data.reply);
console.log("Memory saved:", data.memory.saved);
console.log("Request ID:", data.request_id);

// Dry-run: preview memory decision before committing
const { data: preview } = await axios.post(
  "${apiUrl}/v1/chat",
  { user_id: "customer_8472", message: "I prefer React over Vue.", dry_run: true },
  { headers }
);
// { dry_run: true, would_remember: true, type: "preference", via: "heuristic" }

// Idempotent request — safe to retry on network failure
await axios.post(
  "${apiUrl}/v1/chat",
  { user_id: "customer_8472", message: "My name is Alex.", memory_mode: "force" },
  { headers: { ...headers, "Idempotency-Key": "req_unique_abc123" } }
);`,
                    php: `$headers = ['Authorization' => 'Bearer cmlive_xxx'];

// Standard chat call
$response = Http::withHeaders($headers)
    ->post('${apiUrl}/v1/chat', [
        'user_id' => 'customer_8472',
        'message' => 'I prefer React over Vue.',
    ]);

$data = $response->json();
echo $data['reply'];
echo 'Memory saved: ' . ($data['memory']['saved'] ? 'yes' : 'no');

// Dry-run: preview memory decision only
$preview = Http::withHeaders($headers)
    ->post('${apiUrl}/v1/chat', [
        'user_id'  => 'customer_8472',
        'message'  => 'I prefer React over Vue.',
        'dry_run'  => true,
    ]);
// { "dry_run": true, "would_remember": true, "type": "preference", ... }

// Force memory storage with idempotency key
Http::withHeaders(array_merge($headers, [
        'Idempotency-Key' => 'req_unique_abc123',
    ]))
    ->post('${apiUrl}/v1/chat', [
        'user_id'     => 'customer_8472',
        'message'     => 'My name is Alex.',
        'memory_mode' => 'force',
    ]);`,
                    java: `HttpClient client = HttpClient.newHttpClient();
String auth = "Bearer cmlive_xxx";

// Standard chat call
String body = "{\"user_id\":\"customer_8472\",\"message\":\"I prefer React over Vue.\"}";

HttpRequest request = HttpRequest.newBuilder()
    .uri(URI.create("${apiUrl}/v1/chat"))
    .header("Authorization", auth)
    .header("Content-Type", "application/json")
    .POST(HttpRequest.BodyPublishers.ofString(body))
    .build();

HttpResponse<String> response = client.send(request,
    HttpResponse.BodyHandlers.ofString());
// response.headers().firstValue("X-Request-ID") → "tm_chat_..."

// Dry-run
String dryBody = """
    {
        "user_id": "customer_8472",
        "message": "I prefer React over Vue.",
        "dry_run": true
    }""";

HttpRequest dryRequest = HttpRequest.newBuilder()
    .uri(URI.create("${apiUrl}/v1/chat"))
    .header("Authorization", auth)
    .header("Content-Type", "application/json")
    .POST(HttpRequest.BodyPublishers.ofString(dryBody))
    .build();`,
                    go: `client := &http.Client{}

// Standard chat call
payload := `+"`"+`{"user_id":"customer_8472","message":"I prefer React over Vue."}`+"`"+`

req, _ := http.NewRequest("POST", "${apiUrl}/v1/chat",
    strings.NewReader(payload))
req.Header.Set("Authorization", "Bearer cmlive_xxx")
req.Header.Set("Content-Type", "application/json")

resp, _ := client.Do(req)
defer resp.Body.Close()
// resp.Header.Get("X-Request-ID") → "tm_chat_..."

// Dry-run
dryPayload := `+"`"+`{"user_id":"customer_8472","message":"I prefer React over Vue.","dry_run":true}`+"`"+`

dryReq, _ := http.NewRequest("POST", "${apiUrl}/v1/chat",
    strings.NewReader(dryPayload))
dryReq.Header.Set("Authorization", "Bearer cmlive_xxx")
dryReq.Header.Set("Content-Type", "application/json")

dryResp, _ := client.Do(dryReq)
defer dryResp.Body.Close()`,
                }}
                groups={apiRefGroups}
                prev={{ href: '/api-reference/context-assemble', label: 'Context assemble' }}
            >
                <div className="pt-4 border-t border-zinc-800/50 mt-8 space-y-4">
                    <h2 className="text-white text-lg font-semibold mb-3">Identity & Isolation</h2>
                    <ul className="list-disc pl-5 space-y-2 text-zinc-400">
                        <li><strong>Complete Isolation:</strong> This endpoint performs classification, context assembly, and LLM generation purely within the memory graph defined by the <code>(tenant_scope_id, workspace_id, user_id)</code> tuple.</li>
                        <li><strong>Identity Expectation:</strong> TraceMem trusts the client to pass the correct, stable <code>user_id</code> for whoever is initiating this chat session. This ensures the AI's personalized reply is strictly grounded in their specific memory history.</li>
                    </ul>
                </div>
            </ApiReferencePage>
        </>
    );
}
