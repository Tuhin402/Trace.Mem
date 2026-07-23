import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import {
    Users,
    Mail,
    Trash2,
    X,
    Loader2,
    Plus,
    Clock,
    Check
} from 'lucide-react';

/* ── Types ──────────────────────────────────────────────────────────────── */
type Member = {
    id: number;
    name: string;
    email: string;
    role: string;
    joined_at: string;
};

type Invitation = {
    id: number;
    email: string;
    role: string;
    created_at: string;
    expires_at: string;
    is_expired: boolean;
};

type PageProps = {
    targetWorkspace: {
        id: number;
        name: string;
        slug: string;
    };
    members: Member[];
    invitations: Invitation[];
    flash: { message?: string; error?: string };
};

/* ── Invite Modal ───────────────────────────────────────────────────────── */
function InviteMemberModal({ workspaceSlug, onClose }: { workspaceSlug: string, onClose: () => void }) {
    const [email, setEmail] = useState('');
    const [role, setRole] = useState('member');
    const [submitting, setSubmitting] = useState(false);

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        setSubmitting(true);
        router.post(
            `/workspaces/${workspaceSlug}/invitations`,
            { email, role },
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
                    <h2 className="text-base font-semibold">Invite Member</h2>
                    <button onClick={onClose} className="rounded-md p-1 text-muted-foreground hover:text-foreground">
                        <X size={16} />
                    </button>
                </div>

                <form onSubmit={submit} className="space-y-4 p-6">
                    <div>
                        <label className="mb-1.5 block text-sm font-medium">Email address</label>
                        <input
                            type="email"
                            value={email}
                            onChange={e => setEmail(e.target.value)}
                            required
                            placeholder="colleague@company.com"
                            className="w-full rounded-lg border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
                        />
                    </div>

                    <div>
                        <label className="mb-1.5 block text-sm font-medium">Role</label>
                        <select
                            value={role}
                            onChange={e => setRole(e.target.value)}
                            className="w-full rounded-lg border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
                        >
                            <option value="admin">Admin</option>
                            <option value="developer">Developer</option>
                            <option value="member">Member</option>
                            <option value="viewer">Viewer</option>
                        </select>
                        <p className="mt-2 text-xs text-muted-foreground">
                            Admins can manage members. Developers can create API keys. Viewers have read-only access.
                        </p>
                    </div>

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
                            disabled={submitting || !email.trim()}
                            className="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 disabled:opacity-50"
                        >
                            {submitting && <Loader2 size={14} className="animate-spin" />}
                            Send Invitation
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
}

/* ── Page ────────────────────────────────────────────────────────────────── */
export default function WorkspaceMembers({ targetWorkspace, members, invitations, flash }: PageProps) {
    const [showInvite, setShowInvite] = useState(false);
    const [processingId, setProcessingId] = useState<number | null>(null);

    const handleRemoveMember = (id: number, name: string) => {
        if (!confirm(`Remove ${name} from the workspace?`)) return;
        setProcessingId(id);
        router.delete(`/workspaces/${targetWorkspace.slug}/members/${id}`, {
            onFinish: () => setProcessingId(null)
        });
    };

    const handleCancelInvite = (id: number, email: string) => {
        if (!confirm(`Cancel invitation for ${email}?`)) return;
        setProcessingId(id);
        router.delete(`/workspaces/${targetWorkspace.slug}/invitations/${id}`, {
            onFinish: () => setProcessingId(null)
        });
    };

    return (
        <>
            <Head title={`Members — ${targetWorkspace.name}`} />

            <div className="mx-auto max-w-4xl px-4 py-8 sm:px-6">

                {/* Back Link */}
                <div className="mb-4">
                    <button
                        onClick={() => router.get('/workspaces')}
                        className="text-sm text-muted-foreground hover:text-foreground flex items-center gap-1"
                    >
                        &larr; Back to Workspaces
                    </button>
                </div>

                {/* Flash */}
                {flash?.message && (
                    <div className="mb-6 flex items-center gap-2 rounded-lg border border-emerald-500/20 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-600 dark:text-emerald-400">
                        <Check size={14} />
                        {flash.message}
                    </div>
                )}
                {flash?.error && (
                    <div className="mb-6 flex items-center gap-2 rounded-lg border border-destructive/20 bg-destructive/10 px-4 py-3 text-sm text-destructive">
                        {flash.error}
                    </div>
                )}

                {/* Header */}
                <div className="mb-8 flex items-center justify-between">
                    <div>
                        <h1 className="flex items-center gap-2.5 text-2xl font-bold tracking-tight">
                            <Users className="size-6 text-primary" />
                            {targetWorkspace.name} Members
                        </h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Manage who has access to this workspace.
                        </p>
                    </div>
                    <button
                        onClick={() => setShowInvite(true)}
                        className="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow-sm hover:bg-primary/90 transition-colors"
                    >
                        <Plus size={14} />
                        Invite Member
                    </button>
                </div>

                {/* Pending Invitations Section */}
                {invitations.length > 0 && (
                    <div className="mb-10">
                        <h2 className="mb-4 text-lg font-semibold flex items-center gap-2">
                            <Mail size={18} className="text-muted-foreground" />
                            Pending Invitations
                        </h2>
                        <div className="rounded-xl border border-border bg-card overflow-hidden">
                            <table className="w-full text-sm text-left">
                                <thead className="bg-muted/50 border-b border-border text-xs uppercase text-muted-foreground">
                                    <tr>
                                        <th className="px-6 py-3">Email</th>
                                        <th className="px-6 py-3">Role</th>
                                        <th className="px-6 py-3">Status</th>
                                        <th className="px-6 py-3 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-border">
                                    {invitations.map(invite => (
                                        <tr key={invite.id} className="hover:bg-muted/30 transition-colors">
                                            <td className="px-6 py-4 font-medium">{invite.email}</td>
                                            <td className="px-6 py-4 capitalize">{invite.role}</td>
                                            <td className="px-6 py-4">
                                                {invite.is_expired ? (
                                                    <span className="text-destructive text-xs inline-flex items-center gap-1"><Clock size={12}/> Expired</span>
                                                ) : (
                                                    <span className="text-amber-500 text-xs inline-flex items-center gap-1"><Clock size={12}/> Pending</span>
                                                )}
                                            </td>
                                            <td className="px-6 py-4 text-right">
                                                <button
                                                    onClick={() => handleCancelInvite(invite.id, invite.email)}
                                                    disabled={processingId === invite.id}
                                                    className="text-muted-foreground hover:text-destructive disabled:opacity-50"
                                                    title="Cancel Invitation"
                                                >
                                                    {processingId === invite.id ? <Loader2 size={16} className="animate-spin" /> : <Trash2 size={16} />}
                                                </button>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>
                )}

                {/* Active Members Section */}
                <div>
                    <h2 className="mb-4 text-lg font-semibold flex items-center gap-2">
                        <Users size={18} className="text-muted-foreground" />
                        Active Members
                    </h2>
                    <div className="rounded-xl border border-border bg-card overflow-hidden">
                        <table className="w-full text-sm text-left">
                            <thead className="bg-muted/50 border-b border-border text-xs uppercase text-muted-foreground">
                                <tr>
                                    <th className="px-6 py-3">User</th>
                                    <th className="px-6 py-3">Role</th>
                                    <th className="px-6 py-3">Joined</th>
                                    <th className="px-6 py-3 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-border">
                                {members.map(member => (
                                    <tr key={member.id} className="hover:bg-muted/30 transition-colors">
                                        <td className="px-6 py-4">
                                            <div className="font-medium">{member.name || 'Unknown'}</div>
                                            <div className="text-xs text-muted-foreground">{member.email}</div>
                                        </td>
                                        <td className="px-6 py-4">
                                            <span className={`inline-flex px-2 py-0.5 rounded text-xs font-medium capitalize ${
                                                member.role === 'owner' ? 'bg-primary/10 text-primary' : 'bg-muted text-muted-foreground'
                                            }`}>
                                                {member.role}
                                            </span>
                                        </td>
                                        <td className="px-6 py-4 text-muted-foreground">
                                            {new Date(member.joined_at).toLocaleDateString()}
                                        </td>
                                        <td className="px-6 py-4 text-right">
                                            {member.role !== 'owner' && (
                                                <button
                                                    onClick={() => handleRemoveMember(member.id, member.name || member.email)}
                                                    disabled={processingId === member.id}
                                                    className="text-muted-foreground hover:text-destructive disabled:opacity-50"
                                                    title="Remove Member"
                                                >
                                                    {processingId === member.id ? <Loader2 size={16} className="animate-spin" /> : <Trash2 size={16} />}
                                                </button>
                                            )}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

            {showInvite && <InviteMemberModal workspaceSlug={targetWorkspace.slug} onClose={() => setShowInvite(false)} />}
        </>
    );
}
