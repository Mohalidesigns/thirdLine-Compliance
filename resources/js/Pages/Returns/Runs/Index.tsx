import { useState, useEffect, useRef } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import PageHeader from '@/Components/PageHeader';
import FilterBar from '@/Components/FilterBar';
import Card from '@/Components/Card';
import DataTable, { Column } from '@/Components/DataTable';
import Pagination from '@/Components/Pagination';
import StatusBadge from '@/Components/StatusBadge';
import EmptyState from '@/Components/EmptyState';
import { DocumentArrowUpIcon } from '@heroicons/react/24/outline';

// ---- types ----

// eslint-disable-next-line @typescript-eslint/no-explicit-any -- Laravel paginator shape is dynamic
type AnyMeta = any;

type StatusVariant = 'critical' | 'high' | 'medium' | 'low' | 'info' | 'completed' | 'draft' | 'overdue' | 'ai';

interface RunRow {
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
    maker_name: string | null;
    checker_name: string | null;
    approver_name: string | null;
    submitted_at: string | null;
    acknowledged_at: string | null;
    submission_reference: string | null;
    is_overdue: boolean;
}

interface SelectOption {
    value: string;
    label: string;
}

interface Props {
    runs: { data: RunRow[]; links: AnyMeta; meta: AnyMeta };
    filters: {
        search: string | null;
        regulator: string | null;
        frequency: string | null;
        status: string | null;
    };
    regulator_options: SelectOption[];
    frequency_options: SelectOption[];
    status_options: SelectOption[];
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

// ---- page component ----

export default function ReturnRunsIndex({
    runs,
    filters: initialFilters,
    regulator_options,
    frequency_options,
    status_options,
}: Props) {
    const [filterValues, setFilterValues] = useState<Record<string, string>>({
        search: initialFilters.search ?? '',
        regulator: initialFilters.regulator ?? '',
        frequency: initialFilters.frequency ?? '',
        status: initialFilters.status ?? '',
    });

    const debounceRef = useRef<ReturnType<typeof setTimeout> | null>(null);

    useEffect(() => {
        if (debounceRef.current) clearTimeout(debounceRef.current);
        debounceRef.current = setTimeout(() => {
            const params: Record<string, string> = {};
            if (filterValues.search) params.search = filterValues.search;
            if (filterValues.regulator) params.regulator = filterValues.regulator;
            if (filterValues.frequency) params.frequency = filterValues.frequency;
            if (filterValues.status) params.status = filterValues.status;
            router.get(route('returns.runs.index'), params, { preserveState: true, replace: true });
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
        setFilterValues({ search: '', regulator: '', frequency: '', status: '' });
    };

    const filterConfigs = [
        { id: 'search', label: 'Search', type: 'text' as const, placeholder: 'Code or title…', flex: 2 },
        { id: 'regulator', label: 'Regulator', type: 'select' as const, options: regulator_options },
        { id: 'frequency', label: 'Frequency', type: 'select' as const, options: frequency_options },
        { id: 'status', label: 'Status', type: 'select' as const, options: status_options },
    ];

    const paginationLinks: { url: string | null; label: string; active: boolean }[] = runs.links ?? [];
    const paginationFrom: number = runs.meta?.from ?? 0;
    const paginationTo: number = runs.meta?.to ?? 0;
    const paginationTotal: number = runs.meta?.total ?? 0;

    const columns: Column<RunRow>[] = [
        {
            key: 'code',
            header: 'Code',
            width: 'w-28',
            render: (row) => (
                <span className="font-mono text-xs text-gray-600">{row.code}</span>
            ),
        },
        {
            key: 'title',
            header: 'Title',
            render: (row) => (
                <div>
                    <Link
                        href={route('returns.runs.show', row.id)}
                        onClick={(e) => e.stopPropagation()}
                        className="text-sm font-medium hover:underline focus:outline-none focus:underline"
                        style={{ color: 'var(--color-text-primary)' }}
                    >
                        {row.title}
                    </Link>
                    <span
                        className="block text-xs mt-0.5"
                        style={{ color: 'var(--color-text-secondary)' }}
                    >
                        {row.period_label}
                    </span>
                </div>
            ),
        },
        {
            key: 'regulator_label',
            header: 'Regulator',
            render: (row) => (
                <span className="text-xs" style={{ color: 'var(--color-text-secondary)' }}>
                    {row.regulator_label}
                </span>
            ),
        },
        {
            key: 'status',
            header: 'Status',
            render: (row) => (
                <StatusBadge
                    variant={statusColorToVariant(row.status_color)}
                    label={row.status_label}
                />
            ),
        },
        {
            key: 'due_at',
            header: 'Due',
            render: (row) => (
                <span
                    className={`text-xs ${row.is_overdue ? 'text-red-600 font-semibold' : ''}`}
                    style={!row.is_overdue ? { color: 'var(--color-text-secondary)' } : undefined}
                >
                    {formatDate(row.due_at)}
                    {row.is_overdue && (
                        <span className="block text-[10px] font-normal">Overdue</span>
                    )}
                </span>
            ),
        },
        {
            key: 'maker_name',
            header: 'Maker / Checker / Approver',
            render: (row) => (
                <div className="text-xs space-y-0.5" style={{ color: 'var(--color-text-secondary)' }}>
                    <div>
                        <span className="text-[10px] uppercase tracking-wider text-gray-400">M:</span>{' '}
                        {row.maker_name ?? <span className="text-gray-300">—</span>}
                    </div>
                    <div>
                        <span className="text-[10px] uppercase tracking-wider text-gray-400">C:</span>{' '}
                        {row.checker_name ?? <span className="text-gray-300">—</span>}
                    </div>
                    <div>
                        <span className="text-[10px] uppercase tracking-wider text-gray-400">A:</span>{' '}
                        {row.approver_name ?? <span className="text-gray-300">—</span>}
                    </div>
                </div>
            ),
        },
        {
            key: 'submitted_at',
            header: 'Submitted',
            render: (row) => (
                <span className="text-xs" style={{ color: 'var(--color-text-secondary)' }}>
                    {formatDate(row.submitted_at)}
                </span>
            ),
        },
        {
            key: 'submission_reference',
            header: 'Reference',
            render: (row) => (
                <span className="text-xs font-mono text-gray-500">
                    {row.submission_reference ?? '—'}
                </span>
            ),
        },
    ];

    return (
        <AuthenticatedLayout>
            <Head title="Return Runs" />

            <PageHeader
                title="Return Runs"
                subtitle="All regulatory return submission runs"
                breadcrumb={[
                    { label: 'Returns', href: route('returns.dashboard') },
                    { label: 'Runs' },
                ]}
            />

            <FilterBar
                filters={filterConfigs}
                values={filterValues}
                onChange={handleFilterChange}
                onReset={handleReset}
                isActive={isActive}
            />

            <Card hover={false} padding="none">
                <DataTable
                    columns={columns}
                    data={runs.data}
                    onRowClick={(row) => router.visit(route('returns.runs.show', row.id))}
                    emptyState={
                        <EmptyState
                            icon={
                                <DocumentArrowUpIcon
                                    className="w-8 h-8 text-gray-400"
                                    aria-hidden
                                />
                            }
                            title="No return runs found"
                            description="No runs match your current filters. Runs are generated automatically based on return definitions."
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
