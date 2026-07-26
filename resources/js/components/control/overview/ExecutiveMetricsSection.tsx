import { Users, Database, Key, Zap, AlertTriangle } from 'lucide-react';

export default function ExecutiveMetricsSection({ data }: { data: any }) {
    if (data?.error) {
        return (
            <section id="overview-executive-metrics" className="w-full scroll-mt-24">
                <div className="flex flex-col items-center justify-center p-6 border border-almost-black bg-surface min-h-[120px]">
                    <AlertTriangle className="h-6 w-6 text-destructive mb-2 opacity-80" />
                    <span className="text-sm font-bold text-destructive uppercase tracking-wider">{data.error}</span>
                </div>
            </section>
        );
    }

    const formatNumber = (num: number) => {
        if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M';
        if (num >= 1000) return (num / 1000).toFixed(1) + 'k';
        return num.toString();
    };

    const metrics = [
        { label: 'Total Tenants', value: formatNumber(data?.totalTenants || 0), change: 'View all organizations', icon: <Database className="h-5 w-5" /> },
        { label: 'Active Workspaces', value: formatNumber(data?.activeWorkspaces || 0), change: 'Across all tenants', icon: <Zap className="h-5 w-5" /> },
        { label: 'Platform Users', value: formatNumber(data?.platformUsers || 0), change: 'Registered accounts', icon: <Users className="h-5 w-5" /> },
        { label: 'API Requests (24h)', value: formatNumber(data?.apiRequests24h || 0), change: 'Requires Usage Log integration', icon: <Key className="h-5 w-5" /> },
    ];

    return (
        <section id="overview-executive-metrics" className="w-full scroll-mt-24">
            <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                {metrics.map((metric, i) => (
                    <div key={i} className="flex flex-col p-6 border border-almost-black bg-surface relative overflow-hidden group">
                        <div className="absolute top-0 right-0 p-6 opacity-5 group-hover:opacity-10 transition-opacity transform group-hover:scale-110 duration-500 text-primary">
                            {metric.icon}
                        </div>
                        <span className="text-xs font-bold uppercase tracking-wider text-on-background/60 mb-2 relative z-10">
                            {metric.label}
                        </span>
                        <div className="text-4xl font-bold font-heading text-primary relative z-10">
                            {metric.value}
                        </div>
                        <div className="mt-4 text-[10px] uppercase font-bold text-on-background/40 relative z-10">
                            {metric.change}
                        </div>
                    </div>
                ))}
            </div>
        </section>
    );
}
