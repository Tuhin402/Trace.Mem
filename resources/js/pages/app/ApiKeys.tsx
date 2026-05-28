import { Head, Link, router, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import {
    KeyRound,
    Copy,
    Trash2,
    Activity,
    Clock3,
    Zap,
    AlertTriangle,
    CheckCircle2,
    Plus,
    ArrowRight,
    Globe,
    Wifi,
    Shield,
    Info,
} from 'lucide-react';
import { useToast } from '@/components/app/toast';
import { fmtNum, fmtLatency, statusClass } from '@/lib/fmt';

/* ── Types ────────────────────────────────────────────────── */
type ApiKeyItem = {
    id: number;
    name: string;
    environment: 'test' | 'live';
    mode: 'semantic_only' | 'ai_first';
    key_prefix: string;
    key_last4?: string | null;
    usage_count: number;
    last_used_at?: string | null;
    expires_at?: string | null;
    revoked_at?: string | null;
    created_at: string;
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
    usageStats?: UsageStats;
    usageLogs?: UsageRecentItem[];
    flash?: {
        plain_key?: string;
        message?: string;
        error?: string;
    };
};

/* ── Helpers ─────────────────────────────────────────────── */
function formatMode(mode: string) {
    return mode === 'ai_first' ? 'AI First' : 'Semantic Only';
}

function methodColor(method: string): string {
    switch (method.toUpperCase()) {
        case 'GET': return 'var(--app-info)';
        case 'POST': return 'var(--app-success)';
        case 'DELETE': return 'var(--app-error)';
        default: return 'var(--app-text-dim)';
    }
}

/**
 * Returns days until expiry for a key, or null if no expiry.
 */
function daysUntilExpiry(expiresAt: string | null | undefined): number | null {
    if (!expiresAt) return null;
    const diff = new Date(expiresAt).getTime() - Date.now();
    return Math.max(0, Math.ceil(diff / (1000 * 60 * 60 * 24)));
}

/* ── Component ───────────────────────────────────────────── */
export default function ApiKeys() {
    const { props } = usePage<PageProps>();
    const { toast, Toasts } = useToast();

    const plan = props.plan ?? null;
    const usageStats = props.usageStats;
    const recentUsage = props.usageLogs ?? [];
    const latestKey = props.flash?.plain_key ?? null;
    const flashMsg = props.flash?.message ?? null;
    const flashErr = props.flash?.error ?? null;

    const [localKeys, setLocalKeys] = useState<ApiKeyItem[]>(props.apiKeys ?? []);
    const [name, setName] = useState('');
    const [loading, setLoading] = useState(false);
    const [revokeConfirmId, setRevokeConfirmId] = useState<number | null>(null);

    useEffect(() => {
        setLocalKeys(props.apiKeys ?? []);
    }, [props.apiKeys]);

    useEffect(() => {
        if (flashMsg) toast(flashMsg, 'success');
        if (flashErr) toast(flashErr, 'error');
    }, []);

    const canCreateTest = plan?.allow_test_keys ?? true;
    const canCreateLive = plan?.allow_live_keys ?? false;
    const activeKeys = localKeys.filter((k) => !k.revoked_at);
    const revokedKeys = localKeys.filter((k) => k.revoked_at);

    const generateKey = (environment: 'test' | 'live') => {
        if (plan && activeKeys.length >= plan.api_key_limit) {
            toast(
                `Key limit reached (${plan.api_key_limit} max on ${plan.name}). Revoke a key or upgrade your plan.`,
                'error',
            );
            return;
        }
        if (environment === 'live' && !canCreateLive) {
            toast('Live keys require an active paid plan. Please upgrade first.', 'error');
            return;
        }
        if (environment === 'test' && !canCreateTest) {
            toast('Test keys are not allowed on your current plan.', 'error');
            return;
        }

        setLoading(true);
        router.post(
            '/api-keys',
            { name: name.trim() || `${environment}-key`, environment },
            {
                preserveScroll: true,
                onSuccess: () => setName(''),
                onFinish: () => setLoading(false),
            },
        );
    };

    const revokeKey = (id: number) => {
        setLocalKeys((prev) =>
            prev.map((k) =>
                k.id === id ? { ...k, revoked_at: new Date().toISOString() } : k,
            ),
        );
        setRevokeConfirmId(null);
        toast('API key revoked.', 'success');

        const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? '';

        fetch(`/api-keys/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'application/json',
            },
        })
            .then((res) => {
                if (!res.ok && res.status !== 204) {
                    setLocalKeys(props.apiKeys ?? []);
                    toast('Failed to revoke key. Please try again.', 'error');
                    return;
                }
                router.reload({ only: ['apiKeys'] });
            })
            .catch(() => {
                setLocalKeys(props.apiKeys ?? []);
                toast('Failed to revoke key. Please try again.', 'error');
            });
    };

    const copyKey = async (value: string) => {
        try {
            await navigator.clipboard.writeText(value);
            toast('API key copied to clipboard.', 'success');
        } catch {
            toast('Unable to copy. Please copy it manually.', 'error');
        }
    };

    return (
        <>
            <Head title="API Keys" />
            <Toasts />

            <div className="app-page">

                {/* ── Header ── */}
                <div className="app-page-header">
                    <div>
                        <h1 className="app-page-title">API Keys</h1>
                        <p className="app-page-subtitle">
                            Generate and manage keys for integrating the TraceMem memory layer.
                        </p>
                    </div>
                    {plan && (
                        <div className="ak-plan-badge">
                            <span className="app-badge app-badge-current">{plan.name}</span>
                            <span className="ak-plan-limits">
                                {activeKeys.length} / {plan.api_key_limit} keys
                            </span>
                        </div>
                    )}
                </div>

                {/* ── Stats row ── */}
                <div className="app-stats-grid">
                    <div className="app-stat-card">
                        <div className="app-stat-icon"><Activity size={15} /></div>
                        <div className="app-stat-label">Requests (24h)</div>
                        <div className="app-stat-value">{fmtNum(usageStats?.requests_24h)}</div>
                        <div className="app-stat-trend">Total: {fmtNum(usageStats?.total_requests)}</div>
                    </div>
                    <div className="app-stat-card">
                        <div className="app-stat-icon"><Clock3 size={15} /></div>
                        <div className="app-stat-label">Avg Latency</div>
                        <div className="app-stat-value">{fmtLatency(usageStats?.avg_latency_ms)}</div>
                        <div className="app-stat-trend">Slow: {fmtNum(usageStats?.slow_requests)}</div>
                    </div>
                    <div className="app-stat-card">
                        <div className="app-stat-icon"><CheckCircle2 size={15} /></div>
                        <div className="app-stat-label">Successful</div>
                        <div className="app-stat-value">{fmtNum(usageStats?.successful_requests)}</div>
                        <div className="app-stat-trend">Client errors: {fmtNum(usageStats?.client_errors)}</div>
                    </div>
                    <div className="app-stat-card">
                        <div className="app-stat-icon"><Zap size={15} /></div>
                        <div className="app-stat-label">Active Keys</div>
                        <div className="app-stat-value">{activeKeys.length}</div>
                        <div className="app-stat-trend">Revoked: {revokedKeys.length}</div>
                    </div>
                </div>

                {/* ── New key reveal ── */}
                {latestKey && (
                    <div className="app-key-reveal">
                        <div className="app-key-reveal-header">
                            <span className="app-key-reveal-label">New API Key Generated</span>
                            <button
                                type="button"
                                className="app-btn app-btn-sm app-btn-secondary"
                                onClick={() => copyKey(latestKey)}
                            >
                                <Copy size={12} />
                                Copy
                            </button>
                        </div>
                        <code className="app-key-reveal-code">{latestKey}</code>
                        <div className="app-key-reveal-warning">
                            This secret key is shown <strong>only once</strong>. Copy it now and store it safely, it cannot be retrieved again.
                        </div>
                        {/* Expiry notice */}
                        <div className="ak-expiry-notice">
                            <Info size={12} />
                            <span>
                                Test keys are valid for <strong>30 days</strong> from generation.
                                Live keys do not expire while your subscription is active.
                            </span>
                        </div>
                    </div>
                )}

                {/* ── Two-column: Generate + Keys list ── */}
                <div className="app-main-grid">

                    {/* Generate card */}
                    <div className="app-panel">
                        <div className="app-panel-head">
                            <div>
                                <h2>Generate New Key</h2>
                                <p>Test keys use semantic-only mode. Live keys follow your plan.</p>
                            </div>
                        </div>

                        <div className="app-field" style={{ marginBottom: '16px' }}>
                            <label htmlFor="key-name">Key name</label>
                            <input
                                id="key-name"
                                type="text"
                                value={name}
                                onChange={(e) => setName(e.target.value)}
                                placeholder="e.g. production, postman, ci-cd"
                                onKeyDown={(e) => {
                                    if (e.key === 'Enter' && canCreateTest && !loading) {
                                        generateKey('test');
                                    }
                                }}
                            />
                        </div>

                        <div className="ak-generate-btns">
                            <button
                                type="button"
                                className="app-btn app-btn-secondary"
                                style={{ flex: 1 }}
                                disabled={!canCreateTest || loading}
                                onClick={() => generateKey('test')}
                            >
                                <Plus size={13} />
                                {loading ? 'Generating…' : 'Test Key'}
                            </button>
                            <button
                                type="button"
                                className="app-btn app-btn-primary"
                                style={{ flex: 1 }}
                                disabled={!canCreateLive || loading}
                                onClick={() => generateKey('live')}
                                title={!canCreateLive ? 'Upgrade your plan to generate live keys' : undefined}
                            >
                                <Plus size={13} />
                                {loading ? 'Generating…' : 'Live Key'}
                            </button>
                        </div>

                        {!canCreateLive && (
                            <div className="ak-upgrade-hint">
                                <AlertTriangle size={12} />
                                Live keys require an active paid plan.
                                <Link href="/billing" style={{ color: 'var(--app-accent)', marginLeft: '6px' }}>
                                    View plans →
                                </Link>
                            </div>
                        )}

                        {/* Quick integration snippet */}
                        <div className="app-divider" />
                        <div className="app-mono-label" style={{ marginBottom: '10px' }}>Quick integration</div>
                        <div className="app-code-block">
                            <pre>{`curl -X POST /api/v1/remember \\
  -H "Authorization: Bearer <your-key>" \\
  -H "Content-Type: application/json" \\
  -d '{"content": "User prefers dark mode"}'`}</pre>
                        </div>
                    </div>

                    {/* Keys list card */}
                    <div className="app-panel-dependent">
                        <div className="app-panel">
                            <div className="app-panel-head" style={{ flexShrink: 0, marginBottom: '16px' }}>
                                <div>
                                    <h2>Your API Keys</h2>
                                    <p>
                                        {localKeys.length === 0
                                            ? 'No keys yet. Generate one to get started.'
                                            : `${activeKeys.length} active · ${revokedKeys.length} revoked`}
                                    </p>
                                </div>
                            </div>

                            {localKeys.length === 0 ? (
                                <div className="app-empty-state">
                                    <KeyRound size={28} style={{ color: 'var(--app-text-subtle)', margin: '0 auto 12px' }} />
                                    <div className="app-empty-state-title">No API keys yet</div>
                                    <p className="app-empty-state-desc">
                                        Generate a test key from the form to start integrating TraceMem.
                                    </p>
                                </div>
                            ) : (
                                <div className="app-panel-scroll-area">
                                    <div className="ak-key-list">
                                        {localKeys.map((key) => {
                                            const isRevoked  = Boolean(key.revoked_at);
                                            const confirming = revokeConfirmId === key.id;
                                            const expiryDays = daysUntilExpiry(key.expires_at);
                                            const expiringSoon = expiryDays !== null && expiryDays <= 7;

                                            return (
                                                <div
                                                    key={key.id}
                                                    className={`ak-key-item${isRevoked ? ' ak-key-item--revoked' : ''}`}
                                                >
                                                    {/* Top row */}
                                                    <div className="ak-key-top">
                                                        <div className="ak-key-title-row">
                                                            <span className="ak-key-name">{key.name}</span>
                                                            <div className="ak-key-badges">
                                                                <span className={`app-badge app-badge-${key.environment}`}>
                                                                    {key.environment}
                                                                </span>
                                                                <span className="app-badge app-badge-neutral">
                                                                    {formatMode(key.mode)}
                                                                </span>
                                                                {isRevoked ? (
                                                                    <span className="app-badge app-badge-revoked">Revoked</span>
                                                                ) : (
                                                                    <span className="app-badge app-badge-active">Active</span>
                                                                )}
                                                                {expiringSoon && !isRevoked && (
                                                                    <span className="app-badge ak-badge-expiring">
                                                                        <AlertTriangle size={9} />
                                                                        Expires in {expiryDays}d
                                                                    </span>
                                                                )}
                                                            </div>
                                                        </div>

                                                        {/* Actions */}
                                                        {!isRevoked && (
                                                            <div className="ak-key-actions">
                                                                {confirming ? (
                                                                    <>
                                                                        <button
                                                                            type="button"
                                                                            className="app-btn app-btn-sm app-btn-danger"
                                                                            onClick={() => revokeKey(key.id)}
                                                                        >
                                                                            Confirm revoke
                                                                        </button>
                                                                        <button
                                                                            type="button"
                                                                            className="app-btn app-btn-sm app-btn-ghost"
                                                                            onClick={() => setRevokeConfirmId(null)}
                                                                        >
                                                                            Cancel
                                                                        </button>
                                                                    </>
                                                                ) : (
                                                                    <button
                                                                        type="button"
                                                                        className="app-btn app-btn-sm app-btn-danger"
                                                                        onClick={() => setRevokeConfirmId(key.id)}
                                                                        aria-label={`Revoke ${key.name}`}
                                                                    >
                                                                        <Trash2 size={12} />
                                                                        Revoke
                                                                    </button>
                                                                )}
                                                            </div>
                                                        )}
                                                    </div>

                                                    {/* Meta row */}
                                                    <div className="ak-key-meta">
                                                        <span className="ak-key-prefix">
                                                            {key.key_prefix}••••{key.key_last4 ?? '----'}
                                                        </span>
                                                        <span className="ak-key-sep">|</span>
                                                        <span>{fmtNum(key.usage_count)} uses</span>
                                                        {key.last_used_at && (
                                                            <>
                                                                <span className="ak-key-sep">|</span>
                                                                <span>Last used {key.last_used_at}</span>
                                                            </>
                                                        )}
                                                        {key.expires_at && !isRevoked && expiryDays !== null && (
                                                            <>
                                                                <span className="ak-key-sep">|</span>
                                                                <span style={{ color: expiringSoon ? 'var(--app-warning)' : 'var(--app-text-subtle)' }}>
                                                                    Expires in {expiryDays}d
                                                                </span>
                                                            </>
                                                        )}
                                                        {key.created_at && (
                                                            <>
                                                                <span className="ak-key-sep">|</span>
                                                                <span>Created {key.created_at}</span>
                                                            </>
                                                        )}
                                                    </div>
                                                </div>
                                            );
                                        })}
                                    </div>
                                </div>
                            )}
                        </div>
                    </div>
                </div>

                {/* ── Recent activity (NO pricing section here) ── */}
                <div className="app-panel app-panel-flex" style={{ height: '400px' }}>
                    <div className="app-panel-head" style={{ flexShrink: 0, marginBottom: '16px' }}>
                        <div>
                            <h2>Recent API Activity</h2>
                            <p>Last 24-hour requests made with your keys</p>
                        </div>
                        <Link href="/logs" className="app-panel-link">
                            View all <ArrowRight size={11} style={{ display: 'inline', verticalAlign: 'middle' }} />
                        </Link>
                    </div>

                    {recentUsage.length === 0 ? (
                        <div className="app-empty-state">
                            <Activity size={24} style={{ color: 'var(--app-text-subtle)', margin: '0 auto 12px' }} />
                            <div className="app-empty-state-title">No requests yet</div>
                            <p className="app-empty-state-desc">
                                Start using your API key to see requests appear here.
                            </p>
                        </div>
                    ) : (
                        <div className="app-panel-scroll-area">
                            <div className="db-activity-list">
                                {recentUsage.slice(0, 10).map((row) => (
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