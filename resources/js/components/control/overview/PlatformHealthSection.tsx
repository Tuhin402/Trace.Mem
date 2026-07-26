import { Server, Database, Layers, Cloud, Activity, Mail, AlertTriangle } from 'lucide-react';

export default function PlatformHealthSection({ data }: { data: any }) {
    if (data?.error) {
        return (
            <section id="overview-system-health" className="w-full scroll-mt-24">
                <div className="flex flex-col items-center justify-center p-6 border border-almost-black bg-surface min-h-[120px]">
                    <AlertTriangle className="h-6 w-6 text-destructive mb-2 opacity-80" />
                    <span className="text-sm font-bold text-destructive uppercase tracking-wider">{data.error}</span>
                    <span className="text-xs text-on-background/50 font-mono mt-1">{data.message}</span>
                </div>
            </section>
        );
    }

    const services = data?.services || [];

    return (
        <section id="overview-system-health" className="w-full scroll-mt-24">
            <div className="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-4">
                {services.map((service: any, i: number) => (
                    <div key={i} className={`flex flex-col p-4 border border-almost-black bg-surface`}>
                        <div className="flex items-center gap-2 mb-3 text-on-background/70">
                            {/* Dynamically assign icon if we want, or map them on the frontend.
                                Since the backend just sends strings, let's map icons here based on name.
                            */}
                            {service.name.includes('API') && <Server className="h-4 w-4" />}
                            {service.name.includes('Database') && <Database className="h-4 w-4" />}
                            {service.name.includes('Memory') && <Layers className="h-4 w-4" />}
                            {service.name.includes('Workers') && <Activity className="h-4 w-4" />}
                            {service.name.includes('Storage') && <Cloud className="h-4 w-4" />}
                            {service.name.includes('Email') && <Mail className="h-4 w-4" />}
                            
                            <span className="text-xs font-bold uppercase tracking-wider">{service.name}</span>
                        </div>
                        <div className={`mt-auto inline-flex items-center gap-2 text-sm font-mono font-medium ${service.color}`}>
                            <div className={`h-2 w-2 rounded-full ${service.bg} border ${service.border} flex items-center justify-center`}>
                                <div className="h-1 w-1 rounded-full bg-current" />
                            </div>
                            {service.status}
                        </div>
                    </div>
                ))}
            </div>
        </section>
    );
}
