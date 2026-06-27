import { Head, usePage } from '@inertiajs/react';
import { Helmet } from 'react-helmet-async';
import { useDomains } from '@/lib/domains';
import { useState } from 'react';
import {
    Brain,
    Zap,
    Shield,
    Code2,
    Users,
    Layers,
    ArrowRight,
    GitMerge,
    SlidersHorizontal,
    MessageSquare,
    Cpu,
    Database,
    Network,
    Lock,
    Filter,
    LayoutGrid,
    RefreshCw,
    PackageCheck,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import CtaButton from '@/components/public/cta-button';

/* ─────────────────────────────────────────────────────────────
   Data: Use Case Tabs
───────────────────────────────────────────────────────────── */
type UseCaseCard = {
    icon: React.ReactNode;
    title: string;
    body: string;
    tag: string;
};

type UseCase = {
    tab: string;
    eyebrow: string;
    headline: string;
    lead: string;
    cards: [UseCaseCard, UseCaseCard];
};

const useCases: UseCase[] = [
    {
        tab: 'SaaS Copilots',
        eyebrow: 'SaaS · Copilot Products',
        headline: 'Give your copilot a persistent identity across every session.',
        lead: 'TraceMem stores structured user preferences, goals, and workflow habits. Each session picks up exactly where the last one left off, without re-prompting the user.',
        cards: [
            {
                icon: <Brain size={22} />,
                title: 'Persistent user preferences',
                body: 'Store user tone preferences, response format choices, and stated goals as structured memory. The copilot recalls them on every subsequent request, no repeat questions.',
                tag: 'SaaS · Onboarding',
            },
            {
                icon: <RefreshCw size={22} />,
                title: 'Session continuity',
                body: 'Memory scoped to each user persists across sessions. Copilot responses stay contextually consistent over days, weeks, and months without bloating prompt size.',
                tag: 'SaaS · UX',
            },
        ],
    },
    {
        tab: 'Support Assistants',
        eyebrow: 'Support · Automation',
        headline: 'Stop making users repeat themselves. Give your support bot real memory.',
        lead: 'TraceMem recalls past tickets, resolutions, and user-stated issues, so your support assistant can respond with full context from the first message.',
        cards: [
            {
                icon: <MessageSquare size={22} />,
                title: 'Zero context re-entry',
                body: 'The support agent knows the full resolution history for each user. It recalls past ticket types, stated problems, and agent handoffs, from the first message of a new thread.',
                tag: 'Support · Efficiency',
            },
            {
                icon: <Filter size={22} />,
                title: 'Pattern-aware responses',
                body: 'Structured memory makes recurring issue patterns visible. When a user reports a similar problem again, the assistant can respond with targeted context and prior resolution notes.',
                tag: 'Support · Intelligence',
            },
        ],
    },
    {
        tab: 'Internal AI Tools',
        eyebrow: 'Internal · Teams',
        headline: 'Build internal assistants that know your team\'s processes, without raw prompt exposure.',
        lead: 'TraceMem gives internal AI tools structured, tenant-isolated memory per team. SOPs, norms, and recurring decisions are recalled on demand, not pasted into every prompt.',
        cards: [
            {
                icon: <Lock size={22} />,
                title: 'Tenant-isolated team memory',
                body: 'Each team\'s memory is stored under a strict tenant boundary. HR, Finance, and Engineering memory never intersects, isolated at the data layer, not just at the application layer.',
                tag: 'Internal · Security',
            },
            {
                icon: <LayoutGrid size={22} />,
                title: 'Process and workflow recall',
                body: 'Store standing instructions, team-specific norms, and recurring decisions as structured memory entries. The assistant recalls the right context without being told every time.',
                tag: 'Internal · Ops',
            },
        ],
    },
    {
        tab: 'Developer Platforms',
        eyebrow: 'Developer · API Platforms',
        headline: 'Embed persistent memory into every user\'s AI agent via a clean REST API.',
        lead: 'TraceMem is API-first. Add structured memory to your platform in a single integration, then each user gets their own scoped memory layer that grows with their usage.',
        cards: [
            {
                icon: <Code2 size={22} />,
                title: 'API-first memory layer',
                body: 'Two endpoints: /remember to store structured memory, /recall to retrieve relevant context. No complex SDKs or infrastructure changes. Integrates into any stack in minutes.',
                tag: 'Dev · API',
            },
            {
                icon: <Users size={22} />,
                title: 'Per-user memory scoping',
                body: 'Each API call is scoped to a user_id. Memory is isolated per user automatically, so a single integration supports thousands of distinct memory contexts without extra configuration.',
                tag: 'Dev · Multitenancy',
            },
        ],
    },
    {
        tab: 'Agent Workflows',
        eyebrow: 'Agents · Autonomous Workflows',
        headline: 'Give autonomous agents structured memory that persists across task runs.',
        lead: 'Agents that run repeatedly need to recall prior decisions, learned user intent, and past outcomes. TraceMem provides the memory layer that makes this possible without prompt bloat.',
        cards: [
            {
                icon: <Cpu size={22} />,
                title: 'Cross-run memory persistence',
                body: 'Each agent task run can write and read structured memory. Decisions made in run 1 are available in run 10, scoped to the user or task context and recalled semantically.',
                tag: 'Agents · State',
            },
            {
                icon: <GitMerge size={22} />,
                title: 'Conflict-aware memory updates',
                body: 'When an agent writes memory that contradicts prior state, TraceMem detects and resolves the conflict, keeping long-term context accurate without requiring agent-side logic.',
                tag: 'Agents · Consistency',
            },
        ],
    },
    {
        tab: 'Personalized Experiences',
        eyebrow: 'Personalization · AI Products',
        headline: 'Build AI products that genuinely adapt to each individual user over time.',
        lead: 'TraceMem stores behavioral patterns, stated preferences, and interaction history as structured memory, enabling real personalization without hard-coded user profiles.',
        cards: [
            {
                icon: <SlidersHorizontal size={22} />,
                title: 'Behavioral memory extraction',
                body: 'Raw user interactions are parsed and structured. Facts, preferences, and intent signals are extracted and stored, not raw transcripts. Only meaningful memory is kept.',
                tag: 'Personalization · Behavior',
            },
            {
                icon: <Layers size={22} />,
                title: 'Context assembly for prompts',
                body: 'Retrieved memory entries are assembled into a compact, prompt-ready context block. The right memories are surfaced, ranked, and injected just before the LLM call.',
                tag: 'Personalization · Context',
            },
        ],
    },
];

/* ─────────────────────────────────────────────────────────────
   Data: Workflow Steps
───────────────────────────────────────────────────────────── */
const workflowSteps = [
    {
        phase: '01',
        title: 'Input Arrives',
        desc: 'A user message or agent output is received. TraceMem receives the raw content via the /remember endpoint.',
        visual: 'The raw input is passed to the memory engine, unstructured text, chat turns, or agent output.',
    },
    {
        phase: '02',
        title: 'Memory Segmentation',
        desc: 'The input is parsed and segmented into discrete memory units, facts, preferences, intent, and behavioral signals.',
        visual: 'AI extraction identifies what matters: structured facts are isolated, noise is discarded.',
    },
    {
        phase: '03',
        title: 'Dedup & Conflict Handling',
        desc: 'New memory entries are compared against existing ones. Duplicates are merged, conflicts are resolved using recency and confidence scoring.',
        visual: 'No redundant entries accumulate. Contradictions are detected and the most accurate version is retained.',
    },
    {
        phase: '04',
        title: 'Semantic Recall',
        desc: 'On the next request, /recall retrieves the most relevant memories, ranked by semantic similarity, recency, and salience.',
        visual: 'Vector search surfaces the entries most relevant to the current query, not just the most recent.',
    },
    {
        phase: '05',
        title: 'Context Assembly',
        desc: 'Retrieved memories are assembled into a compact, prompt-ready context block, injected just before your LLM call.',
        visual: 'A structured, minimal context string is returned, ready to prepend to any prompt without token waste.',
    },
];

/* ─────────────────────────────────────────────────────────────
   Data: Product Fit Cards
───────────────────────────────────────────────────────────── */
const productFitItems = [
    {
        icon: <MessageSquare size={20} />,
        title: 'User Assistants',
        desc: 'Products where users interact repeatedly over time benefit from memory that persists across sessions, eliminating redundant context and making responses feel consistent.',
    },
    {
        icon: <Shield size={20} />,
        title: 'Support Automation',
        desc: 'Support bots that recall issue history, user frustration signals, and past resolutions respond faster and more accurately without re-querying the user.',
    },
    {
        icon: <Zap size={20} />,
        title: 'SaaS Copilots',
        desc: 'Copilots that know a user\'s workflow patterns, preferred formats, and prior decisions provide noticeably more useful suggestions from the very first interaction of each session.',
    },
    {
        icon: <Database size={20} />,
        title: 'Internal AI Tools',
        desc: 'Internal tools powered by AI need process memory, SOPs, team norms, and decision history, recalled on demand without exposing sensitive data in raw prompts.',
    },
    {
        icon: <Cpu size={20} />,
        title: 'Autonomous Agents',
        desc: 'Agents that run across multiple tasks or time windows need cross-run state. TraceMem provides the persistent memory layer without requiring agent-side state management.',
    },
    {
        icon: <Network size={20} />,
        title: 'AI Products with Repeated Context',
        desc: 'Any AI product where users repeatedly provide the same background benefits from structured memory, reducing input overhead and improving response quality.',
    },
];

/* ─────────────────────────────────────────────────────────────
   Data: Architecture Pillars
───────────────────────────────────────────────────────────── */
const archPillars = [
    {
        num: '01',
        icon: <Lock size={18} />,
        title: 'Scoped Memory',
        desc: 'Every memory entry is bound to a user_id and tenant. Reads and writes are strictly isolated, no cross-user leakage at the data layer.',
    },
    {
        num: '02',
        icon: <Brain size={18} />,
        title: 'Structured Extraction',
        desc: 'Raw content is parsed by an AI extraction layer that identifies facts, preferences, and intent, discarding noise and storing only structured memory units.',
    },
    {
        num: '03',
        icon: <Filter size={18} />,
        title: 'Deduplication',
        desc: 'New entries are compared against existing memory before storage. Equivalent memories are merged rather than duplicated, keeping storage lean and retrieval precise.',
    },
    {
        num: '04',
        icon: <GitMerge size={18} />,
        title: 'Conflict Handling',
        desc: 'Contradicting memory entries are detected automatically. Resolution uses recency, source confidence, and semantic similarity to determine which version to retain.',
    },
    {
        num: '05',
        icon: <Zap size={18} />,
        title: 'Recall Ranking',
        desc: 'Retrieved memories are scored by semantic similarity to the query, recency weighting, and salience, returning the most relevant context, not just the most recent.',
    },
    {
        num: '06',
        icon: <PackageCheck size={18} />,
        title: 'Context Assembly',
        desc: 'Ranked memories are assembled into a compact, structured context string, returned prompt-ready so it can be injected before any LLM call with no extra formatting.',
    },
];

/* ─────────────────────────────────────────────────────────────
   Page Component
───────────────────────────────────────────────────────── */
type PageProps = { auth?: { user?: unknown } };

export default function UseCases() {
    const { siteUrl } = useDomains();
    const { props } = usePage<PageProps>();
    const isLoggedIn = !!props.auth?.user;
    const getStartedHref = isLoggedIn ? '/dashboard' : '/register';

    const [activeTab, setActiveTab] = useState(0);
    const [activeStep, setActiveStep] = useState(0);
    const current = useCases[activeTab];

    return (
        <>
            <Helmet>
                <title>Use Cases | TraceMem</title>
                <meta
                    name="description"
                    content="Explore how TraceMem powers real AI use cases, SaaS copilots, support assistants, internal tools, agent workflows, and personalized AI experiences. Persistent, structured, tenant-isolated memory for LLM applications."
                />
                <meta
                    name="keywords"
                    content="TraceMem use cases, AI memory use cases, semantic memory, LLM copilots, support automation, internal AI tools, developer memory API, agent memory, personalized AI"
                />
                <meta property="og:title" content="TraceMem Use Cases" />
                <meta property="og:description" content="See how TraceMem enables persistent, structured AI memory across SaaS copilots, support bots, internal tools, and autonomous agents." />
                <meta property="og:type" content="website" />
                <meta property="og:url" content={`${siteUrl}/usecases`} />
                <link rel="canonical" href={`${siteUrl}/usecases`} />
            </Helmet>

            <Head title="Use Cases" />

            <div className="uc-shell">

                {/* ══ 1. PAGE HEADER, slim, focused, distinct from Landing hero ══ */}
                <section className="uc-page-header" aria-label="Use Cases page header">
                    <div className="uc-inner uc-page-header-inner">
                        <div className="uc-page-header-badges" aria-hidden="true">
                            {[
                                { label: 'SaaS Copilots',   icon: <Brain size={13} /> },
                                { label: 'Support Bots',    icon: <MessageSquare size={13} /> },
                                { label: 'Internal Tools',  icon: <Lock size={13} /> },
                                { label: 'Agent Workflows', icon: <GitMerge size={13} /> },
                                { label: 'Developer APIs',  icon: <Code2 size={13} /> },
                                { label: 'Personalized UX', icon: <Layers size={13} /> },
                            ].map((item) => (
                                <span className="uc-ph-badge" key={item.label}>
                                    <span className="uc-ph-badge-icon">{item.icon}</span>
                                    {item.label}
                                </span>
                            ))}
                        </div>
                        <h1 className="uc-ph-h1">
                            Real use cases.
                            <span className="uc-ph-accent"> Real memory.</span>
                        </h1>
                        <p className="uc-ph-lead">
                            TraceMem powers the AI products, agents, and developer
                            workflows that need persistent, structured, and tenant-scoped memory.
                            Explore exactly where it fits into your stack.
                        </p>
                        <div className="uc-ph-actions">
                            <CtaButton href={getStartedHref} label="Start Building" size="lg" />
                            <CtaButton href="/docs" label="Read the Docs" variant="secondary" size="lg" />
                        </div>
                    </div>
                </section>

                {/* ══ 2. BRAND ANCHOR ══════════════════════════════════ */}
                <div className="uc-brand-anchor" aria-hidden="true">
                    <div className="uc-brand-anchor-line" />
                    <div className="uc-brand-anchor-logo">
                        <AppLogo />
                    </div>
                    <div className="uc-brand-anchor-line" />
                </div>

                {/* ══ 3 + 4. USE CASE TABS + CONTENT ══════════════════ */}
                <section className="uc-section uc-tabs-section" aria-label="Use case categories">
                    <div className="uc-inner">

                        {/* Tabs */}
                        <div className="uc-tabs-header" role="tablist" aria-label="Use case sectors">
                            {useCases.map((uc, i) => (
                                <button
                                    key={uc.tab}
                                    type="button"
                                    role="tab"
                                    id={`uc-tab-${i}`}
                                    aria-selected={activeTab === i}
                                    aria-controls={`uc-panel-${i}`}
                                    className={`uc-tab${activeTab === i ? ' active' : ''}`}
                                    onClick={() => setActiveTab(i)}
                                >
                                    {uc.tab}
                                </button>
                            ))}
                        </div>

                        {/* Active panel */}
                        <div
                            id={`uc-panel-${activeTab}`}
                            role="tabpanel"
                            aria-labelledby={`uc-tab-${activeTab}`}
                            className="uc-panel"
                            aria-live="polite"
                        >
                            <div className="uc-panel-head">
                                <span className="uc-panel-eyebrow">{current.eyebrow}</span>
                                <h2 className="uc-panel-headline">{current.headline}</h2>
                                <p className="uc-panel-lead">{current.lead}</p>
                            </div>

                            <div className="uc-panel-intro">
                                <p className="uc-panel-intro-text">
                                    The two key capabilities below show exactly how TraceMem
                                    makes this possible, structured, scoped, and ready to
                                    drop into your existing stack with no infrastructure changes.
                                </p>
                            </div>

                            <div className="uc-cards-grid">
                                {current.cards.map((card) => (
                                    <div className="uc-card" key={card.title}>
                                        <div className="uc-card-top-bar" aria-hidden="true" />
                                        <div className="uc-card-icon" aria-hidden="true">
                                            {card.icon}
                                        </div>
                                        <h3 className="uc-card-title">{card.title}</h3>
                                        <p className="uc-card-body">{card.body}</p>
                                        <span className="uc-card-tag">{card.tag}</span>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </div>
                </section>

                {/* ══ 5. WORKFLOW / VISUAL EXPLANATION ════════════════ */}
                <section className="uc-section uc-workflow-section" aria-label="How memory works">
                    <div className="uc-inner">

                        <div className="uc-workflow-header">
                            <span className="uc-section-tag">Memory Lifecycle</span>
                            <h2 className="uc-section-title">
                                From raw input to prompt-ready context.
                            </h2>
                            <p className="uc-section-lead">
                                Every message that passes through TraceMem follows the same
                                deterministic pipeline, extract, deduplicate, rank, assemble.
                            </p>
                        </div>

                        <div className="uc-workflow-layout">
                            {/* Timeline */}
                            <div className="uc-workflow-timeline">
                                {workflowSteps.map((step, i) => (
                                    <div
                                        key={step.phase}
                                        className={`uc-wf-step${activeStep === i ? ' active' : ''}`}
                                        role="button"
                                        tabIndex={0}
                                        aria-pressed={activeStep === i}
                                        onClick={() => setActiveStep(i)}
                                        onKeyDown={(e) => e.key === 'Enter' && setActiveStep(i)}
                                    >
                                        <div className="uc-wf-dot" aria-hidden="true" />
                                        <div className="uc-wf-phase">Phase {step.phase}</div>
                                        <h3 className="uc-wf-title">{step.title}</h3>
                                        <p className="uc-wf-desc">{step.desc}</p>
                                    </div>
                                ))}
                            </div>

                            {/* Visual panel */}
                            <div className="uc-workflow-visual" aria-live="polite">
                                <div className="uc-wf-card">
                                    {/* Fake terminal header */}
                                    <div className="uc-wf-card-head" aria-hidden="true">
                                        <span className="tl tl-red" />
                                        <span className="tl tl-yellow" />
                                        <span className="tl tl-green" />
                                        <span className="uc-wf-card-title">
                                            tracemem.memory.pipeline
                                        </span>
                                    </div>

                                    <div className="uc-wf-card-body">
                                        <div className="uc-wf-glow" aria-hidden="true" />

                                        <div className="uc-wf-step-num">
                                            Phase {workflowSteps[activeStep].phase} / {String(workflowSteps.length).padStart(2, '0')}
                                        </div>

                                        <div className="uc-wf-step-icon" aria-hidden="true">
                                            {activeStep === 0 && <ArrowRight size={40} strokeWidth={1.5} />}
                                            {activeStep === 1 && <Brain size={40} strokeWidth={1.5} />}
                                            {activeStep === 2 && <GitMerge size={40} strokeWidth={1.5} />}
                                            {activeStep === 3 && <Zap size={40} strokeWidth={1.5} />}
                                            {activeStep === 4 && <PackageCheck size={40} strokeWidth={1.5} />}
                                        </div>

                                        <div className="uc-wf-step-name">
                                            {workflowSteps[activeStep].title}
                                        </div>

                                        <p className="uc-wf-step-visual">
                                            {workflowSteps[activeStep].visual}
                                        </p>
                                    </div>

                                    {/* Progress indicator */}
                                    <div className="uc-wf-progress" aria-hidden="true">
                                        {workflowSteps.map((_, i) => (
                                            <div
                                                key={i}
                                                className={`uc-wf-seg${activeStep === i ? ' active' : ''}`}
                                            />
                                        ))}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                {/* ══ 6. PRODUCT FIT ═══════════════════════════════════ */}
                <section className="uc-section uc-fit-section" aria-label="Where TraceMem fits">
                    <div className="uc-inner">
                        <div className="uc-fit-header">
                            <span className="uc-section-tag">Product Fit</span>
                            <h2 className="uc-section-title">
                                Where TraceMem fits best.
                            </h2>
                            <p className="uc-section-lead uc-section-lead--center">
                                Any AI product where users interact repeatedly, and where
                                repetitive context is a friction point, is a strong fit for
                                structured persistent memory.
                            </p>
                        </div>

                        <div className="uc-fit-grid">
                            {productFitItems.map((item, i) => (
                                <div className="uc-fit-card" key={item.title}>
                                    <div className="uc-fit-card-num" aria-hidden="true">
                                        {String(i + 1).padStart(2, '0')}
                                    </div>
                                    <div className="uc-fit-card-icon" aria-hidden="true">
                                        {item.icon}
                                    </div>
                                    <h3 className="uc-fit-card-title">{item.title}</h3>
                                    <p className="uc-fit-card-desc">{item.desc}</p>
                                </div>
                            ))}
                        </div>
                    </div>
                </section>

                {/* ══ 7. ARCHITECTURE / ENGINEERING ════════════════════ */}
                <section className="uc-section uc-arch-section" aria-label="Architecture">
                    <div className="uc-inner">

                        <div className="uc-arch-split">
                            {/* Left: text */}
                            <div className="uc-arch-left">
                                <span className="uc-section-tag">Engineering</span>
                                <h2 className="uc-arch-title">
                                    Architecture built for{' '}
                                    <span className="uc-arch-code">&lt;production&gt;</span>
                                    <br />
                                    from day one.
                                </h2>
                                <p className="uc-arch-lead">
                                    TraceMem is not a vector database wrapper. It is a purpose-built
                                    memory engine with structured extraction, deduplication, conflict
                                    resolution, and ranked recall, operating as a managed API layer
                                    between your application and your LLM.
                                </p>
                                <div className="uc-arch-cta">
                                    <CtaButton href="/api-reference" label="View API Reference" variant="secondary" />
                                </div>
                            </div>

                            {/* Right: pillar grid */}
                            <div className="uc-arch-right">
                                {archPillars.map((p) => (
                                    <div className="uc-arch-pillar" key={p.num}>
                                        <div className="uc-arch-pillar-head">
                                            <span className="uc-arch-pillar-num">{p.num}</span>
                                            <div className="uc-arch-pillar-icon" aria-hidden="true">
                                                {p.icon}
                                            </div>
                                            <h4 className="uc-arch-pillar-title">{p.title}</h4>
                                        </div>
                                        <p className="uc-arch-pillar-desc">{p.desc}</p>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </div>
                </section>

                {/* ══ 8. CTA, minimal gradient strip ════════════════════ */}
                <section className="uc-cta-strip" aria-label="Call to action">
                    <div className="uc-cta-strip-glow" aria-hidden="true" />
                    <div className="uc-inner uc-cta-strip-inner">
                        <div className="uc-cta-strip-meta">
                            <span className="uc-cta-strip-tag">Ready to ship?</span>
                            <h2 className="uc-cta-strip-headline">
                                One API key. Persistent memory.
                                <span className="uc-cta-strip-accent"> For every user.</span>
                            </h2>
                            <p className="uc-cta-strip-desc">
                                Integrate via REST in minutes, test key included, no credit card, no infra changes.
                            </p>
                        </div>
                        <div className="uc-cta-strip-actions">
                            <CtaButton href={getStartedHref} label="Get Your API Key" size="lg" />
                            <CtaButton href="/api-reference" label="View API Reference" variant="secondary" size="lg" />
                        </div>
                    </div>
                </section>

            </div>
        </>
    );
}