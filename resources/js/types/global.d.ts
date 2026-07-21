import type { Auth } from '@/types/auth';
import type { Team, WorkspaceContext, AccountContext } from '@/types/teams';

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            sidebarOpen: boolean;
            // Legacy team props (preserved for backward compat with existing components)
            currentTeam: Team | null;
            teams: Team[];
            // Workspace context (Phase C onwards)
            workspace: WorkspaceContext | null;
            account: AccountContext | null;
            [key: string]: unknown;
        };
    }
}
