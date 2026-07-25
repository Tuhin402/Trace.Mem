import { Head } from '@inertiajs/react';
import { Settings, Users, Database, Shield, CreditCard, Construction } from 'lucide-react';

export default function Scaffold({ title, description, icon }: { title: string, description: string, icon: string }) {
    
    const IconComponent = () => {
        switch (icon) {
            case 'users': return <Users className="h-12 w-12 text-primary" />;
            case 'database': return <Database className="h-12 w-12 text-primary" />;
            case 'shield': return <Shield className="h-12 w-12 text-primary" />;
            case 'credit-card': return <CreditCard className="h-12 w-12 text-primary" />;
            case 'settings': return <Settings className="h-12 w-12 text-primary" />;
            default: return <Construction className="h-12 w-12 text-primary" />;
        }
    };

    return (
        <>
            <Head title={title} />
            <div className="flex flex-col items-center justify-center min-h-[60vh] text-center px-4">
                <div className="mb-6 p-4 rounded-full bg-almost-black/5">
                    <IconComponent />
                </div>
                <h1 className="text-3xl font-bold font-heading text-primary mb-2">{title}</h1>
                <p className="text-on-background/70 max-w-md font-mono text-sm">
                    {description}
                </p>
                <div className="mt-8 px-4 py-2 border border-almost-black bg-surface-muted text-xs font-mono font-bold uppercase tracking-widest text-on-background/60">
                    Coming in Phase 6+
                </div>
            </div>
        </>
    );
}
