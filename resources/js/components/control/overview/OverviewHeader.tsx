export default function OverviewHeader() {
    return (
        <div className="flex flex-col md:flex-row md:items-end justify-between gap-4 border-b border-almost-black/10 pb-6">
            <div>
                <h1 className="text-3xl font-bold font-heading text-primary tracking-tight">Operations Command Center</h1>
                <p className="text-sm font-mono text-on-background/70 mt-2 uppercase tracking-wider">
                    System Overview & Telemetry
                </p>
            </div>
            <div className="flex items-center gap-4 text-xs font-mono">
                <div className="flex items-center gap-2 px-3 py-1.5 bg-primary/5 border border-primary/20 text-primary">
                    <span className="relative flex h-2 w-2">
                        <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-primary opacity-75"></span>
                        <span className="relative inline-flex rounded-full h-2 w-2 bg-primary"></span>
                    </span>
                    Live Telemetry Active
                </div>
                <div className="text-on-background/50">
                    Last sync: Just now
                </div>
            </div>
        </div>
    );
}
