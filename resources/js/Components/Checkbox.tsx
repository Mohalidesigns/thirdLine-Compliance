import { InputHTMLAttributes } from 'react';

export default function Checkbox({ className = '', ...props }: InputHTMLAttributes<HTMLInputElement>) {
    return (
        <input
            {...props}
            type="checkbox"
            className={
                'rounded border-gray-300 text-primary shadow-sm ' +
                'focus:ring-primary focus:ring-offset-1 ' +
                'disabled:opacity-50 disabled:cursor-not-allowed ' +
                className
            }
        />
    );
}
