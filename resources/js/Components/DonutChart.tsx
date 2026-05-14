import React from 'react';
import ProgressBar from '@/Components/ProgressBar';

interface Segment {
    label: string;
    value: number;
    color: string;
}

interface Props {
    segments: Segment[];
    total?: number;
    centerLabel?: string;
    size?: number;
}

// Degraded ProgressBar-list fallback — full SVG donut chart is deferred to Phase 2.
export default function DonutChart({ segments, total, centerLabel }: Props) {
    const computedTotal = total ?? segments.reduce((sum, s) => sum + s.value, 0);

    return (
        <div className="space-y-3">
            {centerLabel && (
                <p className="text-sm font-semibold text-gray-700 mb-2">{centerLabel}</p>
            )}
            {segments.map((segment) => {
                const pct = computedTotal > 0 ? Math.round((segment.value / computedTotal) * 100) : 0;
                return (
                    <div key={segment.label} className="flex items-center gap-3">
                        <span
                            className="w-2.5 h-2.5 rounded-full flex-shrink-0"
                            style={{ backgroundColor: segment.color }}
                            aria-hidden="true"
                        />
                        <span className="text-xs text-gray-600 w-28 truncate">{segment.label}</span>
                        <div className="flex-1">
                            <ProgressBar value={pct} />
                        </div>
                        <span className="text-xs font-semibold text-gray-700 w-8 text-right">{pct}%</span>
                    </div>
                );
            })}
        </div>
    );
}
