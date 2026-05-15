import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import PageHeader from '@/Components/PageHeader';
import StatCard from '@/Components/StatCard';
import Card from '@/Components/Card';
import DataTable, { Column } from '@/Components/DataTable';
import DonutChart from '@/Components/DonutChart';
import EmptyState from '@/Components/EmptyState';
import SecondaryButton from '@/Components/SecondaryButton';
import { Head, Link } from '@inertiajs/react';
import { useCan } from '@/hooks/usePermission';
import {
    BookOpenIcon,
    ClipboardDocumentListIcon,
    ShieldExclamationIcon,
    CalendarDaysIcon,
    Squares2X2Icon,
    ArrowPathIcon,
    ChartBarIcon,
    WrenchScrewdriverIcon,
    ExclamationTriangleIcon,
    FireIcon,
    DocumentArrowUpIcon,
    AcademicCapIcon,
    ClipboardDocumentCheckIcon,
    BoltIcon,
    ArrowRightIcon,
} from '@heroicons/react/24/outline';

// ---------------------------------------------------------------------------
// Types
// ---------------------------------------------------------------------------

interface Stats {
    instruments: number;
    obligations: number;
    sanctions: number;
    deadlines: number;
}

interface GrcStats {
    open_risks: number | null;
    open_controls: number | null;
    open_issues: number | null;
    open_incidents: number | null;
}

interface OpsStats {
    returns_due: number | null;
    mandatory_outstanding: number | null;
    pending_attestations: number | null;
    open_capa_actions: number | null;
}

interface RecentIncident {
    id: number;
    code: string;
    title: string;
    status: string;
    severity: string;
    detected_at: string | null;
}

interface OutstandingItem {
    type: string;
    label: string;
    url: string;
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
    grcStats?: GrcStats;
    opsStats?: OpsStats;
    recentIncidents?: RecentIncident[] | null;
    outstandingItems?: OutstandingItem[];
    byRegulator?: RegulatorRow[];
    byNature?: NatureRow[];
    byRisk?: { label: string; value: number; color: string }[];
    upcoming?: UpcomingRow[];
}

// ---------------------------------------------------------------------------
// Mock / fallback data (used when the controller passes nothing — dev mode)
// ---------------------------------------------------------------------------

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

// ---------------------------------------------------------------------------
// Table column definitions
// ---------------------------------------------------------------------------

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

// ---------------------------------------------------------------------------
// Severity badge helper
// ---------------------------------------------------------------------------

const SEVERITY_COLORS: Record<string, string> = {
    critical: 'bg-red-100 text-red-800',
    high:     'bg-orange-100 text-orange-800',
    medium:   'bg-amber-100 text-amber-800',
    low:      'bg-green-100 text-green-800',
};

function SeverityBadge({ severity }: { severity: string }) {
    const cls = SEVERITY_COLORS[severity] ?? 'bg-gray-100 text-gray-700';
    return (
        <span className={`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium capitalize ${cls}`}>
            {severity}
        </span>
    );
}

// ---------------------------------------------------------------------------
// Outstanding item icon helper
// ---------------------------------------------------------------------------

const ITEM_ICONS: Record<string, React.ReactNode> = {
    attestation:           <ClipboardDocumentCheckIcon className="w-4 h-4 text-purple-500" aria-hidden />,
    training:              <AcademicCapIcon className="w-4 h-4 text-blue-500" aria-hidden />,
    return:                <DocumentArrowUpIcon className="w-4 h-4 text-amber-500" aria-hidden />,
    incident_notification: <FireIcon className="w-4 h-4 text-red-500" aria-hidden />,
};

// ---------------------------------------------------------------------------
// Component
// ---------------------------------------------------------------------------

const currentMonth = new Date().toLocaleString('en-US', { month: 'long', year: 'numeric' });

export default function DashboardIndex({
    stats = mockStats,
    grcStats,
    opsStats,
    recentIncidents,
    outstandingItems = [],
    byRegulator = mockByRegulator,
    byNature = mockByNature,
    byRisk = mockByRisk,
    upcoming = mockUpcoming,
}: Props) {
    const canViewRisks     = useCan('risks.view');
    const canViewControls  = useCan('controls.view');
    const canViewIssues    = useCan('issues.view');
    const canViewIncidents = useCan('incidents.view');
    const canViewReturns   = useCan('returns.dashboard');
    const canViewTraining  = useCan('training.view');
    const canSignAttest    = useCan('attestations.sign');

    const isEmpty = stats.instruments === 0 && stats.obligations === 0;

    // Whether at least one Row 2 card is visible
    const hasGrcCards = canViewRisks || canViewControls || canViewIssues || canViewIncidents;
    // Whether at least one Row 3 card is visible
    const hasOpsCards = canViewReturns || canViewTraining || canSignAttest;

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
                        <a
                            href="/instruments"
                            className="inline-flex items-center gap-2 rounded-lg border border-transparent bg-primary px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-primary-light transition duration-150"
                        >
                            Go to Library
                        </a>
                    }
                />
            ) : (
                <>
                    {/* -------------------------------------------------------- */}
                    {/* Row 1 — Phase-1 KPIs (always visible)                   */}
                    {/* -------------------------------------------------------- */}
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

                    {/* -------------------------------------------------------- */}
                    {/* Row 2 — GRC KPIs (permission-gated)                     */}
                    {/* -------------------------------------------------------- */}
                    {hasGrcCards && grcStats && (
                        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                            {canViewRisks && grcStats.open_risks !== null && (
                                <StatCard
                                    title="Open Risks"
                                    value={grcStats.open_risks}
                                    icon={<ChartBarIcon className="w-5 h-5" aria-hidden />}
                                    color="red"
                                />
                            )}
                            {canViewControls && grcStats.open_controls !== null && (
                                <StatCard
                                    title="Active Controls"
                                    value={grcStats.open_controls}
                                    icon={<WrenchScrewdriverIcon className="w-5 h-5" aria-hidden />}
                                    color="blue"
                                />
                            )}
                            {canViewIssues && grcStats.open_issues !== null && (
                                <StatCard
                                    title="Open Issues"
                                    value={grcStats.open_issues}
                                    icon={<ExclamationTriangleIcon className="w-5 h-5" aria-hidden />}
                                    color="amber"
                                />
                            )}
                            {canViewIncidents && grcStats.open_incidents !== null && (
                                <StatCard
                                    title="Open Incidents"
                                    value={grcStats.open_incidents}
                                    icon={<FireIcon className="w-5 h-5" aria-hidden />}
                                    color="red"
                                />
                            )}
                        </div>
                    )}

                    {/* -------------------------------------------------------- */}
                    {/* Row 3 — Operations KPIs (permission-gated)               */}
                    {/* -------------------------------------------------------- */}
                    {hasOpsCards && opsStats && (
                        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                            {canViewReturns && opsStats.returns_due !== null && (
                                <StatCard
                                    title="Returns Due This Month"
                                    value={opsStats.returns_due}
                                    icon={<DocumentArrowUpIcon className="w-5 h-5" aria-hidden />}
                                    color="amber"
                                />
                            )}
                            {canViewTraining && opsStats.mandatory_outstanding !== null && (
                                <StatCard
                                    title="Mandatory Training Outstanding"
                                    value={opsStats.mandatory_outstanding}
                                    icon={<AcademicCapIcon className="w-5 h-5" aria-hidden />}
                                    color="blue"
                                />
                            )}
                            {canSignAttest && opsStats.pending_attestations !== null && (
                                <StatCard
                                    title="Pending Attestations"
                                    value={opsStats.pending_attestations}
                                    icon={<ClipboardDocumentCheckIcon className="w-5 h-5" aria-hidden />}
                                    color="purple"
                                />
                            )}
                            {canViewIssues && opsStats.open_capa_actions !== null && (
                                <StatCard
                                    title="Overdue CAPA Actions"
                                    value={opsStats.open_capa_actions}
                                    icon={<BoltIcon className="w-5 h-5" aria-hidden />}
                                    color="red"
                                />
                            )}
                        </div>
                    )}

                    {/* -------------------------------------------------------- */}
                    {/* Two-column contextual widgets                             */}
                    {/* -------------------------------------------------------- */}
                    {(canViewIncidents || outstandingItems.length > 0) && (
                        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                            {/* Recent Incidents */}
                            {canViewIncidents && (
                                <Card hover={false} padding="none">
                                    <Card.Header>
                                        <div className="flex items-center justify-between">
                                            <h2
                                                className="text-base font-semibold"
                                                style={{ color: 'var(--color-text-primary)' }}
                                            >
                                                Recent Incidents
                                            </h2>
                                            <Link
                                                href={route('incidents.index')}
                                                className="inline-flex items-center gap-1 text-xs text-primary hover:underline"
                                            >
                                                View all
                                                <ArrowRightIcon className="w-3 h-3" aria-hidden />
                                            </Link>
                                        </div>
                                    </Card.Header>
                                    <Card.Body>
                                        {!recentIncidents || recentIncidents.length === 0 ? (
                                            <EmptyState
                                                icon={<FireIcon className="w-8 h-8 text-gray-400" aria-hidden />}
                                                title="No incidents"
                                                description="No incidents have been recorded yet."
                                            />
                                        ) : (
                                            <ul className="divide-y divide-gray-100">
                                                {recentIncidents.map((incident) => (
                                                    <li key={incident.id} className="py-3 first:pt-0 last:pb-0">
                                                        <Link
                                                            href={route('incidents.show', incident.id)}
                                                            className="group flex items-start gap-3 rounded hover:bg-gray-50 px-1 -mx-1 transition"
                                                        >
                                                            <div className="flex-1 min-w-0">
                                                                <p className="text-sm font-medium text-gray-900 truncate group-hover:text-primary">
                                                                    {incident.code} — {incident.title}
                                                                </p>
                                                                <p className="text-xs text-gray-500 mt-0.5 capitalize">
                                                                    {incident.status.replace(/_/g, ' ')}
                                                                    {incident.detected_at ? ` · ${incident.detected_at}` : ''}
                                                                </p>
                                                            </div>
                                                            <SeverityBadge severity={incident.severity} />
                                                        </Link>
                                                    </li>
                                                ))}
                                            </ul>
                                        )}
                                    </Card.Body>
                                </Card>
                            )}

                            {/* Outstanding Items */}
                            <Card hover={false} padding="none">
                                <Card.Header>
                                    <h2
                                        className="text-base font-semibold"
                                        style={{ color: 'var(--color-text-primary)' }}
                                    >
                                        Outstanding Items
                                    </h2>
                                </Card.Header>
                                <Card.Body>
                                    {outstandingItems.length === 0 ? (
                                        <EmptyState
                                            icon={<ClipboardDocumentCheckIcon className="w-8 h-8 text-gray-400" aria-hidden />}
                                            title="All clear"
                                            description="No outstanding actions require your attention."
                                        />
                                    ) : (
                                        <ul className="divide-y divide-gray-100">
                                            {outstandingItems.map((item, index) => (
                                                <li key={`${item.type}-${index}`} className="py-3 first:pt-0 last:pb-0">
                                                    <Link
                                                        href={item.url}
                                                        className="group flex items-center gap-3 rounded hover:bg-gray-50 px-1 -mx-1 transition"
                                                    >
                                                        <span className="flex-shrink-0">
                                                            {ITEM_ICONS[item.type] ?? <BoltIcon className="w-4 h-4 text-gray-400" aria-hidden />}
                                                        </span>
                                                        <span className="text-sm text-gray-800 group-hover:text-primary truncate flex-1">
                                                            {item.label}
                                                        </span>
                                                        <ArrowRightIcon className="w-3 h-3 text-gray-400 flex-shrink-0" aria-hidden />
                                                    </Link>
                                                </li>
                                            ))}
                                        </ul>
                                    )}
                                </Card.Body>
                            </Card>
                        </div>
                    )}

                    {/* -------------------------------------------------------- */}
                    {/* Phase-1 widget grid (unchanged)                           */}
                    {/* -------------------------------------------------------- */}
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
