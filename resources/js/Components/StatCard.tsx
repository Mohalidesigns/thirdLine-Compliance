import React from 'react';

type Color = 'blue' | 'red' | 'green' | 'amber' | 'purple' | 'teal';

interface Props {
    title: string;
    value: number | string;
    icon: React.ReactNode;
    color: Color;
    trend?: { value: string; direction: 'up' | 'down' | 'neutral' };
    className?: string;
}

const colorMap: Record<Color, string> = {
    blue:   'bg-blue-100 text-blue-600',
    red:    'bg-red-100 text-red-600',
    green:  'bg-green-100 text-green-600',
    amber:  'bg-amber-100 text-amber-600',
    purple: 'bg-purple-100 text-purple-600',
    teal:   'bg-teal-100 text-teal-600',
};

const trendColorMap = {
    up:      'text-green-600',
    down:    'text-red-600',
    neutral: 'text-gray-500',
};

export default function StatCard({ title, value, icon, color, trend, className = '' }: Props) {
    return (
        <article
            aria-label={`${title}: ${value}`}
            className={'stat-card flex items-center justify-between ' + className}
        >
            <div>
                <p className="text-sm font-medium text-gray-500">{title}</p>
                <p className="text-2xl font-bold mt-1" style={{ color: 'var(--color-text-primary)' }}>
                    {value}
                </p>
                {trend && (
                    <p className={`mt-3 flex items-center gap-1 text-xs ${trendColorMap[trend.direction]}`}>
                        {trend.direction === 'up' && '↑'}
                        {trend.direction === 'down' && '↓'}
                        {trend.value}
                    </p>
                )}
            </div>
            <div className={`w-10 h-10 rounded-lg flex items-center justify-center ${colorMap[color]}`}>
                {icon}
            </div>
        </article>
    );
}
