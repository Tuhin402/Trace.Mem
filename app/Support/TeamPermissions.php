<?php

namespace App\Support;

readonly class TeamPermissions
{
    public function __construct(
        // ── Existing (unchanged) ─────────────────────────────────────────────
        public bool $canUpdateTeam,
        public bool $canDeleteTeam,
        public bool $canAddMember,
        public bool $canUpdateMember,
        public bool $canRemoveMember,
        public bool $canCreateInvitation,
        public bool $canCancelInvitation,
        // ── New workspace permissions ────────────────────────────────────────
        public bool $canCreateApiKey   = false,
        public bool $canViewApiKey     = false,
        public bool $canViewUsage      = false,
        public bool $canViewBilling    = false,
        public bool $canManageBilling  = false,
        public bool $canSuspendWorkspace = false,
    ) {}
}
