import { Head } from '@inertiajs/react';
import { Helmet } from 'react-helmet-async';
import ApiReferencePage from '@/components/public/api-reference-page';
import { apiRefGroups } from '@/components/public/api-ref-nav';
import { useDomains } from '@/lib/domains';

export default function CoreOperations() {
    const { apiUrl, siteUrl } = useDomains();

    return (
        <>
            <Helmet>
                <title>Core Operations | TraceMem API</title>
                <meta name="description" content="TraceMem's three core memory operations: remember, recall, and context assemble. Learn the workflow for AI-powered persistent memory." />
                <meta property="og:title" content="Core Operations | TraceMem API" />
                <meta property="og:description" content="TraceMem's memory layer is built around three atomic operations: remember stores structured memory, recall fetches semantically relevant memories, and context assemble builds prompt-ready context." />
                <meta property="og:type" content="website" />
                <meta property="og:url" content={`${siteUrl}/api-reference/core-operations`} />
                <meta property="og:image" content={`${siteUrl}/og-image.png`} />
                <meta property="og:image:width"  content="1200" />
                <meta property="og:image:height" content="630" />
                <meta property="og:image:alt"    content="TraceMem - Long-Term Memory Infrastructure for AI" />
                <meta property="og:site_name"    content="TraceMem" />
                <meta property="og:locale"       content="en_US" />
                <meta name="twitter:card"        content="summary_large_image" />
                <meta name="twitter:title"       content="Core Operations | TraceMem API" />
                <meta name="twitter:description" content="Three atomic memory operations: remember, recall, and context assemble. The foundation of TraceMem's AI memory layer." />
                <meta name="twitter:image"       content={`${siteUrl}/og-image.png`} />
                <meta name="twitter:image:alt"   content="TraceMem - Long-Term Memory Infrastructure for AI" />
                <link rel="canonical" href={`${siteUrl}/api-reference/core-operations`} />
            </Helmet>

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
    "${apiUrl}/api/v1/remember",
    headers=headers,
    json={"content": "User likes React"}
)

# 2. Recall relevant memories
requests.post(
    "${apiUrl}/api/v1/recall",
    headers=headers,
    json={"limit": 5}
)

# 3. Assemble prompt context
requests.post(
    "${apiUrl}/api/v1/context/assemble",
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
curl -X POST "${apiUrl}/api/v1/remember" \\
  -H "Authorization: Bearer cmlive_xxx" \\
  -H "Content-Type: application/json" \\
  -d '{"content":"User likes React"}'

# 2. Recall
curl -X POST "${apiUrl}/api/v1/recall" \\
  -H "Authorization: Bearer cmlive_xxx" \\
  -H "Content-Type: application/json" \\
  -d '{"limit": 5}'

# 3. Assemble context
curl -X POST "${apiUrl}/api/v1/context/assemble" \\
  -H "Authorization: Bearer cmlive_xxx" \\
  -H "Content-Type: application/json" \\
  -d '{"query":"Help me answer this","token_budget":1200}'`,
                    java: `HttpClient client = HttpClient.newHttpClient();
String auth  = "Bearer cmlive_xxx";

// Remember
client.send(HttpRequest.newBuilder()
    .uri(URI.create("${apiUrl}/api/v1/remember"))
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
req, _ := http.NewRequest("POST", "${apiUrl}/api/v1/remember", body)
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