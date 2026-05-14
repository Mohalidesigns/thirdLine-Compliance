import React from 'react';
import { Link } from '@inertiajs/react';

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface Props {
    links: PaginationLink[];
    from: number;
    to: number;
    total: number;
}

function sanitizeLabel(label: string): string {
    return label
        .replace('&laquo;', '←')
        .replace('&raquo;', '→')
        .replace(/&[a-z]+;/gi, '');
}

export default function Pagination({ links, from, to, total }: Props) {
    if (total === 0) return null;

    return (
        <nav
            aria-label="Pagination"
            className="flex items-center justify-between px-4 py-3 border-t border-gray-100"
        >
            <span className="text-sm" style={{ color: 'var(--color-text-secondary)' }}>
                Showing {from}–{to} of {total}
            </span>
            <div className="flex items-center gap-1">
                {links.map((link, index) => {
                    const display = sanitizeLabel(link.label);
                    if (link.url === null) {
                        return (
                            <span
                                key={index}
                                className="px-3 py-1.5 text-sm text-gray-300 cursor-not-allowed"
                                aria-label={link.label}
                            >
                                {display}
                            </span>
                        );
                    }
                    if (link.active) {
                        return (
                            <span
                                key={index}
                                aria-current="page"
                                className="px-3 py-1.5 text-sm font-semibold rounded-md bg-primary text-white"
                            >
                                {display}
                            </span>
                        );
                    }
                    return (
                        <Link
                            key={index}
                            href={link.url}
                            preserveScroll
                            className="px-3 py-1.5 text-sm rounded-md text-gray-600 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-primary"
                            aria-label={link.label}
                        >
                            {display}
                        </Link>
                    );
                })}
            </div>
        </nav>
    );
}
