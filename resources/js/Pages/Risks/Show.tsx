import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import PageHeader from '@/Components/PageHeader';
import Card from '@/Components/Card';
import StatusBadge from '@/Components/StatusBadge';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import DangerButton from '@/Components/DangerButton';
import ConfirmDialog from '@/Components/ConfirmDialog';
import { useState } from 'react';
import {
    PencilIcon,
    TrashIcon,
    ExclamationTriangleIcon,
} from '@heroicons/react/24/outline';

interface Props {
    risk: {
        id: number;
        reference: string;
        title: string;
        description: string;
        category: string;
        category_label: string;
        risk_owner: string | null;
        inherent_likelihood: number | null;
        inherent_impact: number | null;
        inherent_score: number | null;
        inherent_rating: string | null;
        residual_likelihood: number | null;
        residual_impact: number | null;
        residual_score: number | null;
        residual_rating: string | null;
        mitigation_summary: string | null;
        accept_basis: string | null;
        linked_obligation_ids: number[];
        cycle: { id: number; reference: string; name: string; methodology: '3x3' | '5x5' };
    };
    methodology: '3x3' | '5x5';
    rating_thresholds: { low: number; medium: number; high: number; critical: number };
    appetite: { acceptable_rating: string | null; breach_action: string | null };
    breaches_appetite: boolean;
    can: { edit: boolean; delete: boolean; score: boolean };
}

type StatusVariant = 'critical' | 'high' | 'medium' | 'low' | 'info' | 'completed' | 'draft' | 'overdue' | 'ai';

function ratingToVariant(rating: string | null): StatusVariant {
    if (!rating) return 'draft';
    const map: Record<string, StatusVariant> = {
        low: 'low', medium: 'medium', high: 'high', critical: 'critical',
    };
    return map[rating] ?? 'draft';
}

function ScoreBox({ label, score, rating }: { label: string; score: number | null; rating: string | null }) {
    const variant = ratingToVariant(rating);
    const bgMap: Record<StatusVariant, string> = {
        low:       'bg-green-50  border-green-200',
        medium:    'bg-yellow-50 border-yellow-200',
        high:      'bg-orange-50 border-orange-200',
        critical:  'bg-red-50    border-red-200',
        draft:     'bg-gray-50   border-gray-200',
        info:      'bg-blue-50   border-blue-200',
        completed: 'bg-purple-50 border-purple-200',
        overdue:   'bg-red-50    border-red-200',
        ai:        'bg-violet-50 border-violet-200',
    };
    return (
        <div className={`rounded-xl border p-5 text-center ${bgMap[variant]}`}>
            <p className="text-[10px] uppercase font-semibold text-gray-400 mb-2">{label}</p>
            <p className="text-3xl font-bold mb-2" style={{ color: 'var(--color-text-primary)' }}>
                {score ?? '—'}
            </p>
            {rating ? (
                <StatusBadge variant={variant} label={rating.charAt(0).toUpperCase() + rating.slice(1)} />
            ) : (
                <span className="text-xs text-gray-400">Not scored</span>
            )}
        </div>
    );
}

function DetailItem({ label, children }: { label: string; children: React.ReactNode }) {
    return (
        <div className="py-2.5 border-b border-gray-100 last:border-0 flex justify-between items-start gap-4">
            <span className="text-xs font-medium flex-shrink-0 w-32" style={{ color: 'var(--color-text-secondary)' }}>
                {label}
            </span>
            <span className="text-xs text-right" style={{ color: 'var(--color-text-primary)' }}>
                {children}
            </span>
        </div>
    );
}

export default function RisksShow({ risk, methodology, appetite, breaches_appetite, can }: Props) {
    const [showDeleteDialog, setShowDeleteDialog] = useState(false);
    const [deleting, setDeleting] = useState(false);

    const handleDelete = () => {
        setDeleting(true);
        router.delete(route('risks.destroy', risk.id), {
            onFinish: () => {
                setDeleting(false);
                setShowDeleteDialog(false);
            },
        });
    };

    return (
        <AuthenticatedLayout>
            <Head title={`${risk.reference} — ${risk.title}`} />

            <PageHeader
                title={`${risk.reference}`}
                subtitle={risk.title}
                breadcrumb={[
                    { label: 'Risk Assessments', href: route('risk-assessments.index') },
                    { label: risk.cycle.reference, href: route('risk-assessments.show', risk.cycle.id) },
                    { label: risk.reference },
                ]}
                actions={
                    <div className="flex items-center gap-2 flex-wrap">
                        {can.edit && (
                            <Link href={route('risks.edit', risk.id)}>
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

            {breaches_appetite && (
                <div className="mb-6 rounded-xl border border-yellow-200 bg-yellow-50 px-5 py-4 flex items-start gap-3">
                    <ExclamationTriangleIcon aria-hidden className="w-5 h-5 text-yellow-600 flex-shrink-0 mt-0.5" />
                    <div>
                        <p className="text-sm font-semibold text-yellow-800">Risk Appetite Breach</p>
                        <p className="text-xs text-yellow-700 mt-0.5">
                            This risk exceeds the acceptable rating
                            {appetite.acceptable_rating ? ` (${appetite.acceptable_rating})` : ''}.
                            {appetite.breach_action && ` Required action: ${appetite.breach_action}`}
                        </p>
                    </div>
                </div>
            )}

            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div className="lg:col-span-2 space-y-6">
                    <div className="grid grid-cols-2 gap-4">
                        <ScoreBox
                            label="Inherent Score"
                            score={risk.inherent_score}
                            rating={risk.inherent_rating}
                        />
                        <ScoreBox
                            label="Residual Score"
                            score={risk.residual_score}
                            rating={risk.residual_rating}
                        />
                    </div>

                    {risk.description && (
                        <Card hover={false} padding="none">
                            <Card.Header>
                                <h2 className="text-sm font-semibold" style={{ color: 'var(--color-text-primary)' }}>
                                    Description
                                </h2>
                            </Card.Header>
                            <Card.Body>
                                <p className="text-sm whitespace-pre-wrap leading-relaxed" style={{ color: 'var(--color-text-primary)' }}>
                                    {risk.description}
                                </p>
                            </Card.Body>
                        </Card>
                    )}

                    {risk.mitigation_summary && (
                        <Card hover={false} padding="none">
                            <Card.Header>
                                <h2 className="text-sm font-semibold" style={{ color: 'var(--color-text-primary)' }}>
                                    Mitigation Summary
                                </h2>
                            </Card.Header>
                            <Card.Body>
                                <p className="text-sm whitespace-pre-wrap leading-relaxed" style={{ color: 'var(--color-text-secondary)' }}>
                                    {risk.mitigation_summary}
                                </p>
                            </Card.Body>
                        </Card>
                    )}

                    {risk.accept_basis && (
                        <Card hover={false} padding="none">
                            <Card.Header>
                                <h2 className="text-sm font-semibold" style={{ color: 'var(--color-text-primary)' }}>
                                    Acceptance Basis
                                </h2>
                            </Card.Header>
                            <Card.Body>
                                <p className="text-sm whitespace-pre-wrap leading-relaxed italic" style={{ color: 'var(--color-text-secondary)' }}>
                                    {risk.accept_basis}
                                </p>
                            </Card.Body>
                        </Card>
                    )}

                    {risk.linked_obligation_ids.length > 0 && (
                        <Card hover={false} padding="none">
                            <Card.Header>
                                <h2 className="text-sm font-semibold" style={{ color: 'var(--color-text-primary)' }}>
                                    Linked Obligations
                                </h2>
                            </Card.Header>
                            <Card.Body>
                                <div className="flex flex-wrap gap-2">
                                    {risk.linked_obligation_ids.map((id) => (
                                        <Link
                                            key={id}
                                            href={route('obligations.index')}
                                            className="badge border bg-blue-100 text-blue-700 border-blue-200 text-xs hover:bg-blue-200 transition-colors focus:outline-none focus:ring-2 focus:ring-primary"
                                        >
                                            OBL-{id}
                                        </Link>
                                    ))}
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
                                    <span className="font-mono">{risk.reference}</span>
                                </DetailItem>
                                <DetailItem label="Category">
                                    <StatusBadge variant="info" label={risk.category_label} size="sm" />
                                </DetailItem>
                                <DetailItem label="Owner">{risk.risk_owner ?? '—'}</DetailItem>
                                <DetailItem label="Methodology">{methodology}</DetailItem>
                                <DetailItem label="Inherent L×I">
                                    {risk.inherent_likelihood ?? '—'} × {risk.inherent_impact ?? '—'}
                                </DetailItem>
                                <DetailItem label="Residual L×I">
                                    {risk.residual_likelihood ?? '—'} × {risk.residual_impact ?? '—'}
                                </DetailItem>
                                <DetailItem label="Appetite">
                                    {breaches_appetite ? (
                                        <StatusBadge variant="high" label="Breach" size="sm" />
                                    ) : (
                                        <StatusBadge variant="low" label="Within" size="sm" />
                                    )}
                                </DetailItem>
                            </div>
                        </Card.Body>
                    </Card>

                    <Card hover={false} padding="none">
                        <Card.Header>
                            <h2 className="text-sm font-semibold" style={{ color: 'var(--color-text-primary)' }}>
                                Cycle
                            </h2>
                        </Card.Header>
                        <Card.Body>
                            <Link
                                href={route('risk-assessments.show', risk.cycle.id)}
                                className="text-sm font-medium text-blue-600 hover:text-blue-800 focus:outline-none focus:underline"
                            >
                                {risk.cycle.reference} — {risk.cycle.name}
                            </Link>
                        </Card.Body>
                    </Card>
                </div>
            </div>

            <ConfirmDialog
                show={showDeleteDialog}
                onClose={() => setShowDeleteDialog(false)}
                onConfirm={handleDelete}
                variant="danger"
                title={`Delete risk ${risk.reference}?`}
                message="This action is permanent and will remove all associated data."
                confirmLabel="Delete Risk"
                isLoading={deleting}
            />
        </AuthenticatedLayout>
    );
}
