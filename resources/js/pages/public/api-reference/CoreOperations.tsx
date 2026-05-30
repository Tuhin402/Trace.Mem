import { Head } from '@inertiajs/react';
import ApiReferencePage from '@/components/public/api-reference-page';
import { apiRefGroups } from '@/components/public/api-ref-nav';

export default function CoreOperations() {
    return (
        <>
            <Head title="Core Operations | TraceMem API" />

            <ApiReferencePage
                title="Core Operations"
                description="TraceMem's memory layer is built around three atomic operations: remember stores structured memory, recall fetches semantically relevant memories, and context assemble builds prompt-ready context ranked by relevance to the current query."
                endpoint="TraceMem memory workflow"
                method="GUIDE"
                auth="All protected endpoints require an API key in the Authorization header."
                body={[
                    { key: 'content',       type: 'string',  description: 'The message, note, or statement to persist as a memory unit in the tenant scope.' },
                    { key: 'limit',         type: 'integer', description: 'Optional. Maximum number of ranked memory items to return. Defaults to the plan limit.' },
                    { key: 'query',         type: 'string',  description: 'The current user request or assistant prompt used to rank and filter context.' },
                    { key: 'token_budget',  type: 'integer', description: 'Optional. Maximum token budget for the assembled context window.' },
                ]}
                responses={{
                    ok:         '{ "message": "Core workflow active, remember, recall, assemble." }',
                    badRequest: '{ "message": "Missing API key." }',
                }}
                snippets={{
                    python: `import requests

headers = {"Authorization": "Bearer cmlive_xxx"}

# 1. Store a memory
requests.post(
    "https://tracemem.one/api/v1/remember",
    headers=headers,
    json={"content": "User likes React"}
)

# 2. Recall relevant memories
requests.post(
    "https://tracemem.one/api/v1/recall",
    headers=headers,
    json={"limit": 5}
)

# 3. Assemble prompt context
requests.post(
    "https://tracemem.one/api/v1/context/assemble",
    headers=headers,
    json={"query": "Help me answer this", "token_budget": 1200}
)`,
                    javascript: `import axios from "axios";

const headers = { Authorization: "Bearer cmlive_xxx" };

// 1. Store a memory
await axios.post("/api/v1/remember", { content: "User likes React" }, { headers });

// 2. Recall relevant memories
await axios.post("/api/v1/recall", { limit: 5 }, { headers });

// 3. Assemble prompt context
await axios.post("/api/v1/context/assemble",
  { query: "Help me answer this", token_budget: 1200 },
  { headers }
);`,
                    php: `$headers = ['Authorization' => 'Bearer cmlive_xxx'];

Http::withHeaders($headers)->post('/api/v1/remember',       ['content' => 'User likes React']);
Http::withHeaders($headers)->post('/api/v1/recall',         ['limit'   => 5]);
Http::withHeaders($headers)->post('/api/v1/context/assemble',
    ['query' => 'Help me answer this', 'token_budget' => 1200]
);`,
                    curl: `# 1. Remember
curl -X POST "https://tracemem.one/api/v1/remember" \\
  -H "Authorization: Bearer cmlive_xxx" \\
  -H "Content-Type: application/json" \\
  -d '{"content":"User likes React"}'

# 2. Recall
curl -X POST "https://tracemem.one/api/v1/recall" \\
  -H "Authorization: Bearer cmlive_xxx" \\
  -H "Content-Type: application/json" \\
  -d '{"limit": 5}'

# 3. Assemble context
curl -X POST "https://tracemem.one/api/v1/context/assemble" \\
  -H "Authorization: Bearer cmlive_xxx" \\
  -H "Content-Type: application/json" \\
  -d '{"query":"Help me answer this","token_budget":1200}'`,
                    java: `HttpClient client = HttpClient.newHttpClient();
String auth  = "Bearer cmlive_xxx";

// Remember
client.send(HttpRequest.newBuilder()
    .uri(URI.create("https://tracemem.one/api/v1/remember"))
    .header("Authorization", auth)
    .header("Content-Type", "application/json")
    .POST(HttpRequest.BodyPublishers.ofString("{\"content\":\"User likes React\"}"))
    .build(), HttpResponse.BodyHandlers.ofString());`,
                    go: `client  := &http.Client{}
headers := map[string]string{
    "Authorization": "Bearer cmlive_xxx",
    "Content-Type":  "application/json",
}

// Remember
body := strings.NewReader(\`{"content":"User likes React"}\`)
req, _ := http.NewRequest("POST", "https://tracemem.one/api/v1/remember", body)
for k, v := range headers { req.Header.Set(k, v) }
client.Do(req)`,
                }}
                groups={apiRefGroups}
                prev={{ href: '/api-reference/quick-start',   label: 'Quick start'         }}
                next={{ href: '/api-reference/authentication', label: 'Auth & authorization' }}
            />
        </>
    );
}