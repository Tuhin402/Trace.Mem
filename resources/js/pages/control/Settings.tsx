import { Head, useForm } from '@inertiajs/react';
import { Button } from '@/components/control/ui/Button';
import { Settings as SettingsIcon } from 'lucide-react';
import { SyntheticEvent } from 'react';

export default function Settings({ settings, status }: any) {
    const { data, setData, put, processing, errors } = useForm({
        allow_admin_registration: settings.allow_admin_registration || false,
        experimental_features: settings.experimental_features || false,
        maintenance_banner: settings.maintenance_banner || '',
    });

    const submit = (e: SyntheticEvent) => {
        e.preventDefault();
        put('/settings');
    };

    return (
        <div className="max-w-4xl mx-auto space-y-6 pb-24">
            <Head title="Platform Settings" />

            <div className="flex items-center gap-3 mb-8 pb-4 border-b border-almost-black/10">
                <SettingsIcon className="h-8 w-8 text-primary" />
                <h1 className="text-3xl font-bold font-heading text-primary">Platform Settings</h1>
            </div>

            {status && (
                <div className="mb-6 p-4 bg-primary/10 border border-primary text-primary text-sm font-medium">
                    {status}
                </div>
            )}

            <form onSubmit={submit} className="space-y-8">
                
                {/* Registration Control */}
                <div className="p-6 bg-surface border border-almost-black">
                    <h2 className="text-lg font-bold font-heading text-primary mb-4">Security Controls</h2>
                    
                    <div className="flex items-start gap-4">
                        <div className="flex items-center h-5">
                            <input
                                id="allow_admin_registration"
                                type="checkbox"
                                checked={data.allow_admin_registration}
                                onChange={(e) => setData('allow_admin_registration', e.target.checked)}
                                className="w-4 h-4 text-primary bg-background border-almost-black rounded-none focus:ring-primary focus:ring-2"
                            />
                        </div>
                        <div className="text-sm">
                            <label htmlFor="allow_admin_registration" className="font-bold text-on-background">
                                Allow Control Registration
                            </label>
                            <p className="text-on-background/70 font-mono mt-1">
                                If enabled, anyone can visit /register to create a new control administrator account. This should remain disabled after initial setup to prevent unauthorized admin creation.
                            </p>
                        </div>
                    </div>
                </div>

                {/* Experimental Features */}
                <div className="p-6 bg-surface border border-almost-black">
                    <h2 className="text-lg font-bold font-heading text-primary mb-4">Feature Flags</h2>
                    
                    <div className="flex items-start gap-4">
                        <div className="flex items-center h-5">
                            <input
                                id="experimental_features"
                                type="checkbox"
                                checked={data.experimental_features}
                                onChange={(e) => setData('experimental_features', e.target.checked)}
                                className="w-4 h-4 text-primary bg-background border-almost-black rounded-none focus:ring-primary focus:ring-2"
                            />
                        </div>
                        <div className="text-sm">
                            <label htmlFor="experimental_features" className="font-bold text-on-background">
                                Enable Experimental Features
                            </label>
                            <p className="text-on-background/70 font-mono mt-1">
                                Enable early-access features for all users. Warning: may cause instability.
                            </p>
                        </div>
                    </div>
                </div>

                <div className="flex justify-end">
                    <Button type="submit" disabled={processing}>
                        {processing ? 'Saving...' : 'Save Settings'}
                    </Button>
                </div>
            </form>
        </div>
    );
}
