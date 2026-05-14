import { useState } from 'react';
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
import { Head } from '@inertiajs/react';
import { ClipboardDocumentListIcon, PlusIcon, EyeIcon, PencilIcon, TrashIcon } from '@heroicons/react/24/outline';

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface Obligation {
    id: number;
    ref_code: string;
    description: string;
    instrument: string;
    nature: string;
    due_date: string;
    status: string;
}

interface PaginatedObligations {
    data: Obligation[];
    links: PaginationLink[];
    from: number;
    to: number;
    total: number;
}

interface Props {
    obligations?: PaginatedObligations;
    filters?: Record<string, string>;
    instruments?: { value: string; label: string }[];
    natures?: { value: string; label: string }[];
    statuses?: { value: string; label: string }[];
}

const mockData: Obligation[] = [
    { id: 1, ref_code: 'OBL-0001', description: 'Submit monthly AML returns to NFIU within 5 working days of month end', instrument: 'CBN/REG/001', nature: 'Reporting', due_date: '2026-05-30', status: 'overdue' },
    { id: 2, ref_code: 'OBL-0002', description: 'Maintain customer transaction monitoring systems as per CBN AML guidelines', instrument: 'CBN/REG/001', nature: 'Compliance', due_date: '2026-06-15', status: 'info' },
    { id: 3, ref_code: 'OBL-0003', description: 'Report suspicious transactions within 24 hours of detection', instrument: 'NFIU/ML/018', nature: 'Disclosure', due_date: '2026-06-01', status: 'medium' },
    { id: 4, ref_code: 'OBL-0004', description: 'Conduct annual AML training for all customer-facing staff', instrument: 'CBN/REG/001', nature: 'Governance', due_date: '2026-12-31', status: 'low' },
    { id: 5, ref_code: 'OBL-0005', description: 'File quarterly capital adequacy returns with CBN', instrument: 'SEC/CIR/042', nature: 'Reporting', due_date: '2026-07-15', status: 'info' },
    { id: 6, ref_code: 'OBL-0006', description: 'Conduct Data Protection Impact Assessment for new processing activities', instrument: 'NDPC/GUI/005', nature: 'Compliance', due_date: '2026-05-28', status: 'medium' },
];

const mockPaginated: PaginatedObligations = {
    data: mockData,
    links: [
        { url: null, label: '&laquo; Previous', active: false },
        { url: '#', label: '1', active: true },
        { url: '#', label: '2', active: false },
        { url: null, label: 'Next &raquo;', active: false },
    ],
    from: 1,
    to: 6,
    total: 1847,
};

const natureOptions = [
    { value: 'Reporting', label: 'Reporting' },
    { value: 'Disclosure', label: 'Disclosure' },
    { value: 'Compliance', label: 'Compliance' },
    { value: 'Governance', label: 'Governance' },
    { value: 'Operational', label: 'Operational' },
];

const statusOptions = [
    { value: 'overdue', label: 'Overdue' },
    { value: 'medium', label: 'Due Soon' },
    { value: 'info', label: 'Upcoming' },
    { value: 'low', label: 'On Track' },
    { value: 'completed', label: 'Completed' },
];

const filterConfigs = [
    { id: 'search', label: 'Search', type: 'text' as const, placeholder: 'Obligation text…', flex: 2 },
    { id: 'instrument', label: 'Instrument', type: 'select' as const, options: [] },
    { id: 'nature', label: 'Nature', type: 'select' as const, options: natureOptions },
    { id: 'status', label: 'Status', type: 'select' as const, options: statusOptions },
    { id: 'due_before', label: 'Due Before', type: 'date' as const },
];

export default function ObligationsIndex({
    obligations = mockPaginated,
    filters: initialFilters = {},
}: Props) {
    const [filterValues, setFilterValues] = useState<Record<string, string>>({
        search: initialFilters.search ?? '',
        instrument: initialFilters.instrument ?? '',
        nature: initialFilters.nature ?? '',
        status: initialFilters.status ?? '',
        due_before: initialFilters.due_before ?? '',
    });

    const [sortKey, setSortKey] = useState('');
    const [sortDir, setSortDir] = useState<'asc' | 'desc'>('asc');

    const isActive = Object.values(filterValues).some((v) => v !== '');

    const handleSort = (key: string) => {
        if (sortKey === key) setSortDir((d) => (d === 'asc' ? 'desc' : 'asc'));
        else { setSortKey(key); setSortDir('asc'); }
    };

    const columns: Column<Obligation>[] = [
        {
            key: 'ref_code',
            header: 'Ref',
            sortable: true,
            render: (row) => (
                <span className="font-mono text-xs text-gray-600">{row.ref_code}</span>
            ),
        },
        {
            key: 'description',
            header: 'Obligation',
            render: (row) => (
                <span className="line-clamp-2 max-w-xs">{row.description}</span>
            ),
        },
        { key: 'instrument', header: 'Instrument', sortable: true },
        {
            key: 'nature',
            header: 'Nature',
            sortable: true,
            render: (row) => <StatusBadge variant="info" label={row.nature} />,
        },
        { key: 'due_date', header: 'Due', sortable: true },
        {
            key: 'status',
            header: 'Status',
            sortable: true,
            render: (row) => (
                <StatusBadge
                    variant={row.status as 'overdue' | 'medium' | 'info' | 'low' | 'completed'}
                />
            ),
        },
        {
            key: 'actions',
            header: '',
            render: (row) => (
                <div className="flex items-center gap-1">
                    <IconButton label={`View ${row.ref_code}`} size="sm">
                        <EyeIcon className="w-4 h-4" aria-hidden />
                    </IconButton>
                    <IconButton label={`Edit ${row.ref_code}`} size="sm">
                        <PencilIcon className="w-4 h-4" aria-hidden />
                    </IconButton>
                    <IconButton label={`Delete ${row.ref_code}`} size="sm">
                        <TrashIcon className="w-4 h-4" aria-hidden />
                    </IconButton>
                </div>
            ),
        },
    ];

    return (
        <AuthenticatedLayout>
            <Head title="Obligations Register" />

            <PageHeader
                breadcrumb={[{ label: 'Obligations' }]}
                title="Obligations Register"
                subtitle="Compliance obligations linked to regulatory instruments"
                actions={
                    <PrimaryButton>
                        <PlusIcon aria-hidden className="w-4 h-4" />
                        Add Obligation
                    </PrimaryButton>
                }
            />

            <FilterBar
                filters={filterConfigs}
                values={filterValues}
                onChange={(id, value) => setFilterValues((prev) => ({ ...prev, [id]: value }))}
                onReset={() => setFilterValues({ search: '', instrument: '', nature: '', status: '', due_before: '' })}
                isActive={isActive}
            />

            <Card hover={false} padding="none">
                <DataTable
                    columns={columns}
                    data={obligations.data}
                    sortKey={sortKey}
                    sortDir={sortDir}
                    onSort={handleSort}
                    emptyState={
                        <EmptyState
                            icon={<ClipboardDocumentListIcon className="w-8 h-8 text-gray-400" aria-hidden />}
                            title="No obligations found"
                            description="Add obligations manually or link them from an instrument."
                        />
                    }
                />
                <Pagination
                    links={obligations.links}
                    from={obligations.from}
                    to={obligations.to}
                    total={obligations.total}
                />
            </Card>
        </AuthenticatedLayout>
    );
}
