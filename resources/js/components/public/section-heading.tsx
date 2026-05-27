import { ReactNode } from 'react';

type Props = {
    eyebrow?: string;
    title: string;
    description?: string;
    align?: 'left' | 'center';
    children?: ReactNode;
};

export default function SectionHeading({
    eyebrow,
    title,
    description,
    align = 'left',
    children,
}: Props) {
    return (
        <div className={`section-heading ${align}`}>
            {eyebrow && <div className="section-eyebrow">{eyebrow}</div>}
            <h2>{title}</h2>
            {description && <p>{description}</p>}
            {children}
        </div>
    );
}