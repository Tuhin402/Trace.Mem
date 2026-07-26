import { useSearch } from '@/providers/control/SearchProvider';
import { Search } from 'lucide-react';

export default function ControlSearch() {
    const { isSearchOpen, openSearch } = useSearch();

    return (
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
                placeholder="Search entities..."
                type="search"
                autoComplete="off"
                onFocus={openSearch}
            />
            {/* Future: dropdown portal for search results based on isSearchOpen */}
        </div>
    );
}
