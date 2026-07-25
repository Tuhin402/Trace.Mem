import { useState, useEffect } from 'react';
import { Button } from '@/components/control/ui/Button';
import { Input } from '@/components/control/ui/Input';
import { AlertTriangle } from 'lucide-react';

interface DestructiveModalProps {
    isOpen: boolean;
    onClose: () => void;
    onConfirm: () => void;
    title: string;
    description: string;
    confirmationText: string;
    actionLabel?: string;
}

export function DestructiveModal({
    isOpen,
    onClose,
    onConfirm,
    title,
    description,
    confirmationText,
    actionLabel = "Confirm Action"
}: DestructiveModalProps) {
    const [inputValue, setInputValue] = useState('');
    const [countdown, setCountdown] = useState(10);
    const [isCounting, setIsCounting] = useState(false);

    useEffect(() => {
        if (isOpen) {
            setInputValue('');
            setCountdown(10);
            setIsCounting(true);
        } else {
            setIsCounting(false);
        }
    }, [isOpen]);

    useEffect(() => {
        let timer: NodeJS.Timeout;
        if (isCounting && countdown > 0) {
            timer = setTimeout(() => setCountdown(countdown - 1), 1000);
        } else if (countdown === 0) {
            setIsCounting(false);
        }
        return () => clearTimeout(timer);
    }, [isCounting, countdown]);

    if (!isOpen) return null;

    const isMatch = inputValue === confirmationText;
    const isReady = isMatch && countdown === 0;

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-almost-black/50 backdrop-blur-sm p-4">
            <div className="w-full max-w-lg bg-surface border-2 border-error p-6 shadow-2xl">
                <div className="flex items-start gap-4 mb-6">
                    <div className="flex-shrink-0 h-12 w-12 bg-error/10 text-error flex items-center justify-center">
                        <AlertTriangle className="h-6 w-6" />
                    </div>
                    <div>
                        <h2 className="text-xl font-bold font-heading text-error uppercase tracking-tight">
                            {title}
                        </h2>
                        <p className="mt-2 text-sm text-on-background/80 font-mono">
                            {description}
                        </p>
                    </div>
                </div>

                <div className="bg-error/5 border border-error/20 p-4 mb-6">
                    <label className="block text-sm font-semibold mb-2">
                        Type <span className="font-mono bg-background px-1 border border-almost-black/10 select-all">{confirmationText}</span> to confirm
                    </label>
                    <Input
                        type="text"
                        value={inputValue}
                        onChange={(e) => setInputValue(e.target.value)}
                        placeholder={confirmationText}
                        className="font-mono"
                        autoComplete="off"
                        spellCheck="false"
                    />
                </div>

                <div className="flex justify-end gap-3">
                    <Button variant="outline" onClick={onClose} className="font-mono">
                        Cancel
                    </Button>
                    <Button
                        variant="destructive"
                        disabled={!isReady}
                        onClick={onConfirm}
                        className="font-mono relative min-w-[160px]"
                    >
                        {!isReady && countdown > 0 ? (
                            <span>Wait {countdown}s</span>
                        ) : (
                            <span>{actionLabel}</span>
                        )}
                    </Button>
                </div>
            </div>
        </div>
    );
}
