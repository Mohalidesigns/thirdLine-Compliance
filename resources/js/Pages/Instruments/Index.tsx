import { useRef, useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import PageHeader from '@/Components/PageHeader';
import FilterBar from '@/Components/FilterBar';
import Card from '@/Components/Card';
import DataTable, { Column } from '@/Components/DataTable';
import Pagination from '@/Components/Pagination';
import StatusBadge from '@/Components/StatusBadge';
import EmptyState from '@/Components/EmptyState';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import IconButton from '@/Components/IconButton';
import Modal from '@/Components/Modal';
import { Head, Link, router } from '@inertiajs/react';
import { BookOpenIcon, PlusIcon, ArrowUpTrayIcon, EyeIcon, PencilIcon, TrashIcon } from '@heroicons/react/24/outline';

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface Instrument {
    id: number;
    reference: string;
    title: string;
    regulator: string;
    item_type: string;
    risk_rating: string;
    effective_date: string;
}

interface PaginatedInstruments {
    data: Instrument[];
    links: PaginationLink[];
    from: number;
    to: number;
    total: number;
}

interface FilterValues {
    search?: string;
    regulator?: string;
    item_type?: string;
    risk_rating?: string;
}

interface Props {
    instruments?: PaginatedInstruments;
    filters?: FilterValues;
    regulators?: { value: string; label: string }[];
    itemTypes?: { value: string; label: string }[];
    riskRatings?: { value: string; label: string }[];
}

const mockData: Instrument[] = [
    { id: 1, reference: 'CBN/REG/001', title: 'Anti-Money Laundering Regulations', regulator: 'CBN', item_type: 'Regulation', risk_rating: 'critical', effective_date: '2023-01-01' },
    { id: 2, reference: 'SEC/CIR/042', title: 'Capital Market Operators Directive', regulator: 'SEC', item_type: 'Circular', risk_rating: 'high', effective_date: '2023-03-15' },
    { id: 3, reference: 'NFIU/ML/018', title: 'Suspicious Transaction Reporting Guidelines', regulator: 'NFIU', item_type: 'Guideline', risk_rating: 'high', effective_date: '2022-11-01' },
    { id: 4, reference: 'NDIC/DIR/007', title: 'Deposit Insurance Framework', regulator: 'NDIC', item_type: 'Directive', risk_rating: 'medium', effective_date: '2023-06-01' },
    { id: 5, reference: 'NAICOM/CIR/033', title: 'Insurance Premium Computation Guidelines', regulator: 'NAICOM', item_type: 'Circular', risk_rating: 'medium', effective_date: '2023-02-28' },
    { id: 6, reference: 'PENCOM/REG/011', title: 'Pension Fund Administration Rules', regulator: 'PenCom', item_type: 'Regulation', risk_rating: 'low', effective_date: '2022-09-01' },
    { id: 7, reference: 'NDPC/GUI/005', title: 'Data Protection Impact Assessment Guidelines', regulator: 'NDPC', item_type: 'Guideline', risk_rating: 'high', effective_date: '2024-01-01' },
    { id: 8, reference: 'FIRS/CIR/019', title: 'Transfer Pricing Regulations', regulator: 'FIRS', item_type: 'Regulation', risk_rating: 'medium', effective_date: '2023-04-01' },
];

const mockPaginated: PaginatedInstruments = {
    data: mockData,
    links: [
        { url: null, label: '&laquo; Previous', active: false },
        { url: '#', label: '1', active: true },
        { url: '#', label: '2', active: false },
        { url: null, label: 'Next &raquo;', active: false },
    ],
    from: 1,
    to: 8,
    total: 352,
};

const regulatorOptions = [
    { value: 'CBN', label: 'CBN' },
    { value: 'SEC', label: 'SEC' },
    { value: 'NFIU', label: 'NFIU' },
    { value: 'NDIC', label: 'NDIC' },
    { value: 'NAICOM', label: 'NAICOM' },
    { value: 'PenCom', label: 'PenCom' },
    { value: 'NDPC', label: 'NDPC' },
    { value: 'FIRS', label: 'FIRS' },
];

const typeOptions = [
    { value: 'Regulation', label: 'Regulation' },
    { value: 'Circular', label: 'Circular' },
    { value: 'Guideline', label: 'Guideline' },
    { value: 'Directive', label: 'Directive' },
];

const riskOptions = [
    { value: 'critical', label: 'Critical' },
    { value: 'high', label: 'High' },
    { value: 'medium', label: 'Medium' },
    { value: 'low', label: 'Low' },
];

const filterConfigs = [
    { id: 'search', label: 'Search', type: 'text' as const, placeholder: 'Title, reference…', flex: 2 },
    { id: 'regulator', label: 'Regulator', type: 'select' as const, options: regulatorOptions },
    { id: 'item_type', label: 'Type', type: 'select' as const, options: typeOptions },
    { id: 'risk_rating', label: 'Risk', type: 'select' as const, options: riskOptions },
];

export default function InstrumentsIndex({
    instruments = mockPaginated,
    filters: initialFilters = {},
    regulators = regulatorOptions,
    itemTypes = typeOptions,
    riskRatings = riskOptions,
}: Props) {
    const [filterValues, setFilterValues] = useState<Record<string, string>>({
        search: initialFilters.search ?? '',
        regulator: initialFilters.regulator ?? '',
        item_type: initialFilters.item_type ?? '',
        risk_rating: initialFilters.risk_rating ?? '',
    });

    const [sortKey, setSortKey] = useState<string>('');
    const [sortDir, setSortDir] = useState<'asc' | 'desc'>('asc');

    const [showImportModal, setShowImportModal] = useState(false);
    const [importing, setImporting] = useState(false);
    const fileInputRef = useRef<HTMLInputElement>(null);

    const handleImportSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        const file = fileInputRef.current?.files?.[0];
        if (!file) return;
        const formData = new FormData();
        formData.append('file', file);
        setImporting(true);
        router.post(route('instruments.bulkImport'), formData, {
            forceFormData: true,
            onSuccess: () => {
                setShowImportModal(false);
                setImporting(false);
                if (fileInputRef.current) fileInputRef.current.value = '';
            },
            onError: () => {
                setImporting(false);
            },
        });
    };

    const isActive = Object.values(filterValues).some((v) => v !== '');

    const handleFilterChange = (id: string, value: string) => {
        setFilterValues((prev) => ({ ...prev, [id]: value }));
    };

    const handleReset = () => {
        setFilterValues({ search: '', regulator: '', item_type: '', risk_rating: '' });
    };

    const handleSort = (key: string) => {
        if (sortKey === key) {
            setSortDir((d) => (d === 'asc' ? 'desc' : 'asc'));
        } else {
            setSortKey(key);
            setSortDir('asc');
        }
    };

    const columns: Column<Instrument>[] = [
        {
            key: 'reference',
            header: 'Reference',
            sortable: true,
            width: 'w-28',
            render: (row) => (
                <span className="font-mono text-xs text-gray-600">{row.reference}</span>
            ),
        },
        { key: 'title', header: 'Title', sortable: true },
        { key: 'regulator', header: 'Regulator', sortable: true },
        {
            key: 'item_type',
            header: 'Type',
            sortable: true,
            render: (row) => <StatusBadge variant="info" label={row.item_type} />,
        },
        {
            key: 'risk_rating',
            header: 'Risk',
            sortable: true,
            render: (row) => (
                <StatusBadge
                    variant={row.risk_rating as 'critical' | 'high' | 'medium' | 'low'}
                    dot
                />
            ),
        },
        { key: 'effective_date', header: 'Effective', sortable: true },
        {
            key: 'actions',
            header: '',
            render: (row) => (
                <div className="flex items-center gap-1">
                    <Link href={route('instruments.show', row.id)}>
                        <IconButton label={`View ${row.reference}`} size="sm">
                            <EyeIcon className="w-4 h-4" aria-hidden />
                        </IconButton>
                    </Link>
                    <Link href={route('instruments.edit', row.id)}>
                        <IconButton label={`Edit ${row.reference}`} size="sm">
                            <PencilIcon className="w-4 h-4" aria-hidden />
                        </IconButton>
                    </Link>
                    <IconButton
                        label={`Delete ${row.reference}`}
                        size="sm"
                        onClick={() => router.delete(route('instruments.destroy', row.id), {
                            onBefore: () => confirm('Delete this instrument? This cannot be undone.'),
                        })}
                    >
                        <TrashIcon className="w-4 h-4" aria-hidden />
                    </IconButton>
                </div>
            ),
        },
    ];

    return (
        <AuthenticatedLayout>
            <Head title="Instruments Register" />

            <PageHeader
                breadcrumb={[{ label: 'Library' }]}
                title="Instruments Register"
                subtitle="Regulatory instruments grouped by regulator"
                actions={
                    <>
                        <SecondaryButton onClick={() => setShowImportModal(true)}>
                            <ArrowUpTrayIcon aria-hidden className="w-4 h-4" />
                            Import XLSX
                        </SecondaryButton>
                        <Link href={route('instruments.create')}>
                            <PrimaryButton type="button">
                                <PlusIcon aria-hidden className="w-4 h-4" />
                                Add Instrument
                            </PrimaryButton>
                        </Link>
                    </>
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
                    data={instruments.data}
                    sortKey={sortKey}
                    sortDir={sortDir}
                    onSort={handleSort}
                    emptyState={
                        <EmptyState
                            icon={<BookOpenIcon className="w-8 h-8 text-gray-400" aria-hidden />}
                            title="No instruments found"
                            description="No instruments match your filters, or none have been added yet."
                            action={
                                <Link href={route('instruments.create')}>
                                    <PrimaryButton type="button">
                                        <PlusIcon aria-hidden className="w-4 h-4" />
                                        Add Instrument
                                    </PrimaryButton>
                                </Link>
                            }
                        />
                    }
                />
                <Pagination
                    links={instruments.links}
                    from={instruments.from}
                    to={instruments.to}
                    total={instruments.total}
                />
            </Card>

            <Modal show={showImportModal} maxWidth="md" onClose={() => setShowImportModal(false)}>
                <div className="p-6">
                    <h2 className="text-lg font-semibold text-gray-900 mb-1">Import Instruments</h2>
                    <p className="text-sm text-gray-500 mb-4">
                        Upload an Excel file (.xlsx or .xls) containing instrument data.
                    </p>
                    <form onSubmit={handleImportSubmit} noValidate>
                        <div className="mb-4">
                            <label
                                htmlFor="import-file"
                                className="form-label"
                            >
                                Select file
                            </label>
                            <input
                                id="import-file"
                                ref={fileInputRef}
                                type="file"
                                accept=".xlsx,.xls"
                                required
                                aria-required="true"
                                className="mt-1 block w-full text-sm text-gray-700
                                           file:mr-3 file:py-2 file:px-3
                                           file:rounded-lg file:border-0
                                           file:text-xs file:font-semibold
                                           file:bg-gray-100 file:text-gray-700
                                           hover:file:bg-gray-200
                                           focus:outline-none"
                            />
                        </div>
                        <div className="flex justify-end gap-3">
                            <SecondaryButton
                                type="button"
                                onClick={() => setShowImportModal(false)}
                            >
                                Cancel
                            </SecondaryButton>
                            <PrimaryButton type="submit" isLoading={importing}>
                                Upload
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </Modal>
        </AuthenticatedLayout>
    );
}
