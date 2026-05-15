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
import IconButton from '@/Components/IconButton';
import { BeakerIcon, PlusIcon, ExclamationTriangleIcon, ClipboardDocumentCheckIcon } from '@heroicons/react/24/outline';

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

interface Control {
    id: number;
    reference: string;
    title: string;
    control_type: string;
    nature: string;
    frequency: string;
    frequency_label: string;
    owner_team: string;
    status: string;
    last_tested_at: string | null;
    next_test_due: string | null;
    days_until_due: number;
}

interface Props {
    controls: PaginatedData<Control>;
    filters: { search?: string; type?: string; frequency?: string; status?: string; owner?: string; due_soon?: '1' };
    types: { value: string; label: string }[];
    frequencies: { value: string; label: string }[];
    statuses: { value: string; label: string }[];
    due_count: number;
    overdue_count: number;
}

type StatusVariant = 'critical' | 'high' | 'medium' | 'low' | 'info' | 'completed' | 'draft' | 'overdue' | 'ai';

function statusToVariant(status: string): StatusVariant {
    const map: Record<string, StatusVariant> = {
        active:     'low',
        inactive:   'draft',
        draft:      'draft',
        archived:   'draft',
        in_review:  'info',
    };
    return map[status] ?? 'draft';
}

function relativeTime(isoStr: string | null): string {
    if (!isoStr) return '—';
    const now = Date.now();
    const then = new Date(isoStr).getTime();
    const diffMs = now - then;
    const diffDays = Math.floor(diffMs / 86400000);
    if (diffDays < 1) return 'today';
    if (diffDays < 30) return `${diffDays}d ago`;
    const diffMonths = Math.floor(diffDays / 30);
    if (diffMonths < 12) return `${diffMonths}mo ago`;
    return `${Math.floor(diffMonths / 12)}y ago`;
}

function formatDate(dateStr: string | null): string {
    if (!dateStr) return '—';
    const d = new Date(dateStr);
    if (isNaN(d.getTime())) return '—';
    return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}

export default function ControlsIndex({
    controls,
    filters: initialFilters,
    types,
    frequencies,
    statuses,
    due_count,
    overdue_count,
}: Props) {
    const canCreate = useCan('controls.create');
    const canTest = useCan('controls.test');

    const [filterValues, setFilterValues] = useState<Record<string, string>>({
        search:    initialFilters.search    ?? '',
        type:      initialFilters.type      ?? '',
        frequency: initialFilters.frequency ?? '',
        status:    initialFilters.status    ?? '',
        owner:     initialFilters.owner     ?? '',
        due_soon:  initialFilters.due_soon  ?? '',
    });

    const [sortKey, setSortKey] = useState('');
    const [sortDir, setSortDir] = useState<'asc' | 'desc'>('asc');

    const debounceRef = useRef<ReturnType<typeof setTimeout> | null>(null);

    useEffect(() => {
        if (debounceRef.current) clearTimeout(debounceRef.current);
        debounceRef.current = setTimeout(() => {
            const params: Record<string, string> = {};
            if (filterValues.search)    params.search    = filterValues.search;
            if (filterValues.type)      params.type      = filterValues.type;
            if (filterValues.frequency) params.frequency = filterValues.frequency;
            if (filterValues.status)    params.status    = filterValues.status;
            if (filterValues.owner)     params.owner     = filterValues.owner;
            if (filterValues.due_soon)  params.due_soon  = filterValues.due_soon;
            router.get(route('controls.index'), params, { preserveState: true, replace: true });
        }, 300);
        return () => { if (debounceRef.current) clearTimeout(debounceRef.current); };
    }, [filterValues]);

    const isActive = Object.values(filterValues).some((v) => v !== '');

    const handleFilterChange = (id: string, value: string) => {
        setFilterValues((prev) => ({ ...prev, [id]: value }));
    };

    const handleReset = () => {
        setFilterValues({ search: '', type: '', frequency: '', status: '', owner: '', due_soon: '' });
    };

    const handleSort = (key: string) => {
        if (sortKey === key) {
            setSortDir((d) => (d === 'asc' ? 'desc' : 'asc'));
        } else {
            setSortKey(key);
            setSortDir('asc');
        }
    };

    const dueSoonOptions = [
        { value: '',  label: 'All' },
        { value: '1', label: 'Due This Week' },
    ];

    const filterConfigs = [
        { id: 'search',    label: 'Search',    type: 'text'   as const, placeholder: 'Title or reference…', flex: 2 },
        { id: 'type',      label: 'Type',      type: 'select' as const, options: types },
        { id: 'frequency', label: 'Frequency', type: 'select' as const, options: frequencies },
        { id: 'status',    label: 'Status',    type: 'select' as const, options: statuses },
        { id: 'due_soon',  label: 'Due',       type: 'select' as const, options: dueSoonOptions },
    ];

    const columns: Column<Control>[] = [
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
                    <span className="block text-xs mt-0.5" style={{ color: 'var(--color-text-secondary)' }}>
                        {row.owner_team}
                    </span>
                </div>
            ),
        },
        {
            key: 'control_type',
            header: 'Type',
            sortable: true,
            render: (row) => <StatusBadge variant="info" label={row.control_type} />,
        },
        {
            key: 'nature',
            header: 'Nature',
            sortable: true,
            render: (row) => (
                <span className="text-xs" style={{ color: 'var(--color-text-secondary)' }}>{row.nature}</span>
            ),
        },
        {
            key: 'frequency',
            header: 'Frequency',
            sortable: true,
            render: (row) => (
                <span className="text-xs" style={{ color: 'var(--color-text-secondary)' }}>{row.frequency_label}</span>
            ),
        },
        {
            key: 'last_tested_at',
            header: 'Last Tested',
            sortable: true,
            render: (row) => (
                <span className="text-xs" style={{ color: 'var(--color-text-secondary)' }}>
                    {relativeTime(row.last_tested_at)}
                </span>
            ),
        },
        {
            key: 'next_test_due',
            header: 'Next Due',
            sortable: true,
            render: (row) => {
                const isOverdue = row.days_until_due < 0;
                return (
                    <span className={`text-xs font-medium ${isOverdue ? 'text-red-600' : ''}`} style={!isOverdue ? { color: 'var(--color-text-secondary)' } : undefined}>
                        {formatDate(row.next_test_due)}
                        {isOverdue && (
                            <span className="ml-1 badge border bg-red-100 text-red-800 border-red-200 text-[10px]">
                                {Math.abs(row.days_until_due)}d overdue
                            </span>
                        )}
                    </span>
                );
            },
        },
        {
            key: 'status',
            header: 'Status',
            sortable: true,
            render: (row) => <StatusBadge variant={statusToVariant(row.status)} label={row.status} />,
        },
        {
            key: 'actions',
            header: '',
            render: (row) => (
                <div className="flex items-center gap-1" onClick={(e) => e.stopPropagation()}>
                    {canTest && (
                        <Link href={route('controls.test', row.id)}>
                            <IconButton label={`Test ${row.reference}`} size="sm">
                                <ClipboardDocumentCheckIcon className="w-4 h-4" aria-hidden />
                            </IconButton>
                        </Link>
                    )}
                </div>
            ),
        },
    ];

    return (
        <AuthenticatedLayout>
            <Head title="Controls" />

            {overdue_count > 0 && (
                <div className="mb-4 rounded-xl border border-red-200 bg-red-50 px-5 py-3 flex items-center gap-3">
                    <ExclamationTriangleIcon aria-hidden className="w-5 h-5 text-red-600 flex-shrink-0" />
                    <p className="text-sm font-semibold text-red-800">
                        {overdue_count} control{overdue_count !== 1 ? 's' : ''} overdue for testing
                    </p>
                    <button
                        type="button"
                        className="ml-auto text-xs text-red-600 underline focus:outline-none"
                        onClick={() => setFilterValues((p) => ({ ...p, due_soon: '1' }))}
                    >
                        Filter overdue
                    </button>
                </div>
            )}

            {due_count > 0 && overdue_count === 0 && (
                <div className="mb-4 rounded-xl border border-amber-200 bg-amber-50 px-5 py-3 flex items-center gap-3">
                    <ExclamationTriangleIcon aria-hidden className="w-5 h-5 text-amber-600 flex-shrink-0" />
                    <p className="text-sm font-semibold text-amber-800">
                        {due_count} control{due_count !== 1 ? 's' : ''} due for testing this week
                    </p>
                </div>
            )}

            <PageHeader
                title="Controls"
                subtitle="Internal controls register and testing schedule"
                actions={
                    canCreate ? (
                        <Link href={route('controls.create')}>
                            <PrimaryButton type="button">
                                <PlusIcon aria-hidden className="w-4 h-4" />
                                Add Control
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
                    data={controls.data}
                    sortKey={sortKey}
                    sortDir={sortDir}
                    onSort={handleSort}
                    onRowClick={(row) => router.visit(route('controls.show', row.id))}
                    emptyState={
                        <EmptyState
                            icon={<BeakerIcon className="w-8 h-8 text-gray-400" aria-hidden />}
                            title="No controls found"
                            description="No controls match your filters, or none have been added yet."
                            action={
                                canCreate ? (
                                    <Link href={route('controls.create')}>
                                        <PrimaryButton type="button">
                                            <PlusIcon aria-hidden className="w-4 h-4" />
                                            Add Control
                                        </PrimaryButton>
                                    </Link>
                                ) : undefined
                            }
                        />
                    }
                />
                <Pagination
                    links={controls.links}
                    from={controls.from}
                    to={controls.to}
                    total={controls.total}
                />
            </Card>
        </AuthenticatedLayout>
    );
}
