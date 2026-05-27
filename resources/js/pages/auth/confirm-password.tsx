import { Form, Head } from '@inertiajs/react';
import { ShieldCheck } from 'lucide-react';
import { store } from '@/routes/password/confirm';

export default function ConfirmPassword() {
    return (
        <>
            <Head title="Confirm password" />

            {/* Security icon */}
            <div className="auth-verify-icon" aria-hidden="true">
                <ShieldCheck size={26} />
            </div>

            <Form {...store.form()} resetOnSuccess={['password']}>
                {({ processing, errors }) => (
                    <div className="auth-fields">

                        {/* Password */}
                        <div className="auth-field">
                            <label className="auth-label" htmlFor="confirm-password">
                                Current password
                            </label>
                            <input
                                id="confirm-password"
                                type="password"
                                name="password"
                                className="auth-input"
                                placeholder="Enter your password"
                                autoComplete="current-password"
                                autoFocus
                                required
                            />
                            {errors.password && (
                                <div className="auth-error">{errors.password}</div>
                            )}
                        </div>

                        {/* Submit */}
                        <button
                            type="submit"
                            className="auth-btn"
                            disabled={processing}
                            data-test="confirm-password-button"
                            id="confirm-password-btn"
                        >
                            {processing && <span className="auth-spinner" aria-hidden="true" />}
                            {processing ? 'Confirming…' : 'Confirm & continue'}
                        </button>
                    </div>
                )}
            </Form>
        </>
    );
}

ConfirmPassword.layout = {
    title: 'Confirm your password',
    description: 'This is a secure area. Re-enter your password to continue.',
};
