import { ReactNode } from 'react';
import PublicNavbar from '@/components/public/public-navbar';
import PublicFooter from '@/components/public/public-footer';

export default function ApiReferenceLayout({ children }: { children: ReactNode }) {
    return (
        <div className="public-shell">
            <PublicNavbar />
            <main className="public-main">{children}</main>
            <PublicFooter />
        </div>
    );
}
