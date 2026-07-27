import { ReactNode, useEffect } from 'react';
import { ControlShellProvider, useControlShell } from '@/providers/control/ControlShellProvider';
import { SearchProvider } from '@/providers/control/SearchProvider';
import { NotificationProvider } from '@/providers/control/NotificationProvider';
import { ProfileProvider } from '@/providers/control/ProfileProvider';
import ControlSidebar from '@/components/control/sidebar/ControlSidebar';
import ControlTopbar from '@/components/control/topbar/ControlTopbar';

interface ControlLayoutProps {
    children: ReactNode;
}

function LayoutInner({ children }: ControlLayoutProps) {
    const { collapsed, mobileDrawerOpen, setMobileDrawerOpen } = useControlShell();

    useEffect(() => {
        document.body.classList.add('control-console');
        return () => {
            document.body.classList.remove('control-console');
        };
    }, []);

    // Prevent body scrolling when mobile drawer is open
    useEffect(() => {
        if (mobileDrawerOpen) {
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = '';
        }
        return () => {
            document.body.style.overflow = '';
        };
    }, [mobileDrawerOpen]);

    return (
        <div className="min-h-screen bg-background flex flex-col md:flex-row font-sans">

            {/* Mobile Drawer Overlay */}
            {mobileDrawerOpen && (
                <div
                    className="fixed inset-0 z-40 bg-black/50 backdrop-blur-sm md:hidden transition-opacity"
                    onClick={() => setMobileDrawerOpen(false)}
                    aria-hidden="true"
                />
            )}

            {/* Sidebar Component */}
            <ControlSidebar />

            {/* Main Content Area */}
            <div
                className={`flex-1 flex flex-col min-w-0 transition-all duration-300 ease-in-out transform ${collapsed ? 'md:ml-[var(--control-sidebar-collapsed)]' : 'md:ml-[var(--control-sidebar-expanded)]'
                    }`}
            >
                {/* Topbar Component */}
                <ControlTopbar />

                <main className="flex-1 p-4 md:p-8 lg:p-10 mt-16 overflow-y-auto no-scrollbar relative z-0">
                    <div className="w-full">
                        {children}
                    </div>
                </main>
            </div>
        </div>
    );
}

import { ControlToastProvider } from '@/providers/control/ControlToastProvider';

export default function ControlLayout({ children }: ControlLayoutProps) {
    return (
        <ControlToastProvider>
            <ProfileProvider>
                <ControlShellProvider>
                    <SearchProvider>
                        <NotificationProvider>
                            <LayoutInner>{children}</LayoutInner>
                        </NotificationProvider>
                    </SearchProvider>
                </ControlShellProvider>
            </ProfileProvider>
        </ControlToastProvider>
    );
}
