import { ReactNode } from 'react';
import PublicNavbar from '@/components/public/public-navbar';
import PublicFooter from '@/components/public/public-footer';
import WhatsAppFloatButton from '@/components/public/whatsapp-float-button';

export default function PublicLayout({ children }: { children: ReactNode }) {
    return (
        <div className="public-shell">
            <PublicNavbar />
            <main className="public-main">{children}</main>
            <PublicFooter />
            <WhatsAppFloatButton />
        </div>
    );
}