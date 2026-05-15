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
import ConfirmDialog from '@/Components/ConfirmDialog';
import {
    DocumentTextIcon,
    PlusIcon,
    EyeIcon,
    PencilIcon,
    TrashIcon,
} from '@heroicons/react/24/outline';

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface Policy {
    id: number;
    reference: string;
    title: string;
    category: string;
    category_label: string;
    owner_team: string;
    version: number;
    state: string;
    state_label: string;
    state_color: string;
    effective_date: string | null;
    next_review_date: string | null;
    summary: string | null;
    body: string | null;
    created_at: string;
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
    policies: PaginatedData<Policy>;
    filters: { search?: string; status?: string; category?: string };
    statuses: { value: string; label: string; color: string }[];
    categories: { value: string; label: string }[];
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

function formatDate(dateStr: string | null): string {
    if (!dateStr) return '—';
    const d = new Date(dateStr);
    if (isNaN(d.getTime())) return '—';
    return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
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

export default function PoliciesIndex({ policies, filters: initialFilters, statuses, categories }: Props) {
    const canCreate = useCan('policies.create');
    const canUpdate = useCan('policies.update');
    const canDelete = useCan('policies.delete');

    const [filterValues, setFilterValues] = useState<Record<string, string>>({
        search:   initialFilters.search   ?? '',
        status:   initialFilters.status   ?? '',
        category: initialFilters.category ?? '',
    });

    const [sortKey, setSortKey] = useState('');
    const [sortDir, setSortDir] = useState<'asc' | 'desc'>('asc');

    const [deleteTarget, setDeleteTarget] = useState<Policy | null>(null);
    const [deleting, setDeleting] = useState(false);

    const debounceRef = useRef<ReturnType<typeof setTimeout> | null>(null);

    useEffect(() => {
        if (debounceRef.current) clearTimeout(debounceRef.current);
        debounceRef.current = setTimeout(() => {
            const params: Record<string, string> = {};
            if (filterValues.search)   params.search   = filterValues.search;
            if (filterValues.status)   params.status   = filterValues.status;
            if (filterValues.category) params.category = filterValues.category;
            router.get(route('policies.index'), params, { preserveState: true, replace: true });
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
        setFilterValues({ search: '', status: '', category: '' });
    };

    const handleSort = (key: string) => {
        if (sortKey === key) {
            setSortDir((d) => (d === 'asc' ? 'desc' : 'asc'));
        } else {
            setSortKey(key);
            setSortDir('asc');
        }
    };

    const handleDelete = () => {
        if (!deleteTarget) return;
        setDeleting(true);
        router.delete(route('policies.destroy', deleteTarget.id), {
            onFinish: () => {
                setDeleting(false);
                setDeleteTarget(null);
            },
        });
    };

    const filterConfigs = [
        { id: 'search',   label: 'Search',   type: 'text'   as const, placeholder: 'Title or reference…', flex: 2 },
        { id: 'status',   label: 'Status',   type: 'select' as const, options: statuses },
        { id: 'category', label: 'Category', type: 'select' as const, options: categories },
    ];

    const columns: Column<Policy>[] = [
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
            key: 'category',
            header: 'Category',
            sortable: true,
            render: (row) => (
                <span className="text-xs" style={{ color: 'var(--color-text-secondary)' }}>
                    {row.category_label}
                </span>
            ),
        },
        {
            key: 'state',
            header: 'Status',
            sortable: true,
            render: (row) => (
                <StatusBadge
                    variant={stateColorToVariant(row.state_color)}
                    label={row.state_label}
                />
            ),
        },
        {
            key: 'version',
            header: 'Version',
            sortable: true,
            render: (row) => (
                <span className="text-xs" style={{ color: 'var(--color-text-secondary)' }}>
                    v{row.version}
                </span>
            ),
        },
        {
            key: 'effective_date',
            header: 'Effective Date',
            sortable: true,
            render: (row) => (
                <span className="text-sm">{formatDate(row.effective_date)}</span>
            ),
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
        {
            key: 'actions',
            header: '',
            render: (row) => (
                <div className="flex items-center gap-1" onClick={(e) => e.stopPropagation()}>
                    <Link href={route('policies.show', row.id)}>
                        <IconButton label={`View ${row.reference}`} size="sm">
                            <EyeIcon className="w-4 h-4" aria-hidden />
                        </IconButton>
                    </Link>
                    {canUpdate && row.state === 'draft' && (
                        <Link href={route('policies.edit', row.id)}>
                            <IconButton label={`Edit ${row.reference}`} size="sm">
                                <PencilIcon className="w-4 h-4" aria-hidden />
                            </IconButton>
                        </Link>
                    )}
                    {canDelete && row.state === 'draft' && (
                        <IconButton
                            label={`Delete ${row.reference}`}
                            size="sm"
                            onClick={() => setDeleteTarget(row)}
                        >
                            <TrashIcon className="w-4 h-4" aria-hidden />
                        </IconButton>
                    )}
                </div>
            ),
        },
    ];

    return (
        <AuthenticatedLayout>
            <Head title="Policies" />

            <PageHeader
                title="Policies"
                subtitle="Internal policies and procedures"
                actions={
                    canCreate ? (
                        <Link href={route('policies.create')}>
                            <PrimaryButton type="button">
                                <PlusIcon aria-hidden className="w-4 h-4" />
                                New Policy
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
                    data={policies.data}
                    sortKey={sortKey}
                    sortDir={sortDir}
                    onSort={handleSort}
                    onRowClick={(row) => router.visit(route('policies.show', row.id))}
                    emptyState={
                        <EmptyState
                            icon={<DocumentTextIcon className="w-8 h-8 text-gray-400" aria-hidden />}
                            title="No policies yet"
                            description="No policies match your filters, or none have been added yet."
                            action={
                                canCreate ? (
                                    <Link href={route('policies.create')}>
                                        <PrimaryButton type="button">
                                            <PlusIcon aria-hidden className="w-4 h-4" />
                                            New Policy
                                        </PrimaryButton>
                                    </Link>
                                ) : undefined
                            }
                        />
                    }
                />
                <Pagination
                    links={policies.links}
                    from={policies.from}
                    to={policies.to}
                    total={policies.total}
                />
            </Card>

            <ConfirmDialog
                show={deleteTarget !== null}
                onClose={() => setDeleteTarget(null)}
                onConfirm={handleDelete}
                variant="danger"
                title={`Delete policy ${deleteTarget?.reference ?? ''}?`}
                message={`Delete policy ${deleteTarget?.reference ?? ''}? This action is permanent.`}
                confirmLabel="Delete Policy"
                isLoading={deleting}
            />
        </AuthenticatedLayout>
    );
}
