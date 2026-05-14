import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import PageHeader from '@/Components/PageHeader';
import StatCard from '@/Components/StatCard';
import Card from '@/Components/Card';
import DataTable, { Column } from '@/Components/DataTable';
import DonutChart from '@/Components/DonutChart';
import EmptyState from '@/Components/EmptyState';
import SecondaryButton from '@/Components/SecondaryButton';
import { Head } from '@inertiajs/react';
import {
    BookOpenIcon,
    ClipboardDocumentListIcon,
    ShieldExclamationIcon,
    CalendarDaysIcon,
    Squares2X2Icon,
    ArrowPathIcon,
} from '@heroicons/react/24/outline';

interface Stats {
    instruments: number;
    obligations: number;
    sanctions: number;
    deadlines: number;
}

interface RegulatorRow {
    id: number;
    name: string;
    count: number;
}

interface NatureRow {
    id: number;
    nature: string;
    count: number;
}

interface UpcomingRow {
    id: number;
    obligation: string;
    due_date: string;
    status: string;
}

interface Props {
    stats?: Stats;
    byRegulator?: RegulatorRow[];
    byNature?: NatureRow[];
    byRisk?: { label: string; value: number; color: string }[];
    upcoming?: UpcomingRow[];
}

const mockStats: Stats = {
    instruments: 352,
    obligations: 1847,
    sanctions: 417,
    deadlines: 23,
};

const mockByRegulator: RegulatorRow[] = [
    { id: 1, name: 'CBN', count: 89 },
    { id: 2, name: 'SEC', count: 67 },
    { id: 3, name: 'NFIU', count: 54 },
    { id: 4, name: 'NDIC', count: 43 },
    { id: 5, name: 'NAICOM', count: 38 },
    { id: 6, name: 'PenCom', count: 31 },
    { id: 7, name: 'NDPC', count: 18 },
    { id: 8, name: 'FIRS', count: 12 },
];

const mockByNature: NatureRow[] = [
    { id: 1, nature: 'Reporting', count: 412 },
    { id: 2, nature: 'Disclosure', count: 387 },
    { id: 3, nature: 'Compliance', count: 298 },
    { id: 4, nature: 'Governance', count: 201 },
    { id: 5, nature: 'Operational', count: 174 },
];

const mockByRisk = [
    { label: 'Critical', value: 124, color: '#C53030' },
    { label: 'High',     value: 287, color: '#DD6B20' },
    { label: 'Medium',   value: 541, color: '#D4AF37' },
    { label: 'Low',      value: 895, color: '#2D7D46' },
];

const mockUpcoming: UpcomingRow[] = [
    { id: 1, obligation: 'Monthly AML returns', due_date: '2026-05-20', status: 'Pending' },
    { id: 2, obligation: 'Q2 capital adequacy report', due_date: '2026-05-28', status: 'Pending' },
    { id: 3, obligation: 'NDPC data audit', due_date: '2026-06-01', status: 'In Progress' },
    { id: 4, obligation: 'Annual NAICOM filing', due_date: '2026-06-10', status: 'Pending' },
];

const regulatorColumns: Column<RegulatorRow>[] = [
    { key: 'name', header: 'Regulator' },
    { key: 'count', header: 'Instruments' },
];

const natureColumns: Column<NatureRow>[] = [
    { key: 'nature', header: 'Nature' },
    { key: 'count', header: 'Count' },
];

const upcomingColumns: Column<UpcomingRow>[] = [
    { key: 'obligation', header: 'Obligation' },
    { key: 'due_date', header: 'Due Date', sortable: true },
    { key: 'status', header: 'Status' },
];

const currentMonth = new Date().toLocaleString('en-US', { month: 'long', year: 'numeric' });

export default function DashboardIndex({
    stats = mockStats,
    byRegulator = mockByRegulator,
    byNature = mockByNature,
    byRisk = mockByRisk,
    upcoming = mockUpcoming,
}: Props) {
    const isEmpty = stats.instruments === 0 && stats.obligations === 0;

    return (
        <AuthenticatedLayout>
            <Head title="Dashboard" />

            <PageHeader
                title="Dashboard"
                subtitle={`Compliance overview — ${currentMonth}`}
                actions={
                    <SecondaryButton>
                        <ArrowPathIcon aria-hidden className="w-4 h-4" />
                        Refresh
                    </SecondaryButton>
                }
            />

            {isEmpty ? (
                <EmptyState
                    icon={<Squares2X2Icon className="w-8 h-8 text-gray-400" aria-hidden />}
                    title="No data yet"
                    description="Import instruments and link obligations to populate the dashboard."
                    action={
                        <a href="/instruments" className="inline-flex items-center gap-2 rounded-lg border border-transparent bg-primary px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-primary-light transition duration-150">
                            Go to Library
                        </a>
                    }
                />
            ) : (
                <>
                    {/* KPI grid */}
                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                        <StatCard
                            title="Total Instruments"
                            value={stats.instruments}
                            icon={<BookOpenIcon className="w-5 h-5" aria-hidden />}
                            color="blue"
                        />
                        <StatCard
                            title="Open Obligations"
                            value={stats.obligations.toLocaleString()}
                            icon={<ClipboardDocumentListIcon className="w-5 h-5" aria-hidden />}
                            color="amber"
                        />
                        <StatCard
                            title="Sanctions Entries"
                            value={stats.sanctions}
                            icon={<ShieldExclamationIcon className="w-5 h-5" aria-hidden />}
                            color="red"
                        />
                        <StatCard
                            title="Upcoming Deadlines"
                            value={stats.deadlines}
                            icon={<CalendarDaysIcon className="w-5 h-5" aria-hidden />}
                            color="purple"
                        />
                    </div>

                    {/* Widget grid */}
                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <Card hover={false} padding="none">
                            <Card.Header>
                                <h2 className="text-base font-semibold" style={{ color: 'var(--color-text-primary)' }}>
                                    Obligations by Risk Rating
                                </h2>
                            </Card.Header>
                            <Card.Body>
                                <DonutChart segments={byRisk} centerLabel="Risk distribution" />
                            </Card.Body>
                        </Card>

                        <Card hover={false} padding="none">
                            <Card.Header>
                                <h2 className="text-base font-semibold" style={{ color: 'var(--color-text-primary)' }}>
                                    Instruments by Regulator
                                </h2>
                            </Card.Header>
                            <DataTable
                                columns={regulatorColumns}
                                data={byRegulator.slice(0, 10)}
                                emptyState={
                                    <EmptyState
                                        icon={<BookOpenIcon className="w-8 h-8 text-gray-400" aria-hidden />}
                                        title="No data"
                                        description="No instruments loaded yet."
                                    />
                                }
                            />
                        </Card>

                        <Card hover={false} padding="none">
                            <Card.Header>
                                <h2 className="text-base font-semibold" style={{ color: 'var(--color-text-primary)' }}>
                                    Obligations by Nature
                                </h2>
                            </Card.Header>
                            <DataTable
                                columns={natureColumns}
                                data={byNature.slice(0, 10)}
                                emptyState={
                                    <EmptyState
                                        icon={<ClipboardDocumentListIcon className="w-8 h-8 text-gray-400" aria-hidden />}
                                        title="No data"
                                        description="No obligations loaded yet."
                                    />
                                }
                            />
                        </Card>

                        <Card hover={false} padding="none">
                            <Card.Header>
                                <h2 className="text-base font-semibold" style={{ color: 'var(--color-text-primary)' }}>
                                    Calendar — Next 7 Days
                                </h2>
                            </Card.Header>
                            <DataTable
                                columns={upcomingColumns}
                                data={upcoming.slice(0, 7)}
                                emptyState={
                                    <EmptyState
                                        icon={<CalendarDaysIcon className="w-8 h-8 text-gray-400" aria-hidden />}
                                        title="No upcoming deadlines"
                                        description="No obligations due in the next 7 days."
                                    />
                                }
                            />
                        </Card>
                    </div>
                </>
            )}
        </AuthenticatedLayout>
    );
}
