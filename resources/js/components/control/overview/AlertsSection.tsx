import { AlertTriangle, HardDrive, Mail, Ban, ArrowRight } from 'lucide-react';
import { Link } from '@inertiajs/react';

export default function AlertsSection() {
    const alerts = [
        { id: 1, title: 'High Error Rate Detected', description: 'API Gateway is reporting >5% error rate on /v1/memory endpoints.', type: 'critical', icon: <AlertTriangle className="h-5 w-5" /> },
        { id: 2, title: 'Storage Nearing Capacity', description: 'Tenant "Acme Corp" is at 95% of their allocated memory storage limit.', type: 'warning', icon: <HardDrive className="h-5 w-5" /> },
        { id: 3, title: 'Email Delivery Failures', description: 'Postmark reported 12 hard bounces in the last hour.', type: 'warning', icon: <Mail className="h-5 w-5" /> },
        { id: 4, title: 'Suspicious Login Activity', description: 'Multiple failed admin login attempts from unknown IP ranges.', type: 'critical', icon: <Ban className="h-5 w-5" /> },
    ];

    if (alerts.length === 0) {
        return (
            <section id="overview-alerts" className="w-full p-6 bg-surface border border-almost-black flex items-center justify-center min-h-[120px]">
                <div className="flex flex-col items-center gap-2 text-on-background/50">
                    <AlertTriangle className="h-6 w-6 opacity-50" />
                    <span className="text-sm font-mono uppercase tracking-wider">No active operational alerts</span>
                </div>
            </section>
        );
    }

    return (
        <section id="overview-alerts" className="w-full scroll-mt-24">
            <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                {alerts.map(alert => (
                    <div 
                        key={alert.id} 
                        className={`flex flex-col p-5 border relative overflow-hidden group ${
                            alert.type === 'critical' 
                                ? 'border-destructive/30 bg-destructive/5' 
                                : 'border-amber-500/30 bg-amber-500/5'
                        }`}
                    >
                        <div className="flex items-start gap-3 relative z-10">
                            <div className={`mt-0.5 shrink-0 ${alert.type === 'critical' ? 'text-destructive' : 'text-amber-500'}`}>
                                {alert.icon}
                            </div>
                            <div className="flex flex-col">
                                <h3 className={`text-sm font-bold tracking-wide uppercase ${alert.type === 'critical' ? 'text-destructive' : 'text-amber-600'}`}>
                                    {alert.title}
                                </h3>
                                <p className="text-sm text-on-background/80 mt-1 leading-relaxed">
                                    {alert.description}
                                </p>
                            </div>
                        </div>
                        <div className="mt-4 pt-4 border-t border-almost-black/10 flex justify-end">
                            <Link href="/operations/notifications" className={`text-xs font-bold uppercase tracking-wider flex items-center gap-1 ${alert.type === 'critical' ? 'text-destructive hover:text-destructive/80' : 'text-amber-600 hover:text-amber-700'}`}>
                                Investigate
                                <ArrowRight className="h-3 w-3" />
                            </Link>
                        </div>
                    </div>
                ))}
            </div>
        </section>
    );
}
