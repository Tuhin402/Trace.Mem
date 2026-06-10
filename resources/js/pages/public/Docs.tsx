import { Head, Link } from '@inertiajs/react';
import { Helmet } from 'react-helmet-async';
import { ArrowRight, BookOpen, Zap, Brain, Layers, Code2, Target, Pin, Scale, Wrench, Inbox, Wand2, GitMerge, PackageCheck } from 'lucide-react';
import CtaButton from '@/components/public/cta-button';
import FaqAccordion from '@/components/public/faq-accordion';

/* ── Memory types data ────────────────────────────────────── */
const memoryTypes = [
    {
        key: 'preference',
        icon: <Target size={22} />,
        title: 'Preference',
        desc: 'User-stated or inferred likes, dislikes, communication styles, formatting choices, and behavioral tendencies. These are extracted from conversational cues and stored persistently to personalise future responses.',
        examples: ['"Prefers bullet points"', '"Dislikes formal tone"', '"Uses dark mode"'],
        badge: 'preference',
    },
    {
        key: 'fact',
        icon: <Pin size={22} />,
        title: 'Fact',
        desc: 'Stable, objective information about the user or their world, names, roles, projects, affiliations, and declared knowledge. Facts form the stable foundation of the memory graph.',
        examples: ['"Name: Sarah"', '"Role: ML Engineer"', '"Uses Python"'],
        badge: 'fact',
    },
    {
        key: 'rule',
        icon: <Scale size={22} />,
        title: 'Rule',
        desc: 'Constraints, invariants, and non-negotiables. Rules govern how the AI should or must behave in specific situations, they override general defaults and are strictly respected.',
        examples: ['"Never use jargon"', '"Always cite sources"', '"Respond in English"'],
        badge: 'rule',
    },
    {
        key: 'skill',
        icon: <Wrench size={22} />,
        title: 'Skill',
        desc: 'Capabilities, competencies, and areas of expertise. Skills describe what the user can do, knows how to do, or wants to learn, helping the AI calibrate explanations and suggestions appropriately.',
        examples: ['"Knows React"', '"Beginner at ML"', '"Proficient in SQL"'],
        badge: 'skill',
    },
];

/* ── How it works flow steps ──────────────────────────────── */
const flowSteps = [
    {
        num: '01',
        icon: <Inbox size={22} />,
        label: 'Capture',
        title: 'Ingest',
        desc: 'Raw conversation turns, messages, or structured records are submitted to the /remember endpoint via REST API.',
    },
    {
        num: '02',
        icon: <Wand2 size={22} />,
        label: 'Normalize',
        title: 'Normalize',
        desc: 'Content is cleaned, language-normalized, and pre-processed to prepare for semantic extraction.',
    },
    {
        num: '03',
        icon: <Brain size={22} />,
        label: 'Extract',
        title: 'Extract',
        desc: 'The AI pipeline parses intent, extracts memory candidates, and classifies them by type: preference, fact, rule, or skill.',
    },
    {
        num: '04',
        icon: <GitMerge size={22} />,
        label: 'Dedupe',
        title: 'Deduplicate',
        desc: 'New memories are checked against existing ones. Conflicts are resolved automatically. Duplicates are merged or discarded.',
    },
    {
        num: '05',
        icon: <Zap size={22} />,
        label: 'Recall',
        title: 'Recall',
        desc: 'On /recall, the most semantically relevant memories are retrieved using vector similarity, ranked by recency and salience.',
    },
    {
        num: '06',
        icon: <PackageCheck size={22} />,
        label: 'Assemble',
        title: 'Assemble',
        desc: 'Retrieved memories are assembled into a compact, prompt-ready context block. Inject it before your LLM call, zero extra work.',
    },
];

/* ── FAQ items ────────────────────────────────────────────── */
const docsFaq = [
    {
        q: 'How do I authenticate with the TraceMem API?',
        a: 'All requests require a Bearer token in the Authorization header. You can generate API keys from your TraceMem dashboard. Both live keys (cmlive_) and test keys (cmtest_) are supported.',
    },
    {
        q: 'What is the difference between /remember and /recall?',
        a: '/remember stores new memory content for a user. /recall retrieves the most relevant memories given a query string and user ID. Both endpoints use the same authentication scheme.',
    },
    {
        q: 'Is TraceMem model-agnostic?',
        a: 'Yes. TraceMem integrates at the API level and is fully model-agnostic. Use it alongside OpenAI, Anthropic Claude, Google Gemini, Mistral, or any open-source model via Hugging Face or Ollama.',
    },
    {
        q: 'How are memories isolated between users?',
        a: 'Every memory is scoped to a user_id and your API key. Users never share context. All data is encrypted at rest (AES-256) and in transit (TLS 1.3). Tenant boundaries are cryptographically enforced.',
    },
    // {
    //     q: 'Can I delete or update individual memories?',
    //     a: 'Yes. The API provides endpoints to list, update, and hard-delete specific memories by their ID. You can also bulk-clear all memories for a given user.',
    // },
];

/* ── Quick links panel ────────────────────────────────────── */
const quickLinks = [
    {
        icon: <BookOpen size={18} />,
        label: 'API Reference',
        sub: 'Full endpoint docs and examples',
        href: '/api-reference',
        external: false,
    },
    {
        icon: <Code2 size={18} />,
        label: 'Postman Workspace',
        sub: 'Fork and run API calls instantly',
        href: import.meta.env.VITE_POSTMAN_WORKSPACE_URL,
        external: true,
    },
    {
        icon: <Zap size={18} />,
        label: 'Quick Start Guide',
        sub: 'Up and running in 5 minutes',
        href: '/api-reference',
        external: false,
    },
];

/* ════════════════════════════════════════════════════════════ */
export default function Docs() {
    const postmanUrl = import.meta.env.VITE_POSTMAN_WORKSPACE_URL;

    return (
        <>
            <Helmet>
                <title>Documentation | TraceMem</title>
                <meta
                    name="description"
                    content="Official documentation for TraceMem, the context memory layer for AI applications. Learn how to store, recall, and assemble structured memory for LLMs."
                />
                <meta
                    name="keywords"
                    content="AI memory API, LLM memory layer, semantic memory, contextual memory, RAG memory, AI infrastructure, TraceMem docs"
                />
                <meta property="og:title" content="TraceMem Documentation" />
                <meta property="og:description" content="Persistent memory infrastructure for AI and LLM applications." />
                <meta property="og:type" content="website" />
                <meta property="og:url" content="https://tracemem.one/docs" />
                <link rel="canonical" href="https://tracemem.one/docs" />
            </Helmet>

            <Head title="Documentation" />

            <div className="docs-shell">

                {/* ══ 1. HERO ═══════════════════════════════════════════ */}
                <section className="docs-hero" aria-label="Documentation hero">
                    <div className="docs-hero-blob" aria-hidden="true" />

                    <div className="docs-hero-inner">
                        {/* Left: headline + CTA */}
                        <div className="docs-hero-left">
                            <span className="docs-hero-eyebrow">Developer Documentation</span>

                            <h1 className="docs-hero-h1">
                                Build smarter AI with{' '}
                                <span className="serif-accent">structured memory.</span>
                            </h1>

                            <p className="docs-hero-lead">
                                TraceMem is the context memory layer for AI and LLM applications.
                                Store structured memories, recall relevant context, and assemble
                                prompt-ready knowledge, all through a simple REST API.
                            </p>

                            <div className="docs-hero-actions">
                                <CtaButton href="/api-reference" label="API Reference" size="lg" />
                                <CtaButton
                                    href={postmanUrl}
                                    label="Postman Workspace"
                                    variant="secondary"
                                    size="lg"
                                    external={true}
                                />
                            </div>
                        </div>

                        {/* Right: quick nav panel */}
                        <div className="docs-hero-right">
                            <div className="docs-hero-panel">
                                <div className="docs-panel-title">Quick Navigation</div>
                                <div className="docs-panel-links">
                                    {quickLinks.map((link) =>
                                        link.external ? (
                                            <a
                                                key={link.label}
                                                href={link.href}
                                                target="_blank"
                                                rel="noreferrer"
                                                className="docs-panel-link"
                                            >
                                                <div className="docs-panel-link-left">
                                                    <div className="docs-panel-link-icon">
                                                        {link.icon}
                                                    </div>
                                                    <div>
                                                        <div className="docs-panel-link-label">
                                                            {link.label}
                                                        </div>
                                                        <div className="docs-panel-link-sub">
                                                            {link.sub}
                                                        </div>
                                                    </div>
                                                </div>
                                                <ArrowRight
                                                    size={16}
                                                    className="docs-panel-link-arrow"
                                                />
                                            </a>
                                        ) : (
                                            <Link
                                                key={link.label}
                                                href={link.href}
                                                className="docs-panel-link"
                                            >
                                                <div className="docs-panel-link-left">
                                                    <div className="docs-panel-link-icon">
                                                        {link.icon}
                                                    </div>
                                                    <div>
                                                        <div className="docs-panel-link-label">
                                                            {link.label}
                                                        </div>
                                                        <div className="docs-panel-link-sub">
                                                            {link.sub}
                                                        </div>
                                                    </div>
                                                </div>
                                                <ArrowRight
                                                    size={16}
                                                    className="docs-panel-link-arrow"
                                                />
                                            </Link>
                                        )
                                    )}
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                {/* ══ 2. GETTING STARTED ════════════════════════════════ */}
                <section className="docs-section docs-section-dark" aria-label="Getting started">
                    <div className="docs-section-inner">
                        <div className="docs-section-head">
                            <span className="docs-section-tag">Getting Started</span>
                            <h2 className="docs-section-h2">
                                What is TraceMem, and why does it matter?
                            </h2>
                            <p className="docs-section-lead">
                                Most AI applications treat each conversation as stateless. TraceMem
                                gives your AI a persistent, structured memory, so it learns and
                                improves across every interaction.
                            </p>
                        </div>

                        <div className="docs-gs-grid">
                            <div className="docs-gs-card">
                                <div className="docs-gs-card-num">01 / What it does</div>
                                <div className="docs-gs-card-icon">
                                    <Brain size={36} style={{ color: 'var(--tm-primary)' }} />
                                </div>
                                <h3 className="docs-gs-card-title">Persistent AI Memory</h3>
                                <p className="docs-gs-card-desc">
                                    TraceMem extracts structured memories, facts, preferences, rules,
                                    and skills, from raw conversations and stores them persistently.
                                    Your AI retains what matters, across sessions, users, and contexts.
                                </p>
                            </div>

                            <div className="docs-gs-card">
                                <div className="docs-gs-card-num">02 / Why it matters</div>
                                <div className="docs-gs-card-icon">
                                    <Layers size={36} style={{ color: 'var(--tm-secondary)' }} />
                                </div>
                                <h3 className="docs-gs-card-title">Context That Scales</h3>
                                <p className="docs-gs-card-desc">
                                    Without structured memory, users repeat themselves. Responses
                                    become generic. TraceMem solves this by maintaining a rich,
                                    user-specific context graph, recalling only what's relevant,
                                    never flooding your prompt window.
                                </p>
                            </div>

                            <div className="docs-gs-card">
                                <div className="docs-gs-card-num">03 / How to integrate</div>
                                <div className="docs-gs-card-icon">
                                    <Zap size={36} style={{ color: 'var(--tm-tertiary)' }} />
                                </div>
                                <h3 className="docs-gs-card-title">Two API Calls</h3>
                                <p className="docs-gs-card-desc">
                                    Integrate with two endpoints: <code style={{ fontFamily: 'var(--font-mono)', fontSize: 13 }}>/remember</code> to store
                                    memories and <code style={{ fontFamily: 'var(--font-mono)', fontSize: 13 }}>/recall</code> to retrieve them. Inject the
                                    returned context into your LLM prompt. No infrastructure changes.
                                    Works with any model.
                                </p>
                            </div>
                        </div>
                    </div>
                </section>

                {/* ══ 3. MEMORY TYPES ═══════════════════════════════════ */}
                <section
                    className="docs-section docs-section-alt"
                    aria-label="Memory types"
                >
                    <div className="docs-section-inner">
                        <div className="docs-section-head center">
                            <span className="docs-section-tag">Memory Types</span>
                            <h2 className="docs-section-h2">
                                Four classes of structured memory
                            </h2>
                            <p className="docs-section-lead center">
                                TraceMem classifies every extracted memory into one of four semantic
                                types. Each type is stored, retrieved, and assembled independently,
                                giving your AI precise, contextually correct recall.
                            </p>
                        </div>

                        <div className="docs-memory-grid">
                            {memoryTypes.map((type) => (
                                <div className="docs-memory-card" key={type.key}>
                                    <div
                                        className={`docs-memory-card-accent ${type.badge}`}
                                        aria-hidden="true"
                                    />
                                    <div className="docs-memory-card-header">
                                        <span
                                            className={`docs-memory-card-badge ${type.badge}`}
                                        >
                                            {type.badge}
                                        </span>
                                        <div className="docs-memory-card-icon-wrap">
                                            {type.icon}
                                        </div>
                                    </div>
                                    <h3 className="docs-memory-card-title">{type.title}</h3>
                                    <p className="docs-memory-card-desc">{type.desc}</p>
                                    <div className="docs-memory-card-examples">
                                        {type.examples.map((ex) => (
                                            <span key={ex} className="docs-memory-card-example">
                                                {ex}
                                            </span>
                                        ))}
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                </section>

                {/* ══ 4. HOW IT WORKS ═══════════════════════════════════ */}
                <section
                    className="docs-section docs-section-texture"
                    aria-label="How TraceMem works"
                >
                    <div className="docs-section-inner">
                        <div className="docs-section-head center">
                            <span className="docs-section-tag">The Memory Pipeline</span>
                            <h2 className="docs-section-h2">How TraceMem works</h2>
                            <p className="docs-section-lead center">
                                A transparent, six-stage pipeline between your users and your LLM.
                                Every memory passes through capture, normalization, extraction,
                                deduplication, recall, and assembly, automatically.
                            </p>
                        </div>

                        <div className="docs-flow-steps">
                            {flowSteps.map((step) => (
                                <div className="docs-flow-step" key={step.num}>
                                    <div className="docs-flow-step-num" aria-hidden="true">
                                        {step.num}
                                    </div>
                                    <div className="docs-flow-step-icon">{step.icon}</div>
                                    <div className="docs-flow-step-label">{step.label}</div>
                                    <h3 className="docs-flow-step-title">{step.title}</h3>
                                    <p className="docs-flow-step-desc">{step.desc}</p>
                                </div>
                            ))}
                        </div>
                    </div>
                </section>

                {/* ══ 5. API REFERENCE CTA ══════════════════════════════ */}
                <section
                    className="docs-section docs-section-dark"
                    aria-label="API Reference redirect"
                >
                    <div className="docs-section-inner">
                        <div className="docs-api-cta">
                            <div className="docs-api-cta-left">
                                <div className="docs-api-cta-tag">Developer Reference</div>
                                <h2 className="docs-api-cta-title">
                                    Explore the full API Reference
                                </h2>
                                <p className="docs-api-cta-desc">
                                    Complete endpoint documentation with request schemas, response
                                    formats, error codes, authentication guides, and live examples
                                    for every TraceMem operation. Start building in minutes.
                                </p>
                            </div>
                            <div className="docs-api-cta-right">
                                <CtaButton
                                    href="/api-reference"
                                    label="Open API Reference"
                                    size="lg"
                                />
                            </div>
                        </div>
                    </div>
                </section>

                {/* ══ 6. POSTMAN WORKSPACE ══════════════════════════════ */}
                <section
                    className="docs-section docs-section-alt"
                    aria-label="Postman workspace"
                >
                    <div className="docs-section-inner">
                        <div className="docs-section-head">
                            <span className="docs-section-tag">Test the API</span>
                            <h2 className="docs-section-h2">Try it in Postman</h2>
                            <p className="docs-section-lead">
                                Fork the TraceMem Postman workspace and start firing real API
                                requests in seconds. All endpoints, sample payloads, and
                                environment variables are pre-configured.
                            </p>
                        </div>

                        <div className="docs-postman-wrap">
                            {/* Left: main Postman card */}
                            <div className="docs-postman-card">
                                <div className="docs-postman-logo">
                                    <div className="docs-postman-logo-icon" style={{ display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
                                        <img
                                            src="https://voyager.postman.com/logo/postman-logo-icon-orange.svg"
                                            alt="Postman"
                                            width="28"
                                            height="28"
                                            style={{ objectFit: 'contain' }}
                                        />
                                    </div>
                                    <span className="docs-postman-logo-label">
                                        Postman Workspace
                                    </span>
                                </div>

                                <p className="docs-postman-desc">
                                    The official TraceMem Postman workspace contains every API
                                    endpoint, pre-filled with example request bodies, headers, and
                                    authentication templates. Fork it to your own workspace and
                                    start testing immediately, no configuration required.
                                </p>

                                <div className="docs-postman-feature-list">
                                    {[
                                        'All endpoints pre-configured with example payloads',
                                        'Environment variables for live and test API keys',
                                        'Ready-to-run /remember, /recall, and /assemble calls',
                                        'Fork once, use forever in your own Postman account',
                                    ].map((feat) => (
                                        <div className="docs-postman-feature" key={feat}>
                                            <div
                                                className="docs-postman-feature-dot"
                                                aria-hidden="true"
                                            />
                                            <span>{feat}</span>
                                        </div>
                                    ))}
                                </div>

                                <a
                                    href={postmanUrl}
                                    target="_blank"
                                    rel="noreferrer"
                                    className="docs-postman-btn"
                                    id="docs-postman-open"
                                >
                                    <span className="docs-postman-btn-label">
                                        Fork the Workspace
                                    </span>
                                    <span className="docs-postman-btn-arrow">
                                        <ArrowRight size={14} />
                                    </span>
                                </a>
                            </div>

                            {/* Right: info cards */}
                            <div className="docs-postman-right">
                                <div className="docs-postman-info-card">
                                    <div className="docs-postman-info-label">Authentication</div>
                                    <div className="docs-postman-info-value">Bearer Token</div>
                                    <div className="docs-postman-info-sub">
                                        Add your API key as a Bearer token. Use{' '}
                                        <code style={{ fontFamily: 'var(--font-mono)', fontSize: 11 }}>
                                            cmtest_
                                        </code>{' '}
                                        prefix for test keys.
                                    </div>
                                </div>

                                <div className="docs-postman-info-card">
                                    <div className="docs-postman-info-label">Base URL</div>
                                    <div className="docs-postman-info-value">
                                        tracemem.one/api/v1
                                    </div>
                                    <div className="docs-postman-info-sub">
                                        All endpoints are versioned under /api/v1. The workspace
                                        environment variable is pre-set.
                                    </div>
                                </div>

                                <div className="docs-postman-info-card">
                                    <div className="docs-postman-info-label">
                                        Supported Endpoints
                                    </div>
                                    <div className="docs-postman-info-value">
                                        /remember · /recall · /context/assemble
                                    </div>
                                    <div className="docs-postman-info-sub">
                                        Store, retrieve, and assemble memories.
                                    </div>
                                </div>

                                <div className="docs-postman-info-card">
                                    <div className="docs-postman-info-label">Content-Type</div>
                                    <div className="docs-postman-info-value">
                                        application/json
                                    </div>
                                    <div className="docs-postman-info-sub">
                                        All request and response bodies are JSON. The workspace
                                        headers are pre-configured.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                {/* ══ 7. FAQ ════════════════════════════════════════════ */}
                <section
                    className="docs-section docs-section-dark"
                    aria-label="Frequently asked questions"
                >
                    <div className="docs-section-inner">
                        <div className="docs-section-head center">
                            <span className="docs-section-tag">FAQ</span>
                            <h2 className="docs-section-h2">Common questions</h2>
                            <p className="docs-section-lead center">
                                Answers to the most frequently asked questions about TraceMem
                                integration, memory storage, and the API.
                            </p>
                        </div>

                        <div className="docs-faq-inner">
                            <FaqAccordion items={docsFaq} />
                        </div>

                        <div
                            style={{
                                marginTop: 40,
                                display: 'flex',
                                justifyContent: 'center',
                                gap: 16,
                                flexWrap: 'wrap',
                            }}
                        >
                            <CtaButton href="/api-reference" label="Read Full Docs" />
                            <CtaButton
                                href={postmanUrl}
                                label="Open Postman"
                                variant="secondary"
                                external={true}
                            />
                        </div>
                    </div>
                </section>

            </div>
        </>
    );
}