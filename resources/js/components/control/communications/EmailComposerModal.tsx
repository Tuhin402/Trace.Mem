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
            await axios.post('/platform/communications/send', {
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
            <DialogContent className="max-w-[1300px] sm:max-w-[1300px] lg:max-w-[1300px] w-[95vw] max-h-[95vh] lg:h-[95vh] flex flex-col overflow-hidden p-0 gap-0 border-none">
                <div className="flex-1 grid grid-cols-1 lg:grid-cols-2 overflow-hidden bg-background">
                    {/* Left Column: Editor */}
                    <div className="flex flex-col h-full lg:border-r overflow-y-auto [&::-webkit-scrollbar]:hidden [-ms-overflow-style:none] [scrollbar-width:none] p-6 lg:p-8">
                        <DialogHeader className="mb-8">
                            <DialogTitle className="text-2xl font-bold">Email Details</DialogTitle>
                        </DialogHeader>

                        <div className="space-y-8 flex-1">
                            <div className="space-y-3">
                                <Label className="text-sm font-semibold text-foreground/90 uppercase tracking-wider">People</Label>
                                <RecipientSummary 
                                    recipientName={recipientName}
                                    recipientEmail={recipientEmail}
                                    recipientType={recipientType}
                                    tenantName={tenantName}
                                />
                            </div>
                            
                            {errors.email && (
                                <div className="text-sm text-destructive font-semibold bg-destructive/10 p-3 rounded-md">
                                    Cannot send email: {errors.email}
                                </div>
                            )}

                            <div className="space-y-8">
                                <div className="space-y-3">
                                    <Label className="text-sm font-semibold text-foreground/90 uppercase tracking-wider">Email Template</Label>
                                    <TemplateSelector value={template} onValueChange={handleTemplateChange} />
                                    {errors.template && <p className="text-xs text-destructive mt-1">{errors.template}</p>}
                                </div>
                                
                                <div className="space-y-3">
                                    <Label className="text-sm font-semibold text-foreground/90 uppercase tracking-wider">Subject</Label>
                                    <SubjectInput value={subject} onChange={setSubject} error={errors.subject} />
                                </div>
                                
                                <div className="space-y-3">
                                    <Label className="text-sm font-semibold text-foreground/90 uppercase tracking-wider">Message Body</Label>
                                    <BodyEditor value={body} onChange={setBody} error={errors.body} />
                                </div>
                            </div>
                        </div>
                        
                        <div className="mt-8 pt-6 border-t">
                            <SendFooter 
                                onCancel={() => handleOpenChange(false)} 
                                onSend={handleSend} 
                                isSending={isSending}
                                disabled={!recipientEmail}
                            />
                        </div>
                    </div>
                    
                    {/* Right Column: Preview */}
                    <div className="hidden lg:flex h-full flex-col bg-muted/30 p-6 lg:p-8 min-h-[600px] lg:min-h-0 border-t lg:border-t-0">
                        <div className="flex items-center gap-2 mb-6">
                            <Label className="text-sm font-semibold text-foreground/90 uppercase tracking-wider">Preview</Label>
                            <span className="text-xs text-muted-foreground bg-muted px-2 py-0.5 rounded-full border">Live</span>
                        </div>
                        <div className="flex-1 w-full relative">
                            <div className="absolute inset-0">
                                <PreviewPanel subject={subject} body={body} />
                            </div>
                        </div>
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    );
}
