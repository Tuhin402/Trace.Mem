import React from 'react';
import { Head, Link } from '@inertiajs/react';
import { ChevronRight } from 'lucide-react';
import { ControlErrorBoundary } from '@/components/control/ui/ControlErrorBoundary';

export interface Breadcrumb {
    label: string;
    url?: string;
}

export interface EntityAction {
    label: string;
    icon?: React.ReactNode;
    onClick: () => void;
    destructive?: boolean;
    primary?: boolean;
}

export interface EntityTab {
    label: string;
    id: string; // The anchor hash for deep linking (e.g. 'security')
    isActive?: boolean;
}

interface ControlEntityLayoutProps {
    title: string;
    breadcrumbs: Breadcrumb[];
    badges?: React.ReactNode[];
    actions?: EntityAction[];
    tabs?: EntityTab[];
    currentTab?: string;
    children: React.ReactNode;
}

export default function ControlEntityLayout({
    title,
    breadcrumbs,
    badges,
    actions,
    tabs,
    currentTab = 'overview',
    children,
}: ControlEntityLayoutProps) {
    return (
        <ControlErrorBoundary>
            <Head title={`${title} | Platform`} />

            <div className="w-full pb-24 space-y-6">
                
                {/* 1. Breadcrumbs */}
                <nav className="flex flex-wrap items-center gap-2 text-xs font-mono text-on-background/60 uppercase tracking-wider">
                    <Link href="/control/platform/users" className="hover:text-primary transition-colors">Platform</Link>
                    {breadcrumbs.map((crumb, index) => (
                        <React.Fragment key={index}>
                            <ChevronRight className="h-3 w-3" />
                            {crumb.url ? (
                                <Link href={crumb.url} className="hover:text-primary transition-colors">
                                    {crumb.label}
                                </Link>
                            ) : (
                                <span className="text-on-background">{crumb.label}</span>
                            )}
                        </React.Fragment>
                    ))}
                </nav>

                {/* 2. Entity Header & Actions */}
                <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div className="flex flex-col gap-2">
                        <div className="flex items-center gap-3">
                            <h1 className="text-3xl font-heading font-black tracking-tight text-on-background">
                                {title}
                            </h1>
                            {badges && badges.length > 0 && (
                                <div className="flex items-center gap-2">
                                    {badges.map((badge, i) => (
                                        <React.Fragment key={i}>{badge}</React.Fragment>
                                    ))}
                                </div>
                            )}
                        </div>
                    </div>
                    
                    {actions && actions.length > 0 && (
                        <div className="flex items-center gap-3 flex-wrap">
                            {actions.map((action, i) => (
                                <button
                                    key={i}
                                    onClick={action.onClick}
                                    className={`flex items-center gap-2 px-4 py-2 border text-sm font-bold uppercase tracking-wider transition-colors ${
                                        action.primary 
                                            ? 'bg-primary border-primary text-white hover:bg-primary/90' 
                                            : action.destructive
                                                ? 'bg-destructive/10 border-destructive/20 text-destructive hover:bg-destructive hover:text-white'
                                                : 'bg-surface border-almost-black/20 text-on-background hover:border-primary hover:text-primary'
                                    }`}
                                >
                                    {action.icon}
                                    {action.label}
                                </button>
                            ))}
                        </div>
                    )}
                </div>

                {/* 3. Secondary Navigation (Tabs) */}
                {tabs && tabs.length > 0 && (
                    <div className="w-full border-b border-almost-black/10 flex overflow-x-auto no-scrollbar">
                        {tabs.map((tab) => (
                            <a
                                key={tab.id}
                                href={`#${tab.id}`}
                                className={`px-6 py-3 text-sm font-bold uppercase tracking-wider transition-all border-b-2 whitespace-nowrap ${
                                    (currentTab === tab.id || tab.isActive)
                                        ? 'border-primary text-primary'
                                        : 'border-transparent text-on-background/60 hover:text-on-background hover:border-almost-black/20'
                                }`}
                            >
                                {tab.label}
                            </a>
                        ))}
                    </div>
                )}

                {/* 4. Content Area */}
                <div className="pt-4">
                    {children}
                </div>
            </div>
        </ControlErrorBoundary>
    );
}
