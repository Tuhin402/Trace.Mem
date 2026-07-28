import React, { useState } from 'react';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import axios from 'axios';
import { useControlToast } from '@/providers/control/ControlToastProvider';
import { router } from '@inertiajs/react';

interface GrantFoundingOfferModalProps {
    isOpen: boolean;
    onClose: () => void;
    userId: string | number;
    userName: string;
    userEmail: string;
    isTenantContext?: boolean;
}

export function GrantFoundingOfferModal({
    isOpen,
    onClose,
    userId,
    userName,
    userEmail,
    isTenantContext = false,
}: GrantFoundingOfferModalProps) {
    const [reason, setReason] = useState('');
    const [consent, setConsent] = useState('');
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [errors, setErrors] = useState<{ reason?: string; consent?: string; server?: string }>({});

    const { success, error: toastError } = useControlToast();

    const handleClose = () => {
        if (isSubmitting) return;
        setReason('');
        setConsent('');
        setErrors({});
        onClose();
    };

    const handleSubmit = async () => {
        const newErrors: any = {};
        if (reason.trim().length < 10) {
            newErrors.reason = 'Reason must be at least 10 characters.';
        }
        if (consent !== 'CONSENT') {
            newErrors.consent = 'You must type exactly CONSENT.';
        }

        if (Object.keys(newErrors).length > 0) {
            setErrors(newErrors);
            return;
        }

        setIsSubmitting(true);
        setErrors({});

        try {
            const response = await axios.post(`/platform/billing/users/${userId}/founding-offer-override`, {
                reason: reason.trim(),
                consent,
            });

            success(response.data.message || 'Founding Offer successfully granted.');
            handleClose();

            // Refresh profile data without full reload
            router.reload({ only: ['user', 'tenant'] });

        } catch (error: any) {
            console.error('Failed to grant founding offer:', error);
            if (error.response?.data?.errors) {
                setErrors(error.response.data.errors);
            } else {
                toastError(error.response?.data?.message || 'Failed to grant override. Please check server logs.');
            }
        } finally {
            setIsSubmitting(false);
        }
    };

    return (
        <Dialog open={isOpen} onOpenChange={(open) => !open && handleClose()}>
            <DialogContent className="sm:max-w-[600px] max-h-[90vh] md:max-h-[85vh] border-destructive shadow-lg overflow-hidden flex flex-col gap-0 p-0">
                <div className="bg-destructive/10 border-b border-destructive/20 p-4 lg:p-6 shrink-0">
                    <DialogHeader>
                        <DialogTitle className="text-2xl font-extrabold tracking-tight text-destructive">
                            Administrative Billing Override
                        </DialogTitle>
                    </DialogHeader>
                    <p className="text-sm font-medium text-destructive/90 mt-2">
                        You are about to manually override normal eligibility rules. This is an irreversible administrative action that creates a permanent audit record.
                    </p>
                </div>

                <div className="p-4 lg:p-6 space-y-6 flex-1 overflow-y-auto [&::-webkit-scrollbar]:hidden [-ms-overflow-style:none] [scrollbar-width:none]">
                    {/* Context Notice */}
                    {isTenantContext && (
                        <div className="p-4 border rounded-md bg-muted/20">
                            <Label className="text-xs font-bold uppercase tracking-wider text-black block mb-2">Context</Label>
                            <p className="text-sm font-semibold text-black">This operation applies to the Tenant Owner.</p>
                            <p className="text-sm text-black mt-1">
                                The Founding Offer is attached to the owner's account and billing identity.
                            </p>
                        </div>
                    )}

                    <div className="space-y-4">
                        <Label className="text-xs font-bold uppercase tracking-wider text-black block border-b pb-2">Target Account</Label>
                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <Label className="text-xs text-black">Owner</Label>
                                <p className="text-sm font-bold text-primary">{userName}</p>
                            </div>
                            <div>
                                <Label className="text-xs text-black">Email</Label>
                                <p className="text-sm font-bold text-primary">{userEmail}</p>
                            </div>
                        </div>
                    </div>

                    <div className="grid grid-cols-2 gap-6 bg-muted/10 p-5 rounded-lg border">
                        <div>
                            <Label className="text-xs font-bold uppercase tracking-wider text-black block mb-3">Current Status</Label>
                            <ul className="text-sm space-y-2 text-black">
                                <li>• Subscription: Dependent on Razorpay</li>
                                <li>• Free Trial: Consumed or Null</li>
                                <li>• Override: <span className="font-semibold">None / Inactive</span></li>
                            </ul>
                        </div>
                        <div className="border-l pl-6">
                            <Label className="text-xs font-bold uppercase tracking-wider text-primary block mb-3">Result After Override</Label>
                            <ul className="text-sm space-y-2 text-black">
                                <li>• Subscription: Unchanged</li>
                                <li>• Free Trial: <span className="text-primary font-semibold">Eligible (Reset)</span></li>
                                <li>• Override: <span className="text-primary font-semibold">Active</span></li>
                            </ul>
                        </div>
                    </div>

                    {errors.server && (
                        <div className="text-sm text-destructive font-semibold bg-destructive/10 p-3 rounded-md">
                            {errors.server}
                        </div>
                    )}

                    <div className="space-y-3">
                        <Label className="text-sm font-semibold text-black uppercase tracking-wider">
                            Reason for Override <span className="text-destructive">*</span>
                        </Label>
                        <Textarea
                            value={reason}
                            onChange={(e) => setReason(e.target.value)}
                            placeholder="e.g., Customer support goodwill, Manual migration..."
                            className={`min-h-[100px] text-black ${errors.reason ? 'border-destructive focus-visible:ring-destructive' : ''}`}
                            disabled={isSubmitting}
                        />
                        {errors.reason && <p className="text-xs text-destructive">{errors.reason}</p>}
                    </div>

                    <div className="space-y-3">
                        <Label className="text-sm font-semibold text-black uppercase tracking-wider">
                            Confirmation <span className="text-destructive">*</span>
                        </Label>
                        <p className="text-xs text-black mb-2">
                            To proceed, please type exactly <strong className="text-black">CONSENT</strong> below.
                        </p>
                        <Input
                            value={consent}
                            onChange={(e) => setConsent(e.target.value)}
                            placeholder="Type CONSENT"
                            className={`font-mono text-black ${errors.consent ? 'border-destructive focus-visible:ring-destructive' : ''}`}
                            disabled={isSubmitting}
                        />
                        {errors.consent && <p className="text-xs text-destructive">{errors.consent}</p>}
                    </div>
                </div>

                <div className="p-4 lg:p-6 bg-muted/20 border-t flex justify-end gap-3 shrink-0">
                    <Button variant="outline" onClick={handleClose} disabled={isSubmitting}>
                        Cancel
                    </Button>
                    <Button
                        variant="destructive"
                        onClick={handleSubmit}
                        disabled={isSubmitting || consent !== 'CONSENT' || reason.trim().length < 10}
                    >
                        {isSubmitting ? 'Applying Override...' : 'Confirm Override'}
                    </Button>
                </div>
            </DialogContent>
        </Dialog>
    );
}
