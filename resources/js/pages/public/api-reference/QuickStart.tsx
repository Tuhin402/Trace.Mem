import { Head } from '@inertiajs/react';
import ApiReferencePage from '@/components/public/api-reference-page';
import { apiRefGroups } from '@/components/public/api-ref-nav';
import { useDomains } from '@/lib/domains';

export default function QuickStart() {
    const { apiUrl } = useDomains();

    return (
        <>
            <Head title="Quick Start | TraceMem API" />

            <ApiReferencePage
                title="Quick Start"
                description="Make your first TraceMem request in under two minutes. Store a memory, recall it, and assemble prompt-ready context, all with a single Bearer token."
                endpoint="/api/v1/remember"
                method="POST"
                auth="Authorization: Bearer <api_key>  —  Test keys (cmtest_) use semantic-only mode."
                body={[
                    {
                        key:         'content',
                        type:        'string',
                        required:    true,
                        description: 'The raw user message, note, or statement you want to persist as a memory unit.',
                    },
                ]}
                responses={{
                    ok:         '{ "message": "Memory saved", "memory": { "id": "mem_xxx", "content": "User prefers short answers", "score": 0.94 } }',
                    badRequest: '{ "message": "Missing API key." }',
                }}
                snippets={{
                    python: `import requests

requests.post(
    "${apiUrl}/api/v1/remember",
    headers={"Authorization": "Bearer cmlive_xxx"},
    json={"content": "User prefers short answers"}
)`,
                    javascript: `import axios from "axios";

await axios.post(
  "${apiUrl}/api/v1/remember",
  { content: "User prefers short answers" },
  { headers: { Authorization: "Bearer cmlive_xxx" } }
);`,
                    php: `Http::withToken('cmlive_xxx')
    ->post('${apiUrl}/api/v1/remember', [
        'content' => 'User prefers short answers',
    ]);`,
                    curl: `curl -X POST "${apiUrl}/api/v1/remember" \\
  -H "Authorization: Bearer cmlive_xxx" \\
  -H "Content-Type: application/json" \\
  -d '{ "content": "User prefers short answers" }'`,
                    java: `HttpRequest request = HttpRequest.newBuilder()
    .uri(URI.create("${apiUrl}/api/v1/remember"))
    .header("Authorization", "Bearer cmlive_xxx")
    .header("Content-Type", "application/json")
    .POST(HttpRequest.BodyPublishers.ofString(
        "{\"content\":\"User prefers short answers\"}"
    ))
    .build();`,
                    go: `reqBody := strings.NewReader(\`{"content":"User prefers short answers"}\`)
req, _ := http.NewRequest("POST", "${apiUrl}/api/v1/remember", reqBody)
req.Header.Set("Authorization", "Bearer cmlive_xxx")
req.Header.Set("Content-Type", "application/json")`,
                }}
                groups={apiRefGroups}
                prev={{ href: '/api-reference',              label: 'Overview'         }}
                next={{ href: '/api-reference/core-operations', label: 'Core operations' }}
            />
        </>
    );
}