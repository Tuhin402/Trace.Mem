<?php

namespace App\Services\Workspace;

use App\Models\Team;
use App\Models\User;
use App\Models\WorkspaceAuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WorkspaceAuditService
{
    /**
     * Write an audit log entry for a workspace action.
     *
     * This is fire-and-forget — exceptions are caught and logged to the
     * application log, never surfaced to the caller.
     *
     * @param  Team         $workspace  The workspace being acted upon
     * @param  User|null    $actor      The user performing the action (null = system)
     * @param  string       $action     Dot-namespaced action identifier
     *                                  e.g. 'workspace.created', 'member.invited', 'apikey.rotated'
     * @param  array        $metadata   Event-specific data (will be stored as jsonb)
     * @param  Request|null $request    Current HTTP request for ip/user-agent capture
     */
    public function log(
        Team $workspace,
        ?User $actor,
        string $action,
        array $metadata = [],
        ?Request $request = null,
    ): void {
        try {
            $request ??= app()->runningInConsole() ? null : request();

            WorkspaceAuditLog::create([
                'workspace_id'  => $workspace->id,
                'actor_user_id' => $actor?->id,
                'action'        => $action,
                'subject_type'  => null,
                'subject_id'    => null,
                'ip_address'    => $request?->ip(),
                'user_agent'    => $request?->userAgent(),
                'metadata'      => $metadata ?: null,
            ]);
        } catch (\Throwable $e) {
            Log::warning('WorkspaceAuditService: failed to write audit log', [
                'workspace_id' => $workspace->id,
                'action'       => $action,
                'error'        => $e->getMessage(),
            ]);
        }
    }

    /**
     * Log with an explicit subject (e.g. when acting on a specific user or API key).
     */
    public function logWithSubject(
        Team $workspace,
        ?User $actor,
        string $action,
        string $subjectType,
        string|int $subjectId,
        array $metadata = [],
        ?Request $request = null,
    ): void {
        try {
            $request ??= app()->runningInConsole() ? null : request();

            WorkspaceAuditLog::create([
                'workspace_id'  => $workspace->id,
                'actor_user_id' => $actor?->id,
                'action'        => $action,
                'subject_type'  => $subjectType,
                'subject_id'    => (string) $subjectId,
                'ip_address'    => $request?->ip(),
                'user_agent'    => $request?->userAgent(),
                'metadata'      => $metadata ?: null,
            ]);
        } catch (\Throwable $e) {
            Log::warning('WorkspaceAuditService: failed to write audit log (with subject)', [
                'workspace_id' => $workspace->id,
                'action'       => $action,
                'subject_type' => $subjectType,
                'subject_id'   => $subjectId,
                'error'        => $e->getMessage(),
            ]);
        }
    }
}
