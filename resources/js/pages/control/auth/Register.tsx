import { useEffect, SyntheticEvent } from 'react';
import { Head, useForm, Link } from '@inertiajs/react';
import { Button } from '@/components/control/ui/Button';
import { Input } from '@/components/control/ui/Input';

export default function Register() {
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        email: '',
    });

    useEffect(() => {
        return () => {
            reset('name', 'email');
        };
    }, []);

    const submit = (e: SyntheticEvent) => {
        e.preventDefault();
        post('/register');
    };

    return (
        <>
            <Head title="Control Registration" />

            <div className="w-full max-w-md mx-auto bg-surface border border-almost-black p-8">
                <div className="mb-8 text-center">
                    <h1 className="text-2xl font-bold font-heading text-primary">Operations Control</h1>
                    <p className="text-sm text-on-background/70 mt-2 font-mono">
                        Initialize Control Credentials
                    </p>
                </div>

                <form onSubmit={submit} className="space-y-6">
                    <div>
                        <label htmlFor="name" className="block label-caps mb-2 text-on-background/80">
                            Full Name
                        </label>
                        <Input
                            id="name"
                            type="text"
                            name="name"
                            value={data.name}
                            className="block w-full"
                            autoComplete="name"
                            onChange={(e) => setData('name', e.target.value)}
                            required
                        />
                        {errors.name && (
                            <p className="mt-2 text-sm text-error font-mono">{errors.name}</p>
                        )}
                    </div>

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
                            autoComplete="email"
                            onChange={(e) => setData('email', e.target.value)}
                            required
                        />
                        {errors.email && (
                            <p className="mt-2 text-sm text-error font-mono">{errors.email}</p>
                        )}
                    </div>

                    <Button className="w-full" disabled={processing}>
                        {processing ? 'Provisioning...' : 'Provision Account'}
                    </Button>
                </form>

                <div className="mt-6 text-center text-sm font-mono text-on-background/60">
                    Already provisioned? <Link href="/login" className="text-primary hover:underline">Log in</Link>
                </div>
            </div>
        </>
    );
}
