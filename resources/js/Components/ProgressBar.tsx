import React from 'react';

type Color = 'green' | 'blue' | 'red' | 'amber';

interface Props {
    value: number;
    color?: Color;
    showLabel?: boolean;
    label?: string;
}

const colorFillMap: Record<Color, string> = {
    green: 'bg-secondary',
    blue:  'bg-blue-500',
    red:   'bg-red-500',
    amber: 'bg-amber-500',
};

export default function ProgressBar({ value, color = 'green', showLabel = false, label }: Props) {
    const clamped = Math.max(0, Math.min(100, value));
    const displayLabel = label ?? `${clamped}%`;

    return (
        <div>
            {showLabel && (
                <p className="text-xs text-gray-500 mb-1">{displayLabel}</p>
            )}
            <div
                role="progressbar"
                aria-valuenow={clamped}
                aria-valuemin={0}
                aria-valuemax={100}
                aria-label={displayLabel}
                className="progress-bar"
            >
                <div
                    className={`progress-bar-fill ${colorFillMap[color]}`}
                    style={{ width: `${clamped}%` }}
                />
            </div>
        </div>
    );
}
