import { Form, Head } from '@inertiajs/react';
import { useState } from 'react';
import { Eye, EyeOff } from 'lucide-react';
import { update } from '@/routes/password';

type Props = {
    token: string;
    email: string;
};

export default function ResetPassword({ token, email }: Props) {
    const [showPw, setShowPw] = useState(false);
    const [showConfirm, setShowConfirm] = useState(false);

    return (
        <>
            <Head title="Reset password" />

            <div className="auth-card">
                <h1 className="auth-page-title">Reset your password</h1>
                <p className="auth-page-desc">
                    Choose a new password for your TraceMem account.
                </p>

                <Form
                    {...update.form()}
                    transform={(data) => ({ ...data, token, email })}
                    resetOnSuccess={['password', 'password_confirmation']}
                >
                    {({ processing, errors }) => (
                        <div className="auth-fields">

                            {/* Email (read-only) */}
                            <div className="auth-field">
                                <label className="auth-label" htmlFor="reset-email">
                                    Email address
                                </label>
                                <input
                                    id="reset-email"
                                    type="email"
                                    name="email"
                                    className="auth-input"
                                    value={email}
                                    autoComplete="email"
                                    readOnly
                                />
                                {errors.email && (
                                    <div className="auth-error">{errors.email}</div>
                                )}
                            </div>

                            {/* New password */}
                            <div className="auth-field">
                                <label className="auth-label" htmlFor="reset-password">
                                    New password
                                </label>
                                <div style={{ position: 'relative' }}>
                                    <input
                                        id="reset-password"
                                        type={showPw ? 'text' : 'password'}
                                        name="password"
                                        className="auth-input"
                                        placeholder="New password"
                                        autoComplete="new-password"
                                        autoFocus
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
                                {/* Password rules reminder */}
                                <div className="auth-pw-rules" aria-label="Password requirements">
                                    <span className="auth-pw-rule">Minimum 8 characters</span>
                                    <span className="auth-pw-rule">At least one uppercase, lowercase, number, and special character</span>
                                </div>
                            </div>

                            {/* Confirm password */}
                            <div className="auth-field">
                                <label className="auth-label" htmlFor="reset-password-confirm">
                                    Confirm new password
                                </label>
                                <div style={{ position: 'relative' }}>
                                    <input
                                        id="reset-password-confirm"
                                        type={showConfirm ? 'text' : 'password'}
                                        name="password_confirmation"
                                        className="auth-input"
                                        placeholder="Confirm new password"
                                        autoComplete="new-password"
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
                                data-test="reset-password-button"
                                id="reset-password-btn"
                            >
                                {processing && <span className="auth-spinner" aria-hidden="true" />}
                                {processing ? 'Resetting…' : 'Reset password'}
                            </button>
                        </div>
                    )}
                </Form>
            </div>
        </>
    );
}
