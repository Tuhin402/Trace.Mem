import { Database, Building2, TrendingUp, AlertCircle } from 'lucide-react';
import OverviewCard from './OverviewCard';

export default function TenantsSnapshotSection() {
    const tenants = [
        { id: 1, name: 'Acme Corp', slug: 'acme-corp', plan: 'Enterprise', memoryUsage: '84%', status: 'warning' },
        { id: 2, name: 'Globex Inc', slug: 'globex', plan: 'Pro', memoryUsage: '45%', status: 'healthy' },
        { id: 3, name: 'Soylent', slug: 'soylent', plan: 'Enterprise', memoryUsage: '92%', status: 'critical' },
        { id: 4, name: 'Initech', slug: 'initech', plan: 'Free Trial', memoryUsage: '12%', status: 'healthy' },
    ];

    return (
        <OverviewCard
            id="overview-tenants"
            title="Tenant Snapshot"
            icon={<Building2 className="h-5 w-5" />}
            viewAllHref="/platform/tenants"
        >
            <div className="space-y-4">
                {tenants.map(tenant => (
                    <div key={tenant.id} className="flex flex-col p-3 border border-almost-black/10 hover:border-primary/30 transition-colors group">
                        <div className="flex items-center justify-between mb-2">
                            <span className="text-sm font-bold text-on-background group-hover:text-primary transition-colors">
                                {tenant.name}
                            </span>
                            <span className="text-xs font-mono px-2 py-0.5 bg-almost-black/5 border border-almost-black/10">
                                {tenant.plan}
                            </span>
                        </div>
                        <div className="flex items-center justify-between mt-2">
                            <div className="flex items-center gap-1.5 text-xs text-on-background/60 font-mono">
                                <Database className="h-3.5 w-3.5" />
                                Memory Usage: {tenant.memoryUsage}
                            </div>
                            {tenant.status === 'critical' && <AlertCircle className="h-4 w-4 text-destructive animate-pulse" />}
                            {tenant.status === 'warning' && <AlertCircle className="h-4 w-4 text-amber-500" />}
                        </div>
                        {/* Fake Progress Bar */}
                        <div className="w-full h-1 bg-almost-black/10 mt-2">
                            <div 
                                className={`h-full ${
                                    tenant.status === 'critical' ? 'bg-destructive' : 
                                    tenant.status === 'warning' ? 'bg-amber-500' : 'bg-primary'
                                }`}
                                style={{ width: tenant.memoryUsage }}
                            />
                        </div>
                    </div>
                ))}
            </div>
        </OverviewCard>
    );
}
