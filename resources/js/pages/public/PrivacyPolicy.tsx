import { Head } from '@inertiajs/react';
import { Helmet } from 'react-helmet-async';
import {
    LegalPageShell,
    LegalHero,
    LegalSection,
    type TocEntry,
} from '@/components/public/legal-page-shell';
import '../../../css/pages/legal.css';

const tocEntries: TocEntry[] = [
    { id: 'introduction',        num: '01', label: 'Introduction' },
    { id: 'scope',               num: '02', label: 'Scope of This Policy' },
    { id: 'responsible',         num: '03', label: 'Who Is Responsible' },
    { id: 'information-collected', num: '04', label: 'Information We Collect' },
    { id: 'why-we-process',      num: '05', label: 'Why We Process Your Data' },
    { id: 'legal-basis',         num: '06', label: 'Legal Basis for Processing' },
    { id: 'cookies',             num: '07', label: 'Cookies' },
    { id: 'data-retention',      num: '08', label: 'Data Retention' },
    { id: 'data-deletion',       num: '09', label: 'Data Deletion' },
    { id: 'your-rights',         num: '10', label: 'Your Rights' },
    { id: 'security',            num: '11', label: 'Data Security' },
    { id: 'international',       num: '12', label: 'International Transfers' },
    { id: 'childrens-privacy',   num: '13', label: "Children's Privacy" },
    { id: 'third-party',         num: '14', label: 'Third-Party Services' },
    { id: 'grievance-officer',   num: '15', label: 'Grievance Officer' },
    { id: 'policy-changes',      num: '16', label: 'Changes to This Policy' },
    { id: 'contact',             num: '17', label: 'Contact Us' },
];

export default function PrivacyPolicy() {
    return (
        <>
            <Head title="Privacy Policy" />
            <Helmet>
                <meta
                    name="description"
                    content="Learn how Trace.Mem collects, stores and protects your data."
                />
                <meta name="robots" content="index, follow" />
                <link rel="canonical" href="https://tracemem.one/privacy-policy" />
                <meta property="og:title" content="Privacy Policy" />
                <meta property="og:description" content="Learn how Trace.Mem collects, stores and protects your data." />
                <meta property="og:url" content="https://tracemem.one/privacy-policy" />
            </Helmet>

            <LegalPageShell
                entries={tocEntries}
                hero={
                    <LegalHero
                        eyebrow="Legal · Privacy"
                        title="Privacy Policy"
                        subtitle="How we collect, use, store, share, and protect personal data in connection with the Trace.Mem platform."
                        lastUpdated="June 2026"
                        readingTime="~15 min read"
                    />
                }
            >
                {/* ── 01 Introduction ───────────────────────────────── */}
                <LegalSection id="introduction" num="01" title="Introduction">
                    <p className="legal-p">
                        Trace.Mem ("we," "us," or "our") operates a Memory-as-a-Service (MaaS) platform
                        that provides AI memory infrastructure to developers and businesses ("Developers")
                        who build AI-powered applications.
                    </p>
                    <p className="legal-p">
                        This Privacy Policy explains how we collect, use, store, share, and protect
                        personal data in connection with:
                    </p>
                    <ul className="legal-ul">
                        <li>Our website and developer dashboard at tracemem.one</li>
                        <li>Our REST APIs and related services</li>
                        <li>Our billing, subscription, and account management systems</li>
                    </ul>
                    <p className="legal-p">
                        We are committed to handling personal data responsibly, transparently, and in
                        a manner consistent with applicable privacy laws, including India's Digital
                        Personal Data Protection Act, 2023 (DPDP Act).
                    </p>
                    <div className="legal-callout">
                        <p>
                            If you are a Developer integrating Trace.Mem into your application, please
                            note that you are independently responsible for how you collect and handle
                            your end users' data before it is sent to our platform.
                        </p>
                    </div>
                    <p className="legal-p">
                        By using Trace.Mem, you agree to the practices described in this Privacy Policy.
                    </p>
                </LegalSection>

                {/* ── 02 Scope ──────────────────────────────────────── */}
                <LegalSection id="scope" num="02" title="Scope of This Policy">
                    <p className="legal-p">This Privacy Policy applies to:</p>
                    <ul className="legal-ul">
                        <li>Developers who register for and use the Trace.Mem platform</li>
                        <li>Visitors to our website and documentation</li>
                        <li>
                            Any individual whose personal data is processed through our APIs as part
                            of a Developer's integration
                        </li>
                    </ul>
                    <h3 className="legal-h3">This Policy Does NOT Govern</h3>
                    <ul className="legal-ul">
                        <li>
                            End users of Developer-built applications, whose data may be processed by
                            Trace.Mem on behalf of the Developer. In such cases, the Developer acts as
                            the primary data controller and is responsible for obtaining appropriate
                            consents from their end users.
                        </li>
                        <li>Third-party websites or services linked from our platform.</li>
                    </ul>
                </LegalSection>

                {/* ── 03 Who Is Responsible ─────────────────────────── */}
                <LegalSection id="responsible" num="03" title="Who Is Responsible for Your Data">
                    <h3 className="legal-h3">Trace.Mem as Data Fiduciary / Controller</h3>
                    <p className="legal-p">
                        For data we collect directly from Developers (account information, billing data,
                        API usage logs, etc.), Trace.Mem acts as the Data Fiduciary under the DPDP Act,
                        2023, and as the Data Controller under general privacy principles.
                    </p>

                    <h3 className="legal-h3">Trace.Mem as Data Processor</h3>
                    <p className="legal-p">
                        For memory content and other data that Developers send to us through our APIs
                        on behalf of their end users, Trace.Mem acts as a Data Processor. In this
                        capacity, we process data only as directed by the Developer, who remains the
                        Data Fiduciary / Controller responsible for that data.
                    </p>

                    <h3 className="legal-h3">Developer Responsibility</h3>
                    <p className="legal-p">
                        Developers must ensure they have a lawful basis to process and transmit data
                        to Trace.Mem. Developers are responsible for complying with applicable privacy
                        laws with respect to their end users.
                    </p>
                </LegalSection>

                {/* ── 04 Information We Collect ─────────────────────── */}
                <LegalSection id="information-collected" num="04" title="Information We Collect">
                    <h3 className="legal-h3">Information You Provide to Us</h3>
                    <p className="legal-p">
                        When you register for and use Trace.Mem, we may collect:
                    </p>
                    <p className="legal-p"><strong>Account Information:</strong></p>
                    <ul className="legal-ul">
                        <li>Full name</li>
                        <li>Email address</li>
                        <li>Password (stored in encrypted/hashed form)</li>
                        <li>Organisation or company name (if provided)</li>
                    </ul>
                    <p className="legal-p"><strong>Billing Information:</strong></p>
                    <ul className="legal-ul">
                        <li>Payment method details (processed and stored securely by Stripe)</li>
                        <li>Billing address</li>
                        <li>Invoice history</li>
                        <li>Subscription plan details</li>
                    </ul>
                    <div className="legal-callout">
                        <p>
                            We do not directly store full payment card details. Payment processing is
                            handled by Stripe, Inc. Please refer to{' '}
                            <a
                                href="https://stripe.com/privacy"
                                target="_blank"
                                rel="noreferrer"
                                style={{ color: 'var(--tm-primary)', textDecoration: 'underline', textUnderlineOffset: '3px' }}
                            >
                                Stripe's Privacy Policy
                            </a>{' '}
                            for details on how they handle payment data.
                        </p>
                    </div>
                    <p className="legal-p"><strong>Support and Communication:</strong></p>
                    <ul className="legal-ul">
                        <li>Any information you voluntarily provide when contacting our support team</li>
                        <li>Feedback, bug reports, or feature requests</li>
                    </ul>

                    <h3 className="legal-h3">Information Automatically Collected</h3>
                    <p className="legal-p">When you use our platform, we automatically collect:</p>
                    <p className="legal-p"><strong>API Usage Data:</strong></p>
                    <ul className="legal-ul">
                        <li>API requests made (timestamps, endpoints called, response codes)</li>
                        <li>API key identifiers used (not the key value itself)</li>
                        <li>Request volumes and usage patterns</li>
                        <li>Error logs associated with API calls</li>
                    </ul>
                    <p className="legal-p"><strong>Log Files and Technical Data:</strong></p>
                    <ul className="legal-ul">
                        <li>IP addresses</li>
                        <li>Browser type and version</li>
                        <li>Operating system</li>
                        <li>Device identifiers</li>
                        <li>Referrer URLs</li>
                        <li>Pages viewed and time spent on the developer dashboard</li>
                    </ul>
                    <p className="legal-p"><strong>Cookies and Tracking Technologies:</strong></p>
                    <ul className="legal-ul">
                        <li>Session cookies required for authentication and dashboard functionality</li>
                        <li>Preference cookies to remember your settings</li>
                        <li>Analytics cookies (see Section 04 below)</li>
                    </ul>
                    <p className="legal-p">We do not use advertising cookies or cross-site tracking technologies.</p>

                    <h3 className="legal-h3">API Data Processing — Memory Content</h3>
                    <p className="legal-p">
                        When Developers send data to Trace.Mem via our APIs, we process that data to
                        deliver our core memory services. This may include:
                    </p>
                    <ul className="legal-ul">
                        <li>User preferences, facts, rules, and other structured memory content</li>
                        <li>Schedules, recurring events, and temporal information</li>
                        <li>Contextual memories and AI interaction histories</li>
                        <li>Any other content submitted by the Developer's application</li>
                    </ul>
                    <p className="legal-p">We apply the following automated processing to this data:</p>
                    <ul className="legal-ul">
                        <li>Semantic segmentation and analysis</li>
                        <li>Memory normalisation and structuring</li>
                        <li>Deduplication and conflict detection</li>
                        <li>Memory scoring, confidence scoring, and decay scoring</li>
                        <li>Context assembly for AI memory retrieval</li>
                        <li>Temporal understanding and code detection</li>
                        <li>Structured memory extraction</li>
                    </ul>
                    <div className="legal-callout">
                        <p>
                            We do not intentionally encourage Developers to send sensitive personal
                            information (such as health data, financial account details, government
                            identification numbers, biometric data, religious beliefs, or similar
                            sensitive categories) through our APIs. Our platform is not designed or
                            intended for the processing of such sensitive information. Developers remain
                            solely responsible for the content they transmit to Trace.Mem.
                        </p>
                    </div>

                    <h3 className="legal-h3">Analytics</h3>
                    <p className="legal-p">
                        We use analytics tools to understand how Developers use our dashboard and
                        platform. These tools may collect aggregated and anonymised usage data to help
                        us improve the product. Analytics data is processed in a manner that does not
                        identify individual end users of Developer applications.
                    </p>

                    <h3 className="legal-h3">AI and Semantic Processing</h3>
                    <p className="legal-p">
                        Our platform uses AI models and semantic processing technology to analyse and
                        structure memory content. This processing may be performed using our own internal
                        models and/or through third-party AI providers.
                    </p>
                    <p className="legal-p">
                        Data submitted to Trace.Mem may be processed by third-party AI service providers
                        as part of our infrastructure. We take reasonable steps to ensure such providers
                        handle data appropriately and do not use it to train their own models without
                        our authorisation.
                    </p>
                </LegalSection>

                {/* ── 05 Why We Process ─────────────────────────────── */}
                <LegalSection id="why-we-process" num="05" title="Why We Process Your Information">
                    <p className="legal-p">
                        We process personal data for the following purposes:
                    </p>

                    <h3 className="legal-h3">To Provide Our Services</h3>
                    <ul className="legal-ul">
                        <li>Creating and managing your Developer account</li>
                        <li>Generating and managing API keys</li>
                        <li>Delivering memory infrastructure services through our APIs</li>
                        <li>Providing sandbox and production environments</li>
                    </ul>

                    <h3 className="legal-h3">To Process Payments and Manage Subscriptions</h3>
                    <ul className="legal-ul">
                        <li>Processing subscription payments via Stripe</li>
                        <li>Managing billing, invoicing, and renewals</li>
                        <li>Handling payment failures and subscription changes</li>
                    </ul>

                    <h3 className="legal-h3">To Ensure Security and Prevent Abuse</h3>
                    <ul className="legal-ul">
                        <li>Detecting and preventing API misuse, abuse, and fraudulent activity</li>
                        <li>Monitoring rate-limit compliance</li>
                        <li>Protecting our infrastructure from automated attacks</li>
                    </ul>

                    <h3 className="legal-h3">To Improve Our Platform</h3>
                    <ul className="legal-ul">
                        <li>Analysing usage patterns to improve our services</li>
                        <li>Debugging errors and improving API reliability</li>
                        <li>Developing new features</li>
                    </ul>

                    <h3 className="legal-h3">To Communicate With You</h3>
                    <ul className="legal-ul">
                        <li>Sending service notifications, security alerts, and updates</li>
                        <li>Responding to support requests</li>
                        <li>Sending product announcements (with the ability to opt out)</li>
                    </ul>

                    <h3 className="legal-h3">To Comply With Legal Obligations</h3>
                    <ul className="legal-ul">
                        <li>Responding to lawful requests from government authorities</li>
                        <li>Maintaining required records</li>
                    </ul>
                </LegalSection>

                {/* ── 06 Legal Basis ────────────────────────────────── */}
                <LegalSection id="legal-basis" num="06" title="Legal Basis for Processing">
                    <p className="legal-p">
                        Under India's DPDP Act, 2023, and general privacy principles, we rely on the
                        following bases for processing personal data:
                    </p>

                    <h3 className="legal-h3">Consent</h3>
                    <p className="legal-p">
                        Where required, we obtain your express consent before processing your personal
                        data. You may withdraw your consent at any time (see Section 10).
                    </p>

                    <h3 className="legal-h3">Contractual Necessity</h3>
                    <p className="legal-p">
                        Processing your account information, API usage data, and billing information is
                        necessary to provide the services you have contracted with us for.
                    </p>

                    <h3 className="legal-h3">Legitimate Interests</h3>
                    <p className="legal-p">
                        We process certain data (such as security logs and aggregated analytics) based on
                        our legitimate interest in keeping the platform secure, improving our services,
                        and preventing abuse — provided such interests are not overridden by your
                        fundamental rights.
                    </p>

                    <h3 className="legal-h3">Legal Obligation</h3>
                    <p className="legal-p">
                        We may process data where required to comply with applicable laws or respond to
                        lawful government requests.
                    </p>
                </LegalSection>

                {/* ── 07 Cookies ────────────────────────────────────── */}
                <LegalSection id="cookies" num="07" title="Cookies">
                    <p className="legal-p">
                        We use cookies and similar technologies on our developer dashboard and website.
                    </p>

                    <h3 className="legal-h3">Essential Cookies</h3>
                    <p className="legal-p">
                        Required for the platform to function. These enable login sessions, API key
                        management, and dashboard navigation. You cannot disable these without affecting
                        platform functionality.
                    </p>

                    <h3 className="legal-h3">Preference Cookies</h3>
                    <p className="legal-p">
                        Remember your settings and preferences within the dashboard.
                    </p>

                    <h3 className="legal-h3">Analytics Cookies</h3>
                    <p className="legal-p">
                        Help us understand how Developers navigate and use our platform. These cookies
                        collect aggregated, anonymised data.
                    </p>

                    <h3 className="legal-h3">Cookie Consent</h3>
                    <p className="legal-p">
                        On your first visit, we will request your consent for non-essential cookies.
                        You may manage your cookie preferences through your browser settings or any
                        cookie management tool we provide.
                    </p>

                    <h3 className="legal-h3">Third-Party Cookies</h3>
                    <p className="legal-p">
                        Third-party analytics providers may set their own cookies. Please refer to the
                        respective privacy policies of those providers for details.
                    </p>
                </LegalSection>

                {/* ── 08 Data Retention ─────────────────────────────── */}
                <LegalSection id="data-retention" num="08" title="Data Retention">
                    <p className="legal-p">
                        We retain personal data only for as long as necessary for the purposes described
                        in this Policy or as required by law.
                    </p>
                    <ul className="legal-ul">
                        <li>
                            <strong>Account Data</strong> — Retained for the duration of your account
                            and for a period following account deletion, after which it is deleted or
                            anonymised.
                        </li>
                        <li>
                            <strong>API Usage Logs</strong> — Retained for a defined period for
                            security, debugging, and billing verification purposes, then deleted or
                            anonymised.
                        </li>
                        <li>
                            <strong>Billing Records</strong> — Retained as required for tax and
                            financial compliance under applicable law.
                        </li>
                        <li>
                            <strong>Memory Content (API Data)</strong> — Retained until deleted by
                            the Developer via API or dashboard, or until the Developer's account is
                            terminated.
                        </li>
                        <li>
                            <strong>Support Communications</strong> — Retained for a reasonable period
                            from the date of the last interaction.
                        </li>
                    </ul>
                    <p className="legal-p">
                        Anonymised or aggregated data that cannot identify any individual may be retained
                        indefinitely for analytical purposes.
                    </p>
                </LegalSection>

                {/* ── 09 Data Deletion ──────────────────────────────── */}
                <LegalSection id="data-deletion" num="09" title="Data Deletion">
                    <h3 className="legal-h3">Developer-Initiated Deletion</h3>
                    <p className="legal-p">
                        Developers may delete memory content at any time through the Trace.Mem REST API
                        or the developer dashboard. Upon deletion, data is removed from active systems
                        promptly. Residual copies in backups are overwritten within a reasonable
                        timeframe.
                    </p>

                    <h3 className="legal-h3">Account Deletion</h3>
                    <p className="legal-p">
                        To delete your Trace.Mem account, please use the account management options
                        available in your dashboard settings or contact us directly. Upon account
                        deletion:
                    </p>
                    <ul className="legal-ul">
                        <li>Your account data and associated memory content will be scheduled for deletion</li>
                        <li>Active subscriptions must be cancelled before or at the time of deletion</li>
                        <li>Billing records may be retained as required by law</li>
                    </ul>

                    <h3 className="legal-h3">Deletion Requests from End Users</h3>
                    <p className="legal-p">
                        If an end user of a Developer's application requests deletion of their data from
                        Trace.Mem, the Developer is responsible for handling that request. Developers
                        may use our API to delete that user's memory data from our systems. Where a
                        Developer fails to act, end users may contact us directly and we will assist
                        where technically and legally feasible.
                    </p>
                </LegalSection>

                {/* ── 10 Your Rights ────────────────────────────────── */}
                <LegalSection id="your-rights" num="10" title="Your Rights">
                    <p className="legal-p">
                        Subject to applicable law, you may have the following rights regarding your
                        personal data:
                    </p>
                    <ul className="legal-ul">
                        <li>
                            <strong>Withdrawal of Consent</strong> — You may withdraw consent to
                            processing at any time where we rely on consent as the legal basis.
                            Withdrawal does not affect the lawfulness of processing before withdrawal.
                        </li>
                        <li>
                            <strong>Access</strong> — You may request a copy of the personal data we
                            hold about you.
                        </li>
                        <li>
                            <strong>Correction</strong> — You may request correction of inaccurate or
                            incomplete personal data we hold about you.
                        </li>
                        <li>
                            <strong>Deletion (Right to be Forgotten)</strong> — You may request
                            deletion of your personal data, subject to legal retention obligations.
                        </li>
                        <li>
                            <strong>Restriction</strong> — You may request that we restrict processing
                            of your data in certain circumstances.
                        </li>
                        <li>
                            <strong>Grievance Redressal</strong> — Under the DPDP Act, 2023, you may
                            raise a grievance with our designated Grievance Officer (see Section 15).
                        </li>
                    </ul>
                    <p className="legal-p">
                        To exercise any of these rights, please contact us at{' '}
                        <a
                            href="mailto:trace.mem.official@gmail.com"
                            style={{ color: 'var(--tm-primary)', textDecoration: 'underline', textUnderlineOffset: '3px' }}
                        >
                            trace.mem.official@gmail.com
                        </a>. We will respond to requests within 30 days of receipt, as required by
                        applicable law.
                    </p>
                </LegalSection>

                {/* ── 11 Data Security ──────────────────────────────── */}
                <LegalSection id="security" num="11" title="Data Security">
                    <p className="legal-p">
                        We implement reasonable technical and organisational security measures to protect
                        your personal data, including:
                    </p>
                    <ul className="legal-ul">
                        <li>Encryption of data in transit using TLS (HTTPS)</li>
                        <li>Encryption of data at rest</li>
                        <li>API key hashing and secure storage</li>
                        <li>Access controls and role-based permissions for internal systems</li>
                        <li>Regular security monitoring and logging</li>
                        <li>Vendor security assessments</li>
                    </ul>
                    <div className="legal-callout">
                        <p>
                            No method of electronic transmission or storage is completely secure. While
                            we strive to protect your data, we cannot guarantee absolute security.
                        </p>
                    </div>
                    <p className="legal-p">
                        If you discover a security vulnerability, please report it responsibly to{' '}
                        <a
                            href="mailto:trace.mem.official@gmail.com"
                            style={{ color: 'var(--tm-primary)', textDecoration: 'underline', textUnderlineOffset: '3px' }}
                        >
                            trace.mem.official@gmail.com
                        </a>. In the event of a personal data breach that poses a risk to your rights,
                        we will notify affected parties as required by applicable law.
                    </p>
                </LegalSection>

                {/* ── 12 International Transfers ────────────────────── */}
                <LegalSection id="international" num="12" title="International Transfers">
                    <p className="legal-p">
                        Trace.Mem is headquartered in India. However, some of our infrastructure,
                        third-party service providers (including Stripe and AI model providers), and data
                        processing operations may be located outside India.
                    </p>
                    <p className="legal-p">
                        Where we transfer personal data internationally, we take steps to ensure that
                        appropriate safeguards are in place consistent with applicable law, including the
                        DPDP Act, 2023 and any rules issued thereunder.
                    </p>
                    <p className="legal-p">
                        International transfers may include processing in jurisdictions where our cloud
                        infrastructure providers and AI service providers operate. We engage with these
                        providers under data processing agreements that impose appropriate data protection
                        obligations.
                    </p>
                </LegalSection>

                {/* ── 13 Children's Privacy ─────────────────────────── */}
                <LegalSection id="childrens-privacy" num="13" title="Children's Privacy">
                    <p className="legal-p">
                        The Trace.Mem platform is intended solely for Developers and businesses. It is
                        not directed at children under the age of 18.
                    </p>
                    <p className="legal-p">
                        We do not knowingly collect personal data from individuals under the age of 18.
                        If we become aware that we have collected data from a minor without appropriate
                        consent, we will take steps to delete such data promptly.
                    </p>
                    <p className="legal-p">
                        If you believe we have inadvertently collected data from a minor, please
                        contact us at{' '}
                        <a
                            href="mailto:trace.mem.official@gmail.com"
                            style={{ color: 'var(--tm-primary)', textDecoration: 'underline', textUnderlineOffset: '3px' }}
                        >
                            trace.mem.official@gmail.com
                        </a>.
                    </p>
                </LegalSection>

                {/* ── 14 Third-Party Services ───────────────────────── */}
                <LegalSection id="third-party" num="14" title="Third-Party Services">
                    <p className="legal-p">
                        We use the following categories of third-party services that may process personal
                        data on our behalf or have access to certain data:
                    </p>

                    <h3 className="legal-h3">Payment Processing</h3>
                    <p className="legal-p">
                        Stripe, Inc. — for billing and subscription management.{' '}
                        <a
                            href="https://stripe.com/privacy"
                            target="_blank"
                            rel="noreferrer"
                            style={{ color: 'var(--tm-primary)', textDecoration: 'underline', textUnderlineOffset: '3px' }}
                        >
                            Stripe Privacy Policy →
                        </a>
                    </p>

                    <h3 className="legal-h3">AI and Machine Learning Providers</h3>
                    <p className="legal-p">
                        We use third-party AI model providers to perform semantic processing and memory
                        extraction. These providers are engaged under appropriate data processing
                        agreements that prohibit them from using submitted data to train their own models.
                    </p>

                    <h3 className="legal-h3">Hosting and Infrastructure</h3>
                    <p className="legal-p">
                        Our platform is hosted on cloud infrastructure providers. All data is stored
                        within secure, access-controlled environments.
                    </p>

                    <h3 className="legal-h3">Email and Communications</h3>
                    <p className="legal-p">
                        We use email delivery providers to send transactional messages (account
                        verification, billing notifications, security alerts). These providers act as
                        data processors and are not permitted to use your email address for their own
                        marketing purposes.
                    </p>

                    <p className="legal-p">
                        We enter into data processing agreements with third-party providers where
                        required by applicable law.
                    </p>
                </LegalSection>

                {/* ── 15 Grievance Officer ──────────────────────────── */}
                <LegalSection id="grievance-officer" num="15" title="Grievance Officer">
                    <p className="legal-p">
                        In accordance with the Digital Personal Data Protection Act, 2023, and applicable
                        information technology laws of India, we have designated a Grievance Officer to
                        address data protection concerns.
                    </p>
                    <p className="legal-p">
                        Grievances may be submitted in writing to our Grievance Officer via email.
                        We will acknowledge receipt within 48 hours and endeavour to resolve the
                        grievance within 30 days.
                    </p>

                    <div className="legal-contact-strip">
                        <span className="legal-contact-label">Grievance Officer — DPDP Act, 2023</span>
                        <h3 className="legal-contact-title">Submit a Grievance</h3>
                        <p className="legal-contact-desc">
                            For data protection grievances, privacy concerns, or rights requests,
                            please contact us directly. We respond to all enquiries within 48 hours.
                        </p>
                        <a
                            href="mailto:trace.mem.official@gmail.com"
                            className="legal-contact-email"
                            aria-label="Email Trace.Mem Grievance Officer"
                        >
                            trace.mem.official@gmail.com
                        </a>
                    </div>
                </LegalSection>

                {/* ── 16 Policy Changes ─────────────────────────────── */}
                <LegalSection id="policy-changes" num="16" title="Changes to This Privacy Policy">
                    <p className="legal-p">
                        We may update this Privacy Policy from time to time as our services evolve or
                        as required by applicable law.
                    </p>
                    <p className="legal-p">When we make material changes, we will:</p>
                    <ul className="legal-ul">
                        <li>Update the "Last Updated" date at the top of this Policy</li>
                        <li>
                            Notify registered Developers by email or through a notice on the dashboard
                        </li>
                        <li>Where required by law, seek fresh consent</li>
                    </ul>
                    <p className="legal-p">
                        Your continued use of the platform after any changes constitutes your acceptance
                        of the updated Policy. If you do not agree with the changes, you may close your
                        account.
                    </p>
                </LegalSection>

                {/* ── 17 Contact Us ─────────────────────────────────── */}
                <LegalSection id="contact" num="17" title="Contact Us">
                    <p className="legal-p">
                        For questions, concerns, or requests related to this Privacy Policy or your
                        personal data, please reach out to us. For grievance-related matters, refer to
                        Section 15.
                    </p>

                    <div className="legal-contact-strip">
                        <span className="legal-contact-label">Privacy Enquiries</span>
                        <h3 className="legal-contact-title">Get in touch with our team</h3>
                        <p className="legal-contact-desc">
                            We take all privacy enquiries seriously and aim to respond within 3 business
                            days. For urgent security concerns, please mark your subject line
                            accordingly.
                        </p>
                        <a
                            href="mailto:trace.mem.official@gmail.com"
                            className="legal-contact-email"
                            aria-label="Email Trace.Mem privacy team"
                        >
                            trace.mem.official@gmail.com
                        </a>
                    </div>
                </LegalSection>
            </LegalPageShell>
        </>
    );
}
