import React from 'react';
import { Link } from '@inertiajs/react';

interface BreadcrumbItem {
    label: string;
    href?: string;
}

interface Props {
    title: string;
    subtitle?: string;
    breadcrumb?: BreadcrumbItem[];
    actions?: React.ReactNode;
}

export default function PageHeader({ title, subtitle, breadcrumb, actions }: Props) {
    return (
        <div className="page-header">
            <div>
                {breadcrumb && breadcrumb.length > 0 && (
                    <nav aria-label="Breadcrumb" className="flex items-center gap-1.5 text-sm text-gray-500 mb-1">
                        {breadcrumb.map((item, index) => (
                            <React.Fragment key={index}>
                                {index > 0 && (
                                    <span className="text-gray-300 select-none" aria-hidden="true">›</span>
                                )}
                                {item.href ? (
                                    <Link
                                        href={item.href}
                                        className="hover:text-gray-700 transition-colors"
                                    >
                                        {item.label}
                                    </Link>
                                ) : (
                                    <span className="text-gray-700 font-medium">{item.label}</span>
                                )}
                            </React.Fragment>
                        ))}
                    </nav>
                )}
                <h1 className="page-title">{title}</h1>
                {subtitle && (
                    <p className="text-sm mt-1" style={{ color: 'var(--color-text-secondary)' }}>
                        {subtitle}
                    </p>
                )}
            </div>
            {actions && (
                <div className="flex items-center gap-3 flex-shrink-0">
                    {actions}
                </div>
            )}
        </div>
    );
}
