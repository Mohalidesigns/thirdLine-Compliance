import React from 'react';

interface Props extends React.SelectHTMLAttributes<HTMLSelectElement> {
    hasError?: boolean;
}

export default function Select({ className = '', hasError = false, children, ...props }: Props) {
    return (
        <select
            {...props}
            className={
                'form-select ' +
                (hasError ? 'border-red-500 focus:border-red-500 focus:ring-red-500 ' : '') +
                className
            }
        >
            {children}
        </select>
    );
}
