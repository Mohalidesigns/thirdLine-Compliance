import { useState, useEffect, useRef, useMemo } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import PageHeader from '@/Components/PageHeader';
import StatCard from '@/Components/StatCard';
import FilterBar from '@/Components/FilterBar';
import Card from '@/Components/Card';
import DataTable, { Column } from '@/Components/DataTable';
import StatusBadge from '@/Components/StatusBadge';
import EmptyState from '@/Components/EmptyState';
import PrimaryButton from '@/Components/PrimaryButton';
import {
    AcademicCapIcon,
    CheckCircleIcon,
    ArrowPathIcon,
    ExclamationTriangleIcon,
} from '@heroicons/react/24/outline';

// ─── Types ────────────────────────────────────────────────────────────────────

type EnrollmentStatus = 'enrolled' | 'in_progress' | 'completed' | 'overdue' | 'exempted';

interface Training {
    id: number;
    code: string;
    title: string;
    category: string;
    is_mandatory: boolean;
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
    days_until_due: number | null; // negative = overdue
}

interface Stats {
    total: number;
    completed: number;
    in_progress: number;
    overdue: number;
    completion_rate: number; // 0–1
}

interface Props {
    enrollments: Enrollment[];
    stats: Stats;
    filters: { status: string | null; category: string | null };
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

/** Returns a human-readable due-date annotation. */
function dueAnnotation(enrollment: Enrollment): string {
    if (enrollment.status === 'completed') return 'Completed';
    if (enrollment.status === 'exempted') return 'Exempted';
    const days = enrollment.days_until_due;
    if (days === null) return '';
    if (days < 0) return `Overdue ${Math.abs(days)} day${Math.abs(days) === 1 ? '' : 's'}`;
    if (days === 0) return 'Due today';
    return `${days} day${days === 1 ? '' : 's'} left`;
}

// Maps our enrollment statuses to the existing StatusBadge variants.
// StatusBadge does not have 'enrolled' or 'in_progress' variants, so we map:
//   enrolled   → draft  (neutral gray)
//   in_progress → info   (blue)
//   completed  → low    (green)
//   overdue    → overdue (red)
//   exempted   → medium  (yellow)
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

// ─── Page ─────────────────────────────────────────────────────────────────────

export default function MyTrainingIndex({ enrollments, stats, filters: initialFilters }: Props) {
    const [filterValues, setFilterValues] = useState<Record<string, string>>({
        status:   initialFilters.status   ?? '',
        category: initialFilters.category ?? '',
    });

    const debounceRef = useRef<ReturnType<typeof setTimeout> | null>(null);

    // Derive distinct categories from enrollments for the filter dropdown.
    const categoryOptions = useMemo(() => {
        const seen = new Set<string>();
        enrollments.forEach((e) => {
            if (e.training.category) seen.add(e.training.category);
        });
        return Array.from(seen).sort().map((c) => ({ value: c, label: capitalize(c) }));
    }, [enrollments]);

    useEffect(() => {
        if (debounceRef.current) clearTimeout(debounceRef.current);
        debounceRef.current = setTimeout(() => {
            const params: Record<string, string> = {};
            if (filterValues.status)   params.status   = filterValues.status;
            if (filterValues.category) params.category = filterValues.category;
            router.get(route('my.training.index'), params, { preserveState: true, replace: true });
        }, 300);
        return () => {
            if (debounceRef.current) clearTimeout(debounceRef.current);
        };
    }, [filterValues]);

    const isActive = Object.values(filterValues).some((v) => v !== '');

    const handleFilterChange = (id: string, value: string) => {
        setFilterValues((prev) => ({ ...prev, [id]: value }));
    };

    const handleReset = () => {
        setFilterValues({ status: '', category: '' });
    };

    // Completion rate → color for the Completed StatCard.
    const completionRate = stats.completion_rate ?? 0;
    const completedColor: 'green' | 'amber' | 'red' =
        completionRate >= 0.8 ? 'green' : completionRate >= 0.5 ? 'amber' : 'red';

    const filterConfigs = [
        {
            id: 'status',
            label: 'Status',
            type: 'select' as const,
            options: [
                { value: 'enrolled',    label: 'Enrolled' },
                { value: 'in_progress', label: 'In Progress' },
                { value: 'completed',   label: 'Completed' },
                { value: 'overdue',     label: 'Overdue' },
                { value: 'exempted',    label: 'Exempted' },
            ],
        },
        {
            id: 'category',
            label: 'Category',
            type: 'select' as const,
            options: categoryOptions,
        },
    ];

    const columns: Column<Enrollment>[] = [
        {
            key: 'training',
            header: 'Training',
            render: (row) => (
                <div>
                    <Link
                        href={route('my.training.show', row.id)}
                        className="text-sm font-semibold hover:underline focus:outline-none focus:ring-2 focus:ring-primary rounded"
                        style={{ color: 'var(--color-text-primary)' }}
                    >
                        {row.training.title}
                    </Link>
                    <div className="flex items-center gap-2 mt-0.5">
                        <span className="font-mono text-xs" style={{ color: 'var(--color-text-secondary)' }}>
                            {row.training.code}
                        </span>
                        {row.training.is_mandatory && (
                            <span className="badge border bg-red-50 text-red-700 border-red-200 text-[10px]">
                                Mandatory
                            </span>
                        )}
                    </div>
                </div>
            ),
        },
        {
            key: 'category',
            header: 'Category',
            render: (row) => (
                <span className="text-sm" style={{ color: 'var(--color-text-secondary)' }}>
                    {capitalize(row.training.category)}
                </span>
            ),
        },
        {
            key: 'status',
            header: 'Status',
            render: (row) => (
                <StatusBadge
                    variant={statusVariant(row.status)}
                    label={statusLabel(row.status)}
                />
            ),
        },
        {
            key: 'due_at',
            header: 'Due',
            render: (row) => {
                const annotation = dueAnnotation(row);
                const isOverdue = row.status === 'overdue' || (row.days_until_due ?? 0) < 0;
                return (
                    <div>
                        <span className="text-sm" style={{ color: 'var(--color-text-primary)' }}>
                            {formatDate(row.due_at)}
                        </span>
                        {annotation && (
                            <span
                                className={`block text-xs mt-0.5 ${
                                    isOverdue
                                        ? 'text-red-600'
                                        : row.status === 'completed'
                                        ? 'text-green-600'
                                        : ''
                                }`}
                                style={
                                    !isOverdue && row.status !== 'completed'
                                        ? { color: 'var(--color-text-secondary)' }
                                        : undefined
                                }
                            >
                                {annotation}
                            </span>
                        )}
                    </div>
                );
            },
        },
        {
            key: 'score',
            header: 'Score',
            render: (row) =>
                row.status === 'completed' && row.score !== null ? (
                    <span className="text-sm font-medium text-green-700">{row.score}/100</span>
                ) : (
                    <span className="text-sm" style={{ color: 'var(--color-text-secondary)' }}>
                        —
                    </span>
                ),
        },
        {
            key: 'id',
            header: 'Action',
            render: (row) => (
                <div className="flex items-center gap-2">
                    <Link
                        href={route('my.training.show', row.id)}
                        className="text-xs font-medium text-primary hover:underline focus:outline-none focus:ring-2 focus:ring-primary rounded"
                    >
                        View
                    </Link>
                    {row.status === 'enrolled' && (
                        <PrimaryButton
                            type="button"
                            className="!py-1 !px-2 !text-[10px]"
                            onClick={() =>
                                router.post(route('my.training.start', row.id), {}, {
                                    preserveScroll: true,
                                })
                            }
                        >
                            Start
                        </PrimaryButton>
                    )}
                </div>
            ),
        },
    ];

    return (
        <AuthenticatedLayout>
            <Head title="My Training" />

            <PageHeader title="My Training" subtitle="Track your compliance training enrollments." />

            {/* Stats row */}
            <div className="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <StatCard
                    title="Total Enrollments"
                    value={stats.total}
                    color="blue"
                    icon={<AcademicCapIcon aria-hidden className="w-5 h-5" />}
                />
                <StatCard
                    title="Completed"
                    value={stats.completed}
                    color={completedColor}
                    icon={<CheckCircleIcon aria-hidden className="w-5 h-5" />}
                    trend={{
                        value: `${Math.round(completionRate * 100)}% completion rate`,
                        direction: completionRate >= 0.8 ? 'up' : completionRate >= 0.5 ? 'neutral' : 'down',
                    }}
                />
                <StatCard
                    title="In Progress"
                    value={stats.in_progress}
                    color="teal"
                    icon={<ArrowPathIcon aria-hidden className="w-5 h-5" />}
                />
                <StatCard
                    title="Overdue"
                    value={stats.overdue}
                    color="red"
                    icon={<ExclamationTriangleIcon aria-hidden className="w-5 h-5" />}
                />
            </div>

            {/* Filters */}
            <FilterBar
                filters={filterConfigs}
                values={filterValues}
                onChange={handleFilterChange}
                onReset={handleReset}
                isActive={isActive}
            />

            {/* Table */}
            <Card hover={false} padding="none">
                <DataTable
                    columns={columns}
                    data={enrollments}
                    emptyState={
                        <EmptyState
                            icon={<AcademicCapIcon className="w-8 h-8 text-gray-400" aria-hidden />}
                            title="No training assigned to you yet."
                            description="When your administrator assigns training, it will appear here."
                        />
                    }
                />
            </Card>
        </AuthenticatedLayout>
    );
}
