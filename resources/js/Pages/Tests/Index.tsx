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
import { ClipboardDocumentCheckIcon } from '@heroicons/react/24/outline';

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

interface Test {
    id: number;
    control: { id: number; reference: string; title: string };
    tested_at: string;
    tested_by_name: string;
    outcome: 'passed' | 'partial' | 'failed' | 'not_applicable';
    sample_size: number | null;
    findings: string | null;
}

interface Props {
    tests: PaginatedData<Test>;
    filters: { control?: string; outcome?: string; from?: string; to?: string };
    outcomes: { value: string; label: string }[];
    controls: { value: number; label: string }[];
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

export default function TestsIndex({ tests, filters: initialFilters, outcomes, controls }: Props) {
    const [filterValues, setFilterValues] = useState<Record<string, string>>({
        control: String(initialFilters.control ?? ''),
        outcome: initialFilters.outcome ?? '',
        from:    initialFilters.from    ?? '',
        to:      initialFilters.to      ?? '',
    });

    const [sortKey, setSortKey] = useState('');
    const [sortDir, setSortDir] = useState<'asc' | 'desc'>('asc');

    const debounceRef = useRef<ReturnType<typeof setTimeout> | null>(null);

    useEffect(() => {
        if (debounceRef.current) clearTimeout(debounceRef.current);
        debounceRef.current = setTimeout(() => {
            const params: Record<string, string> = {};
            if (filterValues.control) params.control = filterValues.control;
            if (filterValues.outcome) params.outcome = filterValues.outcome;
            if (filterValues.from)    params.from    = filterValues.from;
            if (filterValues.to)      params.to      = filterValues.to;
            router.get(route('tests.index'), params, { preserveState: true, replace: true });
        }, 300);
        return () => { if (debounceRef.current) clearTimeout(debounceRef.current); };
    }, [filterValues]);

    const isActive = Object.values(filterValues).some((v) => v !== '');

    const handleFilterChange = (id: string, value: string) => {
        setFilterValues((prev) => ({ ...prev, [id]: value }));
    };

    const handleReset = () => {
        setFilterValues({ control: '', outcome: '', from: '', to: '' });
    };

    const handleSort = (key: string) => {
        if (sortKey === key) {
            setSortDir((d) => (d === 'asc' ? 'desc' : 'asc'));
        } else {
            setSortKey(key);
            setSortDir('asc');
        }
    };

    const controlOptions = controls.map((c) => ({ value: String(c.value), label: c.label }));

    const filterConfigs = [
        { id: 'control', label: 'Control', type: 'select' as const, options: controlOptions, flex: 2 },
        { id: 'outcome', label: 'Outcome', type: 'select' as const, options: outcomes },
        { id: 'from',    label: 'From',    type: 'date'   as const },
        { id: 'to',      label: 'To',      type: 'date'   as const },
    ];

    const columns: Column<Test>[] = [
        {
            key: 'tested_at',
            header: 'Test Date',
            sortable: true,
            render: (row) => (
                <span className="text-sm">{formatDate(row.tested_at)}</span>
            ),
        },
        {
            key: 'control',
            header: 'Control',
            sortable: false,
            render: (row) => (
                <div>
                    <Link
                        href={route('controls.show', row.control.id)}
                        className="text-sm font-medium text-blue-600 hover:text-blue-800 focus:outline-none focus:underline"
                        onClick={(e) => e.stopPropagation()}
                    >
                        {row.control.reference}
                    </Link>
                    <span className="block text-xs mt-0.5 truncate max-w-xs" style={{ color: 'var(--color-text-secondary)' }}>
                        {row.control.title}
                    </span>
                </div>
            ),
        },
        {
            key: 'tested_by_name',
            header: 'Tested By',
            sortable: true,
            render: (row) => (
                <span className="text-sm" style={{ color: 'var(--color-text-secondary)' }}>{row.tested_by_name}</span>
            ),
        },
        {
            key: 'outcome',
            header: 'Outcome',
            sortable: true,
            render: (row) => (
                <StatusBadge
                    variant={outcomeToVariant(row.outcome)}
                    label={outcomeLabel(row.outcome)}
                />
            ),
        },
        {
            key: 'sample_size',
            header: 'Sample',
            sortable: true,
            render: (row) => (
                <span className="text-xs" style={{ color: 'var(--color-text-secondary)' }}>
                    {row.sample_size ?? '—'}
                </span>
            ),
        },
        {
            key: 'findings',
            header: 'Findings',
            render: (row) => (
                <span className="text-xs" style={{ color: 'var(--color-text-secondary)' }}>
                    {row.findings ? row.findings.substring(0, 100) + (row.findings.length > 100 ? '…' : '') : '—'}
                </span>
            ),
        },
    ];

    return (
        <AuthenticatedLayout>
            <Head title="Test Log" />

            <PageHeader
                title="Test Log"
                subtitle="All controls testing activity"
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
                    data={tests.data}
                    sortKey={sortKey}
                    sortDir={sortDir}
                    onSort={handleSort}
                    onRowClick={(row) => router.visit(route('tests.show', row.id))}
                    emptyState={
                        <EmptyState
                            icon={<ClipboardDocumentCheckIcon className="w-8 h-8 text-gray-400" aria-hidden />}
                            title="No test records found"
                            description="No tests match your filters, or no tests have been recorded yet."
                        />
                    }
                />
                <Pagination
                    links={tests.links}
                    from={tests.from}
                    to={tests.to}
                    total={tests.total}
                />
            </Card>
        </AuthenticatedLayout>
    );
}
