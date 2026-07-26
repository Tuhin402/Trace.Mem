<?php

namespace App\Services\Control\Overview;

class OverviewDataService
{
    public function __construct(
        private PlatformHealthService $healthService,
        private MetricsService $metricsService,
        private UsersSnapshotService $usersService,
        private TenantsSnapshotService $tenantsService,
        private WorkspacesSnapshotService $workspacesService,
        private ApiOverviewService $apiService,
        private MemoryOverviewService $memoryService,
        private BillingOverviewService $billingService,
        private JobsOverviewService $jobsService,
        private NotificationOverviewService $notificationService,
        private AuditPreviewService $auditService,
        private SystemActivityService $activityService,
        private AlertsService $alertsService
    ) {}

    public function getPayload(): array
    {
        return [
            'health' => $this->healthService->getHealth(),
            'metrics' => $this->metricsService->getMetrics(),
            'users' => $this->usersService->getUsers(),
            'tenants' => $this->tenantsService->getTenants(),
            'workspaces' => $this->workspacesService->getWorkspaces(),
            'api' => $this->apiService->getApiStats(),
            'memory' => $this->memoryService->getMemoryStats(),
            'billing' => $this->billingService->getBilling(),
            'jobs' => $this->jobsService->getJobs(),
            'notifications' => $this->notificationService->getNotifications(),
            'audit' => $this->auditService->getAuditLogs(),
            'activity' => $this->activityService->getActivity(),
            'alerts' => $this->alertsService->getAlerts(),
        ];
    }
}
