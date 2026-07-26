import { createContext, useContext, ReactNode, useState, useMemo, useCallback } from 'react';

interface SearchProviderState {
    // Scaffolding for Phase 2 Global Search
    isSearchOpen: boolean;
    openSearch: () => void;
    closeSearch: () => void;
    toggleSearch: () => void;
}

const SearchContext = createContext<SearchProviderState | undefined>(undefined);

export function SearchProvider({ children }: { children: ReactNode }) {
    const [isSearchOpen, setIsSearchOpen] = useState(false);

    const openSearch = useCallback(() => setIsSearchOpen(true), []);
    const closeSearch = useCallback(() => setIsSearchOpen(false), []);
    const toggleSearch = useCallback(() => setIsSearchOpen(prev => !prev), []);

    const contextValue = useMemo(() => ({
        isSearchOpen,
        openSearch,
        closeSearch,
        toggleSearch
    }), [isSearchOpen, openSearch, closeSearch, toggleSearch]);

    return (
        <SearchContext.Provider value={contextValue}>
            {children}
        </SearchContext.Provider>
    );
}

export function useSearch() {
    const context = useContext(SearchContext);
    if (context === undefined) {
        throw new Error('useSearch must be used within a SearchProvider');
    }
    return context;
}
