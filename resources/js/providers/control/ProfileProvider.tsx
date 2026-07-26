import { createContext, useContext, ReactNode, useMemo } from 'react';
import { usePage } from '@inertiajs/react';

interface ProfileProviderState {
    // Scaffolding for Phase 4 Profile Integration
    user: any | null;
}

const ProfileContext = createContext<ProfileProviderState | undefined>(undefined);

export function ProfileProvider({ children }: { children: ReactNode }) {
    const { auth } = usePage<any>().props;

    const contextValue = useMemo(() => ({
        user: auth?.user || null
    }), [auth]);

    return (
        <ProfileContext.Provider value={contextValue}>
            {children}
        </ProfileContext.Provider>
    );
}

export function useProfile() {
    const context = useContext(ProfileContext);
    if (context === undefined) {
        throw new Error('useProfile must be used within a ProfileProvider');
    }
    return context;
}
