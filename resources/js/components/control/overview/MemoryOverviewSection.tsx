import { Layers, Network, Fingerprint, Zap } from 'lucide-react';
import OverviewCard from './OverviewCard';
import { BarChart, Bar, XAxis, YAxis, Tooltip, ResponsiveContainer, Cell } from 'recharts';

export default function MemoryOverviewSection() {
    // Mock data for the memory accumulation chart
    const data = [
        { name: 'Mon', memories: 1240 },
        { name: 'Tue', memories: 1350 },
        { name: 'Wed', memories: 1800 },
        { name: 'Thu', memories: 2200 },
        { name: 'Fri', memories: 2600 },
        { name: 'Sat', memories: 1900 },
        { name: 'Sun', memories: 1100 },
    ];

    return (
        <OverviewCard
            id="overview-memory"
            title="Memory Pipeline Overview"
            icon={<Layers className="h-5 w-5" />}
            viewAllHref="/platform/memory"
        >
            <div className="flex flex-col xl:flex-row gap-8">
                
                {/* Left Side: Chart */}
                <div className="flex-1 min-w-0">
                    <h4 className="text-xs font-bold uppercase tracking-wider text-on-background/50 mb-4 border-b border-almost-black/10 pb-2">
                        Weekly Injection Volume
                    </h4>
                    <div className="h-48 w-full">
                        <ResponsiveContainer width="100%" height="100%">
                            <BarChart data={data} margin={{ top: 0, right: 0, left: -25, bottom: 0 }}>
                                <XAxis dataKey="name" axisLine={false} tickLine={false} tick={{ fontSize: 10, fill: 'currentColor', opacity: 0.5 }} />
                                <YAxis axisLine={false} tickLine={false} tick={{ fontSize: 10, fill: 'currentColor', opacity: 0.5 }} />
                                <Tooltip 
                                    cursor={{ fill: 'var(--color-almost-black)', opacity: 0.05 }}
                                    contentStyle={{ borderRadius: '0px', border: '1px solid var(--color-almost-black)', backgroundColor: 'var(--color-surface)', fontSize: '12px' }}
                                    itemStyle={{ color: 'var(--color-primary)', fontWeight: 'bold' }}
                                    labelStyle={{ color: 'var(--color-on-background)', opacity: 0.7, marginBottom: '4px' }}
                                />
                                <Bar dataKey="memories" fill="var(--color-primary)" radius={0}>
                                    {data.map((entry, index) => (
                                        <Cell key={`cell-${index}`} fill={index === data.length - 1 ? 'var(--color-primary)' : 'var(--color-primary)'} fillOpacity={0.7} />
                                    ))}
                                </Bar>
                            </BarChart>
                        </ResponsiveContainer>
                    </div>
                </div>

                {/* Right Side: Heavy Metrics */}
                <div className="w-full xl:w-64 shrink-0 flex flex-col gap-4">
                    <h4 className="text-xs font-bold uppercase tracking-wider text-on-background/50 mb-0 border-b border-almost-black/10 pb-2">
                        Pipeline Health
                    </h4>
                    
                    <div className="p-3 border border-almost-black/10 flex items-center justify-between group hover:border-primary/30 transition-colors">
                        <div className="flex items-center gap-2">
                            <Fingerprint className="h-4 w-4 text-on-background/50 group-hover:text-primary transition-colors" />
                            <span className="text-sm font-medium">Resolutions</span>
                        </div>
                        <span className="font-mono font-bold text-primary">12.4K</span>
                    </div>

                    <div className="p-3 border border-almost-black/10 flex items-center justify-between group hover:border-primary/30 transition-colors">
                        <div className="flex items-center gap-2">
                            <Network className="h-4 w-4 text-on-background/50 group-hover:text-primary transition-colors" />
                            <span className="text-sm font-medium">Graph Edges</span>
                        </div>
                        <span className="font-mono font-bold text-primary">48.2M</span>
                    </div>

                    <div className="p-3 border border-almost-black/10 flex items-center justify-between group hover:border-primary/30 transition-colors">
                        <div className="flex items-center gap-2">
                            <Zap className="h-4 w-4 text-on-background/50 group-hover:text-primary transition-colors" />
                            <span className="text-sm font-medium">Avg Latency</span>
                        </div>
                        <span className="font-mono font-bold text-green-500">42ms</span>
                    </div>
                </div>

            </div>
        </OverviewCard>
    );
}
