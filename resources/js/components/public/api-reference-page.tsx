import { Link } from '@inertiajs/react';
import { ArrowLeft, ArrowRight, Key, Code2, FileJson } from 'lucide-react';
import CodeWindow, { type CodeSnippets } from './code-window';
import ApiReferenceSidebar from './api-reference-sidebar';
import type { SidebarGroup } from './api-ref-nav';

/* ── Types ──────────────────────────────────────────────────── */
type BodyField = {
    key:         string;
    type:        string;
    description: string;
    required?:   boolean;
};

type Responses = {
    ok:         string;
    badRequest: string;
};

type NavLink = {
    href:  string;
    label: string;
};

type Props = {
    title:       string;
    description: string;
    endpoint:    string;
    method:      string;
    auth?:       string;
    body?:       BodyField[];
    responses:   Responses;
    snippets:    CodeSnippets;
    groups:      SidebarGroup[];
    prev?:       NavLink;
    next?:       NavLink;
};

/* ── Method badge color map ─────────────────────────────────── */
const methodClass: Record<string, string> = {
    POST:     'method-post',
    GET:      'method-get',
    PUT:      'method-put',
    DELETE:   'method-delete',
    PATCH:    'method-patch',
    GUIDE:    'method-guide',
    OVERVIEW: 'method-overview',
};

/* ── Component ──────────────────────────────────────────────── */
export default function ApiReferencePage({
    title,
    description,
    endpoint,
    method,
    auth,
    body = [],
    responses,
    snippets,
    groups,
    prev,
    next,
}: Props) {
    return (
        <div className="api-docs-shell">
            <div className="api-docs-inner">
                {/* Sidebar */}
                <ApiReferenceSidebar groups={groups} />

                {/* Main content */}
                <div className="api-docs-content">

                    {/* ── Page hero ─────────────────────────────────────── */}
                    <div className="api-page-hero">
                        <div className="api-page-eyebrow">TraceMem API</div>
                        <h1 className="api-page-title">{title}</h1>
                        <p className="api-page-desc">{description}</p>
                    </div>

                    {/* ── Endpoint + Auth card ─────────────────────────── */}
                    <div className="api-card">
                        <div className="api-card-label">
                            <Code2 size={13} />
                            Endpoint
                        </div>

                        <div className="api-endpoint-row">
                            <span className={`method-badge ${methodClass[method] ?? 'method-guide'}`}>
                                {method}
                            </span>
                            <code className="api-endpoint-path">{endpoint}</code>
                        </div>

                        {auth && (
                            <div className="api-auth-block">
                                <div className="api-auth-label">
                                    <Key size={12} />
                                    Authorization
                                </div>
                                <code className="api-auth-value">{auth}</code>
                            </div>
                        )}
                    </div>

                    {/* ── Request body ─────────────────────────────────── */}
                    {body.length > 0 && (
                        <div className="api-card">
                            <div className="api-card-label">
                                <FileJson size={13} />
                                Request Body
                            </div>

                            <div className="api-field-table">
                                <div className="api-field-head">
                                    <div>Key</div>
                                    <div>Type</div>
                                    <div>Description</div>
                                </div>

                                {body.map((field) => (
                                    <div key={field.key} className="api-field-row">
                                        <div>
                                            <code className="api-field-key">{field.key}</code>
                                            {field.required && (
                                                <span className="api-field-required">required</span>
                                            )}
                                        </div>
                                        <div className="api-field-type">{field.type}</div>
                                        <div className="api-field-desc">{field.description}</div>
                                    </div>
                                ))}
                            </div>
                        </div>
                    )}

                    {/* ── Code window ──────────────────────────────────── */}
                    <div className="api-card api-card-code">
                        <CodeWindow
                            title="API Integration"
                            snippets={snippets}
                        />
                    </div>

                    {/* ── Responses ─────────────────────────────────────── */}
                    <div className="api-response-grid">
                        <div className="api-card">
                            <div className="api-response-label api-response-ok">
                                <span className="api-response-dot" />
                                200 OK
                            </div>
                            <pre className="api-response-pre">
                                <code>{responses.ok}</code>
                            </pre>
                        </div>

                        <div className="api-card">
                            <div className="api-response-label api-response-err">
                                <span className="api-response-dot" />
                                400 Error
                            </div>
                            <pre className="api-response-pre">
                                <code>{responses.badRequest}</code>
                            </pre>
                        </div>
                    </div>

                    {/* ── Prev / Next navigation ────────────────────────── */}
                    {(prev || next) && (
                        <div className="api-page-nav">
                            <div className="api-page-nav-side">
                                {prev && (
                                    <Link href={prev.href} className="api-nav-btn api-nav-btn-prev">
                                        <ArrowLeft size={14} />
                                        <span className="api-nav-btn-label">{prev.label}</span>
                                    </Link>
                                )}
                            </div>
                            <div className="api-page-nav-side api-page-nav-right">
                                {next && (
                                    <Link href={next.href} className="api-nav-btn api-nav-btn-next">
                                        <span className="api-nav-btn-label">{next.label}</span>
                                        <ArrowRight size={14} />
                                    </Link>
                                )}
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}