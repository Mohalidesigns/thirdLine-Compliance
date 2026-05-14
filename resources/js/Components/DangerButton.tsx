import React from 'react';

interface Props extends React.ButtonHTMLAttributes<HTMLButtonElement> {
    isLoading?: boolean;
}

export default function DangerButton({
    className = '',
    disabled,
    isLoading = false,
    children,
    ...props
}: Props) {
    return (
        <button
            {...props}
            disabled={disabled || isLoading}
            className={
                'inline-flex items-center gap-2 rounded-lg border border-transparent ' +
                'bg-red-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white ' +
                'hover:bg-red-500 active:bg-red-700 ' +
                'focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 ' +
                'disabled:opacity-25 disabled:cursor-not-allowed ' +
                'transition ease-in-out duration-150 ' +
                className
            }
        >
            {children}
        </button>
    );
}
