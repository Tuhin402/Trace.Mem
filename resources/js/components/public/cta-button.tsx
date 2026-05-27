import { Link } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';

type Props = {
    href: string;
    label: string;
    variant?: 'primary' | 'secondary';
    size?: 'default' | 'lg';
    external?: boolean;
};

export default function CtaButton({
    href,
    label,
    variant = 'primary',
    size = 'default',
    external = false,
}: Props) {
    const cls = `cta-btn ${variant}${size === 'lg' ? ' lg' : ''}`;

    if (external) {
        return (
            <a href={href} target="_blank" rel="noreferrer" className={cls}>
                <span className="cta-label">{label}</span>
                <span className="cta-arrow">
                    <ArrowRight size={14} />
                </span>
            </a>
        );
    }

    return (
        <Link href={href} className={cls}>
            <span className="cta-label">{label}</span>
            <span className="cta-arrow">
                <ArrowRight size={14} />
            </span>
        </Link>
    );
}