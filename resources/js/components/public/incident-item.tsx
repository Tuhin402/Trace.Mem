import { Check, AlertTriangle, Search } from 'lucide-react';

type Severity = 'critical' | 'major' | 'minor';
type IncidentStatus = 'resolved' | 'investigating' | 'active';

type Props = {
    title: string;
    timestamp: string;
    severity: Severity;
    summary: string;
    status: IncidentStatus;
};

export default function IncidentItem({ title, timestamp, severity, summary, status }: Props) {
    return (
        <div className="st-incident">
            <div className={`st-incident-dot ${status}`} aria-hidden="true" />
            <div className="st-incident-head">
                <h3 className="st-incident-title">{title}</h3>
                <span className={`st-incident-severity ${severity}`}>{severity}</span>
            </div>
            <div className="st-incident-time">{timestamp}</div>
            <p className="st-incident-summary">{summary}</p>
            <span className={`st-incident-status-tag ${status}`}>
                {status === 'resolved' && <Check size={12} />}
                {status === 'investigating' && <Search size={12} />}
                {status === 'active' && <AlertTriangle size={12} />}
                {status}
            </span>
        </div>
    );
}

export type { Severity, IncidentStatus };
