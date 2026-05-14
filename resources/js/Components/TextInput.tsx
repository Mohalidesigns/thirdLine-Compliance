import { forwardRef, InputHTMLAttributes, useEffect, useImperativeHandle, useRef } from 'react';

interface Props extends InputHTMLAttributes<HTMLInputElement> {
    isFocused?: boolean;
    hasError?: boolean;
}

export default forwardRef(function TextInput(
    { type = 'text', className = '', isFocused = false, hasError = false, ...props }: Props,
    ref,
) {
    const localRef = useRef<HTMLInputElement>(null);

    useImperativeHandle(ref, () => ({
        focus: () => localRef.current?.focus(),
    }));

    useEffect(() => {
        if (isFocused) {
            localRef.current?.focus();
        }
    }, [isFocused]);

    return (
        <input
            {...props}
            type={type}
            className={
                'form-input ' +
                (hasError ? 'border-red-500 focus:border-red-500 focus:ring-red-500 ' : '') +
                className
            }
            ref={localRef}
        />
    );
});
