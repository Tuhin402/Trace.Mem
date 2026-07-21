// ── Role types ──────────────────────────────────────────────────────────────
export type TeamRole = 'owner' | 'admin' | 'member' | 'developer' | 'viewer';

// ── Workspace status ─────────────────────────────────────────────────────────
export type WorkspaceStatus = 'active' | 'archived' | 'suspended' | 'none';

export type WorkspaceEnvironment = 'development' | 'staging' | 'production' | 'testing' | null;

// ── Basic workspace shape (used in switcher lists) ────────────────────────────
export type Team = {
    id: number;
    name: string;
    slug: string;
    isPersonal: boolean;
    role?: TeamRole;
    roleLabel?: string;
    isCurrent?: boolean;
};

// ── Full workspace context shape (shared by HandleInertiaRequests) ────────────
export type WorkspaceContext = {
    id: number | null;
    name: string | null;
    slug: string | null;
    status: WorkspaceStatus;
    environment?: WorkspaceEnvironment;
    isDefault?: boolean;
    isLocked?: boolean;
    memberships?: WorkspaceMember[];
    all?: WorkspaceSwitcherItem[];
};

// ── Workspace switcher item (minimal — for the dropdown) ─────────────────────
export type WorkspaceSwitcherItem = {
    id: number;
    name: string;
    slug: string;
    isCurrent: boolean;
};

// ── Workspace member ─────────────────────────────────────────────────────────
export type WorkspaceMember = {
    user_id: number;
    name: string | null;
    email: string | null;
    role: TeamRole | null;
};

// ── Account context (shared by HandleInertiaRequests) ────────────────────────
export type AccountContext = {
    type: 'individual' | 'tenant';
    isIndividual: boolean;
    isCompany: boolean;
};

// ── Team member (detail view) ────────────────────────────────────────────────
export type TeamMember = {
    id: number;
    name: string;
    email: string;
    avatar?: string | null;
    role: TeamRole;
    role_label: string;
};

export type TeamInvitation = {
    code: string;
    email: string;
    role: TeamRole;
    role_label: string;
    created_at: string;
};

export type TeamPermissions = {
    canUpdateTeam: boolean;
    canDeleteTeam: boolean;
    canAddMember: boolean;
    canUpdateMember: boolean;
    canRemoveMember: boolean;
    canCreateInvitation: boolean;
    canCancelInvitation: boolean;
    // Workspace permissions (Phase B additions)
    canCreateApiKey?: boolean;
    canViewApiKey?: boolean;
    canViewUsage?: boolean;
    canViewBilling?: boolean;
    canManageBilling?: boolean;
    canSuspendWorkspace?: boolean;
};

export type RoleOption = {
    value: TeamRole;
    label: string;
};
