import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import PageHeader from '@/Components/PageHeader';
import Card from '@/Components/Card';
import StatusBadge from '@/Components/StatusBadge';
import SecondaryButton from '@/Components/SecondaryButton';
import DangerButton from '@/Components/DangerButton';
import ConfirmDialog from '@/Components/ConfirmDialog';

interface Sanction {
    id: number;
    regulator: string;
    reference: string;
    section: string | null;
    offence: string;
    party_name: string;
    party_type: string;
    amount_naira: number | string | null;
    penalty_type: string;
    effective_date: string;
    source_url: string | null;
}

interface Props {
    sanction: Sanction;
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

function penaltyVariant(type: string): 'critical' | 'high' | 'medium' | 'info' | 'draft' {
    const lower = type?.toLowerCase();
    if (lower === 'monetary') return 'high';
    if (lower === 'license_action') return 'critical';
    if (lower === 'reprimand') return 'medium';
    return 'info';
}

function partyTypeLabel(type: string): string {
    const map: Record<string, string> = {
        individual: 'Individual',
        institution: 'Institution',
        committee: 'Committee',
    };
    return map[type] ?? type;
}

function penaltyTypeLabel(type: string): string {
    const map: Record<string, string> = {
        monetary: 'Monetary',
        license_action: 'License Action',
        reprimand: 'Reprimand',
        other: 'Other',
    };
    return map[type] ?? type;
}

function formatNaira(amount: number | string | null): string | null {
    if (amount == null || amount === '') return null;
    const num = typeof amount === 'string' ? parseFloat(amount) : amount;
    if (isNaN(num)) return null;
    return new Intl.NumberFormat('en-NG', { style: 'currency', currency: 'NGN' }).format(num);
}

export default function SanctionsShow({ sanction }: Props) {
    const [showDeleteDialog, setShowDeleteDialog] = useState(false);
    const [deleting, setDeleting] = useState(false);

    const handleDelete = () => {
        setDeleting(true);
        router.delete(route('sanctions.destroy', sanction.id), {
            onFinish: () => {
                setDeleting(false);
                setShowDeleteDialog(false);
            },
        });
    };

    const formattedAmount = formatNaira(sanction.amount_naira);

    return (
        <AuthenticatedLayout>
            <Head title={`Sanction: ${sanction.reference}`} />

            <PageHeader
                title={`Sanction: ${sanction.reference}`}
                breadcrumb={[
                    { label: 'Sanctions KB', href: route('sanctions.index') },
                    { label: sanction.reference },
                ]}
                actions={
                    <>
                        <Link href={route('sanctions.edit', sanction.id)}>
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
                        Offence
                    </h2>
                </Card.Header>
                <Card.Body>
                    <p className="text-sm leading-relaxed" style={{ color: 'var(--color-text-primary)' }}>
                        {sanction.offence}
                    </p>
                </Card.Body>
            </Card>

            <Card hover={false} padding="none">
                <Card.Header>
                    <h2 className="text-sm font-semibold" style={{ color: 'var(--color-text-primary)' }}>
                        Sanction Details
                    </h2>
                </Card.Header>
                <Card.Body>
                    <dl>
                        <DetailRow label="Reference" value={
                            <span className="font-mono text-xs">{sanction.reference}</span>
                        } />
                        {sanction.section && (
                            <DetailRow label="Section" value={sanction.section} />
                        )}
                        <DetailRow label="Regulator" value={
                            <StatusBadge variant="info" label={sanction.regulator} />
                        } />
                        <DetailRow label="Party Name" value={sanction.party_name} />
                        <DetailRow label="Party Type" value={partyTypeLabel(sanction.party_type)} />
                        <DetailRow
                            label="Penalty Type"
                            value={
                                <StatusBadge
                                    variant={penaltyVariant(sanction.penalty_type)}
                                    label={penaltyTypeLabel(sanction.penalty_type)}
                                />
                            }
                        />
                        {formattedAmount && (
                            <DetailRow
                                label="Amount"
                                value={
                                    <span className="font-semibold text-red-700">{formattedAmount}</span>
                                }
                            />
                        )}
                        <DetailRow label="Effective Date" value={sanction.effective_date} />
                        {sanction.source_url && (
                            <DetailRow
                                label="Source"
                                value={
                                    <a
                                        href={sanction.source_url}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className="text-blue-600 hover:underline break-all"
                                    >
                                        {sanction.source_url}
                                    </a>
                                }
                            />
                        )}
                    </dl>
                </Card.Body>
            </Card>

            <ConfirmDialog
                show={showDeleteDialog}
                onClose={() => setShowDeleteDialog(false)}
                onConfirm={handleDelete}
                variant="danger"
                title="Delete Sanction"
                message="Are you sure? This action cannot be undone. The audit trail will record the deletion."
                confirmLabel="Delete Sanction"
                isLoading={deleting}
            />
        </AuthenticatedLayout>
    );
}
