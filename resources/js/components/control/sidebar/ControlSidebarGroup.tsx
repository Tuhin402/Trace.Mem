import { ReactNode } from 'react';
import { useControlShell } from '@/providers/control/ControlShellProvider';
import { ChevronDown, ChevronRight } from 'lucide-react';

interface GroupProps {
    id: string;
    label: string;
    children: ReactNode;
    forceExpanded?: boolean;
}

export function ControlSidebarGroup({ id, label, children, forceExpanded = false }: GroupProps) {
    const { collapsed, expandedGroups, toggleGroup } = useControlShell();
    
    // If sidebar is collapsed, we don't show group headers at all, just a continuous list of icons
    if (collapsed) {
        return <ul className="space-y-1 mb-4">{children}</ul>;
    }

    const isExpanded = forceExpanded || expandedGroups[id] !== false; // Default true

    return (
        <div className="mb-6">
            {label && (
                <button
                    onClick={() => toggleGroup(id)}
                    className="flex w-full items-center justify-between px-4 mb-2 text-xs font-mono font-bold uppercase tracking-widest text-on-background/50 hover:text-on-background/80 transition-colors"
                    aria-expanded={isExpanded}
                    aria-controls={`group-${id}`}
                >
                    <span>{label}</span>
                    {isExpanded ? <ChevronDown className="h-3 w-3" /> : <ChevronRight className="h-3 w-3" />}
                </button>
            )}
            
            {isExpanded && (
                <ul id={`group-${id}`} className="space-y-1 px-2">
                    {children}
                </ul>
            )}
        </div>
    );
}
