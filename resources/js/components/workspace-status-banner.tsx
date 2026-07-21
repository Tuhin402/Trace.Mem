import { usePage } from '@inertiajs/react';
import { AlertTriangle, Archive } from 'lucide-react';
import type { WorkspaceContext } from '@/types';

/**
 * WorkspaceStatusBanner
 *
 * Shown at the top of the app when the current workspace is:
 *  - archived  → informational amber banner
 *  - suspended → critical red banner
 *
 * Hidden completely for Individual accounts and active workspaces.
 * Only Company accounts with a non-active workspace see this.
 */
export function WorkspaceStatusBanner() {
    const page = usePage();
    const workspace = page.props.workspace as WorkspaceContext | null;
    const account = page.props.account;

    if (!account?.isCompany || !workspace?.id) return null;
    if (workspace.status === 'active') return null;

    if (workspace.status === 'archived') {
        return (
            <div className="flex items-center gap-2 bg-amber-500/10 border-b border-amber-500/20 px-4 py-2 text-sm text-amber-600 dark:text-amber-400">
                <Archive className="size-4 shrink-0" />
                <span>
                    This workspace is <strong>archived</strong>. API keys and memories are read-only.
                    Contact your workspace owner to restore it.
                </span>
            </div>
        );
    }

    if (workspace.status === 'suspended') {
        return (
            <div className="flex items-center gap-2 bg-red-500/10 border-b border-red-500/20 px-4 py-2 text-sm text-red-600 dark:text-red-400">
                <AlertTriangle className="size-4 shrink-0" />
                <span>
                    This workspace is <strong>suspended</strong>. All API requests are blocked.
                    Please contact support.
                </span>
            </div>
        );
    }

    return null;
}
