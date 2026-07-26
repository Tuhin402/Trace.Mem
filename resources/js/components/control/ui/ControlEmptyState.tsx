import { ReactNode } from 'react';
import { Construction } from 'lucide-react';

interface ControlEmptyStateProps {
    title: string;
    description: string;
    icon?: ReactNode;
    action?: ReactNode;
    documentationLink?: string;
}

export function ControlEmptyState({ title, description, icon, action, documentationLink }: ControlEmptyStateProps) {
    return (
        <div className="flex flex-col items-center justify-center py-24 text-center px-4 border border-dashed border-almost-black/20 rounded-lg bg-surface-muted/30">
            <div className="mb-6 p-4 rounded-full bg-primary/10 text-primary">
                {icon || <Construction className="h-10 w-10" />}
            </div>
            <h3 className="text-xl font-bold font-heading text-on-background mb-2">{title}</h3>
            <p className="text-on-background/60 max-w-sm font-mono text-sm mb-6">
                {description}
            </p>
            {action && (
                <div className="mb-4">
                    {action}
                </div>
            )}
            {documentationLink && (
                <a 
                    href={documentationLink}
                    target="_blank"
                    rel="noreferrer"
                    className="text-xs font-mono text-primary hover:underline underline-offset-4"
                >
                    Read the Documentation &rarr;
                </a>
            )}
        </div>
    );
}
