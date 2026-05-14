import React, { SVGProps } from 'react';
import Modal from '@/Components/Modal';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import DangerButton from '@/Components/DangerButton';
import { ExclamationTriangleIcon, InformationCircleIcon } from '@heroicons/react/24/outline';

interface Props {
    show: boolean;
    onClose: () => void;
    onConfirm: () => void;
    variant?: 'danger' | 'warning' | 'info';
    title: string;
    message: string;
    confirmLabel?: string;
    cancelLabel?: string;
    isLoading?: boolean;
}

type HeroIcon = React.ForwardRefExoticComponent<
    React.PropsWithoutRef<SVGProps<SVGSVGElement>> & {
        title?: string;
        titleId?: string;
    } & React.RefAttributes<SVGSVGElement>
>;

const iconConfig: Record<string, { box: string; icon: string; Icon: HeroIcon }> = {
    danger:  { box: 'bg-red-100',    icon: 'text-red-600',    Icon: ExclamationTriangleIcon },
    warning: { box: 'bg-yellow-100', icon: 'text-yellow-600', Icon: ExclamationTriangleIcon },
    info:    { box: 'bg-blue-100',   icon: 'text-blue-600',   Icon: InformationCircleIcon },
};

export default function ConfirmDialog({
    show,
    onClose,
    onConfirm,
    variant = 'danger',
    title,
    message,
    confirmLabel = 'Confirm',
    cancelLabel = 'Cancel',
    isLoading = false,
}: Props) {
    const config = iconConfig[variant];
    const { Icon } = config;
    const msgId = `confirm-dialog-message-${title.replace(/\s+/g, '-').toLowerCase()}`;

    return (
        <Modal show={show} maxWidth="md" onClose={onClose}>
            <div className="p-6" aria-describedby={msgId}>
                <div className="flex items-center gap-3 mb-4">
                    <div className={`w-10 h-10 rounded-full flex items-center justify-center ${config.box}`}>
                        <Icon aria-hidden className={`w-5 h-5 ${config.icon}`} />
                    </div>
                    <h2 className="text-lg font-semibold text-gray-900">{title}</h2>
                </div>
                <p id={msgId} className="text-sm text-gray-500 mb-6">{message}</p>
                <div className="flex justify-end gap-3">
                    <SecondaryButton onClick={onClose}>{cancelLabel}</SecondaryButton>
                    {variant === 'danger' ? (
                        <DangerButton onClick={onConfirm} isLoading={isLoading}>
                            {confirmLabel}
                        </DangerButton>
                    ) : (
                        <PrimaryButton onClick={onConfirm} isLoading={isLoading}>
                            {confirmLabel}
                        </PrimaryButton>
                    )}
                </div>
            </div>
        </Modal>
    );
}
