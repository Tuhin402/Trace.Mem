import { Bell, Search, User as UserIcon } from 'lucide-react';

export default function Topbar() {
    return (
        <header className="fixed top-0 right-0 left-0 md:left-64 z-40 flex h-16 shrink-0 items-center gap-x-4 border-b border-almost-black/10 bg-background px-4 shadow-sm sm:gap-x-6 sm:px-6 lg:px-8 transition-all duration-300">
            <div className="flex flex-1 gap-x-4 self-stretch lg:gap-x-6">
                
                {/* Global Search */}
                <form className="relative flex flex-1" action="#" method="GET">
                    <label htmlFor="search-field" className="sr-only">
                        Search Users, Tenants, APIs...
                    </label>
                    <Search
                        className="pointer-events-none absolute inset-y-0 left-0 h-full w-5 text-on-background/50"
                        aria-hidden="true"
                    />
                    <input
                        id="search-field"
                        className="block h-full w-full border-0 py-0 pl-8 pr-0 text-on-background placeholder:text-on-background/50 focus:ring-0 sm:text-sm font-mono bg-transparent outline-none"
                        placeholder="Search entities (User, Tenant, Invoice, Memory ID)..."
                        type="search"
                        name="search"
                    />
                </form>

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
                    <div className="flex items-center p-1.5 cursor-pointer">
                        <div className="h-8 w-8 bg-primary/10 border border-primary/20 text-primary flex items-center justify-center font-bold">
                            <UserIcon className="h-5 w-5" />
                        </div>
                        <span className="ml-2 text-sm font-semibold leading-6 text-on-background hidden md:block">
                            Admin
                        </span>
                    </div>
                </div>
            </div>
        </header>
    );
}
