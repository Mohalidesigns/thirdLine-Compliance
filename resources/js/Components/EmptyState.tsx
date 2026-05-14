import React from 'react';

interface Props {
    icon: React.ReactNode;
    title: string;
    description: string;
    action?: React.ReactNode;
}

export default function EmptyState({ icon, title, description, action }: Props) {
    return (
        <div className="text-center py-12 px-6">
            <div className="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 mb-4">
                {icon}
            </div>
            <p className="text-sm font-semibold text-gray-800 mb-1">{title}</p>
            <p className="text-sm text-gray-500 mb-4 max-w-sm mx-auto">{description}</p>
            {action && <div className="mt-4">{action}</div>}
        </div>
    );
}
