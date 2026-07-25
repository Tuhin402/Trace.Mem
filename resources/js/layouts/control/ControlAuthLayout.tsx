import { ReactNode, useEffect } from 'react';

interface ControlAuthLayoutProps {
    children: ReactNode;
}

export default function ControlAuthLayout({ children }: ControlAuthLayoutProps) {
    useEffect(() => {
        document.body.classList.add('control-console');
        return () => {
            document.body.classList.remove('control-console');
        };
    }, []);

    return (
        <div className="min-h-screen bg-background flex flex-col justify-center py-12 sm:px-6 lg:px-8">
            {children}
        </div>
    );
}
