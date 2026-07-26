import { Key, Activity, ShieldAlert, BarChart3 } from 'lucide-react';
import OverviewCard from './OverviewCard';
import { AreaChart, Area, XAxis, YAxis, Tooltip, ResponsiveContainer } from 'recharts';

export default function ApiOverviewSection() {
    // Mock data for the area chart
    const data = [
        { time: '00:00', requests: 4000, errors: 24 },
        { time: '04:00', requests: 3000, errors: 13 },
        { time: '08:00', requests: 8000, errors: 45 },
        { time: '12:00', requests: 12000, errors: 89 },
        { time: '16:00', requests: 15000, errors: 120 },
        { time: '20:00', requests: 9000, errors: 50 },
        { time: '24:00', requests: 5000, errors: 30 },
    ];

    return (
        <OverviewCard
            id="overview-api"
            title="API Activity"
            icon={<Key className="h-5 w-5" />}
            viewAllHref="/platform/api-keys"
        >
            {/* Chart Area */}
            <div className="h-32 w-full mb-6 relative">
                <ResponsiveContainer width="100%" height="100%">
                    <AreaChart data={data} margin={{ top: 5, right: 0, left: -20, bottom: 0 }}>
                        <defs>
                            <linearGradient id="colorRequests" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="5%" stopColor="var(--color-primary)" stopOpacity={0.3}/>
                                <stop offset="95%" stopColor="var(--color-primary)" stopOpacity={0}/>
                            </linearGradient>
                        </defs>
                        <XAxis dataKey="time" axisLine={false} tickLine={false} tick={{ fontSize: 10, fill: 'currentColor', opacity: 0.5 }} />
                        <YAxis axisLine={false} tickLine={false} tick={{ fontSize: 10, fill: 'currentColor', opacity: 0.5 }} />
                        <Tooltip 
                            contentStyle={{ borderRadius: '0px', border: '1px solid var(--color-almost-black)', backgroundColor: 'var(--color-surface)', fontSize: '12px' }}
                            itemStyle={{ color: 'var(--color-primary)', fontWeight: 'bold' }}
                            labelStyle={{ color: 'var(--color-on-background)', opacity: 0.7, marginBottom: '4px' }}
                        />
                        <Area type="monotone" dataKey="requests" stroke="var(--color-primary)" strokeWidth={2} fillOpacity={1} fill="url(#colorRequests)" />
                    </AreaChart>
                </ResponsiveContainer>
            </div>

            {/* Quick Stats Grid */}
            <div className="grid grid-cols-2 gap-3 mb-6">
                <div className="p-3 border border-almost-black/10 flex flex-col">
                    <span className="text-xs text-on-background/60 uppercase font-bold tracking-wider mb-1">Success Rate</span>
                    <span className="text-lg font-mono text-green-500 font-bold">99.8%</span>
                </div>
                <div className="p-3 border border-destructive/20 bg-destructive/5 flex flex-col">
                    <span className="text-xs text-destructive/80 uppercase font-bold tracking-wider mb-1">Rate Limited</span>
                    <span className="text-lg font-mono text-destructive font-bold">142</span>
                </div>
            </div>

            {/* Recent API Keys */}
            <div>
                <h4 className="text-xs font-bold uppercase tracking-wider text-on-background/50 mb-3 border-b border-almost-black/10 pb-2">Recently Rotated Keys</h4>
                <div className="space-y-3">
                    <div className="flex items-center justify-between text-sm">
                        <span className="font-mono text-on-background/80">pk_live_acme...8f2a</span>
                        <span className="text-xs text-on-background/50">2 hrs ago</span>
                    </div>
                    <div className="flex items-center justify-between text-sm">
                        <span className="font-mono text-on-background/80">pk_test_glob...3x9b</span>
                        <span className="text-xs text-on-background/50">5 hrs ago</span>
                    </div>
                </div>
            </div>
        </OverviewCard>
    );
}
