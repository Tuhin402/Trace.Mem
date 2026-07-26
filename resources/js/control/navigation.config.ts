import { NavigationGroup, NavigationItem } from '@/types/control';

export const navigationGroups: NavigationGroup[] = [
    { id: 'platform', label: 'PLATFORM', order: 1 },
    { id: 'operations', label: 'OPERATIONS', order: 2 },
    { id: 'support', label: 'SUPPORT', order: 3 },
    { id: 'configuration', label: 'CONFIGURATION', order: 4 },
    { id: 'security', label: 'SECURITY', order: 5 },
    { id: 'developer', label: 'DEVELOPER', order: 6 },
];

export const navigationItems: NavigationItem[] = [
    // Overview
    {
        route_name: 'control.overview',
        title: 'Overview',
        description: 'High-level metrics and system status.',
        icon: 'LayoutDashboard',
        group: null,
        searchable: false,
        pinnable: true,
        mobileVisibility: true,
        breadcrumb: 'Overview'
    },
    
    // Platform
    {
        route_name: 'control.platform.users',
        title: 'Users',
        description: 'Manage system users and administrators.',
        icon: 'Users',
        group: 'platform',
        permission: 'platform.users.read',
        searchable: true,
        pinnable: true,
        mobileVisibility: true,
        breadcrumb: 'Platform / Users'
    },
    {
        route_name: 'control.platform.tenants',
        title: 'Tenants',
        description: 'Oversee all company tenants.',
        icon: 'Database',
        group: 'platform',
        permission: 'platform.tenants.read',
        searchable: true,
        pinnable: true,
        mobileVisibility: true,
        breadcrumb: 'Platform / Tenants'
    },
    {
        route_name: 'control.platform.workspaces',
        title: 'Workspaces',
        description: 'Manage individual workspaces within tenants.',
        icon: 'Layout',
        group: 'platform',
        permission: 'platform.workspaces.read',
        searchable: true,
        pinnable: true,
        mobileVisibility: true,
        breadcrumb: 'Platform / Workspaces'
    },
    {
        route_name: 'control.platform.api-keys',
        title: 'API Keys',
        description: 'Global API key management and revocation.',
        icon: 'Key',
        group: 'platform',
        permission: 'platform.api-keys.read',
        searchable: true,
        pinnable: true,
        mobileVisibility: true,
        breadcrumb: 'Platform / API Keys'
    },
    {
        route_name: 'control.platform.memory',
        title: 'Memory',
        description: 'Inspect global memory nodes and vectors.',
        icon: 'Brain',
        group: 'platform',
        permission: 'platform.memory.read',
        searchable: true,
        pinnable: true,
        mobileVisibility: true,
        breadcrumb: 'Platform / Memory'
    },
    {
        route_name: 'control.platform.subscriptions',
        title: 'Subscriptions',
        description: 'Manage active subscriptions and limits.',
        icon: 'Repeat',
        group: 'platform',
        permission: 'platform.subscriptions.read',
        searchable: true,
        pinnable: true,
        mobileVisibility: true,
        breadcrumb: 'Platform / Subscriptions'
    },
    {
        route_name: 'control.platform.billing',
        title: 'Billing',
        description: 'Track revenue, invoices, and trials.',
        icon: 'CreditCard',
        group: 'platform',
        permission: 'platform.billing.read',
        searchable: true,
        pinnable: true,
        mobileVisibility: true,
        breadcrumb: 'Platform / Billing'
    },

    // Operations
    {
        route_name: 'control.operations.notifications',
        title: 'Notifications',
        description: 'System-wide alerts and broadcasts.',
        icon: 'Bell',
        group: 'operations',
        permission: 'operations.notifications.read',
        searchable: true,
        pinnable: true,
        mobileVisibility: true,
        breadcrumb: 'Operations / Notifications'
    },
    {
        route_name: 'control.operations.jobs',
        title: 'Jobs',
        description: 'Monitor active and failed queued jobs.',
        icon: 'ListTodo',
        group: 'operations',
        permission: 'operations.jobs.read',
        searchable: true,
        pinnable: true,
        mobileVisibility: true,
        breadcrumb: 'Operations / Jobs'
    },
    {
        route_name: 'control.operations.audit-logs',
        title: 'Audit Logs',
        description: 'Security and administrative audit trails.',
        icon: 'History',
        group: 'operations',
        permission: 'operations.audit-logs.read',
        searchable: true,
        pinnable: true,
        mobileVisibility: true,
        breadcrumb: 'Operations / Audit Logs'
    },
    {
        route_name: 'control.operations.analytics',
        title: 'Analytics',
        description: 'Platform usage metrics and trends.',
        icon: 'BarChart2',
        group: 'operations',
        permission: 'operations.analytics.read',
        searchable: false,
        pinnable: true,
        mobileVisibility: true,
        breadcrumb: 'Operations / Analytics'
    },
    {
        route_name: 'control.operations.activity',
        title: 'Activity',
        description: 'Real-time user activity across the platform.',
        icon: 'Activity',
        group: 'operations',
        permission: 'operations.activity.read',
        searchable: true,
        pinnable: true,
        mobileVisibility: true,
        breadcrumb: 'Operations / Activity'
    },

    // Support
    {
        route_name: 'control.support.tickets',
        title: 'Support Tickets',
        description: 'Manage incoming user support requests.',
        icon: 'LifeBuoy',
        group: 'support',
        permission: 'support.tickets.read',
        searchable: true,
        pinnable: true,
        mobileVisibility: true,
        breadcrumb: 'Support / Tickets'
    },
    {
        route_name: 'control.support.communications',
        title: 'Communications',
        description: 'Email broadcasts and announcements.',
        icon: 'Mail',
        group: 'support',
        permission: 'support.communications.read',
        searchable: true,
        pinnable: true,
        mobileVisibility: true,
        breadcrumb: 'Support / Communications'
    },

    // Configuration
    {
        route_name: 'control.configuration.settings',
        title: 'Platform Settings',
        description: 'Core system variables and toggles.',
        icon: 'Settings',
        group: 'configuration',
        permission: 'configuration.settings.read',
        searchable: true,
        pinnable: true,
        mobileVisibility: true,
        breadcrumb: 'Configuration / Settings'
    },
    {
        route_name: 'control.configuration.feature-flags',
        title: 'Feature Flags',
        description: 'Granular rollout and beta feature toggles.',
        icon: 'Flag',
        group: 'configuration',
        permission: 'configuration.feature-flags.read',
        searchable: true,
        pinnable: true,
        mobileVisibility: true,
        breadcrumb: 'Configuration / Feature Flags'
    },
    {
        route_name: 'control.configuration.system',
        title: 'System Configuration',
        description: 'Low-level infrastructure settings.',
        icon: 'Server',
        group: 'configuration',
        permission: 'configuration.system.read',
        searchable: true,
        pinnable: true,
        mobileVisibility: true,
        breadcrumb: 'Configuration / System'
    },

    // Security
    {
        route_name: 'control.security.admins',
        title: 'Admins',
        description: 'Manage Operations Console administrators.',
        icon: 'ShieldCheck',
        group: 'security',
        permission: 'security.admins.read',
        searchable: true,
        pinnable: true,
        mobileVisibility: true,
        breadcrumb: 'Security / Admins'
    },
    {
        route_name: 'control.security.permissions',
        title: 'Permissions',
        description: 'Configure RBAC roles and policies.',
        icon: 'Lock',
        group: 'security',
        permission: 'security.permissions.read',
        searchable: true,
        pinnable: true,
        mobileVisibility: true,
        breadcrumb: 'Security / Permissions'
    },
    {
        route_name: 'control.security.sessions',
        title: 'Sessions',
        description: 'Monitor active administrative sessions.',
        icon: 'Laptop',
        group: 'security',
        permission: 'security.sessions.read',
        searchable: true,
        pinnable: true,
        mobileVisibility: true,
        breadcrumb: 'Security / Sessions'
    },
    {
        route_name: 'control.security.events',
        title: 'Security Events',
        description: 'Intrusion attempts and blocked actions.',
        icon: 'AlertTriangle',
        group: 'security',
        permission: 'security.events.read',
        searchable: true,
        pinnable: true,
        mobileVisibility: true,
        breadcrumb: 'Security / Events'
    },

    // Developer
    {
        route_name: 'control.developer.logs',
        title: 'Logs',
        description: 'Application and system logs tailing.',
        icon: 'Terminal',
        group: 'developer',
        permission: 'developer.logs.read',
        searchable: true,
        pinnable: true,
        mobileVisibility: true,
        breadcrumb: 'Developer / Logs'
    },
    {
        route_name: 'control.developer.queues',
        title: 'Queues',
        description: 'Redis queue inspection and management.',
        icon: 'Layers',
        group: 'developer',
        permission: 'developer.queues.read',
        searchable: true,
        pinnable: true,
        mobileVisibility: true,
        breadcrumb: 'Developer / Queues'
    },
    {
        route_name: 'control.developer.background-tasks',
        title: 'Background Tasks',
        description: 'Scheduled cron jobs and maintenance.',
        icon: 'Clock',
        group: 'developer',
        permission: 'developer.background-tasks.read',
        searchable: true,
        pinnable: true,
        mobileVisibility: true,
        breadcrumb: 'Developer / Background Tasks'
    }
];
