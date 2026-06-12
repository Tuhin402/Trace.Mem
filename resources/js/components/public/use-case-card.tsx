import { ReactNode } from 'react';

type UseCaseCardProps = {
    icon: ReactNode;
    title: string;
    desc: string;
    tags: string[];
};

export default function UseCaseCard({ icon, title, desc, tags }: UseCaseCardProps) {
    return (
        <div className="docs-uc-card">
            <div className="docs-uc-card-header">
                <div className="docs-uc-card-icon" aria-hidden="true">
                    {icon}
                </div>
                <h3 className="docs-uc-card-title">{title}</h3>
            </div>
            
            <p className="docs-uc-card-desc">{desc}</p>
            
            <div className="docs-uc-card-tags">
                {tags.map((tag) => (
                    <span key={tag} className="docs-uc-card-tag">{tag}</span>
                ))}
            </div>
        </div>
    );
}
