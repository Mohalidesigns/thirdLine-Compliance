import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import PageHeader from '@/Components/PageHeader';
import Card from '@/Components/Card';
import StatusBadge from '@/Components/StatusBadge';
import SecondaryButton from '@/Components/SecondaryButton';
import DangerButton from '@/Components/DangerButton';
import ConfirmDialog from '@/Components/ConfirmDialog';

interface Instrument {
    id: number;
    source_title: string;
    reference?: string;
}

interface Obligation {
    id: number;
    reference: string;
    title: string;
    description: string;
    instrument_id: number;
    instrument?: Instrument;
    due_basis: string;
    frequency: string | null;
    next_due_date: string | null;
    responsible_team: string | null;
    status: string;
}

interface Props {
    obligation: Obligation;
}

function DetailRow({ label, value }: { label: string; value?: React.ReactNode }) {
    if (!value && value !== 0) return null;
    return (
        <div className="py-3 border-b border-gray-100 last:border-0 sm:grid sm:grid-cols-3 sm:gap-4">
            <dt className="text-sm font-medium" style={{ color: 'var(--color-text-secondary)' }}>
                {label}
            </dt>
            <dd className="mt-1 text-sm sm:col-span-2 sm:mt-0" style={{ color: 'var(--color-text-primary)' }}>
                {value}
            </dd>
        </div>
    );
}

function statusVariant(status: string): 'overdue' | 'medium' | 'info' | 'completed' | 'draft' {
    const lower = status?.toLowerCase();
    if (lower === 'overdue') return 'overdue';
    if (lower === 'in_progress' || lower === 'in progress') return 'medium';
    if (lower === 'open') return 'info';
    if (lower === 'satisfied') return 'completed';
    return 'draft';
}

function dueBasisLabel(basis: string): string {
    const map: Record<string, string> = {
        recurring: 'Recurring',
        one_off: 'One-off',
        event_driven: 'Event Driven',
    };
    return map[basis] ?? basis;
}

function frequencyLabel(freq: string): string {
    const map: Record<string, string> = {
        daily: 'Daily',
        weekly: 'Weekly',
        monthly: 'Monthly',
        quarterly: 'Quarterly',
        semiannual: 'Semi-annual',
        annual: 'Annual',
        adhoc: 'Ad hoc',
    };
    return map[freq] ?? freq;
}

export default function ObligationsShow({ obligation }: Props) {
    const [showDeleteDialog, setShowDeleteDialog] = useState(false);
    const [deleting, setDeleting] = useState(false);

    const handleDelete = () => {
        setDeleting(true);
        router.delete(route('obligations.destroy', obligation.id), {
            onFinish: () => {
                setDeleting(false);
                setShowDeleteDialog(false);
            },
        });
    };

    return (
        <AuthenticatedLayout>
            <Head title={obligation.title} />

            <PageHeader
                title={obligation.title}
                breadcrumb={[
                    { label: 'Obligations', href: route('obligations.index') },
                    { label: obligation.reference },
                ]}
                actions={
                    <>
                        <Link href={route('obligations.edit', obligation.id)}>
                            <SecondaryButton type="button">Edit</SecondaryButton>
                        </Link>
                        <DangerButton type="button" onClick={() => setShowDeleteDialog(true)}>
                            Delete
                        </DangerButton>
                    </>
                }
            />

            <Card hover={false} padding="none" className="mb-6">
                <Card.Header>
                    <h2 className="text-sm font-semibold" style={{ color: 'var(--color-text-primary)' }}>
                        Obligation Details
                    </h2>
                </Card.Header>
                <Card.Body>
                    <dl>
                        <DetailRow label="Reference" value={
                            <span className="font-mono text-xs">{obligation.reference}</span>
                        } />
                        <DetailRow label="Title" value={obligation.title} />
                        <DetailRow label="Description" value={obligation.description} />
                        {obligation.instrument && (
                            <DetailRow
                                label="Linked Instrument"
                                value={
                                    <Link
                                        href={route('instruments.show', obligation.instrument.id)}
                                        className="text-blue-600 hover:underline"
                                    >
                                        {obligation.instrument.source_title}
                                    </Link>
                                }
                            />
                        )}
                        <DetailRow label="Due Basis" value={dueBasisLabel(obligation.due_basis)} />
                        {obligation.frequency && (
                            <DetailRow label="Frequency" value={frequencyLabel(obligation.frequency)} />
                        )}
                        <DetailRow label="Next Due Date" value={obligation.next_due_date ?? '—'} />
                        {obligation.responsible_team && (
                            <DetailRow label="Responsible Team" value={obligation.responsible_team} />
                        )}
                        <DetailRow
                            label="Status"
                            value={
                                <StatusBadge
                                    variant={statusVariant(obligation.status)}
                                    label={obligation.status}
                                />
                            }
                        />
                    </dl>
                </Card.Body>
            </Card>

            <ConfirmDialog
                show={showDeleteDialog}
                onClose={() => setShowDeleteDialog(false)}
                onConfirm={handleDelete}
                variant="danger"
                title="Delete Obligation"
                message="Are you sure? This action cannot be undone. The audit trail will record the deletion."
                confirmLabel="Delete Obligation"
                isLoading={deleting}
            />
        </AuthenticatedLayout>
    );
}
