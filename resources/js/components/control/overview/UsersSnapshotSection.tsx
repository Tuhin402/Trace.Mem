import { Users, Ghost } from 'lucide-react';
import OverviewCard from './OverviewCard';
import { Link } from '@inertiajs/react';

export default function UsersSnapshotSection({ data }: { data: any }) {
    if (data?.error) {
        return (
            <OverviewCard id="overview-users-snapshot" title="Users Snapshot" icon={<Users className="h-5 w-5" />}>
                <div className="flex flex-col items-center justify-center py-8 text-on-background/50">
                    <span className="text-sm font-bold text-destructive uppercase">{data.error}</span>
                </div>
            </OverviewCard>
        );
    }

    const users = Array.isArray(data) ? data : [];

    return (
        <OverviewCard
            id="overview-users-snapshot"
            title="Users Snapshot"
            icon={<Users className="h-5 w-5" />}
            viewAllHref="/operations/users"
        >
            {users.length === 0 ? (
                <div className="flex flex-col items-center justify-center py-10 opacity-60">
                    <Ghost className="h-8 w-8 mb-3 text-primary/40" />
                    <span className="text-sm font-bold uppercase tracking-wider">Empty Platform</span>
                    <span className="text-xs text-center mt-1">No users have registered yet.</span>
                </div>
            ) : (
                <table className="w-full text-left border-collapse">
                    <thead>
                        <tr className="border-b border-almost-black/10 text-[10px] uppercase font-bold text-on-background/50">
                            <th className="pb-3 font-medium">User</th>
                            <th className="pb-3 font-medium hidden sm:table-cell">Status</th>
                            <th className="pb-3 font-medium text-right">Joined</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-almost-black/5">
                        {users.map(user => (
                            <tr key={user.id} className="group hover:bg-almost-black/5 transition-colors">
                                <td className="py-3">
                                    <div className="flex items-center gap-3">
                                        <div className="h-8 w-8 bg-primary/10 text-primary flex items-center justify-center text-xs font-bold font-heading shrink-0">
                                            {user.name.charAt(0)}
                                        </div>
                                        <div className="flex flex-col">
                                            <Link href={`/operations/users/${user.id}`} className="text-sm font-bold text-primary group-hover:underline cursor-pointer">{user.name}</Link>
                                            <span className="text-xs text-on-background/60">{user.email}</span>
                                        </div>
                                    </div>
                                </td>
                                <td className="py-3 hidden sm:table-cell">
                                    <span className="inline-flex items-center gap-1.5 px-2 py-0.5 border border-almost-black/20 text-xs font-medium">
                                        <div className={`h-1.5 w-1.5 rounded-full ${user.status === 'Active' ? 'bg-green-500' : 'bg-destructive'}`} />
                                        {user.status}
                                    </span>
                                </td>
                                <td className="py-3 text-right">
                                    <span className="text-xs font-mono text-on-background/60">{user.time}</span>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            )}
        </OverviewCard>
    );
}
