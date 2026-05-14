import React from 'react';

interface Props extends React.ButtonHTMLAttributes<HTMLButtonElement> {
    isLoading?: boolean;
}

export default function PrimaryButton({
    className = '',
    disabled,
    isLoading = false,
    children,
    ...props
}: Props) {
    return (
        <button
            {...props}
            aria-busy={isLoading}
            disabled={disabled || isLoading}
            className={
                'inline-flex items-center gap-2 rounded-lg border border-transparent ' +
                'bg-primary px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white ' +
                'hover:bg-primary-light focus:bg-primary-light active:bg-primary-dark ' +
                'focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 ' +
                'disabled:opacity-25 disabled:cursor-not-allowed ' +
                'transition ease-in-out duration-150 ' +
                className
            }
        >
            {isLoading ? (
                <>
                    <svg
                        aria-hidden="true"
                        className="h-4 w-4 animate-spin"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                    >
                        <circle
                            className="opacity-25"
                            cx="12"
                            cy="12"
                            r="10"
                            stroke="currentColor"
                            strokeWidth="4"
                        />
                        <path
                            className="opacity-75"
                            fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
                        />
                    </svg>
                    <span>Loading…</span>
                </>
            ) : (
                children
            )}
        </button>
    );
}
