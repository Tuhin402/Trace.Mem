import React, { useState, useEffect } from 'react';
import { router } from '@inertiajs/react';
import { ChevronUp, ChevronDown, Search, ArrowRight, ArrowLeft } from 'lucide-react';

export type ColumnDef<T> = {
    key: string;
    header: string;
    sortable?: boolean;
    render?: (row: T) => React.ReactNode;
};

export type RowAction<T> = {
    label: string;
    icon?: React.ReactNode;
    onClick: (row: T) => void;
    destructive?: boolean;
};

interface PaginationProps {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: { url: string | null; label: string; active: boolean }[];
}

interface DataTableProps<T> {
    data: T[];
    columns: ColumnDef<T>[];
    actions?: RowAction<T>[];
    pagination?: PaginationProps;
    searchable?: boolean;
    currentSort?: string;
    currentDirection?: 'asc' | 'desc';
    currentSearch?: string;
    onRowClick?: (row: T) => void;
}

export function DataTable<T extends { id: string | number }>({
    data,
    columns,
    actions,
    pagination,
    searchable = true,
    currentSort,
    currentDirection = 'desc',
    currentSearch = '',
    onRowClick,
}: DataTableProps<T>) {
    const [searchTerm, setSearchTerm] = useState(currentSearch);
    const [debouncedSearch, setDebouncedSearch] = useState(currentSearch);

    useEffect(() => {
        const timer = setTimeout(() => {
            setDebouncedSearch(searchTerm);
        }, 500);
        return () => clearTimeout(timer);
    }, [searchTerm]);

    // Initial mount skip
    const [isMounted, setIsMounted] = useState(false);

    useEffect(() => {
        if (!isMounted) {
            setIsMounted(true);
            return;
        }

        router.get(
            window.location.pathname,
            { search: debouncedSearch, page: 1 },
            { preserveState: true, preserveScroll: true, replace: true }
        );
    }, [debouncedSearch]);

    const handleSort = (columnKey: string) => {
        let newDirection = 'desc';
        if (currentSort === columnKey) {
            newDirection = currentDirection === 'desc' ? 'asc' : 'desc';
        }

        const currentParams = new URLSearchParams(window.location.search);
        currentParams.set('sort', columnKey);
        currentParams.set('direction', newDirection);

        router.get(
            `${window.location.pathname}?${currentParams.toString()}`,
            {},
            { preserveState: true, preserveScroll: true }
        );
    };

    const handlePageChange = (url: string | null) => {
        if (!url) return;
        router.get(url, {}, { preserveState: true, preserveScroll: true });
    };

    return (
        <div className="w-full bg-surface border border-almost-black">
            {/* Toolbar */}
            <div className="p-4 border-b border-almost-black/10 flex items-center justify-between">
                {searchable && (
                    <div className="relative w-full max-w-sm">
                        <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <Search className="h-4 w-4 text-on-background/50" />
                        </div>
                        <input
                            type="text"
                            placeholder="Search..."
                            value={searchTerm}
                            onChange={(e) => setSearchTerm(e.target.value)}
                            className="block w-full pl-10 pr-3 py-2 border border-almost-black/20 bg-background text-sm focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary transition-colors"
                        />
                    </div>
                )}
                <div className="flex items-center gap-3">
                    {/* Future filters slot */}
                </div>
            </div>

            {/* Table */}
            <div className="overflow-x-auto">
                <table className="w-full text-left border-collapse">
                    <thead>
                        <tr className="border-b border-almost-black/10 bg-almost-black/5">
                            {columns.map((col) => (
                                <th
                                    key={col.key}
                                    className={`px-6 py-3 text-xs font-bold uppercase tracking-wider text-on-background/70 ${
                                        col.sortable ? 'cursor-pointer hover:text-primary transition-colors' : ''
                                    }`}
                                    onClick={() => col.sortable && handleSort(col.key)}
                                >
                                    <div className="flex items-center gap-2">
                                        {col.header}
                                        {col.sortable && currentSort === col.key && (
                                            currentDirection === 'desc' ? (
                                                <ChevronDown className="h-4 w-4 text-primary" />
                                            ) : (
                                                <ChevronUp className="h-4 w-4 text-primary" />
                                            )
                                        )}
                                    </div>
                                </th>
                            ))}
                            {actions && actions.length > 0 && (
                                <th className="px-6 py-3 text-xs font-bold uppercase tracking-wider text-on-background/70 text-right">
                                    Actions
                                </th>
                            )}
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-almost-black/5">
                        {data.length === 0 ? (
                            <tr>
                                <td colSpan={columns.length + (actions ? 1 : 0)} className="px-6 py-12 text-center text-on-background/50">
                                    No results found.
                                </td>
                            </tr>
                        ) : (
                            data.map((row) => (
                                <tr
                                    key={row.id}
                                    onClick={() => onRowClick && onRowClick(row)}
                                    className={`group hover:bg-primary/5 transition-colors ${
                                        onRowClick ? 'cursor-pointer' : ''
                                    }`}
                                >
                                    {columns.map((col) => (
                                        <td key={col.key} className="px-6 py-4 whitespace-nowrap text-sm text-on-background">
                                            {col.render ? col.render(row) : (row as any)[col.key]}
                                        </td>
                                    ))}
                                    {actions && actions.length > 0 && (
                                        <td className="px-6 py-4 whitespace-nowrap text-right text-sm">
                                            <div className="flex justify-end gap-3 opacity-0 group-hover:opacity-100 transition-opacity">
                                                {actions.map((action, i) => (
                                                    <button
                                                        key={i}
                                                        onClick={(e) => {
                                                            e.stopPropagation();
                                                            action.onClick(row);
                                                        }}
                                                        className={`font-medium ${
                                                            action.destructive
                                                                ? 'text-destructive hover:text-destructive/80'
                                                                : 'text-primary hover:text-primary/80'
                                                        }`}
                                                    >
                                                        {action.label}
                                                    </button>
                                                ))}
                                            </div>
                                        </td>
                                    )}
                                </tr>
                            ))
                        )}
                    </tbody>
                </table>
            </div>

            {/* Pagination */}
            {pagination && pagination.last_page > 1 && (
                <div className="p-4 border-t border-almost-black/10 flex items-center justify-between">
                    <div className="text-sm text-on-background/60">
                        Showing <span className="font-bold">{((pagination.current_page - 1) * pagination.per_page) + 1}</span> to{' '}
                        <span className="font-bold">{Math.min(pagination.current_page * pagination.per_page, pagination.total)}</span> of{' '}
                        <span className="font-bold">{pagination.total}</span> results
                    </div>
                    <div className="flex items-center gap-1">
                        {pagination.links.map((link, i) => {
                            // Render Prev/Next with icons
                            if (link.label.includes('&laquo;')) {
                                return (
                                    <button
                                        key={i}
                                        onClick={() => handlePageChange(link.url)}
                                        disabled={!link.url}
                                        className="p-2 border border-almost-black/10 hover:border-primary text-on-background hover:text-primary disabled:opacity-50 disabled:hover:border-almost-black/10 disabled:hover:text-on-background transition-colors"
                                    >
                                        <ArrowLeft className="h-4 w-4" />
                                    </button>
                                );
                            }
                            if (link.label.includes('&raquo;')) {
                                return (
                                    <button
                                        key={i}
                                        onClick={() => handlePageChange(link.url)}
                                        disabled={!link.url}
                                        className="p-2 border border-almost-black/10 hover:border-primary text-on-background hover:text-primary disabled:opacity-50 disabled:hover:border-almost-black/10 disabled:hover:text-on-background transition-colors"
                                    >
                                        <ArrowRight className="h-4 w-4" />
                                    </button>
                                );
                            }
                            return (
                                <button
                                    key={i}
                                    onClick={() => handlePageChange(link.url)}
                                    className={`min-w-[32px] h-8 flex items-center justify-center border transition-colors ${
                                        link.active
                                            ? 'border-primary bg-primary text-white font-bold'
                                            : 'border-almost-black/10 hover:border-primary text-on-background hover:text-primary'
                                    }`}
                                >
                                    {link.label}
                                </button>
                            );
                        })}
                    </div>
                </div>
            )}
        </div>
    );
}
