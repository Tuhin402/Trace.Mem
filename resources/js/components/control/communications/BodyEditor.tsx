import { Label } from '@/components/ui/label';

interface BodyEditorProps {
    value: string;
    onChange: (value: string) => void;
    error?: string;
}

export function BodyEditor({ value, onChange, error }: BodyEditorProps) {
    const maxLength = 10000;
    
    return (
        <div className="space-y-2">
            <div className="flex justify-between items-center">
                <Label htmlFor="body">Message Body (Plain Text)</Label>
                <span className={`text-xs ${value.length > maxLength ? 'text-destructive' : 'text-muted-foreground'}`}>
                    {value.length} / {maxLength}
                </span>
            </div>
            <textarea 
                id="body"
                value={value} 
                onChange={(e) => onChange(e.target.value)} 
                placeholder="Enter email body..."
                className={`flex w-full rounded-md border border-input bg-transparent px-3 py-2 ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 min-h-[250px] font-mono text-sm resize-y ${error ? 'border-destructive' : ''}`}
            />
            {error && <p className="text-xs text-destructive">{error}</p>}
        </div>
    );
}
