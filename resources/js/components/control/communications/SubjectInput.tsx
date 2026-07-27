import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface SubjectInputProps {
    value: string;
    onChange: (value: string) => void;
    error?: string;
}

export function SubjectInput({ value, onChange, error }: SubjectInputProps) {
    const maxLength = 255;
    
    return (
        <div className="space-y-2">
            <div className="flex justify-between items-center">
                <Label htmlFor="subject">Subject</Label>
                <span className={`text-xs ${value.length > maxLength ? 'text-destructive' : 'text-muted-foreground'}`}>
                    {value.length} / {maxLength}
                </span>
            </div>
            <Input 
                id="subject"
                value={value} 
                onChange={(e) => onChange(e.target.value)} 
                placeholder="Enter email subject"
                className={`text-black ${error || value.length > maxLength ? 'border-destructive focus-visible:ring-destructive' : ''}`}
            />
            {error && <p className="text-xs text-destructive">{error}</p>}
        </div>
    );
}
