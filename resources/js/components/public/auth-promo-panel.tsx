export default function AuthPromoPanel() {
    return (
        <div className="auth-right">
            {/* Structural overlays */}
            <div className="auth-promo-dots" aria-hidden="true" />
            <div className="auth-right-accent" aria-hidden="true" />

            {/* Main promo content */}
            <div className="auth-promo-content">
                <h2 className="auth-promo-headline">
                    The memory layer your{' '}
                    <span className="auth-promo-headline-accent">AI never forgets.</span>
                </h2>

                <p className="auth-promo-body">
                    TraceMem gives your AI agents persistent, structured, and
                    tenant-scoped memory. Store what matters, recall it instantly,
                    and assemble prompt-ready context — without bloating your prompts.
                </p>

                <div className="auth-promo-badge" aria-hidden="true">
                    <span className="auth-promo-badge-dot" />
                    <span>tracemem.io/api/v1 — REST · Secure · Production-Ready</span>
                </div>
            </div>
        </div>
    );
}
