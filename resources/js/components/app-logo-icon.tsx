import logo from '@/assets/logos/tracemem-logo-icon.png';

type AppLogoIconProps = {
    className?: string;
};

export default function AppLogoIcon({
    className = '',
}: AppLogoIconProps) {
    return (
        <img src={logo} alt="TraceMem" className={className} draggable={false} />
    );
}