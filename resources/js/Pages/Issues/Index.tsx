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
import { ExclamationTriangleIcon } from '@heroicons/react/24/outline';

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

interface Issue {
    id: number;
    reference: string;
    title: string;
    severity: 'low' | 'medium' | 'high' | 'critical';
    status: 'open' | 'in_progress' | 'resolved' | 'closed' | 'dismissed';
    source_type: string;
    due_date: string | null;
    owner_team: string | null;
    created_at: string;
}

interface Props {
    issues: PaginatedData<Issue>;
    filters: { search?: string; status?: string; severity?: string; source_type?: string };
    severities: { value: string; label: string }[];
    statuses: { value: string; label: string }[];
    source_types: { value: string; label: string }[];
}

type StatusVariant = 'critical' | 'high' | 'medium' | 'low' | 'info' | 'completed' | 'draft' | 'overdue' | 'ai';

function statusToVariant(status: string): StatusVariant {
    const map: Record<string, StatusVariant> = {
        open:       'high',
        in_progress:'info',
        resolved:   'low',
        closed:     'completed',
        dismissed:  'draft',
    };
    return map[status] ?? 'draft';
}

function formatDate(dateStr: string | null): string {
    if (!dateStr) return '—';
    const d = new Date(dateStr);
    if (isNaN(d.getTime())) return '—';
    return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}

function isDatePast(dateStr: string | null): boolean {
    if (!dateStr) return false;
    return new Date(dateStr).getTime() < Date.now();
}

export default function IssuesIndex({
    issues,
    filters: initialFilters,
    severities,
    statuses,
    source_types,
}: Props) {
    const [filterValues, setFilterValues] = useState<Record<string, string>>({
        search:      initialFilters.search      ?? '',
        status:      initialFilters.status      ?? '',
        severity:    initialFilters.severity    ?? '',
        source_type: initialFilters.source_type ?? '',
    });

    const [sortKey, setSortKey] = useState('');
    const [sortDir, setSortDir] = useState<'asc' | 'desc'>('asc');

    const debounceRef = useRef<ReturnType<typeof setTimeout> | null>(null);

    useEffect(() => {
        if (debounceRef.current) clearTimeout(debounceRef.current);
        debounceRef.current = setTimeout(() => {
            const params: Record<string, string> = {};
            if (filterValues.search)      params.search      = filterValues.search;
            if (filterValues.status)      params.status      = filterValues.status;
            if (filterValues.severity)    params.severity    = filterValues.severity;
            if (filterValues.source_type) params.source_type = filterValues.source_type;
            router.get(route('issues.index'), params, { preserveState: true, replace: true });
        }, 300);
        return () => { if (debounceRef.current) clearTimeout(debounceRef.current); };
    }, [filterValues]);

    const isActive = Object.values(filterValues).some((v) => v !== '');

    const handleFilterChange = (id: string, value: string) => {
        setFilterValues((prev) => ({ ...prev, [id]: value }));
    };

    const handleReset = () => {
        setFilterValues({ search: '', status: '', severity: '', source_type: '' });
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
        { id: 'search',      label: 'Search',      type: 'text'   as const, placeholder: 'Title or reference…', flex: 2 },
        { id: 'status',      label: 'Status',      type: 'select' as const, options: statuses },
        { id: 'severity',    label: 'Severity',    type: 'select' as const, options: severities },
        { id: 'source_type', label: 'Source',      type: 'select' as const, options: source_types },
    ];

    const columns: Column<Issue>[] = [
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
                    {row.owner_team && (
                        <span className="block text-xs mt-0.5" style={{ color: 'var(--color-text-secondary)' }}>
                            {row.owner_team}
                        </span>
                    )}
                </div>
            ),
        },
        {
            key: 'severity',
            header: 'Severity',
            sortable: true,
            render: (row) => (
                <StatusBadge
                    variant={row.severity as StatusVariant}
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
                    variant={statusToVariant(row.status)}
                    label={row.status.replace('_', ' ').replace(/\b\w/g, (c) => c.toUpperCase())}
                />
            ),
        },
        {
            key: 'source_type',
            header: 'Source',
            sortable: true,
            render: (row) => (
                <span className="text-xs" style={{ color: 'var(--color-text-secondary)' }}>{row.source_type}</span>
            ),
        },
        {
            key: 'due_date',
            header: 'Due Date',
            sortable: true,
            render: (row) => {
                const past = isDatePast(row.due_date);
                return (
                    <span className={`text-xs font-medium ${past ? 'text-red-600' : ''}`} style={!past ? { color: 'var(--color-text-secondary)' } : undefined}>
                        {formatDate(row.due_date)}
                    </span>
                );
            },
        },
    ];

    return (
        <AuthenticatedLayout>
            <Head title="Issues" />

            <PageHeader
                title="Issues"
                subtitle="Compliance issues and remediation tracker"
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
                    data={issues.data}
                    sortKey={sortKey}
                    sortDir={sortDir}
                    onSort={handleSort}
                    onRowClick={(row) => router.visit(route('issues.show', row.id))}
                    emptyState={
                        <EmptyState
                            icon={<ExclamationTriangleIcon className="w-8 h-8 text-gray-400" aria-hidden />}
                            title="No issues found"
                            description="No issues match your filters. Issues are created automatically when controls testing fails."
                        />
                    }
                />
                <Pagination
                    links={issues.links}
                    from={issues.from}
                    to={issues.to}
                    total={issues.total}
                />
            </Card>
        </AuthenticatedLayout>
    );
}
