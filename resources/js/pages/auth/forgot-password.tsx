import { Form, Head, Link } from '@inertiajs/react';
import { login } from '@/routes';
import { email } from '@/routes/password';

export default function ForgotPassword({ status }: { status?: string }) {
    return (
        <>
            <Head title="Forgot password" />

            {/* Success status */}
            {status && (
                <div className="auth-success" role="alert">
                    {status}
                </div>
            )}

            <Form {...email.form()}>
                {({ processing, errors }) => (
                    <div className="auth-fields">

                        {/* Email */}
                        <div className="auth-field">
                            <label className="auth-label" htmlFor="forgot-email">
                                Email address
                            </label>
                            <input
                                id="forgot-email"
                                type="email"
                                name="email"
                                className="auth-input"
                                placeholder="you@example.com"
                                autoComplete="off"
                                autoFocus
                                required
                            />
                            {errors.email && (
                                <div className="auth-error">{errors.email}</div>
                            )}
                            <p className="auth-field-note">
                                <span className="auth-field-note-icon" aria-hidden="true">→</span>
                                We&apos;ll send a password reset link to this address.
                            </p>
                        </div>

                        {/* Submit */}
                        <button
                            type="submit"
                            className="auth-btn"
                            disabled={processing}
                            data-test="email-password-reset-link-button"
                            id="forgot-password-btn"
                        >
                            {processing && <span className="auth-spinner" aria-hidden="true" />}
                            {processing ? 'Sending link…' : 'Send reset link'}
                        </button>

                        <p className="auth-footer-row">
                            Remember your password?{' '}
                            <Link href={login().url} className="auth-link">
                                Back to sign in
                            </Link>
                        </p>
                    </div>
                )}
            </Form>
        </>
    );
}

ForgotPassword.layout = {
    title: 'Forgot your password?',
    description: 'Enter your email address and we\'ll send you a reset link.',
};