import { HTMLAttributes } from 'react';

interface Props extends HTMLAttributes<HTMLParagraphElement> {
    message?: string;
}

export default function InputError({ message, className = '', ...props }: Props) {
    return message ? (
        <p
            {...props}
            role="alert"
            aria-live="polite"
            className={'text-sm text-red-600 mt-1 ' + className}
        >
            {message}
        </p>
    ) : null;
}
