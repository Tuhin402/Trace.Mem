import { ReactNode } from 'react';

type Props = {
    children: ReactNode;
    className?: string;
};

export default function PublicShell({ children, className = '' }: Props) {
    return (
        <div className={`public-shell ${className}`.trim()}>
            <div className="public-shell-inner">
                {children}
            </div>
        </div>
    );
}