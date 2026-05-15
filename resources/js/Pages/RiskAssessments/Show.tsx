import { useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import PageHeader from '@/Components/PageHeader';
import Card from '@/Components/Card';
import StatCard from '@/Components/StatCard';
import StatusBadge from '@/Components/StatusBadge';
import DataTable, { Column } from '@/Components/DataTable';
import Pagination from '@/Components/Pagination';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import Modal from '@/Components/Modal';
import InputLabel from '@/Components/InputLabel';
import Textarea from '@/Components/Textarea';
import InputError from '@/Components/InputError';
import EmptyState from '@/Components/EmptyState';
import {
    ShieldExclamationIcon,
    ExclamationTriangleIcon,
    ClockIcon,
    ChartBarIcon,
    PlusIcon,
    PencilIcon,
    TrashIcon,
} from '@heroicons/react/24/outline';

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedData<T> {
    data: T[];
    links: PaginationLink[];
    from: number;
    to: number;
    total: number;
    current_page: number;
    last_page: number;
}

interface Cycle {
    id: number;
    reference: string;
    name: string;
    lob: string;
    state: string;
    state_label: string;
    state_color: 'gray' | 'blue' | 'purple' | 'cyan' | 'green' | 'orange';
    cycle_year: number;
    cycle_quarter: number | null;
    methodology: '3x3' | '5x5';
    sla_due_date: string | null;
    sla_days_remaining: number | null;
    risks_count: number;
    high_risk_count: number;
    lead_assessor_name: string | null;
    updated_at: string;
    summary: string | null;
    started_at: string | null;
    closed_at: string | null;
    workshop_notes: Array<{
        id: number;
        recorded_at: string;
        recorded_by_name: string;
        attendees: string[];
        notes: string;
    }>;
}

interface Risk {
    id: number;
    reference: string;
    title: string;
    category: string;
    category_label: string;
    risk_owner: string | null;
    inherent_score: number | null;
    inherent_rating: 'low' | 'medium' | 'high' | 'critical' | null;
    residual_score: number | null;
    residual_rating: 'low' | 'medium' | 'high' | 'critical' | null;
    breaches_appetite: boolean;
}

interface Props {
    cycle: Cycle;
    summary: {
        risks_total: number;
        by_rating: { low: number; medium: number; high: number; critical: number };
        appetite_breaches: number;
        days_to_sla: number | null;
        workshops_count: number;
    };
    heatmap: {
        methodology: '3x3' | '5x5';
        matrix: number[][];
    };
    risks: PaginatedData<Risk>;
    allowed_transitions: { to: string; label: string; requires_changes_note: boolean }[];
    can: { edit: boolean; transition: boolean; delete: boolean; add_risk: boolean; add_workshop: boolean };
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

function ratingToVariant(rating: string | null): StatusVariant {
    if (!rating) return 'draft';
    const map: Record<string, StatusVariant> = {
        low: 'low', medium: 'medium', high: 'high', critical: 'critical',
    };
    return map[rating] ?? 'draft';
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

function cellColor(count: number, impact: number, likelihood: number, size: number): string {
    if (count === 0) return 'bg-gray-50';
    const score = impact * likelihood;
    const max = size * size;
    if (score >= max * 0.7) return 'bg-red-100 text-red-800';
    if (score >= max * 0.4) return 'bg-orange-100 text-orange-800';
    if (score >= max * 0.2) return 'bg-yellow-100 text-yellow-800';
    return 'bg-green-100 text-green-800';
}

interface HeatmapProps {
    heatmap: { methodology: '3x3' | '5x5'; matrix: number[][] };
    onCellClick: (impact: number, likelihood: number) => void;
    activeBucket: { impact: number; likelihood: number } | null;
}

function Heatmap({ heatmap, onCellClick, activeBucket }: HeatmapProps) {
    const size = heatmap.methodology === '5x5' ? 5 : 3;
    const labels = Array.from({ length: size }, (_, i) => i + 1);

    return (
        <div>
            <div className="flex items-center justify-center mb-1">
                <span className="text-[10px] font-semibold text-gray-400 uppercase tracking-wider">
                    Likelihood →
                </span>
            </div>
            <div className="flex gap-1 items-start">
                <div className="flex flex-col justify-around h-full pr-1" style={{ paddingTop: '24px' }}>
                    {[...labels].reverse().map((l) => (
                        <span
                            key={l}
                            className="text-[10px] text-gray-400 w-4 text-right leading-none"
                            style={{ height: `${Math.floor(200 / size)}px`, display: 'flex', alignItems: 'center', justifyContent: 'flex-end' }}
                        >
                            {l}
                        </span>
                    ))}
                </div>
                <div>
                    <div
                        className="flex gap-0.5 mb-0.5"
                        aria-hidden="true"
                    >
                        {labels.map((l) => (
                            <span key={l} className="text-[10px] text-gray-400 text-center" style={{ width: `${Math.floor(200 / size)}px` }}>
                                {l}
                            </span>
                        ))}
                    </div>
                    <div
                        role="grid"
                        aria-label="Risk heatmap"
                        style={{
                            display: 'grid',
                            gridTemplateColumns: `repeat(${size}, ${Math.floor(200 / size)}px)`,
                            gridTemplateRows: `repeat(${size}, ${Math.floor(200 / size)}px)`,
                            gap: '2px',
                        }}
                    >
                        {[...labels].reverse().map((impact) =>
                            labels.map((likelihood) => {
                                const count = (heatmap.matrix[impact - 1]?.[likelihood - 1]) ?? 0;
                                const colorCls = cellColor(count, impact, likelihood, size);
                                const isActive =
                                    activeBucket?.impact === impact && activeBucket?.likelihood === likelihood;
                                return (
                                    <button
                                        key={`${impact}-${likelihood}`}
                                        type="button"
                                        role="gridcell"
                                        aria-label={`Impact ${impact}, Likelihood ${likelihood}: ${count} risk${count !== 1 ? 's' : ''}`}
                                        aria-pressed={isActive}
                                        onClick={() => onCellClick(impact, likelihood)}
                                        className={`rounded text-xs font-semibold flex items-center justify-center transition-all focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-1 ${colorCls} ${
                                            isActive ? 'ring-2 ring-primary ring-offset-1' : ''
                                        }`}
                                        style={{
                                            width: `${Math.floor(200 / size)}px`,
                                            height: `${Math.floor(200 / size)}px`,
                                        }}
                                    >
                                        {count > 0 ? count : ''}
                                    </button>
                                );
                            })
                        )}
                    </div>
                </div>
                <div className="flex items-center pl-1" style={{ paddingTop: '24px' }}>
                    <span
                        className="text-[10px] font-semibold text-gray-400 uppercase tracking-wider"
                        style={{ writingMode: 'vertical-rl', transform: 'rotate(180deg)' }}
                    >
                        Impact ↑
                    </span>
                </div>
            </div>
        </div>
    );
}

interface TransitionFormData {
    to: string;
    note: string;
}

function TransitionModal({
    transition,
    cycleId,
    onClose,
}: {
    transition: { to: string; label: string; requires_changes_note: boolean };
    cycleId: number;
    onClose: () => void;
}) {
    const { data, setData, post, processing, errors, setError, clearErrors } = useForm<TransitionFormData>({
        to:   transition.to,
        note: '',
    });

    const handleConfirm = () => {
        clearErrors();
        if (transition.requires_changes_note && !data.note.trim()) {
            setError('note', 'A note is required for this transition.');
            return;
        }
        post(route('risk-assessments.transition', cycleId), { onSuccess: () => onClose() });
    };

    return (
        <Modal show onClose={onClose} maxWidth="md">
            <div className="p-6">
                <h2 className="text-lg font-semibold text-gray-900 mb-2">
                    Confirm: {transition.label}
                </h2>
                <p className="text-sm text-gray-500 mb-4">
                    This action will be audited and cannot be undone.
                </p>

                {transition.requires_changes_note && (
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
                    <PrimaryButton onClick={handleConfirm} isLoading={processing}>
                        Confirm
                    </PrimaryButton>
                </div>
            </div>
        </Modal>
    );
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

export default function RiskAssessmentsShow({
    cycle,
    summary,
    heatmap,
    risks,
    allowed_transitions,
    can,
}: Props) {
    const [activeTransition, setActiveTransition] = useState<typeof allowed_transitions[0] | null>(null);
    const [activeBucket, setActiveBucket] = useState<{ impact: number; likelihood: number } | null>(null);

    const handleCellClick = (impact: number, likelihood: number) => {
        if (activeBucket?.impact === impact && activeBucket?.likelihood === likelihood) {
            setActiveBucket(null);
            router.visit(route('risk-assessments.show', cycle.id), { preserveState: true });
        } else {
            setActiveBucket({ impact, likelihood });
            router.visit(
                route('risk-assessments.show', cycle.id),
                {
                    data: { filter_impact: impact, filter_likelihood: likelihood },
                    preserveState: true,
                    replace: true,
                },
            );
        }
    };

    const riskColumns: Column<Risk>[] = [
        {
            key: 'reference',
            header: 'Reference',
            sortable: true,
            width: 'w-28',
            render: (row) => (
                <span className="font-mono text-xs text-gray-600">{row.reference}</span>
            ),
        },
        {
            key: 'title',
            header: 'Title',
            sortable: true,
            render: (row) => (
                <div>
                    <span className="text-sm font-medium" style={{ color: 'var(--color-text-primary)' }}>
                        {row.title}
                    </span>
                    <span className="block text-xs mt-0.5" style={{ color: 'var(--color-text-secondary)' }}>
                        {row.category_label}
                    </span>
                </div>
            ),
        },
        {
            key: 'inherent_rating',
            header: 'Inherent',
            render: (row) => row.inherent_rating ? (
                <StatusBadge variant={ratingToVariant(row.inherent_rating)} label={row.inherent_rating} />
            ) : (
                <span className="text-xs text-gray-400">—</span>
            ),
        },
        {
            key: 'residual_rating',
            header: 'Residual',
            render: (row) => row.residual_rating ? (
                <StatusBadge variant={ratingToVariant(row.residual_rating)} label={row.residual_rating} />
            ) : (
                <span className="text-xs text-gray-400">—</span>
            ),
        },
        {
            key: 'breaches_appetite',
            header: 'Appetite',
            render: (row) => row.breaches_appetite ? (
                <span
                    className="inline-flex items-center gap-1 text-xs font-medium text-yellow-700"
                    title="Breaches risk appetite"
                >
                    <ExclamationTriangleIcon aria-hidden className="w-4 h-4 text-yellow-500" />
                    Breach
                </span>
            ) : (
                <span className="text-xs text-gray-400">Within</span>
            ),
        },
    ];

    const criticalHighCount = summary.by_rating.critical + summary.by_rating.high;

    return (
        <AuthenticatedLayout>
            <Head title={`${cycle.reference} — ${cycle.name}`} />

            <PageHeader
                title={`${cycle.reference} — ${cycle.name}`}
                breadcrumb={[
                    { label: 'Risk Assessments', href: route('risk-assessments.index') },
                    { label: cycle.reference },
                ]}
                actions={
                    <div className="flex items-center gap-2 flex-wrap">
                        {can.edit && (
                            <Link href={route('risk-assessments.edit', cycle.id)}>
                                <SecondaryButton type="button">
                                    <PencilIcon aria-hidden className="w-4 h-4" />
                                    Edit
                                </SecondaryButton>
                            </Link>
                        )}
                    </div>
                }
            />

            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                <StatCard
                    title="Total Risks"
                    value={summary.risks_total}
                    icon={<ShieldExclamationIcon aria-hidden className="w-5 h-5" />}
                    color="blue"
                />
                <StatCard
                    title="Critical / High"
                    value={criticalHighCount}
                    icon={<ExclamationTriangleIcon aria-hidden className="w-5 h-5" />}
                    color="red"
                />
                <StatCard
                    title="Appetite Breaches"
                    value={summary.appetite_breaches}
                    icon={<ChartBarIcon aria-hidden className="w-5 h-5" />}
                    color="amber"
                />
                <StatCard
                    title="Days to SLA"
                    value={summary.days_to_sla !== null ? summary.days_to_sla : '—'}
                    icon={<ClockIcon aria-hidden className="w-5 h-5" />}
                    color={summary.days_to_sla !== null && summary.days_to_sla < 0 ? 'red' : 'green'}
                />
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div className="lg:col-span-2 space-y-6">
                    <Card hover={false} padding="none">
                        <Card.Header>
                            <h2 className="text-sm font-semibold" style={{ color: 'var(--color-text-primary)' }}>
                                Risk Heatmap — {heatmap.methodology}
                            </h2>
                        </Card.Header>
                        <Card.Body>
                            <div className="flex justify-center py-2">
                                <Heatmap
                                    heatmap={heatmap}
                                    onCellClick={handleCellClick}
                                    activeBucket={activeBucket}
                                />
                            </div>
                            {activeBucket && (
                                <p className="text-xs text-center mt-2" style={{ color: 'var(--color-text-secondary)' }}>
                                    Filtering risks: Impact {activeBucket.impact}, Likelihood {activeBucket.likelihood}.{' '}
                                    <button
                                        type="button"
                                        className="text-blue-600 underline focus:outline-none"
                                        onClick={() => {
                                            setActiveBucket(null);
                                            router.visit(route('risk-assessments.show', cycle.id));
                                        }}
                                    >
                                        Clear filter
                                    </button>
                                </p>
                            )}
                        </Card.Body>
                    </Card>

                    <Card hover={false} padding="none">
                        <Card.Header>
                            <div className="flex items-center justify-between">
                                <h2 className="text-sm font-semibold" style={{ color: 'var(--color-text-primary)' }}>
                                    Risks
                                </h2>
                                {can.add_risk && (
                                    <Link href={`${route('risks.create')}?cycle=${cycle.id}`}>
                                        <PrimaryButton type="button">
                                            <PlusIcon aria-hidden className="w-4 h-4" />
                                            Add Risk
                                        </PrimaryButton>
                                    </Link>
                                )}
                            </div>
                        </Card.Header>
                        <DataTable
                            columns={riskColumns}
                            data={risks.data}
                            onRowClick={(row) => router.visit(route('risks.show', row.id))}
                            emptyState={
                                <EmptyState
                                    icon={<ShieldExclamationIcon className="w-8 h-8 text-gray-400" aria-hidden />}
                                    title="No risks identified yet"
                                    description="Add risks to this assessment cycle."
                                    action={
                                        can.add_risk ? (
                                            <Link href={`${route('risks.create')}?cycle=${cycle.id}`}>
                                                <PrimaryButton type="button">
                                                    <PlusIcon aria-hidden className="w-4 h-4" />
                                                    Add Risk
                                                </PrimaryButton>
                                            </Link>
                                        ) : undefined
                                    }
                                />
                            }
                        />
                        <Pagination
                            links={risks.links}
                            from={risks.from}
                            to={risks.to}
                            total={risks.total}
                        />
                    </Card>
                </div>

                <div className="space-y-6">
                    <Card hover={false} padding="none">
                        <Card.Header>
                            <h2 className="text-sm font-semibold" style={{ color: 'var(--color-text-primary)' }}>
                                Cycle Details
                            </h2>
                        </Card.Header>
                        <Card.Body>
                            <div className="divide-y divide-gray-100">
                                <DetailItem label="Reference">
                                    <span className="font-mono">{cycle.reference}</span>
                                </DetailItem>
                                <DetailItem label="LOB">{cycle.lob}</DetailItem>
                                <DetailItem label="Methodology">{cycle.methodology}</DetailItem>
                                <DetailItem label="State">
                                    <StatusBadge
                                        variant={stateColorToVariant(cycle.state_color)}
                                        label={cycle.state_label}
                                        size="sm"
                                    />
                                </DetailItem>
                                <DetailItem label="Started">{formatDate(cycle.started_at)}</DetailItem>
                                <DetailItem label="SLA Due">{formatDate(cycle.sla_due_date)}</DetailItem>
                                <DetailItem label="Lead Assessor">
                                    {cycle.lead_assessor_name ?? '—'}
                                </DetailItem>
                            </div>
                        </Card.Body>
                    </Card>

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

                    <Card hover={false} padding="none">
                        <Card.Header>
                            <div className="flex items-center justify-between">
                                <h2 className="text-sm font-semibold" style={{ color: 'var(--color-text-primary)' }}>
                                    Workshop Notes
                                </h2>
                                {can.add_workshop && (
                                    <span className="text-xs text-blue-600 font-medium">
                                        + Add Note
                                    </span>
                                )}
                            </div>
                        </Card.Header>
                        <Card.Body>
                            {cycle.workshop_notes.length === 0 ? (
                                <p className="text-xs text-gray-400 text-center py-4">No workshop notes recorded.</p>
                            ) : (
                                <ul className="divide-y divide-gray-100">
                                    {cycle.workshop_notes.slice(0, 3).map((note) => (
                                        <li key={note.id} className="py-3 first:pt-0 last:pb-0">
                                            <div className="flex items-center justify-between mb-1">
                                                <span className="text-xs font-medium" style={{ color: 'var(--color-text-primary)' }}>
                                                    {note.recorded_by_name}
                                                </span>
                                                <span className="text-xs" style={{ color: 'var(--color-text-secondary)' }}>
                                                    {relativeTime(note.recorded_at)}
                                                </span>
                                            </div>
                                            {note.attendees.length > 0 && (
                                                <p className="text-[10px] text-gray-400 mb-1">
                                                    {note.attendees.join(', ')}
                                                </p>
                                            )}
                                            <p className="text-xs line-clamp-3" style={{ color: 'var(--color-text-secondary)' }}>
                                                {note.notes}
                                            </p>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </Card.Body>
                    </Card>
                </div>
            </div>

            {activeTransition && (
                <TransitionModal
                    transition={activeTransition}
                    cycleId={cycle.id}
                    onClose={() => setActiveTransition(null)}
                />
            )}
        </AuthenticatedLayout>
    );
}
