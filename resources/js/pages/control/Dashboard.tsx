import { Head } from '@inertiajs/react';

export default function Dashboard() {
    return (
        <>
            <Head title="Operations Overview" />

            <div className="space-y-6">
                <div>
                    <h1 className="text-3xl font-bold tracking-tight">Overview</h1>
                    <p className="text-on-background/70">
                        System health, active sessions, and high-level platform metrics.
                    </p>
                </div>

                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    {/* Placeholder metric cards */}
                    <MetricCard title="Total Users" value="10,234" trend="+12%" />
                    <MetricCard title="Active Tenants" value="892" trend="+5%" />
                    <MetricCard title="API Requests (24h)" value="1.2M" trend="-2%" />
                    <MetricCard title="System Health" value="100%" trend="Stable" />
                </div>

                {/* World Map Placeholder */}
                <div className="h-96 w-full border border-almost-black bg-surface p-4 flex flex-col">
                    <h2 className="text-lg font-semibold font-heading mb-4">Global Distribution</h2>
                    <div className="flex-1 flex items-center justify-center border border-dashed border-almost-black/20 text-on-background/50 font-mono">
                        [GeoJSON World Map Component]
                    </div>
                </div>
            </div>
        </>
    );
}

function MetricCard({ title, value, trend }: any) {
    return (
        <div className="p-4 bg-surface border border-almost-black">
            <h3 className="label-caps mb-2 text-on-background/70">{title}</h3>
            <div className="text-3xl font-bold font-heading">{value}</div>
            <div className="text-sm font-mono mt-2 text-on-background/60">{trend}</div>
        </div>
    );
}
