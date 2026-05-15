import React, { useEffect, useState } from 'react';
import { usePage } from '@inertiajs/react';
import {
    CheckCircleIcon,
    XCircleIcon,
    ExclamationTriangleIcon,
    InformationCircleIcon,
    XMarkIcon,
} from '@heroicons/react/24/outline';
import type { PageProps } from '@/types';

type FlashType = 'success' | 'error' | 'warning' | 'info';

type HeroIcon = React.ForwardRefExoticComponent<
    React.PropsWithoutRef<React.SVGProps<SVGSVGElement>> & {
        title?: string;
        titleId?: string;
    } & React.RefAttributes<SVGSVGElement>
>;

interface Toast {
    id: number;
    type: FlashType;
    message: string;
    progress: number;
}

interface Flash {
    success?: string;
    error?: string;
    warning?: string;
    info?: string;
}

const variantConfig: Record<FlashType, {
    container: string;
    icon: string;
    title: string;
    progress: string;
    Icon: HeroIcon;
}> = {
    success: {
        container: 'bg-green-50 border-green-200',
        icon: 'text-green-600',
        title: 'text-green-800',
        progress: 'bg-green-400',
        Icon: CheckCircleIcon,
    },
    error: {
        container: 'bg-red-50 border-red-200',
        icon: 'text-red-600',
        title: 'text-red-800',
        progress: 'bg-red-400',
        Icon: XCircleIcon,
    },
    warning: {
        container: 'bg-amber-50 border-amber-200',
        icon: 'text-amber-600',
        title: 'text-amber-800',
        progress: 'bg-amber-400',
        Icon: ExclamationTriangleIcon,
    },
    info: {
        container: 'bg-blue-50 border-blue-200',
        icon: 'text-blue-600',
        title: 'text-blue-800',
        progress: 'bg-blue-400',
        Icon: InformationCircleIcon,
    },
};

let nextId = 0;

export default function FlashNotification() {
    const { flash } = usePage<PageProps>().props;
    const [toasts, setToasts] = useState<Toast[]>([]);

    useEffect(() => {
        const types: FlashType[] = ['success', 'error', 'warning', 'info'];
        types.forEach((type) => {
            if (flash?.[type]) {
                const id = ++nextId;
                setToasts((prev) => [
                    ...prev,
                    { id, type, message: flash[type]!, progress: 100 },
                ]);

                const interval = setInterval(() => {
                    setToasts((prev) =>
                        prev.map((t) =>
                            t.id === id ? { ...t, progress: t.progress - 2 } : t,
                        ),
                    );
                }, 100);

                setTimeout(() => {
                    clearInterval(interval);
                    setToasts((prev) => prev.filter((t) => t.id !== id));
                }, 5000);
            }
        });
    }, [flash]);

    const dismiss = (id: number) => {
        setToasts((prev) => prev.filter((t) => t.id !== id));
    };

    if (toasts.length === 0) return null;

    return (
        <div
            role="region"
            aria-label="Notifications"
            aria-live="polite"
            className="fixed top-20 right-4 z-50 flex flex-col gap-3 pointer-events-none"
        >
            {toasts.map((toast) => {
                const config = variantConfig[toast.type];
                const { Icon } = config;
                return (
                    <div
                        key={toast.id}
                        className={`w-80 rounded-lg border shadow-lg overflow-hidden pointer-events-auto ${config.container}`}
                    >
                        <div className="p-4 flex items-start gap-3">
                            <Icon
                                aria-hidden
                                className={`flex-shrink-0 mt-0.5 w-5 h-5 ${config.icon}`}
                            />
                            <div className="flex-1 min-w-0">
                                <p className={`text-sm font-semibold ${config.title}`}>
                                    {toast.type.charAt(0).toUpperCase() + toast.type.slice(1)}
                                </p>
                                <p className="text-xs mt-0.5 text-gray-600">{toast.message}</p>
                            </div>
                            <button
                                type="button"
                                onClick={() => dismiss(toast.id)}
                                aria-label="Dismiss notification"
                                className="ml-auto flex-shrink-0 p-1 rounded hover:bg-black/5"
                            >
                                <XMarkIcon className="w-4 h-4 text-gray-400" aria-hidden />
                            </button>
                        </div>
                        <div
                            className={`h-1 w-full transition-all duration-100 ease-linear ${config.progress}`}
                            style={{ width: `${toast.progress}%` }}
                        />
                    </div>
                );
            })}
        </div>
    );
}
