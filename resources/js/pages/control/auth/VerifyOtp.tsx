import { useEffect, SyntheticEvent } from 'react';
import { Head, useForm, usePage } from '@inertiajs/react';
import { Button } from '@/components/control/ui/Button';
import { Input } from '@/components/control/ui/Input';

export default function VerifyOtp() {
    // The email is passed via session flash data from the Login controller
    const { flash } = usePage<any>().props;
    const sessionEmail = flash?.email || '';

    const { data, setData, post, processing, errors, reset } = useForm({
        email: sessionEmail,
        otp: '',
    });

    useEffect(() => {
        return () => {
            reset('otp');
        };
    }, []);

    const submit = (e: SyntheticEvent) => {
        e.preventDefault();
        post('/control/verify-otp');
    };

    return (
        <>
            <Head title="Verify OTP" />

            <div className="w-full max-w-md mx-auto bg-surface border border-almost-black p-8">
                <div className="mb-8 text-center">
                    <h1 className="text-2xl font-bold font-heading text-primary">Verification Required</h1>
                    <p className="text-sm text-on-background/70 mt-2 font-mono">
                        Enter the 6-digit OTP sent to your email.
                    </p>
                </div>

                <form onSubmit={submit} className="space-y-6">
                    <input type="hidden" name="email" value={data.email} />

                    <div>
                        <label htmlFor="otp" className="block label-caps mb-2 text-on-background/80">
                            One-Time Password
                        </label>
                        <Input
                            id="otp"
                            type="text"
                            name="otp"
                            value={data.otp}
                            className="block w-full text-center text-lg tracking-widest"
                            maxLength={6}
                            autoComplete="one-time-code"
                            onChange={(e) => setData('otp', e.target.value)}
                            required
                        />
                        {errors.otp && (
                            <p className="mt-2 text-sm text-error font-mono">{errors.otp}</p>
                        )}
                    </div>

                    <Button className="w-full" disabled={processing || !data.email}>
                        {processing ? 'Verifying...' : 'Verify Identity'}
                    </Button>
                </form>
            </div>
        </>
    );
}
