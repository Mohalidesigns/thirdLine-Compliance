import { useState, useEffect, useRef } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import PageHeader from '@/Components/PageHeader';
import FilterBar from '@/Components/FilterBar';
import Card from '@/Components/Card';
import DataTable, { Column } from '@/Components/DataTable';
import Pagination from '@/Components/Pagination';
import StatusBadge from '@/Components/StatusBadge';
import StatCard from '@/Components/StatCard';
import EmptyState from '@/Components/EmptyState';
import PrimaryButton from '@/Components/PrimaryButton';
import {
    FireIcon,
    ExclamationTriangleIcon,
    ShieldExclamationIcon,
    BellAlertIcon,
    CurrencyDollarIcon,
    BoltIcon,
} from '@heroicons/react/24/outline';

// ---- types ----

type Severity = 'critical' | 'high' | 'medium' | 'low';

type StatusVariant = 'critical' | 'high' | 'medium' | 'low' | 'info' | 'completed' | 'draft' | 'overdue' | 'ai';

interface IncidentRow {
    id: number;
    code: string;
    title: string;
    category: string;
    severity: Severity;
    status: string;
    status_label: string;
    status_color: string;
    detected_at: string;
    occurred_at: string | null;
    is_data_breach: boolean;
    is_cyber_incident: boolean;
    affects_customers: boolean;
    financial_impact: string | null;
    currency: string | null;
    notifications_pending: number;
    notifications_overdue: number;
    open_actions: number;
    days_open: number;
    assigned_to: { id: number; name: string } | null;
    reported_by: { id: number; name: string } | null;
}

interface SelectOption {
    value: string;
    label: string;
}

interface Props {
    incidents: { data: IncidentRow[]; links: any; meta: any }; // eslint-disable-line @typescript-eslint/no-explicit-any -- Laravel paginator meta shape is dynamic
    stats: {
        total_open: number;
        critical_open: number;
        breaches_open: number;
        notifications_due_today: number;
        notifications_overdue: number;
        loss_ytd_ngn: string;
    };
    filters: {
        search: string | null;
        category: string | null;
        severity: string | null;
        status: string | null;
        basel_category: string | null;
        is_data_breach: boolean | null;
    };
    category_options: SelectOption[];
    severity_options: SelectOption[];
    status_options: SelectOption[];
    basel_options: SelectOption[];
    can: { create: boolean };
}

// ---- helpers ----

const severityVariantMap: Record<Severity, StatusVariant> = {
    critical: 'critical',
    high: 'high',
    medium: 'medium',
    low: 'low',
};

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

// ---- indicator chips inside the title cell ----

function IndicatorChip({ label, color }: { label: string; color: string }) {
    return (
        <span
            className={`inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold border ${color}`}
        >
            {label}
        </span>
    );
}

// ---- page component ----

export default function IncidentsIndex({
    incidents,
    stats,
    filters: initialFilters,
    category_options,
    severity_options,
    status_options,
    basel_options,
    can,
}: Props) {
    const [filterValues, setFilterValues] = useState<Record<string, string>>({
        search: initialFilters.search ?? '',
        category: initialFilters.category ?? '',
        severity: initialFilters.severity ?? '',
        status: initialFilters.status ?? '',
        basel_category: initialFilters.basel_category ?? '',
        is_data_breach: initialFilters.is_data_breach ? '1' : '',
    });

    const [sortKey, setSortKey] = useState('');
    const [sortDir, setSortDir] = useState<'asc' | 'desc'>('asc');

    const debounceRef = useRef<ReturnType<typeof setTimeout> | null>(null);

    useEffect(() => {
        if (debounceRef.current) clearTimeout(debounceRef.current);
        debounceRef.current = setTimeout(() => {
            const params: Record<string, string> = {};
            if (filterValues.search) params.search = filterValues.search;
            if (filterValues.category) params.category = filterValues.category;
            if (filterValues.severity) params.severity = filterValues.severity;
            if (filterValues.status) params.status = filterValues.status;
            if (filterValues.basel_category) params.basel_category = filterValues.basel_category;
            if (filterValues.is_data_breach) params.is_data_breach = filterValues.is_data_breach;
            router.get(route('incidents.index'), params, { preserveState: true, replace: true });
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
        setFilterValues({
            search: '',
            category: '',
            severity: '',
            status: '',
            basel_category: '',
            is_data_breach: '',
        });
    };

    const handleSort = (key: string) => {
        if (sortKey === key) {
            setSortDir((d) => (d === 'asc' ? 'desc' : 'asc'));
        } else {
            setSortKey(key);
            setSortDir('asc');
        }
    };

    const filterConfigs = [
        {
            id: 'search',
            label: 'Search',
            type: 'text' as const,
            placeholder: 'Code or title…',
            flex: 2,
        },
        { id: 'category', label: 'Category', type: 'select' as const, options: category_options },
        { id: 'severity', label: 'Severity', type: 'select' as const, options: severity_options },
        { id: 'status', label: 'Status', type: 'select' as const, options: status_options },
        {
            id: 'basel_category',
            label: 'Basel Category',
            type: 'select' as const,
            options: basel_options,
        },
    ];

    // Pagination meta from the paginator — handle both resource and simple paginator shapes
    const paginationLinks: { url: string | null; label: string; active: boolean }[] =
        incidents.links ?? [];
    const paginationFrom: number = incidents.meta?.from ?? 0;
    const paginationTo: number = incidents.meta?.to ?? 0;
    const paginationTotal: number = incidents.meta?.total ?? 0;

    const columns: Column<IncidentRow>[] = [
        {
            key: 'code',
            header: 'Code',
            sortable: true,
            width: 'w-28',
            render: (row) => (
                <span className="font-mono text-xs text-gray-600">{row.code}</span>
            ),
        },
        {
            key: 'title',
            header: 'Title',
            sortable: true,
            render: (row) => (
                <div>
                    <div className="flex items-center gap-1.5 flex-wrap">
                        <Link
                            href={route('incidents.show', row.id)}
                            onClick={(e) => e.stopPropagation()}
                            className="text-sm font-medium hover:underline focus:outline-none focus:underline"
                            style={{ color: 'var(--color-text-primary)' }}
                        >
                            {row.title}
                        </Link>
                        {row.is_data_breach && (
                            <IndicatorChip
                                label="Breach"
                                color="bg-purple-50 text-purple-700 border-purple-200"
                            />
                        )}
                        {row.is_cyber_incident && (
                            <IndicatorChip
                                label="Cyber"
                                color="bg-red-50 text-red-700 border-red-200"
                            />
                        )}
                        {row.affects_customers && (
                            <IndicatorChip
                                label="Customers"
                                color="bg-orange-50 text-orange-700 border-orange-200"
                            />
                        )}
                    </div>
                    <span className="block text-xs mt-0.5" style={{ color: 'var(--color-text-secondary)' }}>
                        {row.category}
                    </span>
                </div>
            ),
        },
        {
            key: 'severity',
            header: 'Severity',
            sortable: true,
            render: (row) => (
                <StatusBadge
                    variant={severityVariantMap[row.severity]}
                    label={row.severity.charAt(0).toUpperCase() + row.severity.slice(1)}
                />
            ),
        },
        {
            key: 'status',
            header: 'Status',
            sortable: true,
            render: (row) => (
                <StatusBadge
                    variant={statusColorToVariant(row.status_color)}
                    label={row.status_label}
                />
            ),
        },
        {
            key: 'detected_at',
            header: 'Detected',
            sortable: true,
            render: (row) => (
                <span className="text-xs" style={{ color: 'var(--color-text-secondary)' }}>
                    {formatDate(row.detected_at)}
                </span>
            ),
        },
        {
            key: 'notifications',
            header: 'Notifications',
            render: (row) => {
                if (row.notifications_pending === 0 && row.notifications_overdue === 0) {
                    return <span className="text-xs text-gray-400">—</span>;
                }
                return (
                    <div className="text-xs">
                        {row.notifications_pending > 0 && (
                            <span style={{ color: 'var(--color-text-secondary)' }}>
                                {row.notifications_pending} pending
                            </span>
                        )}
                        {row.notifications_overdue > 0 && (
                            <>
                                {row.notifications_pending > 0 && (
                                    <span className="text-gray-400"> / </span>
                                )}
                                <span className="text-red-600 font-medium">
                                    {row.notifications_overdue} overdue
                                </span>
                            </>
                        )}
                    </div>
                );
            },
        },
        {
            key: 'open_actions',
            header: 'Actions',
            render: (row) => (
                <span className="text-xs" style={{ color: 'var(--color-text-secondary)' }}>
                    {row.open_actions > 0 ? (
                        <span className="font-medium" style={{ color: 'var(--color-text-primary)' }}>
                            {row.open_actions} open
                        </span>
                    ) : (
                        '—'
                    )}
                </span>
            ),
        },
        {
            key: 'days_open',
            header: 'Days Open',
            sortable: true,
            render: (row) => {
                const isOverdue = row.status !== 'closed' && row.days_open > 30;
                return (
                    <span
                        className={`text-xs font-medium ${isOverdue ? 'text-red-600' : ''}`}
                        style={!isOverdue ? { color: 'var(--color-text-secondary)' } : undefined}
                    >
                        {row.days_open}
                    </span>
                );
            },
        },
        {
            key: 'assigned_to',
            header: 'Assigned',
            render: (row) => (
                <span className="text-xs" style={{ color: 'var(--color-text-secondary)' }}>
                    {row.assigned_to?.name ?? '—'}
                </span>
            ),
        },
    ];

    return (
        <AuthenticatedLayout>
            <Head title="Incidents" />

            <PageHeader
                title="Incidents"
                subtitle="Incident, breach and operational risk tracker"
                actions={
                    can.create ? (
                        <Link href={route('incidents.create')}>
                            <PrimaryButton type="button">
                                <FireIcon aria-hidden className="w-4 h-4" />
                                Report Incident
                            </PrimaryButton>
                        </Link>
                    ) : undefined
                }
            />

            {/* Stats row */}
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-4 mb-6">
                <StatCard
                    title="Total Open"
                    value={stats.total_open}
                    icon={<FireIcon aria-hidden className="w-5 h-5" />}
                    color="blue"
                />
                <StatCard
                    title="Critical Open"
                    value={stats.critical_open}
                    icon={<ExclamationTriangleIcon aria-hidden className="w-5 h-5" />}
                    color="red"
                />
                <StatCard
                    title="Breaches Open"
                    value={stats.breaches_open}
                    icon={<ShieldExclamationIcon aria-hidden className="w-5 h-5" />}
                    color="purple"
                />
                <StatCard
                    title="Notif. Due Today"
                    value={stats.notifications_due_today}
                    icon={<BellAlertIcon aria-hidden className="w-5 h-5" />}
                    color="amber"
                />
                {stats.notifications_overdue > 0 && (
                    <StatCard
                        title="Notif. Overdue"
                        value={stats.notifications_overdue}
                        icon={<BoltIcon aria-hidden className="w-5 h-5" />}
                        color="red"
                    />
                )}
                <StatCard
                    title="Loss YTD"
                    value={stats.loss_ytd_ngn}
                    icon={<CurrencyDollarIcon aria-hidden className="w-5 h-5" />}
                    color="teal"
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

            {/* Data breach checkbox — outside FilterBar since it's boolean */}
            <div className="mb-4 flex items-center gap-2 px-1">
                <input
                    id="filter-is_data_breach"
                    type="checkbox"
                    checked={filterValues.is_data_breach === '1'}
                    onChange={(e) =>
                        handleFilterChange('is_data_breach', e.target.checked ? '1' : '')
                    }
                    className="rounded border-gray-300 text-primary shadow-sm focus:ring-primary focus:ring-offset-1"
                />
                <label
                    htmlFor="filter-is_data_breach"
                    className="text-sm text-gray-600 select-none cursor-pointer"
                >
                    Data breaches only
                </label>
            </div>

            {/* Table */}
            <Card hover={false} padding="none">
                <DataTable
                    columns={columns}
                    data={incidents.data}
                    sortKey={sortKey}
                    sortDir={sortDir}
                    onSort={handleSort}
                    onRowClick={(row) => router.visit(route('incidents.show', row.id))}
                    emptyState={
                        <EmptyState
                            icon={<FireIcon className="w-8 h-8 text-gray-400" aria-hidden />}
                            title="No incidents found"
                            description="No incidents match your current filters."
                            action={
                                can.create ? (
                                    <Link href={route('incidents.create')}>
                                        <PrimaryButton type="button">Report Incident</PrimaryButton>
                                    </Link>
                                ) : undefined
                            }
                        />
                    }
                />
                <Pagination
                    links={paginationLinks}
                    from={paginationFrom}
                    to={paginationTo}
                    total={paginationTotal}
                />
            </Card>
        </AuthenticatedLayout>
    );
}
