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
    };
};

const PERIODS = [
    { key: 'all_time',   label: 'All Time' },
    { key: 'this_month', label: 'This Month' },
    { key: 'last_month', label: 'Last Month' },
    { key: 'year_to_date', label: 'Year to Date' },
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

/* ── Log Row ────────────────────────────────────────────── */
function LogRow({ item }: { item: LogItem }) {
    const ts    = new Date(item.requested_at);
    const time  = ts.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false });
    const env   = item.apiKey?.environment ?? 'test';
    const isSandbox = item.is_sandbox ?? (env === 'test');

    return (
        <div className="lg-row">
            {/* Status dot */}
            <div
                className="lg-status-dot"
                style={{
                    background: item.status_code >= 500
                        ? 'var(--app-error)'
                        : item.status_code >= 400
                        ? 'var(--app-warning)'
                        : 'var(--app-success)',
                }}
                aria-hidden="true"
            />

            {/* Main content */}
            <div className="lg-row-body">
                {/* Top line */}
                <div className="lg-row-top">
                    <span
                        className="lg-method"
                        style={{ color: methodColor(item.method) }}
                    >
                        {item.method}
                    </span>
                    <span className="lg-endpoint">{item.endpoint}</span>
                    <span className={`lg-status ${statusClass(item.status_code)}`}>
                        {item.status_code}
                    </span>
                    {item.latency_ms !== null && (
                        <span className="lg-latency">
                            <Clock3 size={10} />
                            {fmtLatency(item.latency_ms)}
                        </span>
                    )}
                </div>

                {/* Bottom meta line */}
                <div className="lg-row-meta">
                    <span className="lg-time">{time}</span>

                    {item.apiKey && (
                        <span className={`app-badge app-badge-${item.apiKey.environment} lg-env-badge`}>
                            {isSandbox ? '⬡ sandbox' : item.apiKey.environment}
                        </span>
                    )}

                    {item.request_host && (
                        <span className="lg-meta-chip">
                            <Globe size={10} />
                            {item.request_host}
                        </span>
                    )}

                    {item.ip_address && (
                        <span className="lg-meta-chip">
                            <Wifi size={10} />
                            {item.ip_address}
                        </span>
                    )}

                    {item.request_origin && item.request_origin !== item.request_host && (
                        <span className="lg-meta-chip" style={{ opacity: 0.6 }}>
                            origin: {item.request_origin}
                        </span>
                    )}

                    {item.apiKey && (
                        <span className="lg-meta-chip">
                            <Shield size={10} />
                            {item.apiKey.name}
                        </span>
                    )}
                </div>
            </div>
        </div>
    );
}

/* ── Main page ──────────────────────────────────────────── */
export default function Logs() {
    const { props }  = usePage<PageProps>();
    const logs       = props.logs ?? [];
    const pagination = props.pagination;
    const months     = props.availableMonths ?? [];
    const filters    = props.selectedFilters ?? {};

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
            <Head title="API Logs" />

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
                            <span>API Logs</span>
                        </div>
                        <h1 className="app-page-title" style={{ marginTop: '8px' }}>API Usage Logs</h1>
                        <p className="app-page-subtitle">
                            Full request history across all your API keys.
                            {pagination && (
                                <span style={{ marginLeft: '8px', color: 'var(--app-text-subtle)' }}>
                                    {fmtNum(pagination.total)} total records
                                </span>
                            )}
                        </p>
                    </div>
                    <Link href="/api-keys" className="app-btn app-btn-secondary">
                        Manage Keys
                    </Link>
                </div>

                {/* ── Filters ── */}
                <div className="app-panel" style={{ padding: '16px 20px' }}>
                    <div className="lg-filter-row">
                        {/* Period pills */}
                        <div className="app-filter-bar">
                            {PERIODS.map((p) => (
                                <button
                                    key={p.key}
                                    type="button"
                                    className={`app-filter-btn${(filters.period ?? 'all_time') === p.key ? ' app-filter-btn--active' : ''}`}
                                    onClick={() => applyFilter({ period: p.key, month: undefined, page: undefined })}
                                >
                                    {p.label}
                                </button>
                            ))}
                        </div>

                        {/* Month select */}
                        {months.length > 0 && (
                            <div className="lg-month-select-wrap">
                                <Filter size={12} style={{ color: 'var(--app-text-dim)' }} />
                                <select
                                    className="lg-month-select"
                                    value={filters.month ?? ''}
                                    onChange={(e) => applyFilter({ month: e.target.value || undefined, period: undefined, page: undefined })}
                                >
                                    <option value="">All months</option>
                                    {months.map((m) => {
                                        const [y, mo] = m.split('-').map(Number);
                                        const label = new Date(y, mo - 1, 1).toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
                                        return <option key={m} value={m}>{label}</option>;
                                    })}
                                </select>
                            </div>
                        )}

                        {/* Search */}
                        <div className="lg-search-wrap">
                            <Search size={12} />
                            <input
                                type="text"
                                className="lg-search"
                                placeholder="Search endpoint, IP, host, status…"
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                            />
                        </div>
                    </div>
                </div>

                {/* ── Log list ── */}
                {logs.length === 0 ? (
                    <div className="app-panel">
                        <div className="app-empty-state">
                            <Activity size={32} style={{ color: 'var(--app-text-subtle)', margin: '0 auto 16px' }} />
                            <div className="app-empty-state-title">No logs found</div>
                            <p className="app-empty-state-desc">
                                {filters.search
                                    ? `No results for "${filters.search}". Try a different search.`
                                    : 'No API requests recorded yet. Start calling the TraceMem API to see logs here.'}
                            </p>
                        </div>
                    </div>
                ) : (
                    <>
                        {grouped.map(({ label, items }) => (
                            <div key={label} className="lg-month-group">
                                <div className="lg-month-heading">
                                    <span>{label}</span>
                                    <span className="lg-month-count">{fmtNum(items.length)} requests</span>
                                </div>
                                <div className="app-panel lg-log-panel">
                                    {items.map((item) => (
                                        <LogRow key={item.id} item={item} />
                                    ))}
                                </div>
                            </div>
                        ))}

                        {/* Pagination */}
                        {pagination && pagination.last_page > 1 && (
                            <div className="lg-pagination">
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
                            <p className="lg-total-note">
                                Showing all {fmtNum(totalShown)} records for selected period.
                            </p>
                        )}
                    </>
                )}

            </div>
        </>
    );
}
