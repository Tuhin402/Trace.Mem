import { Form, Head, setLayoutProps } from '@inertiajs/react';
import { REGEXP_ONLY_DIGITS } from 'input-otp';
import { useMemo, useState } from 'react';
import InputError from '@/components/input-error';
import {
    InputOTP,
    InputOTPGroup,
    InputOTPSlot,
} from '@/components/ui/input-otp';
import { OTP_MAX_LENGTH } from '@/hooks/use-two-factor-auth';
import { store } from '@/routes/two-factor/login';

export default function TwoFactorChallenge() {
    const [showRecoveryInput, setShowRecoveryInput] = useState<boolean>(false);
    const [code, setCode] = useState<string>('');

    const authConfigContent = useMemo<{
        title: string;
        description: string;
        toggleText: string;
    }>(() => {
        if (showRecoveryInput) {
            return {
                title: 'Recovery code',
                description: 'Enter one of your emergency recovery codes to access your account.',
                toggleText: 'Use authentication code instead',
            };
        }

        return {
            title: 'Two-factor authentication',
            description: 'Enter the 6-digit code from your authenticator app.',
            toggleText: 'Use a recovery code instead',
        };
    }, [showRecoveryInput]);

    const toggleRecoveryMode = (clearErrors: () => void): void => {
        setShowRecoveryInput(!showRecoveryInput);
        clearErrors();
        setCode('');
    };

    return (
        <>
            <Head title="Two-factor authentication" />

            <div className="auth-card">
                <h1 className="auth-page-title">{authConfigContent.title}</h1>
                <p className="auth-page-desc">
                    {authConfigContent.description}
                </p>

                <Form
                    {...store.form()}
                    className="auth-fields"
                    resetOnError
                    resetOnSuccess={!showRecoveryInput}
                >
                    {({ errors, processing, clearErrors }) => (
                        <>
                            {showRecoveryInput ? (
                                /* Recovery code input */
                                <div className="auth-field">
                                    <label className="auth-label" htmlFor="recovery-code-input">
                                        Recovery code
                                    </label>
                                    <input
                                        id="recovery-code-input"
                                        name="recovery_code"
                                        type="text"
                                        className="auth-recovery-input"
                                        placeholder="xxxx-xxxx-xxxx-xxxx"
                                        autoFocus={showRecoveryInput}
                                        required
                                        spellCheck={false}
                                        autoComplete="one-time-code"
                                    />
                                    {errors.recovery_code && (
                                        <div className="auth-error">{errors.recovery_code}</div>
                                    )}
                                </div>
                            ) : (
                                /* OTP input */
                                <div className="auth-field" style={{ alignItems: 'center' }}>
                                    <div className="auth-otp-wrap">
                                        <InputOTP
                                            name="code"
                                            maxLength={OTP_MAX_LENGTH}
                                            value={code}
                                            onChange={(value) => setCode(value)}
                                            disabled={processing}
                                            pattern={REGEXP_ONLY_DIGITS}
                                        >
                                            <InputOTPGroup>
                                                {Array.from(
                                                    { length: OTP_MAX_LENGTH },
                                                    (_, index) => (
                                                        <InputOTPSlot
                                                            key={index}
                                                            index={index}
                                                        />
                                                    ),
                                                )}
                                            </InputOTPGroup>
                                        </InputOTP>
                                    </div>
                                    {errors.code && (
                                        <div className="auth-error" style={{ marginTop: '8px' }}>
                                            {errors.code}
                                        </div>
                                    )}
                                </div>
                            )}

                            {/* Submit */}
                            <button
                                type="submit"
                                className="auth-btn"
                                disabled={processing}
                                id="two-factor-submit-btn"
                            >
                                {processing && <span className="auth-spinner" aria-hidden="true" />}
                                {processing ? 'Verifying…' : 'Verify & continue'}
                            </button>

                            {/* Toggle mode */}
                            <div className="auth-mode-toggle">
                                <button
                                    type="button"
                                    onClick={() => toggleRecoveryMode(clearErrors)}
                                >
                                    {authConfigContent.toggleText}
                                </button>
                            </div>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}
