import { useState, useCallback } from 'react';
import axios from 'axios';
import { useControlToast } from '@/providers/control/ControlToastProvider';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { RecipientSummary } from './RecipientSummary';
import { TemplateSelector } from './TemplateSelector';
import { SubjectInput } from './SubjectInput';
import { BodyEditor } from './BodyEditor';
import { PreviewPanel } from './PreviewPanel';
import { HistoryPanel } from './HistoryPanel';
import { SendFooter } from './SendFooter';
import { Label } from '@/components/ui/label';

interface EmailComposerModalProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    recipientName: string;
    recipientEmail: string;
    recipientType: 'user' | 'tenant';
    recipientId: string;
    tenantName?: string;
}

export function EmailComposerModal({
    open,
    onOpenChange,
    recipientName,
    recipientEmail,
    recipientType,
    recipientId,
    tenantName,
}: EmailComposerModalProps) {
    const [template, setTemplate] = useState('');
    const [subject, setSubject] = useState('');
    const [body, setBody] = useState('');
    const [isSending, setIsSending] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>({});
    
    const { success, error: toastError } = useControlToast();

    // Check for unsaved changes
    const hasUnsavedChanges = template !== '' || subject !== '' || body !== '';

    const handleOpenChange = (newOpen: boolean) => {
        if (!newOpen && hasUnsavedChanges) {
            if (!window.confirm('You have unsaved changes. Are you sure you want to close this window?')) {
                return;
            }
        }
        
        if (!newOpen) {
            // reset state on close
            setTemplate('');
            setSubject('');
            setBody('');
            setErrors({});
        }
        
        onOpenChange(newOpen);
    };

    const handleTemplateChange = (newTemplate: string, defaultSubject: string, defaultBody: string) => {
        setTemplate(newTemplate);
        setSubject(defaultSubject);
        setBody(defaultBody);
        setErrors({});
    };

    const validate = () => {
        const newErrors: Record<string, string> = {};
        if (!template) newErrors.template = 'Please select a template.';
        if (!subject || subject.length > 255) newErrors.subject = 'Subject is required and must be less than 255 characters.';
        if (!body || body.length > 10000) newErrors.body = 'Body is required and must be less than 10000 characters.';
        if (!recipientEmail) newErrors.email = 'Recipient must have an email address.';
        setErrors(newErrors);
        return Object.keys(newErrors).length === 0;
    };

    const handleSend = async () => {
        if (!validate()) return;
        
        setIsSending(true);
        try {
            await axios.post('/control/platform/communications/send', {
                recipient_uuid: recipientId,
                recipient_type: recipientType,
                recipient_email: recipientEmail,
                recipient_name: recipientName,
                template: template,
                subject: subject,
                body: body,
            });
            
            success('Operational Email Queued Successfully!');
            // Reset and close
            setTemplate('');
            setSubject('');
            setBody('');
            onOpenChange(false);
            
        } catch (error: any) {
            console.error('Failed to send email:', error);
            if (error.response?.status === 429) {
                toastError('You are sending emails too quickly or clicking twice. Please wait.');
            } else if (error.response?.data?.message) {
                toastError(error.response.data.message);
            } else {
                toastError('Failed to queue email. The queue server may be unreachable.');
            }
        } finally {
            setIsSending(false);
        }
    };

    return (
        <Dialog open={open} onOpenChange={handleOpenChange}>
            <DialogContent className="max-w-[1200px] h-[90vh] flex flex-col overflow-hidden">
                <DialogHeader>
                    <DialogTitle>Send Operational Communication</DialogTitle>
                </DialogHeader>
                
                <div className="flex-1 overflow-y-auto pr-2 grid grid-cols-1 lg:grid-cols-2 gap-8">
                    {/* Left Column: Editor */}
                    <div className="space-y-6">
                        <RecipientSummary 
                            recipientName={recipientName}
                            recipientEmail={recipientEmail}
                            recipientType={recipientType}
                            tenantName={tenantName}
                        />
                        
                        {errors.email && (
                            <div className="text-sm text-destructive font-semibold bg-destructive/10 p-3 rounded-md">
                                Cannot send email: {errors.email}
                            </div>
                        )}

                        <div className="space-y-4">
                            <div>
                                <TemplateSelector value={template} onValueChange={handleTemplateChange} />
                                {errors.template && <p className="text-xs text-destructive mt-1">{errors.template}</p>}
                            </div>
                            
                            <SubjectInput value={subject} onChange={setSubject} error={errors.subject} />
                            
                            <BodyEditor value={body} onChange={setBody} error={errors.body} />
                        </div>
                    </div>
                    
                    {/* Right Column: Preview & History */}
                    <div className="space-y-6 flex flex-col h-full">
                        <div className="flex-1 space-y-2">
                            <Label>Live Preview</Label>
                            <PreviewPanel subject={subject} body={body} />
                        </div>
                        
                        <div className="space-y-2">
                            <Label>Recent Communications</Label>
                            <HistoryPanel recipientType={recipientType} recipientId={recipientId} />
                        </div>
                    </div>
                </div>

                <SendFooter 
                    onCancel={() => handleOpenChange(false)} 
                    onSend={handleSend} 
                    isSending={isSending}
                    disabled={!recipientEmail}
                />
            </DialogContent>
        </Dialog>
    );
}
