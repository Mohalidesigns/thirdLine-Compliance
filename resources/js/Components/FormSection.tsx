import React from 'react';
import Card from '@/Components/Card';

interface Props {
    title?: string;
    description?: string;
    children: React.ReactNode;
}

export default function FormSection({ title, description, children }: Props) {
    return (
        <Card hover={false} padding="none" className="mb-6">
            {(title || description) && (
                <Card.Header>
                    {title && (
                        <h2 className="text-sm font-semibold" style={{ color: 'var(--color-text-primary)' }}>
                            {title}
                        </h2>
                    )}
                    {description && (
                        <p className="text-xs mt-0.5" style={{ color: 'var(--color-text-secondary)' }}>
                            {description}
                        </p>
                    )}
                </Card.Header>
            )}
            <Card.Body>
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4">
                    {children}
                </div>
            </Card.Body>
        </Card>
    );
}
