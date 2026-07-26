import { createContext, useContext, ReactNode, useMemo } from 'react';

interface NotificationProviderState {
    // Scaffolding for Phase 3
    unreadCount: number;
    hasUnread: boolean;
}

const NotificationContext = createContext<NotificationProviderState | undefined>(undefined);

export function NotificationProvider({ children }: { children: ReactNode }) {
    const contextValue = useMemo(() => ({
        unreadCount: 0,
        hasUnread: false
    }), []);

    return (
        <NotificationContext.Provider value={contextValue}>
            {children}
        </NotificationContext.Provider>
    );
}

export function useNotifications() {
    const context = useContext(NotificationContext);
    if (context === undefined) {
        throw new Error('useNotifications must be used within a NotificationProvider');
    }
    return context;
}
