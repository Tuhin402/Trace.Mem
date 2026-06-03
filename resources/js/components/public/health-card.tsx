import { type ReactNode } from 'react';
import StatusBadge, { type StatusType } from './status-badge';

type Props = {
    name: string;
    status: StatusType;
    description: string;
    lastChecked: string;
    icon: ReactNode;
};

export default function HealthCard({ name, status, description, lastChecked, icon }: Props) {
    return (
        <div className="st-health-card">
            <div className="st-health-card-head">
                <div className="st-health-card-icon" aria-hidden="true">
                    {icon}
                </div>
                <StatusBadge status={status} />
            </div>
            <div className="st-health-card-name">{name}</div>
            <div className="st-health-card-desc">{description}</div>
            <div className="st-health-card-footer">
                <span className="st-health-card-time">Last checked: {lastChecked}</span>
            </div>
        </div>
    );
}
