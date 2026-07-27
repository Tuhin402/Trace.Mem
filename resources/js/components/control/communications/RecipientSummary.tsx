import { User, Building2 } from 'lucide-react';
import { Badge } from '@/components/ui/badge';

interface RecipientSummaryProps {
    recipientName: string;
    recipientEmail: string;
    recipientType: 'user' | 'tenant';
    tenantName?: string;
}

export function RecipientSummary({ recipientName, recipientEmail, recipientType, tenantName }: RecipientSummaryProps) {
    return (
        <div className="flex items-center gap-4 py-2">
            <div className="flex items-center justify-center p-3 rounded-full bg-primary/10 text-primary shrink-0">
                {recipientType === 'tenant' ? <Building2 className="h-6 w-6" /> : <User className="h-6 w-6" />}
            </div>
            <div className="flex flex-col">
                <div className="flex items-center gap-3">
                    <h3 className="text-base font-bold text-black">{recipientName}</h3>
                    <Badge variant="default" className="text-[10px] uppercase font-bold tracking-wider px-2 py-0.5 shadow-sm text-white">
                        {recipientType}
                    </Badge>
                </div>
                <div className="flex items-center gap-2 mt-1">
                    <p className="text-sm font-medium text-black">{recipientEmail}</p>
                </div>
            </div>
        </div>
    );
}
