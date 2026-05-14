import React from 'react';

export interface Column<T> {
    key: keyof T | string;
    header: string;
    sortable?: boolean;
    width?: string;
    render?: (row: T) => React.ReactNode;
}

interface Props<T extends { id: number | string }> {
    columns: Column<T>[];
    data: T[];
    sortKey?: string;
    sortDir?: 'asc' | 'desc';
    onSort?: (key: string) => void;
    isLoading?: boolean;
    emptyState?: React.ReactNode;
    rowKey?: (row: T) => string;
    onRowClick?: (row: T) => void;
    stickyHeader?: boolean;
}

function SortIcon({ active, dir }: { active: boolean; dir?: 'asc' | 'desc' }) {
    if (active && dir === 'asc') {
        return (
            <svg className="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <path strokeLinecap="round" strokeLinejoin="round" d="M5 15l7-7 7 7" />
            </svg>
        );
    }
    if (active && dir === 'desc') {
        return (
            <svg className="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <path strokeLinecap="round" strokeLinejoin="round" d="M19 9l-7 7-7-7" />
            </svg>
        );
    }
    return (
        <svg className="w-3 h-3 text-gray-300 group-hover:text-gray-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
            <path strokeLinecap="round" strokeLinejoin="round" d="M7 16V4m0 0L3 8m4-4l4 4M17 8v12m0 0l4-4m-4 4l-4-4" />
        </svg>
    );
}

function SkeletonRows({ count, cols }: { count: number; cols: number }) {
    return (
        <>
            {Array.from({ length: count }).map((_, i) => (
                <tr key={i}>
                    {Array.from({ length: cols }).map((_, j) => (
                        <td key={j} className="px-4 py-3 border-b border-gray-100">
                            <div className="h-4 bg-gray-200 rounded animate-pulse" />
                        </td>
                    ))}
                </tr>
            ))}
        </>
    );
}

export default function DataTable<T extends { id: number | string }>({
    columns,
    data,
    sortKey,
    sortDir,
    onSort,
    isLoading = false,
    emptyState,
    rowKey,
    onRowClick,
    stickyHeader = false,
}: Props<T>) {
    const getKey = rowKey ?? ((row: T) => String(row.id));

    return (
        <div className="overflow-x-auto" aria-busy={isLoading}>
            <table className="data-table" role="table">
                <thead className={stickyHeader ? 'sticky top-0' : ''}>
                    <tr>
                        {columns.map((col) => {
                            const isActive = sortKey === String(col.key);
                            const ariaSort = col.sortable
                                ? isActive
                                    ? sortDir === 'asc'
                                        ? ('ascending' as const)
                                        : ('descending' as const)
                                    : ('none' as const)
                                : undefined;
                            return (
                                <th
                                    key={String(col.key)}
                                    scope="col"
                                    aria-sort={ariaSort}
                                    className={col.width ?? ''}
                                >
                                    {col.sortable && onSort ? (
                                        <button
                                            type="button"
                                            className="flex items-center gap-1 group w-full text-left hover:text-gray-700 transition-colors text-xs font-semibold text-gray-500 uppercase tracking-wider"
                                            onClick={() => onSort(String(col.key))}
                                        >
                                            {col.header}
                                            <SortIcon active={isActive} dir={sortDir} />
                                        </button>
                                    ) : (
                                        col.header
                                    )}
                                </th>
                            );
                        })}
                    </tr>
                </thead>
                <tbody>
                    {isLoading ? (
                        <SkeletonRows count={5} cols={columns.length} />
                    ) : data.length === 0 ? (
                        <tr>
                            <td colSpan={columns.length} className="border-b-0">
                                {emptyState}
                            </td>
                        </tr>
                    ) : (
                        data.map((row) => (
                            <tr
                                key={getKey(row)}
                                className={onRowClick ? 'cursor-pointer' : ''}
                                onClick={onRowClick ? () => onRowClick(row) : undefined}
                                tabIndex={onRowClick ? 0 : undefined}
                                onKeyDown={
                                    onRowClick
                                        ? (e) => e.key === 'Enter' && onRowClick(row)
                                        : undefined
                                }
                                role={onRowClick ? 'button' : undefined}
                            >
                                {columns.map((col) => (
                                    <td key={String(col.key)}>
                                        {col.render
                                            ? col.render(row)
                                            : String((row as Record<string, unknown>)[String(col.key)] ?? '')}
                                    </td>
                                ))}
                            </tr>
                        ))
                    )}
                </tbody>
            </table>
        </div>
    );
}
