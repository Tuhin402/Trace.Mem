import { Key, Ghost } from 'lucide-react';
import OverviewCard from './OverviewCard';
import { AreaChart, Area, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts';

export default function ApiOverviewSection({ data }: { data: any }) {
    if (data?.error) {
        return (
            <OverviewCard id="overview-api" title="API Activity" icon={<Key className="h-5 w-5" />}>
                <div className="flex flex-col items-center justify-center py-8 text-on-background/50">
                    <span className="text-sm font-bold text-destructive uppercase">{data.error}</span>
                </div>
            </OverviewCard>
        );
    }

    const chartData = data?.chart || [];

    return (
        <OverviewCard
            id="overview-api"
            title="API Activity"
            icon={<Key className="h-5 w-5" />}
            viewAllHref="/operations/api"
        >
            <div className="mb-6 flex flex-col">
                <span className="text-[10px] font-bold uppercase tracking-wider text-on-background/50 mb-1">Total Requests (24h)</span>
                <span className="text-2xl font-heading font-bold text-primary">{data?.total || 0}</span>
            </div>

            {chartData.length === 0 ? (
                <div className="flex flex-col items-center justify-center py-10 opacity-60">
                    <Ghost className="h-8 w-8 mb-3 text-primary/40" />
                    <span className="text-sm font-bold uppercase tracking-wider">No API Activity</span>
                    <span className="text-xs text-center mt-1">Waiting for the first API request.</span>
                </div>
            ) : (
                <div className="h-32 w-full mt-4">
                    <ResponsiveContainer width="100%" height="100%">
                        <AreaChart data={chartData} margin={{ top: 0, right: 0, left: 0, bottom: 0 }}>
                            <defs>
                                <linearGradient id="apiGradient" x1="0" y1="0" x2="0" y2="1">
                                    <stop offset="5%" stopColor="#4f46e5" stopOpacity={0.2}/>
                                    <stop offset="95%" stopColor="#4f46e5" stopOpacity={0}/>
                                </linearGradient>
                            </defs>
                            <Tooltip 
                                contentStyle={{ backgroundColor: 'var(--color-surface)', borderColor: 'var(--color-almost-black)', borderRadius: 0, padding: '8px' }}
                                itemStyle={{ color: 'var(--color-primary)', fontSize: '12px', fontWeight: 'bold' }}
                                labelStyle={{ color: 'rgba(var(--color-on-background), 0.6)', fontSize: '10px', textTransform: 'uppercase' }}
                            />
                            <Area 
                                type="monotone" 
                                dataKey="requests" 
                                stroke="#4f46e5" 
                                strokeWidth={2}
                                fillOpacity={1} 
                                fill="url(#apiGradient)" 
                                isAnimationActive={false}
                            />
                        </AreaChart>
                    </ResponsiveContainer>
                </div>
            )}
        </OverviewCard>
    );
}
