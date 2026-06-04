import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent, useState } from 'react';
import { Eye, EyeOff } from 'lucide-react';
import AppLogo from '@/components/app-logo';
import AuthPromoPanel from '@/components/public/auth-promo-panel';
import '../../../css/pages/auth.css';

export default function Login() {
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: false,
    });
    const [showPw, setShowPw] = useState(false);

    function submit(e: FormEvent<HTMLFormElement>) {
        e.preventDefault();
        post('/login', {
            onFinish: () => reset('password'),
        });
    }

    return (
        <>
            <Head title="Sign In" />

            <div className="auth-card">
                <h1 className="auth-page-title">Sign in to TraceMem</h1>
                <p className="auth-page-desc">
                    Access your memory infrastructure dashboard.
                </p>

                <form onSubmit={submit} noValidate>
                    <div className="auth-fields">

                        {/* Email */}
                        <div className="auth-field">
                            <label className="auth-label" htmlFor="email">
                                Email address
                            </label>
                            <input
                                id="email"
                                type="email"
                                className="auth-input"
                                placeholder="you@example.com"
                                value={data.email}
                                onChange={(e) => setData('email', e.target.value)}
                                autoComplete="email"
                                autoFocus
                                required
                            />
                            {errors.email && (
                                <div className="auth-error">{errors.email}</div>
                            )}
                        </div>

                        {/* Password */}
                        <div className="auth-field">
                            <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
                                <label className="auth-label" htmlFor="password">
                                    Password
                                </label>
                                <Link href="/forgot-password" className="auth-link" style={{ fontSize: '11px' }}>
                                    Forgot password?
                                </Link>
                            </div>
                            <div style={{ position: 'relative' }}>
                                <input
                                    id="password"
                                    type={showPw ? 'text' : 'password'}
                                    className="auth-input"
                                    placeholder="••••••••"
                                    value={data.password}
                                    onChange={(e) => setData('password', e.target.value)}
                                    autoComplete="current-password"
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
                        </div>

                        {/* Remember me */}
                        <div className="auth-remember-row">
                            <label className="auth-remember-label">
                                <input
                                    id="remember"
                                    type="checkbox"
                                    className="auth-checkbox"
                                    checked={data.remember}
                                    onChange={(e) => setData('remember', e.target.checked)}
                                />
                                <span className="auth-remember-text">Keep me signed in</span>
                            </label>
                        </div>

                        {/* Submit */}
                        <button
                            type="submit"
                            className="auth-btn"
                            disabled={processing}
                            id="login-submit-btn"
                        >
                            {processing && <span className="auth-spinner" aria-hidden="true" />}
                            {processing ? 'Signing in…' : 'Sign in'}
                        </button>
                    </div>
                </form>

                <p className="auth-footer-row">
                    Don&apos;t have an account?{' '}
                    <Link href="/register" className="auth-link">
                        Create one
                    </Link>
                </p>
            </div>
        </>
    );
}