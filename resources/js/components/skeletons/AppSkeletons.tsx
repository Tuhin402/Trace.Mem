import { SkeletonBase, SkeletonText, SkeletonTitle, SkeletonAvatar } from './SkeletonBase';

export function StatsCardSkeleton() {
    return (
        <div className="tm-card p-6 flex flex-col gap-4">
            <div className="flex justify-between items-start">
                <div className="flex flex-col gap-2 w-full">
                    <SkeletonText variant="short" className="h-3" />
                    <SkeletonTitle className="h-8 w-1/2" />
                </div>
                <SkeletonBase className="w-8 h-8 rounded-full" />
            </div>
            <SkeletonText variant="medium" className="h-3" />
        </div>
    );
}

export function ActivityRowSkeleton() {
    return (
        <div className="flex items-center justify-between py-4 border-b border-white/5 last:border-0">
            <div className="flex items-center gap-4 w-full">
                <SkeletonAvatar />
                <div className="flex flex-col gap-2 flex-1">
                    <SkeletonText variant="medium" />
                    <SkeletonText variant="short" className="h-2 opacity-50" />
                </div>
            </div>
            <SkeletonText variant="short" className="w-20" />
        </div>
    );
}

export function DashboardSkeleton() {
    return (
        <div className="flex-1 space-y-6 p-4 pt-0">
            <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <StatsCardSkeleton />
                <StatsCardSkeleton />
                <StatsCardSkeleton />
                <StatsCardSkeleton />
            </div>
            <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-7">
                <div className="tm-card col-span-4 p-6 h-[400px]">
                    <SkeletonTitle className="mb-6" />
                    <SkeletonBase className="w-full h-full rounded-md opacity-50" />
                </div>
                <div className="tm-card col-span-3 p-6">
                    <SkeletonTitle className="mb-6" />
                    <div className="space-y-2">
                        <ActivityRowSkeleton />
                        <ActivityRowSkeleton />
                        <ActivityRowSkeleton />
                        <ActivityRowSkeleton />
                    </div>
                </div>
            </div>
        </div>
    );
}

export function ApiKeyRowSkeleton() {
    return (
        <div className="flex items-center justify-between p-4 border border-white/5 rounded-lg mb-3">
            <div className="flex flex-col gap-2 w-1/3">
                <SkeletonText variant="short" />
                <SkeletonText variant="medium" className="h-2 opacity-50" />
            </div>
            <div className="w-1/3 flex justify-center">
                <SkeletonBase className="h-6 w-32 rounded-md" />
            </div>
            <div className="w-1/3 flex justify-end gap-2">
                <SkeletonBase className="h-8 w-8 rounded-md" />
                <SkeletonBase className="h-8 w-8 rounded-md" />
            </div>
        </div>
    );
}

export function ApiKeysSkeleton() {
    return (
        <div className="flex-1 space-y-6 p-4 pt-0">
            <div className="flex justify-between items-center mb-6">
                <div className="space-y-2">
                    <SkeletonTitle className="w-48" />
                    <SkeletonText variant="long" className="w-96 opacity-70" />
                </div>
                <SkeletonBase className="h-10 w-32 rounded-md" />
            </div>
            
            <div className="tm-card p-6">
                <SkeletonTitle className="mb-6 w-32" />
                <div className="space-y-4">
                    <ApiKeyRowSkeleton />
                    <ApiKeyRowSkeleton />
                    <ApiKeyRowSkeleton />
                </div>
            </div>
        </div>
    );
}

export function SettingsSectionSkeleton() {
    return (
        <div className="tm-card p-6 mb-6">
            <SkeletonTitle className="mb-2" />
            <SkeletonText variant="long" className="mb-6 opacity-70" />
            
            <div className="space-y-4">
                <div className="flex flex-col gap-2">
                    <SkeletonText variant="short" className="w-24 h-3" />
                    <SkeletonBase className="h-10 w-full rounded-md" />
                </div>
                <div className="flex flex-col gap-2">
                    <SkeletonText variant="short" className="w-32 h-3" />
                    <SkeletonBase className="h-10 w-full rounded-md" />
                </div>
                <div className="pt-4">
                    <SkeletonBase className="h-10 w-32 rounded-md" />
                </div>
            </div>
        </div>
    );
}

export function SettingsSkeleton() {
    return (
        <div className="flex-1 space-y-6 p-4 pt-0 max-w-4xl">
            <div className="mb-8">
                <SkeletonTitle className="w-48 mb-2" />
                <SkeletonText variant="long" className="w-96 opacity-70" />
            </div>
            <SettingsSectionSkeleton />
            <SettingsSectionSkeleton />
        </div>
    );
}
