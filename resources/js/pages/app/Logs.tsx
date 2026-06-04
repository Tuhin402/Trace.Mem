import { Head, Link, router, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import {
    Activity,
    ArrowLeft,
    ChevronLeft,
    ChevronRight,
    Search,
    Filter,
    Globe,
    Wifi,
    Shield,
    Clock3,
    BarChart3
} from 'lucide-react';
import { fmtLatency, fmtNum, statusClass, groupByMonth } from '@/lib/fmt';

/* ── Types ──────────────────────────────────────────────── */
type LogItem = {
    id: number;
    api_key_id: number;
    endpoint: string;
    method: string;
    status_code: number;
    latency_ms: number | null;
    ip_address?: string | null;
    request_host?: string | null;
    request_origin?: string | null;
    is_sandbox?: boolean;
    is_localhost?: boolean;
    request_id?: string | null;
    requested_at: string;
    apiKey?: {
        name: string;
        environment: 'test' | 'live';
        mode: 'semantic_only' | 'ai_first';
    } | null;
};

type InsightsData = {
    memory_writes: number;
    recall_hit_rate: number;
    total_tokens: number;
    top_endpoints: { endpoint: string; total: number }[];
    split: { test: number; live: number };
    latency_trend: { date: string; avg_latency: number }[];
    error_trend: { date: string; error_count: number }[];
    request_trend: { date: string; total: number }[];
    mode_distribution: { mode: string; total: number }[];
    key_usage: { name: string; environment: string; total_requests: number; avg_latency: number }[];
    error_rate: number;
    total_requests: number;
};

type Pagination = {
    current_page: number;
    last_page: number;
    total: number;
    per_page: number;
};

type PageProps = {
    logs?: LogItem[];
    pagination?: Pagination;
    availableMonths?: string[];
    selectedFilters?: {
        period?: string;
        month?: string;
        search?: string;
        tab?: string;
        environment?: string;
        status?: string;
        mode?: string;
    };
    insights?: InsightsData | null;
};

const PERIODS = [
    { key: 'today',      label: 'Today' },
    { key: '7_days',     label: '7 Days' },
    { key: '30_days',    label: '30 Days' },
    { key: '90_days',    label: '90 Days' },
    { key: 'all_time',   label: 'All Time' },
];

function methodColor(method: string): string {
    switch (method.toUpperCase()) {
        case 'GET':    return 'var(--app-info)';
        case 'POST':   return 'var(--app-success)';
        case 'DELETE': return 'var(--app-error)';
        case 'PUT':
        case 'PATCH':  return 'var(--app-warning)';
        default:       return 'var(--app-text-dim)';
    }
}

/* ── Interactive Chart Component ────────────────────────── */
function TooltipBar({ value, label, maxVal, color, unit }: { value: number, label: string, maxVal: number, color: string, unit?: string }) {
    const [hover, setHover] = useState(false);
    return (
        <div 
            style={{ flex: 1, display: 'flex', flexDirection: 'column', justifyContent: 'flex-end', position: 'relative', height: '100%' }}
            onMouseEnter={() => setHover(true)}
            onMouseLeave={() => setHover(false)}
            onClick={() => setHover(!hover)}
        >
            <div 
                style={{ 
                    background: color, 
                    opacity: hover ? 1 : 0.8, 
                    height: `${(value / maxVal) * 100}%`, 
                    minHeight: '4px', 
                    borderRadius: '2px 2px 0 0',
                    transition: 'opacity 0.2s, height 0.3s'
                }} 
            />
            {hover && (
                <div style={{
                    position: 'absolute',
                    bottom: 'calc(100% + 8px)',
                    left: '50%',
                    transform: 'translateX(-50%)',
                    background: 'var(--tm-surface-2)',
                    border: '1px solid var(--tm-border)',
                    padding: '6px 10px',
                    borderRadius: '6px',
                    fontSize: '12px',
                    color: 'var(--app-text)',
                    whiteSpace: 'nowrap',
                    zIndex: 20,
                    boxShadow: '0 4px 12px rgba(0,0,0,0.5)',
                    pointerEvents: 'none',
                    fontFamily: 'var(--font-sans)'
                }}>
                    <strong style={{ display: 'block', marginBottom: '4px', color: 'var(--app-text-dim)', fontSize: '11px', fontWeight: 600, textTransform: 'uppercase' }}>{label}</strong>
                    <span style={{ color: color, fontWeight: 700 }}>{fmtNum(value)}</span> {unit && <span style={{ color: 'var(--app-text-subtle)' }}>{unit}</span>}
                </div>
            )}
        </div>
    );
}

function MiniChart({ data, color, valueKey, labelKey, unit }: { data: any[], color: string, valueKey: string, labelKey: string, unit?: string }) {
    const maxVal = Math.max(...data.map(d => d[valueKey]), 1);
    
    return (
        <div style={{ display: 'flex', flexDirection: 'column', height: '180px' }}>
            <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: '11px', color: 'var(--app-text-subtle)', marginBottom: '8px' }}>
                <span>{fmtNum(maxVal)} {unit}</span>
                <span>0 {unit}</span>
            </div>
            <div style={{ display: 'flex', alignItems: 'flex-end', flex: 1, gap: '4px', borderBottom: '1px solid var(--tm-border)', position: 'relative', paddingTop: '16px' }}>
                {data.map(d => (
                    <TooltipBar key={d[labelKey]} value={d[valueKey]} label={d[labelKey]} maxVal={maxVal} color={color} unit={unit} />
                ))}
                {data.length === 0 && <span style={{ color: 'var(--app-text-dim)', margin: 'auto', position: 'absolute', left: '50%', transform: 'translateX(-50%)', bottom: '20px' }}>No data available</span>}
            </div>
            <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: '10px', color: 'var(--app-text-dim)', marginTop: '8px' }}>
                <span>{data.length > 0 ? data[0][labelKey] : ''}</span>
                <span>{data.length > 0 ? data[data.length - 1][labelKey] : ''}</span>
            </div>
        </div>
    );
}

/* ── Log Row ────────────────────────────────────────────── */
function LogRow({ item }: { item: LogItem }) {
    const [expanded, setExpanded] = useState(false);
    
    const ts    = new Date(item.requested_at);
    const time  = ts.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false });
    const env   = item.apiKey?.environment ?? 'test';
    const mode  = item.apiKey?.mode ?? 'semantic_only';
    const isSandbox = item.is_sandbox ?? (env === 'test');

    return (
        <div style={{ borderBottom: '1px solid var(--tm-border)' }}>
            <div 
                className="lg-row" 
                onClick={() => setExpanded(!expanded)}
                style={{ cursor: 'pointer', borderBottom: 'none', transition: 'background 0.2s', background: expanded ? 'rgba(255,255,255,0.02)' : 'transparent', padding: '16px 20px', display: 'flex', gap: '16px' }}
            >
                {/* Status dot */}
                <div
                    className="lg-status-dot"
                    style={{
                        marginTop: '6px',
                        background: item.status_code >= 500
                            ? 'var(--app-error)'
                            : item.status_code >= 400
                            ? 'var(--app-warning)'
                            : 'var(--app-success)',
                    }}
                    aria-hidden="true"
                />

                {/* Main content */}
                <div style={{ flex: 1, display: 'flex', flexDirection: 'column', gap: '12px' }}>
                    <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', gap: '16px' }}>
                        <div style={{ display: 'flex', flexDirection: 'column', gap: '4px' }}>
                            <span className="lg-endpoint" style={{ wordBreak: 'break-all', fontWeight: 600, color: 'var(--app-text)', fontSize: '14px' }}>{item.endpoint}</span>
                            <span className="lg-time" style={{ color: 'var(--app-text-subtle)', fontSize: '12px' }}>{time} <span style={{ opacity: 0.5, margin: '0 4px' }}>•</span> ID: {item.request_id?.slice(0,8) || 'N/A'}</span>
                        </div>
                        <div style={{ paddingTop: '2px' }}>
                            <ChevronRight size={18} style={{ color: 'var(--app-text-dim)', transform: expanded ? 'rotate(90deg)' : 'none', transition: 'transform 0.2s' }} />
                        </div>
                    </div>
                    
                    <div style={{ display: 'flex', flexWrap: 'wrap', gap: '10px', alignItems: 'center', fontSize: '12px' }}>
                        <span className={`lg-status ${statusClass(item.status_code)}`} style={{ padding: '2px 8px', borderRadius: '12px', fontWeight: 600 }}>
                            {item.status_code}
                        </span>
                        {item.latency_ms !== null && (
                            <span className="lg-latency" style={{ color: 'var(--app-text-dim)', display: 'flex', alignItems: 'center', gap: '4px' }}>
                                <Clock3 size={12} /> {fmtLatency(item.latency_ms)}
                            </span>
                        )}
                        <span className={`app-badge app-badge-${env}`}>{isSandbox ? 'test' : 'live'}</span>
                        <span className="app-badge" style={{ background: 'rgba(255,255,255,0.05)', color: 'var(--app-text-dim)' }}>
                            {mode === 'semantic_only' ? 'Semantic' : 'AI First'}
                        </span>
                    </div>
                </div>
            </div>
            
            {expanded && (
                <div style={{ padding: '20px 24px', background: 'var(--tm-surface-2)', borderTop: '1px dashed var(--tm-border-strong)', fontSize: '13px', color: 'var(--app-text-dim)', fontFamily: 'var(--font-mono)' }}>
                    <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(220px, 1fr))', gap: '20px' }}>
                        <div style={{ display: 'flex', flexDirection: 'column', gap: '8px' }}>
                            <div><strong style={{ color: 'var(--app-text)', display: 'inline-block', width: '90px' }}>Method:</strong> <span style={{ color: methodColor(item.method) }}>{item.method}</span></div>
                            <div><strong style={{ color: 'var(--app-text)', display: 'inline-block', width: '90px' }}>Req ID:</strong> {item.request_id || 'N/A'}</div>
                            <div><strong style={{ color: 'var(--app-text)', display: 'inline-block', width: '90px' }}>Timestamp:</strong> {new Date(item.requested_at).toISOString()}</div>
                        </div>
                        <div style={{ display: 'flex', flexDirection: 'column', gap: '8px' }}>
                            <div><strong style={{ color: 'var(--app-text)', display: 'inline-block', width: '90px' }}>IP Addr:</strong> {item.ip_address || 'N/A'}</div>
                            <div><strong style={{ color: 'var(--app-text)', display: 'inline-block', width: '90px' }}>Origin:</strong> {item.request_origin || 'N/A'}</div>
                            <div><strong style={{ color: 'var(--app-text)', display: 'inline-block', width: '90px' }}>Host:</strong> {item.request_host || 'N/A'}</div>
                        </div>
                        <div style={{ display: 'flex', flexDirection: 'column', gap: '8px' }}>
                            <div><strong style={{ color: 'var(--app-text)', display: 'inline-block', width: '90px' }}>API Key:</strong> {item.apiKey?.name || 'N/A'}</div>
                            <div><strong style={{ color: 'var(--app-text)', display: 'inline-block', width: '90px' }}>Localhost:</strong> {item.is_localhost ? 'Yes' : 'No'}</div>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
}

/* ── Main page ──────────────────────────────────────────── */
export default function Logs() {
    const { props }  = usePage<PageProps>();
    const logs       = props.logs ?? [];
    const pagination = props.pagination;
    const filters    = props.selectedFilters ?? {};
    const insights   = props.insights;
    const activeTab  = filters.tab ?? 'usage';

    const [search, setSearch]     = useState(filters.search ?? '');
    const [searchTimer, setSearchTimer] = useState<ReturnType<typeof setTimeout> | null>(null);

    // Debounced search
    useEffect(() => {
        if (searchTimer) clearTimeout(searchTimer);
        const t = setTimeout(() => {
            if (search !== (filters.search ?? '')) {
                applyFilter({ search: search || undefined, page: undefined });
            }
        }, 400);
        setSearchTimer(t);
        return () => clearTimeout(t);
    }, [search]);

    function applyFilter(patch: Record<string, string | number | undefined>) {
        router.get('/logs', { ...filters, ...patch }, {
            preserveScroll: true,
            preserveState: true,
            replace: true,
        });
    }

    const grouped = groupByMonth(logs);
    const totalShown = logs.length;

    return (
        <>
            <Head title="Usage Logs" />
            <style>{`
                .lg-hide-scroll::-webkit-scrollbar {
                    display: none;
                }
                .lg-hide-scroll {
                    -ms-overflow-style: none;
                    scrollbar-width: none;
                }
            `}</style>

            <div className="app-page">

                {/* ── Header ── */}
                <div className="app-page-header">
                    <div>
                        <div className="lg-breadcrumb">
                            <Link href="/dashboard" className="lg-back-link">
                                <ArrowLeft size={12} />
                                Dashboard
                            </Link>
                            <span className="lg-breadcrumb-sep">/</span>
                            <span>Usage Logs</span>
                        </div>
                        <h1 className="app-page-title" style={{ marginTop: '8px' }}>Usage Logs</h1>
                        <p className="app-page-subtitle">
                            Inspect API activity, latency, usage trends, and memory operations across your TraceMem account.
                        </p>
                    </div>
                    <Link href="/api-keys" className="app-btn app-btn-secondary">
                        Manage Keys
                    </Link>
                </div>

                {/* ── Tabs ── */}
                <div style={{ borderBottom: '1px solid var(--tm-border)', marginBottom: '24px' }}>
                    <div style={{ display: 'flex', gap: '24px' }}>
                        <button
                            type="button"
                            onClick={() => applyFilter({ tab: 'usage', page: undefined })}
                            style={{
                                padding: '12px 0',
                                background: 'transparent',
                                border: 'none',
                                borderBottom: activeTab === 'usage' ? '2px solid var(--tm-primary)' : '2px solid transparent',
                                color: activeTab === 'usage' ? 'var(--tm-primary)' : 'var(--app-text-dim)',
                                fontFamily: 'var(--font-headline)',
                                fontSize: '15px',
                                fontWeight: activeTab === 'usage' ? 700 : 500,
                                cursor: 'pointer',
                                transition: 'all 0.2s ease',
                            }}
                        >
                            API Usage
                        </button>
                        <button
                            type="button"
                            onClick={() => applyFilter({ tab: 'insights', page: undefined })}
                            style={{
                                padding: '12px 0',
                                background: 'transparent',
                                border: 'none',
                                borderBottom: activeTab === 'insights' ? '2px solid var(--tm-primary)' : '2px solid transparent',
                                color: activeTab === 'insights' ? 'var(--tm-primary)' : 'var(--app-text-dim)',
                                fontFamily: 'var(--font-headline)',
                                fontSize: '15px',
                                fontWeight: activeTab === 'insights' ? 700 : 500,
                                cursor: 'pointer',
                                transition: 'all 0.2s ease',
                            }}
                        >
                            Usage Insights
                        </button>
                    </div>
                </div>

                {/* ── Content ── */}
                {activeTab === 'insights' && insights ? (
                    insights.total_requests === 0 ? (
                        <div className="app-panel" style={{ padding: '48px 24px', textAlign: 'center' }}>
                            <div className="obs-empty-icon" style={{ margin: '0 auto 16px', background: 'rgba(225, 78, 246, 0.05)', width: '64px', height: '64px', borderRadius: '50%', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
                                <BarChart3 size={32} style={{ color: 'var(--tm-primary)' }} />
                            </div>
                            <div className="obs-title" style={{ fontSize: '20px' }}>No insights available yet</div>
                            <p className="obs-subtitle" style={{ maxWidth: '400px', margin: '8px auto 24px' }}>
                                Usage insights appear once requests begin flowing through TraceMem.
                            </p>
                            <Link href="/api-keys" className="app-btn app-btn-primary" style={{ display: 'inline-flex' }}>
                                Go to API Keys
                            </Link>
                        </div>
                    ) : (
                        <div style={{ display: 'flex', flexDirection: 'column', gap: '24px' }}>
                            {/* Top Metrics Cards */}
                            <div className="obs-insights-grid" style={{ gridTemplateColumns: 'repeat(auto-fit, minmax(150px, 1fr))', gap: '16px' }}>
                                <div className="obs-insight-card">
                                    <span className="obs-insight-label">Total Requests</span>
                                    <span className="obs-insight-value">{fmtNum(insights.total_requests)}</span>
                                </div>
                                <div className="obs-insight-card">
                                    <span className="obs-insight-label">Memory Writes</span>
                                    <span className="obs-insight-value">{fmtNum(insights.memory_writes)}</span>
                                </div>
                                <div className="obs-insight-card">
                                    <span className="obs-insight-label">Recall Hit Rate</span>
                                    <span className="obs-insight-value">{insights.recall_hit_rate}%</span>
                                </div>
                                <div className="obs-insight-card">
                                    <span className="obs-insight-label">Total Tokens</span>
                                    <span className="obs-insight-value">{fmtNum(insights.total_tokens)}</span>
                                </div>
                                <div className="obs-insight-card">
                                    <span className="obs-insight-label">Error Rate</span>
                                    <span className="obs-insight-value" style={{ color: insights.error_rate > 5 ? 'var(--app-error)' : 'inherit' }}>{insights.error_rate}%</span>
                                </div>
                            </div>

                            {/* Charts Row 1 */}
                            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(min(100%, 300px), 1fr))', gap: '24px' }}>
                                <div className="app-panel">
                                    <div className="obs-title" style={{ marginBottom: '16px' }}>Daily Requests</div>
                                    <MiniChart data={insights.request_trend} color="var(--tm-primary)" valueKey="total" labelKey="date" unit="reqs" />
                                </div>
                                
                                <div className="app-panel">
                                    <div className="obs-title" style={{ marginBottom: '16px' }}>Error Volume</div>
                                    <MiniChart data={insights.error_trend} color="var(--app-error)" valueKey="error_count" labelKey="date" unit="errors" />
                                </div>

                                <div className="app-panel">
                                    <div className="obs-title" style={{ marginBottom: '16px' }}>Average Latency</div>
                                    <MiniChart data={insights.latency_trend} color="var(--app-info)" valueKey="avg_latency" labelKey="date" unit="ms" />
                                </div>
                            </div>

                            {/* Tables Row */}
                            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(min(100%, 400px), 1fr))', gap: '24px' }}>
                                <div className="app-panel" style={{ overflow: 'hidden' }}>
                                    <div className="obs-title" style={{ marginBottom: '16px' }}>Top Endpoints</div>
                                    <div style={{ overflowX: 'auto', WebkitOverflowScrolling: 'touch' }}>
                                        <table style={{ width: '100%', minWidth: '300px', textAlign: 'left', borderCollapse: 'collapse', fontSize: '13px' }}>
                                            <thead>
                                                <tr style={{ color: 'var(--app-text-dim)', borderBottom: '1px solid var(--tm-border)' }}>
                                                    <th style={{ padding: '12px 8px', fontWeight: 'normal' }}>Endpoint</th>
                                                    <th style={{ padding: '12px 8px', fontWeight: 'normal', textAlign: 'right' }}>Requests</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {insights.top_endpoints.map(ep => (
                                                    <tr key={ep.endpoint} style={{ borderBottom: '1px solid var(--tm-border-light)' }}>
                                                        <td style={{ padding: '12px 8px', fontFamily: 'var(--font-mono)' }}>{ep.endpoint}</td>
                                                        <td style={{ padding: '12px 8px', color: 'var(--app-text)', textAlign: 'right' }}>{fmtNum(ep.total)}</td>
                                                    </tr>
                                                ))}
                                                {insights.top_endpoints.length === 0 && (
                                                    <tr><td colSpan={2} style={{ padding: '16px', textAlign: 'center', color: 'var(--app-text-dim)' }}>No data</td></tr>
                                                )}
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <div className="app-panel" style={{ overflow: 'hidden' }}>
                                    <div className="obs-title" style={{ marginBottom: '16px' }}>Key Usage</div>
                                    <div style={{ overflowX: 'auto', WebkitOverflowScrolling: 'touch' }}>
                                        <table style={{ width: '100%', minWidth: '400px', textAlign: 'left', borderCollapse: 'collapse', fontSize: '13px' }}>
                                            <thead>
                                                <tr style={{ color: 'var(--app-text-dim)', borderBottom: '1px solid var(--tm-border)' }}>
                                                    <th style={{ padding: '12px 8px', fontWeight: 'normal' }}>API Key</th>
                                                    <th style={{ padding: '12px 8px', fontWeight: 'normal' }}>Env</th>
                                                    <th style={{ padding: '12px 8px', fontWeight: 'normal', textAlign: 'right' }}>Requests</th>
                                                    <th style={{ padding: '12px 8px', fontWeight: 'normal', textAlign: 'right' }}>Avg Latency</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {insights.key_usage.map(ku => (
                                                    <tr key={ku.name} style={{ borderBottom: '1px solid var(--tm-border-light)' }}>
                                                        <td style={{ padding: '12px 8px', color: 'var(--app-text)' }}>{ku.name}</td>
                                                        <td style={{ padding: '12px 8px' }}><span className={`app-badge app-badge-${ku.environment}`}>{ku.environment}</span></td>
                                                        <td style={{ padding: '12px 8px', color: 'var(--app-text)', textAlign: 'right' }}>{fmtNum(ku.total_requests)}</td>
                                                        <td style={{ padding: '12px 8px', fontFamily: 'var(--font-mono)', textAlign: 'right' }}>{ku.avg_latency}ms</td>
                                                    </tr>
                                                ))}
                                                {insights.key_usage.length === 0 && (
                                                    <tr><td colSpan={4} style={{ padding: '16px', textAlign: 'center', color: 'var(--app-text-dim)' }}>No key usage found</td></tr>
                                                )}
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            
                            {/* Distributions */}
                            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(min(100%, 300px), 1fr))', gap: '24px' }}>
                                <div className="app-panel">
                                    <div className="obs-title" style={{ marginBottom: '16px' }}>Environment Split</div>
                                    <div style={{ display: 'flex', height: '24px', borderRadius: '4px', overflow: 'hidden', marginBottom: '12px' }}>
                                        <div style={{ width: `${(insights.split.test / Math.max(insights.total_requests, 1)) * 100}%`, background: 'var(--app-warning)' }} />
                                        <div style={{ width: `${(insights.split.live / Math.max(insights.total_requests, 1)) * 100}%`, background: 'var(--app-success)' }} />
                                    </div>
                                    <div style={{ display: 'flex', gap: '16px', fontSize: '13px' }}>
                                        <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}><div style={{ width: '8px', height: '8px', borderRadius: '50%', background: 'var(--app-warning)' }}/> Test ({fmtNum(insights.split.test)})</div>
                                        <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}><div style={{ width: '8px', height: '8px', borderRadius: '50%', background: 'var(--app-success)' }}/> Live ({fmtNum(insights.split.live)})</div>
                                    </div>
                                </div>
                                <div className="app-panel">
                                    <div className="obs-title" style={{ marginBottom: '16px' }}>Mode Distribution</div>
                                    <div style={{ display: 'flex', flexWrap: 'wrap', gap: '8px' }}>
                                        {insights.mode_distribution.map(m => (
                                            <div key={m.mode} style={{ padding: '8px 12px', background: 'var(--tm-surface-2)', border: '1px solid var(--tm-border)', borderRadius: '4px', fontSize: '13px' }}>
                                                <span style={{ color: 'var(--app-text-dim)' }}>{m.mode === 'semantic_only' ? 'Semantic' : 'AI First'}:</span>
                                                <strong style={{ marginLeft: '8px', color: 'var(--app-text)' }}>{fmtNum(m.total)}</strong>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            </div>
                            
                        </div>
                    )
                ) : activeTab === 'usage' ? (
                    <>
                        {/* ── Filters (API Usage) ── */}
                        <div className="app-panel" style={{ padding: '16px 20px', marginBottom: '24px' }}>
                            <div style={{ display: 'flex', flexWrap: 'wrap', gap: '12px', alignItems: 'center' }}>
                                
                                <select
                                    className="lg-month-select"
                                    style={{ flex: '1 1 140px' }}
                                    value={filters.environment ?? 'all'}
                                    onChange={(e) => applyFilter({ environment: e.target.value, page: undefined })}
                                >
                                    <option value="all">All Environments</option>
                                    <option value="test">Test</option>
                                    <option value="live">Live</option>
                                </select>

                                <select
                                    className="lg-month-select"
                                    style={{ flex: '1 1 140px' }}
                                    value={filters.status ?? 'all'}
                                    onChange={(e) => applyFilter({ status: e.target.value, page: undefined })}
                                >
                                    <option value="all">All Statuses</option>
                                    <option value="success">Success (2xx)</option>
                                    <option value="client_error">Client Error (4xx)</option>
                                    <option value="server_error">Server Error (5xx)</option>
                                </select>
                                
                                <select
                                    className="lg-month-select"
                                    style={{ flex: '1 1 140px' }}
                                    value={filters.mode ?? 'all'}
                                    onChange={(e) => applyFilter({ mode: e.target.value, page: undefined })}
                                >
                                    <option value="all">All Modes</option>
                                    <option value="semantic_only">Semantic Only</option>
                                    <option value="ai_first">AI First</option>
                                </select>

                                <select
                                    className="lg-month-select"
                                    style={{ flex: '1 1 140px' }}
                                    value={filters.period ?? 'all_time'}
                                    onChange={(e) => applyFilter({ period: e.target.value, month: undefined, page: undefined })}
                                >
                                    {PERIODS.map(p => (
                                        <option key={p.key} value={p.key}>{p.label}</option>
                                    ))}
                                </select>

                                <div className="lg-search-wrap" style={{ flex: '1 1 100%', minWidth: '200px', marginTop: '4px' }}>
                                    <Search size={14} />
                                    <input
                                        type="text"
                                        className="lg-search"
                                        placeholder="Search endpoint, IP, request ID…"
                                        value={search}
                                        onChange={(e) => setSearch(e.target.value)}
                                        style={{ width: '100%' }}
                                    />
                                </div>
                            </div>
                        </div>

                        {/* ── Log list ── */}
                        {logs.length === 0 ? (
                            <div className="app-panel" style={{ padding: '64px 24px', textAlign: 'center' }}>
                                <div className="obs-empty-icon" style={{ margin: '0 auto 16px', background: 'rgba(225, 78, 246, 0.05)', width: '64px', height: '64px', borderRadius: '50%', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
                                    <Shield size={32} style={{ color: 'var(--tm-primary)' }} />
                                </div>
                                <div className="obs-title" style={{ fontSize: '20px' }}>No API activity yet</div>
                                <p className="obs-subtitle" style={{ maxWidth: '400px', margin: '8px auto 24px' }}>
                                    {filters.search
                                        ? `No results for "${filters.search}". Try a different search or filter.`
                                        : 'Generate an API key and send your first request to begin tracking usage.'}
                                </p>
                                {!filters.search && (
                                    <Link href="/api-keys" className="app-btn app-btn-primary" style={{ display: 'inline-flex' }}>
                                        Go to API Keys
                                    </Link>
                                )}
                            </div>
                        ) : (
                            <div style={{ display: 'flex', flexDirection: 'column', gap: '32px' }}>
                                {grouped.map(({ label, items }) => (
                                    <div key={label} className="lg-month-group">
                                        <div className="lg-month-heading" style={{ marginBottom: '16px', display: 'flex', alignItems: 'center', borderBottom: '1px solid var(--tm-border)', paddingBottom: '8px' }}>
                                            <span style={{ fontFamily: 'var(--font-headline)', fontSize: '16px', color: 'var(--app-text)', fontWeight: 600 }}>{label}</span>
                                            <span className="lg-month-count" style={{ marginLeft: '12px', background: 'var(--tm-surface-2)', padding: '2px 8px', borderRadius: '12px', color: 'var(--app-text-dim)', fontSize: '11px', fontWeight: 600 }}>{fmtNum(items.length)} requests</span>
                                        </div>
                                        <div className="app-panel" style={{ padding: 0, border: '1px solid var(--tm-border)', borderRadius: '6px', overflow: 'hidden' }}>
                                            <div className="lg-hide-scroll" style={{ maxHeight: '400px', overflowY: 'auto' }}>
                                                {items.map((item) => (
                                                    <LogRow key={item.id} item={item} />
                                                ))}
                                            </div>
                                        </div>
                                    </div>
                                ))}
                                
                                {/* Pagination */}
                                {pagination && pagination.last_page > 1 && (
                                    <div className="lg-pagination" style={{ marginTop: '8px', paddingTop: '24px', borderTop: '1px solid var(--tm-border)' }}>
                                        <span className="lg-pagination-info">
                                            Page {pagination.current_page} of {pagination.last_page}
                                            {' '}({fmtNum(pagination.total)} total)
                                        </span>
                                        <div className="lg-pagination-btns">
                                            <button
                                                type="button"
                                                className="app-btn app-btn-ghost app-btn-sm"
                                                disabled={pagination.current_page <= 1}
                                                onClick={() => applyFilter({ page: pagination.current_page - 1 })}
                                            >
                                                <ChevronLeft size={13} />
                                                Prev
                                            </button>
                                            <button
                                                type="button"
                                                className="app-btn app-btn-ghost app-btn-sm"
                                                disabled={pagination.current_page >= pagination.last_page}
                                                onClick={() => applyFilter({ page: pagination.current_page + 1 })}
                                            >
                                                Next
                                                <ChevronRight size={13} />
                                            </button>
                                        </div>
                                    </div>
                                )}

                                {totalShown > 0 && pagination?.last_page === 1 && (
                                    <p className="lg-total-note" style={{ textAlign: 'center', marginTop: '8px', color: 'var(--app-text-dim)', fontSize: '13px' }}>
                                        Showing all {fmtNum(totalShown)} records for selected period.
                                    </p>
                                )}
                            </div>
                        )}
                    </>
                ) : null}

            </div>
        </>
    );
}
