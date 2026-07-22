import { useEffect, useState } from 'react';
import { usePage } from '@inertiajs/react';
import { Copy, X, CheckCircle2 } from 'lucide-react';
import { useToast } from '@/components/app/toast';

export function NewApiKeyBanner() {
    const { props } = usePage<any>();
    const { toast } = useToast();
    const flashKey = props.flash?.plain_key ?? null;
    const [apiKey, setApiKey] = useState<string | null>(null);

    useEffect(() => {
        // If the server sends a new key, store it in sessionStorage
        if (flashKey) {
            sessionStorage.setItem('new_api_key', flashKey);
            setApiKey(flashKey);
        } else {
            // Otherwise, check if we have one saved from a previous page load
            const saved = sessionStorage.getItem('new_api_key');
            if (saved) {
                setApiKey(saved);
            }
        }
    }, [flashKey]);

    const handleDismiss = () => {
        sessionStorage.removeItem('new_api_key');
        setApiKey(null);
    };

    const handleCopy = async () => {
        if (!apiKey) return;
        try {
            await navigator.clipboard.writeText(apiKey);
            toast('API key copied to clipboard.', 'success');
        } catch {
            toast('Unable to copy key.', 'error');
        }
    };

    if (!apiKey) return null;

    return (
        <div className="bg-emerald-500/10 border-b border-emerald-500/20 px-4 py-3 sm:px-6">
            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 max-w-7xl mx-auto">
                <div className="flex-1 min-w-0">
                    <div className="flex items-center gap-2 text-sm font-semibold text-emerald-800 dark:text-emerald-400">
                        <CheckCircle2 className="size-4 shrink-0" />
                        New API Key Generated
                    </div>
                    <div className="mt-1.5 flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-3 text-sm text-emerald-700 dark:text-emerald-300">
                        <code className="bg-emerald-500/20 px-2 py-0.5 rounded font-mono text-xs break-all sm:break-normal select-all">
                            {apiKey}
                        </code>
                        <span className="text-xs opacity-90">Please copy this key now and store it securely. It will not be shown again.</span>
                    </div>
                </div>
                
                <div className="flex items-center gap-2 shrink-0 self-end sm:self-auto">
                    <button
                        type="button"
                        className="flex items-center gap-1.5 px-3 py-1.5 bg-emerald-600 dark:bg-emerald-500 text-white rounded-md text-sm font-medium hover:bg-emerald-700 dark:hover:bg-emerald-600 transition-colors shadow-sm"
                        onClick={handleCopy}
                        title="Copy API Key"
                    >
                        <Copy size={14} />
                        Copy
                    </button>
                    <button
                        type="button"
                        onClick={handleDismiss}
                        className="p-1.5 text-emerald-600 hover:text-emerald-900 dark:text-emerald-400 dark:hover:text-emerald-100 hover:bg-emerald-500/20 rounded-md transition-colors"
                        title="Dismiss"
                        aria-label="Dismiss"
                    >
                        <X size={16} />
                    </button>
                </div>
            </div>
        </div>
    );
}
