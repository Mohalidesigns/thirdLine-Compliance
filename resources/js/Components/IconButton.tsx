import React from 'react';

interface Props extends React.ButtonHTMLAttributes<HTMLButtonElement> {
    label: string;
    size?: 'sm' | 'md';
}

export default function IconButton({
    label,
    size = 'md',
    className = '',
    children,
    ...props
}: Props) {
    const sizeClass = size === 'sm' ? 'p-1.5' : 'p-2';

    return (
        <button
            {...props}
            aria-label={label}
            className={
                `${sizeClass} min-w-[44px] min-h-[44px] flex items-center justify-center ` +
                'rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-100 ' +
                'focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-1 ' +
                'disabled:opacity-25 disabled:cursor-not-allowed ' +
                'transition-colors duration-150 ' +
                className
            }
        >
            {children}
        </button>
    );
}
