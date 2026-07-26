import { ReactNode } from 'react';

export function ControlSkeleton({ className = '' }: { className?: string }) {
    return (
        <div className={`animate-pulse bg-almost-black/10 rounded ${className}`} />
    );
}

export function ControlPageSkeleton() {
    return (
        <div className="space-y-6">
            <div className="flex items-center space-x-4 mb-8">
                <ControlSkeleton className="h-12 w-12 rounded-full" />
                <div className="space-y-2">
                    <ControlSkeleton className="h-6 w-48" />
                    <ControlSkeleton className="h-4 w-64" />
                </div>
            </div>
            
            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                <ControlSkeleton className="h-32 w-full rounded-lg" />
                <ControlSkeleton className="h-32 w-full rounded-lg" />
                <ControlSkeleton className="h-32 w-full rounded-lg" />
            </div>

            <ControlSkeleton className="h-64 w-full rounded-lg" />
        </div>
    );
}
