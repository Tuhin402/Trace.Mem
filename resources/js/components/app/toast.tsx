import { createPortal } from 'react-dom';
import { useCallback, useEffect, useRef, useState } from 'react';
import { CheckCircle2, XCircle, AlertTriangle, Info, X } from 'lucide-react';

/* ── Types ──────────────────────────────────────────────── */
export type ToastType = 'success' | 'error' | 'warning' | 'info';

interface ToastItem {
    id: string;
    message: string;
    type: ToastType;
    exiting?: boolean;
}

/* ── Single Toast ────────────────────────────────────────── */
const ICON = {
    success: CheckCircle2,
    error:   XCircle,
    warning: AlertTriangle,
    info:    Info,
};

function Toast({
    item,
    onClose,
}: {
    item: ToastItem;
    onClose: (id: string) => void;
}) {
    const Icon = ICON[item.type];

    return (
        <div
            className={`app-toast app-toast-${item.type}${item.exiting ? ' exiting' : ''}`}
            role="alert"
            aria-live="polite"
        >
            <span className="app-toast-icon" aria-hidden="true">
                <Icon size={16} />
            </span>

            <div className="app-toast-body">
                <p className="app-toast-message">{item.message}</p>
            </div>

            <button
                type="button"
                className="app-toast-close"
                aria-label="Dismiss notification"
                onClick={() => onClose(item.id)}
            >
                <X size={14} />
            </button>
        </div>
    );
}

/* ── Portal container ────────────────────────────────────── */
function ToastPortal({ items, onClose }: { items: ToastItem[]; onClose: (id: string) => void }) {
    if (typeof document === 'undefined' || items.length === 0) return null;

    return createPortal(
        <div className="app-toast-portal" aria-label="Notifications">
            {items.map((item) => (
                <Toast key={item.id} item={item} onClose={onClose} />
            ))}
        </div>,
        document.body,
    );
}

/* ── Hook ────────────────────────────────────────────────── */
let globalToast: ((message: string, type?: ToastType) => void) | null = null;

export function useToast() {
    const [items, setItems] = useState<ToastItem[]>([]);
    const timersRef = useRef<Map<string, ReturnType<typeof setTimeout>>>(new Map());

    const dismiss = useCallback((id: string) => {
        // Start exit animation
        setItems((prev) =>
            prev.map((t) => (t.id === id ? { ...t, exiting: true } : t)),
        );
        // Remove after animation
        const t = setTimeout(() => {
            setItems((prev) => prev.filter((t) => t.id !== id));
            timersRef.current.delete(id);
        }, 200);
        timersRef.current.set(`exit-${id}`, t);
    }, []);

    const toast = useCallback(
        (message: string, type: ToastType = 'success') => {
            const id = `${Date.now()}-${Math.random().toString(36).slice(2, 7)}`;
            setItems((prev) => [...prev, { id, message, type }]);

            const timer = setTimeout(() => dismiss(id), 3500);
            timersRef.current.set(id, timer);
        },
        [dismiss],
    );

    useEffect(() => {
        globalToast = toast;
        return () => {
            globalToast = null;
            timersRef.current.forEach((t) => clearTimeout(t));
        };
    }, [toast]);

    const Toasts = () => <ToastPortal items={items} onClose={dismiss} />;

    return { toast, Toasts };
}

/** Call outside React (e.g., in event handlers) if needed. */
export function triggerToast(message: string, type: ToastType = 'success') {
    globalToast?.(message, type);
}
