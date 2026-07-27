import { User, Building2 } from 'lucide-react';
import { Card } from '@/components/ui/card';

interface RecipientSummaryProps {
    recipientName: string;
    recipientEmail: string;
    recipientType: 'user' | 'tenant';
    tenantName?: string;
}

export function RecipientSummary({ recipientName, recipientEmail, recipientType, tenantName }: RecipientSummaryProps) {
    return (
        <Card className="p-4 bg-muted/50 flex items-center justify-between">
            <div className="flex items-center gap-3">
                <div className="p-2 bg-primary/10 rounded-full text-primary">
                    {recipientType === 'tenant' ? <Building2 className="h-5 w-5" /> : <User className="h-5 w-5" />}
                </div>
                <div>
                    <h3 className="font-semibold text-foreground">{recipientName}</h3>
                    <p className="text-sm text-muted-foreground">{recipientEmail}</p>
                </div>
            </div>
            <div className="text-right">
                <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-secondary text-secondary-foreground uppercase tracking-wider">
                    {recipientType}
                </span>
                {tenantName && (
                    <p className="text-xs text-muted-foreground mt-1">{tenantName}</p>
                )}
            </div>
        </Card>
    );
}
