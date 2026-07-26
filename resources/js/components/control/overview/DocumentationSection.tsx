import { BookOpen, Shield, Code, Server, ArrowUpRight } from 'lucide-react';
import OverviewCard from './OverviewCard';

export default function DocumentationSection() {
    const docs = [
        { name: 'Platform Architecture', icon: <Server className="h-4 w-4" /> },
        { name: 'Security Protocols', icon: <Shield className="h-4 w-4" /> },
        { name: 'API Reference', icon: <Code className="h-4 w-4" /> },
        { name: 'Operational Runbooks', icon: <BookOpen className="h-4 w-4" /> },
    ];

    return (
        <OverviewCard
            id="overview-documentation"
            title="Documentation"
            icon={<BookOpen className="h-5 w-5" />}
        >
            <div className="space-y-2">
                {docs.map((doc, i) => (
                    <a
                        key={i}
                        href="#"
                        className="flex items-center justify-between p-3 bg-almost-black/5 hover:bg-primary/10 text-on-background hover:text-primary transition-colors group border border-transparent hover:border-primary/20"
                    >
                        <div className="flex items-center gap-3">
                            <div className="text-on-background/50 group-hover:text-primary transition-colors">
                                {doc.icon}
                            </div>
                            <span className="text-sm font-medium">{doc.name}</span>
                        </div>
                        <ArrowUpRight className="h-4 w-4 opacity-50 group-hover:opacity-100 transition-opacity" />
                    </a>
                ))}
            </div>
        </OverviewCard>
    );
}
