import { ReactNode, useEffect } from 'react';
import Sidebar from '@/components/control/Sidebar';
import Topbar from '@/components/control/Topbar';

interface ControlLayoutProps {
    children: ReactNode;
}

export default function ControlLayout({ children }: ControlLayoutProps) {
    useEffect(() => {
        document.body.classList.add('control-console');
        return () => {
            document.body.classList.remove('control-console');
        };
    }, []);

    return (
        <div className="min-h-screen bg-background flex flex-col md:flex-row">
            {/* Sidebar Component (Fixed) */}
            <Sidebar />

            <div className="flex-1 flex flex-col min-w-0 md:ml-64 transition-all duration-300">
                {/* Topbar Component (Fixed) */}
                <Topbar />

                <main className="flex-1 p-4 md:p-8 mt-16 overflow-y-auto no-scrollbar">
                    <div className="mx-auto w-full max-w-7xl">
                        {children}
                    </div>
                </main>
            </div>
        </div>
    );
}
