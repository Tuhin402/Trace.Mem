import { SkeletonBase, SkeletonText, SkeletonTitle } from './SkeletonBase';

export function AuthSkeleton() {
    return (
        <div className="space-y-4 pt-4">
            <div className="flex flex-col gap-2">
                <SkeletonText variant="short" className="w-24 h-3" />
                <SkeletonBase className="h-11 w-full rounded-md" />
            </div>
            <div className="flex flex-col gap-2">
                <SkeletonText variant="short" className="w-24 h-3" />
                <SkeletonBase className="h-11 w-full rounded-md" />
            </div>
            <SkeletonBase className="h-11 w-full rounded-md mt-6" />
        </div>
    );
}

export function ContentSectionSkeleton() {
    return (
        <div className="w-full max-w-4xl mx-auto py-12 space-y-8">
            <div className="space-y-4">
                <SkeletonTitle className="h-10 w-2/3" />
                <SkeletonText variant="long" className="opacity-70 h-4" />
                <SkeletonText variant="medium" className="opacity-70 h-4" />
            </div>
            
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mt-12">
                <SkeletonBase className="h-48 rounded-xl" />
                <SkeletonBase className="h-48 rounded-xl" />
                <SkeletonBase className="h-48 rounded-xl" />
                <SkeletonBase className="h-48 rounded-xl" />
            </div>
        </div>
    );
}
