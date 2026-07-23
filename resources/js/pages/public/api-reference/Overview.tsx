import { Head } from '@inertiajs/react';
import { Helmet } from 'react-helmet-async';
import ApiReferencePage from '@/components/public/api-reference-page';
import { apiRefGroups } from '@/components/public/api-ref-nav';
import { useDomains } from '@/lib/domains';

export default function Overview() {
    const { siteUrl } = useDomains();
    return (
        <>
            <Helmet>
                <title>API Reference Overview | TraceMem</title>
                <meta name="description" content="Official TraceMem API reference. Explore endpoints and integrate persistent, semantic, and AI-assembled memory into your applications using our REST API." />
                <meta name="keywords" content="TraceMem API, memory API, REST API, semantic memory, persistent memory, API docs, LLM memory API, TraceMem developer, AI memory integration" />
                <meta property="og:title" content="API Reference | TraceMem" />
                <meta property="og:description" content="Full documentation for the TraceMem REST API. Learn how to store, recall, and contextually assemble memory for modern AI and LLM apps." />
                <meta property="og:type" content="website" />
                <meta property="og:url" content={`${siteUrl}/api-reference`} />
                <meta property="og:image" content={`${siteUrl}/og-image.png`} />
                <meta property="og:image:width"  content="1200" />
                <meta property="og:image:height" content="630" />
                <meta property="og:image:alt"    content="TraceMem - Long-Term Memory Infrastructure for AI" />
                <meta property="og:site_name"    content="TraceMem" />
                <meta property="og:locale"       content="en_US" />
                <meta name="twitter:card"        content="summary_large_image" />
                <meta name="twitter:title"       content="API Reference | TraceMem" />
                <meta name="twitter:description" content="Full documentation for the TraceMem REST API. Learn how to store, recall, and contextually assemble memory for AI apps." />
                <meta name="twitter:image"       content={`${siteUrl}/og-image.png`} />
                <meta name="twitter:image:alt"   content="TraceMem - Long-Term Memory Infrastructure for AI" />
                <link rel="canonical" href={`${siteUrl}/api-reference`} />
            </Helmet>

            <Head title="API Reference Overview" />

            <ApiReferencePage
                title="TraceMem REST API"
                description="A developer-first memory layer for AI applications. Store structured meaning, recall semantically relevant memories, and assemble prompt-ready context, all over a clean, authenticated REST interface."
                endpoint="/v1"
                method="OVERVIEW"
                auth="Bearer API tokens are required for all protected endpoints. Test keys (cmtest_) use semantic-only mode with rate limits. Live keys (cmlive_) unlock AI-first mode and higher throughput."
                body={[]}
                responses={{
                    ok:         '{ "ok": true }',
                    badRequest: '{ "message": "Missing API key." }',
                }}
                snippets={{
                    python:     '# See individual endpoint pages for full request examples.',
                    javascript: '// See individual endpoint pages for full request examples.',
                    php:        '<?php // See individual endpoint pages for full request examples.',
                    curl:       '# See individual endpoint pages for full request examples.',
                    java:       '// See individual endpoint pages for full request examples.',
                    go:         '// See individual endpoint pages for full request examples.',
                }}
                groups={apiRefGroups}
                next={{ href: '/api-reference/quick-start', label: 'Quick start' }}
            >
                <div className="pt-4 border-t border-zinc-800/50 mt-8 space-y-8">
                    <section>
                        <h2 className="text-white text-lg font-semibold mb-3">Authentication</h2>
                        <p className="mb-4">
                            All API requests must be authenticated using an <code>X-API-Key</code> header. 
                            TraceMem supports two types of keys:
                        </p>
                        <ul className="list-disc pl-5 space-y-2 text-zinc-400">
                            <li><strong>Test Keys (<code>cmtest_</code>):</strong> Restricted to localhost/Postman. Useful for local development.</li>
                            <li><strong>Live Keys (<code>cmlive_</code>):</strong> Used in production environments. Requires HTTPS.</li>
                        </ul>
                        <p className="mt-4">
                            Authentication is performed <em>entirely</em> through the API key. There are no end-user tokens or OAuth flows for TraceMem.
                        </p>
                    </section>

                    <section>
                        <h2 className="text-white text-lg font-semibold mb-3">Identity Model</h2>
                        <p className="mb-4">
                            TraceMem relies on a strict identity hierarchy to ensure absolute data isolation:
                        </p>
                        <div className="bg-[#111] border border-zinc-800/80 rounded-md p-4 font-mono text-sm text-zinc-400">
                            TraceMem Tenant <br/>
                            &nbsp;&nbsp;↓ <br/>
                            Workspace <br/>
                            &nbsp;&nbsp;&nbsp;&nbsp;↓ <br/>
                            End User (user_id) <br/>
                            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;↓ <br/>
                            Memory
                        </div>
                    </section>

                    <section>
                        <h2 className="text-white text-lg font-semibold mb-3">Memory Boundary</h2>
                        <p className="mb-4">
                            Every single memory in TraceMem is strictly scoped by the following tuple:
                        </p>
                        <p className="font-mono text-sm text-amber-500 bg-amber-500/10 border border-amber-500/20 px-3 py-2 rounded-md inline-block mb-4">
                            (tenant_scope_id, workspace_id, user_id)
                        </p>
                        <p>
                            Memory retrieval never crosses any of these boundaries. A query belonging to one <code>user_id</code> will physically never retrieve memories belonging to another user, even within the same workspace.
                        </p>
                    </section>

                    <section className="space-y-6">
                        <div>
                            <h3 className="text-white font-medium mb-2">1. <code>tenant_scope_id</code></h3>
                            <ul className="list-disc pl-5 space-y-1 text-zinc-400 text-sm">
                                <li>Automatically resolved from your authenticated API Key.</li>
                                <li><strong>Never supplied by the client.</strong></li>
                                <li>Used internally for tenant isolation and billing aggregation.</li>
                            </ul>
                        </div>
                        
                        <div>
                            <h3 className="text-white font-medium mb-2">2. <code>workspace_id</code></h3>
                            <ul className="list-disc pl-5 space-y-1 text-zinc-400 text-sm">
                                <li>Automatically resolved from your authenticated API Key.</li>
                                <li><strong>Never supplied by the client.</strong></li>
                                <li>Defines the strict workspace boundary. API keys and memories are permanently bound to a single workspace.</li>
                            </ul>
                        </div>

                        <div>
                            <h3 className="text-white font-medium mb-2">3. <code>user_id</code></h3>
                            <ul className="list-disc pl-5 space-y-1 text-zinc-400 text-sm">
                                <li><strong>Required</strong> in the JSON payload on every memory-related endpoint.</li>
                                <li>Supplied by you, the API client.</li>
                                <li>Represents your downstream user or entity (e.g., <code>customer_8472</code>, <code>patient_991</code>, <code>employee_42</code>, <code>session_abcd1234</code>).</li>
                                <li>Must remain stable across sessions. TraceMem groups memories using this identifier.</li>
                                <li>Changing <code>user_id</code> creates a completely different memory graph. TraceMem never merges identities automatically.</li>
                            </ul>
                        </div>
                    </section>

                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mt-8 pt-8 border-t border-zinc-800/50">
                        <section>
                            <h2 className="text-white text-lg font-semibold mb-3">Client Responsibilities</h2>
                            <ul className="list-disc pl-5 space-y-2 text-zinc-400">
                                <li>Authenticating your own end-users securely.</li>
                                <li>Supplying stable <code>user_id</code> values in your API payloads.</li>
                                <li>Protecting your Live API Keys.</li>
                                <li>Maintaining identity consistency across your user's sessions.</li>
                            </ul>
                        </section>
                        <section>
                            <h2 className="text-white text-lg font-semibold mb-3">TraceMem Responsibilities</h2>
                            <ul className="list-disc pl-5 space-y-2 text-zinc-400">
                                <li>Guaranteeing strict tenant and workspace isolation.</li>
                                <li>Secure API key resolution.</li>
                                <li>Immutable workspace binding for keys and memories.</li>
                                <li>Semantic retrieval and long-term memory persistence.</li>
                            </ul>
                        </section>
                    </div>
                </div>
            </ApiReferencePage>
        </>
    );
}