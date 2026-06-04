import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent, useState } from 'react';
import { Eye, EyeOff } from 'lucide-react';
import AppLogo from '@/components/app-logo';
import AuthPromoPanel from '@/components/public/auth-promo-panel';
import '../../../css/pages/auth.css';

export default function Register() {
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
        account_type: 'individual',
        company_name: '',
    });
    const [showPw, setShowPw] = useState(false);
    const [showConfirm, setShowConfirm] = useState(false);

    function submit(e: FormEvent<HTMLFormElement>) {
        e.preventDefault();
        post('/register', {
            onFinish: () => reset('password', 'password_confirmation'),
        });
    }

    return (
        <>
            <Head title="Create Account" />
            <div className="auth-card">
                <h1 className="auth-page-title">Create your account</h1>
                <p className="auth-page-desc">
                    Start building with persistent AI memory in minutes.
                </p>

                <form onSubmit={submit} noValidate>
                    <div className="auth-fields">

                        {/* Name */}
                        <div className="auth-field">
                            <label className="auth-label" htmlFor="reg-name">
                                Full name
                            </label>
                            <input
                                id="reg-name"
                                type="text"
                                className="auth-input"
                                placeholder="Your name"
                                value={data.name}
                                onChange={(e) => setData('name', e.target.value)}
                                autoComplete="name"
                                autoFocus
                                required
                            />
                            {errors.name && (
                                <div className="auth-error">{errors.name}</div>
                            )}
                        </div>

                        {/* Email */}
                        <div className="auth-field">
                            <label className="auth-label" htmlFor="reg-email">
                                Email address
                            </label>
                            <input
                                id="reg-email"
                                type="email"
                                className="auth-input"
                                placeholder="you@example.com"
                                value={data.email}
                                onChange={(e) => setData('email', e.target.value)}
                                autoComplete="email"
                                required
                            />
                            {errors.email && (
                                <div className="auth-error">{errors.email}</div>
                            )}
                            <p className="auth-field-note">
                                <span className="auth-field-note-icon" aria-hidden="true">→</span>
                                Use a real, accessible email, your verification link will be sent here.
                            </p>
                        </div>

                        {/* Account type */}
                        <div className="auth-field">
                            <label className="auth-label" htmlFor="reg-account-type">
                                Account type
                            </label>
                            <div className="auth-select-wrap">
                                <select
                                    id="reg-account-type"
                                    className="auth-select"
                                    value={data.account_type}
                                    onChange={(e) => setData('account_type', e.target.value)}
                                >
                                    <option value="individual">Individual</option>
                                    <option value="tenant">Company / Tenant</option>
                                </select>
                            </div>
                            {errors.account_type && (
                                <div className="auth-error">{errors.account_type}</div>
                            )}
                        </div>

                        {/* Company name (conditional) */}
                        {data.account_type === 'tenant' && (
                            <div className="auth-field">
                                <label className="auth-label" htmlFor="reg-company">
                                    Company name
                                </label>
                                <input
                                    id="reg-company"
                                    type="text"
                                    className="auth-input"
                                    placeholder="Your company name"
                                    value={data.company_name}
                                    onChange={(e) => setData('company_name', e.target.value)}
                                    autoComplete="organization"
                                />
                                {errors.company_name && (
                                    <div className="auth-error">{errors.company_name}</div>
                                )}
                            </div>
                        )}

                        {/* Password */}
                        <div className="auth-field">
                            <label className="auth-label" htmlFor="reg-password">
                                Password
                            </label>
                            <div style={{ position: 'relative' }}>
                                <input
                                    id="reg-password"
                                    type={showPw ? 'text' : 'password'}
                                    className="auth-input"
                                    placeholder="••••••••"
                                    value={data.password}
                                    onChange={(e) => setData('password', e.target.value)}
                                    autoComplete="new-password"
                                    required
                                    style={{ paddingRight: '44px' }}
                                />
                                <button
                                    type="button"
                                    onClick={() => setShowPw(!showPw)}
                                    aria-label={showPw ? 'Hide password' : 'Show password'}
                                    style={{ position: 'absolute', right: '12px', top: '50%', transform: 'translateY(-50%)', background: 'none', border: 'none', padding: '4px', cursor: 'pointer', color: 'var(--app-text-dim)', display: 'flex', alignItems: 'center' }}
                                >
                                    {showPw ? <EyeOff size={16} /> : <Eye size={16} />}
                                </button>
                            </div>
                            {errors.password && (
                                <div className="auth-error">{errors.password}</div>
                            )}
                            {/* Password rules — live highlighting */}
                            <div className="auth-pw-rules" aria-label="Password requirements">
                                <span className="auth-pw-rule" style={{ color: data.password.length >= 8 ? 'var(--app-success, #22c55e)' : undefined }}>Minimum 8 characters</span>
                                <span className="auth-pw-rule" style={{ color: /[A-Z]/.test(data.password) ? 'var(--app-success, #22c55e)' : undefined }}>At least one uppercase letter (A–Z)</span>
                                <span className="auth-pw-rule" style={{ color: /[a-z]/.test(data.password) ? 'var(--app-success, #22c55e)' : undefined }}>At least one lowercase letter (a–z)</span>
                                <span className="auth-pw-rule" style={{ color: /[0-9]/.test(data.password) ? 'var(--app-success, #22c55e)' : undefined }}>At least one number (0–9)</span>
                                <span className="auth-pw-rule" style={{ color: /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?`~]/.test(data.password) ? 'var(--app-success, #22c55e)' : undefined }}>At least one special character (!@#$…)</span>
                            </div>
                        </div>

                        {/* Confirm password */}
                        <div className="auth-field">
                            <label className="auth-label" htmlFor="reg-password-confirm">
                                Confirm password
                            </label>
                            <div style={{ position: 'relative' }}>
                                <input
                                    id="reg-password-confirm"
                                    type={showConfirm ? 'text' : 'password'}
                                    className="auth-input"
                                    placeholder="••••••••"
                                    value={data.password_confirmation}
                                    onChange={(e) => setData('password_confirmation', e.target.value)}
                                    autoComplete="new-password"
                                    required
                                    style={{ paddingRight: '44px' }}
                                />
                                <button
                                    type="button"
                                    onClick={() => setShowConfirm(!showConfirm)}
                                    aria-label={showConfirm ? 'Hide password' : 'Show password'}
                                    style={{ position: 'absolute', right: '12px', top: '50%', transform: 'translateY(-50%)', background: 'none', border: 'none', padding: '4px', cursor: 'pointer', color: 'var(--app-text-dim)', display: 'flex', alignItems: 'center' }}
                                >
                                    {showConfirm ? <EyeOff size={16} /> : <Eye size={16} />}
                                </button>
                            </div>
                            {errors.password_confirmation && (
                                <div className="auth-error">{errors.password_confirmation}</div>
                            )}
                        </div>

                        {/* Submit */}
                        <button
                            type="submit"
                            className="auth-btn"
                            disabled={processing}
                            id="register-submit-btn"
                        >
                            {processing && <span className="auth-spinner" aria-hidden="true" />}
                            {processing ? 'Creating account…' : 'Create account'}
                        </button>
                    </div>
                </form>

                <p className="auth-footer-row">
                    Already have an account?{' '}
                    <Link href="/login" className="auth-link">
                        Sign in
                    </Link>
                </p>
            </div>
        </>
    );
}