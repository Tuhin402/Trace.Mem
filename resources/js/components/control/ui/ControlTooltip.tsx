import { useState, useRef, useEffect, ReactNode } from 'react';
import { createPortal } from 'react-dom';

interface ControlTooltipProps {
    children: ReactNode;
    label: string;
    delay?: number;
    disabled?: boolean;
}

export function ControlTooltip({ children, label, delay = 200, disabled = false }: ControlTooltipProps) {
    const [isVisible, setIsVisible] = useState(false);
    const [coords, setCoords] = useState({ top: 0, left: 0 });
    const timerRef = useRef<NodeJS.Timeout | null>(null);
    const triggerRef = useRef<HTMLDivElement>(null);

    const handleMouseEnter = () => {
        if (disabled) return;
        timerRef.current = setTimeout(() => {
            if (triggerRef.current) {
                const rect = triggerRef.current.getBoundingClientRect();
                setCoords({
                    top: rect.top + rect.height / 2,
                    left: rect.right + 8 // 8px spacing
                });
            }
            setIsVisible(true);
        }, delay);
    };

    const handleMouseLeave = () => {
        if (timerRef.current) clearTimeout(timerRef.current);
        setIsVisible(false);
    };

    useEffect(() => {
        return () => {
            if (timerRef.current) clearTimeout(timerRef.current);
        };
    }, []);

    const tooltip = isVisible ? createPortal(
        <div 
            role="tooltip"
            className="fixed z-[100] px-2 py-1 text-xs font-medium text-white bg-black rounded shadow-lg whitespace-nowrap pointer-events-none transform -translate-y-1/2"
            style={{ top: coords.top, left: coords.left }}
            aria-hidden={!isVisible}
        >
            {label}
        </div>,
        document.body
    ) : null;

    return (
        <div 
            ref={triggerRef}
            className="inline-flex"
            onMouseEnter={handleMouseEnter}
            onMouseLeave={handleMouseLeave}
            onFocus={handleMouseEnter}
            onBlur={handleMouseLeave}
        >
            {children}
            {tooltip}
        </div>
    );
}
