import { Head, Link } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import PageHeader from '@/Components/PageHeader';
import Card from '@/Components/Card';
import StatCard from '@/Components/StatCard';
import StatusBadge from '@/Components/StatusBadge';
import SecondaryButton from '@/Components/SecondaryButton';
import {
    DocumentArrowUpIcon,
    CalendarDaysIcon,
    CheckCircleIcon,
    ClockIcon,
    ExclamationTriangleIcon,
    ChartBarIcon,
} from '@heroicons/react/24/outline';

// ---- types ----

type StatusVariant = 'critical' | 'high' | 'medium' | 'low' | 'info' | 'completed' | 'draft' | 'overdue' | 'ai';

interface UpcomingRun {
    id: number;
    code: string;
    title: string;
    regulator: string;
    regulator_label: string;
    period_label: string;
    due_at: string;
    days_until_due: number;
    status: string;
    status_label: string;
    status_color: string;
}

interface RecentSubmission {
    id: number;
    code: string;
    title: string;
    period_label: string;
    submitted_at: string;
    submission_reference: string | null;
    regulator_label: string;
    acknowledged: boolean;
}

interface ByRegulator {
    regulator: string;
    label: string;
    due: number;
    submitted: number;
    late: number;
    on_time_rate: number;
}

interface Props {
    stats: {
        total_active_definitions: number;
        runs_due_this_month: number;
        runs_submitted_this_month: number;
        runs_acknowledged_this_month: number;
        runs_late: number;
        on_time_rate_30d: number;
    };
    by_regulator: ByRegulator[];
    upcoming_runs: UpcomingRun[];
    recent_submissions: RecentSubmission[];
    can: {
        view_runs: boolean;
        manage_definitions: boolean;
    };
}

// ---- helpers ----

function statusColorToVariant(color: string): StatusVariant {
    const map: Record<string, StatusVariant> = {
        blue: 'info',
        red: 'critical',
        green: 'low',
        yellow: 'medium',
        orange: 'high',
        gray: 'draft',
        purple: 'completed',
    };
    return map[color] ?? 'draft';
}

function formatDate(dateStr: string | null): string {
    if (!dateStr) return '—';
    const d = new Date(dateStr);
    if (isNaN(d.getTime())) return '—';
    return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}

function formatDateTime(dateStr: string | null): string {
    if (!dateStr) return '—';
    const d = new Date(dateStr);
    if (isNaN(d.getTime())) return '—';
    return d.toLocaleString('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function onTimeRateColor(rate: number): string {
    if (rate >= 0.95) return 'text-green-600';
    if (rate >= 0.80) return 'text-yellow-600';
    return 'text-red-600';
}

function onTimeRateBadgeVariant(rate: number): StatusVariant {
    if (rate >= 0.95) return 'low';       // green
    if (rate >= 0.80) return 'medium';    // yellow
    return 'critical';                     // red
}

function daysDueClass(days: number): string {
    if (days <= 3) return 'text-red-600 font-semibold';
    if (days <= 14) return 'text-yellow-600 font-medium';
    return '';
}

// ---- sub-components ----

function ByRegulatorTable({ rows }: { rows: ByRegulator[] }) {
    if (rows.length === 0) {
        return (
            <p className="text-sm text-gray-400 text-center py-8">No regulator data available.</p>
        );
    }

    return (
        <div className="overflow-x-auto">
            <table className="w-full text-sm" role="table">
                <thead>
                    <tr className="border-b border-gray-100">
                        <th scope="col" className="text-left text-xs font-semibold text-gray-500 uppercase tracking-wider pb-3 pr-4">
                            Regulator
                        </th>
                        <th scope="col" className="text-right text-xs font-semibold text-gray-500 uppercase tracking-wider pb-3 px-2">
                            Due
                        </th>
                        <th scope="col" className="text-right text-xs font-semibold text-gray-500 uppercase tracking-wider pb-3 px-2">
                            Submitted
                        </th>
                        <th scope="col" className="text-right text-xs font-semibold text-gray-500 uppercase tracking-wider pb-3 px-2">
                            Late
                        </th>
                        <th scope="col" className="text-right text-xs font-semibold text-gray-500 uppercase tracking-wider pb-3 pl-2">
                            On-Time
                        </th>
                    </tr>
                </thead>
                <tbody className="divide-y divide-gray-50">
                    {rows.map((row) => (
                        <tr key={row.regulator} className="hover:bg-gray-50 transition-colors">
                            <td className="py-3 pr-4">
                                <span className="font-medium text-gray-800">{row.label}</span>
                                <span className="block text-xs text-gray-400 font-mono">{row.regulator}</span>
                            </td>
                            <td className="py-3 px-2 text-right text-gray-700">{row.due}</td>
                            <td className="py-3 px-2 text-right text-gray-700">{row.submitted}</td>
                            <td className="py-3 px-2 text-right">
                                {row.late > 0 ? (
                                    <span className="text-red-600 font-medium">{row.late}</span>
                                ) : (
                                    <span className="text-gray-400">0</span>
                                )}
                            </td>
                            <td className="py-3 pl-2 text-right">
                                <StatusBadge
                                    variant={onTimeRateBadgeVariant(row.on_time_rate)}
                                    label={`${Math.round(row.on_time_rate * 100)}%`}
                                    size="sm"
                                />
                            </td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}

function UpcomingRunsList({ runs }: { runs: UpcomingRun[] }) {
    if (runs.length === 0) {
        return (
            <p className="text-sm text-gray-400 text-center py-8">No upcoming runs.</p>
        );
    }

    return (
        <ul className="divide-y divide-gray-50" role="list">
            {runs.map((run) => (
                <li key={run.id}>
                    <Link
                        href={route('returns.runs.show', run.id)}
                        className="flex items-start justify-between gap-4 py-3 px-1 rounded-lg hover:bg-gray-50 transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-primary"
                    >
                        <div className="flex-1 min-w-0">
                            <div className="flex items-center gap-2 flex-wrap mb-0.5">
                                <span className="font-mono text-xs text-gray-500">{run.code}</span>
                                <StatusBadge
                                    variant={statusColorToVariant(run.status_color)}
                                    label={run.status_label}
                                    size="sm"
                                />
                            </div>
                            <p
                                className="text-sm font-medium truncate"
                                style={{ color: 'var(--color-text-primary)' }}
                            >
                                {run.title}
                            </p>
                            <p className="text-xs mt-0.5" style={{ color: 'var(--color-text-secondary)' }}>
                                {run.regulator_label} · {run.period_label}
                            </p>
                        </div>
                        <div className="flex-shrink-0 text-right">
                            <p className="text-xs text-gray-400">{formatDate(run.due_at)}</p>
                            <p className={`text-xs mt-0.5 ${daysDueClass(run.days_until_due)}`}>
                                {run.days_until_due <= 0
                                    ? 'Overdue'
                                    : run.days_until_due === 1
                                    ? '1 day left'
                                    : `${run.days_until_due} days`}
                            </p>
                        </div>
                    </Link>
                </li>
            ))}
        </ul>
    );
}

function RecentSubmissionsList({ submissions }: { submissions: RecentSubmission[] }) {
    if (submissions.length === 0) {
        return (
            <p className="text-sm text-gray-400 text-center py-8">No recent submissions.</p>
        );
    }

    return (
        <ul className="divide-y divide-gray-50" role="list">
            {submissions.map((sub) => (
                <li key={sub.id} className="py-3">
                    <div className="flex items-start justify-between gap-4">
                        <div className="flex-1 min-w-0">
                            <div className="flex items-center gap-2 flex-wrap mb-0.5">
                                <span className="font-mono text-xs text-gray-500">{sub.code}</span>
                                {sub.acknowledged ? (
                                    <span className="inline-flex items-center gap-1 text-[10px] font-semibold px-1.5 py-0.5 rounded-full bg-green-100 text-green-700 border border-green-200">
                                        <CheckCircleIcon aria-hidden className="w-3 h-3" />
                                        Acknowledged
                                    </span>
                                ) : (
                                    <span className="inline-flex items-center gap-1 text-[10px] font-semibold px-1.5 py-0.5 rounded-full bg-yellow-100 text-yellow-700 border border-yellow-200">
                                        <ClockIcon aria-hidden className="w-3 h-3" />
                                        Pending Ack.
                                    </span>
                                )}
                            </div>
                            <p
                                className="text-sm font-medium truncate"
                                style={{ color: 'var(--color-text-primary)' }}
                            >
                                {sub.title}
                            </p>
                            <p className="text-xs mt-0.5" style={{ color: 'var(--color-text-secondary)' }}>
                                {sub.regulator_label} · {sub.period_label}
                            </p>
                        </div>
                        <div className="flex-shrink-0 text-right text-xs text-gray-400">
                            <p>{formatDateTime(sub.submitted_at)}</p>
                            {sub.submission_reference && (
                                <p className="font-mono text-gray-500 mt-0.5">
                                    {sub.submission_reference}
                                </p>
                            )}
                        </div>
                    </div>
                </li>
            ))}
        </ul>
    );
}

// ---- main component ----

export default function ReturnsDashboard({
    stats,
    by_regulator,
    upcoming_runs,
    recent_submissions,
    can,
}: Props) {
    const onTimeRatePct = Math.round(stats.on_time_rate_30d * 100);
    const onTimeColor = onTimeRateColor(stats.on_time_rate_30d);

    return (
        <AuthenticatedLayout>
            <Head title="Regulatory Returns" />

            <PageHeader
                title="Regulatory Returns"
                subtitle="Monitor and manage all regulatory return submissions"
                actions={
                    can.view_runs ? (
                        <Link href={route('returns.runs.index')}>
                            <SecondaryButton type="button">
                                <DocumentArrowUpIcon aria-hidden className="w-4 h-4" />
                                View All Runs
                            </SecondaryButton>
                        </Link>
                    ) : undefined
                }
            />

            {/* Stats row — 6 cards */}
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-4 mb-6">
                <StatCard
                    title="Active Definitions"
                    value={stats.total_active_definitions}
                    icon={<DocumentArrowUpIcon aria-hidden className="w-5 h-5" />}
                    color="blue"
                />
                <StatCard
                    title="Due This Month"
                    value={stats.runs_due_this_month}
                    icon={<CalendarDaysIcon aria-hidden className="w-5 h-5" />}
                    color="amber"
                />
                <StatCard
                    title="Submitted This Month"
                    value={stats.runs_submitted_this_month}
                    icon={<CheckCircleIcon aria-hidden className="w-5 h-5" />}
                    color="green"
                />
                <StatCard
                    title="Acknowledged"
                    value={stats.runs_acknowledged_this_month}
                    icon={<CheckCircleIcon aria-hidden className="w-5 h-5" />}
                    color="teal"
                />
                {stats.runs_late > 0 && (
                    <StatCard
                        title="Late"
                        value={stats.runs_late}
                        icon={<ExclamationTriangleIcon aria-hidden className="w-5 h-5" />}
                        color="red"
                    />
                )}
                <article
                    aria-label={`30-day On-Time Rate: ${onTimeRatePct}%`}
                    className="stat-card flex items-center justify-between"
                >
                    <div>
                        <p className="text-sm font-medium text-gray-500">30-day On-Time Rate</p>
                        <p className={`text-2xl font-bold mt-1 ${onTimeColor}`}>
                            {onTimeRatePct}%
                        </p>
                    </div>
                    <div className="w-10 h-10 rounded-lg flex items-center justify-center bg-blue-100 text-blue-600">
                        <ChartBarIcon aria-hidden className="w-5 h-5" />
                    </div>
                </article>
            </div>

            {/* Two-column layout */}
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {/* Left: By Regulator */}
                <div>
                    <Card hover={false} padding="none">
                        <Card.Header>
                            <h2
                                className="text-sm font-semibold"
                                style={{ color: 'var(--color-text-primary)' }}
                            >
                                By Regulator
                            </h2>
                        </Card.Header>
                        <Card.Body>
                            <ByRegulatorTable rows={by_regulator} />
                        </Card.Body>
                    </Card>
                </div>

                {/* Right: Upcoming + Recent */}
                <div className="space-y-6">
                    <Card hover={false} padding="none">
                        <Card.Header>
                            <h2
                                className="text-sm font-semibold"
                                style={{ color: 'var(--color-text-primary)' }}
                            >
                                Upcoming Runs
                            </h2>
                        </Card.Header>
                        <Card.Body>
                            <UpcomingRunsList runs={upcoming_runs.slice(0, 8)} />
                        </Card.Body>
                    </Card>

                    <Card hover={false} padding="none">
                        <Card.Header>
                            <h2
                                className="text-sm font-semibold"
                                style={{ color: 'var(--color-text-primary)' }}
                            >
                                Recent Submissions
                            </h2>
                        </Card.Header>
                        <Card.Body>
                            <RecentSubmissionsList submissions={recent_submissions.slice(0, 5)} />
                        </Card.Body>
                    </Card>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
