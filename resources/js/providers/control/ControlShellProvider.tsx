import { createContext, useContext, useState, useEffect, ReactNode, useCallback, useMemo } from 'react';

const STORAGE_KEY = 'tm-control-shell-v1';

interface ShellState {
    version: number;
    collapsed: boolean;
    groups: Record<string, boolean>;
    pins: string[];
}

const defaultState: ShellState = {
    version: 1,
    collapsed: false,
    groups: {},
    pins: []
};

interface ControlShellContextType {
    collapsed: boolean;
    setCollapsed: (val: boolean) => void;
    toggleCollapsed: () => void;
    
    mobileDrawerOpen: boolean;
    setMobileDrawerOpen: (val: boolean) => void;
    
    pinnedItems: string[];
    togglePin: (routeName: string) => void;
    reorderPins: (newPins: string[]) => void;
    
    expandedGroups: Record<string, boolean>;
    toggleGroup: (groupId: string) => void;
}

const ControlShellContext = createContext<ControlShellContextType | undefined>(undefined);

export function ControlShellProvider({ children }: { children: ReactNode }) {
    // 1. Initial State Load (with recovery)
    const loadState = (): ShellState => {
        try {
            const raw = localStorage.getItem(STORAGE_KEY);
            if (!raw) return defaultState;
            const parsed = JSON.parse(raw);
            if (parsed.version !== defaultState.version) return defaultState; // Version mismatch, reset
            
            // Validate schema roughly to recover from corruption
            if (typeof parsed.collapsed !== 'boolean' || !Array.isArray(parsed.pins)) {
                return defaultState;
            }
            return parsed as ShellState;
        } catch (e) {
            console.warn('[ControlShellProvider] LocalStorage corrupted, resetting to defaults', e);
            return defaultState;
        }
    };

    const [state, setState] = useState<ShellState>(loadState);
    const [mobileDrawerOpen, setMobileDrawerOpen] = useState(false);

    // 2. Persist State Changes
    const saveState = useCallback((newState: ShellState) => {
        setState(newState);
        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(newState));
        } catch (e) {
            console.error('[ControlShellProvider] Failed to save state to LocalStorage', e);
        }
    }, []);

    // 3. Cross-tab Synchronization
    useEffect(() => {
        const handleStorageChange = (e: StorageEvent) => {
            if (e.key === STORAGE_KEY && e.newValue) {
                try {
                    const parsed = JSON.parse(e.newValue);
                    if (parsed.version === defaultState.version) {
                        setState(parsed);
                    }
                } catch (err) {
                    // Ignore parsing errors from other tabs
                }
            }
        };
        window.addEventListener('storage', handleStorageChange);
        return () => window.removeEventListener('storage', handleStorageChange);
    }, []);

    // 4. Actions (Memoized)
    const setCollapsed = useCallback((val: boolean) => {
        saveState({ ...state, collapsed: val });
    }, [state, saveState]);

    const toggleCollapsed = useCallback(() => {
        saveState({ ...state, collapsed: !state.collapsed });
    }, [state, saveState]);

    const togglePin = useCallback((routeName: string) => {
        const newPins = state.pins.includes(routeName)
            ? state.pins.filter(p => p !== routeName)
            : [...state.pins, routeName].slice(0, 10); // Enforce max 10 pins
        saveState({ ...state, pins: newPins });
    }, [state, saveState]);

    const reorderPins = useCallback((newPins: string[]) => {
        saveState({ ...state, pins: newPins });
    }, [state, saveState]);

    const toggleGroup = useCallback((groupId: string) => {
        saveState({
            ...state,
            groups: {
                ...state.groups,
                [groupId]: !state.groups[groupId]
            }
        });
    }, [state, saveState]);

    // 5. Context Value
    const contextValue = useMemo(() => ({
        collapsed: state.collapsed,
        setCollapsed,
        toggleCollapsed,
        mobileDrawerOpen,
        setMobileDrawerOpen,
        pinnedItems: state.pins,
        togglePin,
        reorderPins,
        expandedGroups: state.groups,
        toggleGroup
    }), [state.collapsed, state.pins, state.groups, mobileDrawerOpen, setCollapsed, toggleCollapsed, togglePin, reorderPins, toggleGroup]);

    return (
        <ControlShellContext.Provider value={contextValue}>
            {children}
        </ControlShellContext.Provider>
    );
}

export function useControlShell() {
    const context = useContext(ControlShellContext);
    if (context === undefined) {
        throw new Error('useControlShell must be used within a ControlShellProvider');
    }
    return context;
}
