import { Head, Link, router, usePage } from '@inertiajs/react';
import { Fragment, useState } from 'react';
import {
    ArrowLeft,
    ChevronDown,
    ChevronLeft,
    ChevronRight,
    Activity,
    AlertTriangle,
    FileText,
    Crosshair,
} from 'lucide-react';
import { fmtNum } from '@/lib/fmt';

/* ── Types ──────────────────────────────────────────────── */
type MemoryMeta = Record<string, unknown>;

type CreationReason = {
    summary: string;
    source: string;
    flags: string[];
};

type RecallEligibility = {
    eligible: boolean;
    recall_score: number;
    reasons: string[];
};

type ConflictInfo = {
    has_conflict: boolean;
    conflict_score: number;
    superseded_by: number | null;
    details: string[];
};

type MemoryItem = {
    id: number;
    type: string;
    content: string;
    normalized_content: string | null;
    importance: number;
    confidence: number;
    decay_score: number;
    current_decay: number;
    recall_score: number;
    status: string;
    access_count: number;
    last_accessed_at: string | null;
    last_reinforced_at: string | null;
    archived_at: string | null;
    created_at: string;
    updated_at: string;
    metadata: MemoryMeta;
    creation_reason: CreationReason;
    recall_eligibility: RecallEligibility;
    conflict_info: ConflictInfo;
};

type SummaryStats = {
    total: number;
    active: number;
    stale: number;
    archived: number;
    avg_confidence: number;
    avg_importance: number;
};

type Pagination = {
    current_page: number;
    last_page: number;
    total: number;
    per_page: number;
};

type PageProps = {
    memories: MemoryItem[];
    summary: SummaryStats;
    pagination: Pagination;
    selectedFilters: { period?: string; type?: string; page?: string };
};

/* ── Filter definitions ─────────────────────────────────── */
const PERIODS = [
    { key: 'all',  label: 'All Time' },
    { key: '24h',  label: 'Last 24h' },
    { key: '7d',   label: 'Last 7 Days' },
    { key: '30d',  label: 'Last 30 Days' },
    { key: '90d',  label: 'Last 90 Days' },
] as const;

const TYPES = [
    { key: '',           label: 'All Types' },
    { key: 'preference', label: 'Preference' },
    { key: 'fact',       label: 'Fact' },
    { key: 'rule',       label: 'Rule' },
    { key: 'skill',      label: 'Skill' },
] as const;

/* ── Helpers ────────────────────────────────────────────── */
function scoreColor(v: number): string {
    if (v >= 0.7) return 'var(--app-success)';
    if (v >= 0.4) return 'var(--app-warning)';
    return 'var(--app-error)';
}

function scoreFillClass(v: number): string {
    if (v >= 0.7) return 'mi-score-bar-fill--success';
    if (v >= 0.4) return 'mi-score-bar-fill--warning';
    return 'mi-score-bar-fill--error';
}

function fmtDate(iso: string): string {
    const d = new Date(iso);
    return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}

function fmtRelative(iso: string | null): string {
    if (!iso) return 'Never';
    const d = new Date(iso);
    const diff = Date.now() - d.getTime();
    const mins = Math.floor(diff / 60000);
    if (mins < 1) return 'Just now';
    if (mins < 60) return `${mins}m ago`;
    const hrs = Math.floor(mins / 60);
    if (hrs < 24) return `${hrs}h ago`;
    const days = Math.floor(hrs / 24);
    if (days < 30) return `${days}d ago`;
    return fmtDate(iso);
}

/* ── Score bar component ────────────────────────────────── */
function ScoreBar({ label, value, max = 1 }: { label: string; value: number; max?: number }) {
    const pct = Math.min(100, (value / max) * 100);
    return (
        <div className="mi-score-row">
            <span className="mi-score-label">{label}</span>
            <div className="mi-score-bar">
                <div
                    className={`mi-score-bar-fill ${scoreFillClass(value)}`}
                    style={{ width: `${pct}%` }}
                />
            </div>
            <span className="mi-score-value">{value.toFixed(4)}</span>
        </div>
    );
}

/* ── Inline mini score bar ──────────────────────────────── */
function MiniScore({ value }: { value: number }) {
    const pct = Math.min(100, value * 100);
    return (
        <span className="mi-score-inline">
            <span className="mi-score-bar-mini">
                <span
                    className="mi-score-bar-mini-fill"
                    style={{ width: `${pct}%`, background: scoreColor(value) }}
                />
            </span>
            <span style={{ color: scoreColor(value), fontFamily: 'var(--font-mono)', fontSize: '11px', fontWeight: 600 }}>
                {value.toFixed(2)}
            </span>
        </span>
    );
}

/* ── Detail panel (used in both table & card) ───────────── */
function DetailPanel({ mem }: { mem: MemoryItem }) {
    const [showMeta, setShowMeta] = useState(false);

    return (
        <div className="mi-detail-panel">
            {/* Why Created */}
            <div className="mi-detail-section">
                <div className="mi-detail-section-title">
                    <FileText size={12} /> Why Created
                </div>
                <div className="mi-detail-text">{mem.creation_reason.summary}</div>
                {mem.creation_reason.flags.length > 0 && (
                    <div className="mi-detail-flags">
                        {mem.creation_reason.flags.map((flag, i) => (
                            <span key={i} className="mi-flag-badge mi-flag-badge--warning">{flag}</span>
                        ))}
                    </div>
                )}
                <div style={{ marginTop: '4px' }}>
                    <span className="mi-flag-badge mi-flag-badge--neutral">
                        Source: {mem.creation_reason.source}
                    </span>
                </div>
            </div>

            {/* Scoring Breakdown */}
            <div className="mi-detail-section">
                <div className="mi-detail-section-title">
                    <Activity size={12} /> Scoring Breakdown
                </div>
                <ScoreBar label="Importance" value={mem.importance} />
                <ScoreBar label="Confidence" value={mem.confidence} />
                <ScoreBar label="Decay" value={mem.current_decay} />
                <ScoreBar label="Recall" value={mem.recall_score} />
                <div style={{ display: 'flex', gap: '12px', marginTop: '4px', flexWrap: 'wrap' }}>
                    <span className="mi-flag-badge mi-flag-badge--info">
                        Access count: {mem.access_count}
                    </span>
                    <span className="mi-flag-badge mi-flag-badge--neutral">
                        Last accessed: {fmtRelative(mem.last_accessed_at)}
                    </span>
                    {mem.last_reinforced_at && (
                        <span className="mi-flag-badge mi-flag-badge--neutral">
                            Reinforced: {fmtRelative(mem.last_reinforced_at)}
                        </span>
                    )}
                </div>
            </div>

            {/* Recall Eligibility */}
            <div className="mi-detail-section">
                <div className="mi-detail-section-title">
                    <Crosshair size={12} /> Recall Eligibility
                </div>
                <div>
                    <span className={`mi-recall-badge ${mem.recall_eligibility.eligible ? 'mi-recall-badge--eligible' : 'mi-recall-badge--ineligible'}`}>
                        {mem.recall_eligibility.eligible ? '✓ Eligible' : '✗ Ineligible'}
                    </span>
                </div>
                <div className="mi-recall-reasons">
                    {mem.recall_eligibility.reasons.map((r, i) => (
                        <div key={i} className="mi-recall-reason">{r}</div>
                    ))}
                </div>
            </div>

            {/* Conflict Info */}
            <div className="mi-detail-section">
                <div className="mi-detail-section-title">
                    <AlertTriangle size={12} /> Conflicts
                </div>
                <div>
                    <span className={`mi-conflict-status ${mem.conflict_info.has_conflict ? 'mi-conflict-status--conflict' : 'mi-conflict-status--clean'}`}>
                        {mem.conflict_info.has_conflict ? '⚠ Conflict Detected' : '✓ No Conflicts'}
                    </span>
                </div>
                <div className="mi-conflict-details">
                    {mem.conflict_info.details.map((d, i) => (
                        <div key={i} className="mi-conflict-detail">{d}</div>
                    ))}
                </div>

                {/* Metadata toggle */}
                <button
                    type="button"
                    className="mi-metadata-toggle"
                    onClick={(e) => { e.stopPropagation(); setShowMeta(v => !v); }}
                >
                    {showMeta ? 'Hide' : 'Show'} Raw Metadata
                </button>
                {showMeta && (
                    <div className="mi-metadata-block">
                        <pre>{JSON.stringify(mem.metadata, null, 2)}</pre>
                    </div>
                )}
            </div>
        </div>
    );
}

/* ── Mobile card component ──────────────────────────────── */
function MemoryCard({ mem }: { mem: MemoryItem }) {
    const [expanded, setExpanded] = useState(false);

    return (
        <div className="mi-card" onClick={() => setExpanded(v => !v)}>
            <div className="mi-card-head">
                <span className="mi-type-badge">{mem.type}</span>
                <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                    <span className="mi-status-dot" data-status={mem.status}>{mem.status}</span>
                    <span className={`mi-expand-icon ${expanded ? 'mi-expand-icon--open' : ''}`}>
                        <ChevronDown size={12} />
                    </span>
                </div>
            </div>
            <div className="mi-card-content">{mem.content}</div>
            <div className="mi-card-scores">
                <div className="mi-card-score-item">
                    <span className="mi-card-score-label">Confidence</span>
                    <span className="mi-card-score-value" style={{ color: scoreColor(mem.confidence) }}>
                        {mem.confidence.toFixed(2)}
                    </span>
                </div>
                <div className="mi-card-score-item">
                    <span className="mi-card-score-label">Importance</span>
                    <span className="mi-card-score-value" style={{ color: scoreColor(mem.importance) }}>
                        {mem.importance.toFixed(2)}
                    </span>
                </div>
                <div className="mi-card-score-item">
                    <span className="mi-card-score-label">Recall</span>
                    <span className="mi-card-score-value" style={{ color: scoreColor(mem.recall_score) }}>
                        {mem.recall_score.toFixed(2)}
                    </span>
                </div>
            </div>
            <div className="mi-card-footer">
                <span className="mi-date-cell">{fmtDate(mem.created_at)}</span>
                <span className={`mi-recall-badge ${mem.recall_eligibility.eligible ? 'mi-recall-badge--eligible' : 'mi-recall-badge--ineligible'}`}
                      style={{ margin: 0, fontSize: '9px', padding: '2px 8px' }}>
                    {mem.recall_eligibility.eligible ? 'Recallable' : 'Skipped'}
                </span>
            </div>
            {expanded && (
                <div className="mi-card-detail-panel" onClick={(e) => e.stopPropagation()}>
                    <DetailPanel mem={mem} />
                </div>
            )}
        </div>
    );
}

/* ── Main Page ──────────────────────────────────────────── */
export default function MemoryInspector() {
    const { props } = usePage<PageProps>();
    const { memories, summary, pagination, selectedFilters } = props;

    const [expandedId, setExpandedId] = useState<number | null>(null);
    const activePeriod = selectedFilters?.period ?? 'all';
    const activeType = selectedFilters?.type ?? '';

    function navigate(params: Record<string, string>) {
        const merged = {
            period: activePeriod,
            type: activeType,
            ...params,
        };
        // Remove empty values
        const clean: Record<string, string> = {};
        for (const [k, v] of Object.entries(merged)) {
            if (v) clean[k] = v;
        }
        router.get('/memory-inspector', clean, {
            preserveScroll: true,
            preserveState: true,
            replace: true,
        });
    }

    function toggleExpand(id: number) {
        setExpandedId(prev => prev === id ? null : id);
    }

    return (
        <>
            <Head title="Memory Inspector" />

            <div className="app-page">

                {/* ── Header ── */}
                <div className="app-page-header">
                    <div>
                        <div style={{ marginBottom: '12px' }}>
                            <Link href="/dashboard" className="mi-back-link">
                                <ArrowLeft size={12} />
                                Back to Dashboard
                            </Link>
                        </div>
                        <h1 className="app-page-title">Memory Inspector</h1>
                        <p className="app-page-subtitle">
                            Debug console - inspect stored memories, scoring, recall eligibility, and conflicts.
                        </p>
                    </div>
                </div>

                {/* ── Summary Stats ── */}
                <div className="mi-stats-grid">
                    <div className="mi-stat-card">
                        <span className="mi-stat-label">Total Memories</span>
                        <span className="mi-stat-value">{fmtNum(summary.total)}</span>
                        <span className="mi-stat-sub">Across all time</span>
                    </div>
                    <div className="mi-stat-card">
                        <span className="mi-stat-label">Active</span>
                        <span className="mi-stat-value" style={{ color: 'var(--app-success)' }}>{fmtNum(summary.active)}</span>
                        <span className="mi-stat-sub">Eligible for recall</span>
                    </div>
                    <div className="mi-stat-card">
                        <span className="mi-stat-label">Stale / Archived</span>
                        <span className="mi-stat-value" style={{ color: 'var(--app-warning)' }}>
                            {fmtNum(summary.stale + summary.archived)}
                        </span>
                        <span className="mi-stat-sub">{fmtNum(summary.stale)} stale · {fmtNum(summary.archived)} archived</span>
                    </div>
                    <div className="mi-stat-card">
                        <span className="mi-stat-label">Avg Confidence</span>
                        <span className="mi-stat-value">{(summary.avg_confidence ?? 0).toFixed(2)}</span>
                        <span className="mi-stat-sub">Importance avg: {(summary.avg_importance ?? 0).toFixed(2)}</span>
                    </div>
                </div>

                {/* ── Filters ── */}
                <div className="mi-filters">
                    <div className="app-filter-bar">
                        {PERIODS.map((p) => (
                            <button
                                key={p.key}
                                type="button"
                                className={`app-filter-btn${activePeriod === p.key ? ' app-filter-btn--active' : ''}`}
                                onClick={() => navigate({ period: p.key, page: '1' })}
                            >
                                {p.label}
                            </button>
                        ))}
                    </div>
                    <select
                        className="mi-type-select"
                        value={activeType}
                        onChange={(e) => navigate({ type: e.target.value, page: '1' })}
                    >
                        {TYPES.map((t) => (
                            <option key={t.key} value={t.key}>{t.label}</option>
                        ))}
                    </select>
                    <span className="mi-results-count">
                        {pagination.total} {pagination.total === 1 ? 'memory' : 'memories'} found
                    </span>
                </div>

                {/* ── Desktop Table ── */}
                <div className="mi-desktop-only">
                    {memories.length === 0 ? (
                        <div className="app-empty-state">
                            <div className="app-empty-state-title">No memories found</div>
                            <p className="app-empty-state-desc">
                                {activePeriod !== 'all' || activeType
                                    ? 'Try adjusting your filters to see more results.'
                                    : 'Use the /remember endpoint to store your first memory.'}
                            </p>
                        </div>
                    ) : (
                        <div className="mi-table-wrap">
                            <table className="mi-table">
                                <thead>
                                    <tr>
                                        <th style={{ width: '24px' }}></th>
                                        <th>Type</th>
                                        <th>Content</th>
                                        <th>Confidence</th>
                                        <th>Importance</th>
                                        <th>Decay</th>
                                        <th>Recall</th>
                                        <th>Status</th>
                                        <th>Created</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {memories.map((mem) => (
                                        <Fragment key={mem.id}>
                                            <tr
                                                className={expandedId === mem.id ? 'mi-row-expanded' : ''}
                                                onClick={() => toggleExpand(mem.id)}
                                            >
                                                <td>
                                                    <span className={`mi-expand-icon ${expandedId === mem.id ? 'mi-expand-icon--open' : ''}`}>
                                                        <ChevronDown size={11} />
                                                    </span>
                                                </td>
                                                <td><span className="mi-type-badge">{mem.type}</span></td>
                                                <td><div className="mi-content-cell">{mem.content}</div></td>
                                                <td><MiniScore value={mem.confidence} /></td>
                                                <td><MiniScore value={mem.importance} /></td>
                                                <td><MiniScore value={mem.current_decay} /></td>
                                                <td><MiniScore value={mem.recall_score} /></td>
                                                <td>
                                                    <span className="mi-status-dot" data-status={mem.status}>
                                                        {mem.status}
                                                    </span>
                                                </td>
                                                <td className="mi-date-cell">{fmtDate(mem.created_at)}</td>
                                            </tr>
                                            {expandedId === mem.id && (
                                                <tr className="mi-detail-row">
                                                    <td colSpan={9}>
                                                        <DetailPanel mem={mem} />
                                                    </td>
                                                </tr>
                                            )}
                                        </Fragment>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </div>

                {/* ── Mobile Card List ── */}
                <div className="mi-mobile-only">
                    {memories.length === 0 ? (
                        <div className="app-empty-state">
                            <div className="app-empty-state-title">No memories found</div>
                            <p className="app-empty-state-desc">
                                {activePeriod !== 'all' || activeType
                                    ? 'Try adjusting your filters to see more results.'
                                    : 'Use the /remember endpoint to store your first memory.'}
                            </p>
                        </div>
                    ) : (
                        <div className="mi-card-list">
                            {memories.map((mem) => (
                                <MemoryCard key={mem.id} mem={mem} />
                            ))}
                        </div>
                    )}
                </div>

                {/* ── Pagination ── */}
                {pagination.last_page > 1 && (
                    <div className="mi-pagination">
                        <button
                            type="button"
                            className="mi-page-btn"
                            disabled={pagination.current_page <= 1}
                            onClick={() => navigate({ page: String(pagination.current_page - 1) })}
                        >
                            <ChevronLeft size={12} /> Prev
                        </button>

                        <span className="mi-page-info">
                            Page {pagination.current_page} of {pagination.last_page}
                        </span>

                        <button
                            type="button"
                            className="mi-page-btn"
                            disabled={pagination.current_page >= pagination.last_page}
                            onClick={() => navigate({ page: String(pagination.current_page + 1) })}
                        >
                            Next <ChevronRight size={12} />
                        </button>
                    </div>
                )}

            </div>
        </>
    );
}
