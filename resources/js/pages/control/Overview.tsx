import { Head } from '@inertiajs/react';
import { Suspense, lazy } from 'react';
import { ControlPageSkeleton } from '@/components/control/ui/ControlSkeleton';
import { ControlErrorBoundary } from '@/components/control/ui/ControlErrorBoundary';

// We will statically import the critical fold components
import OverviewHeader from '@/components/control/overview/OverviewHeader';
import PlatformHealthSection from '@/components/control/overview/PlatformHealthSection';
import ExecutiveMetricsSection from '@/components/control/overview/ExecutiveMetricsSection';
import SystemActivitySection from '@/components/control/overview/SystemActivitySection';
import AlertsSection from '@/components/control/overview/AlertsSection';

// We will lazy-load the snapshot and preview components below the fold
const UsersSnapshotSection = lazy(() => import('@/components/control/overview/UsersSnapshotSection'));
const TenantsSnapshotSection = lazy(() => import('@/components/control/overview/TenantsSnapshotSection'));
const WorkspacesSnapshotSection = lazy(() => import('@/components/control/overview/WorkspacesSnapshotSection'));
const ApiOverviewSection = lazy(() => import('@/components/control/overview/ApiOverviewSection'));
const MemoryOverviewSection = lazy(() => import('@/components/control/overview/MemoryOverviewSection'));
const BillingOverviewSection = lazy(() => import('@/components/control/overview/BillingOverviewSection'));
const JobsOverviewSection = lazy(() => import('@/components/control/overview/JobsOverviewSection'));
const NotificationsPreviewSection = lazy(() => import('@/components/control/overview/NotificationsPreviewSection'));
const AuditPreviewSection = lazy(() => import('@/components/control/overview/AuditPreviewSection'));
const ActivityFeedSection = lazy(() => import('@/components/control/overview/ActivityFeedSection'));
const QuickActionsSection = lazy(() => import('@/components/control/overview/QuickActionsSection'));
const DocumentationSection = lazy(() => import('@/components/control/overview/DocumentationSection'));

export default function Overview({ health, metrics }: any) {
    return (
        <ControlErrorBoundary>
            <Head title="Operations Overview | TraceMem Control" />

            <div className="w-full space-y-8 pb-24">

                {/* 1. Overview Header */}
                <OverviewHeader />

                {/* 2. Platform Health (Full Width) */}
                <PlatformHealthSection data={health} />

                {/* 3. Executive Metrics */}
                <ExecutiveMetricsSection data={metrics} />

                {/* 4. Active Alerts */}
                <AlertsSection />

                <div className="grid grid-cols-1 xl:grid-cols-3 gap-8">
                    {/* Main Content Column (2/3 width on xl) */}
                    <div className="xl:col-span-2 space-y-8">
                        {/* 5. Memory Overview (Core Value Proposition) */}
                        <Suspense fallback={<ControlPageSkeleton />}><MemoryOverviewSection /></Suspense>

                        {/* 6. System Activity Feed */}
                        <SystemActivitySection />

                        <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                            {/* 7. Workspaces Snapshot */}
                            <Suspense fallback={<ControlPageSkeleton />}><WorkspacesSnapshotSection /></Suspense>

                            {/* 8. API Overview */}
                            <Suspense fallback={<ControlPageSkeleton />}><ApiOverviewSection /></Suspense>
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                            {/* 9. Tenants Snapshot */}
                            <Suspense fallback={<ControlPageSkeleton />}><TenantsSnapshotSection /></Suspense>

                            {/* 10. Users Snapshot */}
                            <Suspense fallback={<ControlPageSkeleton />}><UsersSnapshotSection /></Suspense>
                        </div>

                        {/* 11. Jobs & Queues */}
                        <Suspense fallback={<ControlPageSkeleton />}><JobsOverviewSection /></Suspense>

                        {/* 12. Billing Snapshot */}
                        <Suspense fallback={<ControlPageSkeleton />}><BillingOverviewSection /></Suspense>

                        {/* 13. Audit Preview */}
                        <Suspense fallback={<ControlPageSkeleton />}><AuditPreviewSection /></Suspense>
                    </div>

                    {/* Right Sidebar Column (1/3 width on xl) */}
                    <div className="space-y-8">
                        {/* 14. Quick Actions */}
                        <Suspense fallback={<ControlPageSkeleton />}><QuickActionsSection /></Suspense>

                        {/* 15. Notifications Preview */}
                        <Suspense fallback={<ControlPageSkeleton />}><NotificationsPreviewSection /></Suspense>

                        {/* 16. Full Activity Feed */}
                        <Suspense fallback={<ControlPageSkeleton />}><ActivityFeedSection /></Suspense>

                        {/* 17. Documentation */}
                        <Suspense fallback={<ControlPageSkeleton />}><DocumentationSection /></Suspense>
                    </div>
                </div>

            </div>
        </ControlErrorBoundary>
    );
}
