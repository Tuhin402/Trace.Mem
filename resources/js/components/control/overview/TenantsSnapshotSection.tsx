import { Database, Ghost } from 'lucide-react';
import OverviewCard from './OverviewCard';
import { Link } from '@inertiajs/react';

export default function TenantsSnapshotSection({ data }: { data: any }) {
    if (data?.error) {
        return (
            <OverviewCard id="overview-tenants-snapshot" title="Tenants Snapshot" icon={<Database className="h-5 w-5" />}>
                <div className="flex flex-col items-center justify-center py-8 text-on-background/50">
                    <span className="text-sm font-bold text-destructive uppercase">{data.error}</span>
                </div>
            </OverviewCard>
        );
    }

    const tenants = Array.isArray(data) ? data : [];

    return (
        <OverviewCard
            id="overview-tenants-snapshot"
            title="Tenants Snapshot"
            icon={<Database className="h-5 w-5" />}
            viewAllHref="/platform/tenants"
        >
            {tenants.length === 0 ? (
                <div className="flex flex-col items-center justify-center py-10 opacity-60">
                    <Ghost className="h-8 w-8 mb-3 text-primary/40" />
                    <span className="text-sm font-bold uppercase tracking-wider">No Organizations</span>
                    <span className="text-xs text-center mt-1">Tenant backfill may be required.</span>
                </div>
            ) : (
                <table className="w-full text-left border-collapse">
                    <thead>
                        <tr className="border-b border-almost-black/10 text-[10px] uppercase font-bold text-on-background/50">
                            <th className="pb-3 font-medium">Organization</th>
                            <th className="pb-3 font-medium hidden sm:table-cell">Plan</th>
                            <th className="pb-3 font-medium text-right">Status</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-almost-black/5">
                        {tenants.map(tenant => (
                            <tr key={tenant.id} className="group hover:bg-almost-black/5 transition-colors">
                                <td className="py-3">
                                    <div className="flex flex-col">
                                        <Link href={`/platform/tenants/${tenant.slug || tenant.id}`} className="text-sm font-bold text-primary group-hover:underline cursor-pointer">{tenant.name}</Link>
                                        <span className="text-xs text-on-background/50 font-mono">ID: {tenant.id.substring(0, 8)}...</span>
                                    </div>
                                </td>
                                <td className="py-3 hidden sm:table-cell">
                                    <span className="text-xs font-bold uppercase text-on-background/70">{tenant.plan}</span>
                                </td>
                                <td className="py-3 text-right">
                                    <span className={`text-xs font-bold uppercase ${tenant.status === 'active' ? 'text-green-600' : 'text-on-background/50'}`}>
                                        {tenant.status}
                                    </span>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            )}
        </OverviewCard>
    );
}
