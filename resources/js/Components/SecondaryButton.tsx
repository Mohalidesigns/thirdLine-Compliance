import React from 'react';

interface Props extends React.ButtonHTMLAttributes<HTMLButtonElement> {}

export default function SecondaryButton({
    className = '',
    disabled,
    children,
    ...props
}: Props) {
    return (
        <button
            {...props}
            disabled={disabled}
            className={
                'inline-flex items-center gap-2 rounded-lg border border-gray-300 ' +
                'bg-white px-4 py-2 text-xs font-semibold uppercase tracking-widest text-gray-700 ' +
                'shadow-sm hover:bg-gray-50 ' +
                'focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 ' +
                'disabled:opacity-25 disabled:cursor-not-allowed ' +
                'transition ease-in-out duration-150 ' +
                className
            }
        >
            {children}
        </button>
    );
}
