import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import PageHeader from '@/Components/PageHeader';
import Card from '@/Components/Card';
import StatusBadge from '@/Components/StatusBadge';
import SecondaryButton from '@/Components/SecondaryButton';
import DangerButton from '@/Components/DangerButton';
import ConfirmDialog from '@/Components/ConfirmDialog';
import { ClipboardDocumentListIcon } from '@heroicons/react/24/outline';

interface LinkedObligation {
    id: number;
    ref_code: string;
    title?: string;
    description: string;
    status: string;
}

interface Instrument {
    id: number;
    source_title: string;
    regulator: string;
    instrument_type: string;
    nature: string;
    status: string;
    area_of_focus: string;
    risk_rating: string;
    risk_rating_explain: string;
    objectives: string;
    commercial_bank_relevance: string;
    commercial_bank_compliance_context: string;
    applicability: string;
    date_issue: string | null;
    date_commence: string | null;
    link_url: string | null;
    obligations?: LinkedObligation[];
}

interface Props {
    instrument: Instrument;
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

function riskVariant(rating: string): 'critical' | 'high' | 'medium' | 'low' | 'info' | 'draft' {
    const lower = rating?.toLowerCase();
    if (lower === 'critical') return 'critical';
    if (lower === 'high') return 'high';
    if (lower === 'medium') return 'medium';
    if (lower === 'low') return 'low';
    return 'info';
}

function obligationStatusVariant(status: string): 'overdue' | 'medium' | 'info' | 'low' | 'completed' | 'draft' {
    const lower = status?.toLowerCase();
    if (lower === 'overdue') return 'overdue';
    if (lower === 'in_progress' || lower === 'in progress') return 'medium';
    if (lower === 'open') return 'info';
    if (lower === 'satisfied') return 'completed';
    return 'draft';
}

export default function InstrumentsShow({ instrument }: Props) {
    const [showDeleteDialog, setShowDeleteDialog] = useState(false);
    const [deleting, setDeleting] = useState(false);

    const handleDelete = () => {
        setDeleting(true);
        router.delete(route('instruments.destroy', instrument.id), {
            onFinish: () => {
                setDeleting(false);
                setShowDeleteDialog(false);
            },
        });
    };

    const obligations = instrument.obligations ?? [];

    return (
        <AuthenticatedLayout>
            <Head title={instrument.source_title} />

            <PageHeader
                title={instrument.source_title}
                breadcrumb={[
                    { label: 'Library', href: route('instruments.index') },
                    { label: 'Instruments', href: route('instruments.index') },
                    { label: instrument.source_title },
                ]}
                actions={
                    <>
                        <Link href={route('instruments.edit', instrument.id)}>
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
                        Instrument Details
                    </h2>
                </Card.Header>
                <Card.Body>
                    <dl>
                        <DetailRow label="Source Title" value={instrument.source_title} />
                        <DetailRow
                            label="Regulator"
                            value={<StatusBadge variant="info" label={instrument.regulator} />}
                        />
                        <DetailRow
                            label="Instrument Type"
                            value={<StatusBadge variant="info" label={instrument.instrument_type} />}
                        />
                        <DetailRow
                            label="Nature"
                            value={instrument.nature}
                        />
                        <DetailRow
                            label="Status"
                            value={<StatusBadge variant="draft" label={instrument.status} />}
                        />
                        <DetailRow label="Area of Focus" value={instrument.area_of_focus} />
                        <DetailRow
                            label="Risk Rating"
                            value={
                                <StatusBadge
                                    variant={riskVariant(instrument.risk_rating)}
                                    label={instrument.risk_rating}
                                    dot
                                />
                            }
                        />
                        <DetailRow label="Applicability" value={instrument.applicability} />
                        <DetailRow label="Date of Issue" value={instrument.date_issue ?? '—'} />
                        <DetailRow label="Date of Commencement" value={instrument.date_commence ?? '—'} />
                        {instrument.link_url && (
                            <DetailRow
                                label="Link"
                                value={
                                    <a
                                        href={instrument.link_url}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className="text-blue-600 hover:underline break-all"
                                    >
                                        {instrument.link_url}
                                    </a>
                                }
                            />
                        )}
                        {instrument.risk_rating_explain && (
                            <DetailRow label="Risk Explanation" value={instrument.risk_rating_explain} />
                        )}
                        {instrument.objectives && (
                            <DetailRow label="Objectives" value={instrument.objectives} />
                        )}
                        {instrument.commercial_bank_relevance && (
                            <DetailRow label="Commercial Bank Relevance" value={instrument.commercial_bank_relevance} />
                        )}
                        {instrument.commercial_bank_compliance_context && (
                            <DetailRow label="Compliance Context" value={instrument.commercial_bank_compliance_context} />
                        )}
                    </dl>
                </Card.Body>
            </Card>

            <Card hover={false} padding="none">
                <Card.Header>
                    <div className="flex items-center gap-2">
                        <ClipboardDocumentListIcon aria-hidden className="w-4 h-4 text-gray-400" />
                        <h2 className="text-sm font-semibold" style={{ color: 'var(--color-text-primary)' }}>
                            Linked Obligations
                        </h2>
                        {obligations.length > 0 && (
                            <span className="ml-auto text-xs text-gray-500">{obligations.length} total</span>
                        )}
                    </div>
                </Card.Header>
                <Card.Body>
                    {obligations.length === 0 ? (
                        <p className="text-sm text-center py-6" style={{ color: 'var(--color-text-secondary)' }}>
                            No obligations yet — link one from the{' '}
                            <Link
                                href={route('obligations.index')}
                                className="text-blue-600 hover:underline"
                            >
                                Obligations page
                            </Link>.
                        </p>
                    ) : (
                        <ul className="divide-y divide-gray-100">
                            {obligations.map((obl) => (
                                <li key={obl.id} className="py-3 flex items-start justify-between gap-4">
                                    <div className="min-w-0 flex-1">
                                        <span className="font-mono text-xs text-gray-500 mr-2">{obl.ref_code}</span>
                                        <span className="text-sm line-clamp-2" style={{ color: 'var(--color-text-primary)' }}>
                                            {obl.title ?? obl.description}
                                        </span>
                                    </div>
                                    <StatusBadge
                                        variant={obligationStatusVariant(obl.status)}
                                        label={obl.status}
                                    />
                                </li>
                            ))}
                        </ul>
                    )}
                </Card.Body>
            </Card>

            <ConfirmDialog
                show={showDeleteDialog}
                onClose={() => setShowDeleteDialog(false)}
                onConfirm={handleDelete}
                variant="danger"
                title="Delete Instrument"
                message="Are you sure? This action cannot be undone. The audit trail will record the deletion."
                confirmLabel="Delete Instrument"
                isLoading={deleting}
            />
        </AuthenticatedLayout>
    );
}
