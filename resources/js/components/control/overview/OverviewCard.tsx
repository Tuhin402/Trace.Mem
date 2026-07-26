import { ReactNode } from 'react';
import { Link } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';

interface OverviewCardProps {
    id: string;
    title: string;
    icon: ReactNode;
    viewAllHref?: string;
    children: ReactNode;
    className?: string;
}

export default function OverviewCard({ 
    id, 
    title, 
    icon, 
    viewAllHref, 
    children, 
    className = '' 
}: OverviewCardProps) {
    return (
        <section 
            id={id} 
            className={`flex flex-col bg-surface border border-almost-black scroll-mt-24 ${className}`}
        >
            <div className="flex items-center justify-between px-6 py-4 border-b border-almost-black/10 shrink-0">
                <div className="flex items-center gap-3">
                    <div className="text-primary/80">
                        {icon}
                    </div>
                    <h2 className="text-lg font-bold font-heading text-primary tracking-tight">
                        {title}
                    </h2>
                </div>
                
                {viewAllHref && (
                    <Link 
                        href={viewAllHref}
                        className="group flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-primary/70 hover:text-primary transition-colors"
                    >
                        View All
                        <ArrowRight className="h-3.5 w-3.5 transition-transform group-hover:translate-x-1" />
                    </Link>
                )}
            </div>
            
            <div className="p-6 overflow-y-auto no-scrollbar relative min-h-[200px] max-h-[500px]">
                {children}
            </div>
        </section>
    );
}
