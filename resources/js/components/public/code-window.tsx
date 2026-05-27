import { useMemo, useState } from 'react';
import { Check, Copy } from 'lucide-react';

export type CodeSnippets = {
    python?:     string;
    javascript?: string;
    php?:        string;
    curl?:       string;
    java?:       string;
    go?:         string;
};

type TabKey = keyof CodeSnippets;

type Props = {
    snippets: CodeSnippets;
    title?: string;
};

const ALL_TABS: { key: TabKey; label: string }[] = [
    { key: 'python',     label: 'Python'  },
    { key: 'javascript', label: 'Node.js' },
    { key: 'php',        label: 'PHP'     },
    { key: 'curl',       label: 'cURL'    },
    { key: 'java',       label: 'Java'    },
    { key: 'go',         label: 'Go'      },
];

export default function CodeWindow({ snippets, title }: Props) {
    /* Only show tabs for languages that have a snippet */
    const visibleTabs = useMemo(
        () => ALL_TABS.filter((t) => snippets[t.key] !== undefined && snippets[t.key] !== ''),
        [snippets],
    );

    const [active, setActive] = useState<TabKey>(() => visibleTabs[0]?.key ?? 'python');
    const [copied, setCopied] = useState(false);

    /* If active tab was removed from visible set, snap to first visible */
    const safeActive: TabKey =
        visibleTabs.find((t) => t.key === active)?.key ?? visibleTabs[0]?.key ?? 'python';

    const code = snippets[safeActive] ?? '# No snippet available for this language.';

    const copy = async () => {
        await navigator.clipboard.writeText(code);
        setCopied(true);
        window.setTimeout(() => setCopied(false), 1800);
    };

    return (
        <div className="code-win">
            {/* Header bar */}
            <div className="code-win-head">
                {/* macOS traffic lights */}
                <div className="traffic-lights" aria-hidden="true">
                    <span className="tl tl-red"    />
                    <span className="tl tl-yellow" />
                    <span className="tl tl-green"  />
                </div>

                <div className="code-win-title">{title ?? 'tracemem-api.sh'}</div>

                <div className="code-win-actions">
                    {/* Language tabs */}
                    <div className="code-tabs" role="tablist">
                        {visibleTabs.map((tab) => (
                            <button
                                key={tab.key}
                                type="button"
                                role="tab"
                                aria-selected={safeActive === tab.key}
                                className={`code-tab ${safeActive === tab.key ? 'active' : ''}`}
                                onClick={() => setActive(tab.key)}
                            >
                                {tab.label}
                            </button>
                        ))}
                    </div>

                    {/* Copy button */}
                    <button
                        type="button"
                        className={`code-copy-btn ${copied ? 'copied' : ''}`}
                        onClick={copy}
                        aria-label={copied ? 'Copied!' : 'Copy code'}
                    >
                        {copied ? <Check size={13} /> : <Copy size={13} />}
                        <span>{copied ? 'Copied' : 'Copy'}</span>
                    </button>
                </div>
            </div>

            {/* Code body */}
            <pre className="code-win-body" role="tabpanel">
                <code>{code}</code>
            </pre>
        </div>
    );
}