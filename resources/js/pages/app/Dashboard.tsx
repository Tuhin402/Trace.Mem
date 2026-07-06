import { Head, Link, router, usePage } from '@inertiajs/react';
import axios from 'axios';
import { useEffect, useRef, useState } from 'react';
import {
    Activity,
    Clock3,
    KeyRound,
    Zap,
    ArrowRight,
    CheckCircle2,
    XCircle,
    AlertTriangle,
    Globe,
    Wifi,
    Shield,
    CreditCard,
    ShieldCheck,
} from 'lucide-react';
import { useToast } from '@/components/app/toast';
import { fmtNum, fmtLatency, fmtRate, fmtMoney, statusClass } from '@/lib/fmt';

/* ── Types ──────────────────────────────────────────────── */
type ApiKeyItem = {
    id: number;
    name: string;
    environment: 'test' | 'live';
    mode: 'semantic_only' | 'ai_first';
    key_prefix: string;
    key_last4?: string | null;
    usage_count: number;
    revoked_at?: string | null;
    last_used_at?: string | null;
    created_at: string;
};

type MemoryItem = {
    id: number;
    type: string;
    content: string;
    confidence: string;
    decay_score: string;
    status: string;
    created_at: string;
};

type InsightsData = {
    memory_writes: number;
    recall_hit_rate: number;
    total_tokens: number;
    top_endpoints: { endpoint: string; total: number }[];
    split: { test: number; live: number };
    latency_trend: { date: string; avg_latency: number }[];
    error_trend: { date: string; error_count: number }[];
};

type SubscriptionFeature = {
    id: number;
    feature_scope: 'global' | 'model';
    model_provider?: string | null;
    model_name?: string | null;
    feature_key: string;
    feature_value?: unknown;
    is_enabled: boolean;
};

type SubscriptionPlan = {
    id: number;
    slug: string;
    name: string;
    description?: string | null;
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
    price_monthly: string | number;
    price_quarterly: string | number;
    price_yearly: string | number;
    features?: SubscriptionFeature[];
};

type UsageRecentItem = {
    id: number;
    endpoint: string;
    method: string;
    status_code: number;
    latency_ms: number | null;
    ip_address?: string | null;
    request_host?: string | null;
    request_origin?: string | null;
    is_sandbox?: boolean;
    request_id?: string | null;
    requested_at: string;
    apiKey?: {
        name: string;
        environment: 'test' | 'live';
        mode: 'semantic_only' | 'ai_first';
    } | null;
};

type UsageStats = {
    total_requests: number;
    requests_24h: number;
    successful_requests: number;
    client_errors: number;
    server_errors: number;
    slow_requests: number;
    avg_latency_ms: number;
};

type PageProps = {
    apiKeys?: ApiKeyItem[];
    plan?: SubscriptionPlan | null;
    plans?: SubscriptionPlan[];
    usageStats?: UsageStats;
    usageLogs?: UsageRecentItem[];
    todayInsights?: InsightsData;
    memories?: MemoryItem[];
    subscription?: {
        starts_at?: string | null;
        renews_at?: string | null;
        ends_at?: string | null;
        billing_cycle?: string;
        auto_renew?: boolean;
        is_cancelled?: boolean;
    } | null;
    selectedFilters?: { period?: string; month?: string };
    availableMonths?: string[];
    founding_offer?: {
        eligible: boolean;
        campaign_active: boolean;
        show_founding_offer: boolean;
        plan_slug: string;
        display_price: number;
        original_price: number;
        next_price: number;
        badge_text: string;
    } | null;
    flash?: {
        plain_key?: string;
        message?: string;
        error?: string;
    };
};

const PERIODS = [
    { key: 'all_time',    label: 'All Time' },
    { key: 'this_month',  label: 'This Month' },
    { key: 'last_month',  label: 'Last Month' },
    { key: 'year_to_date', label: 'Year to Date' },
] as const;

function formatMode(mode: string) {
    return mode === 'ai_first' ? 'AI First' : 'Semantic Only';
}

function methodColor(method: string): string {
    switch (method.toUpperCase()) {
        case 'GET':    return 'var(--app-info)';
        case 'POST':   return 'var(--app-success)';
        case 'DELETE': return 'var(--app-error)';
        default:       return 'var(--app-text-dim)';
    }
}

/**
 * Dynamically loads the Razorpay Checkout.js script once and caches the result.
 * Returns a promise that resolves when the script is ready.
 */
function loadRazorpayScript(): Promise<void> {
    return new Promise((resolve, reject) => {
        if (typeof window.Razorpay !== 'undefined') {
            resolve();
            return;
        }
        const existing = document.getElementById('razorpay-checkout-js');
        if (existing) {
            existing.addEventListener('load', () => resolve());
            existing.addEventListener('error', () => reject(new Error('Razorpay script failed to load')));
            return;
        }
        const script = document.createElement('script');
        script.id = 'razorpay-checkout-js';
        script.src = 'https://checkout.razorpay.com/v1/checkout.js';
        script.async = true;
        script.onload = () => resolve();
        script.onerror = () => reject(new Error('Razorpay script failed to load'));
        document.head.appendChild(script);
    });
}

/* ── Dashboard ───────────────────────────────────────────── */
export default function Dashboard() {
    const { props } = usePage<PageProps>();
    const { toast, Toasts } = useToast();
    const [checkingOut, setCheckingOut] = useState(false);
    const razorpayOpenRef = useRef(false);

    const apiKeys      = props.apiKeys ?? [];
    const plan         = props.plan ?? null;
    const plans        = props.plans ?? [];
    const usageStats   = props.usageStats;
    const recentUsage  = props.usageLogs ?? [];
    const todayInsights = props.todayInsights;
    const memories     = props.memories ?? [];
    const subscription = props.subscription ?? null;
    const foundingOffer = props.founding_offer ?? null;
    const filters      = props.selectedFilters ?? {};
    const flashKey     = props.flash?.plain_key ?? null;
    const flashMsg     = props.flash?.message ?? null;
    const flashErr     = props.flash?.error ?? null;

    const activeKeys = apiKeys.filter((k) => !k.revoked_at);

    useEffect(() => {
        if (flashMsg) toast(flashMsg, 'success');
        if (flashErr) toast(flashErr, 'error');
    }, []);

    function applyPeriod(period: string) {
        router.get('/dashboard', { period }, {
            preserveScroll: true,
            preserveState: true,
            replace: true,
        });
    }

    const startCheckout = async (planSlug: string, billingCycle: 'monthly' | 'quarterly' | 'yearly') => {
        setCheckingOut(true);

        try {
            await loadRazorpayScript();
        } catch {
            toast('Payment provider failed to load. Please check your connection and try again.', 'error');
            setCheckingOut(false);
            return;
        }

        let orderData: {
            subscription_id: string;
            key_id: string;
            amount: number;
            currency: string;
            name: string;
            description: string;
            prefill: { email: string; name: string };
        };

        try {
            const response = await axios.post<typeof orderData>(
                '/billing/checkout',
                { plan_slug: planSlug, billing_cycle: billingCycle },
                { headers: { 'X-Requested-With': 'XMLHttpRequest' } },
            );
            orderData = response.data;
        } catch (err: unknown) {
            const message =
                axios.isAxiosError(err) && err.response?.data?.error
                    ? String(err.response.data.error)
                    : 'Failed to initiate checkout. Please try again.';
            toast(message, 'error');
            setCheckingOut(false);
            return;
        }

        razorpayOpenRef.current = true;

        const rzp = new window.Razorpay({
            key: orderData.key_id,
            subscription_id: orderData.subscription_id,
            name: orderData.name,
            description: orderData.description,
            currency: orderData.currency,
            prefill: orderData.prefill,
            theme: { color: '#741ab4ff' },
            modal: {
                backdropclose: false,
                escape: true,
                handleback: true,
                confirm_close: false,
                ondismiss: () => {
                    razorpayOpenRef.current = false;
                    setCheckingOut(false);
                    toast(
                        'Payment cancelled - your subscription has not been changed.',
                        'info' as Parameters<typeof toast>[1],
                    );
                },
            },
            handler: async (response: {
                razorpay_payment_id: string;
                razorpay_subscription_id: string;
                razorpay_signature: string;
            }) => {
                razorpayOpenRef.current = false;

                try {
                    await axios.post(
                        '/billing/verify-payment',
                        {
                            razorpay_payment_id: response.razorpay_payment_id,
                            razorpay_subscription_id: response.razorpay_subscription_id,
                            razorpay_signature: response.razorpay_signature,
                        },
                        { headers: { 'X-Requested-With': 'XMLHttpRequest' } },
                    );

                    toast('Subscription activated! Your plan is now active.', 'success');
                    router.reload({ only: ['plan', 'subscription', 'flash'] });
                } catch (verifyErr: unknown) {
                    const message =
                        axios.isAxiosError(verifyErr) && verifyErr.response?.data?.error
                            ? String(verifyErr.response.data.error)
                            : 'Payment was received but verification failed. Please contact support.';
                    toast(message, 'error');
                } finally {
                    setCheckingOut(false);
                }
            },
        });

        rzp.open();
    };

    const activePeriod = filters.period ?? 'all_time';
    const successRate  = fmtRate(usageStats?.successful_requests ?? 0, usageStats?.total_requests ?? 0);

    return (
        <>
            <Head title="Dashboard" />
            <Toasts />

            <div className="app-page">

                {/* ── Page header ── */}
                <div className="app-page-header">
                    <div>
                        <h1 className="app-page-title">Dashboard</h1>
                        <p className="app-page-subtitle">
                            Monitor your memory layer, API usage, and plan status.
                        </p>
                    </div>
                    <div style={{ display: 'flex', alignItems: 'center', gap: '10px', flexWrap: 'wrap' }}>
                        <div className="app-filter-bar">
                            {PERIODS.map((p) => (
                                <button
                                    key={p.key}
                                    type="button"
                                    className={`app-filter-btn${activePeriod === p.key ? ' app-filter-btn--active' : ''}`}
                                    onClick={() => applyPeriod(p.key)}
                                >
                                    {p.label}
                                </button>
                            ))}
                        </div>
                        <Link href="/api-keys" className="app-btn app-btn-primary">
                            <KeyRound size={14} />
                            Manage Keys
                        </Link>
                    </div>
                </div>

                {/* ── Stats row ── */}
                <div className="app-stats-grid">
                    <div className="app-stat-card">
                        <div className="app-stat-icon"><Activity size={15} /></div>
                        <div className="app-stat-label">Requests (24h)</div>
                        <div className="app-stat-value">{fmtNum(usageStats?.requests_24h)}</div>
                        <div className="app-stat-trend">All-time: {fmtNum(usageStats?.total_requests)}</div>
                    </div>

                    <div className="app-stat-card">
                        <div className="app-stat-icon"><Clock3 size={15} /></div>
                        <div className="app-stat-label">Avg Latency</div>
                        <div className="app-stat-value">
                            {fmtLatency(usageStats?.avg_latency_ms)}
                        </div>
                        <div className="app-stat-trend">Slow (&gt;2s): {fmtNum(usageStats?.slow_requests)}</div>
                    </div>

                    <div className="app-stat-card">
                        <div className="app-stat-icon"><KeyRound size={15} /></div>
                        <div className="app-stat-label">Active Keys</div>
                        <div className="app-stat-value">{activeKeys.length}</div>
                        <div className="app-stat-trend">
                            {plan ? `${activeKeys.length} / ${plan.api_key_limit} max` : 'Free tier: 1 key'}
                        </div>
                    </div>

                    <div className="app-stat-card">
                        <div className="app-stat-icon"><Zap size={15} /></div>
                        <div className="app-stat-label">Success Rate</div>
                        <div className="app-stat-value">{successRate}</div>
                        <div className="app-stat-trend">
                            Errors: {fmtNum((usageStats?.client_errors ?? 0) + (usageStats?.server_errors ?? 0))}
                        </div>
                    </div>
                </div>

                {/* ── New key reveal ── */}
                {flashKey && (
                    <div className="app-key-reveal">
                        <div className="app-key-reveal-header">
                            <span className="app-key-reveal-label">New API Key Generated</span>
                            <button
                                type="button"
                                className="app-btn app-btn-sm app-btn-secondary"
                                onClick={async () => {
                                    await navigator.clipboard.writeText(flashKey);
                                    toast('API key copied to clipboard.', 'success');
                                }}
                            >
                                Copy Key
                            </button>
                        </div>
                        <code className="app-key-reveal-code">{flashKey}</code>
                        <div className="app-key-reveal-warning">
                            This secret is shown only once. Copy it now and store it securely.
                        </div>
                    </div>
                )}

                {/* ── Main two-column grid ── */}
                <div className="app-main-grid">

                    {/* API Keys panel */}
                    <div className="app-panel-dependent">
                        <div className="app-panel">
                            <div className="app-panel-head" style={{ flexShrink: 0, marginBottom: '16px' }}>
                                <div>
                                    <h2>API Keys</h2>
                                    <p>Your most recent keys</p>
                                </div>
                                <Link href="/api-keys" className="app-panel-link">
                                    Manage all <ArrowRight size={11} style={{ display: 'inline', verticalAlign: 'middle' }} />
                                </Link>
                            </div>

                            {apiKeys.length === 0 ? (
                                <div className="app-empty-state">
                                    <div className="app-empty-state-title">No API keys yet</div>
                                    <p className="app-empty-state-desc">
                                        Generate your first key to start sending memory to TraceMem.
                                    </p>
                                    <Link href="/api-keys" className="app-btn app-btn-primary" style={{ marginTop: '16px', display: 'inline-flex' }}>
                                        Generate a key
                                    </Link>
                                </div>
                            ) : (
                                <div className="app-panel-scroll-area">
                                    <div className="db-key-list">
                                        {apiKeys.slice(0, 5).map((key) => (
                                            <div className="db-key-row" key={key.id}>
                                                <div className="db-key-dot" data-env={key.environment} aria-hidden="true" />
                                                <div className="db-key-info">
                                                    <span className="db-key-name">{key.name}</span>
                                                    <span className="db-key-meta">
                                                        {key.key_prefix}••••{key.key_last4 ?? '----'}
                                                        &nbsp;&nbsp;{fmtNum(key.usage_count)} uses
                                                    </span>
                                                </div>
                                                <div className="db-key-right">
                                                    <span className={`app-badge app-badge-${key.environment}`}>
                                                        {key.environment}
                                                    </span>
                                                    {key.revoked_at ? (
                                                        <span className="app-badge app-badge-revoked">Revoked</span>
                                                    ) : (
                                                        <span className="app-badge app-badge-active">Active</span>
                                                    )}
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            )}
                        </div>
                    </div>

                    {/* Usage overview panel */}
                    <div className="app-panel">
                        <div className="app-panel-head">
                            <div>
                                <h2>Usage Overview</h2>
                                <p>Error breakdown and health metrics</p>
                            </div>
                        </div>

                        <div className="db-usage-list">
                            <div className="db-usage-row">
                                <div className="db-usage-row-left">
                                    <CheckCircle2 size={14} style={{ color: 'var(--app-success)' }} />
                                    <span>Successful requests</span>
                                </div>
                                <strong style={{ color: 'var(--app-success)' }}>{fmtNum(usageStats?.successful_requests)}</strong>
                            </div>
                            <div className="db-usage-row">
                                <div className="db-usage-row-left">
                                    <AlertTriangle size={14} style={{ color: 'var(--app-warning)' }} />
                                    <span>Client errors (4xx)</span>
                                </div>
                                <strong style={{ color: 'var(--app-warning)' }}>{fmtNum(usageStats?.client_errors)}</strong>
                            </div>
                            <div className="db-usage-row">
                                <div className="db-usage-row-left">
                                    <XCircle size={14} style={{ color: 'var(--app-error)' }} />
                                    <span>Server errors (5xx)</span>
                                </div>
                                <strong style={{ color: 'var(--app-error)' }}>{fmtNum(usageStats?.server_errors)}</strong>
                            </div>
                            <div className="db-usage-row">
                                <div className="db-usage-row-left">
                                    <Clock3 size={14} style={{ color: 'var(--app-text-dim)' }} />
                                    <span>Slow requests (&gt;2s)</span>
                                </div>
                                <strong style={{ color: 'var(--app-text-muted)' }}>{fmtNum(usageStats?.slow_requests)}</strong>
                            </div>
                        </div>

                        {/* ── Active plan mini ── */}
                        {plan && (
                            <>
                                <div className="app-divider" />
                                <div className="db-plan-mini">
                                    <span className="app-mono-label">Active plan</span>
                                    <div className="db-plan-mini-row">
                                        <span className="app-badge app-badge-current">{plan.name}</span>
                                        <span style={{ fontSize: '12px', color: 'var(--app-text-dim)', fontFamily: 'var(--font-mono)' }}>
                                            {formatMode(plan.base_mode)}
                                        </span>
                                    </div>
                                    <div style={{ display: 'flex', gap: '6px', flexWrap: 'wrap', marginTop: '8px' }}>
                                        <span className="app-badge app-badge-neutral">{fmtNum(plan.memory_write_limit)} writes</span>
                                        <span className="app-badge app-badge-neutral">{fmtNum(plan.request_limit)} req/mo</span>
                                        <span className="app-badge app-badge-neutral">{plan.api_key_limit} keys</span>
                                    </div>
                                    {subscription?.renews_at && (
                                        <div className="db-plan-renew">
                                            <ShieldCheck size={11} />
                                            Renews {subscription.renews_at}
                                        </div>
                                    )}
                                    {subscription?.ends_at && !subscription.renews_at && (
                                        <div className="db-plan-renew" style={{ color: 'var(--app-warning)' }}>
                                            <AlertTriangle size={11} />
                                            Expires {subscription.ends_at}
                                        </div>
                                    )}
                                    <Link href="/billing" className="app-panel-link" style={{ marginTop: '4px' }}>
                                        View billing <ArrowRight size={10} style={{ display: 'inline', verticalAlign: 'middle' }} />
                                    </Link>
                                </div>
                            </>
                        )}
                    </div>
                </div>

                {/* ── Data Portability Promo ── */}
                <div className="app-panel" style={{ marginBottom: '24px', background: 'linear-gradient(145deg, var(--app-surface), var(--app-background))', border: '1px solid var(--app-border)', position: 'relative', overflow: 'hidden' }}>
                    <div style={{ position: 'absolute', right: '-20px', top: '-20px', opacity: 0.05, pointerEvents: 'none' }}>
                        <Activity size={180} />
                    </div>
                    <div className="app-panel-head db-promo-head" style={{ borderBottom: 'none', paddingBottom: 0 }}>
                        <div style={{ position: 'relative', zIndex: 1 }}>
                            <h2 style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                                <ShieldCheck size={18} style={{ color: 'var(--app-accent)' }} /> 
                                Memory Portability & Migration
                            </h2>
                            <p style={{ maxWidth: '600px', marginTop: '4px' }}>
                                Full control over your data. You can now easily export all your memories as JSON for backup, or securely import memories migrated from another system directly into your tenant.
                            </p>
                        </div>
                        <Link href="/memory-inspector" className="app-btn app-btn-primary db-promo-btn">
                            Go to Memory Inspector <ArrowRight size={14} />
                        </Link>
                    </div>
                </div>

                {/* ── Plans section — ONLY if no active plan ── */}
                {!plan && plans.length > 0 && (
                    <div className="app-panel">
                        <div className="app-panel-head">
                            <div>
                                <h2>Choose a Plan</h2>
                                <p>Pick a plan to unlock live API keys and higher memory limits.</p>
                            </div>
                        </div>

                        <div className="db-billing-grid">
                            {plans.map((p) => {
                                const isFoundingOffer = foundingOffer?.show_founding_offer && p.slug === foundingOffer.plan_slug;

                                return (
                                <div className={`app-plan-card${isFoundingOffer ? ' bl-plan-card-founding' : ''}`} key={p.id}>
                                    <div style={{ display: 'flex', alignItems: 'flex-start', justifyContent: 'space-between', gap: '12px' }}>
                                        <div>
                                            <div className="app-plan-name">
                                                {p.name}
                                                {isFoundingOffer && (
                                                    <span className="bl-founding-inline-badge">{foundingOffer.badge_text}</span>
                                                )}
                                            </div>
                                            {p.description && (
                                                <div className="app-plan-desc" style={{ marginTop: '6px' }}>{p.description}</div>
                                            )}
                                        </div>
                                    </div>

                                    <div className="app-plan-meta">
                                        {[
                                            ['Mode',          formatMode(p.base_mode)],
                                            ['Memory writes', `${fmtNum(p.memory_write_limit)} / mo`],
                                            ['Requests',      `${fmtNum(p.request_limit)} / mo`],
                                            ['API keys',      String(p.api_key_limit)],
                                        ].map(([k, v]) => (
                                            <div className="app-plan-meta-row" key={k}>
                                                <span className="app-plan-meta-key">{k}</span>
                                                <span className="app-plan-meta-val">{v}</span>
                                            </div>
                                        ))}
                                    </div>

                                    <div className="db-price-grid">
                                        {(['monthly', 'quarterly', 'yearly'] as const).map((cycle) => {
                                            const price = cycle === 'monthly' ? p.price_monthly : cycle === 'quarterly' ? p.price_quarterly : p.price_yearly;
                                            const suffix = cycle === 'monthly' ? '/ mo' : cycle === 'quarterly' ? '/ 3mo' : '/ yr';
                                            const isPrimary = cycle === 'yearly' || (cycle === 'monthly' && isFoundingOffer);

                                            return (
                                                <button
                                                    key={cycle}
                                                    type="button"
                                                    className={`app-btn ${isPrimary ? 'app-btn-primary' : 'app-btn-ghost'}`}
                                                    style={{ width: '100%', justifyContent: 'space-between' }}
                                                    disabled={checkingOut}
                                                    onClick={() => startCheckout(p.slug, cycle)}
                                                >
                                                    <span style={{ textTransform: 'capitalize' }}>{cycle}</span>
                                                    {isFoundingOffer && cycle === 'monthly' ? (
                                                        <span style={{ color: isPrimary ? 'inherit' : 'var(--app-accent)', fontFamily: 'var(--font-mono)', textAlign: 'right' }}>
                                                            ₹{foundingOffer.display_price} today
                                                            <span style={{ textDecoration: 'line-through', opacity: 0.5, fontSize: '10px', marginLeft: '6px' }}>
                                                                ₹{foundingOffer.original_price}
                                                            </span>
                                                            <span style={{ opacity: 0.65, fontSize: '9px', display: 'block', marginTop: '2px' }}>
                                                                Then ₹{foundingOffer.next_price}/month
                                                            </span>
                                                        </span>
                                                    ) : (
                                                        <span style={{ color: isPrimary ? 'inherit' : 'var(--app-accent)', fontFamily: 'var(--font-mono)' }}>
                                                            {fmtMoney(price)}<span style={{ opacity: 0.5, fontSize: '9px' }}> {suffix}</span>
                                                        </span>
                                                    )}
                                                </button>
                                            );
                                        })}
                                    </div>
                                </div>
                                );
                            })}
                        </div>
                    </div>
                )}

                {/* ── Observability Modules ── */}
                <div className="app-panel">
                    <div className="app-panel-head">
                        <div>
                            <h2>Observability & Insights</h2>
                            <p>Real-time analytics and memory inspection.</p>
                        </div>
                    </div>

                    {(recentUsage.length === 0 && memories.length === 0) ? (
                        <div className="obs-empty-banner">
                            <div className="obs-empty-icon">
                                <ShieldCheck size={20} />
                            </div>
                            <div className="obs-empty-title">Start sending data to unlock insights</div>
                            <div className="obs-empty-desc">
                                Once your API keys are active, this section will populate with a premium Memory Inspector 
                                and detailed usage analytics for the current day.
                            </div>
                        </div>
                    ) : (
                        <div style={{ display: 'flex', flexDirection: 'column', gap: '24px', marginTop: '16px' }}>
                            
                            {/* Today's Insights */}
                            {todayInsights && (
                                <div style={{ paddingBottom: '24px', borderBottom: '1px solid var(--tm-border)' }}>
                                    <div className="obs-title">Today's Activity</div>
                                    <div className="obs-insights-grid">
                                        <div className="obs-insight-card">
                                            <span className="obs-insight-label">Memory Writes</span>
                                            <span className="obs-insight-value">{fmtNum(todayInsights.memory_writes)}</span>
                                        </div>
                                        <div className="obs-insight-card">
                                            <span className="obs-insight-label">Recall Hit Rate</span>
                                            <span className="obs-insight-value">{todayInsights.recall_hit_rate}%</span>
                                        </div>
                                        <div className="obs-insight-card">
                                            <span className="obs-insight-label">Total Tokens</span>
                                            <span className="obs-insight-value">{fmtNum(todayInsights.total_tokens)}</span>
                                        </div>
                                        <div className="obs-insight-card">
                                            <span className="obs-insight-label">Env Split</span>
                                            <div style={{ display: 'flex', gap: '8px', marginTop: '8px' }}>
                                                <span className="app-badge app-badge-test">{fmtNum(todayInsights.split.test)} test</span>
                                                <span className="app-badge app-badge-live">{fmtNum(todayInsights.split.live)} live</span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginTop: '16px' }}>
                                        <span className="obs-subtitle">Top Endpoints (Today)</span>
                                        <Link href="/logs?tab=insights" className="app-panel-link">
                                            View all-time insights <ArrowRight size={11} style={{ display: 'inline', verticalAlign: 'middle' }} />
                                        </Link>
                                    </div>
                                    <div className="obs-endpoints-list">
                                        {todayInsights.top_endpoints.length === 0 && (
                                            <span className="app-mono-label" style={{ color: 'var(--app-text-subtle)' }}>No requests today</span>
                                        )}
                                        {todayInsights.top_endpoints.map(ep => (
                                            <div className="obs-endpoint-row" key={ep.endpoint}>
                                                <span className="obs-endpoint-name">{ep.endpoint}</span>
                                                <span className="obs-endpoint-count">{fmtNum(ep.total)} calls</span>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            )}

                            {/* Memory Inspector */}
                            <div>
                                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', gap: '12px' }}>
                                    <div>
                                        <div className="obs-title">Memory Inspector</div>
                                        <div className="obs-subtitle">Recent atomic memories created across your environments.</div>
                                    </div>
                                    <Link href="/memory-inspector" className="app-panel-link" style={{ marginTop: '2px' }}>
                                        View all <ArrowRight size={11} style={{ display: 'inline', verticalAlign: 'middle' }} />
                                    </Link>
                                </div>
                                
                                {memories.length === 0 ? (
                                    <div className="app-empty-state" style={{ marginTop: '16px' }}>
                                        <div className="app-empty-state-title">No memories stored yet</div>
                                        <p className="app-empty-state-desc">Use the /remember endpoint to store your first memory.</p>
                                    </div>
                                ) : (
                                    <div className="obs-memory-grid">
                                        {memories.map(mem => (
                                            <div className="obs-memory-card" key={mem.id}>
                                                <div className="obs-memory-head">
                                                    <span className="obs-memory-type">{mem.type}</span>
                                                    <span className="obs-memory-date">
                                                        {new Date(mem.created_at).toLocaleDateString()}
                                                    </span>
                                                </div>
                                                <div className="obs-memory-content">
                                                    {mem.content}
                                                </div>
                                                <div className="obs-memory-meta">
                                                    <div className="obs-meta-item">
                                                        <Activity size={10} /> Conf: {Number(mem.confidence).toFixed(2)}
                                                    </div>
                                                    <div className="obs-meta-item">
                                                        <Clock3 size={10} /> Decay: {Number(mem.decay_score).toFixed(2)}
                                                    </div>
                                                    <div className="obs-meta-item">
                                                        Status: <span style={{ color: 'var(--tm-primary)' }}>{mem.status}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                )}
                            </div>
                        </div>
                    )}
                </div>

                {/* ── Recent API activity ── */}
                <div className="app-panel app-panel-flex" style={{ height: '400px' }}>
                    <div className="app-panel-head" style={{ flexShrink: 0, marginBottom: '16px' }}>
                        <div>
                            <h2>Recent API Activity</h2>
                            <p>Last 24-hour requests across all keys</p>
                        </div>
                        <Link href="/logs?tab=usage" className="app-panel-link">
                            View all logs <ArrowRight size={11} style={{ display: 'inline', verticalAlign: 'middle' }} />
                        </Link>
                    </div>

                    {recentUsage.length === 0 ? (
                        <div className="app-empty-state">
                            <div className="app-empty-state-title">No requests yet</div>
                            <p className="app-empty-state-desc">
                                Once your application starts calling the TraceMem API, requests will appear here.
                            </p>
                        </div>
                    ) : (
                        <div className="app-panel-scroll-area">
                            <div className="db-activity-list">
                                {recentUsage.slice(0, 8).map((row) => (
                                    <div key={row.id} className="db-activity-row">
                                        <div
                                            className="db-activity-dot"
                                            style={{
                                                background: row.status_code >= 500
                                                    ? 'var(--app-error)'
                                                    : row.status_code >= 400
                                                    ? 'var(--app-warning)'
                                                    : 'var(--app-success)',
                                            }}
                                        />
                                        <div className="db-activity-body">
                                            <div className="db-activity-top">
                                                <span className="db-activity-method" style={{ color: methodColor(row.method) }}>
                                                    {row.method}
                                                </span>
                                                <span className="db-activity-endpoint">{row.endpoint}</span>
                                                <span className={`db-activity-status ${statusClass(row.status_code)}`}>
                                                    {row.status_code}
                                                </span>
                                                <span className="db-activity-latency">
                                                    {fmtLatency(row.latency_ms)}
                                                </span>
                                            </div>
                                            <div className="db-activity-meta">
                                                <span>{new Date(row.requested_at).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: false })}</span>
                                                {row.apiKey && (
                                                    <span className={`app-badge app-badge-${row.apiKey.environment}`} style={{ fontSize: '9px', padding: '1px 5px' }}>
                                                        {row.is_sandbox ? 'sandbox' : row.apiKey.environment}
                                                    </span>
                                                )}
                                                {row.request_host && (
                                                    <span className="db-activity-chip">
                                                        <Globe size={9} />
                                                        {row.request_host}
                                                    </span>
                                                )}
                                                {row.ip_address && (
                                                    <span className="db-activity-chip">
                                                        <Wifi size={9} />
                                                        {row.ip_address}
                                                    </span>
                                                )}
                                            </div>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>
                    )}
                </div>

            </div>
        </>
    );
}