import React from 'react';

type Props = {
    content: string;
};

export default function MarkdownRenderer({ content }: Props) {
    if (!content) return null;

    const lines = content.split('\n');
    const elements: React.ReactNode[] = [];
    
    let i = 0;
    while (i < lines.length) {
        let line = lines[i];
        
        // Skip empty lines
        if (line.trim() === '') {
            i++;
            continue;
        }

        // Headers
        if (line.startsWith('#')) {
            const level = line.match(/^#+/)?.[0].length || 1;
            const text = line.replace(/^#+\s*/, '').trim();
            const cleanText = renderInlineCode(text);
            
            if (level === 1) {
                elements.push(<h1 key={i} className="docs-hero-h1" style={{ fontSize: '32px', marginBottom: '16px' }}>{cleanText}</h1>);
            } else if (level === 2) {
                elements.push(<h2 key={i} className="docs-section-h2" style={{ marginTop: '32px', marginBottom: '16px', fontSize: '24px' }}>{cleanText}</h2>);
            } else if (level === 3) {
                elements.push(<h3 key={i} className="docs-section-h3">{cleanText}</h3>);
            } else {
                elements.push(<h4 key={i} className="docs-section-h4">{cleanText}</h4>);
            }
            i++;
            continue;
        }

        // Callouts (Blockquotes starting with > [!TYPE])
        if (line.startsWith('>')) {
            const blockquoteLines = [];
            while (i < lines.length && lines[i].startsWith('>')) {
                blockquoteLines.push(lines[i].replace(/^>\s*/, ''));
                i++;
            }
            const joinedText = blockquoteLines.join('\n');
            let isAlert = false;
            let alertType = 'info';
            let alertContent = joinedText;
            
            if (joinedText.startsWith('[!NOTE]') || joinedText.startsWith('[!INFO]')) {
                isAlert = true;
                alertContent = joinedText.replace(/^\[!(NOTE|INFO)\]\s*/i, '');
            } else if (joinedText.startsWith('[!WARNING]') || joinedText.startsWith('[!IMPORTANT]')) {
                isAlert = true;
                alertType = 'warning';
                alertContent = joinedText.replace(/^\[!(WARNING|IMPORTANT)\]\s*/i, '');
            } else if (joinedText.startsWith('[!TIP]')) {
                isAlert = true;
                alertType = 'success';
                alertContent = joinedText.replace(/^\[!TIP\]\s*/i, '');
            } else if (joinedText.startsWith('[!CAUTION]')) {
                isAlert = true;
                alertType = 'danger';
                alertContent = joinedText.replace(/^\[!CAUTION\]\s*/i, '');
            }
            
            const badgeClass = alertType === 'warning' ? 'docs-memory-card-badge fact' : 
                               alertType === 'success' ? 'docs-memory-card-badge skill' :
                               alertType === 'danger' ? 'docs-memory-card-badge rule' : 
                               'docs-memory-card-badge preference';
            
            const borderColor = alertType === 'warning' ? '#F59E0B' : 
                                alertType === 'success' ? '#10B981' :
                                alertType === 'danger' ? '#EF4444' : 
                                'var(--tm-secondary)';

            elements.push(
                <div key={i} className={`docs-callout docs-callout-${alertType}`} style={{ marginTop: 24, marginBottom: 24, borderLeftColor: borderColor }}>
                    {isAlert && <span className={badgeClass} style={{ display: 'inline-block', marginBottom: 12 }}>{alertType.toUpperCase()}</span>}
                    <div style={{ fontFamily: 'var(--font-body)', fontSize: 15, lineHeight: 1.6, color: 'var(--tm-text-muted)' }}>
                        {renderInlineCode(alertContent)}
                    </div>
                </div>
            );
            continue;
        }

        // Code blocks
        if (line.startsWith('```')) {
            const codeLines = [];
            i++;
            while (i < lines.length && !lines[i].startsWith('```')) {
                codeLines.push(lines[i]);
                i++;
            }
            i++; // skip closing ```
            elements.push(
                <pre key={i} className="docs-code-block">
                    <code>{codeLines.join('\n')}</code>
                </pre>
            );
            continue;
        }

        // Unordered Lists
        if (line.trim().startsWith('- ') || line.trim().startsWith('* ')) {
            const listItems = [];
            while (i < lines.length && (lines[i].trim().startsWith('- ') || lines[i].trim().startsWith('* '))) {
                listItems.push(lines[i].replace(/^[-*]\s*/, ''));
                i++;
            }
            elements.push(
                <ul key={i} className="docs-list">
                    {listItems.map((item, idx) => <li key={idx}>{renderInlineCode(item)}</li>)}
                </ul>
            );
            continue;
        }

        // Ordered Lists
        if (/^\d+\.\s/.test(line.trim())) {
            const listItems = [];
            while (i < lines.length && /^\d+\.\s/.test(lines[i].trim())) {
                listItems.push(lines[i].replace(/^\d+\.\s*/, ''));
                i++;
            }
            elements.push(
                <ol key={i} className="docs-list docs-list-numbered">
                    {listItems.map((item, idx) => <li key={idx}>{renderInlineCode(item)}</li>)}
                </ol>
            );
            continue;
        }

        // Tables (simple markdown table parser)
        if (line.includes('|') && i + 1 < lines.length && lines[i+1].includes('|') && lines[i+1].includes('-')) {
            const headers = line.split('|').filter(Boolean).map(h => h.trim());
            i += 2; // skip header and separator
            const rows = [];
            while (i < lines.length && lines[i].includes('|')) {
                const row = lines[i].split('|').filter(Boolean).map(c => c.trim());
                rows.push(row);
                i++;
            }
            elements.push(
                <div key={i} className="docs-table-wrapper">
                    <table className="docs-table">
                        <thead>
                            <tr>
                                {headers.map((h, idx) => <th key={idx}>{renderInlineCode(h)}</th>)}
                            </tr>
                        </thead>
                        <tbody>
                            {rows.map((row, rowIdx) => (
                                <tr key={rowIdx}>
                                    {row.map((cell, cellIdx) => <td key={cellIdx}>{renderInlineCode(cell)}</td>)}
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            );
            continue;
        }
        
        // Horizontal Rules
        if (line.match(/^---/)) {
            elements.push(<hr key={i} className="docs-hr" />);
            i++;
            continue;
        }

        // Paragraphs
        const pLines = [];
        while (i < lines.length && lines[i].trim() !== '' && !lines[i].startsWith('#') && !lines[i].startsWith('>') && !lines[i].startsWith('```') && !lines[i].startsWith('- ') && !/^\d+\.\s/.test(lines[i])) {
            pLines.push(lines[i]);
            i++;
        }
        if (pLines.length > 0) {
            elements.push(<p key={i} style={{ marginBottom: 16 }}>{renderInlineCode(pLines.join('\n'))}</p>);
        }
    }

    return <div className="docs-markdown-content">{elements}</div>;
}

// Helper to parse `code` and **bold**
function renderInlineCode(text: string) {
    // Process bold first
    const parts = text.split(/(\*\*.*?\*\*)/g);
    
    return parts.map((part, index) => {
        if (part.startsWith('**') && part.endsWith('**')) {
            const inner = part.slice(2, -2);
            // check for code inside bold
            return <strong key={index} style={{ color: 'var(--tm-text)' }}>{renderCodeOnly(inner)}</strong>;
        }
        return <React.Fragment key={index}>{renderCodeOnly(part)}</React.Fragment>;
    });
}

function renderCodeOnly(text: string) {
    const parts = text.split(/(`[^`]+`)/g);
    return parts.map((part, index) => {
        if (part.startsWith('`') && part.endsWith('`')) {
            return <code key={index}>{part.slice(1, -1)}</code>;
        }
        return part;
    });
}
