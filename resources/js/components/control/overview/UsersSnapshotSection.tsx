import { Users, UserPlus, ShieldAlert, CheckCircle2 } from 'lucide-react';
import OverviewCard from './OverviewCard';

export default function UsersSnapshotSection() {
    const users = [
        { id: 1, name: 'Alice Freeman', email: 'alice@example.com', status: 'verified', role: 'owner', time: '10 mins ago' },
        { id: 2, name: 'Bob Jenkins', email: 'bob@acmecorp.com', status: 'pending', role: 'member', time: '1 hour ago' },
        { id: 3, name: 'Charlie Davis', email: 'charlie@startup.io', status: 'verified', role: 'admin', time: '3 hours ago' },
        { id: 4, name: 'Diana Prince', email: 'diana@themyscira.gov', status: 'verified', role: 'owner', time: '5 hours ago' },
    ];

    return (
        <OverviewCard
            id="overview-users"
            title="Recent Users"
            icon={<Users className="h-5 w-5" />}
            viewAllHref="/platform/users"
        >
            <div className="space-y-4">
                {users.map(user => (
                    <div key={user.id} className="flex items-center justify-between p-3 border border-almost-black/10 hover:border-primary/30 transition-colors group">
                        <div className="flex items-center gap-3 overflow-hidden">
                            <div className="h-10 w-10 bg-primary/5 border border-primary/20 flex items-center justify-center shrink-0 text-primary font-bold font-heading">
                                {user.name.charAt(0)}
                            </div>
                            <div className="flex flex-col overflow-hidden">
                                <span className="text-sm font-bold text-on-background truncate group-hover:text-primary transition-colors">
                                    {user.name}
                                </span>
                                <span className="text-xs text-on-background/60 truncate">
                                    {user.email}
                                </span>
                            </div>
                        </div>
                        <div className="flex flex-col items-end shrink-0 ml-4">
                            <div className="flex items-center gap-1.5">
                                {user.status === 'verified' ? (
                                    <CheckCircle2 className="h-3.5 w-3.5 text-green-500" />
                                ) : (
                                    <ShieldAlert className="h-3.5 w-3.5 text-amber-500" />
                                )}
                                <span className="text-xs font-mono text-on-background/70 uppercase">
                                    {user.role}
                                </span>
                            </div>
                            <span className="text-xs font-mono text-on-background/40 mt-1">
                                {user.time}
                            </span>
                        </div>
                    </div>
                ))}
            </div>
            <div className="mt-6 pt-4 border-t border-almost-black/10 flex justify-between items-center text-xs font-mono text-on-background/50">
                <div className="flex items-center gap-1.5">
                    <UserPlus className="h-4 w-4" /> 14 new today
                </div>
                <div>3,402 total active</div>
            </div>
        </OverviewCard>
    );
}
