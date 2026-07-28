import { useState, useEffect } from 'react';
import axios from 'axios';
import { Card } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Eye, Mail, AlertCircle, Clock, CheckCircle2 } from 'lucide-react';

interface CommunicationLog {
    id: string;
    subject: string;
    template: string;
    sender: string;
    status: string;
    sent_at: string;
    body: string;
}

interface HistoryPanelProps {
    recipientType: 'user' | 'tenant';
    recipientId: string | number;
}

export function HistoryPanel({ recipientType, recipientId }: HistoryPanelProps) {
    const [history, setHistory] = useState<CommunicationLog[]>([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(false);

    const fetchHistory = async () => {
        setLoading(true);
        setError(false);
        try {
            const res = await axios.get(`/platform/communications/history/${recipientType}/${recipientId}`);
            setHistory(Array.isArray(res.data.history) ? res.data.history : []);
        } catch (e) {
            console.error(e);
            setError(true);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchHistory();
    }, [recipientType, recipientId]);

    const getStatusIcon = (status: string) => {
        switch (status) {
            case 'sent': return <CheckCircle2 className="h-4 w-4 text-green-500" />;
            case 'failed': return <AlertCircle className="h-4 w-4 text-red-500" />;
            case 'queued':
            case 'processing': return <Clock className="h-4 w-4 text-blue-500" />;
            default: return <Mail className="h-4 w-4 text-gray-500" />;
        }
    };

    if (loading) return <div className="text-sm text-muted-foreground">Loading history...</div>;
    if (error) return <div className="text-sm text-destructive">Failed to load communication history.</div>;
    if (!history || history.length === 0) return <div className="text-sm text-muted-foreground">No recent communications found.</div>;

    return (
        <div className="w-full">
            <div className="space-y-3">
                {history.map((log) => (
                    <div key={log.id} className="p-4 rounded-xl border bg-background shadow-sm transition-all hover:border-primary/50 hover:shadow-md">
                        <div className="flex justify-between items-start mb-2">
                            <div className="flex items-center gap-2 font-medium">
                                {getStatusIcon(log.status)}
                                {log.subject}
                            </div>
                            <span className="text-xs font-medium text-black">{log.sent_at}</span>
                        </div>
                        <div className="flex justify-between items-center text-foreground/80 text-xs mt-3">
                            <div className="flex gap-3 items-center">
                                <Badge variant="default" className="text-[10px] uppercase font-bold tracking-wider px-2 py-0.5 text-white">{log.template}</Badge>
                                <span className="font-medium text-black">Sent by {log.sender}</span>
                            </div>
                            
                            <Dialog>
                                <DialogTrigger asChild>
                                    <button className="flex items-center gap-1.5 font-semibold hover:text-primary transition-colors bg-muted px-3 py-1.5 rounded-md hover:bg-primary/10">
                                        <Eye className="h-3 w-3" /> Preview
                                    </button>
                                </DialogTrigger>
                                <DialogContent className="max-w-3xl h-[80vh] flex flex-col overflow-hidden">
                                    <DialogHeader>
                                        <DialogTitle>View Rendered Email</DialogTitle>
                                    </DialogHeader>
                                    <div className="flex-1 overflow-y-auto mt-4 rounded-md border [&::-webkit-scrollbar]:hidden [-ms-overflow-style:none] [scrollbar-width:none]">
                                        <iframe
                                            srcDoc={log.body + '<style>::-webkit-scrollbar { display: none; } * { -ms-overflow-style: none; scrollbar-width: none; }</style>'}
                                            title="Email Preview"
                                            className="w-full h-full min-h-[600px] border-0"
                                        />
                                    </div>
                                </DialogContent>
                            </Dialog>
                        </div>
                    </div>
                ))}
            </div>
        </div>
    );
}
