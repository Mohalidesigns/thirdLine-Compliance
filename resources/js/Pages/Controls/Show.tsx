import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import PageHeader from '@/Components/PageHeader';
import Card from '@/Components/Card';
import StatCard from '@/Components/StatCard';
import StatusBadge from '@/Components/StatusBadge';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import DangerButton from '@/Components/DangerButton';
import ConfirmDialog from '@/Components/ConfirmDialog';
import {
    BeakerIcon,
    PencilIcon,
    TrashIcon,
    ClipboardDocumentCheckIcon,
    ExclamationCircleIcon,
} from '@heroicons/react/24/outline';

interface Control {
    id: number;
    reference: string;
    title: string;
    control_type: string;
    nature: string;
    frequency: string;
    frequency_label: string;
    owner_team: string;
    status: string;
    last_tested_at: string | null;
    next_test_due: string | null;
    days_until_due: number;
    description: string | null;
    linked_obligations: Array<{ id: number; reference: string; title: string }>;
    linked_risks: Array<{ id: number; reference: string; title: string }>;
}

interface RecentTest {
    id: number;
    tested_at: string;
    tested_by_name: string;
    outcome: 'passed' | 'partial' | 'failed' | 'not_applicable';
    sample_size: number | null;
    findings: string | null;
}

interface Props {
    control: Control;
    recent_tests: RecentTest[];
    related_issues_count: number;
    next_test_due: string | null;
    sample_size_suggestion: number | null;
    can: { edit: boolean; delete: boolean; test: boolean };
}

type StatusVariant = 'critical' | 'high' | 'medium' | 'low' | 'info' | 'completed' | 'draft' | 'overdue' | 'ai';

function outcomeToVariant(outcome: string): StatusVariant {
    const map: Record<string, StatusVariant> = {
        passed:         'low',
        partial:        'medium',
        failed:         'critical',
        not_applicable: 'draft',
    };
    return map[outcome] ?? 'draft';
}

function outcomeLabel(outcome: string): string {
    const map: Record<string, string> = {
        passed:         'Passed',
        partial:        'Partial',
        failed:         'Failed',
        not_applicable: 'N/A',
    };
    return map[outcome] ?? outcome;
}

function formatDate(dateStr: string | null): string {
    if (!dateStr) return '—';
    const d = new Date(dateStr);
    if (isNaN(d.getTime())) return '—';
    return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}

function relativeTime(isoStr: string | null): string {
    if (!isoStr) return '—';
    const now = Date.now();
    const then = new Date(isoStr).getTime();
    const diffMs = now - then;
    const diffDays = Math.floor(diffMs / 86400000);
    if (diffDays < 1) return 'today';
    if (diffDays < 30) return `${diffDays}d ago`;
    const diffMonths = Math.floor(diffDays / 30);
    if (diffMonths < 12) return `${diffMonths}mo ago`;
    return `${Math.floor(diffMonths / 12)}y ago`;
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

export default function ControlsShow({
    control,
    recent_tests,
    related_issues_count,
    next_test_due,
    sample_size_suggestion,
    can,
}: Props) {
    const [showDeleteDialog, setShowDeleteDialog] = useState(false);
    const [deleting, setDeleting] = useState(false);

    const handleDelete = () => {
        setDeleting(true);
        router.delete(route('controls.destroy', control.id), {
            onFinish: () => {
                setDeleting(false);
                setShowDeleteDialog(false);
            },
        });
    };

    return (
        <AuthenticatedLayout>
            <Head title={`${control.reference} — ${control.title}`} />

            <PageHeader
                title={`${control.reference}`}
                subtitle={control.title}
                breadcrumb={[
                    { label: 'Controls', href: route('controls.index') },
                    { label: control.reference },
                ]}
                actions={
                    <div className="flex items-center gap-2 flex-wrap">
                        {can.test && (
                            <Link href={route('controls.test', control.id)}>
                                <PrimaryButton type="button">
                                    <ClipboardDocumentCheckIcon aria-hidden className="w-4 h-4" />
                                    Test Now
                                </PrimaryButton>
                            </Link>
                        )}
                        {can.edit && (
                            <Link href={route('controls.edit', control.id)}>
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

            <div className="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-6">
                <StatCard
                    title="Last Tested"
                    value={relativeTime(control.last_tested_at)}
                    icon={<ClipboardDocumentCheckIcon aria-hidden className="w-5 h-5" />}
                    color="blue"
                />
                <StatCard
                    title="Suggested Sample Size"
                    value={sample_size_suggestion ?? '—'}
                    icon={<BeakerIcon aria-hidden className="w-5 h-5" />}
                    color="teal"
                />
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div className="lg:col-span-2 space-y-6">
                    {control.description && (
                        <Card hover={false} padding="none">
                            <Card.Header>
                                <h2 className="text-sm font-semibold" style={{ color: 'var(--color-text-primary)' }}>
                                    Description
                                </h2>
                            </Card.Header>
                            <Card.Body>
                                <p className="text-sm whitespace-pre-wrap leading-relaxed" style={{ color: 'var(--color-text-primary)' }}>
                                    {control.description}
                                </p>
                            </Card.Body>
                        </Card>
                    )}

                    {control.linked_obligations.length > 0 && (
                        <Card hover={false} padding="none">
                            <Card.Header>
                                <h2 className="text-sm font-semibold" style={{ color: 'var(--color-text-primary)' }}>
                                    Linked Obligations
                                </h2>
                            </Card.Header>
                            <Card.Body>
                                <div className="flex flex-wrap gap-2">
                                    {control.linked_obligations.map((ob) => (
                                        <Link
                                            key={ob.id}
                                            href={route('obligations.index')}
                                            className="badge border bg-blue-100 text-blue-700 border-blue-200 text-xs hover:bg-blue-200 transition-colors focus:outline-none focus:ring-2 focus:ring-primary"
                                            title={ob.title}
                                        >
                                            {ob.reference}
                                        </Link>
                                    ))}
                                </div>
                            </Card.Body>
                        </Card>
                    )}

                    {control.linked_risks.length > 0 && (
                        <Card hover={false} padding="none">
                            <Card.Header>
                                <h2 className="text-sm font-semibold" style={{ color: 'var(--color-text-primary)' }}>
                                    Linked Risks
                                </h2>
                            </Card.Header>
                            <Card.Body>
                                <div className="flex flex-wrap gap-2">
                                    {control.linked_risks.map((r) => (
                                        <Link
                                            key={r.id}
                                            href={route('risks.show', r.id)}
                                            className="badge border bg-orange-100 text-orange-700 border-orange-200 text-xs hover:bg-orange-200 transition-colors focus:outline-none focus:ring-2 focus:ring-primary"
                                            title={r.title}
                                        >
                                            {r.reference}
                                        </Link>
                                    ))}
                                </div>
                            </Card.Body>
                        </Card>
                    )}

                    <Card hover={false} padding="none">
                        <Card.Header>
                            <div className="flex items-center justify-between">
                                <h2 className="text-sm font-semibold" style={{ color: 'var(--color-text-primary)' }}>
                                    Recent Tests
                                </h2>
                                <Link
                                    href={route('tests.index', { control: control.id })}
                                    className="text-xs text-blue-600 hover:text-blue-800 focus:outline-none focus:underline"
                                >
                                    View all
                                </Link>
                            </div>
                        </Card.Header>
                        <Card.Body>
                            {recent_tests.length === 0 ? (
                                <p className="text-xs text-gray-400 text-center py-4">No tests recorded yet.</p>
                            ) : (
                                <div className="overflow-x-auto">
                                    <table className="data-table">
                                        <thead>
                                            <tr>
                                                <th scope="col">Date</th>
                                                <th scope="col">Tested By</th>
                                                <th scope="col">Outcome</th>
                                                <th scope="col">Sample</th>
                                                <th scope="col">Findings</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {recent_tests.map((test) => (
                                                <tr key={test.id}>
                                                    <td className="text-xs">{formatDate(test.tested_at)}</td>
                                                    <td className="text-xs">{test.tested_by_name}</td>
                                                    <td>
                                                        <StatusBadge
                                                            variant={outcomeToVariant(test.outcome)}
                                                            label={outcomeLabel(test.outcome)}
                                                            size="sm"
                                                        />
                                                    </td>
                                                    <td className="text-xs">{test.sample_size ?? '—'}</td>
                                                    <td className="text-xs max-w-xs truncate" style={{ color: 'var(--color-text-secondary)' }}>
                                                        {test.findings ?? '—'}
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            )}
                        </Card.Body>
                    </Card>

                    {related_issues_count > 0 && (
                        <div className="rounded-xl border border-orange-200 bg-orange-50 px-5 py-4 flex items-center gap-3">
                            <ExclamationCircleIcon aria-hidden className="w-5 h-5 text-orange-600 flex-shrink-0" />
                            <p className="text-sm text-orange-800">
                                {related_issues_count} related issue{related_issues_count !== 1 ? 's' : ''} raised from testing.
                            </p>
                            <Link
                                href={route('issues.index')}
                                className="ml-auto text-xs text-orange-700 underline focus:outline-none"
                            >
                                View issues
                            </Link>
                        </div>
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
                                    <span className="font-mono">{control.reference}</span>
                                </DetailItem>
                                <DetailItem label="Type">
                                    <StatusBadge variant="info" label={control.control_type} size="sm" />
                                </DetailItem>
                                <DetailItem label="Nature">{control.nature}</DetailItem>
                                <DetailItem label="Frequency">{control.frequency_label}</DetailItem>
                                <DetailItem label="Owner Team">{control.owner_team}</DetailItem>
                                <DetailItem label="Status">
                                    <StatusBadge variant="low" label={control.status} size="sm" />
                                </DetailItem>
                                <DetailItem label="Next Test Due">
                                    {formatDate(next_test_due)}
                                </DetailItem>
                            </div>
                        </Card.Body>
                    </Card>
                </div>
            </div>

            <ConfirmDialog
                show={showDeleteDialog}
                onClose={() => setShowDeleteDialog(false)}
                onConfirm={handleDelete}
                variant="danger"
                title={`Delete control ${control.reference}?`}
                message="This action is permanent and will remove all test history."
                confirmLabel="Delete Control"
                isLoading={deleting}
            />
        </AuthenticatedLayout>
    );
}
