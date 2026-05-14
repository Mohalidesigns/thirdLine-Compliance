import React from 'react';

type StatusVariant =
    | 'critical' | 'high' | 'medium' | 'low'
    | 'info' | 'completed' | 'draft' | 'overdue' | 'ai';

interface Props {
    variant: StatusVariant;
    label?: string;
    dot?: boolean;
    size?: 'sm' | 'md';
}

const variantMap: Record<StatusVariant, string> = {
    critical:  'bg-red-100 text-red-800 border-red-200',
    high:      'bg-orange-100 text-orange-800 border-orange-200',
    medium:    'bg-yellow-100 text-yellow-800 border-yellow-200',
    low:       'bg-green-100 text-green-800 border-green-200',
    info:      'bg-blue-100 text-blue-700 border-blue-200',
    completed: 'bg-purple-100 text-purple-700 border-purple-200',
    draft:     'bg-gray-100 text-gray-700 border-gray-200',
    overdue:   'bg-red-100 text-red-700 border-red-200',
    ai:        'bg-violet-100 text-violet-700 border-violet-200',
};

const dotColorMap: Partial<Record<StatusVariant, string>> = {
    critical: 'bg-red-500',
    overdue:  'bg-red-500',
    high:     'bg-orange-500',
    medium:   'bg-yellow-500',
    low:      'bg-green-500',
};

function toTitleCase(str: string): string {
    return str.charAt(0).toUpperCase() + str.slice(1);
}

export default function StatusBadge({ variant, label, dot = false, size = 'md' }: Props) {
    const sizeClass = size === 'sm' ? 'text-[10px]' : 'text-xs';
    const displayLabel = label ?? toTitleCase(variant);

    return (
        <span
            role="status"
            className={`badge border ${variantMap[variant]} ${sizeClass}`}
        >
            {dot && dotColorMap[variant] && (
                <span
                    aria-hidden="true"
                    className={`w-1.5 h-1.5 rounded-full mr-1.5 ${dotColorMap[variant]}`}
                />
            )}
            {displayLabel}
        </span>
    );
}
