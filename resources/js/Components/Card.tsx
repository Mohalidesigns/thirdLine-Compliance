import React from 'react';

interface Props {
    children: React.ReactNode;
    className?: string;
    padding?: 'none' | 'sm' | 'md' | 'lg';
    hover?: boolean;
}

const paddingMap = {
    none: '',
    sm: 'p-3',
    md: 'p-4',
    lg: 'p-6',
};

function Card({ children, className = '', padding = 'lg', hover = true }: Props) {
    return (
        <div
            className={
                'card ' +
                paddingMap[padding] +
                (hover ? '' : ' hover:shadow-none hover:translate-y-0') +
                ' ' +
                className
            }
        >
            {children}
        </div>
    );
}

function CardHeader({ children, className = '' }: { children: React.ReactNode; className?: string }) {
    return (
        <div className={'px-6 py-4 border-b border-gray-100 ' + className}>
            {children}
        </div>
    );
}

function CardBody({ children, className = '' }: { children: React.ReactNode; className?: string }) {
    return (
        <div className={'px-6 py-4 ' + className}>
            {children}
        </div>
    );
}

Card.Header = CardHeader;
Card.Body = CardBody;

export default Card;
