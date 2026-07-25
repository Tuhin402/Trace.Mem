import { useState, useEffect, useRef } from 'react';
import { Bell, Search, User as UserIcon, Loader2 } from 'lucide-react';
import { Link } from '@inertiajs/react';

export default function Topbar() {
    const [searchQuery, setSearchQuery] = useState('');
    const [results, setResults] = useState<any[]>([]);
    const [isSearching, setIsSearching] = useState(false);
    const [showDropdown, setShowDropdown] = useState(false);
    const searchRef = useRef<HTMLDivElement>(null);

    // Close dropdown on click outside
    useEffect(() => {
        function handleClickOutside(event: MouseEvent) {
            if (searchRef.current && !searchRef.current.contains(event.target as Node)) {
                setShowDropdown(false);
            }
        }
        document.addEventListener("mousedown", handleClickOutside);
        return () => document.removeEventListener("mousedown", handleClickOutside);
    }, []);

    // Debounced search effect
    useEffect(() => {
        if (!searchQuery.trim()) {
            setResults([]);
            setIsSearching(false);
            setShowDropdown(false);
            return;
        }

        const timer = setTimeout(async () => {
            setIsSearching(true);
            try {
                // Ensure query is strictly a string and fetch
                const response = await fetch(`/api/search?q=${encodeURIComponent(searchQuery)}`);
                const data = await response.json();
                setResults(data.results || []);
                setShowDropdown(true);
            } catch (error) {
                console.error("Search failed", error);
            } finally {
                setIsSearching(false);
            }
        }, 300); // 300ms debounce

        return () => clearTimeout(timer);
    }, [searchQuery]);

    return (
        <header className="fixed top-0 right-0 left-0 md:left-64 z-40 flex h-16 shrink-0 items-center gap-x-4 border-b border-almost-black/10 bg-background px-4 shadow-sm sm:gap-x-6 sm:px-6 lg:px-8 transition-all duration-300">
            <div className="flex flex-1 gap-x-4 self-stretch lg:gap-x-6 relative" ref={searchRef}>
                
                {/* Global Search */}
                <div className="relative flex flex-1 items-center">
                    <label htmlFor="search-field" className="sr-only">
                        Search Users, Tenants, APIs...
                    </label>
                    <Search
                        className="pointer-events-none absolute left-0 h-5 w-5 text-on-background/50"
                        aria-hidden="true"
                    />
                    <input
                        id="search-field"
                        className="block h-full w-full border-0 py-0 pl-8 pr-0 text-on-background placeholder:text-on-background/50 focus:ring-0 sm:text-sm font-mono bg-transparent outline-none"
                        placeholder="Search entities (User, Workspace, Memory ID)..."
                        type="search"
                        value={searchQuery}
                        onChange={(e) => setSearchQuery(e.target.value)}
                        onFocus={() => {
                            if (searchQuery.trim()) setShowDropdown(true);
                        }}
                        autoComplete="off"
                    />
                    {isSearching && (
                        <Loader2 className="absolute right-0 h-4 w-4 animate-spin text-on-background/50" />
                    )}
                </div>

                {/* Search Results Dropdown */}
                {showDropdown && (
                    <div className="absolute top-full left-0 mt-1 w-full max-w-2xl bg-surface border border-almost-black shadow-lg max-h-96 overflow-y-auto z-50">
                        {results.length === 0 ? (
                            <div className="p-4 text-sm font-mono text-on-background/60">
                                No secure results found for "{searchQuery}"
                            </div>
                        ) : (
                            <ul className="py-2">
                                {results.map((item, idx) => (
                                    <li key={`${item.type}-${item.id}-${idx}`}>
                                        <Link 
                                            href={item.url} 
                                            className="flex flex-col px-4 py-2 hover:bg-almost-black/5 transition-colors border-b border-almost-black/5 last:border-0"
                                            onClick={() => setShowDropdown(false)}
                                        >
                                            <div className="flex items-center justify-between">
                                                <span className="font-semibold text-sm">{item.title}</span>
                                                <span className="text-xs font-mono bg-primary/10 text-primary px-1.5 py-0.5">
                                                    {item.type}
                                                </span>
                                            </div>
                                            <span className="text-xs text-on-background/60 mt-0.5">
                                                {item.subtitle}
                                            </span>
                                        </Link>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </div>
                )}

                <div className="flex items-center gap-x-4 lg:gap-x-6">
                    {/* Notifications */}
                    <button type="button" className="-m-2.5 p-2.5 text-on-background/70 hover:text-on-background relative">
                        <span className="sr-only">View notifications</span>
                        <Bell className="h-6 w-6" aria-hidden="true" />
                        <span className="absolute top-2 right-2 h-2 w-2 rounded-full bg-primary ring-2 ring-background"></span>
                    </button>

                    {/* Separator */}
                    <div className="hidden lg:block lg:h-6 lg:w-px lg:bg-almost-black/10" aria-hidden="true" />

                    {/* Profile */}
                    <div className="flex items-center p-1.5 cursor-pointer hover:bg-almost-black/5 transition-colors">
                        <div className="h-8 w-8 bg-primary/10 border border-primary/20 text-primary flex items-center justify-center font-bold">
                            <UserIcon className="h-5 w-5" />
                        </div>
                        <span className="ml-2 text-sm font-semibold leading-6 text-on-background hidden md:block">
                            Platform Admin
                        </span>
                    </div>
                </div>
            </div>
        </header>
    );
}
