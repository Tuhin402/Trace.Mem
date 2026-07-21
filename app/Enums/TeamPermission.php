<?php

namespace App\Enums;

enum TeamPermission: string
{
    // ── Team management ───────────────────────────────────────────────────────
    case UpdateTeam   = 'team:update';
    case DeleteTeam   = 'team:delete';

    // ── Member management ─────────────────────────────────────────────────────
    case AddMember    = 'member:add';
    case UpdateMember = 'member:update';
    case RemoveMember = 'member:remove';

    // ── Invitation management ─────────────────────────────────────────────────
    case CreateInvitation = 'invitation:create';
    case CancelInvitation = 'invitation:cancel';

    // ── API Key management ────────────────────────────────────────────────────
    case CreateApiKey = 'apikey:create';
    case ViewApiKey   = 'apikey:view';

    // ── Usage & analytics ─────────────────────────────────────────────────────
    case ViewUsage    = 'usage:view';

    // ── Billing ───────────────────────────────────────────────────────────────
    case ViewBilling   = 'billing:view';
    case ManageBilling = 'billing:manage';

    // ── Workspace admin ───────────────────────────────────────────────────────
    case SuspendWorkspace = 'workspace:suspend';
}
