import { useState, useEffect, useRef } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { useCan } from '@/hooks/usePermission';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import PageHeader from '@/Components/PageHeader';
import FilterBar from '@/Components/FilterBar';
import Card from '@/Components/Card';
import DataTable, { Column } from '@/Components/DataTable';
import Pagination from '@/Components/Pagination';
import StatusBadge from '@/Components/StatusBadge';
import EmptyState from '@/Components/EmptyState';
import PrimaryButton from '@/Components/PrimaryButton';
import { ShieldExclamationIcon, PlusIcon, ExclamationCircleIcon } from '@heroicons/react/24/outline';

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
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

interface Props {
    cycles: PaginatedData<Cycle>;
    filters: { search?: string; lob?: string; state?: string; year?: string };
    lobs: { value: string; label: string }[];
    states: { value: string; label: string; color: string }[];
    years: number[];
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

export default function RiskAssessmentsIndex({ cycles, filters: initialFilters, lobs, states, years }: Props) {
    const canCreate = useCan('cycles.create');

    const [filterValues, setFilterValues] = useState<Record<string, string>>({
        search: initialFilters.search ?? '',
        lob:    initialFilters.lob    ?? '',
        state:  initialFilters.state  ?? '',
        year:   initialFilters.year   ?? '',
    });

    const [sortKey, setSortKey] = useState('');
    const [sortDir, setSortDir] = useState<'asc' | 'desc'>('asc');

    const debounceRef = useRef<ReturnType<typeof setTimeout> | null>(null);

    useEffect(() => {
        if (debounceRef.current) clearTimeout(debounceRef.current);
        debounceRef.current = setTimeout(() => {
            const params: Record<string, string> = {};
            if (filterValues.search) params.search = filterValues.search;
            if (filterValues.lob)    params.lob    = filterValues.lob;
            if (filterValues.state)  params.state  = filterValues.state;
            if (filterValues.year)   params.year   = filterValues.year;
            router.get(route('risk-assessments.index'), params, { preserveState: true, replace: true });
        }, 300);
        return () => { if (debounceRef.current) clearTimeout(debounceRef.current); };
    }, [filterValues]);

    const isActive = Object.values(filterValues).some((v) => v !== '');

    const handleFilterChange = (id: string, value: string) => {
        setFilterValues((prev) => ({ ...prev, [id]: value }));
    };

    const handleReset = () => {
        setFilterValues({ search: '', lob: '', state: '', year: '' });
    };

    const handleSort = (key: string) => {
        if (sortKey === key) {
            setSortDir((d) => (d === 'asc' ? 'desc' : 'asc'));
        } else {
            setSortKey(key);
            setSortDir('asc');
        }
    };

    const yearOptions = years.map((y) => ({ value: String(y), label: String(y) }));

    const filterConfigs = [
        { id: 'search', label: 'Search',   type: 'text'   as const, placeholder: 'Name or reference…', flex: 2 },
        { id: 'lob',    label: 'LOB',      type: 'select' as const, options: lobs },
        { id: 'state',  label: 'State',    type: 'select' as const, options: states },
        { id: 'year',   label: 'Year',     type: 'select' as const, options: yearOptions },
    ];

    const columns: Column<Cycle>[] = [
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
            key: 'name',
            header: 'Name',
            sortable: true,
            render: (row) => (
                <div>
                    <span className="text-sm font-medium" style={{ color: 'var(--color-text-primary)' }}>
                        {row.name}
                    </span>
                    <span className="block text-xs mt-0.5" style={{ color: 'var(--color-text-secondary)' }}>
                        {row.methodology} · {row.cycle_year}{row.cycle_quarter ? ` Q${row.cycle_quarter}` : ''}
                    </span>
                </div>
            ),
        },
        {
            key: 'lob',
            header: 'LOB',
            sortable: true,
            render: (row) => (
                <span className="text-sm" style={{ color: 'var(--color-text-secondary)' }}>{row.lob}</span>
            ),
        },
        {
            key: 'state',
            header: 'State',
            sortable: true,
            render: (row) => (
                <StatusBadge
                    variant={stateColorToVariant(row.state_color)}
                    label={row.state_label}
                />
            ),
        },
        {
            key: 'risks_count',
            header: 'Risks',
            sortable: true,
            render: (row) => (
                <div className="flex items-center gap-1.5">
                    <span className="text-sm font-medium" style={{ color: 'var(--color-text-primary)' }}>
                        {row.risks_count}
                    </span>
                    {row.high_risk_count > 0 && (
                        <span className="badge border bg-red-100 text-red-800 border-red-200 text-[10px]">
                            {row.high_risk_count} high
                        </span>
                    )}
                </div>
            ),
        },
        {
            key: 'sla_days_remaining',
            header: 'SLA',
            sortable: true,
            render: (row) => {
                if (row.state === 'closed' || row.sla_days_remaining === null) {
                    return <span className="text-xs text-gray-400">—</span>;
                }
                const isOverdue = row.sla_days_remaining < 0;
                return (
                    <span
                        className={`badge border text-xs ${
                            isOverdue
                                ? 'bg-red-100 text-red-800 border-red-200'
                                : 'bg-gray-100 text-gray-700 border-gray-200'
                        }`}
                    >
                        {isOverdue ? `${Math.abs(row.sla_days_remaining)}d overdue` : `${row.sla_days_remaining}d left`}
                    </span>
                );
            },
        },
        {
            key: 'updated_at',
            header: 'Updated',
            sortable: true,
            render: (row) => (
                <span className="text-xs" style={{ color: 'var(--color-text-secondary)' }}>
                    {relativeTime(row.updated_at)}
                </span>
            ),
        },
    ];

    return (
        <AuthenticatedLayout>
            <Head title="Risk Assessments" />

            <PageHeader
                title="Risk Assessments"
                subtitle="RCSA cycles and risk registers"
                actions={
                    canCreate ? (
                        <Link href={route('risk-assessments.create')}>
                            <PrimaryButton type="button">
                                <PlusIcon aria-hidden className="w-4 h-4" />
                                New Cycle
                            </PrimaryButton>
                        </Link>
                    ) : undefined
                }
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
                    data={cycles.data}
                    sortKey={sortKey}
                    sortDir={sortDir}
                    onSort={handleSort}
                    onRowClick={(row) => router.visit(route('risk-assessments.show', row.id))}
                    emptyState={
                        <EmptyState
                            icon={<ShieldExclamationIcon className="w-8 h-8 text-gray-400" aria-hidden />}
                            title="No assessment cycles yet"
                            description="Create a new RCSA cycle to begin assessing risks across your lines of business."
                            action={
                                canCreate ? (
                                    <Link href={route('risk-assessments.create')}>
                                        <PrimaryButton type="button">
                                            <PlusIcon aria-hidden className="w-4 h-4" />
                                            New Cycle
                                        </PrimaryButton>
                                    </Link>
                                ) : undefined
                            }
                        />
                    }
                />
                <Pagination
                    links={cycles.links}
                    from={cycles.from}
                    to={cycles.to}
                    total={cycles.total}
                />
            </Card>
        </AuthenticatedLayout>
    );
}
