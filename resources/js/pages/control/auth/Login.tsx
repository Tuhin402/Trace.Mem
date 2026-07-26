import { useEffect, SyntheticEvent } from 'react';
import { Head, useForm, Link } from '@inertiajs/react';
import { Button } from '@/components/control/ui/Button';
import { Input } from '@/components/control/ui/Input';
import AppLogoIcon from '@/components/app-logo-icon';

export default function Login({ status }: { status?: string }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
    });

    useEffect(() => {
        return () => {
            reset('email');
        };
    }, []);

    const submit = (e: SyntheticEvent) => {
        e.preventDefault();
        post('/login');
    };

    return (
        <>
            <Head title="Admin Login" />

            <div className="w-full max-w-md mx-auto bg-surface border border-almost-black p-8">
                <div className="mb-8 text-center flex flex-col items-center">
                    <div className="flex items-center justify-center gap-3 mb-6">
                        <AppLogoIcon className="h-10 w-auto" />
                        <span className="text-3xl font-bold font-heading tracking-tight text-[#2c0133]">
                            TraceMem
                        </span>
                    </div>
                    <h1 className="text-xl font-bold font-heading text-primary uppercase tracking-wider">Operations Console</h1>
                    <p className="text-sm text-on-background/70 mt-2 font-mono">
                        Restricted Access Boundary
                    </p>
                </div>

                {status && (
                    <div className="mb-4 text-sm font-medium text-primary border border-primary/20 bg-primary/5 p-3">
                        {status}
                    </div>
                )}

                <form onSubmit={submit} className="space-y-6">
                    <div>
                        <label htmlFor="email" className="block label-caps mb-2 text-on-background/80">
                            Administrator Email
                        </label>
                        <Input
                            id="email"
                            type="email"
                            name="email"
                            value={data.email}
                            className="block w-full"
                            autoComplete="username"
                            onChange={(e) => setData('email', e.target.value)}
                            required
                        />
                        {errors.email && (
                            <p className="mt-2 text-sm text-error font-mono">{errors.email}</p>
                        )}
                    </div>

                    <Button className="w-full" disabled={processing}>
                        {processing ? 'Authenticating...' : 'Send OTP'}
                    </Button>
                </form>
            </div>
        </>
    );
}
