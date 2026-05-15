import { Head, Link } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import PageHeader from '@/Components/PageHeader';
import Card from '@/Components/Card';
import DataTable, { Column } from '@/Components/DataTable';
import EmptyState from '@/Components/EmptyState';
import PrimaryButton from '@/Components/PrimaryButton';
import { ClipboardDocumentCheckIcon } from '@heroicons/react/24/outline';

// ─── Types ────────────────────────────────────────────────────────────────────

interface PendingCampaign {
    id: number;
    code: string;
    title: string;
    body_preview: string;
    ends_at: string;
    days_remaining: number;
    is_overdue: boolean;
}

interface SignedCampaign {
    id: number;
    code: string;
    title: string;
    signed_at: string;
}

interface Props {
    pending: PendingCampaign[];
    signed: SignedCampaign[];
}

// ─── Helpers ──────────────────────────────────────────────────────────────────

function formatDate(iso: string | null): string {
    if (!iso) return '—';
    const d = new Date(iso);
    if (isNaN(d.getTime())) return '—';
    return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}

// ─── Page ─────────────────────────────────────────────────────────────────────

export default function MyAttestationsIndex({ pending, signed }: Props) {
    const isEmpty = pending.length === 0 && signed.length === 0;

    const signedColumns: Column<SignedCampaign>[] = [
        {
            key: 'title',
            header: 'Title',
            render: (row) => (
                <Link
                    href={route('my.attestations.show', row.id)}
                    className="text-sm font-medium hover:underline focus:outline-none focus:ring-2 focus:ring-primary rounded"
                    style={{ color: 'var(--color-text-primary)' }}
                >
                    {row.title}
                </Link>
            ),
        },
        {
            key: 'code',
            header: 'Code',
            render: (row) => (
                <span className="font-mono text-xs" style={{ color: 'var(--color-text-secondary)' }}>
                    {row.code}
                </span>
            ),
        },
        {
            key: 'signed_at',
            header: 'Signed At',
            render: (row) => (
                <span className="text-sm" style={{ color: 'var(--color-text-secondary)' }}>
                    {formatDate(row.signed_at)}
                </span>
            ),
        },
    ];

    return (
        <AuthenticatedLayout>
            <Head title="My Attestations" />

            <PageHeader
                title="My Attestations"
                subtitle="Review and sign compliance attestation campaigns."
            />

            {isEmpty ? (
                <EmptyState
                    icon={<ClipboardDocumentCheckIcon className="w-8 h-8 text-gray-400" aria-hidden />}
                    title="No attestations assigned."
                    description="When your administrator publishes an attestation campaign, it will appear here."
                />
            ) : (
                <div className="space-y-8">
                    {/* Pending section */}
                    {pending.length > 0 && (
                        <section aria-labelledby="pending-heading">
                            <h2
                                id="pending-heading"
                                className="text-base font-semibold mb-3"
                                style={{ color: 'var(--color-text-primary)' }}
                            >
                                Pending Attestations
                            </h2>
                            <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
                                {pending.map((campaign) => {
                                    // Color for the days remaining label:
                                    // overdue → red, < 7 days → yellow, otherwise → default text
                                    const daysColorClass = campaign.is_overdue
                                        ? 'text-red-600 font-semibold'
                                        : campaign.days_remaining < 7
                                        ? 'text-amber-600 font-semibold'
                                        : 'text-gray-500';

                                    const daysLabel = campaign.is_overdue
                                        ? `Overdue by ${Math.abs(campaign.days_remaining)} day${
                                              Math.abs(campaign.days_remaining) === 1 ? '' : 's'
                                          }`
                                        : `${campaign.days_remaining} day${
                                              campaign.days_remaining === 1 ? '' : 's'
                                          } remaining`;

                                    return (
                                        <Card key={campaign.id} hover={false} padding="none">
                                            <Card.Body>
                                                <div className="flex flex-col gap-3">
                                                    {/* Header row */}
                                                    <div className="flex items-start justify-between gap-3">
                                                        <div className="flex-1 min-w-0">
                                                            <p
                                                                className="text-sm font-semibold truncate"
                                                                style={{ color: 'var(--color-text-primary)' }}
                                                            >
                                                                {campaign.title}
                                                            </p>
                                                            <p
                                                                className="text-xs mt-0.5 font-mono"
                                                                style={{ color: 'var(--color-text-secondary)' }}
                                                            >
                                                                {campaign.code}
                                                            </p>
                                                        </div>
                                                        <span className={`text-xs flex-shrink-0 mt-0.5 ${daysColorClass}`}>
                                                            {daysLabel}
                                                        </span>
                                                    </div>

                                                    {/* Body preview */}
                                                    {campaign.body_preview && (
                                                        <p
                                                            className="text-sm italic leading-relaxed line-clamp-3"
                                                            style={{ color: 'var(--color-text-secondary)' }}
                                                        >
                                                            {campaign.body_preview}
                                                        </p>
                                                    )}

                                                    {/* Ends at + CTA */}
                                                    <div className="flex items-center justify-between gap-3 pt-1">
                                                        <span
                                                            className="text-xs"
                                                            style={{ color: 'var(--color-text-secondary)' }}
                                                        >
                                                            Closes {formatDate(campaign.ends_at)}
                                                        </span>
                                                        <Link
                                                            href={route('my.attestations.show', campaign.id)}
                                                        >
                                                            <PrimaryButton type="button">
                                                                Review &amp; Sign
                                                            </PrimaryButton>
                                                        </Link>
                                                    </div>
                                                </div>
                                            </Card.Body>
                                        </Card>
                                    );
                                })}
                            </div>
                        </section>
                    )}

                    {/* Signed section */}
                    {signed.length > 0 && (
                        <section aria-labelledby="signed-heading">
                            <h2
                                id="signed-heading"
                                className="text-base font-semibold mb-3"
                                style={{ color: 'var(--color-text-primary)' }}
                            >
                                Signed Attestations
                            </h2>
                            <Card hover={false} padding="none">
                                <DataTable
                                    columns={signedColumns}
                                    data={signed}
                                    emptyState={null}
                                />
                            </Card>
                        </section>
                    )}
                </div>
            )}
        </AuthenticatedLayout>
    );
}
