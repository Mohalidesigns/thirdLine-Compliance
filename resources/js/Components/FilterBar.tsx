import React, { useCallback } from 'react';

interface FilterConfig {
    id: string;
    label: string;
    type: 'text' | 'select' | 'date';
    placeholder?: string;
    options?: { value: string; label: string }[];
    flex?: number;
}

interface Props {
    filters: FilterConfig[];
    values: Record<string, string>;
    onChange: (id: string, value: string) => void;
    onReset: () => void;
    isActive: boolean;
}

const inputClasses =
    'w-full rounded-lg border-0 bg-white shadow-sm ' +
    'ring-1 ring-gray-200 focus:ring-2 focus:ring-primary ' +
    'text-sm py-2.5 px-3 text-gray-700 placeholder-gray-400';

export default function FilterBar({ filters, values, onChange, onReset, isActive }: Props) {
    const handleKeyDown = useCallback(
        (e: React.KeyboardEvent) => {
            if (e.key === 'Escape') onReset();
        },
        [onReset],
    );

    return (
        <div className="filter-bar" onKeyDown={handleKeyDown}>
            <div className="flex flex-wrap items-end gap-4">
                {filters.map((config) => (
                    <div
                        key={config.id}
                        className="filter-group"
                        style={{ flex: config.flex ?? 1 }}
                    >
                        <label
                            htmlFor={`filter-${config.id}`}
                            className="text-[10px] font-semibold text-gray-400 uppercase tracking-wider"
                        >
                            {config.label}
                        </label>
                        {config.type === 'select' ? (
                            <select
                                id={`filter-${config.id}`}
                                value={values[config.id] ?? ''}
                                onChange={(e) => onChange(config.id, e.target.value)}
                                className={inputClasses + ' appearance-none'}
                            >
                                <option value="">All</option>
                                {config.options?.map((opt) => (
                                    <option key={opt.value} value={opt.value}>
                                        {opt.label}
                                    </option>
                                ))}
                            </select>
                        ) : (
                            <input
                                id={`filter-${config.id}`}
                                type={config.type}
                                value={values[config.id] ?? ''}
                                placeholder={config.placeholder}
                                onChange={(e) => onChange(config.id, e.target.value)}
                                className={inputClasses}
                            />
                        )}
                    </div>
                ))}
                {isActive && (
                    <button
                        type="button"
                        onClick={onReset}
                        aria-label="Clear all filters"
                        className="px-5 py-2.5 text-sm font-semibold text-gray-600 bg-white rounded-lg ring-1 ring-gray-200 hover:bg-gray-100 hover:text-gray-800 self-end transition-colors"
                    >
                        Reset
                    </button>
                )}
            </div>
        </div>
    );
}
