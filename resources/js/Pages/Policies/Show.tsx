import { useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import PageHeader from '@/Components/PageHeader';
import Card from '@/Components/Card';
import StatusBadge from '@/Components/StatusBadge';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import DangerButton from '@/Components/DangerButton';
import ConfirmDialog from '@/Components/ConfirmDialog';
import Modal from '@/Components/Modal';
import InputLabel from '@/Components/InputLabel';
import Textarea from '@/Components/Textarea';
import InputError from '@/Components/InputError';
import {
    DocumentArrowDownIcon,
    CheckBadgeIcon,
    PencilIcon,
    TrashIcon,
} from '@heroicons/react/24/outline';

interface PolicyVersion {
    id: number;
    version: number;
    state: string;
    state_label: string;
    transitioned_at: string;
    transitioned_by_name: string | null;
    transition_note: string | null;
}

interface Policy {
    id: number;
    reference: string;
    title: string;
    category: string;
    category_label: string;
    owner_team: string;
    version: number;
    state: string;
    state_label: string;
    state_color: string;
    effective_date: string | null;
    next_review_date: string | null;
    summary: string | null;
    body: string | null;
    created_at: string;
    updated_at: string;
    versions: PolicyVersion[];
    acknowledgements_count: number;
    published_pdf_path: string | null;
}

interface AllowedTransition {
    to: string;
    label: string;
    requires_note: boolean;
}

interface Props {
    policy: Policy;
    allowed_transitions: AllowedTransition[];
    can: {
        edit: boolean;
        transition: boolean;
        delete: boolean;
        acknowledge: boolean;
        download: boolean;
    };
    has_acknowledged: boolean;
}

type StatusVariant = 'critical' | 'high' | 'medium' | 'low' | 'info' | 'completed' | 'draft' | 'overdue' | 'ai';

function stateColorToVariant(color: string): StatusVariant {
    const map: Record<string, StatusVariant> = {
        gray:   'draft',
        blue:   'info',
        purple: 'completed',
        cyan:   'info',
        green:  'low',
        orange: 'high',
    };
    return map[color] ?? 'draft';
}

function formatDate(dateStr: string | null): string {
    if (!dateStr) return '—';
    const d = new Date(dateStr);
    if (isNaN(d.getTime())) return '—';
    return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}

function relativeTime(isoStr: string): string {
    const now = Date.now();
    const then = new Date(isoStr).getTime();
    const diffMs = now - then;
    const diffMins = Math.floor(diffMs / 60000);
    if (diffMins < 1) return 'just now';
    if (diffMins < 60) return `${diffMins}m ago`;
    const diffHours = Math.floor(diffMins / 60);
    if (diffHours < 24) return `${diffHours}h ago`;
    const diffDays = Math.floor(diffHours / 24);
    if (diffDays < 30) return `${diffDays}d ago`;
    const diffMonths = Math.floor(diffDays / 30);
    if (diffMonths < 12) return `${diffMonths}mo ago`;
    return `${Math.floor(diffMonths / 12)}y ago`;
}

const stateDescriptions: Record<string, string> = {
    draft:        'This policy is in draft. Submit for review when ready.',
    in_review:    'This policy is under review. Awaiting approval.',
    approved:     'This policy has been approved and is ready for publication.',
    published:    'This policy is published and available to all staff.',
    in_force:     'This policy is in force and actively enforced.',
    under_review: 'This policy is being reviewed for updates.',
    superseded:   'This policy has been superseded by a newer version.',
};

function DetailItem({ label, children }: { label: string; children: React.ReactNode }) {
    return (
        <div className="py-2.5 border-b border-gray-100 last:border-0 flex justify-between items-start gap-4">
            <span className="text-xs font-medium flex-shrink-0 w-28" style={{ color: 'var(--color-text-secondary)' }}>
                {label}
            </span>
            <span className="text-xs text-right" style={{ color: 'var(--color-text-primary)' }}>
                {children}
            </span>
        </div>
    );
}

interface TransitionFormData {
    to: string;
    note: string;
}

function TransitionModal({
    transition,
    policyStateLabel,
    onClose,
    onConfirm,
}: {
    transition: AllowedTransition;
    policyStateLabel: string;
    onClose: () => void;
    onConfirm: (to: string, note: string) => void;
}) {
    const { data, setData, errors, setError, clearErrors } = useForm<TransitionFormData>({
        to:   transition.to,
        note: '',
    });

    const [submitting, setSubmitting] = useState(false);

    const handleConfirm = () => {
        clearErrors();
        if (transition.requires_note && !data.note.trim()) {
            setError('note', 'A note is required for this transition.');
            return;
        }
        setSubmitting(true);
        onConfirm(data.to, data.note);
    };

    return (
        <Modal show onClose={onClose} maxWidth="md">
            <div className="p-6">
                <h2 className="text-lg font-semibold text-gray-900 mb-2">
                    Confirm transition: {transition.label}
                </h2>
                <p className="text-sm text-gray-500 mb-4">
                    This policy will move from{' '}
                    <span className="font-medium">{policyStateLabel}</span> to{' '}
                    <span className="font-medium">{transition.label}</span>. This action will be audited.
                </p>

                {transition.requires_note && (
                    <div className="mb-4">
                        <InputLabel htmlFor="transition-note" value="Note" required />
                        <Textarea
                            id="transition-note"
                            value={data.note}
                            onChange={(e) => setData('note', e.target.value)}
                            hasError={!!errors.note}
                            rows={3}
                            placeholder="Explain the reason for this transition…"
                            aria-required="true"
                            aria-describedby={errors.note ? 'transition-note-error' : undefined}
                            className="mt-1 w-full"
                        />
                        <InputError id="transition-note-error" message={errors.note} />
                    </div>
                )}

                <div className="flex justify-end gap-3 mt-4">
                    <SecondaryButton onClick={onClose}>Cancel</SecondaryButton>
                    <PrimaryButton onClick={handleConfirm} isLoading={submitting}>
                        Confirm
                    </PrimaryButton>
                </div>
            </div>
        </Modal>
    );
}

export default function PoliciesShow({ policy, allowed_transitions, can, has_acknowledged }: Props) {
    const [showDeleteDialog, setShowDeleteDialog] = useState(false);
    const [deleting, setDeleting] = useState(false);

    const [acknowledging, setAcknowledging] = useState(false);

    const [activeTransition, setActiveTransition] = useState<AllowedTransition | null>(null);

    const handleDelete = () => {
        setDeleting(true);
        router.delete(route('policies.destroy', policy.id), {
            onFinish: () => {
                setDeleting(false);
                setShowDeleteDialog(false);
            },
        });
    };

    const handleAcknowledge = () => {
        setAcknowledging(true);
        router.post(
            route('policies.acknowledge', policy.id),
            {},
            { onFinish: () => setAcknowledging(false) },
        );
    };

    const handleTransitionConfirm = (to: string, note: string) => {
        router.post(
            route('policies.transition', policy.id),
            { to, note },
            {
                onFinish: () => setActiveTransition(null),
            },
        );
    };

    const stateVariant = stateColorToVariant(policy.state_color);
    const stateDesc = stateDescriptions[policy.state] ?? '';

    const stateBannerBg: Record<StatusVariant, string> = {
        draft:     'bg-gray-50 border-gray-200',
        info:      'bg-blue-50 border-blue-200',
        completed: 'bg-purple-50 border-purple-200',
        low:       'bg-green-50 border-green-200',
        high:      'bg-orange-50 border-orange-200',
        medium:    'bg-yellow-50 border-yellow-200',
        critical:  'bg-red-50 border-red-200',
        overdue:   'bg-red-50 border-red-200',
        ai:        'bg-violet-50 border-violet-200',
    };

    return (
        <AuthenticatedLayout>
            <Head title={`${policy.reference} — ${policy.title}`} />

            <PageHeader
                title={`${policy.reference} — ${policy.title}`}
                breadcrumb={[
                    { label: 'Policies', href: route('policies.index') },
                    { label: policy.reference },
                ]}
                actions={
                    <div className="flex items-center gap-2 flex-wrap">
                        {can.download && (
                            <a
                                href={route('policies.download', policy.id)}
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                <SecondaryButton type="button">
                                    <DocumentArrowDownIcon aria-hidden className="w-4 h-4" />
                                    Download PDF
                                </SecondaryButton>
                            </a>
                        )}

                        {can.acknowledge && !has_acknowledged && (
                            <PrimaryButton
                                type="button"
                                onClick={handleAcknowledge}
                                isLoading={acknowledging}
                            >
                                <CheckBadgeIcon aria-hidden className="w-4 h-4" />
                                Acknowledge
                            </PrimaryButton>
                        )}

                        {has_acknowledged && (
                            <span className="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-xs font-semibold bg-green-100 text-green-800 border border-green-200">
                                <CheckBadgeIcon aria-hidden className="w-4 h-4" />
                                Acknowledged
                            </span>
                        )}

                        {can.edit && (
                            <Link href={route('policies.edit', policy.id)}>
                                <SecondaryButton type="button">
                                    <PencilIcon aria-hidden className="w-4 h-4" />
                                    Edit
                                </SecondaryButton>
                            </Link>
                        )}

                        {can.delete && (
                            <DangerButton type="button" onClick={() => setShowDeleteDialog(true)}>
                                <TrashIcon aria-hidden className="w-4 h-4" />
                                Delete
                            </DangerButton>
                        )}
                    </div>
                }
            />

            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                {/* Left column */}
                <div className="lg:col-span-2 space-y-6">
                    {/* Status banner */}
                    <div
                        className={`rounded-xl border p-4 flex items-start gap-3 ${stateBannerBg[stateVariant]}`}
                    >
                        <div className="flex-1">
                            <div className="flex items-center gap-2 mb-1">
                                <StatusBadge
                                    variant={stateVariant}
                                    label={policy.state_label}
                                />
                            </div>
                            {stateDesc && (
                                <p className="text-sm mt-1" style={{ color: 'var(--color-text-secondary)' }}>
                                    {stateDesc}
                                </p>
                            )}
                        </div>
                    </div>

                    {/* Policy body */}
                    {policy.body && (
                        <Card hover={false} padding="none">
                            <Card.Header>
                                <h2 className="text-sm font-semibold" style={{ color: 'var(--color-text-primary)' }}>
                                    Policy Body
                                </h2>
                            </Card.Header>
                            <Card.Body>
                                <div
                                    className="text-sm whitespace-pre-wrap leading-relaxed"
                                    style={{ color: 'var(--color-text-primary)' }}
                                >
                                    {policy.body}
                                </div>
                            </Card.Body>
                        </Card>
                    )}

                    {/* Summary */}
                    {policy.summary && (
                        <Card hover={false} padding="none">
                            <Card.Header>
                                <h2 className="text-sm font-semibold" style={{ color: 'var(--color-text-primary)' }}>
                                    Summary
                                </h2>
                            </Card.Header>
                            <Card.Body>
                                <p
                                    className="text-sm italic leading-relaxed"
                                    style={{ color: 'var(--color-text-secondary)' }}
                                >
                                    {policy.summary}
                                </p>
                            </Card.Body>
                        </Card>
                    )}
                </div>

                {/* Right column */}
                <div className="space-y-6">
                    {/* Details card */}
                    <Card hover={false} padding="none">
                        <Card.Header>
                            <h2 className="text-sm font-semibold" style={{ color: 'var(--color-text-primary)' }}>
                                Details
                            </h2>
                        </Card.Header>
                        <Card.Body>
                            <div className="divide-y divide-gray-100">
                                <DetailItem label="Reference">
                                    <span className="font-mono">{policy.reference}</span>
                                </DetailItem>
                                <DetailItem label="Category">
                                    <StatusBadge variant="info" label={policy.category_label} size="sm" />
                                </DetailItem>
                                <DetailItem label="Owner">
                                    {policy.owner_team}
                                </DetailItem>
                                <DetailItem label="Version">
                                    v{policy.version}
                                </DetailItem>
                                <DetailItem label="Effective">
                                    {formatDate(policy.effective_date)}
                                </DetailItem>
                                <DetailItem label="Next review">
                                    {formatDate(policy.next_review_date)}
                                </DetailItem>
                                <DetailItem label="Acknowledgements">
                                    {policy.acknowledgements_count} users
                                </DetailItem>
                            </div>
                        </Card.Body>
                    </Card>

                    {/* Transitions card */}
                    {can.transition && allowed_transitions.length > 0 && (
                        <Card hover={false} padding="none">
                            <Card.Header>
                                <h2 className="text-sm font-semibold" style={{ color: 'var(--color-text-primary)' }}>
                                    Transitions
                                </h2>
                            </Card.Header>
                            <Card.Body>
                                <div className="flex flex-col gap-2">
                                    {allowed_transitions.map((transition) => (
                                        <SecondaryButton
                                            key={transition.to}
                                            type="button"
                                            onClick={() => setActiveTransition(transition)}
                                            className="w-full justify-center text-xs"
                                        >
                                            {transition.label}
                                        </SecondaryButton>
                                    ))}
                                </div>
                            </Card.Body>
                        </Card>
                    )}

                    {/* History card */}
                    {policy.versions.length > 0 && (
                        <Card hover={false} padding="none">
                            <Card.Header>
                                <h2 className="text-sm font-semibold" style={{ color: 'var(--color-text-primary)' }}>
                                    History
                                </h2>
                            </Card.Header>
                            <Card.Body>
                                <ul className="divide-y divide-gray-100">
                                    {policy.versions.slice(0, 10).map((entry) => (
                                        <li key={entry.id} className="py-2.5 first:pt-0 last:pb-0">
                                            <div className="flex items-center gap-2 flex-wrap">
                                                <span className="font-mono text-xs text-gray-500">
                                                    v{entry.version}
                                                </span>
                                                <StatusBadge
                                                    variant={stateColorVariantFromStateName(entry.state)}
                                                    label={entry.state_label}
                                                    size="sm"
                                                />
                                                <span className="text-xs ml-auto" style={{ color: 'var(--color-text-secondary)' }}>
                                                    {relativeTime(entry.transitioned_at)}
                                                </span>
                                            </div>
                                            {entry.transitioned_by_name && (
                                                <p className="text-xs mt-0.5" style={{ color: 'var(--color-text-secondary)' }}>
                                                    by {entry.transitioned_by_name}
                                                </p>
                                            )}
                                            {entry.transition_note && (
                                                <p className="text-xs mt-0.5 italic" style={{ color: 'var(--color-text-secondary)' }}>
                                                    {entry.transition_note}
                                                </p>
                                            )}
                                        </li>
                                    ))}
                                </ul>
                            </Card.Body>
                        </Card>
                    )}
                </div>
            </div>

            {/* Transition modal */}
            {activeTransition && (
                <TransitionModal
                    transition={activeTransition}
                    policyStateLabel={policy.state_label}
                    onClose={() => setActiveTransition(null)}
                    onConfirm={handleTransitionConfirm}
                />
            )}

            {/* Delete confirmation */}
            <ConfirmDialog
                show={showDeleteDialog}
                onClose={() => setShowDeleteDialog(false)}
                onConfirm={handleDelete}
                variant="danger"
                title={`Delete policy ${policy.reference}?`}
                message={`Delete policy ${policy.reference}? This action is permanent.`}
                confirmLabel="Delete Policy"
                isLoading={deleting}
            />
        </AuthenticatedLayout>
    );
}

function stateColorVariantFromStateName(state: string): StatusVariant {
    const map: Record<string, StatusVariant> = {
        draft:        'draft',
        in_review:    'info',
        approved:     'completed',
        published:    'low',
        in_force:     'low',
        under_review: 'high',
        superseded:   'draft',
    };
    return map[state] ?? 'draft';
}
