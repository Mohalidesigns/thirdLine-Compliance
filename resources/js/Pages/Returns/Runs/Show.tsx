import { useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import PageHeader from '@/Components/PageHeader';
import Card from '@/Components/Card';
import StatusBadge from '@/Components/StatusBadge';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import Modal from '@/Components/Modal';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import Textarea from '@/Components/Textarea';
import InputError from '@/Components/InputError';
import {
    CheckCircleIcon,
    ClockIcon,
    ExclamationTriangleIcon,
    UserCircleIcon,
    DocumentTextIcon,
} from '@heroicons/react/24/outline';

// ---- types ----

type StatusVariant = 'critical' | 'high' | 'medium' | 'low' | 'info' | 'completed' | 'draft' | 'overdue' | 'ai';

type AvailableAction = 'submit' | 'approve' | 'sign_off' | 'acknowledge';

interface RunDefinition {
    id: number;
    code: string;
    title: string;
    regulator: string;
    regulator_label: string;
    submission_channel: string;
    frequency: string;
    evidence_required: boolean;
    legal_basis: string | null;
    acts: string | null;
}

interface ApprovalStep {
    id: number;
    step: string;
    step_label: string;
    actor_name: string;
    decision: string;
    notes: string | null;
    acted_at: string;
}

interface RunFull {
    id: number;
    definition: RunDefinition;
    period_label: string;
    period_start: string;
    period_end: string;
    due_at: string;
    days_until_due: number;
    status: string;
    status_label: string;
    status_color: string;
    maker: { id: number; name: string } | null;
    checker: { id: number; name: string } | null;
    approver: { id: number; name: string } | null;
    payload_path: string | null;
    submission_reference: string | null;
    submitted_at: string | null;
    acknowledged_at: string | null;
    acknowledgement_path: string | null;
    rejection_reason: string | null;
    notes: string | null;
}

interface Props {
    run: RunFull;
    approvals: ApprovalStep[];
    available_actions: AvailableAction[];
    can: {
        submit: boolean;
        approve: boolean;
        sign_off: boolean;
        acknowledge: boolean;
    };
}

// ---- helpers ----

function statusColorToVariant(color: string): StatusVariant {
    const map: Record<string, StatusVariant> = {
        blue: 'info',
        red: 'critical',
        green: 'low',
        yellow: 'medium',
        orange: 'high',
        gray: 'draft',
        purple: 'completed',
    };
    return map[color] ?? 'draft';
}

function formatDate(dateStr: string | null): string {
    if (!dateStr) return '—';
    const d = new Date(dateStr);
    if (isNaN(d.getTime())) return '—';
    return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}

function formatDateTime(dateStr: string | null): string {
    if (!dateStr) return '—';
    const d = new Date(dateStr);
    if (isNaN(d.getTime())) return '—';
    return d.toLocaleString('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function dueBadgeClass(days: number): string {
    if (days <= 3) return 'text-red-600 font-semibold';
    if (days <= 14) return 'text-yellow-600 font-medium';
    return '';
}

// ---- Detail item ----

function DetailItem({ label, children }: { label: string; children: React.ReactNode }) {
    return (
        <div className="py-2.5 border-b border-gray-100 last:border-0 flex justify-between items-start gap-4">
            <span
                className="text-xs font-medium flex-shrink-0 w-32"
                style={{ color: 'var(--color-text-secondary)' }}
            >
                {label}
            </span>
            <span className="text-xs text-right" style={{ color: 'var(--color-text-primary)' }}>
                {children}
            </span>
        </div>
    );
}

// ---- Approval Timeline ----

const EXPECTED_STEPS = [
    { key: 'maker_submit', label: 'Maker Submit' },
    { key: 'checker_review', label: 'Checker Review' },
    { key: 'approver_sign_off', label: 'Approver Sign Off' },
];

function decisionVariant(decision: string): StatusVariant {
    if (decision === 'approved' || decision === 'submitted' || decision === 'signed_off') return 'low';
    if (decision === 'rejected') return 'critical';
    return 'info';
}

function ApprovalTimeline({
    approvals,
    currentStatus,
}: {
    approvals: ApprovalStep[];
    currentStatus: string;
}) {
    const completedStepKeys = new Set(approvals.map((a) => a.step));

    return (
        <ol aria-label="Approval timeline" className="space-y-0">
            {EXPECTED_STEPS.map((expected, idx) => {
                const completed = approvals.find((a) => a.step === expected.key);
                const isLast = idx === EXPECTED_STEPS.length - 1;

                return (
                    <li key={expected.key} className="relative flex gap-4">
                        {/* vertical connector line */}
                        {!isLast && (
                            <div
                                aria-hidden="true"
                                className={`absolute left-3.5 top-8 w-px h-full ${
                                    completedStepKeys.has(expected.key)
                                        ? 'bg-green-300'
                                        : 'bg-gray-200'
                                }`}
                            />
                        )}

                        {/* dot */}
                        <div
                            className={`relative z-10 flex-shrink-0 w-7 h-7 rounded-full flex items-center justify-center mt-0.5 ${
                                completed
                                    ? completed.decision === 'rejected'
                                        ? 'bg-red-100 text-red-600'
                                        : 'bg-green-100 text-green-600'
                                    : 'bg-gray-100 text-gray-400'
                            }`}
                            aria-hidden="true"
                        >
                            {completed ? (
                                completed.decision === 'rejected' ? (
                                    <ExclamationTriangleIcon className="w-4 h-4" />
                                ) : (
                                    <CheckCircleIcon className="w-4 h-4" />
                                )
                            ) : (
                                <ClockIcon className="w-4 h-4" />
                            )}
                        </div>

                        <div className="flex-1 pb-6">
                            {completed ? (
                                <>
                                    <div className="flex items-center gap-2 flex-wrap">
                                        <span
                                            className="text-sm font-medium"
                                            style={{ color: 'var(--color-text-primary)' }}
                                        >
                                            {completed.step_label}
                                        </span>
                                        <StatusBadge
                                            variant={decisionVariant(completed.decision)}
                                            label={completed.decision.replace(/_/g, ' ')}
                                            size="sm"
                                        />
                                    </div>
                                    <p
                                        className="text-xs mt-0.5"
                                        style={{ color: 'var(--color-text-secondary)' }}
                                    >
                                        {completed.actor_name} &middot; {formatDateTime(completed.acted_at)}
                                    </p>
                                    {completed.notes && (
                                        <p
                                            className="text-xs mt-1 italic"
                                            style={{ color: 'var(--color-text-secondary)' }}
                                        >
                                            &ldquo;{completed.notes}&rdquo;
                                        </p>
                                    )}
                                </>
                            ) : (
                                <p
                                    className="text-sm"
                                    style={{ color: 'var(--color-text-secondary)' }}
                                >
                                    Pending: {expected.label}
                                </p>
                            )}
                        </div>
                    </li>
                );
            })}
        </ol>
    );
}

// ---- Action Modals ----

type NotesFormData = { notes: string };

function SubmitModal({ runId, onClose }: { runId: number; onClose: () => void }) {
    const { data, setData, post, processing, errors } = useForm<NotesFormData>({ notes: '' });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('returns.runs.submit', runId), { onSuccess: () => onClose() });
    };

    return (
        <Modal show onClose={onClose} maxWidth="md">
            <form onSubmit={handleSubmit} noValidate>
                <div className="p-6">
                    <h2 className="text-lg font-semibold text-gray-900 mb-1">
                        Submit for Review
                    </h2>
                    <p className="text-sm text-gray-500 mb-4">
                        The return will be sent to the checker for approval.
                    </p>

                    <div className="mb-4">
                        <InputLabel htmlFor="submit-notes" value="Notes (optional)" />
                        <Textarea
                            id="submit-notes"
                            value={data.notes}
                            onChange={(e) => setData('notes', e.target.value)}
                            hasError={!!errors.notes}
                            rows={3}
                            placeholder="Add any notes for the checker…"
                            className="mt-1 w-full"
                        />
                        <InputError message={errors.notes} />
                    </div>

                    <div className="flex justify-end gap-3">
                        <SecondaryButton type="button" onClick={onClose}>
                            Cancel
                        </SecondaryButton>
                        <PrimaryButton type="submit" isLoading={processing}>
                            Submit for Review
                        </PrimaryButton>
                    </div>
                </div>
            </form>
        </Modal>
    );
}

function ApproveModal({ runId, onClose }: { runId: number; onClose: () => void }) {
    const { data, setData, post, processing, errors } = useForm<NotesFormData>({ notes: '' });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('returns.runs.approve', runId), { onSuccess: () => onClose() });
    };

    return (
        <Modal show onClose={onClose} maxWidth="md">
            <form onSubmit={handleSubmit} noValidate>
                <div className="p-6">
                    <h2 className="text-lg font-semibold text-gray-900 mb-1">
                        Approve as Checker
                    </h2>
                    <p className="text-sm text-gray-500 mb-4">
                        Confirm the return is accurate and approve for sign-off.
                    </p>

                    <div className="mb-4">
                        <InputLabel htmlFor="approve-notes" value="Notes (optional)" />
                        <Textarea
                            id="approve-notes"
                            value={data.notes}
                            onChange={(e) => setData('notes', e.target.value)}
                            hasError={!!errors.notes}
                            rows={3}
                            placeholder="Add any review notes…"
                            className="mt-1 w-full"
                        />
                        <InputError message={errors.notes} />
                    </div>

                    <div className="flex justify-end gap-3">
                        <SecondaryButton type="button" onClick={onClose}>
                            Cancel
                        </SecondaryButton>
                        <PrimaryButton type="submit" isLoading={processing}>
                            Approve
                        </PrimaryButton>
                    </div>
                </div>
            </form>
        </Modal>
    );
}

type SignOffFormData = { notes: string; manual_reference: string };

function SignOffModal({
    runId,
    submissionChannel,
    onClose,
}: {
    runId: number;
    submissionChannel: string;
    onClose: () => void;
}) {
    const isManual = submissionChannel === 'manual';
    const { data, setData, post, processing, errors } = useForm<SignOffFormData>({
        notes: '',
        manual_reference: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('returns.runs.sign_off', runId), { onSuccess: () => onClose() });
    };

    return (
        <Modal show onClose={onClose} maxWidth="md">
            <form onSubmit={handleSubmit} noValidate>
                <div className="p-6">
                    <h2 className="text-lg font-semibold text-gray-900 mb-1">
                        Sign Off &amp; Submit
                    </h2>
                    <p className="text-sm text-gray-500 mb-4">
                        Final approval and submission to the regulator.
                    </p>

                    {isManual && (
                        <div className="mb-4">
                            <InputLabel
                                htmlFor="signoff-manual-ref"
                                value="Manual Submission Reference (optional)"
                            />
                            <TextInput
                                id="signoff-manual-ref"
                                value={data.manual_reference}
                                onChange={(e) => setData('manual_reference', e.target.value)}
                                hasError={!!errors.manual_reference}
                                placeholder="e.g. CBN/2024/RET-001"
                                className="mt-1 w-full"
                            />
                            <InputError message={errors.manual_reference} />
                            <p className="text-xs mt-1 text-gray-400">
                                Enter the reference assigned during manual submission.
                            </p>
                        </div>
                    )}

                    <div className="mb-4">
                        <InputLabel htmlFor="signoff-notes" value="Notes (optional)" />
                        <Textarea
                            id="signoff-notes"
                            value={data.notes}
                            onChange={(e) => setData('notes', e.target.value)}
                            hasError={!!errors.notes}
                            rows={3}
                            placeholder="Add any sign-off notes…"
                            className="mt-1 w-full"
                        />
                        <InputError message={errors.notes} />
                    </div>

                    <div className="flex justify-end gap-3">
                        <SecondaryButton type="button" onClick={onClose}>
                            Cancel
                        </SecondaryButton>
                        <PrimaryButton type="submit" isLoading={processing}>
                            Sign Off &amp; Submit
                        </PrimaryButton>
                    </div>
                </div>
            </form>
        </Modal>
    );
}

type AcknowledgeFormData = { acknowledgement_path: string };

function AcknowledgeModal({ runId, onClose }: { runId: number; onClose: () => void }) {
    const { data, setData, post, processing, errors } = useForm<AcknowledgeFormData>({
        acknowledgement_path: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('returns.runs.acknowledge', runId), { onSuccess: () => onClose() });
    };

    return (
        <Modal show onClose={onClose} maxWidth="md">
            <form onSubmit={handleSubmit} noValidate>
                <div className="p-6">
                    <h2 className="text-lg font-semibold text-gray-900 mb-1">
                        Record Acknowledgement
                    </h2>
                    <p className="text-sm text-gray-500 mb-4">
                        Upload or link the regulator&apos;s acknowledgement document.
                    </p>

                    <div className="mb-4">
                        <InputLabel
                            htmlFor="ack-path"
                            value="Acknowledgement Path / URL"
                            required
                        />
                        <TextInput
                            id="ack-path"
                            value={data.acknowledgement_path}
                            onChange={(e) => setData('acknowledgement_path', e.target.value)}
                            hasError={!!errors.acknowledgement_path}
                            aria-required="true"
                            placeholder="/storage/returns/acknowledgements/ack-001.pdf"
                            className="mt-1 w-full"
                        />
                        <InputError message={errors.acknowledgement_path} />
                        <p className="text-xs mt-1 text-gray-400">
                            File upload widget is deferred to Phase 2. Enter the storage path for now.
                        </p>
                    </div>

                    <div className="flex justify-end gap-3">
                        <SecondaryButton type="button" onClick={onClose}>
                            Cancel
                        </SecondaryButton>
                        <PrimaryButton type="submit" isLoading={processing}>
                            Record Acknowledgement
                        </PrimaryButton>
                    </div>
                </div>
            </form>
        </Modal>
    );
}

// ---- main component ----

export default function ReturnRunShow({ run, approvals, available_actions, can }: Props) {
    type ModalType = AvailableAction | null;
    const [activeModal, setActiveModal] = useState<ModalType>(null);

    const isSubmitted = !!run.submitted_at;
    const isRejected = run.status === 'rejected';

    return (
        <AuthenticatedLayout>
            <Head title={`${run.definition.code} — ${run.definition.title}`} />

            <PageHeader
                title={run.definition.code}
                subtitle={`${run.definition.title} · ${run.period_label}`}
                breadcrumb={[
                    { label: 'Returns', href: route('returns.dashboard') },
                    { label: 'Runs', href: route('returns.runs.index') },
                    { label: run.definition.code },
                ]}
            />

            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                {/* ── Left column (2/3) ── */}
                <div className="lg:col-span-2 space-y-6">
                    {/* Header card */}
                    <Card hover={false} padding="none">
                        <Card.Header>
                            <div className="flex items-start justify-between gap-4 flex-wrap">
                                <div>
                                    <div className="flex items-center gap-2 mb-1 flex-wrap">
                                        <span className="font-mono text-sm font-semibold text-gray-700">
                                            {run.definition.code}
                                        </span>
                                        <span
                                            className="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200"
                                        >
                                            {run.definition.regulator_label}
                                        </span>
                                        <StatusBadge
                                            variant={statusColorToVariant(run.status_color)}
                                            label={run.status_label}
                                        />
                                    </div>
                                    <h2
                                        className="text-base font-semibold"
                                        style={{ color: 'var(--color-text-primary)' }}
                                    >
                                        {run.definition.title}
                                    </h2>
                                    <p
                                        className="text-sm mt-0.5"
                                        style={{ color: 'var(--color-text-secondary)' }}
                                    >
                                        {run.period_label}
                                    </p>
                                </div>
                            </div>
                        </Card.Header>
                    </Card>

                    {/* Definition details card */}
                    <Card hover={false} padding="none">
                        <Card.Header>
                            <h2
                                className="text-sm font-semibold"
                                style={{ color: 'var(--color-text-primary)' }}
                            >
                                Definition Details
                            </h2>
                        </Card.Header>
                        <Card.Body>
                            <div className="divide-y divide-gray-100">
                                <DetailItem label="Submission Channel">
                                    <span className="capitalize">{run.definition.submission_channel.replace(/_/g, ' ')}</span>
                                </DetailItem>
                                <DetailItem label="Frequency">
                                    <span className="capitalize">{run.definition.frequency.replace(/_/g, ' ')}</span>
                                </DetailItem>
                                <DetailItem label="Evidence Required">
                                    {run.definition.evidence_required ? (
                                        <span className="text-green-600 font-medium">Yes</span>
                                    ) : (
                                        <span className="text-gray-400">No</span>
                                    )}
                                </DetailItem>
                                {run.definition.legal_basis && (
                                    <DetailItem label="Legal Basis">
                                        {run.definition.legal_basis}
                                    </DetailItem>
                                )}
                                {run.definition.acts && (
                                    <DetailItem label="Enabling Acts">
                                        {run.definition.acts}
                                    </DetailItem>
                                )}
                            </div>
                        </Card.Body>
                    </Card>

                    {/* Approval timeline card */}
                    <Card hover={false} padding="none">
                        <Card.Header>
                            <h2
                                className="text-sm font-semibold"
                                style={{ color: 'var(--color-text-primary)' }}
                            >
                                Approval Timeline
                            </h2>
                        </Card.Header>
                        <Card.Body>
                            <ApprovalTimeline
                                approvals={approvals}
                                currentStatus={run.status}
                            />
                        </Card.Body>
                    </Card>

                    {/* Submission card — only once submitted */}
                    {isSubmitted && (
                        <Card hover={false} padding="none">
                            <Card.Header>
                                <h2
                                    className="text-sm font-semibold"
                                    style={{ color: 'var(--color-text-primary)' }}
                                >
                                    Submission Details
                                </h2>
                            </Card.Header>
                            <Card.Body>
                                <div className="divide-y divide-gray-100">
                                    <DetailItem label="Submitted At">
                                        {formatDateTime(run.submitted_at)}
                                    </DetailItem>
                                    {run.submission_reference && (
                                        <DetailItem label="Reference">
                                            <span className="font-mono">{run.submission_reference}</span>
                                        </DetailItem>
                                    )}
                                    {run.payload_path && (
                                        <DetailItem label="Payload File">
                                            <a
                                                href={run.payload_path}
                                                className="text-blue-600 hover:underline focus:outline-none focus:underline break-all"
                                                target="_blank"
                                                rel="noreferrer noopener"
                                            >
                                                {run.payload_path}
                                            </a>
                                        </DetailItem>
                                    )}
                                    {run.acknowledged_at && (
                                        <>
                                            <DetailItem label="Acknowledged At">
                                                {formatDateTime(run.acknowledged_at)}
                                            </DetailItem>
                                            {run.acknowledgement_path && (
                                                <DetailItem label="Acknowledgement">
                                                    <a
                                                        href={run.acknowledgement_path}
                                                        className="text-blue-600 hover:underline focus:outline-none focus:underline break-all"
                                                        target="_blank"
                                                        rel="noreferrer noopener"
                                                    >
                                                        {run.acknowledgement_path}
                                                    </a>
                                                </DetailItem>
                                            )}
                                        </>
                                    )}
                                </div>
                            </Card.Body>
                        </Card>
                    )}

                    {/* Rejection reason card */}
                    {isRejected && run.rejection_reason && (
                        <div
                            role="alert"
                            className="rounded-xl border border-red-200 bg-red-50 p-4"
                        >
                            <div className="flex items-start gap-3">
                                <ExclamationTriangleIcon
                                    aria-hidden
                                    className="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5"
                                />
                                <div>
                                    <p className="text-sm font-semibold text-red-800 mb-1">
                                        This return was rejected
                                    </p>
                                    <p className="text-sm text-red-700 whitespace-pre-wrap leading-relaxed">
                                        {run.rejection_reason}
                                    </p>
                                </div>
                            </div>
                        </div>
                    )}

                    {/* Notes card */}
                    <Card hover={false} padding="none">
                        <Card.Header>
                            <h2
                                className="text-sm font-semibold"
                                style={{ color: 'var(--color-text-primary)' }}
                            >
                                Notes
                            </h2>
                        </Card.Header>
                        <Card.Body>
                            {run.notes ? (
                                <p
                                    className="text-sm whitespace-pre-wrap leading-relaxed"
                                    style={{ color: 'var(--color-text-primary)' }}
                                >
                                    {run.notes}
                                </p>
                            ) : (
                                <p className="text-sm text-gray-400">No notes recorded.</p>
                            )}
                        </Card.Body>
                    </Card>
                </div>

                {/* ── Right column (1/3) ── */}
                <div className="space-y-6">
                    {/* Action panel */}
                    {available_actions.length > 0 && (
                        <Card hover={false} padding="none">
                            <Card.Header>
                                <h2
                                    className="text-sm font-semibold"
                                    style={{ color: 'var(--color-text-primary)' }}
                                >
                                    Actions
                                </h2>
                            </Card.Header>
                            <Card.Body>
                                <div className="space-y-3">
                                    {available_actions.includes('submit') && (
                                        <PrimaryButton
                                            type="button"
                                            disabled={!can.submit}
                                            onClick={() => setActiveModal('submit')}
                                            className="w-full justify-center"
                                        >
                                            Submit for Review
                                        </PrimaryButton>
                                    )}
                                    {available_actions.includes('approve') && (
                                        <PrimaryButton
                                            type="button"
                                            disabled={!can.approve}
                                            onClick={() => setActiveModal('approve')}
                                            className="w-full justify-center"
                                        >
                                            Approve as Checker
                                        </PrimaryButton>
                                    )}
                                    {available_actions.includes('sign_off') && (
                                        <PrimaryButton
                                            type="button"
                                            disabled={!can.sign_off}
                                            onClick={() => setActiveModal('sign_off')}
                                            className="w-full justify-center"
                                        >
                                            Sign Off &amp; Submit
                                        </PrimaryButton>
                                    )}
                                    {available_actions.includes('acknowledge') && (
                                        <SecondaryButton
                                            type="button"
                                            disabled={!can.acknowledge}
                                            onClick={() => setActiveModal('acknowledge')}
                                            className="w-full justify-center"
                                        >
                                            Record Acknowledgement
                                        </SecondaryButton>
                                    )}
                                </div>
                            </Card.Body>
                        </Card>
                    )}

                    {/* Due date countdown */}
                    <Card hover={false} padding="none">
                        <Card.Header>
                            <h2
                                className="text-sm font-semibold"
                                style={{ color: 'var(--color-text-primary)' }}
                            >
                                Deadline
                            </h2>
                        </Card.Header>
                        <Card.Body>
                            <div className="text-center py-2">
                                <p
                                    className={`text-3xl font-bold ${dueBadgeClass(run.days_until_due)}`}
                                    style={
                                        run.days_until_due > 14
                                            ? { color: 'var(--color-text-primary)' }
                                            : undefined
                                    }
                                >
                                    {run.days_until_due <= 0 ? 'Overdue' : `${run.days_until_due}d`}
                                </p>
                                <p className="text-xs text-gray-400 mt-1">
                                    {run.days_until_due <= 0
                                        ? 'Past due date'
                                        : run.days_until_due === 1
                                        ? '1 day remaining'
                                        : `${run.days_until_due} days remaining`}
                                </p>
                                <p
                                    className="text-sm mt-3 font-medium"
                                    style={{ color: 'var(--color-text-primary)' }}
                                >
                                    {formatDate(run.due_at)}
                                </p>
                            </div>

                            <div className="mt-4 divide-y divide-gray-100">
                                <DetailItem label="Period Start">{formatDate(run.period_start)}</DetailItem>
                                <DetailItem label="Period End">{formatDate(run.period_end)}</DetailItem>
                            </div>
                        </Card.Body>
                    </Card>

                    {/* Assignments */}
                    <Card hover={false} padding="none">
                        <Card.Header>
                            <h2
                                className="text-sm font-semibold"
                                style={{ color: 'var(--color-text-primary)' }}
                            >
                                Assigned Users
                            </h2>
                        </Card.Header>
                        <Card.Body>
                            <div className="space-y-3">
                                {[
                                    { role: 'Maker', user: run.maker },
                                    { role: 'Checker', user: run.checker },
                                    { role: 'Approver', user: run.approver },
                                ].map(({ role, user }) => (
                                    <div key={role} className="flex items-center gap-3">
                                        <div className="w-7 h-7 rounded-full bg-gray-100 flex items-center justify-center flex-shrink-0">
                                            <UserCircleIcon
                                                aria-hidden
                                                className="w-4 h-4 text-gray-400"
                                            />
                                        </div>
                                        <div>
                                            <p className="text-[10px] uppercase tracking-wider font-semibold text-gray-400">
                                                {role}
                                            </p>
                                            <p
                                                className="text-sm"
                                                style={{ color: 'var(--color-text-primary)' }}
                                            >
                                                {user?.name ?? (
                                                    <span className="text-gray-400 italic">
                                                        Unassigned
                                                    </span>
                                                )}
                                            </p>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </Card.Body>
                    </Card>

                    {/* View Definition link (admin only) */}
                    <Card hover={false} padding="none">
                        <Card.Body>
                            <a
                                href={`/admin/return-definitions/${run.definition.id}`}
                                className="flex items-center gap-2 text-sm text-blue-600 hover:underline focus:outline-none focus:underline"
                                target="_blank"
                                rel="noreferrer noopener"
                            >
                                <DocumentTextIcon aria-hidden className="w-4 h-4" />
                                View Definition in Admin
                            </a>
                        </Card.Body>
                    </Card>
                </div>
            </div>

            {/* Action modals */}
            {activeModal === 'submit' && (
                <SubmitModal runId={run.id} onClose={() => setActiveModal(null)} />
            )}
            {activeModal === 'approve' && (
                <ApproveModal runId={run.id} onClose={() => setActiveModal(null)} />
            )}
            {activeModal === 'sign_off' && (
                <SignOffModal
                    runId={run.id}
                    submissionChannel={run.definition.submission_channel}
                    onClose={() => setActiveModal(null)}
                />
            )}
            {activeModal === 'acknowledge' && (
                <AcknowledgeModal runId={run.id} onClose={() => setActiveModal(null)} />
            )}
        </AuthenticatedLayout>
    );
}
