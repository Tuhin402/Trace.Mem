type StatusType = 'operational' | 'degraded' | 'outage' | 'maintenance';

type Props = {
    status: StatusType;
    size?: 'sm' | 'md';
};

const labels: Record<StatusType, string> = {
    operational: 'Operational',
    degraded: 'Degraded',
    outage: 'Outage',
    maintenance: 'Maintenance',
};

export default function StatusBadge({ status, size = 'sm' }: Props) {
    return (
        <span className={`st-badge ${status}`}>
            <span className="st-badge-dot" />
            {labels[status]}
        </span>
    );
}

export type { StatusType };
