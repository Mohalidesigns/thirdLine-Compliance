import { Head, router, useForm } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import PageHeader from '@/Components/PageHeader';
import Card from '@/Components/Card';
import StatusBadge from '@/Components/StatusBadge';
import PrimaryButton from '@/Components/PrimaryButton';
import FormSection from '@/Components/FormSection';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import InputError from '@/Components/InputError';

// ─── Types ────────────────────────────────────────────────────────────────────

type EnrollmentStatus = 'enrolled' | 'in_progress' | 'completed' | 'overdue' | 'exempted';

interface Training {
    id: number;
    code: string;
    title: string;
    description: string;
    category: string;
    is_mandatory: boolean;
    sla_days: number;
    source: string;
    source_url: string | null;
}

interface Enrollment {
    id: number;
    training: Training;
    enrolled_at: string;
    due_at: string;
    started_at: string | null;
    completed_at: string | null;
    score: number | null;
    status: EnrollmentStatus;
    days_until_due: number | null;
}

interface Props {
    enrollment: Enrollment;
    can: { start: boolean; complete: boolean };
}

// ─── Helpers ──────────────────────────────────────────────────────────────────

function formatDate(iso: string | null): string {
    if (!iso) return '—';
    const d = new Date(iso);
    if (isNaN(d.getTime())) return '—';
    return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}

function capitalize(str: string): string {
    return str.charAt(0).toUpperCase() + str.slice(1);
}

function dueAnnotation(enrollment: Enrollment): string {
    const days = enrollment.days_until_due;
    if (days === null) return '';
    if (days < 0) return `Overdue by ${Math.abs(days)} day${Math.abs(days) === 1 ? '' : 's'}`;
    if (days === 0) return 'Due today';
    return `${days} day${days === 1 ? '' : 's'} remaining`;
}

type StatusVariant = 'draft' | 'info' | 'low' | 'overdue' | 'medium';

function statusVariant(status: EnrollmentStatus): StatusVariant {
    const map: Record<EnrollmentStatus, StatusVariant> = {
        enrolled:    'draft',
        in_progress: 'info',
        completed:   'low',
        overdue:     'overdue',
        exempted:    'medium',
    };
    return map[status];
}

function statusLabel(status: EnrollmentStatus): string {
    const map: Record<EnrollmentStatus, string> = {
        enrolled:    'Enrolled',
        in_progress: 'In Progress',
        completed:   'Completed',
        overdue:     'Overdue',
        exempted:    'Exempted',
    };
    return map[status];
}

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

// ─── Complete form data ───────────────────────────────────────────────────────

interface CompleteFormData {
    score: string; // string so TextInput works; coerce to number on submit
}

// ─── Page ─────────────────────────────────────────────────────────────────────

export default function MyTrainingShow({ enrollment, can }: Props) {
    const { training, status } = enrollment;

    const { data, setData, post, processing, errors } = useForm<CompleteFormData>({
        score: '',
    });

    const handleComplete = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('my.training.complete', enrollment.id));
    };

    const annotation = dueAnnotation(enrollment);
    const isOverdue = status === 'overdue' || (enrollment.days_until_due ?? 0) < 0;

    return (
        <AuthenticatedLayout>
            <Head title={`${training.code} — ${training.title}`} />

            <PageHeader
                title={training.title}
                breadcrumb={[
                    { label: 'My Training', href: route('my.training.index') },
                    { label: training.title },
                ]}
            />

            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                {/* ── Left: Training details ── */}
                <div className="lg:col-span-2 space-y-6">
                    <Card hover={false} padding="none">
                        <Card.Header>
                            <div className="flex items-center gap-2 flex-wrap">
                                <h2
                                    className="text-sm font-semibold"
                                    style={{ color: 'var(--color-text-primary)' }}
                                >
                                    Training Details
                                </h2>
                            </div>
                        </Card.Header>
                        <Card.Body>
                            {/* Badges */}
                            <div className="flex flex-wrap items-center gap-2 mb-4">
                                <StatusBadge variant="info" label={capitalize(training.category)} />
                                {training.is_mandatory && (
                                    <span className="badge border bg-red-50 text-red-700 border-red-200 text-xs">
                                        Mandatory
                                    </span>
                                )}
                            </div>

                            {/* Description */}
                            {training.description ? (
                                <p
                                    className="text-sm whitespace-pre-wrap leading-relaxed"
                                    style={{ color: 'var(--color-text-primary)' }}
                                >
                                    {training.description}
                                </p>
                            ) : (
                                <p
                                    className="text-sm italic"
                                    style={{ color: 'var(--color-text-secondary)' }}
                                >
                                    No description provided.
                                </p>
                            )}

                            {/* External source link */}
                            {training.source !== 'native' && training.source_url && (
                                <a
                                    href={training.source_url}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="inline-flex items-center gap-1.5 mt-4 text-sm text-primary hover:underline focus:outline-none focus:ring-2 focus:ring-primary rounded"
                                >
                                    Open training in {capitalize(training.source)}
                                    <svg
                                        aria-hidden="true"
                                        className="w-4 h-4"
                                        fill="none"
                                        stroke="currentColor"
                                        strokeWidth="2"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            strokeLinecap="round"
                                            strokeLinejoin="round"
                                            d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"
                                        />
                                    </svg>
                                </a>
                            )}
                        </Card.Body>
                    </Card>

                    {/* ── Action panels ── */}

                    {/* Start button */}
                    {can.start && (
                        <div className="flex justify-start">
                            <PrimaryButton
                                type="button"
                                className="!px-6 !py-3 !text-sm"
                                onClick={() =>
                                    router.post(route('my.training.start', enrollment.id), {}, {
                                        preserveScroll: true,
                                    })
                                }
                            >
                                Start Training
                            </PrimaryButton>
                        </div>
                    )}

                    {/* Complete form */}
                    {can.complete && (
                        <form onSubmit={handleComplete} noValidate aria-label="Mark training complete">
                            <FormSection
                                title="Mark as Complete"
                                description="Record your score if applicable. Score is optional for pass/fail trainings."
                            >
                                <div className="sm:col-span-2">
                                    <InputLabel htmlFor="score" value="Score (optional, 0–100)" />
                                    <TextInput
                                        id="score"
                                        type="number"
                                        min={0}
                                        max={100}
                                        step={1}
                                        value={data.score}
                                        onChange={(e) => setData('score', e.target.value)}
                                        hasError={!!errors.score}
                                        placeholder="e.g. 85"
                                        className="mt-1 w-40"
                                        aria-describedby={errors.score ? 'score-error' : undefined}
                                    />
                                    <InputError id="score-error" message={errors.score} />
                                </div>

                                <div className="sm:col-span-2 flex justify-start">
                                    <PrimaryButton type="submit" isLoading={processing}>
                                        Mark Complete
                                    </PrimaryButton>
                                </div>
                            </FormSection>
                        </form>
                    )}

                    {/* Completion confirmation panel */}
                    {status === 'completed' && (
                        <div className="rounded-xl border border-green-200 bg-green-50 p-4 flex flex-col gap-1">
                            <p className="text-sm font-semibold text-green-800">
                                Training completed on {formatDate(enrollment.completed_at)}
                            </p>
                            {enrollment.score !== null && (
                                <p className="text-sm text-green-700">
                                    Score: <span className="font-bold">{enrollment.score}/100</span>
                                </p>
                            )}
                        </div>
                    )}

                    {/* Exemption panel */}
                    {status === 'exempted' && (
                        <div className="rounded-xl border border-amber-200 bg-amber-50 p-4">
                            <p className="text-sm font-semibold text-amber-800">
                                Exempted
                            </p>
                            <p className="text-sm text-amber-700 mt-0.5">
                                You have been granted an exemption for this training.
                            </p>
                        </div>
                    )}
                </div>

                {/* ── Right: Enrollment status ── */}
                <div className="space-y-6">
                    <Card hover={false} padding="none">
                        <Card.Header>
                            <h2
                                className="text-sm font-semibold"
                                style={{ color: 'var(--color-text-primary)' }}
                            >
                                Enrollment
                            </h2>
                        </Card.Header>
                        <Card.Body>
                            <div className="divide-y divide-gray-100">
                                <DetailItem label="Status">
                                    <StatusBadge
                                        variant={statusVariant(status)}
                                        label={statusLabel(status)}
                                        size="sm"
                                    />
                                </DetailItem>
                                <DetailItem label="Enrolled">
                                    {formatDate(enrollment.enrolled_at)}
                                </DetailItem>
                                <DetailItem label="Due">
                                    <span className={isOverdue ? 'text-red-600 font-medium' : ''}>
                                        {formatDate(enrollment.due_at)}
                                    </span>
                                </DetailItem>
                                {annotation && status !== 'completed' && status !== 'exempted' && (
                                    <DetailItem label="Remaining">
                                        <span className={isOverdue ? 'text-red-600 font-medium' : ''}>
                                            {annotation}
                                        </span>
                                    </DetailItem>
                                )}
                                {enrollment.started_at && (
                                    <DetailItem label="Started">
                                        {formatDate(enrollment.started_at)}
                                    </DetailItem>
                                )}
                                {enrollment.completed_at && (
                                    <DetailItem label="Completed">
                                        {formatDate(enrollment.completed_at)}
                                    </DetailItem>
                                )}
                                {enrollment.score !== null && (
                                    <DetailItem label="Score">
                                        <span className="font-semibold text-green-700">
                                            {enrollment.score}/100
                                        </span>
                                    </DetailItem>
                                )}
                                <DetailItem label="SLA Days">{training.sla_days} days</DetailItem>
                                <DetailItem label="Code">
                                    <span className="font-mono">{training.code}</span>
                                </DetailItem>
                            </div>
                        </Card.Body>
                    </Card>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
