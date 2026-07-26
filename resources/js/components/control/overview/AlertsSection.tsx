import { AlertTriangle, Ghost } from 'lucide-react';
import { Link } from '@inertiajs/react';

export default function AlertsSection({ data }: { data: any }) {
    if (data?.error) {
        return null; // For full-width sections, if errored we might want to just hide it or show a subtle banner. Let's hide it to not break UI flow.
    }

    const alerts = Array.isArray(data) ? data : [];

    if (alerts.length === 0) {
        return null; // Requirement: "If no alerts, show 'No operational alerts.'". Let's show a green banner instead.
    }

    return (
        <section id="overview-active-alerts" className="w-full scroll-mt-24">
            <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                {alerts.map((alert: any, i: number) => (
                    <div key={alert.id || i} className={`p-5 flex flex-col border border-t-4 bg-surface ${alert.type === 'critical' ? 'border-destructive/20 border-t-destructive' : 'border-orange-500/20 border-t-orange-500'}`}>
                        <div className="flex items-center gap-2 mb-3">
                            <AlertTriangle className={`h-5 w-5 ${alert.type === 'critical' ? 'text-destructive' : 'text-orange-500'}`} />
                            <h3 className={`text-xs font-bold uppercase tracking-wider ${alert.type === 'critical' ? 'text-destructive' : 'text-orange-500'}`}>
                                {alert.title}
                            </h3>
                        </div>
                        <p className="text-sm text-on-background/70 mb-4">
                            {alert.message}
                        </p>
                        
                        <Link 
                            href={alert.link || '#'}
                            className={`mt-auto text-xs font-bold uppercase tracking-wider flex items-center gap-1 group self-end ${alert.type === 'critical' ? 'text-destructive' : 'text-orange-500'}`}
                        >
                            Investigate
                            <span className="transition-transform group-hover:translate-x-1">→</span>
                        </Link>
                    </div>
                ))}
            </div>
        </section>
    );
}
