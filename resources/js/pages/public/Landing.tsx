import { Head, usePage } from '@inertiajs/react';
import { Helmet } from 'react-helmet-async';
import { useEffect, useState, type ReactNode } from 'react';
import { Zap, Shield, Brain, Layers, Code2, Users, RefreshCw, MessageSquare, LayoutGrid, GitMerge, PackageCheck } from 'lucide-react';

import Typewriter from '@/components/public/typewriter';
import CodeWindow from '@/components/public/code-window';
import CtaButton from '@/components/public/cta-button';
import FaqAccordion from '@/components/public/faq-accordion';
import AppLogo from '@/components/app-logo';
import PlaygroundSection from '@/components/public/playground-section';
import ChatDemoPanel from '@/components/public/chat-demo-panel';

/* ── Code snippets ────────────────────────────────────────── */
const snippets = {
    python: `import requests

# Initialise the TraceMem client
TM_KEY = "cmlive_your_key_here"
BASE   = "https://tracemem.one/api/v1"

# ── Store a memory ──────────────────────────────────
response = requests.post(
    f"{BASE}/remember",
    headers={"Authorization": f"Bearer {TM_KEY}"},
    json={
        "user_id":  "usr_98765",
        "content":  "User prefers concise answers in bullet form.",
        "category": "preferences",
    }
)

# ── Recall relevant context ─────────────────────────
context = requests.post(
    f"{BASE}/recall",
    headers={"Authorization": f"Bearer {TM_KEY}"},
    json={"user_id": "usr_98765", "query": "response style"}
)

print(context.json())   # → [{"content": "User prefers concise ..."}]`,

    javascript: `import axios from "axios";

// Initialise the TraceMem client
const TM_KEY = "cmlive_your_key_here";
const BASE   = "https://tracemem.one/api/v1";
const auth   = { headers: { Authorization: \`Bearer \${TM_KEY}\` } };

// ── Store a memory ──────────────────────────────────
await axios.post(\`\${BASE}/remember\`, {
  user_id:  "usr_98765",
  content:  "User prefers concise answers in bullet form.",
  category: "preferences",
}, auth);

// ── Recall relevant context ─────────────────────────
const { data } = await axios.post(\`\${BASE}/recall\`, {
  user_id: "usr_98765",
  query:   "response style",
}, auth);

console.log(data); // → [{ content: "User prefers concise ..." }]`,

    php: `<?php

// Initialise the TraceMem client
$key  = 'cmlive_your_key_here';
$base = 'https://tracemem.one/api/v1';

// ── Store a memory ──────────────────────────────────
Http::withToken($key)->post("{$base}/remember", [
    'user_id'  => 'usr_98765',
    'content'  => 'User prefers concise answers in bullet form.',
    'category' => 'preferences',
]);

// ── Recall relevant context ─────────────────────────
$context = Http::withToken($key)->post("{$base}/recall", [
    'user_id' => 'usr_98765',
    'query'   => 'response style',
])->json();

// → [['content' => 'User prefers concise ...']]
dd($context);`,
};

/* ── How It Works steps ───────────────────────────────────── */
const howSteps = [
    {
        phase: 'Phase 01',
        title: 'Remember',
        desc: 'Raw interactions are parsed. Core facts, preferences, skills, and user intent are extracted and stored as structured semantic memories.',
        icon: <Brain size={40} strokeWidth={1.5} />,
        viDesc: 'AI extracts meaning from every interaction and stores it as a rich, structured memory.',
    },
    {
        phase: 'Phase 02',
        title: 'Recall',
        desc: 'On every new request, TraceMem retrieves the most semantically relevant memories, ranked by recency, salience, and context match.',
        icon: <Zap size={40} strokeWidth={1.5} />,
        viDesc: 'The right memories surface instantly, ranked by relevance, not just recency.',
    },
    {
        phase: 'Phase 03',
        title: 'Context Assembly',
        desc: 'Retrieved memories are assembled into a compact, prompt-ready context block, injected seamlessly before your LLM call.',
        icon: <PackageCheck size={40} strokeWidth={1.5} />,
        viDesc: 'Memories are assembled into a lean context block. Zero token waste. Instant injection.',
    },
    {
        phase: 'Phase 04',
        title: 'Conflict Resolution',
        desc: 'When contradicting memories exist, TraceMem resolves conflicts automatically, keeping context accurate and coherent over time.',
        icon: <GitMerge size={40} strokeWidth={1.5} />,
        viDesc: 'Contradictions detected and resolved automatically. Memory stays accurate forever.',
    },
];

/* ── Use Cases ────────────────────────────────────────────── */
type UseCase = {
    tab: string;
    desc: string;
    cards: Array<{ icon: ReactNode; title: string; desc: string; tag: string }>;
};

const useCases: UseCase[] = [
    {
        tab: 'SaaS Copilots',
        desc: 'Give your product AI a persistent identity. Remember user preferences, workflow habits, and goals across every session, no repeated onboarding.',
        cards: [
            {
                icon: <Brain size={22} />,
                title: 'Memory-aware onboarding',
                desc: 'The AI remembers what the user already knows, skipping redundant setup steps on every login.',
                tag: 'SaaS · Onboarding',
            },
            {
                icon: <RefreshCw size={22} />,
                title: 'Contextually consistent replies',
                desc: 'Responses stay coherent across weeks, preserving tone, preferences, and user-specific context automatically.',
                tag: 'SaaS · UX',
            },
        ],
    },
    {
        tab: 'Support Assistants',
        desc: 'Stop making users repeat themselves. TraceMem gives your support bot a perfect memory of every past interaction, issue, and resolution.',
        cards: [
            {
                icon: <MessageSquare size={22} />,
                title: 'Zero context re-entry',
                desc: 'The support agent knows the full history, ticket types, resolutions, and user sentiment, from day one.',
                tag: 'Support · Efficiency',
            },
            {
                icon: <Zap size={22} />,
                title: 'Proactive escalation logic',
                desc: 'Detect recurring issues by memory pattern and escalate before the user complains a third time.',
                tag: 'Support · Intelligence',
            },
        ],
    },
    {
        tab: 'Internal Tools',
        desc: 'Build internal AI assistants that know your team\'s processes, preferences, and history, without storing sensitive data in raw prompts.',
        cards: [
            {
                icon: <Shield size={22} />,
                title: 'Tenant-isolated team memory',
                desc: 'Each team\'s memory is cryptographically isolated. HR, Finance, and Eng never share context.',
                tag: 'Internal · Security',
            },
            {
                icon: <LayoutGrid size={22} />,
                title: 'Process & workflow recall',
                desc: 'The AI remembers your SOPs, team norms, and recurring decisions, reducing onboarding time.',
                tag: 'Internal · Ops',
            },
        ],
    },
    {
        tab: 'Developer Platforms',
        desc: 'Embed persistent AI memory directly into your developer platform, giving each user\'s agent a unique, persistent memory layer.',
        cards: [
            {
                icon: <Code2 size={22} />,
                title: 'API-first memory layer',
                desc: 'Simple REST API. Integrate in minutes, scale to millions of users without infrastructure changes.',
                tag: 'Dev · API',
            },
            {
                icon: <Layers size={22} />,
                title: 'Usage-based, predictable pricing',
                desc: 'Pay per active session, not per token. Your costs scale linearly with real usage.',
                tag: 'Dev · Pricing',
            },
        ],
    },
];

/* ── USP cards ────────────────────────────────────────────── */
type UspCardData = { iconName: 'brain' | 'zap' | 'layers' | 'shield' | 'code2' | 'users'; title: string; desc: string };
const uspCards: UspCardData[] = [
    {
        iconName: 'brain',
        title: 'Semantic Extraction',
        desc: 'Raw interactions are parsed with AI to extract structured facts, preferences, and intent, not just raw text.',
    },
    {
        iconName: 'zap',
        title: 'AI-First Memory Intelligence',
        desc: 'Memory is stored, ranked, and retrieved using embeddings, no hand-crafted schemas or keyword matching.',
    },
    {
        iconName: 'layers',
        title: 'Context Assembly',
        desc: 'Relevant memories are assembled into compact, prompt-ready blocks, injected just-in-time before your LLM call.',
    },
    {
        iconName: 'shield',
        title: 'Tenant Isolation',
        desc: 'Strict cryptographic boundaries per user and tenant. Data encrypted at rest (AES-256) and in transit (TLS 1.3).',
    },
    {
        iconName: 'code2',
        title: 'Developer-Friendly APIs',
        desc: 'Clean REST API with SDKs for Python, Node.js, and PHP. Integrates in minutes, scales to production.',
    },
    {
        iconName: 'users',
        title: 'Conflict Resolution',
        desc: 'Contradicting memories are detected and resolved automatically, keeping long-term context coherent and accurate.',
    },
];

/* ── FAQ items ────────────────────────────────────────────── */
const faqItems = [
    {
        q: 'What does TraceMem store?',
        a: 'TraceMem stores structured semantic memories, extracted facts, preferences, skills, and user intent, not raw chat logs. Only meaningful, structured information is persisted, keeping storage lean and retrieval fast.',
    },
    {
        q: 'How does TraceMem differ from standard RAG?',
        a: 'Standard RAG is optimised for document retrieval from static sources. TraceMem is built for episodic and semantic user memory, extracting implicit preferences, user behaviour patterns, and stateful context over time, rather than just searching PDFs.',
    },
    {
        q: 'Is it model-agnostic? Can I use my own LLM?',
        a: 'Yes. TraceMem integrates at the API level, independent of your LLM. Use it with OpenAI, Anthropic, Google, open-source models via Hugging Face, or your own fine-tuned instances.',
    },
    {
        q: 'Is my data secure and tenant-isolated?',
        a: 'Yes. Every user and tenant has strict cryptographic isolation. Data is encrypted at rest (AES-256) and in transit (TLS 1.3). TraceMem never uses your memory data to train its own models.',
    },
    {
        q: 'Do you offer test keys to try it out?',
        a: 'Yes. Test keys are semantic-only and rate-limited, perfect for prototyping and integration testing before going live. No credit card required.',
    },
];

/* ── Devs tabs ────────────────────────────────────────────── */
type DevsTab = 'efficiency' | 'visibility' | 'control';
const devsTabs: { key: DevsTab; label: string; stat: string; statLabel: string; desc: string }[] = [
    { key: 'efficiency',  label: 'Efficiency',  stat: '85%',  statLabel: 'Reduction in Token Usage',   desc: 'TraceMem eliminates redundant context, your LLM only sees what matters.' },
    { key: 'visibility',  label: 'Visibility',  stat: '<50ms', statLabel: 'Memory Retrieval Latency', desc: 'Sub-50ms vector search under the hood. Zero perceptible latency in production.' },
    { key: 'control',     label: 'Control',     stat: '100%', statLabel: 'Tenant Isolated',            desc: 'Every memory is scoped cryptographically. No cross-tenant leakage, ever.' },
];

function UspIcon({ name }: { name: UspCardData['iconName'] }) {
    const size = 28;
    if (name === 'brain')  return <Brain  size={size} />;
    if (name === 'zap')    return <Zap    size={size} />;
    if (name === 'layers') return <Layers size={size} />;
    if (name === 'shield') return <Shield size={size} />;
    if (name === 'code2')  return <Code2  size={size} />;
    return <Users size={size} />;
}

/* ══════════════════════════════════════════════════════════ */
import GlobalLoader from '@/components/loaders/GlobalLoader';

export default function Landing() {
    const { props } = usePage<{ auth?: { user?: unknown } }>();
    const isLoggedIn = !!props.auth?.user;
    const getStartedHref = isLoggedIn ? '/dashboard' : '/register';

    /* How it Works auto-cycle */
    const [activeStep, setActiveStep] = useState(0);
    useEffect(() => {
        const id = window.setInterval(() => {
            setActiveStep((prev) => (prev + 1) % howSteps.length);
        }, 3200);
        return () => window.clearInterval(id);
    }, []);

    /* Use Cases tab */
    const [activeUseCase, setActiveUseCase] = useState(0);

    /* Devs tab */
    const [activeDevsTab, setActiveDevsTab] = useState<DevsTab>('efficiency');
    const devsData = devsTabs.find((t) => t.key === activeDevsTab)!;

    return (
        <>
            <GlobalLoader />
            <Helmet>
                <meta
                    name="description"
                    content="Persistent long-term memory infrastructure for AI and LLM applications with semantic recall, ranking, tenant isolation, and context assembly."
                />
                <meta
                    name="keywords"
                    content="AI memory, LLM memory, semantic memory, context memory, retrieval system, AI infrastructure, vector memory, long-term memory"
                />
                <meta property="og:title"       content="TraceMem" />
                <meta property="og:description" content="Long-term memory infrastructure for AI applications." />
                <meta property="og:type"        content="website" />
                <meta property="og:url"         content="https://tracemem.one" />
                <meta property="og:image"       content="https://tracemem.one/og-image.png" />
                <meta name="twitter:card"        content="summary_large_image" />
                <meta name="twitter:title"       content="TraceMem" />
                <meta name="twitter:description" content="Long-term memory infrastructure for AI applications." />
                <link rel="canonical"            href="https://tracemem.one" />
            </Helmet>

            <Head title="TraceMem | Long-Term Contextual Memory Infrastructure" />

            {/* ══ 1. HERO ═══════════════════════════════════════════ */}
            <section className="hero-section" aria-label="Hero">
                <div className="hero-blob" aria-hidden="true" />

                <div className="hero-inner">
                    <span className="hero-eyebrow">
                        Context Memory Infrastructure for AI
                    </span>

                    <h1 className="hero-h1">
                        Give your AI agents a{' '}
                        <span className="serif-accent">flawless memory.</span>
                    </h1>

                    <p className="hero-subtext">
                        The drop-in semantic layer for LLMs. Store structured memories,
                        recall relevant context instantly, and assemble prompt-ready
                        knowledge, built for production scale.
                    </p>

                    <div className="hero-typewriter" aria-live="polite">
                        <Typewriter
                            phrases={[
                                'Remember anything.',
                                'Recall context instantly.',
                                'Assemble smarter prompts.',
                                'Resolve memory conflicts.',
                            ]}
                        />
                    </div>

                    <div className="hero-actions">
                        <CtaButton href={getStartedHref} label="Start Building" size="lg" />
                        <CtaButton href="/docs" label="Read the Docs" variant="secondary" size="lg" />
                    </div>
                </div>
            </section>

            {/* ══ 2. API INTEGRATION ════════════════════════════════ */}
            <section className="lp-section api-section" aria-label="API Integration">
                <div className="lp-section-inner">
                    <div className="api-section-head">
                        <span className="lp-section-tag">Developer First</span>
                        <h2 className="lp-section-title" style={{ textAlign: 'center' }}>
                            Integrate in minutes, scale forever.
                        </h2>
                        <p className="lp-section-lead center">
                            Simple REST API. No complex SDKs required. Drop TraceMem into
                            any stack with a single API call.
                        </p>
                    </div>

                    <CodeWindow
                        title="tracemem-api.sh"
                        snippets={snippets}
                    />
                </div>
            </section>

            {/* ══ 2b. PLAYGROUND ═════════════════════════════════════ */}
            <PlaygroundSection />

            {/* ══ 2c. MEMORY PROMO ═══════════════════════════════════ */}
            <section className="promo-section" aria-label="Memory in Action">
                <div className="lp-section-inner promo-grid">
                    <div className="promo-left">
                        <span className="lp-section-tag">Memory in Action</span>
                        <h2 className="promo-title">
                            Human-like memory,<br />
                            built for production.
                        </h2>
                        <p className="promo-lead">
                            TraceMem seamlessly handles real-world inputs: schedules, events, complex logic, and code snippets. 
                            It breaks down messy prompts into atomic, structured memory pieces and retrieves them precisely when needed.
                        </p>
                        <div className="promo-actions">
                            <CtaButton href={getStartedHref} label="Start Building" size="md" />
                            <span className="promo-note">No training required.</span>
                        </div>
                    </div>
                    <div className="promo-right">
                        <ChatDemoPanel />
                    </div>
                </div>
            </section>

            {/* ══ 3. BUILT FOR DEVS ════════════════════════════════ */}
            <section className="lp-section devs-section" aria-label="Built for Developers">
                <div className="lp-section-inner devs-grid">
                    {/* Left */}
                    <div className="devs-left">
                        <span className="lp-section-tag">Why TraceMem</span>
                        <h2 className="devs-title">
                            Built for{' '}
                            <span className="code-accent">&lt;developers&gt;</span>
                            <br />
                            who want proof, not promises.
                        </h2>
                        <p className="devs-lead">
                            TraceMem gives AI agents persistent memory without pipeline
                            changes. Less redundant context, lower token costs, measurably
                            faster and smarter responses.
                        </p>

                        <div className="devs-features">
                            {[
                                { icon: <Zap size={16} />,    title: 'Sub-50ms Latency',      desc: 'Optimised vector search under the hood.' },
                                { icon: <Shield size={16} />, title: 'Tenant Isolation',      desc: 'Strict cryptographic boundaries per user.' },
                                { icon: <Brain size={16} />,  title: 'Semantic Extraction',   desc: 'Structured facts, not raw chat transcripts.' },
                                { icon: <Code2 size={16} />,  title: 'Model Agnostic',        desc: 'Works with any LLM, OpenAI, Anthropic, or your own.' },
                            ].map((f) => (
                                <div className="devs-feature-item" key={f.title}>
                                    <div className="devs-feature-icon">{f.icon}</div>
                                    <div className="devs-feature-text">
                                        <h4>{f.title}</h4>
                                        <p>{f.desc}</p>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>

                    {/* Right: tabbed stat panel */}
                    <div className="devs-right">
                        <div className="devs-tabs">
                            {devsTabs.map((t) => (
                                <button
                                    key={t.key}
                                    type="button"
                                    className={`devs-tab ${activeDevsTab === t.key ? 'active' : ''}`}
                                    onClick={() => setActiveDevsTab(t.key)}
                                >
                                    {t.label}
                                </button>
                            ))}
                        </div>

                        <div className="devs-panel">
                            <div className="devs-panel-glow" aria-hidden="true" />
                            <div className="devs-stat-num">{devsData.stat}</div>
                            <div className="devs-stat-label">{devsData.statLabel}</div>
                            <p className="devs-stat-desc">{devsData.desc}</p>
                        </div>
                    </div>
                </div>
            </section>

            {/* ══ 4. HOW IT WORKS ══════════════════════════════════ */}
            <section className="lp-section how-section" aria-label="How TraceMem works">
                <div className="lp-section-inner">
                    <div className="how-header">
                        <div className="how-typewriter-wrap">
                            <Typewriter
                                phrases={['The Memory Lifecycle', 'How TraceMem Works', 'Remember → Recall → Assemble']}
                                typingSpeed={60}
                                deletingSpeed={40}
                                delay={2500}
                            />
                        </div>
                        <p className="lp-section-lead center" style={{ marginTop: 12 }}>
                            A transparent, reliable pipeline between your users and your LLM.
                        </p>
                    </div>

                    <div className="how-layout">
                        {/* Timeline */}
                        <div className="how-timeline">
                            {howSteps.map((step, i) => (
                                <div
                                    key={step.title}
                                    className={`how-step ${activeStep === i ? 'active' : ''}`}
                                    onClick={() => setActiveStep(i)}
                                    role="button"
                                    tabIndex={0}
                                    onKeyDown={(e) => e.key === 'Enter' && setActiveStep(i)}
                                    aria-pressed={activeStep === i}
                                >
                                    <div className="how-step-dot" aria-hidden="true" />
                                    <div className="how-step-phase">{step.phase}</div>
                                    <h3 className="how-step-title">{step.title}</h3>
                                    <p className="how-step-desc">{step.desc}</p>
                                </div>
                            ))}
                        </div>

                        {/* Visual panel */}
                        <div className="how-visual" aria-live="polite">
                            <div className="how-visual-card">
                                <div className="how-visual-head" aria-hidden="true">
                                    <span className="tl tl-red"    />
                                    <span className="tl tl-yellow" />
                                    <span className="tl tl-green"  />
                                </div>
                                <div className="how-visual-body">
                                    <div className="how-vis-glow" aria-hidden="true" />
                                    <div className="how-vis-phase">
                                        {howSteps[activeStep].phase}
                                    </div>
                                    <div className="how-vis-icon" aria-hidden="true">
                                        {howSteps[activeStep].icon}
                                    </div>
                                    <div className="how-vis-title">
                                        {howSteps[activeStep].title}
                                    </div>
                                    <p className="how-vis-desc">
                                        {howSteps[activeStep].viDesc}
                                    </p>
                                </div>

                                {/* Progress segments */}
                                <div className="how-progress" aria-hidden="true">
                                    {howSteps.map((_, i) => (
                                        <div
                                            key={i}
                                            className={`how-progress-seg ${activeStep === i ? 'active' : ''}`}
                                        />
                                    ))}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {/* ══ 5. USE CASES ═════════════════════════════════════ */}
            <section className="lp-section usecases-section" aria-label="Use cases">
                <div className="lp-section-inner">
                    <div className="usecases-header">
                        <div className="usecases-logo" aria-hidden="true">
                            <AppLogo />
                        </div>
                        <span className="lp-section-tag">Use Cases</span>
                        <h2 className="lp-section-title" style={{ textAlign: 'center' }}>
                            TraceMem works everywhere AI meets memory.
                        </h2>
                    </div>

                    {/* Horizontal scrollable tabs */}
                    <div className="usecases-tabs" role="tablist" aria-label="Use case categories">
                        {useCases.map((uc, i) => (
                            <button
                                key={uc.tab}
                                type="button"
                                role="tab"
                                aria-selected={activeUseCase === i}
                                className={`usecases-tab ${activeUseCase === i ? 'active' : ''}`}
                                onClick={() => setActiveUseCase(i)}
                            >
                                {uc.tab}
                            </button>
                        ))}
                    </div>

                    {/* Active use case content */}
                    <div role="tabpanel" aria-live="polite">
                        <p className="usecases-desc">
                            {useCases[activeUseCase].desc}
                        </p>

                        <div className="usecases-cards">
                            {useCases[activeUseCase].cards.map((card) => (
                                <div className="usecase-card" key={card.title}>
                                    <div className="usecase-card-icon" aria-hidden="true">
                                        {card.icon}
                                    </div>
                                    <h3 className="usecase-card-title">{card.title}</h3>
                                    <p className="usecase-card-desc">{card.desc}</p>
                                    <span className="usecase-card-tag">{card.tag}</span>
                                </div>
                            ))}
                        </div>
                    </div>
                </div>
            </section>

            {/* ══ 6. USP ═══════════════════════════════════════════ */}
            <section className="lp-section usp-section" aria-label="Why TraceMem">
                <div className="lp-section-inner">
                    <div className="usp-header">
                        <span className="lp-section-tag">What sets us apart</span>
                        <h2 className="lp-section-title" style={{ textAlign: 'center' }}>
                            Memory intelligence, purpose-built for AI.
                        </h2>
                        <p className="lp-section-lead center">
                            Not a vector database. Not a RAG layer. A purpose-built semantic
                            memory engine that makes AI agents genuinely smarter.
                        </p>
                    </div>

                    <div className="usp-grid">
                        {uspCards.map((card, i) => (
                            <div className="usp-card" key={card.title}>
                                <div className="usp-card-num">
                                    {String(i + 1).padStart(2, '0')}
                                </div>
                                <div className="usp-card-icon" style={{ color: 'var(--tm-primary)' }}>
                                    <UspIcon name={card.iconName} />
                                </div>
                                <h3 className="usp-card-title">{card.title}</h3>
                                <p className="usp-card-desc">{card.desc}</p>
                            </div>
                        ))}
                    </div>
                </div>
            </section>

            {/* ══ 7. FAQ ═══════════════════════════════════════════ */}
            <section className="lp-section faq-section" aria-label="Frequently asked questions">
                <div className="lp-section-inner">
                    <div className="faq-header">
                        <span className="lp-section-tag">FAQ</span>
                        <h2 className="lp-section-title" style={{ textAlign: 'center' }}>
                            Frequently asked questions
                        </h2>
                        <p className="lp-section-lead center">
                            Everything you need to know about TraceMem.
                        </p>
                    </div>

                    <div className="faq-inner">
                        <FaqAccordion items={faqItems} />
                    </div>
                </div>
            </section>
        </>
    );
}