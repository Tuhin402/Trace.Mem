import { useState, useCallback } from 'react';
import { Play, Terminal, Sparkles, Hash, Tag, Layers, FileText } from 'lucide-react';
import CtaButton from './cta-button';

/* ── Mode types ────────────────────────────────────────────── */
type Mode = 'semantic' | 'ai-first';

/* ── Mock memory result ────────────────────────────────────── */
type ExtractedMemory = {
    type: 'fact' | 'preference' | 'skill' | 'intent';
    content: string;
    confidence: number;
    source: string;
};

type PlaygroundResult = {
    memories: ExtractedMemory[];
    contextAssembly: string;
    modeDiff: string;
};

/* ── Sample conversations ──────────────────────────────────── */
const examples: Record<string, { label: string; text: string }> = {
    short: {
        label: 'Short conversation',
        text: `User: I prefer dark mode in all my tools.\nAssistant: Got it, I'll keep that in mind.\nUser: Also, I'm a backend developer working mostly with Go and PostgreSQL.`,
    },
    long: {
        label: 'Long conversation',
        text: `User: I've been working on a microservices architecture for the past 3 months.\nAssistant: That sounds like a significant project. What stack are you using?\nUser: Go for the services, gRPC for inter-service communication, and PostgreSQL for persistence.\nAssistant: Solid choices. Are you handling service discovery?\nUser: Yes, we use Consul for that. And we recently migrated from REST to gRPC for internal calls.\nAssistant: That should improve latency. How's the team handling the transition?\nUser: Most of the team adapted quickly. We have 5 backend engineers and 2 SREs.\nUser: Oh, and I always want code examples in Go, never Python.`,
    },
    code: {
        label: 'Code + text',
        text: `User: Here's my current auth middleware:\n\nfunc AuthMiddleware(next http.Handler) http.Handler {\n    return http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {\n        token := r.Header.Get("Authorization")\n        if token == "" {\n            http.Error(w, "unauthorized", 401)\n            return\n        }\n        next.ServeHTTP(w, r)\n    })\n}\n\nUser: I want to add JWT validation to this. I prefer using the golang-jwt library.`,
    },
    conflict: {
        label: 'Conflicting statements',
        text: `User: I'm using MySQL for my database.\nAssistant: Noted, MySQL it is.\nUser: Actually wait, we switched to PostgreSQL last month.\nAssistant: Updated — PostgreSQL now.\nUser: And my timezone is EST.\nUser: Sorry, I moved recently. My timezone is now PST.`,
    },
};

/* ── Mock processing logic ─────────────────────────────────── */
function generateMockResults(input: string, mode: Mode): PlaygroundResult {
    const isAiFirst = mode === 'ai-first';

    const memories: ExtractedMemory[] = [];

    /* Detect preferences */
    if (/prefer|always|never|like|want/i.test(input)) {
        memories.push({
            type: 'preference',
            content: isAiFirst
                ? 'User has strong tool/format preferences — should be respected in all future responses'
                : 'Keyword match: preference-related language detected',
            confidence: isAiFirst ? 0.94 : 0.78,
            source: 'preference-extractor',
        });
    }

    /* Detect facts */
    if (/working|using|developer|engineer|team|stack|database|PostgreSQL|MySQL|Go\b/i.test(input)) {
        memories.push({
            type: 'fact',
            content: isAiFirst
                ? 'User is a backend developer, works with specific tech stack — contextual identity established'
                : 'Factual entities detected: role, technologies, team structure',
            confidence: isAiFirst ? 0.91 : 0.72,
            source: 'fact-extractor',
        });
    }

    /* Detect skills */
    if (/middleware|function|code|auth|gRPC|microservices/i.test(input)) {
        memories.push({
            type: 'skill',
            content: isAiFirst
                ? 'User demonstrates backend architecture expertise — adjust technical depth accordingly'
                : 'Technical terms detected: middleware, architecture patterns',
            confidence: isAiFirst ? 0.88 : 0.65,
            source: 'skill-detector',
        });
    }

    /* Detect intent */
    if (/want|need|help|add|migrate|switch|build/i.test(input)) {
        memories.push({
            type: 'intent',
            content: isAiFirst
                ? 'User has an active implementation goal — provide actionable, specific guidance'
                : 'Action-oriented language detected: potential request intent',
            confidence: isAiFirst ? 0.87 : 0.61,
            source: 'intent-classifier',
        });
    }

    /* Fallback if nothing matched */
    if (memories.length === 0) {
        memories.push({
            type: 'fact',
            content: isAiFirst
                ? 'General conversational context captured — no strong signals yet'
                : 'Low-confidence extraction — insufficient signal for structured memory',
            confidence: isAiFirst ? 0.52 : 0.34,
            source: 'fallback-extractor',
        });
    }

    /* Conflict detection */
    if (/actually|wait|sorry|switched|moved|changed/i.test(input)) {
        memories.push({
            type: 'fact',
            content: isAiFirst
                ? 'Contradiction detected and auto-resolved — newer statement supersedes older'
                : 'Potential conflict flagged — manual resolution may be needed',
            confidence: isAiFirst ? 0.96 : 0.7,
            source: 'conflict-resolver',
        });
    }

    const contextAssembly = memories
        .map((m, i) => `[${String(i + 1).padStart(2, '0')}] (${m.type}) ${m.content}`)
        .join('\n');

    const modeDiff = isAiFirst
        ? 'AI-First mode uses deep semantic understanding to extract implicit meaning, resolve conflicts automatically, and produce higher-confidence memory units with actionable context.'
        : 'Semantic-Only mode relies on keyword matching and surface-level NLP. It detects entities and patterns but may miss implicit preferences, nuanced intent, or contradictions.';

    return { memories, contextAssembly, modeDiff };
}

/* ── Component ─────────────────────────────────────────────── */
export default function PlaygroundSection() {
    const [input, setInput] = useState('');
    const [mode, setMode] = useState<Mode>('ai-first');
    const [isProcessing, setIsProcessing] = useState(false);
    const [results, setResults] = useState<PlaygroundResult | null>(null);

    const handleRun = useCallback(() => {
        if (!input.trim()) return;
        setIsProcessing(true);
        setResults(null);

        /* Simulate processing delay */
        setTimeout(() => {
            const result = generateMockResults(input, mode);
            setResults(result);
            setIsProcessing(false);
        }, 1500);
    }, [input, mode]);

    const loadExample = useCallback((key: string) => {
        const ex = examples[key];
        if (ex) {
            setInput(ex.text);
            setResults(null);
        }
    }, []);

    const typeIcons: Record<string, React.ReactNode> = {
        fact: <Hash size={12} />,
        preference: <Sparkles size={12} />,
        skill: <Layers size={12} />,
        intent: <Tag size={12} />,
    };

    return (
        <section className="lp-section playground-section" aria-label="TraceMem Playground">
            <div className="lp-section-inner">
                {/* Header */}
                <div className="playground-header">
                    <span className="lp-section-tag">Interactive Demo</span>
                    <h2 className="lp-section-title" style={{ textAlign: 'center' }}>
                        Try the TraceMem Playground
                    </h2>
                    <p className="lp-section-lead center">
                        Paste a sample conversation and see how TraceMem extracts structured
                        memories, detects types, and assembles context in real time.
                    </p>
                </div>

                {/* Split panels */}
                <div className="pg-panels">
                    {/* ── LEFT: Input ── */}
                    <div className="pg-input-panel">
                        <div className="pg-panel-head">
                            <span className="tl tl-red" />
                            <span className="tl tl-yellow" />
                            <span className="tl tl-green" />
                            <span className="pg-panel-title">input.conversation</span>
                        </div>

                        <div className="pg-input-body">
                            <textarea
                                className="pg-textarea"
                                value={input}
                                onChange={(e) => {
                                    setInput(e.target.value);
                                    setResults(null);
                                }}
                                placeholder="Paste a sample conversation here...&#10;&#10;Example:&#10;User: I prefer dark mode in all my tools.&#10;Assistant: Got it!"
                                aria-label="Sample conversation input"
                            />

                            {/* Mode toggle */}
                            <div className="pg-mode-row">
                                <span className="pg-mode-label">Mode</span>
                                <div className="pg-mode-toggle">
                                    <button
                                        type="button"
                                        className={`pg-mode-btn ${mode === 'semantic' ? 'active' : ''}`}
                                        onClick={() => {
                                            setMode('semantic');
                                            setResults(null);
                                        }}
                                    >
                                        Semantic Only
                                    </button>
                                    <button
                                        type="button"
                                        className={`pg-mode-btn ${mode === 'ai-first' ? 'active' : ''}`}
                                        onClick={() => {
                                            setMode('ai-first');
                                            setResults(null);
                                        }}
                                    >
                                        AI First
                                    </button>
                                </div>
                            </div>

                            {/* Quick examples */}
                            <div className="pg-chips-row">
                                {Object.entries(examples).map(([key, ex]) => (
                                    <button
                                        key={key}
                                        type="button"
                                        className="pg-chip"
                                        onClick={() => loadExample(key)}
                                    >
                                        {ex.label}
                                    </button>
                                ))}
                            </div>

                            {/* Actions */}
                            <div className="pg-actions">
                                <span className="pg-disclaimer">
                                    Preview only — no data is stored.
                                    <br />
                                    Results are simulated for demonstration.
                                </span>
                                <button
                                    type="button"
                                    className="cta-btn primary"
                                    onClick={handleRun}
                                    disabled={!input.trim() || isProcessing}
                                    style={{ opacity: !input.trim() ? 0.5 : 1 }}
                                >
                                    <span className="cta-label">
                                        <Play size={13} style={{ marginRight: 6 }} />
                                        Run Playground
                                    </span>
                                    <span className="cta-arrow">
                                        <Terminal size={14} />
                                    </span>
                                </button>
                            </div>
                        </div>
                    </div>

                    {/* ── RIGHT: Preview ── */}
                    <div className="pg-preview-panel">
                        <div className="pg-panel-head">
                            <span className="tl tl-red" />
                            <span className="tl tl-yellow" />
                            <span className="tl tl-green" />
                            <span className="pg-panel-title">output.analysis</span>
                        </div>

                        <div className="pg-preview-body">
                            {/* Empty state */}
                            {!isProcessing && !results && (
                                <div className="pg-empty-state">
                                    <div className="pg-empty-icon">
                                        <FileText size={22} />
                                    </div>
                                    <div className="pg-empty-title">Awaiting input</div>
                                    <div className="pg-empty-desc">
                                        Enter a conversation on the left and click
                                        "Run Playground" to see extracted memories,
                                        detected types, and context assembly output.
                                    </div>
                                </div>
                            )}

                            {/* Loading shimmer */}
                            {isProcessing && (
                                <div className="pg-shimmer-stack">
                                    <div className="pg-shimmer-bar" />
                                    <div className="pg-shimmer-bar short" />
                                    <div className="pg-shimmer-bar" />
                                    <div className="pg-shimmer-bar shorter" />
                                    <div className="pg-shimmer-bar short" />
                                </div>
                            )}

                            {/* Results */}
                            {results && !isProcessing && (
                                <>
                                    {/* Extracted memories */}
                                    <div className="pg-results-section">
                                        <div className="pg-results-label">
                                            Extracted Memories ({results.memories.length})
                                        </div>
                                        {results.memories.map((mem, i) => (
                                            <div className="pg-memory-card" key={i}>
                                                <div className="pg-memory-head">
                                                    <span className="pg-memory-type">
                                                        {typeIcons[mem.type]} {mem.type}
                                                    </span>
                                                    <span className="pg-memory-confidence">
                                                        {(mem.confidence * 100).toFixed(0)}% confidence
                                                    </span>
                                                </div>
                                                <div className="pg-memory-content">{mem.content}</div>
                                                <div className="pg-memory-meta">
                                                    <span className="pg-memory-meta-item">
                                                        Source: {mem.source}
                                                    </span>
                                                    <span className="pg-memory-meta-item">
                                                        Mode: {mode === 'ai-first' ? 'AI-First' : 'Semantic'}
                                                    </span>
                                                </div>
                                            </div>
                                        ))}
                                    </div>

                                    {/* Mode comparison */}
                                    <div className="pg-mode-diff">
                                        <div className="pg-mode-diff-title">
                                            Mode: {mode === 'ai-first' ? 'AI-First' : 'Semantic Only'} — Behavior
                                        </div>
                                        <div className="pg-mode-diff-text">{results.modeDiff}</div>
                                    </div>

                                    {/* Context assembly */}
                                    <div className="pg-results-section">
                                        <div className="pg-results-label">Context Assembly Output</div>
                                        <div className="pg-context-block">{results.contextAssembly}</div>
                                    </div>
                                </>
                            )}
                        </div>

                        {/* Progress footer */}
                        <div className="pg-preview-footer" aria-hidden="true">
                            <div className={`pg-preview-seg ${results ? 'active' : ''}`} />
                            <div className={`pg-preview-seg ${results && results.memories.length > 1 ? 'active' : ''}`} />
                            <div className={`pg-preview-seg ${results && results.memories.length > 2 ? 'active' : ''}`} />
                            <div className={`pg-preview-seg ${results && results.memories.length > 3 ? 'active' : ''}`} />
                        </div>
                    </div>
                </div>
            </div>
        </section>
    );
}
