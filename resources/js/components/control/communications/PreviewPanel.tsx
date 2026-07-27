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
            const response = await axios.post('/control/platform/communications/preview', {
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
        <div className="relative border rounded-md bg-white min-h-[400px]">
            {loading && (
                <div className="absolute inset-0 bg-white/50 flex items-center justify-center z-10">
                    <Loader2 className="h-6 w-6 animate-spin text-muted-foreground" />
                </div>
            )}
            <div className="h-[500px] overflow-y-auto">
                {html ? (
                    <iframe
                        srcDoc={html}
                        title="Email Preview"
                        className="w-full h-[800px] border-0"
                    />
                ) : (
                    <div className="p-8 text-center text-muted-foreground">
                        Type a message to see the preview.
                    </div>
                )}
            </div>
        </div>
    );
}
