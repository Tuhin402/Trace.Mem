import AppLogoIcon from '@/components/app-logo-icon';

type AppLogoProps = {
    collapsed?: boolean;
};

export default function AppLogo({
    collapsed = false,
}: AppLogoProps) {
    return (
        <div className={`flex items-center transition-all duration-200 ${
                collapsed ? 'justify-center' : ''
            }`}>
            <div className="flex shrink-0 items-center justify-center">
                <AppLogoIcon className="h-8 w-auto" />
            </div>

            <div className={`
                    overflow-hidden transition-all duration-200
                    ${collapsed ? 'ml-0 w-0 opacity-0' : 'ml-2 w-auto opacity-100'}
                `}>
                <span className=" whitespace-nowrap text-lg font-semibold tracking-tight bg-gradient-to-r
                        from-[#e9d5ff] via-[#f5d0fe] to-[#c4b5fd] bg-clip-text text-transparent">
                    Trace.Mem
                </span>
            </div>
        </div>
    );
}