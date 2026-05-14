import React from 'react';

interface Props extends React.TextareaHTMLAttributes<HTMLTextAreaElement> {
    hasError?: boolean;
}

export default function Textarea({ className = '', hasError = false, ...props }: Props) {
    return (
        <textarea
            {...props}
            className={
                'form-input resize-y min-h-[80px] ' +
                (hasError ? 'border-red-500 focus:border-red-500 focus:ring-red-500 ' : '') +
                className
            }
        />
    );
}
