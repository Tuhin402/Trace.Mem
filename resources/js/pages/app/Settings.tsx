import { Head, router, usePage } from '@inertiajs/react';
import { useState, useRef } from 'react';
import {
    User,
    Lock,
    AlertTriangle,
    CheckCircle2,
    Save,
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
        if (newPassword.length < 8) {
            setPwErrors({ password: 'New password must be at least 8 characters.' });
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
                            <div className="app-field">
                                <label htmlFor="st-current-pw">Current password</label>
                                <input
                                    id="st-current-pw"
                                    type="password"
                                    value={currentPassword}
                                    onChange={(e) => setCurrentPassword(e.target.value)}
                                    autoComplete="current-password"
                                    placeholder="Your current password"
                                />
                                {pwErrors.current_password && (
                                    <span className="app-field-error">{pwErrors.current_password}</span>
                                )}
                            </div>

                            <div className="app-field">
                                <label htmlFor="st-new-pw">New password</label>
                                <input
                                    id="st-new-pw"
                                    type="password"
                                    value={newPassword}
                                    onChange={(e) => setNewPassword(e.target.value)}
                                    autoComplete="new-password"
                                    placeholder="At least 8 characters"
                                />
                                {pwErrors.password && (
                                    <span className="app-field-error">{pwErrors.password}</span>
                                )}
                            </div>

                            <div className="app-field">
                                <label htmlFor="st-confirm-pw">Confirm new password</label>
                                <input
                                    id="st-confirm-pw"
                                    type="password"
                                    value={confirmPassword}
                                    onChange={(e) => setConfirmPassword(e.target.value)}
                                    autoComplete="new-password"
                                    placeholder="Repeat your new password"
                                />
                                {pwErrors.password_confirmation && (
                                    <span className="app-field-error">{pwErrors.password_confirmation}</span>
                                )}
                            </div>

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