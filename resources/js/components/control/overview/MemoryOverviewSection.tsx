import { Layers, Ghost } from 'lucide-react';
import OverviewCard from './OverviewCard';
import { BarChart, Bar, ResponsiveContainer, Tooltip } from 'recharts';

export default function MemoryOverviewSection({ data }: { data: any }) {
    if (data?.error) {
        return (
            <OverviewCard id="overview-memory" title="Memory Pipeline" icon={<Layers className="h-5 w-5" />}>
                <div className="flex flex-col items-center justify-center py-8 text-on-background/50">
                    <span className="text-sm font-bold text-destructive uppercase">{data.error}</span>
                </div>
            </OverviewCard>
        );
    }

    const chartData = data?.chart || [];

    return (
        <OverviewCard
            id="overview-memory"
            title="Memory Pipeline"
            icon={<Layers className="h-5 w-5" />}
            viewAllHref="/operations/memory"
        >
            <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                <div className="flex flex-col">
                    <span className="text-[10px] font-bold uppercase tracking-wider text-on-background/50 mb-1">Total Memories</span>
                    <span className="text-2xl font-heading font-bold text-primary">{data?.total || 0}</span>
                </div>
                <div className="flex flex-col">
                    <span className="text-[10px] font-bold uppercase tracking-wider text-on-background/50 mb-1">Stored Today</span>
                    <span className="text-2xl font-heading font-bold text-primary">{data?.today || 0}</span>
                </div>
                <div className="flex flex-col">
                    <span className="text-[10px] font-bold uppercase tracking-wider text-on-background/50 mb-1">Avg Latency</span>
                    <span className="text-2xl font-heading font-bold text-primary">--</span>
                </div>
                <div className="flex flex-col">
                    <span className="text-[10px] font-bold uppercase tracking-wider text-on-background/50 mb-1">Conflicts</span>
                    <span className="text-2xl font-heading font-bold text-primary">0</span>
                </div>
            </div>

            {chartData.length === 0 ? (
                <div className="flex flex-col items-center justify-center py-10 opacity-60">
                    <Ghost className="h-8 w-8 mb-3 text-primary/40" />
                    <span className="text-sm font-bold uppercase tracking-wider">No Memories Stored</span>
                    <span className="text-xs text-center mt-1">Users haven't pushed any memories yet.</span>
                </div>
            ) : (
                <div className="h-48 w-full mt-4 border border-almost-black/10 p-2 relative">
                    <span className="absolute top-2 left-2 text-[10px] font-bold uppercase tracking-wider text-on-background/50 z-10">
                        7-Day Storage Volume
                    </span>
                    <ResponsiveContainer width="100%" height="100%">
                        <BarChart data={chartData} margin={{ top: 20, right: 0, left: 0, bottom: 0 }}>
                            <Tooltip 
                                cursor={{ fill: 'transparent' }}
                                contentStyle={{ backgroundColor: 'var(--color-surface)', borderColor: 'var(--color-almost-black)', borderRadius: 0, padding: '8px' }}
                                itemStyle={{ color: 'var(--color-primary)', fontSize: '12px', fontWeight: 'bold' }}
                                labelStyle={{ color: 'rgba(var(--color-on-background), 0.6)', fontSize: '10px', textTransform: 'uppercase' }}
                            />
                            <Bar dataKey="memories" fill="var(--color-primary)" radius={[2, 2, 0, 0]} isAnimationActive={false} />
                        </BarChart>
                    </ResponsiveContainer>
                </div>
            )}
        </OverviewCard>
    );
}
