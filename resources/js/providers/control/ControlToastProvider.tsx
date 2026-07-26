import { createContext, useContext, useState, ReactNode, useCallback } from 'react';
import { X, CheckCircle, AlertTriangle, Info } from 'lucide-react';

interface Toast {
    id: number;
    message: string;
    type: 'success' | 'error' | 'info';
}

interface ToastContextType {
    toast: (message: string, type?: 'success' | 'error' | 'info') => void;
    success: (message: string) => void;
    error: (message: string) => void;
}

const ToastContext = createContext<ToastContextType | undefined>(undefined);

export function ControlToastProvider({ children }: { children: ReactNode }) {
    const [toasts, setToasts] = useState<Toast[]>([]);

    const toast = useCallback((message: string, type: 'success' | 'error' | 'info' = 'info') => {
        const id = Date.now() + Math.random();
        setToasts(prev => [...prev, { id, message, type }]);
        setTimeout(() => {
            setToasts(prev => prev.filter(t => t.id !== id));
        }, 4000); // 4 second duration
    }, []);

    const success = useCallback((message: string) => toast(message, 'success'), [toast]);
    const error = useCallback((message: string) => toast(message, 'error'), [toast]);

    return (
        <ToastContext.Provider value={{ toast, success, error }}>
            {children}
            {/* Toast Container */}
            <div className="fixed bottom-6 right-6 z-[9999] flex flex-col gap-3 pointer-events-none">
                {toasts.map(t => (
                    <div 
                        key={t.id} 
                        className={`pointer-events-auto flex items-center justify-between p-4 border min-w-[320px] shadow-xl transition-all animate-in slide-in-from-right-8 fade-in duration-300 ${
                            t.type === 'success' ? 'bg-[#0f0514] border-primary text-white' : 
                            t.type === 'error' ? 'bg-destructive/10 border-destructive text-destructive' : 
                            'bg-surface border-almost-black text-on-background'
                        }`}
                        style={{ borderRadius: '0px' }} // Forcing the sharp edges vibe
                    >
                        <div className="flex items-center gap-3">
                            {t.type === 'success' && <CheckCircle className="h-5 w-5 text-primary shrink-0" />}
                            {t.type === 'error' && <AlertTriangle className="h-5 w-5 shrink-0" />}
                            {t.type === 'info' && <Info className="h-5 w-5 shrink-0" />}
                            <span className="font-mono text-sm font-medium">{t.message}</span>
                        </div>
                        <button 
                            onClick={() => setToasts(prev => prev.filter(x => x.id !== t.id))} 
                            className="ml-4 opacity-70 hover:opacity-100 transition-opacity"
                        >
                            <X className="h-4 w-4" />
                        </button>
                    </div>
                ))}
            </div>
        </ToastContext.Provider>
    );
}

export const useControlToast = () => {
    const context = useContext(ToastContext);
    if (!context) throw new Error('useControlToast must be used within ControlToastProvider');
    return context;
};
