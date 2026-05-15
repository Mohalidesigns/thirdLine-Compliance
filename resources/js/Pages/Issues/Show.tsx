import { useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import PageHeader from '@/Components/PageHeader';
import Card from '@/Components/Card';
import StatusBadge from '@/Components/StatusBadge';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import Modal from '@/Components/Modal';
import InputLabel from '@/Components/InputLabel';
import Select from '@/Components/Select';
import Textarea from '@/Components/Textarea';
import InputError from '@/Components/InputError';

interface Issue {
    id: number;
    reference: string;
    title: string;
    severity: 'low' | 'medium' | 'high' | 'critical';
    status: 'open' | 'in_progress' | 'resolved' | 'closed' | 'dismissed';
    source_type: string;
    due_date: string | null;
    owner_team: string | null;
    created_at: string;
    description: string | null;
    resolution_notes: string | null;
    resolved_at: string | null;
    linked_control: { id: number; reference: string; title: string } | null;
    linked_risk: { id: number; reference: string; title: string } | null;
}

interface Props {
    issue: Issue;
    can: { update_status: boolean };
}

type StatusVariant = 'critical' | 'high' | 'medium' | 'low' | 'info' | 'completed' | 'draft' | 'overdue' | 'ai';

function statusToVariant(status: string): StatusVariant {
    const map: Record<string, StatusVariant> = {
        open:       'high',
        in_progress:'info',
        resolved:   'low',
        closed:     'completed',
        dismissed:  'draft',
    };
    return map[status] ?? 'draft';
}

function formatDate(dateStr: string | null): string {
    if (!dateStr) return '—';
    const d = new Date(dateStr);
    if (isNaN(d.getTime())) return '—';
    return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}

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

type UpdateFormData = {
    status: string;
    resolution_notes: string;
};

const statusOptions = [
    { value: 'open',        label: 'Open' },
    { value: 'in_progress', label: 'In Progress' },
    { value: 'resolved',    label: 'Resolved' },
    { value: 'closed',      label: 'Closed' },
    { value: 'dismissed',   label: 'Dismissed' },
];

export default function IssuesShow({ issue, can }: Props) {
    const [showUpdateModal, setShowUpdateModal] = useState(false);

    const { data, setData, patch, processing, errors } = useForm<UpdateFormData>({
        status:           issue.status,
        resolution_notes: issue.resolution_notes ?? '',
    });

    const handleUpdate = (e: React.FormEvent) => {
        e.preventDefault();
        patch(route('issues.update', issue.id), {
            onSuccess: () => setShowUpdateModal(false),
        });
    };

    return (
        <AuthenticatedLayout>
            <Head title={`${issue.reference} — ${issue.title}`} />

            <PageHeader
                title={`${issue.reference}`}
                subtitle={issue.title}
                breadcrumb={[
                    { label: 'Issues', href: route('issues.index') },
                    { label: issue.reference },
                ]}
                actions={
                    can.update_status ? (
                        <PrimaryButton type="button" onClick={() => setShowUpdateModal(true)}>
                            Update Status
                        </PrimaryButton>
                    ) : undefined
                }
            />

            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div className="lg:col-span-2 space-y-6">
                    <div className="rounded-xl border p-4 flex items-start gap-3 bg-gray-50 border-gray-200">
                        <div className="flex items-center gap-2 flex-wrap">
                            <StatusBadge
                                variant={issue.severity as StatusVariant}
                                label={issue.severity.charAt(0).toUpperCase() + issue.severity.slice(1)}
                            />
                            <StatusBadge
                                variant={statusToVariant(issue.status)}
                                label={issue.status.replace('_', ' ').replace(/\b\w/g, (c) => c.toUpperCase())}
                            />
                            <span className="text-xs" style={{ color: 'var(--color-text-secondary)' }}>
                                Source: {issue.source_type}
                            </span>
                        </div>
                    </div>

                    {issue.description && (
                        <Card hover={false} padding="none">
                            <Card.Header>
                                <h2 className="text-sm font-semibold" style={{ color: 'var(--color-text-primary)' }}>
                                    Description
                                </h2>
                            </Card.Header>
                            <Card.Body>
                                <p className="text-sm whitespace-pre-wrap leading-relaxed" style={{ color: 'var(--color-text-primary)' }}>
                                    {issue.description}
                                </p>
                            </Card.Body>
                        </Card>
                    )}

                    {issue.resolution_notes && (
                        <Card hover={false} padding="none">
                            <Card.Header>
                                <h2 className="text-sm font-semibold" style={{ color: 'var(--color-text-primary)' }}>
                                    Resolution Notes
                                </h2>
                            </Card.Header>
                            <Card.Body>
                                <p className="text-sm whitespace-pre-wrap leading-relaxed italic" style={{ color: 'var(--color-text-secondary)' }}>
                                    {issue.resolution_notes}
                                </p>
                                {issue.resolved_at && (
                                    <p className="text-xs mt-2" style={{ color: 'var(--color-text-secondary)' }}>
                                        Resolved on {formatDate(issue.resolved_at)}
                                    </p>
                                )}
                            </Card.Body>
                        </Card>
                    )}

                    {(issue.linked_control || issue.linked_risk) && (
                        <Card hover={false} padding="none">
                            <Card.Header>
                                <h2 className="text-sm font-semibold" style={{ color: 'var(--color-text-primary)' }}>
                                    Linked Items
                                </h2>
                            </Card.Header>
                            <Card.Body>
                                <div className="flex flex-wrap gap-3">
                                    {issue.linked_control && (
                                        <Link
                                            href={route('controls.show', issue.linked_control.id)}
                                            className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-blue-200 bg-blue-50 text-sm text-blue-700 hover:bg-blue-100 transition-colors focus:outline-none focus:ring-2 focus:ring-primary"
                                        >
                                            <span className="font-mono text-xs">{issue.linked_control.reference}</span>
                                            <span className="text-xs truncate max-w-[200px]">{issue.linked_control.title}</span>
                                        </Link>
                                    )}
                                    {issue.linked_risk && (
                                        <Link
                                            href={route('risks.show', issue.linked_risk.id)}
                                            className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-orange-200 bg-orange-50 text-sm text-orange-700 hover:bg-orange-100 transition-colors focus:outline-none focus:ring-2 focus:ring-primary"
                                        >
                                            <span className="font-mono text-xs">{issue.linked_risk.reference}</span>
                                            <span className="text-xs truncate max-w-[200px]">{issue.linked_risk.title}</span>
                                        </Link>
                                    )}
                                </div>
                            </Card.Body>
                        </Card>
                    )}
                </div>

                <div className="space-y-6">
                    <Card hover={false} padding="none">
                        <Card.Header>
                            <h2 className="text-sm font-semibold" style={{ color: 'var(--color-text-primary)' }}>
                                Details
                            </h2>
                        </Card.Header>
                        <Card.Body>
                            <div className="divide-y divide-gray-100">
                                <DetailItem label="Reference">
                                    <span className="font-mono">{issue.reference}</span>
                                </DetailItem>
                                <DetailItem label="Severity">
                                    <StatusBadge
                                        variant={issue.severity as StatusVariant}
                                        label={issue.severity.charAt(0).toUpperCase() + issue.severity.slice(1)}
                                        size="sm"
                                    />
                                </DetailItem>
                                <DetailItem label="Status">
                                    <StatusBadge
                                        variant={statusToVariant(issue.status)}
                                        label={issue.status.replace('_', ' ').replace(/\b\w/g, (c) => c.toUpperCase())}
                                        size="sm"
                                    />
                                </DetailItem>
                                <DetailItem label="Owner Team">{issue.owner_team ?? '—'}</DetailItem>
                                <DetailItem label="Source">{issue.source_type}</DetailItem>
                                <DetailItem label="Due Date">{formatDate(issue.due_date)}</DetailItem>
                                <DetailItem label="Created">{formatDate(issue.created_at)}</DetailItem>
                                {issue.resolved_at && (
                                    <DetailItem label="Resolved">{formatDate(issue.resolved_at)}</DetailItem>
                                )}
                            </div>
                        </Card.Body>
                    </Card>
                </div>
            </div>

            <Modal show={showUpdateModal} maxWidth="md" onClose={() => setShowUpdateModal(false)}>
                <form onSubmit={handleUpdate} noValidate>
                    <div className="p-6">
                        <h2 className="text-lg font-semibold text-gray-900 mb-4">
                            Update Issue Status
                        </h2>

                        <div className="mb-4">
                            <InputLabel htmlFor="update-status" value="Status" required />
                            <Select
                                id="update-status"
                                value={data.status}
                                onChange={(e) => setData('status', e.target.value)}
                                hasError={!!errors.status}
                                aria-required="true"
                                aria-describedby={errors.status ? 'update-status-error' : undefined}
                                className="mt-1 w-full"
                            >
                                {statusOptions.map((s) => (
                                    <option key={s.value} value={s.value}>{s.label}</option>
                                ))}
                            </Select>
                            <InputError id="update-status-error" message={errors.status} />
                        </div>

                        <div className="mb-4">
                            <InputLabel htmlFor="resolution-notes" value="Resolution Notes" />
                            <Textarea
                                id="resolution-notes"
                                value={data.resolution_notes}
                                onChange={(e) => setData('resolution_notes', e.target.value)}
                                hasError={!!errors.resolution_notes}
                                aria-describedby={errors.resolution_notes ? 'resolution-notes-error' : undefined}
                                rows={4}
                                placeholder="Describe how the issue was resolved or why it is being dismissed…"
                                className="mt-1 w-full"
                            />
                            <InputError id="resolution-notes-error" message={errors.resolution_notes} />
                        </div>

                        <div className="flex justify-end gap-3">
                            <SecondaryButton type="button" onClick={() => setShowUpdateModal(false)}>
                                Cancel
                            </SecondaryButton>
                            <PrimaryButton type="submit" isLoading={processing}>
                                Update
                            </PrimaryButton>
                        </div>
                    </div>
                </form>
            </Modal>
        </AuthenticatedLayout>
    );
}
