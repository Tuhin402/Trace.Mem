import { Head, usePage } from '@inertiajs/react';
import { Suspense } from 'react';
import { ControlEmptyState } from '@/components/control/ui/ControlEmptyState';
import { ControlPageSkeleton } from '@/components/control/ui/ControlSkeleton';
import { ControlErrorBoundary } from '@/components/control/ui/ControlErrorBoundary';
import { navigationItems } from '@/control/navigation.config';
import * as Icons from 'lucide-react';

export default function Scaffold() {
    const { url } = usePage();
    
    // Find metadata from navigation config based on URL path matching 
    // (since Inertia does not pass route_name by default, though we can match segments)
    // As a simple match:
    const item = navigationItems.find(nav => 
        url.includes(nav.route_name.replace('control.', '').replaceAll('.', '/')) ||
        (nav.route_name === 'control.overview' && url === '/overview')
    );

    const title = item?.title || 'Unknown Module';
    const description = item?.description || 'This module is under development.';
    
    // @ts-ignore
    const Icon = item?.icon ? Icons[item.icon] : Icons.Construction;

    return (
        <ControlErrorBoundary>
            <Suspense fallback={<ControlPageSkeleton />}>
                <Head title={`${title} | TraceMem Control`} />
                
                <div className="py-6">
                    <div className="mb-8">
                        <h1 className="text-2xl font-bold font-heading text-on-background">{title}</h1>
                        {item?.breadcrumb && (
                            <p className="text-xs font-mono text-on-background/50 mt-1 uppercase tracking-wider">
                                {item.breadcrumb}
                            </p>
                        )}
                    </div>

                    <ControlEmptyState 
                        title={`${title} is currently under development`}
                        description={description}
                        icon={<Icon className="h-10 w-10" />}
                    />
                </div>
            </Suspense>
        </ControlErrorBoundary>
    );
}
