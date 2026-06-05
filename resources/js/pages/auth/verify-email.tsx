import { Form, Head } from '@inertiajs/react';
import { Mail } from 'lucide-react';
import { logout } from '@/routes';
import { send } from '@/routes/verification';

export default function VerifyEmail({ status }: { status?: string }) {
    return (
        <>
            <Head title="Email verification" />

            <div className="auth-card">
                <h1 className="auth-page-title">Verify your email</h1>
                <p className="auth-page-desc">
                    Check your inbox and click the link we sent to verify your account.
                </p>

                {/* Success status */}
                {status === 'verification-link-sent' && (
                    <div className="auth-success" role="alert">
                        A new verification link has been sent to your email address. Check your inbox, and your spam folder if it doesn't arrive within a few minutes.
                    </div>
                )}

                {/* Icon */}
                <div className="auth-verify-icon" aria-hidden="true">
                    <Mail size={26} />
                </div>

                {/* Note */}
                <p style={{
                    fontFamily: "'Mona Sans', sans-serif",
                    fontSize: '14px',
                    color: '#9f8b9d',
                    lineHeight: '1.65',
                    marginBottom: '28px',
                    textAlign: 'center',
                }}>
                    A verification link has been sent to your email address. Click the link to confirm your account before continuing.
                    <br /><br />
                    <span style={{ color: '#7a6a78', fontSize: '13px' }}>
                        Didn't receive it? Check your spam folder.
                    </span>
                </p>

                {/* Resend form */}
                <Form {...send.form()} className="auth-fields">
                    {({ processing }) => (
                        <>
                            <button
                                type="submit"
                                className="auth-btn"
                                disabled={processing}
                                id="resend-verification-btn"
                            >
                                {processing && <span className="auth-spinner" aria-hidden="true" />}
                                {processing ? 'Sending…' : 'Resend verification email'}
                            </button>
                        </>
                    )}
                </Form>

                {/* Logout — POST route, must use Form not <a> */}
                <Form {...logout.form()}>
                    {({ processing }) => (
                        <p className="auth-footer-row">
                            Wrong account?{' '}
                            <button
                                type="submit"
                                className="auth-footer-link"
                                disabled={processing}
                            >
                                Log out
                            </button>
                        </p>
                    )}
                </Form>
            </div>
        </>
    );
}
