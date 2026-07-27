import { Button } from '@/components/ui/button';
import { Loader2, Send } from 'lucide-react';

interface SendFooterProps {
    onCancel: () => void;
    onSend: () => void;
    isSending: boolean;
    disabled?: boolean;
}

export function SendFooter({ onCancel, onSend, isSending, disabled }: SendFooterProps) {
    return (
        <div className="flex justify-end gap-3 pt-4 border-t mt-6">
            <Button variant="outline" onClick={onCancel} disabled={isSending}>
                Cancel
            </Button>
            <Button onClick={onSend} disabled={isSending || disabled} className="text-white">
                {isSending ? (
                    <>
                        <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                        Queuing Email...
                    </>
                ) : (
                    <>
                        <Send className="mr-2 h-4 w-4" />
                        Send Email
                    </>
                )}
            </Button>
        </div>
    );
}
