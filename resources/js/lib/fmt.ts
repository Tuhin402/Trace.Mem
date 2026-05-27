/**
 * TraceMem — Shared formatting utilities
 * Used by Dashboard, ApiKeys, Logs pages for consistent display.
 */

/**
 * Format a raw integer count in a human-friendly abbreviated form.
 * - 0–999: as-is
 * - 1K–99K: e.g. "12.3K"
 * - 1L–99L (lakh range, 1,00,000+): e.g. "1.2L"
 * - 1Cr+ (crore, 1,00,00,000+): e.g. "2.4Cr"
 */
export function fmtNum(n: number | null | undefined): string {
    const v = n ?? 0;
    if (v >= 1_00_00_000) return `${(v / 1_00_00_000).toFixed(1).replace(/\.0$/, '')}Cr`;
    if (v >= 1_00_000)    return `${(v / 1_00_000).toFixed(1).replace(/\.0$/, '')}L`;
    if (v >= 1_000)       return `${(v / 1_000).toFixed(1).replace(/\.0$/, '')}K`;
    return String(v);
}

/**
 * Format a latency value.
 * - Under 1000ms: "243ms"
 * - 1000ms+: "1.2s"
 */
export function fmtLatency(ms: number | null | undefined): string {
    const v = ms ?? 0;
    if (v >= 1000) return `${(v / 1000).toFixed(1).replace(/\.0$/, '')}s`;
    return `${v}ms`;
}

/**
 * Format a monetary value as USD.
 */
export function fmtMoney(value: string | number | null | undefined): string {
    const n = Number(value ?? 0);
    return `$${Number.isFinite(n) ? n.toFixed(2) : '0.00'}`;
}

/**
 * Format a success rate as a percentage string.
 * Returns "—" if total is 0.
 */
export function fmtRate(successful: number, total: number): string {
    if (total === 0) return '—';
    return `${Math.round((successful / total) * 100)}%`;
}

/**
 * Return a CSS class name for an HTTP status code.
 */
export function statusClass(code: number): string {
    if (code >= 500) return 'app-status-5xx';
    if (code >= 400) return 'app-status-4xx';
    return 'app-status-2xx';
}

/**
 * Group a flat array of log items by calendar month.
 * Returns an array of { label: string, items: T[] } in chronological-descending order.
 *
 * Each item must have a `requested_at` string (ISO or similar parseable by Date).
 */
export function groupByMonth<T extends { requested_at: string }>(
    items: T[],
): Array<{ label: string; items: T[] }> {
    const now   = new Date();
    const thisY = now.getFullYear();
    const thisM = now.getMonth();

    const map = new Map<string, T[]>();

    for (const item of items) {
        const d    = new Date(item.requested_at);
        const y    = d.getFullYear();
        const m    = d.getMonth();
        const key  = `${y}-${String(m + 1).padStart(2, '0')}`;

        if (!map.has(key)) map.set(key, []);
        map.get(key)!.push(item);
    }

    // Sort keys descending
    const sorted = [...map.keys()].sort((a, b) => b.localeCompare(a));

    return sorted.map((key) => {
        const [y, m] = key.split('-').map(Number);
        const isThisMonth  = y === thisY && m - 1 === thisM;
        const isLastMonth  = y === thisY && m - 1 === thisM - 1
            || (thisM === 0 && y === thisY - 1 && m === 12);

        let label: string;
        if (isThisMonth) {
            label = 'This Month';
        } else if (isLastMonth) {
            label = 'Last Month';
        } else {
            label = new Date(y, m - 1, 1).toLocaleDateString('en-US', {
                month: 'long',
                year: 'numeric',
            });
        }

        return { label, items: map.get(key)! };
    });
}
