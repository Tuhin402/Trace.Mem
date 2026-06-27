import { Head } from '@inertiajs/react';
import { Helmet } from 'react-helmet-async';
import { useDomains } from '@/lib/domains';
import {
    LegalPageShell,
    LegalHero,
    LegalSection,
    type TocEntry,
} from '@/components/public/legal-page-shell';
import '../../../css/pages/legal.css';

const tocEntries: TocEntry[] = [
    { id: 'acceptance',          num: '01', label: 'Acceptance of Terms' },
    { id: 'eligibility',         num: '02', label: 'Eligibility' },
    { id: 'account',             num: '03', label: 'Account Registration' },
    { id: 'api-keys',            num: '04', label: 'API Keys & Authentication' },
    { id: 'developer-responsibilities', num: '05', label: 'Developer Responsibilities' },
    { id: 'acceptable-use',      num: '06', label: 'Acceptable Use' },
    { id: 'availability',        num: '07', label: 'Service Availability' },
    { id: 'billing',             num: '08', label: 'Billing & Payments' },
    { id: 'termination',         num: '09', label: 'Suspension & Termination' },
    { id: 'ip',                  num: '10', label: 'Intellectual Property' },
    { id: 'ai-processing',       num: '11', label: 'AI Processing & Output' },
    { id: 'warranties',          num: '12', label: 'Disclaimer of Warranties' },
    { id: 'liability',           num: '13', label: 'Limitation of Liability' },
    { id: 'indemnification',     num: '14', label: 'Indemnification' },
    { id: 'force-majeure',       num: '15', label: 'Force Majeure' },
    { id: 'governing-law',       num: '16', label: 'Governing Law' },
    { id: 'dispute-resolution',  num: '17', label: 'Dispute Resolution' },
    { id: 'changes',             num: '18', label: 'Changes to These Terms' },
    { id: 'general',             num: '19', label: 'General Provisions' },
    { id: 'contact',             num: '20', label: 'Contact Information' },
];

export default function TermsOfUse() {
    const { siteUrl } = useDomains();
    return (
        <>
            <Head title="Terms of Use" />
            <Helmet>
                <meta
                    name="description"
                    content="Read the terms governing the use of the Trace.Mem platform."
                />
                <meta name="robots" content="index, follow" />
                <link rel="canonical" href={`${siteUrl}/terms-of-use`} />
                <meta property="og:title" content="Terms of Use" />
                <meta property="og:description" content="Read the terms governing the use of the Trace.Mem platform." />
                <meta property="og:url" content={`${siteUrl}/terms-of-use`} />
            </Helmet>

            <LegalPageShell
                entries={tocEntries}
                hero={
                    <LegalHero
                        eyebrow="Legal · Terms"
                        title="Terms of Use"
                        subtitle="The binding agreement governing your access to and use of the Trace.Mem platform, APIs, and related services."
                        lastUpdated="June 2026"
                        readingTime="~20 min read"
                    />
                }
            >
                {/* ── 01 Acceptance of Terms ────────────────────────── */}
                <LegalSection id="acceptance" num="01" title="Acceptance of Terms">
                    <p className="legal-p">
                        By registering for, accessing, or using the Trace.Mem platform, its APIs,
                        developer dashboard, documentation, or any related services (collectively, the
                        "Services"), you ("Developer," "you," or "your") agree to be bound by these
                        Terms of Use ("Terms").
                    </p>
                    <p className="legal-p">
                        If you are accepting these Terms on behalf of a company or other legal entity,
                        you represent and warrant that you have the authority to bind that entity, and
                        references to "you" shall refer to that entity.
                    </p>
                    <div className="legal-callout">
                        <p>
                            If you do not agree to these Terms, do not register for or use the Services.
                        </p>
                    </div>
                    <p className="legal-p">
                        These Terms form a binding legal agreement between you and Trace.Mem ("Trace.Mem,"
                        "we," "us," or "our").
                    </p>
                </LegalSection>

                {/* ── 02 Eligibility ────────────────────────────────── */}
                <LegalSection id="eligibility" num="02" title="Eligibility">
                    <h3 className="legal-h3">Age Requirement</h3>
                    <p className="legal-p">
                        You must be at least 18 years of age to use the Services. By using Trace.Mem,
                        you confirm that you are at least 18 years old.
                    </p>

                    <h3 className="legal-h3">Legal Capacity</h3>
                    <p className="legal-p">
                        You must have the legal capacity to enter into binding contracts under applicable
                        law in your jurisdiction.
                    </p>

                    <h3 className="legal-h3">Business Use</h3>
                    <p className="legal-p">
                        The Trace.Mem platform is designed for Developers and businesses building
                        AI-powered applications. It is not intended for personal, household, or consumer
                        use.
                    </p>

                    <h3 className="legal-h3">Jurisdiction</h3>
                    <p className="legal-p">
                        You represent that your use of the Services complies with all laws applicable to
                        you in your jurisdiction. If access to or use of the Services is prohibited by
                        applicable law in your jurisdiction, you may not use them.
                    </p>
                </LegalSection>

                {/* ── 03 Account Registration ───────────────────────── */}
                <LegalSection id="account" num="03" title="Account Registration and Responsibilities">
                    <h3 className="legal-h3">Account Creation</h3>
                    <p className="legal-p">
                        To access the Services, you must create a Trace.Mem developer account by
                        providing accurate, complete, and current information, including a valid email
                        address.
                    </p>

                    <h3 className="legal-h3">Email Verification</h3>
                    <p className="legal-p">
                        You must verify your email address to activate your account. Accounts that are
                        not verified within a reasonable period may be automatically removed.
                    </p>

                    <h3 className="legal-h3">Account Security</h3>
                    <p className="legal-p">You are solely responsible for:</p>
                    <ul className="legal-ul">
                        <li>
                            Maintaining the confidentiality of your account credentials (email, password,
                            and API keys)
                        </li>
                        <li>All activity that occurs under your account</li>
                        <li>
                            Promptly notifying us at{' '}
                            <a href="mailto:trace.mem.official@gmail.com" style={{ color: 'var(--tm-primary)', textDecoration: 'underline', textUnderlineOffset: '3px' }}>
                                trace.mem.official@gmail.com
                            </a>{' '}
                            if you suspect unauthorised access to your account
                        </li>
                    </ul>

                    <h3 className="legal-h3">Accurate Information</h3>
                    <p className="legal-p">
                        You agree to keep your account information accurate and up to date. We are not
                        responsible for issues arising from inaccurate account information.
                    </p>

                    <h3 className="legal-h3">One Account Per Entity</h3>
                    <p className="legal-p">
                        Unless expressly permitted by us in writing, each individual or legal entity may
                        maintain only one active Trace.Mem account.
                    </p>

                    <h3 className="legal-h3">Account Transfer</h3>
                    <p className="legal-p">
                        You may not transfer or assign your account to another person or entity without
                        our prior written consent.
                    </p>
                </LegalSection>

                {/* ── 04 API Keys ───────────────────────────────────── */}
                <LegalSection id="api-keys" num="04" title="API Keys and Authentication">
                    <h3 className="legal-h3">API Key Issuance</h3>
                    <p className="legal-p">
                        Upon account creation and email verification, Trace.Mem will issue API keys that
                        enable access to our Services. API keys are issued in two categories:
                    </p>
                    <p className="legal-p"><strong>Sandbox Keys:</strong></p>
                    <ul className="legal-ul">
                        <li>Intended solely for local development, testing, and evaluation purposes</li>
                        <li>Must not be used in production environments or deployed applications</li>
                        <li>Subject to lower rate limits and restricted functionality</li>
                        <li>Do not process real end-user data in production scenarios</li>
                    </ul>
                    <p className="legal-p"><strong>Production / Live Keys:</strong></p>
                    <ul className="legal-ul">
                        <li>Intended for use in live, production-facing applications</li>
                        <li>Subject to rate limits and quotas according to your subscription plan</li>
                        <li>
                            Must be kept confidential and never exposed in client-side code, public
                            repositories, or other accessible locations
                        </li>
                    </ul>

                    <h3 className="legal-h3">API Key Confidentiality</h3>
                    <p className="legal-p">
                        You are solely responsible for keeping your API keys secure. You must not:
                    </p>
                    <ul className="legal-ul">
                        <li>Share your API keys with unauthorised third parties</li>
                        <li>
                            Embed production API keys in publicly accessible code, repositories, mobile
                            applications, or front-end code
                        </li>
                        <li>Store API keys in plain text in any publicly accessible location</li>
                        <li>Use another Developer's API keys without their express authorisation</li>
                    </ul>

                    <h3 className="legal-h3">Compromised Keys</h3>
                    <p className="legal-p">
                        If you believe your API key has been compromised, leaked, or used without
                        authorisation, you must immediately rotate or revoke the affected key via your
                        developer dashboard and notify us. We are not liable for any damages resulting
                        from your failure to keep your API keys secure.
                    </p>

                    <h3 className="legal-h3">Sandbox Key Restrictions</h3>
                    <p className="legal-p">
                        Sandbox keys must only be used for development and testing. Using sandbox keys
                        in production environments, or attempting to circumvent sandbox restrictions, is
                        a material breach of these Terms and may result in immediate account suspension
                        or termination.
                    </p>

                    <h3 className="legal-h3">Key Rotation</h3>
                    <p className="legal-p">
                        We recommend rotating your API keys periodically as a security best practice.
                        Key rotation tools are available in your developer dashboard.
                    </p>
                </LegalSection>

                {/* ── 05 Developer Responsibilities ─────────────────── */}
                <LegalSection id="developer-responsibilities" num="05" title="Developer Responsibilities">
                    <h3 className="legal-h3">Your Applications</h3>
                    <p className="legal-p">
                        You own and are solely responsible for the applications you build using Trace.Mem
                        APIs ("Your Applications"). Trace.Mem provides memory infrastructure only. We
                        have no control over and accept no responsibility for:
                    </p>
                    <ul className="legal-ul">
                        <li>The design, functionality, or content of Your Applications</li>
                        <li>How Your Applications collect, process, or use data</li>
                        <li>The end users of Your Applications</li>
                    </ul>

                    <h3 className="legal-h3">End User Data</h3>
                    <p className="legal-p">
                        Where Your Applications transmit data about your end users to Trace.Mem, you are
                        responsible for:
                    </p>
                    <ul className="legal-ul">
                        <li>
                            Obtaining all necessary consents from your end users prior to transmitting
                            their data to Trace.Mem
                        </li>
                        <li>
                            Providing your end users with clear notice of how their data will be
                            processed by Trace.Mem
                        </li>
                        <li>
                            Ensuring your use of Trace.Mem APIs complies with your own privacy policy
                            and applicable privacy law
                        </li>
                        <li>Handling data deletion and correction requests from your end users</li>
                    </ul>

                    <h3 className="legal-h3">Data Content Responsibility</h3>
                    <p className="legal-p">
                        You are solely responsible for all data you transmit to Trace.Mem through our
                        APIs. You must not transmit data that:
                    </p>
                    <ul className="legal-ul">
                        <li>Violates any applicable law or regulation</li>
                        <li>Infringes any third-party intellectual property rights</li>
                        <li>Contains illegal, abusive, defamatory, or harmful content</li>
                        <li>Constitutes prohibited sensitive personal information (see Section 06)</li>
                    </ul>

                    <h3 className="legal-h3">Compliance with Laws</h3>
                    <p className="legal-p">
                        You are responsible for ensuring that your use of Trace.Mem and Your Applications
                        comply with all applicable laws in your jurisdiction and the jurisdictions of
                        your end users, including data protection, consumer protection, and AI-related
                        laws.
                    </p>
                </LegalSection>

                {/* ── 06 Acceptable Use ─────────────────────────────── */}
                <LegalSection id="acceptable-use" num="06" title="Acceptable Use">
                    <h3 className="legal-h3">Permitted Use</h3>
                    <p className="legal-p">
                        You may use the Services solely for the purpose of building, testing, and
                        operating AI-powered applications that require long-term memory infrastructure,
                        in accordance with these Terms.
                    </p>

                    <h3 className="legal-h3">Prohibited Activities</h3>
                    <p className="legal-p">
                        You must not use the Services to conduct any of the following:
                    </p>

                    <p className="legal-p"><strong>Security and Infrastructure Abuse:</strong></p>
                    <ul className="legal-ul">
                        <li>Conduct or facilitate denial-of-service (DoS) or distributed denial-of-service (DDoS) attacks</li>
                        <li>Deploy bots, scrapers, or automated tools to access the platform in ways not permitted by these Terms or our documentation</li>
                        <li>Probe, scan, or test the vulnerability of our systems without our prior written consent</li>
                        <li>Circumvent or attempt to circumvent any rate limits, access controls, or security measures</li>
                        <li>Introduce malware, viruses, trojans, ransomware, or other malicious code into our systems</li>
                    </ul>

                    <p className="legal-p"><strong>API and Memory Abuse:</strong></p>
                    <ul className="legal-ul">
                        <li>Submit malicious, fabricated, or injected content designed to manipulate AI behaviour through memory (prompt injection via memory)</li>
                        <li>Inject false, misleading, or corrupted memory data to interfere with another user's application or AI system</li>
                        <li>Use the API to artificially inflate usage metrics or circumvent billing</li>
                        <li>Abuse sandbox keys for production traffic or to avoid paid subscription limits</li>
                    </ul>

                    <p className="legal-p"><strong>Unlawful and Harmful Content:</strong></p>
                    <ul className="legal-ul">
                        <li>Store, process, or transmit content that is illegal under applicable law</li>
                        <li>Store or process content that infringes copyrights, trademarks, or other intellectual property rights</li>
                        <li>Transmit phishing content, fraudulent communications, or impersonation content</li>
                        <li>Transmit or store content that constitutes harassment, hate speech, or threats of violence</li>
                        <li>Distribute spam or unsolicited communications through or using the Services</li>
                    </ul>

                    <p className="legal-p"><strong>Privacy Violations:</strong></p>
                    <ul className="legal-ul">
                        <li>Store unlawfully obtained personal data</li>
                        <li>Process personal data without a lawful basis</li>
                        <li>Use Trace.Mem to build surveillance tools, stalkerware, or systems designed to monitor individuals without their knowledge and consent</li>
                    </ul>

                    <p className="legal-p"><strong>Platform Integrity:</strong></p>
                    <ul className="legal-ul">
                        <li>Reverse engineer, decompile, disassemble, or attempt to extract the source code of Trace.Mem's platform, APIs, or AI models</li>
                        <li>Resell, sublicense, or otherwise commercialise API access without our express written permission</li>
                        <li>Impersonate Trace.Mem or any of our employees, representatives, or other Developers</li>
                        <li>Use the Services in any way that could damage, disrupt, or impair the platform or other Developers' use of the Services</li>
                    </ul>

                    <h3 className="legal-h3">Prohibited Data — Sensitive Personal Information</h3>
                    <p className="legal-p">
                        The Trace.Mem platform is not designed or intended for the storage or processing
                        of sensitive personal information. You must not use Trace.Mem APIs to transmit
                        or store:
                    </p>
                    <ul className="legal-ul">
                        <li>Government-issued identification numbers (Aadhaar, passport numbers, PAN, Social Security Numbers, etc.)</li>
                        <li>Full financial account details (bank account numbers, full card numbers)</li>
                        <li>Health or medical records</li>
                        <li>Biometric data used for identification purposes</li>
                        <li>Data concerning racial or ethnic origin, religious beliefs, political opinions, or sexual orientation</li>
                        <li>Passwords or authentication credentials of any kind</li>
                    </ul>
                    <p className="legal-p">
                        We reserve the right to refuse, remove, or purge any data we determine to fall
                        within these prohibited categories.
                    </p>

                    <h3 className="legal-h3">Rate Limits</h3>
                    <p className="legal-p">
                        Your use of the APIs is subject to rate limits as specified in your subscription
                        plan and our documentation. You must not attempt to circumvent rate limits
                        through technical means, use multiple accounts to aggregate API access beyond
                        permitted limits, or distribute API requests across proxy services to evade rate
                        limiting. We reserve the right to throttle, suspend, or terminate access for
                        accounts that repeatedly violate rate limits.
                    </p>
                </LegalSection>

                {/* ── 07 Service Availability ───────────────────────── */}
                <LegalSection id="availability" num="07" title="Service Availability">
                    <h3 className="legal-h3">Uptime and Availability</h3>
                    <p className="legal-p">
                        We strive to maintain high availability of the Services. However, we do not
                        guarantee uninterrupted, error-free, or continuously available access.
                    </p>

                    <h3 className="legal-h3">Scheduled Maintenance</h3>
                    <p className="legal-p">
                        We may take the Services offline temporarily for scheduled maintenance. Where
                        possible, we will provide advance notice via the Trace.Mem status page and
                        email notifications to registered Developers.
                    </p>

                    <h3 className="legal-h3">Unscheduled Downtime</h3>
                    <p className="legal-p">
                        Infrastructure failures, third-party provider outages, or unforeseen
                        circumstances may cause unscheduled downtime. We will endeavour to restore
                        Services as quickly as possible and communicate status updates through our
                        status page.
                    </p>

                    <h3 className="legal-h3">No SLA (Default)</h3>
                    <p className="legal-p">
                        Unless you have entered into a separate Service Level Agreement with us, we do
                        not provide guaranteed uptime commitments or SLA credits.
                    </p>

                    <h3 className="legal-h3">Status Page</h3>
                    <p className="legal-p">
                        You can monitor the real-time status of Trace.Mem services at our status page,
                        linked in the footer of this website.
                    </p>
                </LegalSection>

                {/* ── 08 Billing ────────────────────────────────────── */}
                <LegalSection id="billing" num="08" title="Subscription Plans and Billing">
                    <h3 className="legal-h3">Subscription Plans</h3>
                    <p className="legal-p">
                        Trace.Mem offers multiple subscription plans with different API usage limits,
                        features, and pricing. Current plan details are available on our pricing page.
                        Plans may include a free/sandbox tier (limited to development and testing),
                        paid tiers with defined monthly API usage quotas, and enterprise or custom
                        arrangements.
                    </p>

                    <h3 className="legal-h3">Billing Cycle</h3>
                    <p className="legal-p">
                        Paid subscriptions are billed on a recurring basis (monthly or annually,
                        depending on the plan selected). Billing is processed on the date your
                        subscription begins or renews.
                    </p>

                    <h3 className="legal-h3">Stripe Payments</h3>
                    <p className="legal-p">
                        All payment processing is handled by Stripe, Inc. By subscribing to a paid
                        plan, you agree to{' '}
                        <a href="https://stripe.com/legal" target="_blank" rel="noreferrer" style={{ color: 'var(--tm-primary)', textDecoration: 'underline', textUnderlineOffset: '3px' }}>
                            Stripe's Terms of Service
                        </a>{' '}
                        and authorise Stripe to charge your designated payment method on our behalf.
                        We do not store your full payment card details. All payment data is handled
                        directly by Stripe in accordance with PCI-DSS standards.
                    </p>

                    <h3 className="legal-h3">Failed Payments</h3>
                    <p className="legal-p">If a payment fails:</p>
                    <ul className="legal-ul">
                        <li>We will retry the charge in accordance with Stripe's retry logic</li>
                        <li>We will notify you by email</li>
                        <li>If payment remains outstanding, your account may be downgraded or suspended</li>
                        <li>We reserve the right to terminate your account for prolonged non-payment</li>
                    </ul>

                    <h3 className="legal-h3">Subscription Cancellation</h3>
                    <p className="legal-p">
                        You may cancel your subscription at any time through your developer dashboard.
                        Upon cancellation, your paid access will continue until the end of the current
                        billing period. You will not be charged for the next billing cycle, and your
                        account will be downgraded to the free/sandbox tier (if available) or
                        deactivated at the end of the billing period.
                    </p>

                    <h3 className="legal-h3">Price Changes</h3>
                    <p className="legal-p">
                        We reserve the right to modify our pricing with advance notice provided via
                        email or dashboard notification. Continued use of the Services after a price
                        change takes effect constitutes your acceptance of the new pricing.
                    </p>

                    <h3 className="legal-h3">Taxes</h3>
                    <p className="legal-p">
                        All prices are exclusive of applicable taxes. You are responsible for paying
                        any taxes, levies, or duties imposed by your applicable tax authority in
                        connection with your use of the Services. We will collect GST or other
                        applicable taxes where required by law.
                    </p>

                    <h3 className="legal-h3">Unpaid Subscriptions</h3>
                    <p className="legal-p">
                        Accounts with outstanding unpaid balances may be subject to restriction or
                        suspension of API access, permanent account termination for prolonged
                        non-payment, and recovery of outstanding amounts through lawful means.
                    </p>
                </LegalSection>

                {/* ── 09 Suspension & Termination ───────────────────── */}
                <LegalSection id="termination" num="09" title="Suspension and Termination">
                    <h3 className="legal-h3">Termination by You</h3>
                    <p className="legal-p">
                        You may close your account at any time through the account management options
                        in your dashboard settings or by contacting us. Upon termination, you must
                        cease all use of the Services, your API keys will be deactivated, and your
                        account data will be handled per our Privacy Policy.
                    </p>

                    <h3 className="legal-h3">Temporary Suspension by Trace.Mem</h3>
                    <p className="legal-p">
                        We may temporarily suspend your access to the Services, without prior notice
                        where circumstances require urgency, if:
                    </p>
                    <ul className="legal-ul">
                        <li>We suspect your account is being used in violation of these Terms</li>
                        <li>Your account is associated with security threats, data breaches, or malicious activity</li>
                        <li>A payment is overdue</li>
                        <li>We are required to do so by law or regulatory order</li>
                        <li>Your API usage is causing disruption to other users of the platform</li>
                    </ul>
                    <p className="legal-p">
                        We will notify you of the suspension and the reason as soon as reasonably
                        practicable. Where the issue is remedied to our satisfaction, we may restore
                        access.
                    </p>

                    <h3 className="legal-h3">Permanent Termination by Trace.Mem</h3>
                    <p className="legal-p">
                        We may permanently terminate your account if:
                    </p>
                    <ul className="legal-ul">
                        <li>You commit a material or repeated breach of these Terms</li>
                        <li>Temporary suspension fails to resolve the issue</li>
                        <li>You engage in serious prohibited activities (including malicious memory injection, fraud, illegal activity, or reverse engineering)</li>
                        <li>Your account remains unpaid for an extended period</li>
                        <li>We determine that continued access poses an unacceptable risk to the platform or other users</li>
                    </ul>

                    <h3 className="legal-h3">Effect of Termination</h3>
                    <p className="legal-p">Upon termination of your account:</p>
                    <ul className="legal-ul">
                        <li>All API keys associated with your account will be permanently revoked</li>
                        <li>Your access to the developer dashboard will cease</li>
                        <li>Memory data associated with your account will be deleted in accordance with our data retention policy</li>
                        <li>Any outstanding subscription fees remain payable</li>
                    </ul>

                    <h3 className="legal-h3">Survival</h3>
                    <p className="legal-p">
                        Sections relating to intellectual property, indemnification, limitation of
                        liability, governing law, and any other provisions that by their nature should
                        survive termination will continue in force after termination.
                    </p>
                </LegalSection>

                {/* ── 10 Intellectual Property ──────────────────────── */}
                <LegalSection id="ip" num="10" title="Intellectual Property">
                    <h3 className="legal-h3">Trace.Mem Platform</h3>
                    <p className="legal-p">
                        All rights, title, and interest in and to the Trace.Mem platform, including its
                        APIs, algorithms, AI models, memory processing technology, documentation,
                        software, user interface, trademarks, and service marks, are owned by or
                        licensed to Trace.Mem. Nothing in these Terms transfers any ownership of our
                        intellectual property to you.
                    </p>

                    <h3 className="legal-h3">Licence to Use</h3>
                    <p className="legal-p">
                        Subject to your compliance with these Terms, we grant you a limited,
                        non-exclusive, non-transferable, non-sublicensable, revocable licence to access
                        and use the Services solely for the purpose of building and operating Your
                        Applications during the term of your account.
                    </p>

                    <h3 className="legal-h3">Your Applications</h3>
                    <p className="legal-p">
                        You retain all rights, title, and interest in and to Your Applications. You
                        grant us a limited licence to access, process, and transmit data through Your
                        Applications solely to the extent necessary to provide the Services.
                    </p>

                    <h3 className="legal-h3">Memory Content and User Content</h3>
                    <p className="legal-p">As between you and Trace.Mem:</p>
                    <ul className="legal-ul">
                        <li>You (or your end users, as applicable) own the memory content that you submit to Trace.Mem through the APIs</li>
                        <li>You grant us a limited, non-exclusive licence to process, store, and retrieve that content solely to provide the Services to you</li>
                        <li>We do not claim ownership of memory content or use it for our own purposes beyond service delivery</li>
                    </ul>

                    <h3 className="legal-h3">AI-Processed Output Disclaimer</h3>
                    <p className="legal-p">
                        Memory content processed by our AI and semantic processing systems may be
                        transformed, normalised, deduplicated, scored, or structured in ways that
                        differ from the original input. Such processing is inherent to our service.
                        We do not represent that AI-processed outputs are accurate, complete, or
                        suitable for any particular purpose.
                    </p>

                    <h3 className="legal-h3">Feedback</h3>
                    <p className="legal-p">
                        If you provide us with feedback, suggestions, or ideas regarding the Services,
                        you grant us an irrevocable, royalty-free, perpetual licence to use such
                        feedback for any purpose without obligation to you.
                    </p>

                    <h3 className="legal-h3">Restrictions</h3>
                    <p className="legal-p">You must not:</p>
                    <ul className="legal-ul">
                        <li>Reverse engineer, decompile, disassemble, or attempt to derive the source code or underlying algorithms of the Services</li>
                        <li>Use our trademarks, service marks, or branding without our prior written consent</li>
                        <li>Remove or obscure any proprietary notices from our software or documentation</li>
                        <li>Create derivative works based on our platform or technology</li>
                    </ul>
                </LegalSection>

                {/* ── 11 AI Processing ──────────────────────────────── */}
                <LegalSection id="ai-processing" num="11" title="AI Processing and Generated Output">
                    <h3 className="legal-h3">Nature of AI Processing</h3>
                    <p className="legal-p">
                        Trace.Mem uses artificial intelligence, machine learning, and semantic
                        processing to store, structure, normalise, and retrieve memories. These
                        processes are probabilistic in nature and may not always produce accurate or
                        complete results.
                    </p>

                    <h3 className="legal-h3">Output Disclaimer</h3>
                    <p className="legal-p">
                        Memory outputs, retrieved contexts, confidence scores, and other AI-generated
                        results provided by Trace.Mem:
                    </p>
                    <ul className="legal-ul">
                        <li>May contain inaccuracies, omissions, or errors</li>
                        <li>Should not be relied upon as definitive facts without independent verification</li>
                        <li>Are provided as part of memory infrastructure, not as professional advice of any kind</li>
                        <li>May vary between requests due to the probabilistic nature of AI processing</li>
                    </ul>

                    <h3 className="legal-h3">Developer Responsibility for AI Outputs</h3>
                    <p className="legal-p">
                        You are solely responsible for how Your Applications use and present memory data
                        and AI-generated outputs to your end users. You must not represent AI-generated
                        memory outputs as guaranteed facts or authoritative information.
                    </p>

                    <h3 className="legal-h3">No Training on Your Data</h3>
                    <p className="legal-p">
                        We do not use your memory content to train our general AI models without your
                        explicit consent.
                    </p>

                    <h3 className="legal-h3">Third-Party AI Providers</h3>
                    <p className="legal-p">
                        Some processing may be performed by third-party AI providers under appropriate
                        agreements. We take reasonable steps to ensure such providers do not use your
                        data for their own model training.
                    </p>
                </LegalSection>

                {/* ── 12 Disclaimer of Warranties ───────────────────── */}
                <LegalSection id="warranties" num="12" title="Disclaimer of Warranties">
                    <div className="legal-callout">
                        <p>
                            To the maximum extent permitted by applicable law, the Services are provided
                            "as is" and "as available," without warranty of any kind, express or implied.
                        </p>
                    </div>
                    <p className="legal-p">We specifically disclaim:</p>
                    <ul className="legal-ul">
                        <li>Any warranty of merchantability, fitness for a particular purpose, or non-infringement</li>
                        <li>Any warranty that the Services will be uninterrupted, error-free, secure, or free of viruses or other harmful components</li>
                        <li>Any warranty regarding the accuracy, completeness, or reliability of AI-generated outputs, memory retrieval results, or semantic processing outcomes</li>
                        <li>Any warranty that the Services will meet your requirements or expectations</li>
                    </ul>
                    <p className="legal-p">
                        You assume full responsibility for your use of the Services and your
                        applications built on Trace.Mem.
                    </p>
                </LegalSection>

                {/* ── 13 Limitation of Liability ────────────────────── */}
                <LegalSection id="liability" num="13" title="Limitation of Liability">
                    <h3 className="legal-h3">Cap on Liability</h3>
                    <p className="legal-p">
                        To the maximum extent permitted by applicable law, Trace.Mem's total cumulative
                        liability to you for all claims arising out of or related to these Terms or the
                        Services shall not exceed the greater of: (A) the total fees paid by you to
                        Trace.Mem in the three (3) months immediately preceding the claim, or (B) INR
                        5,000.
                    </p>

                    <h3 className="legal-h3">Exclusion of Certain Damages</h3>
                    <p className="legal-p">
                        To the maximum extent permitted by applicable law, we shall not be liable for:
                    </p>
                    <ul className="legal-ul">
                        <li>Loss of profits, revenue, or business</li>
                        <li>Loss of data or corruption of data</li>
                        <li>Loss of goodwill or reputation</li>
                        <li>Indirect, incidental, special, consequential, or punitive damages</li>
                        <li>Costs of procuring substitute services</li>
                    </ul>
                    <p className="legal-p">
                        This exclusion applies even if we have been advised of the possibility of such
                        damages and even if the remedy fails of its essential purpose.
                    </p>

                    <h3 className="legal-h3">Essential Basis</h3>
                    <p className="legal-p">
                        The limitations of liability in this Section reflect a reasonable allocation of
                        risk and form an essential basis of the bargain between you and Trace.Mem.
                        Trace.Mem would not provide the Services without these limitations.
                    </p>

                    <h3 className="legal-h3">Exceptions</h3>
                    <p className="legal-p">Nothing in these Terms limits liability for:</p>
                    <ul className="legal-ul">
                        <li>Fraud or wilful misconduct by Trace.Mem</li>
                        <li>Death or personal injury caused by our negligence</li>
                        <li>Any liability that cannot be excluded or limited under applicable law</li>
                    </ul>
                </LegalSection>

                {/* ── 14 Indemnification ────────────────────────────── */}
                <LegalSection id="indemnification" num="14" title="Indemnification">
                    <p className="legal-p">
                        You agree to indemnify, defend, and hold harmless Trace.Mem, its affiliates,
                        officers, directors, employees, and agents from and against any and all claims,
                        damages, losses, liabilities, costs, and expenses (including reasonable legal
                        fees) arising out of or related to:
                    </p>
                    <ul className="legal-ul">
                        <li>Your use of the Services in violation of these Terms</li>
                        <li>Your Applications, including any claims by your end users</li>
                        <li>Data you transmit to Trace.Mem, including any claim that such data violates any applicable law or third-party rights</li>
                        <li>Your breach of any representation, warranty, or obligation under these Terms</li>
                        <li>Any negligence or wilful misconduct by you or your employees or agents</li>
                        <li>Any failure by you to obtain necessary consents from end users</li>
                    </ul>
                    <p className="legal-p">
                        We reserve the right, at our own expense, to assume the exclusive defence and
                        control of any matter subject to indemnification by you, in which case you
                        agree to cooperate with our defence of such matter.
                    </p>
                </LegalSection>

                {/* ── 15 Force Majeure ──────────────────────────────── */}
                <LegalSection id="force-majeure" num="15" title="Force Majeure">
                    <p className="legal-p">
                        We shall not be liable for any failure or delay in performing our obligations
                        under these Terms to the extent such failure or delay is caused by circumstances
                        beyond our reasonable control, including but not limited to:
                    </p>
                    <ul className="legal-ul">
                        <li>Acts of God, natural disasters, floods, fires, or earthquakes</li>
                        <li>War, armed conflict, terrorism, or civil unrest</li>
                        <li>Government actions, embargoes, or regulatory restrictions</li>
                        <li>Failures of third-party infrastructure, telecommunications, or internet service providers</li>
                        <li>Pandemic, epidemic, or public health emergency</li>
                        <li>Power outages or failures of third-party cloud or AI service providers</li>
                        <li>Cyberattacks or security incidents beyond our reasonable control</li>
                    </ul>
                    <p className="legal-p">
                        In the event of a force majeure event, we will notify you as soon as practicable
                        and take reasonable steps to mitigate the impact. If the force majeure event
                        persists for more than 30 days, either party may terminate affected Services by
                        providing written notice.
                    </p>
                </LegalSection>

                {/* ── 16 Governing Law ──────────────────────────────── */}
                <LegalSection id="governing-law" num="16" title="Governing Law">
                    <p className="legal-p">
                        These Terms shall be governed by and construed in accordance with the laws of
                        India, without regard to its conflict of law provisions.
                    </p>
                    <p className="legal-p">
                        The courts located in India shall have exclusive jurisdiction over any disputes
                        arising out of or relating to these Terms or the Services, subject to Section 17
                        (Dispute Resolution).
                    </p>
                </LegalSection>

                {/* ── 17 Dispute Resolution ─────────────────────────── */}
                <LegalSection id="dispute-resolution" num="17" title="Dispute Resolution">
                    <h3 className="legal-h3">Informal Resolution</h3>
                    <p className="legal-p">
                        Before initiating formal proceedings, you agree to first attempt to resolve any
                        dispute informally by contacting us at{' '}
                        <a href="mailto:trace.mem.official@gmail.com" style={{ color: 'var(--tm-primary)', textDecoration: 'underline', textUnderlineOffset: '3px' }}>
                            trace.mem.official@gmail.com
                        </a>{' '}
                        and providing written notice of the dispute. We will endeavour to resolve the
                        dispute within 30 days of receiving your notice.
                    </p>

                    <h3 className="legal-h3">Jurisdiction</h3>
                    <p className="legal-p">
                        Disputes that cannot be resolved informally shall be subject to the exclusive
                        jurisdiction of the courts specified in Section 16.
                    </p>
                </LegalSection>

                {/* ── 18 Changes to These Terms ─────────────────────── */}
                <LegalSection id="changes" num="18" title="Changes to These Terms">
                    <p className="legal-p">
                        We reserve the right to modify these Terms at any time. When we make material
                        changes, we will:
                    </p>
                    <ul className="legal-ul">
                        <li>Update the "Last Updated" date at the top of these Terms</li>
                        <li>Notify registered Developers by email before the changes take effect</li>
                        <li>Post the updated Terms on our website</li>
                    </ul>
                    <p className="legal-p">
                        Your continued use of the Services after the effective date of any changes
                        constitutes your acceptance of the updated Terms. If you do not agree to the
                        updated Terms, you must stop using the Services and may close your account
                        before the effective date.
                    </p>
                </LegalSection>

                {/* ── 19 General Provisions ─────────────────────────── */}
                <LegalSection id="general" num="19" title="General Provisions">
                    <h3 className="legal-h3">Entire Agreement</h3>
                    <p className="legal-p">
                        These Terms, together with our Privacy Policy and any other policies referenced
                        herein, constitute the entire agreement between you and Trace.Mem regarding the
                        Services and supersede all prior agreements, representations, and understandings.
                    </p>

                    <h3 className="legal-h3">Severability</h3>
                    <p className="legal-p">
                        If any provision of these Terms is found to be unenforceable or invalid under
                        applicable law, that provision shall be modified to the minimum extent necessary
                        to make it enforceable, or severed if modification is not possible, without
                        affecting the validity and enforceability of the remaining provisions.
                    </p>

                    <h3 className="legal-h3">Waiver</h3>
                    <p className="legal-p">
                        Our failure to enforce any right or provision of these Terms shall not constitute
                        a waiver of that right or provision. A waiver is only effective if given in
                        writing and signed by an authorised representative of Trace.Mem.
                    </p>

                    <h3 className="legal-h3">Assignment</h3>
                    <p className="legal-p">
                        You may not assign or transfer your rights or obligations under these Terms
                        without our prior written consent. We may assign our rights and obligations
                        under these Terms at any time, including in connection with a merger, acquisition,
                        or sale of assets, with notice to you.
                    </p>

                    <h3 className="legal-h3">No Partnership</h3>
                    <p className="legal-p">
                        Nothing in these Terms creates a partnership, joint venture, agency, franchise,
                        or employment relationship between you and Trace.Mem. You have no authority to
                        bind Trace.Mem in any way.
                    </p>

                    <h3 className="legal-h3">Language</h3>
                    <p className="legal-p">
                        These Terms are drafted in English. In the event of any conflict between an
                        English version and any translated version, the English version shall prevail.
                    </p>
                </LegalSection>

                {/* ── 20 Contact Information ────────────────────────── */}
                <LegalSection id="contact" num="20" title="Contact Information">
                    <p className="legal-p">
                        For general enquiries about these Terms or the Services, legal notices, security
                        concerns, and privacy or data protection enquiries, please contact us. We will
                        direct your message to the appropriate team and aim to respond promptly.
                    </p>

                    <div className="legal-contact-strip">
                        <span className="legal-contact-label">General & Legal Enquiries</span>
                        <h3 className="legal-contact-title">Reach our team directly</h3>
                        <p className="legal-contact-desc">
                            For Terms of Use questions, security disclosures, abuse reports, and
                            grievance submissions. We aim to respond within 3 business days.
                        </p>
                        <a
                            href="mailto:trace.mem.official@gmail.com"
                            className="legal-contact-email"
                            aria-label="Email Trace.Mem"
                        >
                            trace.mem.official@gmail.com
                        </a>
                    </div>
                </LegalSection>
            </LegalPageShell>
        </>
    );
}
