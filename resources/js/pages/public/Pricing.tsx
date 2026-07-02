import { Head, usePage } from '@inertiajs/react';
import { Helmet } from 'react-helmet-async';
import { useDomains } from '@/lib/domains';
import { Fragment, useState } from 'react';
import { Check, X, ArrowRight } from 'lucide-react';
import CtaButton from '@/components/public/cta-button';

/* ── Types ────────────────────────────────────────────────── */
type PlanFeature = {
    feature_scope: string;
    model_provider: string | null;
    model_name: string | null;
    feature_key: string;
    feature_value: Record<string, unknown> | null;
    is_enabled: boolean;
};

type Plan = {
    id: number;
    slug: string;
    name: string;
    description: string;
    base_mode: 'semantic_only' | 'ai_first';
    memory_write_limit: number;
    request_limit: number;
    api_key_limit: number;
    request_rate_limit_max_requests: number;
    request_rate_limit_window_seconds: number;
    test_rate_limit_max_requests: number;
    test_rate_limit_window_seconds: number;
    allow_test_keys: boolean;
    allow_live_keys: boolean;
    price_monthly: number;
    price_quarterly: number;
    price_yearly: number;
    features: PlanFeature[];
};

type PageProps = {
    auth?: { user?: unknown };
    plans: Plan[];
};

type BillingCycle = 'monthly' | 'quarterly' | 'yearly';

/* ── Helpers ──────────────────────────────────────────────── */
function formatNumber(n: number): string {
    if (n >= 1_000_000) return `${(n / 1_000_000).toFixed(0)}M`;
    if (n >= 1_000) return `${(n / 1_000).toFixed(0)}K`;
    return String(n);
}

function formatRateLimit(maxReqs: number, windowSec: number): string {
    return `${maxReqs} req / ${windowSec}s`;
}

function modeLabel(mode: string): string {
    if (mode === 'semantic_only') return 'Semantic Only';
    if (mode === 'ai_first') return 'AI First';
    return mode;
}

function getPlanPrice(plan: Plan, cycle: BillingCycle): number {
    if (cycle === 'quarterly') return plan.price_quarterly;
    if (cycle === 'yearly') return plan.price_yearly;
    return plan.price_monthly;
}

function periodLabel(cycle: BillingCycle): string {
    if (cycle === 'quarterly') return '/ quarter';
    if (cycle === 'yearly') return '/ year';
    return '/ month';
}

function hasPlanFeature(plan: Plan, featureKey: string): boolean {
    return plan.features.some((f) => f.feature_key === featureKey && f.is_enabled);
}

/* ── Feature row definitions ──────────────────────────────── */
type FeatureGroup = {
    groupLabel: string;
    rows: Array<{
        key: string;
        label: string;
        sublabel?: string;
        getValue: (plan: Plan) => { type: 'text'; value: string } | { type: 'check'; ok: boolean } | { type: 'dash' };
    }>;
};

const featureGroups: FeatureGroup[] = [
    {
        groupLabel: 'Memory Capabilities',
        rows: [
            {
                key: 'memory.write',
                label: 'Memory Write Limit',
                sublabel: 'Per billing cycle',
                getValue: (p) => ({ type: 'text', value: `${formatNumber(p.memory_write_limit)} saves` }),
            },
            {
                key: 'memory.recall',
                label: 'Request Limit',
                sublabel: 'API calls per cycle',
                getValue: (p) => ({ type: 'text', value: formatNumber(p.request_limit) }),
            },
            {
                key: 'memory.semantic.search',
                label: 'Semantic Search',
                getValue: (p) => ({ type: 'check', ok: hasPlanFeature(p, 'memory.semantic.search') }),
            },
            {
                key: 'memory.ai.extraction',
                label: 'AI Memory Extraction',
                sublabel: 'GPT-4o-mini powered',
                getValue: (p) => ({ type: 'check', ok: hasPlanFeature(p, 'memory.ai.extraction') }),
            },
            {
                key: 'memory.context.assemble',
                label: 'Context Assembly',
                sublabel: '/context/assemble endpoint',
                getValue: (p) => ({
                    type: 'check',
                    ok: p.base_mode === 'ai_first' || hasPlanFeature(p, 'memory.context.assemble'),
                }),
            },
        ],
    },
    {
        groupLabel: 'API Access',
        rows: [
            {
                key: 'api.rate.live',
                label: 'Live Rate Limit',
                getValue: (p) => ({
                    type: 'text',
                    value: formatRateLimit(p.request_rate_limit_max_requests, p.request_rate_limit_window_seconds),
                }),
            },
            {
                key: 'api.rate.test',
                label: 'Test Rate Limit',
                getValue: (p) => ({
                    type: 'text',
                    value: formatRateLimit(p.test_rate_limit_max_requests, p.test_rate_limit_window_seconds),
                }),
            },
            {
                key: 'api.key.limit',
                label: 'API Key Slots',
                getValue: (p) => ({ type: 'text', value: `${p.api_key_limit} key${p.api_key_limit !== 1 ? 's' : ''}` }),
            },
            {
                key: 'allow_test_keys',
                label: 'Test Keys',
                sublabel: 'cmtest_ prefix',
                getValue: (p) => ({ type: 'check', ok: p.allow_test_keys }),
            },
            {
                key: 'allow_live_keys',
                label: 'Live Keys',
                sublabel: 'cmlive_ prefix',
                getValue: (p) => ({ type: 'check', ok: p.allow_live_keys }),
            },
        ],
    },
    {
        groupLabel: 'Plan Details',
        rows: [
            {
                key: 'base_mode',
                label: 'Processing Mode',
                getValue: (p) => ({ type: 'text', value: modeLabel(p.base_mode) }),
            },
        ],
    },
];

/* ── Custom contact URL ─────────────────────────────────── */
const contactUrl =
    'https://wa.me/916291996890?text=' +
    encodeURIComponent(
        "Hi, I'm looking for a custom TraceMem subscription plan. My team has specific memory write limits and API usage requirements beyond the standard plans. Could you help me with a custom arrangement?",
    );

/* ════════════════════════════════════════════════════════════ */
export default function Pricing() {
    const { siteUrl } = useDomains();
    const { props } = usePage<PageProps>();
    const isLoggedIn = !!props.auth?.user;
    const getStartedHref = isLoggedIn ? '/dashboard' : '/register';
    const plans: Plan[] = props.plans ?? [];

    const [cycle, setCycle] = useState<BillingCycle>('monthly');

    /* Find the recommended (AI First Pro) plan */
    const recommendedSlug = 'ai-first-pro';

    /* Value cell renderer */
    const renderValue = (
        val: { type: 'text'; value: string } | { type: 'check'; ok: boolean } | { type: 'dash' },
    ) => {
        if (val.type === 'text') {
            return <span className="pricing-val">{val.value}</span>;
        }
        if (val.type === 'check') {
            return val.ok ? (
                <span className="pricing-check" aria-label="Included">
                    <Check size={13} strokeWidth={2.5} />
                </span>
            ) : (
                <span className="pricing-cross" aria-label="Not included">
                    <X size={12} strokeWidth={2} />
                </span>
            );
        }
        return <span className="pricing-val-dash" aria-label="Not available">—</span>;
    };

    return (
        <>
            <Helmet>
                <title>Pricing | TraceMem</title>
                <meta
                    name="description"
                    content="Transparent pricing for TraceMem AI memory infrastructure. Compare Semantic Starter and AI First Pro, semantic search, AI extraction, context assembly, and developer-friendly APIs."
                />
                <meta
                    name="keywords"
                    content="TraceMem pricing, AI memory pricing, semantic memory plan, LLM API pricing, context memory infrastructure"
                />
                <meta property="og:title" content="Pricing | TraceMem" />
                <meta property="og:description" content="Simple, transparent pricing for persistent AI memory infrastructure. Compare Semantic Starter and AI First Pro plans." />
                <meta property="og:type" content="website" />
                <meta property="og:url" content={`${siteUrl}/pricing`} />
                <meta property="og:image" content={`${siteUrl}/og-image.png`} />
                <meta property="og:image:width"  content="1200" />
                <meta property="og:image:height" content="630" />
                <meta property="og:image:alt"    content="TraceMem - Long-Term Memory Infrastructure for AI" />
                <meta property="og:site_name"    content="TraceMem" />
                <meta property="og:locale"       content="en_US" />
                <meta name="twitter:card"        content="summary_large_image" />
                <meta name="twitter:title"       content="Pricing | TraceMem" />
                <meta name="twitter:description" content="Simple, transparent pricing for persistent AI memory infrastructure. Compare Semantic Starter and AI First Pro plans." />
                <meta name="twitter:image"       content={`${siteUrl}/og-image.png`} />
                <meta name="twitter:image:alt"   content="TraceMem - Long-Term Memory Infrastructure for AI" />
                <link rel="canonical" href={`${siteUrl}/pricing`} />
            </Helmet>

            <Head title="Pricing" />

            <div className="pricing-shell">

                {/* ══ 1. HERO ═══════════════════════════════════════════ */}
                <section className="pricing-hero" aria-label="Pricing hero">
                    <div className="pricing-hero-inner">
                        <span className="pricing-hero-eyebrow">Pricing</span>

                        <h1 className="pricing-hero-h1">
                            Simple and{' '}
                            <span className="serif-accent">Affordable</span>
                        </h1>

                        <p className="pricing-hero-lead">
                            Two plans built for different stages of AI development.
                            Start with semantic memory or unlock full AI-first extraction
                            and context assembly. No hidden fees.
                        </p>

                        {/* Billing cycle toggle */}
                        <div className="pricing-toggle-bar" role="group" aria-label="Billing cycle">
                            {(['monthly', 'quarterly', 'yearly'] as BillingCycle[]).map((c) => (
                                <button
                                    key={c}
                                    type="button"
                                    className={`pricing-toggle-btn ${cycle === c ? 'active' : ''}`}
                                    onClick={() => setCycle(c)}
                                >
                                    {c.charAt(0).toUpperCase() + c.slice(1)}
                                    {c === 'yearly' && (
                                        <span className="pricing-toggle-badge">Save ~40%</span>
                                    )}
                                </button>
                            ))}
                        </div>
                    </div>
                </section>

                {/* ══ 2. COMPARISON TABLE (Desktop) ═══════════════════ */}
                <section className="pricing-table-section" aria-label="Plan comparison">
                    <div className="pricing-table-inner">

                        {/* ─── Desktop table ─── */}
                        <table className="pricing-table" aria-label="TraceMem plan comparison">
                            <thead className="pricing-thead">
                                <tr>
                                    <th className="pricing-th-empty" scope="col">
                                        <div className="pricing-th-empty-label">Features</div>
                                    </th>
                                    {plans.map((plan) => (
                                        <th
                                            key={plan.slug}
                                            className={`pricing-plan-col${plan.slug === recommendedSlug ? ' highlight' : ''}`}
                                            scope="col"
                                        >
                                            <div className="pricing-plan-mode">
                                                {modeLabel(plan.base_mode)}
                                            </div>
                                            <div className="pricing-plan-name">{plan.name}</div>
                                            <div className="pricing-plan-price">
                                                ₹{getPlanPrice(plan, cycle).toFixed(0)}
                                            </div>
                                            <div className="pricing-plan-price-period">
                                                {periodLabel(cycle)}
                                            </div>
                                            <div className="pricing-plan-desc">
                                                {plan.description}
                                            </div>
                                            <div className="pricing-plan-cta">
                                                <CtaButton
                                                    href={getStartedHref}
                                                    label="Get Started"
                                                    variant={plan.slug === recommendedSlug ? 'primary' : 'secondary'}
                                                />
                                            </div>
                                        </th>
                                    ))}
                                </tr>
                            </thead>

                            <tbody className="pricing-tbody">
                                {featureGroups.map((group) => (
                                    <Fragment key={group.groupLabel}>
                                        {/* Group header row */}
                                        <tr className="pricing-feature-group-row">
                                            <td colSpan={plans.length + 1}>
                                                <span className="pricing-feature-group-label">
                                                    {group.groupLabel}
                                                </span>
                                            </td>
                                        </tr>

                                        {/* Feature rows */}
                                        {group.rows.map((row) => (
                                            <tr key={row.key} className="pricing-row">
                                                <td className="pricing-row-label-cell">
                                                    <div className="pricing-row-icon">
                                                        <div className="pricing-row-icon-dot" aria-hidden="true" />
                                                        <span className="pricing-row-label">{row.label}</span>
                                                    </div>
                                                    {row.sublabel && (
                                                        <div className="pricing-row-sublabel">{row.sublabel}</div>
                                                    )}
                                                </td>
                                                {plans.map((plan) => (
                                                    <td
                                                        key={plan.slug}
                                                        className={`pricing-value-cell${plan.slug === recommendedSlug ? ' highlight' : ''}`}
                                                    >
                                                        {renderValue(row.getValue(plan))}
                                                    </td>
                                                ))}
                                            </tr>
                                        ))}
                                    </Fragment>
                                ))}
                            </tbody>
                        </table>

                        {/* ─── Mobile cards (≤900px) ─── */}
                        <div className="pricing-mobile-plans" aria-label="Plan cards">
                            {plans.map((plan) => (
                                <div
                                    key={plan.slug}
                                    className={`pricing-mobile-plan-card${plan.slug === recommendedSlug ? ' highlight' : ''}`}
                                >
                                    {/* Header */}
                                    <div className="pricing-mobile-plan-header">
                                        <div className="pricing-mobile-plan-mode">
                                            {modeLabel(plan.base_mode)}
                                        </div>
                                        <div className="pricing-mobile-plan-name">{plan.name}</div>
                                        <div className="pricing-mobile-plan-price">
                                            ₹{getPlanPrice(plan, cycle).toFixed(0)}
                                        </div>
                                        <div className="pricing-mobile-plan-period">
                                            {periodLabel(cycle)}
                                        </div>
                                        <p className="pricing-mobile-plan-desc">
                                            {plan.description}
                                        </p>
                                        <CtaButton
                                            href={getStartedHref}
                                            label="Get Started"
                                            variant={plan.slug === recommendedSlug ? 'primary' : 'secondary'}
                                        />
                                    </div>

                                    {/* Feature rows */}
                                    <div className="pricing-mobile-rows">
                                        {featureGroups.map((group) => (
                                            <div key={group.groupLabel}>
                                                <div className="pricing-mobile-group-label">
                                                    {group.groupLabel}
                                                </div>
                                                {group.rows.map((row) => {
                                                    const val = row.getValue(plan);
                                                    const displayVal =
                                                        val.type === 'text'
                                                            ? val.value
                                                            : val.type === 'check'
                                                              ? val.ok
                                                                  ? '✓'
                                                                  : '✗'
                                                              : '—';
                                                    return (
                                                        <div
                                                            key={row.key}
                                                            className="pricing-mobile-row"
                                                        >
                                                            <span className="pricing-mobile-row-label">
                                                                {row.label}
                                                            </span>
                                                            <span
                                                                className="pricing-mobile-row-val"
                                                                style={{
                                                                    color:
                                                                        val.type === 'check'
                                                                            ? val.ok
                                                                                ? 'var(--tm-primary)'
                                                                                : 'var(--tm-border-strong)'
                                                                            : 'var(--tm-text)',
                                                                }}
                                                            >
                                                                {displayVal}
                                                            </span>
                                                        </div>
                                                    );
                                                })}
                                            </div>
                                        ))}
                                    </div>

                                    {/* Bottom CTA */}
                                    <div className="pricing-mobile-row-cta">
                                        <CtaButton
                                            href={getStartedHref}
                                            label="Get Started"
                                            variant={plan.slug === recommendedSlug ? 'primary' : 'secondary'}
                                        />
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                </section>

                {/* ══ 3. CUSTOM PLAN SECTION ════════════════════════════ */}
                <section className="pricing-custom-section" aria-label="Custom plan">
                    <div className="pricing-custom-inner">
                        <div className="pricing-custom-left">
                            <span className="pricing-custom-tag">Custom Plan</span>
                            <h2 className="pricing-custom-title">
                                Need higher limits or a tailored plan?
                            </h2>
                            <p className="pricing-custom-desc">
                                Teams with custom memory write limits, higher API throughput,
                                or dedicated infrastructure needs can reach out directly.
                                We'll build a plan around your usage.
                            </p>
                        </div>
                        <div className="pricing-custom-right">
                            <a
                                href={contactUrl}
                                target="_blank"
                                rel="noreferrer noopener"
                                className="pricing-contact-btn"
                                id="pricing-contact-team"
                                aria-label="Contact the TraceMem team for a custom plan"
                            >
                                <span className="pricing-contact-btn-label">Contact Team</span>
                                <span className="pricing-contact-btn-arrow">
                                    <ArrowRight size={14} />
                                </span>
                            </a>
                        </div>
                    </div>
                </section>

            </div>
        </>
    );
}