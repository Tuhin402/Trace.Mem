import { Head } from '@inertiajs/react';
import { Helmet } from 'react-helmet-async';
import { useDomains } from '@/lib/domains';
import {
    Server,
    Brain,
    Layers,
    Clock,
    Database,
    Sparkles,
    Mail,
    CreditCard,
    CheckCircle2,
    Shield,
} from 'lucide-react';

import HealthCard from '@/components/public/health-card';
import IncidentItem from '@/components/public/incident-item';
import StatusBadge from '@/components/public/status-badge';

/* ── Static mock data ──────────────────────────────────────── */
const overallStatus = 'operational' as const;

const services = [
    {
        name: 'API Service',
        status: 'operational' as const,
        description: 'Core REST API endpoints responding normally with sub-50ms latency.',
        lastChecked: '2 min ago',
        icon: <Server size={18} />,
    },
    {
        name: 'Memory Engine',
        status: 'operational' as const,
        description: 'Semantic extraction, storage, and retrieval pipeline fully operational.',
        lastChecked: '1 min ago',
        icon: <Brain size={18} />,
    },
    {
        name: 'Context Assembly',
        status: 'operational' as const,
        description: 'Prompt-ready context block generation running within normal parameters.',
        lastChecked: '3 min ago',
        icon: <Layers size={18} />,
    },
    {
        name: 'Queue System',
        status: 'operational' as const,
        description: 'Background job processing and async memory writes healthy.',
        lastChecked: '1 min ago',
        icon: <Clock size={18} />,
    },
    {
        name: 'Database',
        status: 'operational' as const,
        description: 'Primary and replica databases responding. Replication lag nominal.',
        lastChecked: '30 sec ago',
        icon: <Database size={18} />,
    },
    {
        name: 'OpenAI Integration',
        status: 'operational' as const,
        description: 'External AI model calls completing within expected latency windows.',
        lastChecked: '2 min ago',
        icon: <Sparkles size={18} />,
    },
    {
        name: 'Mail Service',
        status: 'operational' as const,
        description: 'Transactional email delivery operating normally.',
        lastChecked: '5 min ago',
        icon: <Mail size={18} />,
    },
    {
        name: 'Stripe Billing',
        status: 'operational' as const,
        description: 'Payment processing and webhook ingestion functioning correctly.',
        lastChecked: '4 min ago',
        icon: <CreditCard size={18} />,
    },
];

const incidents = [
    {
        title: 'Elevated API Latency',
        timestamp: 'May 28, 2026 — 14:32 UTC',
        severity: 'minor' as const,
        summary:
            'Brief period of elevated response times on /recall endpoint due to a sudden spike in concurrent requests. Auto-scaling resolved the issue within 8 minutes. No data loss or errors.',
        status: 'resolved' as const,
    },
    {
        title: 'Scheduled Database Maintenance',
        timestamp: 'May 15, 2026 — 03:00 UTC',
        severity: 'minor' as const,
        summary:
            'Planned PostgreSQL version upgrade and index optimization. Memory reads were temporarily routed to replica. Write operations were queued and replayed after maintenance. Total window: 12 minutes.',
        status: 'resolved' as const,
    },
    {
        title: 'OpenAI Upstream Degradation',
        timestamp: 'Apr 22, 2026 — 09:15 UTC',
        severity: 'major' as const,
        summary:
            'OpenAI API experienced intermittent 503 errors affecting AI-first memory extraction. Semantic-only mode remained fully operational. Switched to fallback provider for 45 minutes until upstream recovered.',
        status: 'resolved' as const,
    },
];

/* ── Uptime bar data (90 days, all healthy for mock) ────────── */
function generateUptimeBars() {
    const bars = [];
    for (let i = 0; i < 90; i++) {
        // Make most bars full, with a couple of partial days for realism
        let status: 'full' | 'partial' | 'down' = 'full';
        if (i === 62) status = 'partial'; // corresponds to ~May 28 incident
        if (i === 49) status = 'partial'; // corresponds to ~May 15 maintenance
        bars.push({ day: 90 - i, status, height: status === 'full' ? 100 : status === 'partial' ? 65 : 20 });
    }
    return bars;
}

const uptimeBars = generateUptimeBars();

/* ── Component ─────────────────────────────────────────────── */
export default function Status() {
    const { siteUrl } = useDomains();
    return (
        <>
            <Helmet>
                <meta
                    name="description"
                    content="TraceMem system status — real-time service health, uptime, and incident history for the TraceMem memory infrastructure."
                />
                <meta property="og:title" content="TraceMem — System Status" />
                <meta
                    property="og:description"
                    content="Live status of TraceMem services including API, Memory Engine, Context Assembly, and more."
                />
                <meta property="og:type" content="website" />
                <meta property="og:url" content={`${siteUrl}/status`} />
                <link rel="canonical" href={`${siteUrl}/status`} />
            </Helmet>

            <Head title="System Status | TraceMem" />

            {/* ══ 1. HERO ═══════════════════════════════════════════ */}
            <section className="status-hero" aria-label="Status hero">
                <div className="status-hero-inner">
                    <span className="status-hero-eyebrow">Service Health</span>
                    <h1 className="status-hero-title">System Status</h1>
                    <p className="status-hero-sub">
                        Real-time health and uptime for all TraceMem services.
                        Transparency is a core part of our infrastructure promise.
                    </p>
                    <div className="status-overall">
                        <span className={`status-overall-dot ${overallStatus}`} />
                        <span className="status-overall-text">All Systems Operational</span>
                    </div>
                </div>
            </section>

            {/* ══ 2. HEALTH OVERVIEW ════════════════════════════════ */}
            <section className="st-section st-health-section" aria-label="Service health">
                <div className="st-section-inner">
                    <div className="st-section-head">
                        <span className="st-section-tag">Health Overview</span>
                        <h2 className="st-section-title">Service Health</h2>
                        <p className="st-section-desc">
                            Individual status of every TraceMem service and external dependency.
                        </p>
                    </div>
                    <div className="st-health-grid">
                        {services.map((svc) => (
                            <HealthCard key={svc.name} {...svc} />
                        ))}
                    </div>
                </div>
            </section>

            {/* ══ 3. UPTIME ═════════════════════════════════════════ */}
            <section className="st-section st-uptime-section" aria-label="Uptime">
                <div className="st-section-inner">
                    <div className="st-section-head">
                        <span className="st-section-tag">Reliability</span>
                        <h2 className="st-section-title">Uptime</h2>
                        <p className="st-section-desc">
                            Overall service availability across the last 90 days.
                        </p>
                    </div>

                    <div className="st-uptime-layout">
                        <div className="st-uptime-stat">
                            <div className="st-uptime-num">99.97%</div>
                            <div className="st-uptime-label">90-Day Uptime</div>
                            <p className="st-uptime-desc">
                                TraceMem maintains enterprise-grade availability across all
                                critical services, with automated failover and self-healing
                                infrastructure.
                            </p>
                        </div>

                        <div className="st-uptime-chart">
                            <div className="st-uptime-chart-label">
                                Daily Availability — Last 90 Days
                            </div>
                            <div className="st-uptime-bars" aria-label="Uptime chart">
                                {uptimeBars.map((bar, i) => (
                                    <div
                                        key={i}
                                        className={`st-uptime-bar ${bar.status}`}
                                        style={{ height: `${bar.height}%` }}
                                        title={`Day ${bar.day}: ${bar.status === 'full' ? '100%' : bar.status === 'partial' ? '~99.5%' : 'Outage'}`}
                                    />
                                ))}
                            </div>
                            <div className="st-uptime-chart-footer">
                                <span>90 days ago</span>
                                <span>Today</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {/* ══ 4. INCIDENT LOG ═══════════════════════════════════ */}
            <section className="st-section st-incidents-section" aria-label="Incident history">
                <div className="st-section-inner">
                    <div className="st-section-head">
                        <span className="st-section-tag">Incident History</span>
                        <h2 className="st-section-title">Past Incidents</h2>
                        <p className="st-section-desc">
                            A timeline of resolved incidents and maintenance windows.
                        </p>
                    </div>

                    <div className="st-incidents-timeline">
                        {incidents.map((inc, i) => (
                            <IncidentItem key={i} {...inc} />
                        ))}
                    </div>
                </div>
            </section>

            {/* ══ 5. CURRENT STATUS BANNER ═════════════════════════ */}
            <section className="st-section st-current-section" aria-label="Current status">
                <div className="st-section-inner">
                    <div className="st-current-banner">
                        <div className="st-current-icon operational">
                            <CheckCircle2 size={24} />
                        </div>
                        <h2 className="st-current-title">All systems operational</h2>
                        <p className="st-current-desc">
                            All TraceMem services are running normally. No active incidents
                            or scheduled maintenance windows at this time.
                        </p>
                        <span className="st-current-time">
                            Last updated: {new Date().toLocaleString('en-US', {
                                month: 'short',
                                day: 'numeric',
                                year: 'numeric',
                                hour: '2-digit',
                                minute: '2-digit',
                                timeZoneName: 'short',
                            })}
                        </span>
                    </div>
                </div>
            </section>
        </>
    );
}
