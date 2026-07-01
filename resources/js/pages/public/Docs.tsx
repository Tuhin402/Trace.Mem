import { Head, Link } from '@inertiajs/react';
import { Helmet } from 'react-helmet-async';
import { useDomains } from '@/lib/domains';
import { ArrowRight, BookOpen, Zap, Brain, Layers, Code2, Target, Pin, Scale, Wrench, Inbox, Wand2, GitMerge, PackageCheck, Briefcase, Calendar, LayoutTemplate, Database, Users } from 'lucide-react';
import CtaButton from '@/components/public/cta-button';
import FaqAccordion from '@/components/public/faq-accordion';
import UseCaseCard from '@/components/public/use-case-card';

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
    const { siteUrl } = useDomains();
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
                <meta property="og:title" content="Documentation | TraceMem" />
                <meta property="og:description" content="Persistent memory infrastructure for AI and LLM applications. Learn how to store, recall, and assemble structured memory." />
                <meta property="og:type" content="website" />
                <meta property="og:url" content={`${siteUrl}/docs`} />
                <meta property="og:image" content={`${siteUrl}/og-image.png`} />
                <meta property="og:image:width"  content="1200" />
                <meta property="og:image:height" content="630" />
                <meta property="og:image:alt"    content="TraceMem - Long-Term Memory Infrastructure for AI" />
                <meta property="og:site_name"    content="TraceMem" />
                <meta property="og:locale"       content="en_US" />
                <meta name="twitter:card"        content="summary_large_image" />
                <meta name="twitter:title"       content="Documentation | TraceMem" />
                <meta name="twitter:description" content="Persistent memory infrastructure for AI and LLM applications. Learn how to store, recall, and assemble structured memory." />
                <meta name="twitter:image"       content={`${siteUrl}/og-image.png`} />
                <meta name="twitter:image:alt"   content="TraceMem - Long-Term Memory Infrastructure for AI" />
                <link rel="canonical" href={`${siteUrl}/docs`} />
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

                {/* ══ 2.5 USE CASES & SCOPE ════════════════════════════════ */}
                <section className="docs-section docs-section-alt" aria-label="Use cases and architecture">
                    <div className="docs-section-inner">
                        <div className="docs-section-head">
                            <span className="docs-section-tag">Scope & Fit</span>
                            <h2 className="docs-section-h2">Where TraceMem Fits</h2>
                            <p className="docs-section-lead">
                                TraceMem is designed for production applications that require persistent, semantic memory for their AI features.
                            </p>
                        </div>
                        
                        <div className="docs-use-cases-grid">
                            <UseCaseCard 
                                icon={<Briefcase size={20} />} 
                                title="B2B SaaS" 
                                desc="Maintain state across sessions. Remember tenant-specific business rules, user workflows, and preferences." 
                                tags={['Tenant Isolated', 'Rules']}
                            />
                            <UseCaseCard 
                                icon={<Users size={20} />} 
                                title="Support CRMs" 
                                desc="Equip AI agents with complete historical context of user issues to prevent repetitive context-gathering." 
                                tags={['Zero Repetition', 'Facts']}
                            />
                            <UseCaseCard 
                                icon={<Calendar size={20} />} 
                                title="Scheduling & Productivity" 
                                desc="Remember upcoming events, routines, and temporal data automatically without manual calendar syncing." 
                                tags={['Events', 'Temporal']}
                            />
                            <UseCaseCard 
                                icon={<LayoutTemplate size={20} />} 
                                title="Note-taking Apps" 
                                desc="Extract structured knowledge from unstructured notes to assemble highly relevant context graphs." 
                                tags={['Extraction', 'RAG']}
                            />
                            <UseCaseCard 
                                icon={<Code2 size={20} />} 
                                title="Developer Tools" 
                                desc="Persist code styles, architectural preferences, and snippets across the entire developer lifecycle." 
                                tags={['Code Aware', 'Skills']}
                            />
                            <UseCaseCard 
                                icon={<Brain size={20} />} 
                                title="AI Assistants" 
                                desc="Give personal copilots a human-like memory that evolves with the user, learning their habits over time." 
                                tags={['Personalized', 'Habits']}
                            />
                        </div>

                        <div className="docs-architecture">
                            <div className="docs-section-head" style={{ marginBottom: '32px' }}>
                                <h3 className="docs-section-h2" style={{ fontSize: '24px' }}>Architecture & Project Flow</h3>
                                <p className="docs-section-lead">
                                    TraceMem operates as a fast, stateless middleware layer between your app's frontend/backend and your LLM provider.
                                </p>
                            </div>

                            <div className="arch-flow-diagram">
                                <div className="arch-node">
                                    <div className="arch-node-icon"><Users size={24} /></div>
                                    <div className="arch-node-content">
                                        <div className="arch-node-title">1. Your Application</div>
                                        <div className="arch-node-desc">User interacts with your SaaS, CRM, or AI assistant. Your backend sends the raw conversational turn to TraceMem.</div>
                                    </div>
                                </div>
                                <div className="arch-arrow">↓</div>
                                <div className="arch-node">
                                    <div className="arch-node-icon"><Inbox size={24} /></div>
                                    <div className="arch-node-content">
                                        <div className="arch-node-title">2. Memory Ingestion & Classification</div>
                                        <div className="arch-node-desc">TraceMem detects if the input contains schedule-like, code-like, preference-like, or factual data, and breaks it into atomic pieces.</div>
                                    </div>
                                </div>
                                <div className="arch-arrow">↓</div>
                                <div className="arch-node">
                                    <div className="arch-node-icon"><Database size={24} /></div>
                                    <div className="arch-node-content">
                                        <div className="arch-node-title">3. Secure Storage</div>
                                        <div className="arch-node-desc">The extracted semantic memories are embedded and stored in a cryptographically isolated tenant vault.</div>
                                    </div>
                                </div>
                                <div className="arch-arrow">↓</div>
                                <div className="arch-node">
                                    <div className="arch-node-icon"><Zap size={24} /></div>
                                    <div className="arch-node-content">
                                        <div className="arch-node-title">4. Context Assembly</div>
                                        <div className="arch-node-desc">Before generating a response, your app queries TraceMem. Relevant memories are recalled and assembled into prompt-ready context.</div>
                                    </div>
                                </div>
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
                                        <svg
                                            fill="none"
                                            height="28"
                                            viewBox="0 0 32 32"
                                            width="28"
                                            xmlns="http://www.w3.org/2000/svg"
                                            aria-label="Postman"
                                            role="img"
                                        >
                                            <path d="m18.0379.13033c-3.1388-.403077-6.3266.133689-9.16038 1.54242-2.83373 1.40873-5.18608 3.62614-6.75956 6.37182-1.573474 2.74573-2.297402 5.89633-2.0802323 9.05343.2171693 3.1571 1.3656823 6.179 3.3002923 8.6833 1.93461 2.5044 4.56843 4.3788 7.56838 5.3863 2.9999 1.0074 6.2312 1.1026 9.2853.2736 3.054-.8291 5.7937-2.5452 7.8724-4.9313 2.0787-2.3862 3.4031-5.3351 3.8058-8.474.5399-4.2086-.614-8.45933-3.208-11.81721-2.5939-3.35789-6.4154-5.547909-10.624-6.08836z" fill="#ff6c37"/>
                                            <g fill="#fff">
                                                <path d="m11.5675 17.0112c.0062.0127.0167.0228.0295.0286.0128.0059.0273.0071.0409.0034l2.56-.552-1.0768-1.0912-1.5344 1.5344c-.0121.0082-.0207.0205-.0243.0347-.0035.0142-.0017.0292.0051.0421z"/>
                                                <path d="m23.5547 6.01921c-.3567.00036-.7089.08074-1.0305.23521-.3215.15447-.6044.3791-.8277.65736-.2232.27825-.3813.60304-.4624.95045s-.0833.70859-.0064 1.05696.231.67505.4509.95599c.2199.28092.5.50902.8197.66732.3197.1584.6708.243 1.0275.2477s.71-.0707 1.0337-.2206l-1.6224-1.62239c-.0186-.01858-.0334-.04065-.0435-.06495-.01-.0243-.0152-.05035-.0152-.07665 0-.02631.0052-.05236.0152-.07666.0101-.0243.0249-.04637.0435-.06494l2.12-2.1184c-.4257-.34232-.9561-.52814-1.5024-.5264z"/>
                                                <path d="m25.3483 6.8208-1.9872 1.9792 1.5584 1.5584c.1148-.0806.2219-.1717.32-.272.4271-.43.6757-1.00568.6959-1.61142.0202-.60575-.1896-1.1967-.5871-1.65418z"/>
                                                <path d="m21.3723 10.4736h-.0352c-.0413-.0003-.0826.0034-.1232.0112h-.0144c-.0446.0096-.0884.0224-.1312.0384l-.0336.016c-.0322.0134-.0632.0295-.0928.048l-.0352.0224c-.0387.0267-.0751.0567-.1088.0896l-5.8928 5.8944.7296.7296 6.24-5.4768c.0353-.0309.0674-.0652.096-.1024l.0272-.0352c.0213-.0312.0406-.0638.0576-.0976.0096-.0192.0176-.0384.0256-.0576.0108-.0255.0198-.0517.0272-.0784 0-.0192.0112-.0384.016-.0576.0079-.0396.0132-.0797.016-.12v-.0528c0-.0288 0-.0576 0-.0864s0-.0384-.008-.0576c-.0296-.151-.1033-.2898-.2118-.3989s-.247-.1835-.3978-.2139h-.0304c-.0396-.0076-.0797-.0124-.12-.0144z"/>
                                                <path d="m13.3963 15.1168 1.2096 1.2032 5.9088-5.9088c.1923-.188.4428-.3048.7104-.3312-1.0448-.8-2.184-.5904-7.8288 5.0368z"/>
                                                <path d="m22.2075 12.0768-.072.0704-6.24 5.4752 1.0608 1.0592c2.6304-2.488 4.9648-4.8576 5.2512-6.6048z"/>
                                                <path d="m6.64277 24.904c.00324.0113.0099.0214.01905.0288.00916.0075.02038.0119.03215.0128l2.71999.1872-1.5248-1.5248-1.23359 1.232c-.008.0083-.01345.0188-.01572.0301-.00226.0114-.00125.0231.00292.0339z"/>
                                                <path d="m8.17394 23.3248 1.60799 1.608c.01909.0204.04463.0336.07231.0374s.05582-.0021.07969-.0166c.02493-.0124.04478-.033.05617-.0584.0114-.0254.0136-.054.00623-.0808l-.2704-1.1552c-.01751-.0749-.00955-.1535.02262-.2233.03216-.0698.0867-.1269.15498-.1623 2.81917-1.4128 5.09277-2.8672 6.76157-4.32l-1.12-1.12-2.4.5168z"/>
                                                <path d="m15.2011 17.4944-.6016-.6016-.832.8304c-.006.0072-.0093.0163-.0093.0256 0 .0094.0033.0184.0093.0256.0039.0085.0107.0154.0193.0192.0085.0038.0181.0044.0271.0016z"/>
                                            </g>
                                            <path d="m25.4043 8.1104c-.0082-.02518-.0217-.04831-.0396-.06784-.0178-.01953-.0397-.03501-.0641-.04539-.0243-.01039-.0506-.01544-.0771-.01481-.0265.00062-.0525.00691-.0764.01843-.0238.01152-.0449.02801-.0619.04836-.0169.02035-.0293.04409-.0363.06963s-.0085.05229-.0043.07844.0139.0511.0285.07318c.0478.09622.0668.20422.0546.31097s-.0551.20768-.1234.29063c-.0224.0271-.0367.06001-.0411.09492-.0045.0349.0011.07035.016.10221s.0386.05883.0683.07775c.0296.01892.0641.02902.0992.02912.0272-.00029.054-.00645.0786-.01805.0246-.01159.0463-.02836.0638-.04915.1133-.13769.1844-.30513.2048-.48222s-.0107-.35632-.0896-.51618z" fill="#ff6c37"/>
                                        </svg>
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