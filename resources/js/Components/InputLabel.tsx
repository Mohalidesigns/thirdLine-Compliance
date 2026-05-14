import { LabelHTMLAttributes } from 'react';

interface Props extends LabelHTMLAttributes<HTMLLabelElement> {
    value?: string;
    required?: boolean;
}

export default function InputLabel({ value, className = '', required, children, ...props }: Props) {
    return (
        <label {...props} className={'form-label ' + className}>
            {value ? value : children}
            {required && (
                <span aria-hidden="true" className="text-red-500 ml-0.5">*</span>
            )}
        </label>
    );
}
