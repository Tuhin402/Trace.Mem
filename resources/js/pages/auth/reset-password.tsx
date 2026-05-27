import { Form, Head } from '@inertiajs/react';
import { update } from '@/routes/password';

type Props = {
    token: string;
    email: string;
};

export default function ResetPassword({ token, email }: Props) {
    return (
        <>
            <Head title="Reset password" />

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
                            <input
                                id="reset-password"
                                type="password"
                                name="password"
                                className="auth-input"
                                placeholder="New password"
                                autoComplete="new-password"
                                autoFocus
                            />
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
                            <input
                                id="reset-password-confirm"
                                type="password"
                                name="password_confirmation"
                                className="auth-input"
                                placeholder="Confirm new password"
                                autoComplete="new-password"
                            />
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
        </>
    );
}

ResetPassword.layout = {
    title: 'Reset your password',
    description: 'Choose a new password for your TraceMem account.',
};
