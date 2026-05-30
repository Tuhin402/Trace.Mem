import { Head, router, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import {
    CreditCard,
    CheckCircle2,
    Clock3,
    ShieldCheck,
    Layers,
    AlertTriangle,
    XCircle,
    ChevronDown,
    X,
} from 'lucide-react';
import { useToast } from '@/components/app/toast';

/* ── Types ──────────────────────────────────────────────── */
type SubscriptionFeature = {
    id: number;
    feature_scope: 'global' | 'model';
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

type UserSubscription = {
    id: number;
    status: string;
    billing_cycle: 'monthly' | 'quarterly' | 'yearly';
    starts_at?: string | null;
    renews_at?: string | null;
    ends_at?: string | null;
    auto_renew?: boolean;
    cancelled_at?: string | null;
    cancellation_reason?: string | null;
    is_cancelled?: boolean;
};

type UsageSummary = {
    active_keys: number;
    total_requests: number;
};

type PageProps = {
    plan?: SubscriptionPlan | null;
    plans?: SubscriptionPlan[];
    subscription?: UserSubscription | null;
    usage?: UsageSummary | null;
    flash?: {
        message?: string;
        error?: string;
    };
};

/* ── Cancellation reasons ────────────────────────────────── */
const CANCEL_REASONS = [
    'Too expensive for my current use case',
    "I don't use it enough to justify the cost",
    'Missing features I need for my workflow',
    'Switching to a different memory / context solution',
    'Technical issues or reliability concerns',
    'Just taking a break, I plan to return later',
];

/* ── Helpers ─────────────────────────────────────────────── */
function money(value: string | number | null | undefined): string {
    const n = Number(value ?? 0);
    return `$${Number.isFinite(n) ? n.toFixed(2) : '0.00'}`;
}

function formatMode(mode: string) {
    return mode === 'ai_first' ? 'AI First' : 'Semantic Only';
}

/* ── Quota bar ───────────────────────────────────────────── */
function QuotaBar({ label, used, limit }: { label: string; used: number; limit: number }) {
    const pct   = limit > 0 ? Math.min(100, (used / limit) * 100) : 0;
    const color = pct >= 90 ? 'var(--app-error)' : pct >= 70 ? 'var(--app-warning)' : 'var(--app-accent)';
    return (
        <div className="bl-quota-bar">
            <div className="bl-quota-bar-header">
                <span className="bl-quota-bar-label">{label}</span>
                <span className="bl-quota-bar-nums">
                    {used.toLocaleString()} <span style={{ opacity: 0.4 }}>/ {limit.toLocaleString()}</span>
                </span>
            </div>
            <div className="bl-quota-bar-track">
                <div className="bl-quota-bar-fill" style={{ width: `${pct}%`, background: color }} />
            </div>
        </div>
    );
}

/* ── Plan card ───────────────────────────────────────────── */
function PlanCard({
    plan,
    currentPlanSlug,
    onSelect,
}: {
    plan: SubscriptionPlan;
    currentPlanSlug?: string | null;
    onSelect: (slug: string, cycle: 'monthly' | 'quarterly' | 'yearly') => void;
}) {
    const isCurrent = plan.slug === currentPlanSlug;

    return (
        <div className={`app-plan-card bl-plan-card${isCurrent ? ' app-plan-card-active' : ''}`}>
            <div className="bl-plan-card-header">
                <div>
                    <div className="app-plan-name">{plan.name}</div>
                    {plan.description && (
                        <div className="app-plan-desc" style={{ marginTop: '6px' }}>{plan.description}</div>
                    )}
                </div>
                {isCurrent && <span className="app-badge app-badge-current">Current Plan</span>}
            </div>

            <div className="app-plan-meta">
                {[
                    ['Base mode',       formatMode(plan.base_mode)],
                    ['Memory writes',   `${plan.memory_write_limit.toLocaleString()} / mo`],
                    ['API requests',    `${plan.request_limit.toLocaleString()} / mo`],
                    ['API keys',        String(plan.api_key_limit)],
                    ['Live rate limit', `${plan.request_rate_limit_max_requests} req / ${plan.request_rate_limit_window_seconds}s`],
                    ['Test rate limit', `${plan.test_rate_limit_max_requests} req / ${plan.test_rate_limit_window_seconds}s`],
                    ['Test keys',       plan.allow_test_keys ? 'Yes' : 'No'],
                    ['Live keys',       plan.allow_live_keys ? 'Yes' : 'No'],
                ].map(([k, v]) => (
                    <div className="app-plan-meta-row" key={k}>
                        <span className="app-plan-meta-key">{k}</span>
                        <span className="app-plan-meta-val">{v}</span>
                    </div>
                ))}
            </div>

            <div className="bl-pricing-grid">
                {(['monthly', 'quarterly', 'yearly'] as const).map((cycle) => {
                    const price = cycle === 'monthly'   ? plan.price_monthly
                                : cycle === 'quarterly' ? plan.price_quarterly
                                :                        plan.price_yearly;
                    const suffix = cycle === 'monthly' ? '/ mo' : cycle === 'quarterly' ? '/ 3 mo' : '/ yr';
                    const isPrimary = cycle === 'yearly' && !isCurrent;
                    return (
                        <button
                            key={cycle}
                            type="button"
                            className={`app-btn ${isPrimary ? 'app-btn-primary' : 'app-btn-ghost'}`}
                            style={{ width: '100%', justifyContent: 'space-between' }}
                            onClick={() => onSelect(plan.slug, cycle)}
                        >
                            <span style={{ textTransform: 'capitalize' }}>{cycle}</span>
                            <span style={{ color: isPrimary ? 'inherit' : 'var(--app-accent)', fontFamily: 'var(--font-mono)' }}>
                                {money(price)}<span style={{ opacity: 0.5, fontSize: '10px' }}> {suffix}</span>
                            </span>
                        </button>
                    );
                })}
            </div>
        </div>
    );
}

/* ── Cancel subscription modal ───────────────────────────── */
function CancelModal({
    subscription,
    onClose,
}: {
    subscription: UserSubscription;
    onClose: () => void;
}) {
    const [reason, setReason]       = useState('');
    const [submitting, setSubmitting] = useState(false);

    const handleSubmit = () => {
        if (!reason) return;
        setSubmitting(true);
        router.post(
            '/billing/cancel-subscription',
            { reason },
            {
                onFinish: () => setSubmitting(false),
                onSuccess: onClose,
            },
        );
    };

    // Close on Escape
    useEffect(() => {
        const handler = (e: KeyboardEvent) => { if (e.key === 'Escape') onClose(); };
        window.addEventListener('keydown', handler);
        return () => window.removeEventListener('keydown', handler);
    }, [onClose]);

    return (
        <div className="bl-modal-overlay" role="dialog" aria-modal="true" aria-label="Cancel Subscription">
            {/* Backdrop */}
            <div className="bl-modal-backdrop" onClick={onClose} />

            <div className="bl-modal">
                {/* Header */}
                <div className="bl-modal-header">
                    <div className="bl-modal-header-icon">
                        <AlertTriangle size={20} />
                    </div>
                    <div>
                        <h2 className="bl-modal-title">Cancel Subscription</h2>
                        <p className="bl-modal-subtitle">This action takes effect immediately.</p>
                    </div>
                    <button type="button" className="bl-modal-close" onClick={onClose} aria-label="Close">
                        <X size={16} />
                    </button>
                </div>

                {/* Warning banner */}
                <div className="bl-modal-warning">
                    <XCircle size={15} />
                    <div>
                        <strong>You will lose access immediately.</strong>
                        {' '}Live API keys will stop working, memory writes will be blocked,
                        and your rate limits will drop to the free tier as soon as you confirm.
                        {subscription.renews_at && (
                            <span> Your subscription was set to renew on <strong>{subscription.renews_at}</strong>.</span>
                        )}
                    </div>
                </div>

                {/* Reason selection */}
                <div className="bl-modal-body">
                    <p className="bl-modal-reason-heading">
                        Before you go, please tell us why you're cancelling:
                    </p>
                    <div className="bl-reason-list">
                        {CANCEL_REASONS.map((r) => (
                            <label
                                key={r}
                                className={`bl-reason-item${reason === r ? ' bl-reason-item--selected' : ''}`}
                            >
                                <input
                                    type="radio"
                                    name="cancel_reason"
                                    value={r}
                                    checked={reason === r}
                                    onChange={() => setReason(r)}
                                    className="bl-reason-radio"
                                />
                                <span className="bl-reason-text">{r}</span>
                            </label>
                        ))}
                    </div>
                </div>

                {/* Actions */}
                <div className="bl-modal-footer">
                    <button
                        type="button"
                        className="app-btn app-btn-ghost"
                        onClick={onClose}
                        disabled={submitting}
                    >
                        Keep Subscription
                    </button>
                    <button
                        type="button"
                        className="app-btn app-btn-danger"
                        onClick={handleSubmit}
                        disabled={!reason || submitting}
                    >
                        {submitting ? 'Cancelling…' : 'Yes, Cancel My Subscription'}
                    </button>
                </div>
            </div>
        </div>
    );
}

/* ── Main page ───────────────────────────────────────────── */
export default function Billing() {
    const { props }           = usePage<PageProps>();
    const { toast, Toasts }   = useToast();

    const plan         = props.plan ?? null;
    const plans        = props.plans ?? [];
    const subscription = props.subscription ?? null;
    const usage        = props.usage ?? null;
    const flashMsg     = props.flash?.message ?? null;
    const flashErr     = props.flash?.error ?? null;

    const [cancelOpen,  setCancelOpen]  = useState(false);
    const [showModal,   setShowModal]   = useState(false);

    useEffect(() => {
        if (flashMsg) toast(flashMsg, 'success');
        if (flashErr) toast(flashErr, 'error');
    }, []);

    const startCheckout = (planSlug: string, billingCycle: 'monthly' | 'quarterly' | 'yearly') => {
        router.post('/billing/checkout', { plan_slug: planSlug, billing_cycle: billingCycle });
    };

    const isCancelled = subscription?.is_cancelled ?? false;

    return (
        <>
            <Head title="Billing" />
            <Toasts />

            {/* Cancellation modal */}
            {showModal && subscription && (
                <CancelModal
                    subscription={subscription}
                    onClose={() => setShowModal(false)}
                />
            )}

            <div className="app-page">

                {/* ── Header ── */}
                <div className="app-page-header">
                    <div>
                        <h1 className="app-page-title">Billing</h1>
                        <p className="app-page-subtitle">
                            Manage your subscription plan and billing cycle.
                        </p>
                    </div>
                </div>

                {/* ── Current subscription / Cancelled / No plan ── */}
                {isCancelled ? (
                    /* ── Cancelled state ── */
                    <div className="app-panel">
                        <div className="app-panel-head">
                            <div>
                                <h2>Subscription Cancelled</h2>
                                <p>Your subscription has been cancelled and access has been revoked.</p>
                            </div>
                            <span className="app-badge app-badge-revoked">
                                <XCircle size={10} />
                                Cancelled
                            </span>
                        </div>
                        <div className="bl-cancelled-info">
                            {subscription?.cancelled_at && (
                                <div className="bl-sub-meta-item">
                                    <Clock3 size={13} />
                                    <span>Cancelled on {subscription.cancelled_at}</span>
                                </div>
                            )}
                            {subscription?.cancellation_reason && (
                                <div className="bl-sub-meta-item" style={{ marginTop: '6px' }}>
                                    <AlertTriangle size={13} />
                                    <span>Reason: <em>{subscription.cancellation_reason}</em></span>
                                </div>
                            )}
                            <p className="bl-cancelled-note">
                                Choose a plan below to resubscribe and restore access to your TraceMem features.
                            </p>
                        </div>
                    </div>
                ) : plan ? (
                    /* ── Active plan ── */
                    <div className="app-panel">
                        <div className="app-panel-head">
                            <div>
                                <h2>Active Subscription</h2>
                                <p>Your current plan and quota usage</p>
                            </div>
                            <span className="app-badge app-badge-active">
                                <CheckCircle2 size={10} />
                                Active
                            </span>
                        </div>

                        {/* Plan name + meta */}
                        <div className="bl-current-plan">
                            <div className="bl-current-plan-name">
                                <CreditCard size={18} style={{ color: 'var(--app-accent)' }} />
                                <span>{plan.name}</span>
                            </div>

                            {subscription && (
                                <div className="bl-sub-meta">
                                    <div className="bl-sub-meta-item">
                                        <Clock3 size={13} />
                                        <span>
                                            {subscription.billing_cycle
                                                ? `Billed ${subscription.billing_cycle}`
                                                : 'Active'}
                                        </span>
                                    </div>
                                    {subscription.starts_at && (
                                        <div className="bl-sub-meta-item">
                                            <CheckCircle2 size={13} />
                                            <span>Started {subscription.starts_at}</span>
                                        </div>
                                    )}
                                    {subscription.renews_at && (
                                        <div className="bl-sub-meta-item">
                                            <ShieldCheck size={13} />
                                            <span>Renews {subscription.renews_at}</span>
                                        </div>
                                    )}
                                    {subscription.ends_at && !subscription.renews_at && (
                                        <div className="bl-sub-meta-item" style={{ color: 'var(--app-warning)' }}>
                                            <Clock3 size={13} />
                                            <span>Expires {subscription.ends_at}</span>
                                        </div>
                                    )}
                                    {subscription.auto_renew === false && (
                                        <div className="bl-sub-meta-item" style={{ color: 'var(--app-warning)' }}>
                                            <AlertTriangle size={13} />
                                            <span>Auto-renew off</span>
                                        </div>
                                    )}
                                </div>
                            )}

                            {/* Quota bars */}
                            {usage && (
                                <div className="bl-quota-bars">
                                    <QuotaBar
                                        label="API Requests used"
                                        used={usage.total_requests}
                                        limit={plan.request_limit}
                                    />
                                    <QuotaBar
                                        label="API Keys used"
                                        used={usage.active_keys}
                                        limit={plan.api_key_limit}
                                    />
                                    <QuotaBar
                                        label="Memory writes limit"
                                        used={0}
                                        limit={plan.memory_write_limit}
                                    />
                                </div>
                            )}

                            {/* Plan limits badges */}
                            <div className="bl-current-limits">
                                <span className="app-badge app-badge-neutral">{plan.memory_write_limit.toLocaleString()} writes / mo</span>
                                <span className="app-badge app-badge-neutral">{plan.request_limit.toLocaleString()} requests / mo</span>
                                <span className="app-badge app-badge-neutral">{plan.api_key_limit} API keys</span>
                                <span className="app-badge app-badge-neutral">{formatMode(plan.base_mode)}</span>
                            </div>
                        </div>

                        {/* ── Cancel subscription accordion ── */}
                        <div className="bl-cancel-accordion">
                            <button
                                type="button"
                                className="bl-cancel-toggle"
                                onClick={() => setCancelOpen((v) => !v)}
                                aria-expanded={cancelOpen}
                            >
                                <span className="bl-cancel-toggle-label">
                                    <AlertTriangle size={13} />
                                    Cancel subscription
                                </span>
                                <ChevronDown
                                    size={14}
                                    className="bl-cancel-toggle-chevron"
                                    style={{ transform: cancelOpen ? 'rotate(180deg)' : 'rotate(0deg)' }}
                                />
                            </button>

                            {cancelOpen && (
                                <div className="bl-cancel-body">
                                    <p className="bl-cancel-desc">
                                        Cancelling will <strong>immediately revoke</strong> all paid features -
                                        live keys, memory write quota, and higher rate limits.
                                        You will drop to the free tier right away.
                                    </p>
                                    <button
                                        type="button"
                                        className="app-btn app-btn-danger"
                                        onClick={() => setShowModal(true)}
                                    >
                                        <XCircle size={14} />
                                        Open Cancellation Form
                                    </button>
                                </div>
                            )}
                        </div>
                    </div>
                ) : (
                    /* ── No plan ── */
                    <div className="app-panel">
                        <div className="bl-no-plan">
                            <div className="bl-no-plan-icon">
                                <Layers size={28} />
                            </div>
                            <div className="bl-no-plan-text">
                                <h3>No active plan</h3>
                                <p>
                                    You are currently on the free tier. Choose a plan below to unlock
                                    live API keys, higher memory write limits, and faster rate limits.
                                </p>
                            </div>
                        </div>
                    </div>
                )}

                {/* ── Plans grid ── */}
                {plans.length > 0 && (
                    <div className="app-panel">
                        <div className="app-panel-head">
                            <div>
                                <h2>{plan && !isCancelled ? 'Change Plan' : 'Available Plans'}</h2>
                                <p>
                                    {plan && !isCancelled
                                        ? 'Select a plan to upgrade or change your billing cycle.'
                                        : 'Pick the right plan for your usage. No hidden fees.'}
                                </p>
                            </div>
                        </div>

                        <div className="bl-plans-grid">
                            {plans.map((p) => (
                                <PlanCard
                                    key={p.id}
                                    plan={p}
                                    currentPlanSlug={isCancelled ? null : plan?.slug}
                                    onSelect={startCheckout}
                                />
                            ))}
                        </div>
                    </div>
                )}

                {/* ── Stripe note ── */}
                <div className="bl-stripe-note">
                    <ShieldCheck size={14} />
                    <span>
                        All payments are processed securely by Stripe. TraceMem never stores your
                        card details. You can cancel or change your plan at any time.
                    </span>
                </div>

            </div>
        </>
    );
}
