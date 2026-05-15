import { useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import PageHeader from '@/Components/PageHeader';
import Card from '@/Components/Card';
import StatusBadge from '@/Components/StatusBadge';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import DangerButton from '@/Components/DangerButton';
import Modal from '@/Components/Modal';
import ConfirmDialog from '@/Components/ConfirmDialog';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import Textarea from '@/Components/Textarea';
import Select from '@/Components/Select';
import InputError from '@/Components/InputError';
import {
    PencilIcon,
    TrashIcon,
    PlusIcon,
    PaperClipIcon,
    BellAlertIcon,
    CurrencyDollarIcon,
    CheckCircleIcon,
    ClockIcon,
} from '@heroicons/react/24/outline';

// ---- types ----

type Severity = 'critical' | 'high' | 'medium' | 'low';
type StatusVariant =
    | 'critical'
    | 'high'
    | 'medium'
    | 'low'
    | 'info'
    | 'completed'
    | 'draft'
    | 'overdue'
    | 'ai';

interface IncidentFull {
    id: number;
    code: string;
    title: string;
    description: string | null;
    root_cause: string | null;
    lessons_learned: string | null;
    category: string;
    severity: Severity;
    status: string;
    status_label: string;
    status_color: string;
    detected_at: string;
    occurred_at: string | null;
    closed_at: string | null;
    is_data_breach: boolean;
    is_cyber_incident: boolean;
    affects_customers: boolean;
    affected_customer_count: number | null;
    financial_impact: string | null;
    currency: string | null;
    basel_category: string | null;
    basel_category_label: string | null;
    notifications_sent_count: number;
    assigned_to: { id: number; name: string } | null;
    reported_by: { id: number; name: string } | null;
    linked_risk: { id: number; code: string; title: string } | null;
    linked_control: { id: number; code: string; title: string } | null;
    linked_policy: { id: number; code: string; title: string } | null;
}

interface ActionRow {
    id: number;
    type: 'corrective' | 'preventive';
    title: string;
    description: string | null;
    owner_name: string | null;
    due_at: string | null;
    completed_at: string | null;
    completed_by_name: string | null;
}

interface NotificationRow {
    id: number;
    regulator: string;
    regulator_label: string;
    deadline: string;
    hours_remaining: number;
    status: 'pending' | 'submitted' | 'overdue';
    notification_reference: string | null;
    submitted_at: string | null;
}

interface EvidenceRow {
    id: number;
    type: string;
    type_label: string;
    title: string;
    description: string | null;
    file_path: string | null;
    uploaded_by_name: string | null;
    uploaded_at: string;
}

interface LossEvent {
    id: number;
    gross_loss: string;
    recovery_amount: string | null;
    net_loss: string;
    currency: string;
    event_date: string;
    recognized_date: string | null;
}

interface Props {
    incident: IncidentFull;
    actions: ActionRow[];
    notifications: NotificationRow[];
    evidence: EvidenceRow[];
    loss_event: LossEvent | null;
    available_transitions: Array<{ value: string; label: string }>;
    can: {
        update: boolean;
        delete: boolean;
        transition: boolean;
        notify: boolean;
        attach_evidence: boolean;
        close: boolean;
        record_loss: boolean;
    };
    users_options?: Array<{ value: number; label: string }>;
}

// ---- helpers ----

const severityVariantMap: Record<Severity, StatusVariant> = {
    critical: 'critical',
    high: 'high',
    medium: 'medium',
    low: 'low',
};

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

function isDatePast(dateStr: string | null): boolean {
    if (!dateStr) return false;
    return new Date(dateStr).getTime() < Date.now();
}

// ---- detail item used in sidebar ----

function DetailItem({ label, children }: { label: string; children: React.ReactNode }) {
    return (
        <div className="py-2.5 border-b border-gray-100 last:border-0 flex justify-between items-start gap-4">
            <span
                className="text-xs font-medium flex-shrink-0 w-28"
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

// ---- chip ----

function Chip({ label, color }: { label: string; color: string }) {
    return (
        <span
            className={`inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold border ${color}`}
        >
            {label}
        </span>
    );
}

// ---- tab system ----

type TabKey = 'actions' | 'notifications' | 'evidence' | 'loss';

interface TabDef {
    key: TabKey;
    label: string;
    count?: number;
    visible: boolean;
}

// ---- Add Action modal ----

type ActionFormData = {
    type: 'corrective' | 'preventive';
    title: string;
    description: string;
    owner_user_id: string;
    due_at: string;
};

function AddActionModal({
    incidentId,
    usersOptions,
    onClose,
}: {
    incidentId: number;
    usersOptions: Array<{ value: number; label: string }>;
    onClose: () => void;
}) {
    const { data, setData, post, processing, errors } = useForm<ActionFormData>({
        type: 'corrective',
        title: '',
        description: '',
        owner_user_id: '',
        due_at: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('incidents.actions.store', incidentId), { onSuccess: () => onClose() });
    };

    return (
        <Modal show onClose={onClose} maxWidth="md">
            <form onSubmit={handleSubmit} noValidate>
                <div className="p-6">
                    <h2 className="text-lg font-semibold text-gray-900 mb-4">Add CAPA Action</h2>

                    {/* Type radio */}
                    <div className="mb-4">
                        <InputLabel value="Action Type" required />
                        <div className="flex gap-6 mt-2" role="radiogroup" aria-label="Action type">
                            {(['corrective', 'preventive'] as const).map((t) => (
                                <label key={t} className="flex items-center gap-2 cursor-pointer">
                                    <input
                                        type="radio"
                                        name="type"
                                        value={t}
                                        checked={data.type === t}
                                        onChange={() => setData('type', t)}
                                        className="text-primary focus:ring-primary"
                                    />
                                    <span className="text-sm capitalize">{t}</span>
                                </label>
                            ))}
                        </div>
                        <InputError message={errors.type} />
                    </div>

                    <div className="mb-4">
                        <InputLabel htmlFor="action-title" value="Title" required />
                        <TextInput
                            id="action-title"
                            value={data.title}
                            onChange={(e) => setData('title', e.target.value)}
                            hasError={!!errors.title}
                            aria-required="true"
                            aria-describedby={errors.title ? 'action-title-error' : undefined}
                            className="mt-1 w-full"
                        />
                        <InputError id="action-title-error" message={errors.title} />
                    </div>

                    <div className="mb-4">
                        <InputLabel htmlFor="action-description" value="Description" />
                        <Textarea
                            id="action-description"
                            value={data.description}
                            onChange={(e) => setData('description', e.target.value)}
                            hasError={!!errors.description}
                            rows={3}
                            className="mt-1 w-full"
                        />
                        <InputError message={errors.description} />
                    </div>

                    <div className="mb-4">
                        <InputLabel htmlFor="action-owner" value="Owner" />
                        <Select
                            id="action-owner"
                            value={data.owner_user_id}
                            onChange={(e) => setData('owner_user_id', e.target.value)}
                            hasError={!!errors.owner_user_id}
                            className="mt-1 w-full"
                        >
                            <option value="">Unassigned</option>
                            {usersOptions.map((u) => (
                                <option key={u.value} value={String(u.value)}>
                                    {u.label}
                                </option>
                            ))}
                        </Select>
                        <InputError message={errors.owner_user_id} />
                    </div>

                    <div className="mb-4">
                        <InputLabel htmlFor="action-due_at" value="Due Date" />
                        <TextInput
                            id="action-due_at"
                            type="date"
                            value={data.due_at}
                            onChange={(e) => setData('due_at', e.target.value)}
                            hasError={!!errors.due_at}
                            className="mt-1 w-full"
                        />
                        <InputError message={errors.due_at} />
                    </div>

                    <div className="flex justify-end gap-3">
                        <SecondaryButton type="button" onClick={onClose}>
                            Cancel
                        </SecondaryButton>
                        <PrimaryButton type="submit" isLoading={processing}>
                            Add Action
                        </PrimaryButton>
                    </div>
                </div>
            </form>
        </Modal>
    );
}

// ---- Attach Evidence modal ----

type EvidenceFormData = {
    type: string;
    title: string;
    description: string;
    file_path: string;
};

function AttachEvidenceModal({
    incidentId,
    onClose,
}: {
    incidentId: number;
    onClose: () => void;
}) {
    const { data, setData, post, processing, errors } = useForm<EvidenceFormData>({
        type: '',
        title: '',
        description: '',
        file_path: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('incidents.attach_evidence', incidentId), { onSuccess: () => onClose() });
    };

    return (
        <Modal show onClose={onClose} maxWidth="md">
            <form onSubmit={handleSubmit} noValidate>
                <div className="p-6">
                    <h2 className="text-lg font-semibold text-gray-900 mb-4">Attach Evidence</h2>

                    <div className="mb-4">
                        <InputLabel htmlFor="ev-type" value="Evidence Type" required />
                        <TextInput
                            id="ev-type"
                            value={data.type}
                            onChange={(e) => setData('type', e.target.value)}
                            hasError={!!errors.type}
                            aria-required="true"
                            placeholder="e.g. Screenshot, Log file, Report"
                            className="mt-1 w-full"
                        />
                        <InputError message={errors.type} />
                    </div>

                    <div className="mb-4">
                        <InputLabel htmlFor="ev-title" value="Title" required />
                        <TextInput
                            id="ev-title"
                            value={data.title}
                            onChange={(e) => setData('title', e.target.value)}
                            hasError={!!errors.title}
                            aria-required="true"
                            className="mt-1 w-full"
                        />
                        <InputError message={errors.title} />
                    </div>

                    <div className="mb-4">
                        <InputLabel htmlFor="ev-description" value="Description" />
                        <Textarea
                            id="ev-description"
                            value={data.description}
                            onChange={(e) => setData('description', e.target.value)}
                            rows={3}
                            className="mt-1 w-full"
                        />
                        <InputError message={errors.description} />
                    </div>

                    <div className="mb-4">
                        <InputLabel htmlFor="ev-file_path" value="File Path (MVP — path string)" />
                        {/* Phase-1 MVP: file upload widget deferred; accept path string only */}
                        <TextInput
                            id="ev-file_path"
                            value={data.file_path}
                            onChange={(e) => setData('file_path', e.target.value)}
                            hasError={!!errors.file_path}
                            placeholder="/storage/evidence/document.pdf"
                            className="mt-1 w-full"
                        />
                        <InputError message={errors.file_path} />
                        <p className="text-xs mt-1 text-gray-400">
                            File upload widget is deferred to Phase 2. Enter the storage path for now.
                        </p>
                    </div>

                    <div className="flex justify-end gap-3">
                        <SecondaryButton type="button" onClick={onClose}>
                            Cancel
                        </SecondaryButton>
                        <PrimaryButton type="submit" isLoading={processing}>
                            Attach
                        </PrimaryButton>
                    </div>
                </div>
            </form>
        </Modal>
    );
}

// ---- Record Submission modal ----

type SubmissionFormData = {
    notification_reference: string;
    notes: string;
};

function RecordSubmissionModal({
    notification,
    incidentId,
    onClose,
}: {
    notification: NotificationRow;
    incidentId: number;
    onClose: () => void;
}) {
    const { data, setData, post, processing, errors } = useForm<SubmissionFormData>({
        notification_reference: '',
        notes: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(
            route('incidents.record_notification', {
                incident: incidentId,
                notification: notification.id,
            }),
            { onSuccess: () => onClose() },
        );
    };

    return (
        <Modal show onClose={onClose} maxWidth="md">
            <form onSubmit={handleSubmit} noValidate>
                <div className="p-6">
                    <h2 className="text-lg font-semibold text-gray-900 mb-1">
                        Record Submission
                    </h2>
                    <p className="text-sm text-gray-500 mb-4">
                        Regulator: <strong>{notification.regulator_label}</strong>
                    </p>

                    <div className="mb-4">
                        <InputLabel
                            htmlFor="sub-reference"
                            value="Notification Reference"
                            required
                        />
                        <TextInput
                            id="sub-reference"
                            value={data.notification_reference}
                            onChange={(e) => setData('notification_reference', e.target.value)}
                            hasError={!!errors.notification_reference}
                            aria-required="true"
                            placeholder="e.g. CBN/2024/123"
                            className="mt-1 w-full"
                        />
                        <InputError message={errors.notification_reference} />
                    </div>

                    <div className="mb-4">
                        <InputLabel htmlFor="sub-notes" value="Notes" />
                        <Textarea
                            id="sub-notes"
                            value={data.notes}
                            onChange={(e) => setData('notes', e.target.value)}
                            rows={3}
                            className="mt-1 w-full"
                        />
                        <InputError message={errors.notes} />
                    </div>

                    <div className="flex justify-end gap-3">
                        <SecondaryButton type="button" onClick={onClose}>
                            Cancel
                        </SecondaryButton>
                        <PrimaryButton type="submit" isLoading={processing}>
                            Record Submission
                        </PrimaryButton>
                    </div>
                </div>
            </form>
        </Modal>
    );
}

// ---- Record Loss modal ----

type LossFormData = {
    gross_loss: string;
    recovery_amount: string;
    currency: string;
    event_date: string;
    recognized_date: string;
};

function RecordLossModal({
    incidentId,
    currency,
    onClose,
}: {
    incidentId: number;
    currency: string;
    onClose: () => void;
}) {
    const { data, setData, post, processing, errors } = useForm<LossFormData>({
        gross_loss: '',
        recovery_amount: '',
        currency: currency || 'NGN',
        event_date: '',
        recognized_date: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('incidents.record_loss', incidentId), { onSuccess: () => onClose() });
    };

    return (
        <Modal show onClose={onClose} maxWidth="md">
            <form onSubmit={handleSubmit} noValidate>
                <div className="p-6">
                    <h2 className="text-lg font-semibold text-gray-900 mb-4">Record Loss Event</h2>

                    <div className="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <InputLabel htmlFor="loss-currency" value="Currency" required />
                            <TextInput
                                id="loss-currency"
                                value={data.currency}
                                onChange={(e) =>
                                    setData('currency', e.target.value.slice(0, 3).toUpperCase())
                                }
                                hasError={!!errors.currency}
                                maxLength={3}
                                className="mt-1 w-full"
                            />
                            <InputError message={errors.currency} />
                        </div>
                        <div>
                            <InputLabel htmlFor="loss-event_date" value="Event Date" required />
                            <TextInput
                                id="loss-event_date"
                                type="date"
                                value={data.event_date}
                                onChange={(e) => setData('event_date', e.target.value)}
                                hasError={!!errors.event_date}
                                aria-required="true"
                                className="mt-1 w-full"
                            />
                            <InputError message={errors.event_date} />
                        </div>
                    </div>

                    <div className="mb-4">
                        <InputLabel htmlFor="loss-gross" value="Gross Loss" required />
                        <TextInput
                            id="loss-gross"
                            type="number"
                            min="0"
                            step="0.01"
                            value={data.gross_loss}
                            onChange={(e) => setData('gross_loss', e.target.value)}
                            hasError={!!errors.gross_loss}
                            aria-required="true"
                            placeholder="0.00"
                            className="mt-1 w-full"
                        />
                        <InputError message={errors.gross_loss} />
                    </div>

                    <div className="mb-4">
                        <InputLabel htmlFor="loss-recovery" value="Recovery Amount (optional)" />
                        <TextInput
                            id="loss-recovery"
                            type="number"
                            min="0"
                            step="0.01"
                            value={data.recovery_amount}
                            onChange={(e) => setData('recovery_amount', e.target.value)}
                            hasError={!!errors.recovery_amount}
                            placeholder="0.00"
                            className="mt-1 w-full"
                        />
                        <InputError message={errors.recovery_amount} />
                    </div>

                    <div className="mb-4">
                        <InputLabel
                            htmlFor="loss-recognized_date"
                            value="Recognised Date (optional)"
                        />
                        <TextInput
                            id="loss-recognized_date"
                            type="date"
                            value={data.recognized_date}
                            onChange={(e) => setData('recognized_date', e.target.value)}
                            hasError={!!errors.recognized_date}
                            className="mt-1 w-full"
                        />
                        <InputError message={errors.recognized_date} />
                    </div>

                    <div className="flex justify-end gap-3">
                        <SecondaryButton type="button" onClick={onClose}>
                            Cancel
                        </SecondaryButton>
                        <PrimaryButton type="submit" isLoading={processing}>
                            Record Loss
                        </PrimaryButton>
                    </div>
                </div>
            </form>
        </Modal>
    );
}

// ---- CAPA tab content ----

function ActionsTab({
    actions,
    canUpdate,
    incidentId,
    usersOptions,
}: {
    actions: ActionRow[];
    canUpdate: boolean;
    incidentId: number;
    usersOptions: Array<{ value: number; label: string }>;
}) {
    const [showAddModal, setShowAddModal] = useState(false);

    const handleMarkComplete = (actionId: number) => {
        router.post(route('incidents.actions.complete', { incident: incidentId, action: actionId }));
    };

    return (
        <div>
            {canUpdate && (
                <div className="flex justify-end mb-4">
                    <PrimaryButton type="button" onClick={() => setShowAddModal(true)}>
                        <PlusIcon aria-hidden className="w-4 h-4" />
                        Add Action
                    </PrimaryButton>
                </div>
            )}

            {actions.length === 0 ? (
                <p className="text-sm text-gray-400 text-center py-8">No CAPA actions recorded.</p>
            ) : (
                <ul className="divide-y divide-gray-100">
                    {actions.map((action) => {
                        const isOverdue =
                            !action.completed_at && isDatePast(action.due_at);
                        return (
                            <li key={action.id} className="py-4 first:pt-0 last:pb-0">
                                <div className="flex items-start justify-between gap-4">
                                    <div className="flex-1 min-w-0">
                                        <div className="flex items-center gap-2 mb-1">
                                            <StatusBadge
                                                variant={
                                                    action.type === 'corrective' ? 'high' : 'info'
                                                }
                                                label={
                                                    action.type.charAt(0).toUpperCase() +
                                                    action.type.slice(1)
                                                }
                                                size="sm"
                                            />
                                            <span
                                                className="text-sm font-medium"
                                                style={{ color: 'var(--color-text-primary)' }}
                                            >
                                                {action.title}
                                            </span>
                                        </div>
                                        {action.description && (
                                            <p
                                                className="text-xs mt-0.5"
                                                style={{ color: 'var(--color-text-secondary)' }}
                                            >
                                                {action.description}
                                            </p>
                                        )}
                                        <div className="flex flex-wrap gap-3 mt-2 text-xs" style={{ color: 'var(--color-text-secondary)' }}>
                                            {action.owner_name && (
                                                <span>Owner: {action.owner_name}</span>
                                            )}
                                            {action.due_at && (
                                                <span
                                                    className={isOverdue ? 'text-red-600 font-medium' : ''}
                                                >
                                                    Due: {formatDate(action.due_at)}
                                                    {isOverdue && ' (overdue)'}
                                                </span>
                                            )}
                                            {action.completed_at && (
                                                <span className="text-green-600">
                                                    <CheckCircleIcon
                                                        aria-hidden
                                                        className="w-3.5 h-3.5 inline mr-0.5"
                                                    />
                                                    Completed {formatDate(action.completed_at)}
                                                    {action.completed_by_name &&
                                                        ` by ${action.completed_by_name}`}
                                                </span>
                                            )}
                                        </div>
                                    </div>
                                    {!action.completed_at && canUpdate && (
                                        <SecondaryButton
                                            type="button"
                                            className="text-xs shrink-0"
                                            onClick={() => handleMarkComplete(action.id)}
                                        >
                                            Mark Completed
                                        </SecondaryButton>
                                    )}
                                </div>
                            </li>
                        );
                    })}
                </ul>
            )}

            {showAddModal && (
                <AddActionModal
                    incidentId={incidentId}
                    usersOptions={usersOptions}
                    onClose={() => setShowAddModal(false)}
                />
            )}
        </div>
    );
}

// ---- Notifications tab ----

function NotificationStatusVariant(n: NotificationRow): StatusVariant {
    if (n.status === 'submitted') return 'completed';
    if (n.status === 'overdue') return 'overdue';
    if (n.hours_remaining < 2) return 'high'; // red-ish for very close
    if (n.hours_remaining < 24) return 'medium'; // yellow for approaching
    return 'info';
}

function NotificationsTab({
    notifications,
    canNotify,
    incidentId,
}: {
    notifications: NotificationRow[];
    canNotify: boolean;
    incidentId: number;
}) {
    const [submittingNotification, setSubmittingNotification] =
        useState<NotificationRow | null>(null);

    return (
        <div>
            {notifications.length === 0 ? (
                <p className="text-sm text-gray-400 text-center py-8">
                    No regulatory notifications required.
                </p>
            ) : (
                <ul className="divide-y divide-gray-100">
                    {notifications.map((n) => (
                        <li key={n.id} className="py-4 first:pt-0 last:pb-0">
                            <div className="flex items-start justify-between gap-4">
                                <div className="flex-1 min-w-0">
                                    <div className="flex items-center gap-2 mb-1">
                                        <StatusBadge
                                            variant={NotificationStatusVariant(n)}
                                            label={n.regulator_label}
                                        />
                                        {n.status === 'submitted' && n.notification_reference && (
                                            <span
                                                className="text-xs font-mono"
                                                style={{ color: 'var(--color-text-secondary)' }}
                                            >
                                                Ref: {n.notification_reference}
                                            </span>
                                        )}
                                    </div>
                                    <div className="flex flex-wrap gap-4 text-xs mt-1" style={{ color: 'var(--color-text-secondary)' }}>
                                        <span>
                                            <ClockIcon aria-hidden className="w-3.5 h-3.5 inline mr-0.5" />
                                            Deadline: {formatDateTime(n.deadline)}
                                        </span>
                                        {n.status !== 'submitted' && (
                                            <span
                                                className={
                                                    n.status === 'overdue'
                                                        ? 'text-red-600 font-medium'
                                                        : n.hours_remaining < 2
                                                        ? 'text-red-600 font-medium'
                                                        : n.hours_remaining < 24
                                                        ? 'text-yellow-600 font-medium'
                                                        : ''
                                                }
                                            >
                                                {n.status === 'overdue'
                                                    ? 'OVERDUE'
                                                    : `${n.hours_remaining}h remaining`}
                                            </span>
                                        )}
                                        {n.submitted_at && (
                                            <span className="text-green-600">
                                                Submitted {formatDateTime(n.submitted_at)}
                                            </span>
                                        )}
                                    </div>
                                </div>
                                {n.status !== 'submitted' && canNotify && (
                                    <SecondaryButton
                                        type="button"
                                        className="text-xs shrink-0"
                                        onClick={() => setSubmittingNotification(n)}
                                    >
                                        Record Submission
                                    </SecondaryButton>
                                )}
                            </div>
                        </li>
                    ))}
                </ul>
            )}

            {submittingNotification && (
                <RecordSubmissionModal
                    notification={submittingNotification}
                    incidentId={incidentId}
                    onClose={() => setSubmittingNotification(null)}
                />
            )}
        </div>
    );
}

// ---- Evidence tab ----

function EvidenceTab({
    evidence,
    canAttach,
    incidentId,
}: {
    evidence: EvidenceRow[];
    canAttach: boolean;
    incidentId: number;
}) {
    const [showModal, setShowModal] = useState(false);

    return (
        <div>
            {canAttach && (
                <div className="flex justify-end mb-4">
                    <PrimaryButton type="button" onClick={() => setShowModal(true)}>
                        <PaperClipIcon aria-hidden className="w-4 h-4" />
                        Attach Evidence
                    </PrimaryButton>
                </div>
            )}

            {evidence.length === 0 ? (
                <p className="text-sm text-gray-400 text-center py-8">No evidence attached.</p>
            ) : (
                <ul className="divide-y divide-gray-100">
                    {evidence.map((ev) => (
                        <li key={ev.id} className="py-3 first:pt-0 last:pb-0">
                            <div className="flex items-start gap-3">
                                <PaperClipIcon
                                    aria-hidden
                                    className="w-4 h-4 flex-shrink-0 mt-0.5 text-gray-400"
                                />
                                <div className="flex-1 min-w-0">
                                    <div className="flex items-center gap-2">
                                        <span
                                            className="text-sm font-medium"
                                            style={{ color: 'var(--color-text-primary)' }}
                                        >
                                            {ev.title}
                                        </span>
                                        <span
                                            className="text-xs px-1.5 py-0.5 rounded bg-gray-100 text-gray-600"
                                        >
                                            {ev.type_label || ev.type}
                                        </span>
                                    </div>
                                    {ev.description && (
                                        <p
                                            className="text-xs mt-0.5"
                                            style={{ color: 'var(--color-text-secondary)' }}
                                        >
                                            {ev.description}
                                        </p>
                                    )}
                                    <p
                                        className="text-xs mt-1"
                                        style={{ color: 'var(--color-text-secondary)' }}
                                    >
                                        {ev.uploaded_by_name && `Uploaded by ${ev.uploaded_by_name} · `}
                                        {formatDate(ev.uploaded_at)}
                                    </p>
                                    {ev.file_path && (
                                        <p className="text-xs mt-0.5 font-mono text-gray-400 truncate">
                                            {ev.file_path}
                                        </p>
                                    )}
                                </div>
                            </div>
                        </li>
                    ))}
                </ul>
            )}

            {showModal && (
                <AttachEvidenceModal
                    incidentId={incidentId}
                    onClose={() => setShowModal(false)}
                />
            )}
        </div>
    );
}

// ---- Loss tab ----

function LossTab({
    lossEvent,
    canRecordLoss,
    incidentId,
    currency,
}: {
    lossEvent: LossEvent | null;
    canRecordLoss: boolean;
    incidentId: number;
    currency: string;
}) {
    const [showModal, setShowModal] = useState(false);

    return (
        <div>
            {lossEvent ? (
                <div className="space-y-3">
                    <div className="grid grid-cols-2 gap-4">
                        <div className="rounded-lg border border-gray-100 bg-gray-50 p-4">
                            <p className="text-xs text-gray-500 mb-1">Gross Loss</p>
                            <p className="text-xl font-bold" style={{ color: 'var(--color-text-primary)' }}>
                                {lossEvent.gross_loss}
                            </p>
                        </div>
                        <div className="rounded-lg border border-gray-100 bg-gray-50 p-4">
                            <p className="text-xs text-gray-500 mb-1">Net Loss</p>
                            <p className="text-xl font-bold text-red-600">{lossEvent.net_loss}</p>
                        </div>
                    </div>
                    {lossEvent.recovery_amount && (
                        <div className="text-sm" style={{ color: 'var(--color-text-secondary)' }}>
                            Recovery: {lossEvent.recovery_amount}
                        </div>
                    )}
                    <div className="text-xs" style={{ color: 'var(--color-text-secondary)' }}>
                        Event date: {formatDate(lossEvent.event_date)}
                        {lossEvent.recognized_date &&
                            ` · Recognised: ${formatDate(lossEvent.recognized_date)}`}
                    </div>
                </div>
            ) : canRecordLoss ? (
                <div className="text-center py-8">
                    <p className="text-sm text-gray-500 mb-4">No operational loss recorded.</p>
                    <PrimaryButton type="button" onClick={() => setShowModal(true)}>
                        <CurrencyDollarIcon aria-hidden className="w-4 h-4" />
                        Record Loss
                    </PrimaryButton>
                </div>
            ) : (
                <p className="text-sm text-gray-400 text-center py-8">No loss event recorded.</p>
            )}

            {showModal && (
                <RecordLossModal
                    incidentId={incidentId}
                    currency={currency}
                    onClose={() => setShowModal(false)}
                />
            )}
        </div>
    );
}

// ---- main component ----

export default function IncidentsShow({
    incident,
    actions,
    notifications,
    evidence,
    loss_event,
    available_transitions,
    can,
    users_options = [],
}: Props) {
    const [activeTab, setActiveTab] = useState<TabKey>('actions');
    const [showDeleteConfirm, setShowDeleteConfirm] = useState(false);
    const [transitionValue, setTransitionValue] = useState('');

    const { delete: destroy, processing: deletingIncident } = useForm({});
    const { post: postTransition, processing: transitioning } = useForm({ to: '' });

    const allTabs: TabDef[] = [
        { key: 'actions', label: 'CAPA Actions', count: actions.length, visible: true },
        {
            key: 'notifications',
            label: 'Regulator Notifications',
            count: notifications.length,
            visible: true,
        },
        { key: 'evidence', label: 'Evidence', count: evidence.length, visible: true },
        {
            key: 'loss',
            label: 'Operational Loss',
            count: loss_event ? 1 : 0,
            visible: loss_event !== null || can.record_loss,
        },
    ];
    const tabs = allTabs.filter((t) => t.visible);

    const handleDelete = () => {
        destroy(route('incidents.destroy', incident.id), {
            onSuccess: () => router.visit(route('incidents.index')),
        });
    };

    const handleTransition = () => {
        if (!transitionValue) return;
        const isClose = transitionValue === 'closed';
        if (isClose && evidence.length === 0) return; // guarded in UI
        postTransition(route('incidents.transition', incident.id), {
            data: { to: transitionValue },
        } as Parameters<typeof postTransition>[1]);
    };

    const closeTransitionGuarded =
        transitionValue === 'closed' && evidence.length === 0;

    return (
        <AuthenticatedLayout>
            <Head title={`${incident.code} — ${incident.title}`} />

            <PageHeader
                title={incident.code}
                subtitle={incident.title}
                breadcrumb={[
                    { label: 'Incidents', href: route('incidents.index') },
                    { label: incident.code },
                ]}
                actions={
                    <div className="flex items-center gap-2 flex-wrap">
                        {can.update && (
                            <Link href={route('incidents.edit', incident.id)}>
                                <SecondaryButton type="button">
                                    <PencilIcon aria-hidden className="w-4 h-4" />
                                    Edit
                                </SecondaryButton>
                            </Link>
                        )}
                        {can.delete && (
                            <DangerButton
                                type="button"
                                onClick={() => setShowDeleteConfirm(true)}
                            >
                                <TrashIcon aria-hidden className="w-4 h-4" />
                                Delete
                            </DangerButton>
                        )}
                    </div>
                }
            />

            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                {/* ── Left column (2/3) ── */}
                <div className="lg:col-span-2 space-y-6">
                    {/* Description card */}
                    {(incident.description || incident.root_cause || incident.lessons_learned) && (
                        <Card hover={false} padding="none">
                            <Card.Header>
                                <h2
                                    className="text-sm font-semibold"
                                    style={{ color: 'var(--color-text-primary)' }}
                                >
                                    Description
                                </h2>
                            </Card.Header>
                            <Card.Body>
                                <div className="space-y-4">
                                    {incident.description && (
                                        <div>
                                            {/* Safe text rendering — whitespace-pre-wrap only, never dangerouslySetInnerHTML */}
                                            <p
                                                className="text-sm whitespace-pre-wrap leading-relaxed"
                                                style={{ color: 'var(--color-text-primary)' }}
                                            >
                                                {incident.description}
                                            </p>
                                        </div>
                                    )}
                                    {incident.root_cause && (
                                        <div>
                                            <p
                                                className="text-xs font-semibold uppercase tracking-wider mb-1"
                                                style={{ color: 'var(--color-text-secondary)' }}
                                            >
                                                Root Cause
                                            </p>
                                            <p
                                                className="text-sm whitespace-pre-wrap leading-relaxed"
                                                style={{ color: 'var(--color-text-primary)' }}
                                            >
                                                {incident.root_cause}
                                            </p>
                                        </div>
                                    )}
                                    {incident.lessons_learned && (
                                        <div>
                                            <p
                                                className="text-xs font-semibold uppercase tracking-wider mb-1"
                                                style={{ color: 'var(--color-text-secondary)' }}
                                            >
                                                Lessons Learned
                                            </p>
                                            <p
                                                className="text-sm whitespace-pre-wrap leading-relaxed"
                                                style={{ color: 'var(--color-text-primary)' }}
                                            >
                                                {incident.lessons_learned}
                                            </p>
                                        </div>
                                    )}
                                </div>
                            </Card.Body>
                        </Card>
                    )}

                    {/* Tabbed section */}
                    <Card hover={false} padding="none">
                        {/* Tab bar */}
                        <div className="border-b border-gray-100 px-6">
                            <nav
                                className="flex gap-0 -mb-px"
                                aria-label="Incident details tabs"
                                role="tablist"
                            >
                                {tabs.map((tab) => {
                                    const isActive = activeTab === tab.key;
                                    return (
                                        <button
                                            key={tab.key}
                                            type="button"
                                            role="tab"
                                            id={`tab-${tab.key}`}
                                            aria-selected={isActive}
                                            aria-controls={`panel-${tab.key}`}
                                            onClick={() => setActiveTab(tab.key)}
                                            className={[
                                                'flex items-center gap-1.5 px-4 py-3 text-sm font-medium border-b-2 transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-primary',
                                                isActive
                                                    ? 'border-primary text-primary'
                                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                                            ].join(' ')}
                                        >
                                            {tab.label}
                                            {tab.count !== undefined && tab.count > 0 && (
                                                <span
                                                    className={`text-[10px] font-semibold px-1.5 py-0.5 rounded-full ${
                                                        isActive
                                                            ? 'bg-primary text-white'
                                                            : 'bg-gray-100 text-gray-600'
                                                    }`}
                                                >
                                                    {tab.count}
                                                </span>
                                            )}
                                        </button>
                                    );
                                })}
                            </nav>
                        </div>

                        {/* Tab panels */}
                        <Card.Body>
                            <div
                                role="tabpanel"
                                id={`panel-actions`}
                                aria-labelledby={`tab-actions`}
                                hidden={activeTab !== 'actions'}
                            >
                                {activeTab === 'actions' && (
                                    <ActionsTab
                                        actions={actions}
                                        canUpdate={can.update}
                                        incidentId={incident.id}
                                        usersOptions={users_options}
                                    />
                                )}
                            </div>

                            <div
                                role="tabpanel"
                                id={`panel-notifications`}
                                aria-labelledby={`tab-notifications`}
                                hidden={activeTab !== 'notifications'}
                            >
                                {activeTab === 'notifications' && (
                                    <NotificationsTab
                                        notifications={notifications}
                                        canNotify={can.notify}
                                        incidentId={incident.id}
                                    />
                                )}
                            </div>

                            <div
                                role="tabpanel"
                                id={`panel-evidence`}
                                aria-labelledby={`tab-evidence`}
                                hidden={activeTab !== 'evidence'}
                            >
                                {activeTab === 'evidence' && (
                                    <EvidenceTab
                                        evidence={evidence}
                                        canAttach={can.attach_evidence}
                                        incidentId={incident.id}
                                    />
                                )}
                            </div>

                            {tabs.some((t) => t.key === 'loss') && (
                                <div
                                    role="tabpanel"
                                    id={`panel-loss`}
                                    aria-labelledby={`tab-loss`}
                                    hidden={activeTab !== 'loss'}
                                >
                                    {activeTab === 'loss' && (
                                        <LossTab
                                            lossEvent={loss_event}
                                            canRecordLoss={can.record_loss}
                                            incidentId={incident.id}
                                            currency={incident.currency ?? 'NGN'}
                                        />
                                    )}
                                </div>
                            )}
                        </Card.Body>
                    </Card>
                </div>

                {/* ── Right column (1/3) — sidebar metadata ── */}
                <div className="space-y-6">
                    {/* Status + transition */}
                    <Card hover={false} padding="none">
                        <Card.Header>
                            <h2
                                className="text-sm font-semibold"
                                style={{ color: 'var(--color-text-primary)' }}
                            >
                                Status
                            </h2>
                        </Card.Header>
                        <Card.Body>
                            <div className="flex items-center gap-2 mb-4 flex-wrap">
                                <StatusBadge
                                    variant={statusColorToVariant(incident.status_color)}
                                    label={incident.status_label}
                                />
                                <StatusBadge
                                    variant={severityVariantMap[incident.severity]}
                                    label={
                                        incident.severity.charAt(0).toUpperCase() +
                                        incident.severity.slice(1)
                                    }
                                />
                            </div>

                            {can.transition && available_transitions.length > 0 && (
                                <div className="space-y-2">
                                    <InputLabel htmlFor="transition-select" value="Transition to" />
                                    <Select
                                        id="transition-select"
                                        value={transitionValue}
                                        onChange={(e) => setTransitionValue(e.target.value)}
                                        className="w-full"
                                        aria-describedby={
                                            closeTransitionGuarded
                                                ? 'transition-close-warning'
                                                : undefined
                                        }
                                    >
                                        <option value="">Select next state…</option>
                                        {available_transitions.map((t) => (
                                            <option key={t.value} value={t.value}>
                                                {t.label}
                                            </option>
                                        ))}
                                    </Select>
                                    {closeTransitionGuarded && (
                                        <p
                                            id="transition-close-warning"
                                            role="alert"
                                            className="text-xs text-red-600"
                                        >
                                            At least one piece of evidence must be attached before
                                            closing this incident.
                                        </p>
                                    )}
                                    <PrimaryButton
                                        type="button"
                                        className="w-full justify-center"
                                        disabled={
                                            !transitionValue ||
                                            closeTransitionGuarded ||
                                            transitioning
                                        }
                                        isLoading={transitioning}
                                        onClick={handleTransition}
                                    >
                                        Transition
                                    </PrimaryButton>
                                </div>
                            )}
                        </Card.Body>
                    </Card>

                    {/* Metadata */}
                    <Card hover={false} padding="none">
                        <Card.Header>
                            <h2
                                className="text-sm font-semibold"
                                style={{ color: 'var(--color-text-primary)' }}
                            >
                                Details
                            </h2>
                        </Card.Header>
                        <Card.Body>
                            <div className="divide-y divide-gray-100">
                                <DetailItem label="Category">{incident.category}</DetailItem>
                                {incident.basel_category_label && (
                                    <DetailItem label="Basel Category">
                                        {incident.basel_category_label}
                                    </DetailItem>
                                )}
                                <DetailItem label="Flags">
                                    <div className="flex flex-wrap gap-1 justify-end">
                                        {incident.is_data_breach && (
                                            <Chip
                                                label="Data Breach"
                                                color="bg-purple-50 text-purple-700 border-purple-200"
                                            />
                                        )}
                                        {incident.is_cyber_incident && (
                                            <Chip
                                                label="Cyber"
                                                color="bg-red-50 text-red-700 border-red-200"
                                            />
                                        )}
                                        {incident.affects_customers && (
                                            <Chip
                                                label="Customers"
                                                color="bg-orange-50 text-orange-700 border-orange-200"
                                            />
                                        )}
                                        {!incident.is_data_breach &&
                                            !incident.is_cyber_incident &&
                                            !incident.affects_customers && (
                                                <span className="text-gray-400">None</span>
                                            )}
                                    </div>
                                </DetailItem>
                                {incident.affects_customers &&
                                    incident.affected_customer_count !== null && (
                                        <DetailItem label="Customers Affected">
                                            {incident.affected_customer_count.toLocaleString()}
                                        </DetailItem>
                                    )}
                                <DetailItem label="Detected">
                                    {formatDateTime(incident.detected_at)}
                                </DetailItem>
                                {incident.occurred_at && (
                                    <DetailItem label="Occurred">
                                        {formatDateTime(incident.occurred_at)}
                                    </DetailItem>
                                )}
                                {incident.closed_at && (
                                    <DetailItem label="Closed">
                                        {formatDateTime(incident.closed_at)}
                                    </DetailItem>
                                )}
                                <DetailItem label="Reported by">
                                    {incident.reported_by?.name ?? '—'}
                                </DetailItem>
                                <DetailItem label="Assigned to">
                                    {incident.assigned_to?.name ?? '—'}
                                </DetailItem>
                                {incident.financial_impact && (
                                    <DetailItem label="Financial Impact">
                                        <span className="font-semibold">
                                            {incident.financial_impact}
                                        </span>
                                    </DetailItem>
                                )}
                            </div>
                        </Card.Body>
                    </Card>

                    {/* Linked items */}
                    {(incident.linked_risk ||
                        incident.linked_control ||
                        incident.linked_policy) && (
                        <Card hover={false} padding="none">
                            <Card.Header>
                                <h2
                                    className="text-sm font-semibold"
                                    style={{ color: 'var(--color-text-primary)' }}
                                >
                                    Linked Items
                                </h2>
                            </Card.Header>
                            <Card.Body>
                                <div className="flex flex-col gap-2">
                                    {incident.linked_risk && (
                                        <Link
                                            href={route('risks.show', incident.linked_risk.id)}
                                            className="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-orange-200 bg-orange-50 text-sm text-orange-700 hover:bg-orange-100 transition-colors focus:outline-none focus:ring-2 focus:ring-primary"
                                        >
                                            <span className="font-mono text-xs">
                                                {incident.linked_risk.code}
                                            </span>
                                            <span className="text-xs truncate">
                                                {incident.linked_risk.title}
                                            </span>
                                        </Link>
                                    )}
                                    {incident.linked_control && (
                                        <Link
                                            href={route('controls.show', incident.linked_control.id)}
                                            className="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-blue-200 bg-blue-50 text-sm text-blue-700 hover:bg-blue-100 transition-colors focus:outline-none focus:ring-2 focus:ring-primary"
                                        >
                                            <span className="font-mono text-xs">
                                                {incident.linked_control.code}
                                            </span>
                                            <span className="text-xs truncate">
                                                {incident.linked_control.title}
                                            </span>
                                        </Link>
                                    )}
                                    {incident.linked_policy && (
                                        <Link
                                            href={route('policies.show', incident.linked_policy.id)}
                                            className="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-green-200 bg-green-50 text-sm text-green-700 hover:bg-green-100 transition-colors focus:outline-none focus:ring-2 focus:ring-primary"
                                        >
                                            <span className="font-mono text-xs">
                                                {incident.linked_policy.code}
                                            </span>
                                            <span className="text-xs truncate">
                                                {incident.linked_policy.title}
                                            </span>
                                        </Link>
                                    )}
                                </div>
                            </Card.Body>
                        </Card>
                    )}
                </div>
            </div>

            {/* Delete confirmation */}
            <ConfirmDialog
                show={showDeleteConfirm}
                onClose={() => setShowDeleteConfirm(false)}
                onConfirm={handleDelete}
                variant="danger"
                title="Delete Incident"
                message={`Are you sure you want to permanently delete incident ${incident.code}? This action cannot be undone.`}
                confirmLabel="Delete"
                isLoading={deletingIncident}
            />
        </AuthenticatedLayout>
    );
}
