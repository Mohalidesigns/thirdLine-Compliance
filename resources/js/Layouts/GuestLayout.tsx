import { Link } from '@inertiajs/react';
import { PropsWithChildren } from 'react';

export default function Guest({ children }: PropsWithChildren) {
    return (
        <div
            className="flex min-h-screen flex-col items-center pt-6 sm:justify-center sm:pt-0"
            style={{ backgroundColor: 'var(--color-bg)' }}
        >
            <div>
                <Link href="/" className="flex items-center gap-0">
                    <span
                        className="text-2xl font-bold leading-none"
                        style={{ color: 'var(--color-primary)' }}
                    >
                        A
                    </span>
                    <span
                        className="text-2xl font-bold leading-none"
                        style={{ color: 'var(--color-primary)' }}
                    >
                        theris
                    </span>
                </Link>
            </div>

            <div className="mt-6 w-full overflow-hidden bg-white px-6 py-8 sm:max-w-md rounded-xl border border-gray-100"
                style={{ boxShadow: 'var(--shadow-card)' }}
            >
                {children}
            </div>
        </div>
    );
}
