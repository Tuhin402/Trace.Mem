import { Head, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import {
    Building2,
    Plus,
    Archive,
    Check,
    Pencil,
    X,
    Loader2,
    Lock,
} from 'lucide-react';
import type { WorkspaceContext } from '@/types';

/* ── Types ──────────────────────────────────────────────────────────────── */
type WorkspaceRow = {
    id: number;
    name: string;
    slug: string;
    status: string;
    environment: string | null;
    purpose: string | null;
    isDefault: boolean;
    isLocked: boolean;
    isCurrent: boolean;
    memberCount: number;
    canManageMembers: boolean;
};

type PageProps = {
    workspaces: WorkspaceRow[];
    flash: { message?: string; error?: string };
};

/* ── Status badge ────────────────────────────────────────────────────────── */
function StatusBadge({ status }: { status: string }) {
    const styles: Record<string, string> = {
        active:   'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400',
        archived: 'bg-amber-500/10 text-amber-600 dark:text-amber-400',
        suspended:'bg-red-500/10 text-red-600 dark:text-red-400',
    };
    return (
        <span className={`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium capitalize ${styles[status] ?? 'bg-muted text-muted-foreground'}`}>
            {status}
        </span>
    );
}

/* ── Environment badge ───────────────────────────────────────────────────── */
function EnvBadge({ env }: { env: string | null }) {
    if (!env) return null;
    const styles: Record<string, string> = {
        production:  'bg-blue-500/10 text-blue-600 dark:text-blue-400',
        staging:     'bg-purple-500/10 text-purple-600 dark:text-purple-400',
        development: 'bg-zinc-500/10 text-zinc-500',
        testing:     'bg-orange-500/10 text-orange-600 dark:text-orange-400',
    };
    return (
        <span className={`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium capitalize ${styles[env] ?? 'bg-muted text-muted-foreground'}`}>
            {env}
        </span>
    );
}

/* ── Create workspace modal ──────────────────────────────────────────────── */
function CreateWorkspaceModal({ onClose }: { onClose: () => void }) {
    const [name, setName] = useState('');
    const [purpose, setPurpose] = useState('');
    const [environment, setEnvironment] = useState('');
    const [submitting, setSubmitting] = useState(false);

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        setSubmitting(true);
        router.post(
            '/workspaces',
            { name, purpose, environment: environment || null },
            {
                onFinish: () => setSubmitting(false),
                onSuccess: () => onClose(),
            }
        );
    };

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
            <div className="w-full max-w-md rounded-xl border border-border bg-card shadow-2xl">
                <div className="flex items-center justify-between border-b border-border px-6 py-4">
                    <h2 className="text-base font-semibold">Create Workspace</h2>
                    <button onClick={onClose} className="rounded-md p-1 text-muted-foreground hover:text-foreground">
                        <X size={16} />
                    </button>
                </div>

                <form onSubmit={submit} className="space-y-4 p-6">
                    <div>
                        <label className="mb-1.5 block text-sm font-medium">
                            Workspace name <span className="text-destructive">*</span>
                        </label>
                        <input
                            type="text"
                            value={name}
                            onChange={e => setName(e.target.value)}
                            required
                            maxLength={255}
                            placeholder="e.g. Staging, Production, Client A"
                            className="w-full rounded-lg border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
                        />
                    </div>

                    <div>
                        <label className="mb-1.5 block text-sm font-medium">Environment</label>
                        <select
                            value={environment}
                            onChange={e => setEnvironment(e.target.value)}
                            className="w-full rounded-lg border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
                        >
                            <option value="">— None —</option>
                            <option value="development">Development</option>
                            <option value="staging">Staging</option>
                            <option value="production">Production</option>
                            <option value="testing">Testing</option>
                        </select>
                    </div>

                    <div>
                        <label className="mb-1.5 block text-sm font-medium">Purpose</label>
                        <textarea
                            value={purpose}
                            onChange={e => setPurpose(e.target.value)}
                            maxLength={500}
                            rows={2}
                            placeholder="Optional description of what this workspace is for"
                            className="w-full resize-none rounded-lg border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
                        />
                    </div>

                    <p className="text-xs text-muted-foreground">
                        A test API key will be generated automatically. Live keys are created manually.
                    </p>

                    <div className="flex justify-end gap-2 pt-2">
                        <button
                            type="button"
                            onClick={onClose}
                            className="rounded-lg border border-border px-4 py-2 text-sm hover:bg-muted"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            disabled={submitting || !name.trim()}
                            className="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 disabled:opacity-50"
                        >
                            {submitting && <Loader2 size={14} className="animate-spin" />}
                            Create Workspace
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
}

/* ── Workspace row ───────────────────────────────────────────────────────── */
function WorkspaceRow({ workspace }: { workspace: WorkspaceRow }) {
    const [editing, setEditing] = useState(false);
    const [newName, setNewName] = useState(workspace.name);
    const [saving, setSaving] = useState(false);
    const [archiving, setArchiving] = useState(false);
    const [switching, setSwitching] = useState(false);

    const handleRename = (e: React.FormEvent) => {
        e.preventDefault();
        if (!newName.trim() || newName === workspace.name) {
            setEditing(false);
            return;
        }
        setSaving(true);
        router.patch(
            `/workspaces/${workspace.slug}`,
            { name: newName },
            {
                onFinish: () => { setSaving(false); setEditing(false); },
            }
        );
    };

    const handleSwitch = () => {
        if (workspace.isCurrent) return;
        setSwitching(true);
        router.post(
            `/workspaces/${workspace.slug}/switch`,
            {},
            {
                onFinish: () => setSwitching(false),
                preserveScroll: false,
            }
        );
    };

    const handleArchive = () => {
        if (!confirm(`Archive "${workspace.name}"? This cannot be undone from the dashboard.`)) return;
        setArchiving(true);
        router.post(
            `/workspaces/${workspace.slug}/archive`,
            {},
            { onFinish: () => setArchiving(false) }
        );
    };

    return (
        <div className={`workspace-row group rounded-xl border border-border bg-card p-4 transition-all hover:border-ring/30 ${workspace.isCurrent ? 'border-ring/40 bg-card/80' : ''}`}>
            <div className="flex items-start justify-between gap-4">
                {/* Left: name + badges */}
                <div className="min-w-0 flex-1">
                    {editing ? (
                        <form onSubmit={handleRename} className="flex items-center gap-2">
                            <input
                                autoFocus
                                type="text"
                                value={newName}
                                onChange={e => setNewName(e.target.value)}
                                maxLength={255}
                                className="rounded-md border border-input bg-background px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
                            />
                            <button type="submit" disabled={saving} className="rounded p-1 text-emerald-500 hover:text-emerald-400">
                                {saving ? <Loader2 size={14} className="animate-spin" /> : <Check size={14} />}
                            </button>
                            <button type="button" onClick={() => { setEditing(false); setNewName(workspace.name); }} className="rounded p-1 text-muted-foreground hover:text-foreground">
                                <X size={14} />
                            </button>
                        </form>
                    ) : (
                        <div className="flex flex-wrap items-center gap-2">
                            <span className="text-sm font-semibold">{workspace.name}</span>
                            {workspace.isCurrent && (
                                <span className="inline-flex items-center gap-1 rounded-full bg-primary/10 px-2 py-0.5 text-xs font-medium text-primary">
                                    <Check size={10} /> Current
                                </span>
                            )}
                            {workspace.isLocked && (
                                <span className="inline-flex items-center gap-1 rounded-full bg-muted px-2 py-0.5 text-xs text-muted-foreground">
                                    <Lock size={10} /> Default
                                </span>
                            )}
                            <StatusBadge status={workspace.status} />
                            <EnvBadge env={workspace.environment} />
                        </div>
                    )}

                    <div className="mt-1 flex flex-wrap items-center gap-3 text-xs text-muted-foreground">
                        <span className="font-mono">{workspace.slug}</span>
                        <span>{workspace.memberCount} {workspace.memberCount === 1 ? 'member' : 'members'}</span>
                        {workspace.purpose && (
                            <span className="truncate max-w-xs">{workspace.purpose}</span>
                        )}
                    </div>
                </div>

                {/* Right: actions */}
                <div className="flex shrink-0 items-center gap-1">
                    {!workspace.isCurrent && workspace.status === 'active' && (
                        <button
                            onClick={handleSwitch}
                            disabled={switching}
                            title="Switch to this workspace"
                            className="rounded-lg border border-border px-3 py-1.5 text-xs hover:bg-muted disabled:opacity-50"
                        >
                            {switching ? <Loader2 size={12} className="animate-spin" /> : 'Switch'}
                        </button>
                    )}

                    {!workspace.isLocked && !editing && (
                        <button
                            onClick={() => setEditing(true)}
                            title="Rename workspace"
                            className="rounded-lg p-1.5 text-muted-foreground hover:text-foreground hover:bg-muted"
                        >
                            <Pencil size={13} />
                        </button>
                    )}

                    {workspace.canManageMembers && (
                        <button
                            onClick={() => router.get(`/workspaces/${workspace.slug}/members`)}
                            title="Manage members"
                            className="rounded-lg p-1.5 text-muted-foreground hover:text-primary hover:bg-primary/10"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        </button>
                    )}

                    {!workspace.isLocked && workspace.status === 'active' && (
                        <button
                            onClick={handleArchive}
                            disabled={archiving}
                            title="Archive workspace"
                            className="rounded-lg p-1.5 text-muted-foreground hover:text-amber-500 hover:bg-amber-500/10 disabled:opacity-50"
                        >
                            {archiving ? <Loader2 size={13} className="animate-spin" /> : <Archive size={13} />}
                        </button>
                    )}
                </div>
            </div>
        </div>
    );
}

/* ── Page ────────────────────────────────────────────────────────────────── */
export default function Workspaces({ workspaces, flash }: PageProps) {
    const [showCreate, setShowCreate] = useState(false);

    return (
        <>
            <Head title="Workspaces — Trace.Mem" />

            <div className="mx-auto max-w-4xl px-4 py-8 sm:px-6">

                {/* Flash */}
                {flash?.message && (
                    <div className="mb-6 flex items-center gap-2 rounded-lg border border-emerald-500/20 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-600 dark:text-emerald-400">
                        <Check size={14} />
                        {flash.message}
                    </div>
                )}
                {flash?.error && (
                    <div className="mb-6 rounded-lg border border-destructive/20 bg-destructive/10 px-4 py-3 text-sm text-destructive">
                        {flash.error}
                    </div>
                )}

                {/* Header */}
                <div className="mb-8 flex items-center justify-between">
                    <div>
                        <h1 className="flex items-center gap-2.5 text-2xl font-bold tracking-tight">
                            <Building2 className="size-6 text-primary" />
                            Workspaces
                        </h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Manage your workspaces. Each workspace has its own API keys and memory scope.
                        </p>
                    </div>
                    <button
                        onClick={() => setShowCreate(true)}
                        className="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow-sm hover:bg-primary/90 transition-colors"
                    >
                        <Plus size={14} />
                        New Workspace
                    </button>
                </div>

                {/* Workspace list */}
                <div className="space-y-3">
                    {workspaces.length === 0 ? (
                        <div className="rounded-xl border border-dashed border-border p-12 text-center text-muted-foreground">
                            <Building2 className="mx-auto mb-3 size-8 opacity-30" />
                            <p className="text-sm">No workspaces yet. Create your first one above.</p>
                        </div>
                    ) : (
                        workspaces.map(ws => (
                            <WorkspaceRow key={ws.id} workspace={ws} />
                        ))
                    )}
                </div>

                {/* Info box */}
                <div className="mt-8 rounded-lg border border-border bg-muted/30 p-4 text-xs text-muted-foreground">
                    <strong className="text-foreground">Workspace rules:</strong>
                    <ul className="mt-2 space-y-1 list-disc list-inside">
                        <li>The <strong>Default</strong> workspace cannot be renamed to empty, archived, or deleted.</li>
                        <li>Each new workspace automatically gets one test API key. Live keys are created manually.</li>
                        <li>Archiving a workspace does not delete its API keys or memories — they become read-only.</li>
                        <li>Workspace slugs are immutable — they never change, even after renaming.</li>
                    </ul>
                </div>
            </div>

            {showCreate && <CreateWorkspaceModal onClose={() => setShowCreate(false)} />}
        </>
    );
}
