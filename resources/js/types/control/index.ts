export type ControlPermission =
    | 'platform.users.read'
    | 'platform.tenants.read'
    | 'platform.workspaces.read'
    | 'platform.api-keys.read'
    | 'platform.memory.read'
    | 'platform.subscriptions.read'
    | 'platform.billing.read'
    | 'platform.billing.catalog.read'
    | 'platform.billing.catalog.write'
    | 'operations.notifications.read'
    | 'operations.jobs.read'
    | 'operations.audit-logs.read'
    | 'operations.analytics.read'
    | 'operations.activity.read'
    | 'support.tickets.read'
    | 'support.communications.read'
    | 'configuration.settings.read'
    | 'configuration.feature-flags.read'
    | 'configuration.system.read'
    | 'security.admins.read'
    | 'security.permissions.read'
    | 'security.sessions.read'
    | 'security.events.read'
    | 'developer.logs.read'
    | 'developer.queues.read'
    | 'developer.background-tasks.read';

export * from './billing';

export interface NavigationItem {
    route_name: string;
    title: string;
    description: string;
    icon: string;
    group: string | null;
    permission?: ControlPermission;
    searchable: boolean;
    pinnable: boolean;
    mobileVisibility: boolean;
    breadcrumb: string;
}

export interface NavigationGroup {
    id: string;
    label: string;
    order: number;
    icon?: string;
    badge?: string;
}
