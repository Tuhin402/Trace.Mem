import { useState, useEffect } from 'react';
import axios from 'axios';
import { Loader2 } from 'lucide-react';

interface PreviewPanelProps {
    subject: string;
    body: string;
}

export function PreviewPanel({ subject, body }: PreviewPanelProps) {
    const [html, setHtml] = useState<string>('');
    const [loading, setLoading] = useState<boolean>(false);

    useEffect(() => {
        if (!subject && !body) return;
        
        const timeoutId = setTimeout(() => {
            fetchPreview();
        }, 500); // debounce
        
        return () => clearTimeout(timeoutId);
    }, [subject, body]);

    const fetchPreview = async () => {
        setLoading(true);
        try {
            const response = await axios.post('/platform/communications/preview', {
                subject,
                body,
            });
            setHtml(response.data.html);
        } catch (error) {
            console.error('Failed to fetch preview', error);
            setHtml('<div class="text-red-500 p-4">Failed to load preview.</div>');
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="relative border rounded-xl bg-white h-full flex flex-col overflow-hidden">
            {loading && (
                <div className="absolute inset-0 bg-white/50 flex items-center justify-center z-10">
                    <Loader2 className="h-6 w-6 animate-spin text-muted-foreground" />
                </div>
            )}
            <div className="flex-1 overflow-y-auto [&::-webkit-scrollbar]:hidden [-ms-overflow-style:none] [scrollbar-width:none]">
                {html ? (
                    <iframe
                        srcDoc={html}
                        title="Email Preview"
                        className="w-full h-full min-h-[800px] border-0"
                    />
                ) : (
                    <div className="p-8 text-center text-muted-foreground mt-10">
                        Type a message to see the preview.
                    </div>
                )}
            </div>
        </div>
    );
}
