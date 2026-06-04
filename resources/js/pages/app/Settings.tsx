import { Head, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import {
    User,
    Lock,
    AlertTriangle,
    CheckCircle2,
    Save,
    Eye,
    EyeOff,
} from 'lucide-react';
import { useToast } from '@/components/app/toast';

type PageProps = {
    auth: {
        user: {
            id: number;
            name: string;
            email: string;
            email_verified_at?: string | null;
        };
    };
    mustVerifyEmail?: boolean;
    status?: string;
};

type Tab = 'profile' | 'password';

/* ── Reusable password input with visibility toggle ── */
function PasswordField({
    id,
    value,
    onChange,
    placeholder,
    autoComplete,
    error,
}: {
    id: string;
    value: string;
    onChange: (v: string) => void;
    placeholder: string;
    autoComplete: string;
    error?: string;
}) {
    const [visible, setVisible] = useState(false);

    return (
        <div className="app-field">
            <div style={{ position: 'relative' }}>
                <input
                    id={id}
                    type={visible ? 'text' : 'password'}
                    value={value}
                    onChange={(e) => onChange(e.target.value)}
                    autoComplete={autoComplete}
                    placeholder={placeholder}
                    style={{ paddingRight: '44px' }}
                />
                <button
                    type="button"
                    onClick={() => setVisible(!visible)}
                    aria-label={visible ? 'Hide password' : 'Show password'}
                    style={{
                        position: 'absolute',
                        right: '12px',
                        top: '50%',
                        transform: 'translateY(-50%)',
                        background: 'none',
                        border: 'none',
                        padding: '4px',
                        cursor: 'pointer',
                        color: 'var(--app-text-dim)',
                        display: 'flex',
                        alignItems: 'center',
                    }}
                >
                    {visible ? <EyeOff size={16} /> : <Eye size={16} />}
                </button>
            </div>
            {error && <span className="app-field-error">{error}</span>}
        </div>
    );
}

/* ── Password validation rules (same as registration) ── */
function validatePassword(pw: string): string | null {
    if (pw.length < 8)              return 'Password must be at least 8 characters.';
    if (!/[A-Z]/.test(pw))          return 'Password must contain at least one uppercase letter (A–Z).';
    if (!/[a-z]/.test(pw))          return 'Password must contain at least one lowercase letter (a–z).';
    if (!/[0-9]/.test(pw))          return 'Password must contain at least one number (0–9).';
    if (!/[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?`~]/.test(pw))
        return 'Password must contain at least one special character (!@#$…).';
    return null;
}

export default function Settings() {
    const { props } = usePage<PageProps>();
    const { toast, Toasts } = useToast();
    const user = props.auth?.user;

    const [activeTab, setActiveTab] = useState<Tab>('profile');

    /* ── Profile form state ── */
    const [profileName,  setProfileName]  = useState(user?.name  ?? '');
    const [profileEmail, setProfileEmail] = useState(user?.email ?? '');
    const [profileSaving, setProfileSaving] = useState(false);

    /* ── Password form state ── */
    const [currentPassword, setCurrentPassword] = useState('');
    const [newPassword,     setNewPassword]     = useState('');
    const [confirmPassword, setConfirmPassword] = useState('');
    const [pwSaving, setPwSaving] = useState(false);
    const [pwErrors, setPwErrors] = useState<Record<string, string>>({});

    /* ── Profile save ── */
    const saveProfile = () => {
        if (!profileName.trim() || !profileEmail.trim()) return;
        setProfileSaving(true);

        router.patch(
            '/settings/profile',
            { name: profileName.trim(), email: profileEmail.trim() },
            {
                preserveScroll: true,
                onSuccess: () => toast('Profile updated successfully.', 'success'),
                onError:   (errs) => {
                    toast('Failed to update profile.', 'error');
                    console.error(errs);
                },
                onFinish: () => setProfileSaving(false),
            },
        );
    };

    /* ── Password save ── */
    const savePassword = () => {
        setPwErrors({});

        if (!currentPassword) {
            setPwErrors({ current_password: 'Current password is required.' });
            return;
        }

        const pwError = validatePassword(newPassword);
        if (pwError) {
            setPwErrors({ password: pwError });
            return;
        }

        if (newPassword !== confirmPassword) {
            setPwErrors({ password_confirmation: 'Passwords do not match.' });
            return;
        }

        setPwSaving(true);

        router.put(
            '/settings/password',
            {
                current_password:      currentPassword,
                password:              newPassword,
                password_confirmation: confirmPassword,
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    toast('Password updated successfully.', 'success');
                    setCurrentPassword('');
                    setNewPassword('');
                    setConfirmPassword('');
                    setPwErrors({});
                },
                onError: (errs) => {
                    setPwErrors(errs as Record<string, string>);
                    toast('Could not update password. Check the form for errors.', 'error');
                },
                onFinish: () => setPwSaving(false),
            },
        );
    };

    return (
        <>
            <Head title="Settings" />
            <Toasts />

            <div className="app-page">

                {/* ── Header ── */}
                <div className="app-page-header">
                    <div>
                        <h1 className="app-page-title">Settings</h1>
                        <p className="app-page-subtitle">Manage your account profile and security settings.</p>
                    </div>
                </div>

                {/* ── Tab bar ── */}
                <div className="st-tab-bar">
                    <button
                        type="button"
                        className={`st-tab${activeTab === 'profile' ? ' st-tab--active' : ''}`}
                        onClick={() => setActiveTab('profile')}
                    >
                        <User size={14} />
                        Profile
                    </button>
                    <button
                        type="button"
                        className={`st-tab${activeTab === 'password' ? ' st-tab--active' : ''}`}
                        onClick={() => setActiveTab('password')}
                    >
                        <Lock size={14} />
                        Password Reset
                    </button>
                </div>

                {/* ── Profile tab ── */}
                {activeTab === 'profile' && (
                    <div className="app-panel" style={{ maxWidth: '560px' }}>
                        <div className="app-panel-head">
                            <div>
                                <h2>Profile Information</h2>
                                <p>Update your display name and email address.</p>
                            </div>
                        </div>

                        <div className="st-form">
                            <div className="app-field">
                                <label htmlFor="st-name">Full name</label>
                                <input
                                    id="st-name"
                                    type="text"
                                    value={profileName}
                                    onChange={(e) => setProfileName(e.target.value)}
                                    autoComplete="name"
                                    placeholder="Your full name"
                                />
                            </div>

                            <div className="app-field">
                                <label htmlFor="st-email">Email address</label>
                                <input
                                    id="st-email"
                                    type="email"
                                    value={profileEmail}
                                    onChange={(e) => setProfileEmail(e.target.value)}
                                    autoComplete="email"
                                    placeholder="your@email.com"
                                />
                            </div>

                            <button
                                type="button"
                                className="app-btn app-btn-primary"
                                disabled={profileSaving}
                                onClick={saveProfile}
                            >
                                <Save size={13} />
                                {profileSaving ? 'Saving...' : 'Save Profile'}
                            </button>
                        </div>
                    </div>
                )}

                {/* ── Password tab ── */}
                {activeTab === 'password' && (
                    <div className="app-panel" style={{ maxWidth: '560px' }}>
                        <div className="app-panel-head">
                            <div>
                                <h2>Password Reset</h2>
                                <p>Use a strong password that you do not use on other websites.</p>
                            </div>
                        </div>

                        <div className="st-form">
                            <label htmlFor="st-current-pw">Current password</label>
                            <PasswordField
                                id="st-current-pw"
                                value={currentPassword}
                                onChange={setCurrentPassword}
                                autoComplete="current-password"
                                placeholder="Your current password"
                                error={pwErrors.current_password}
                            />

                            <label htmlFor="st-new-pw">New password</label>
                            <PasswordField
                                id="st-new-pw"
                                value={newPassword}
                                onChange={setNewPassword}
                                autoComplete="new-password"
                                placeholder="At least 8 characters"
                                error={pwErrors.password}
                            />

                            {/* Password requirement rules */}
                            <div className="auth-pw-rules" aria-label="Password requirements" style={{ marginTop: '-4px', marginBottom: '8px' }}>
                                <span className="auth-pw-rule" style={{ color: newPassword.length >= 8 ? 'var(--app-success)' : undefined }}>Minimum 8 characters</span>
                                <span className="auth-pw-rule" style={{ color: /[A-Z]/.test(newPassword) ? 'var(--app-success)' : undefined }}>At least one uppercase letter (A–Z)</span>
                                <span className="auth-pw-rule" style={{ color: /[a-z]/.test(newPassword) ? 'var(--app-success)' : undefined }}>At least one lowercase letter (a–z)</span>
                                <span className="auth-pw-rule" style={{ color: /[0-9]/.test(newPassword) ? 'var(--app-success)' : undefined }}>At least one number (0–9)</span>
                                <span className="auth-pw-rule" style={{ color: /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?`~]/.test(newPassword) ? 'var(--app-success)' : undefined }}>At least one special character (!@#$…)</span>
                            </div>

                            <label htmlFor="st-confirm-pw">Confirm new password</label>
                            <PasswordField
                                id="st-confirm-pw"
                                value={confirmPassword}
                                onChange={setConfirmPassword}
                                autoComplete="new-password"
                                placeholder="Repeat your new password"
                                error={pwErrors.password_confirmation}
                            />

                            <button
                                type="button"
                                className="app-btn app-btn-primary"
                                disabled={pwSaving}
                                onClick={savePassword}
                            >
                                <Lock size={13} />
                                {pwSaving ? 'Updating...' : 'Update Password'}
                            </button>
                        </div>

                        {/* Warning note below password form */}
                        <div className="st-pw-warning">
                            <AlertTriangle size={13} className="st-pw-warning-icon" aria-hidden="true" />
                            <span>
                                Rotating your password regularly is a good security practice. We recommend updating it every 90 days, especially if you share devices or reuse passwords across sites.
                            </span>
                        </div>
                    </div>
                )}

            </div>
        </>
    );
}