import { useState, useCallback, useRef } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import PageHeader from '@/Components/PageHeader';
import FilterBar from '@/Components/FilterBar';
import Card from '@/Components/Card';
import DataTable, { Column } from '@/Components/DataTable';
import Pagination from '@/Components/Pagination';
import StatusBadge from '@/Components/StatusBadge';
import EmptyState from '@/Components/EmptyState';
import SecondaryButton from '@/Components/SecondaryButton';
import IconButton from '@/Components/IconButton';
import { Head } from '@inertiajs/react';
import { ShieldExclamationIcon, ArrowDownTrayIcon, EyeIcon } from '@heroicons/react/24/outline';

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface Sanction {
    id: number;
    entry_no: string;
    category: string;
    provision: string;
    description: string;
    jurisdiction: string;
    severity: string;
}

interface PaginatedSanctions {
    data: Sanction[];
    links: PaginationLink[];
    from: number;
    to: number;
    total: number;
    per_page: number;
}

interface ExposureSummary {
    count: number;
    highestSeverity?: string;
}

interface Props {
    sanctions?: PaginatedSanctions;
    filters?: Record<string, string>;
    categories?: { value: string; label: string }[];
    severities?: { value: string; label: string }[];
    exposureSummary?: ExposureSummary;
}

const mockData: Sanction[] = [
    { id: 1,  entry_no: '0001', category: 'AML',        provision: 'CBN/REG/001 §4.2', description: 'Failure to file suspicious transaction report within 24 hours constitutes a violation of AML obligations.', jurisdiction: 'Nigeria', severity: 'critical' },
    { id: 2,  entry_no: '0002', category: 'AML',        provision: 'NFIU/ML/018 §7',   description: 'Inadequate customer due diligence procedures for politically exposed persons.', jurisdiction: 'Nigeria', severity: 'high' },
    { id: 3,  entry_no: '0003', category: 'Capital',    provision: 'CBN/BSD/2020 §12', description: 'Breach of minimum capital adequacy ratio of 15% for systematically important banks.', jurisdiction: 'Nigeria', severity: 'critical' },
    { id: 4,  entry_no: '0004', category: 'Data',       provision: 'NDPC/GUI/005 §3',  description: 'Processing personal data without a valid legal basis or data subject consent.', jurisdiction: 'Nigeria', severity: 'high' },
    { id: 5,  entry_no: '0005', category: 'Tax',        provision: 'FIRS/ITA §28',     description: 'Transfer pricing arrangements not at arm\'s length without adequate documentation.', jurisdiction: 'Nigeria', severity: 'medium' },
    { id: 6,  entry_no: '0006', category: 'Insurance',  provision: 'NAICOM/CIR/033 §9', description: 'Failure to maintain statutory minimum solvency margin for insurance companies.', jurisdiction: 'Nigeria', severity: 'critical' },
    { id: 7,  entry_no: '0007', category: 'Pension',    provision: 'PENCOM/REG/011 §5', description: 'Non-remittance of employee pension contributions within 7 working days.', jurisdiction: 'Nigeria', severity: 'high' },
    { id: 8,  entry_no: '0008', category: 'Securities', provision: 'SEC/CIR/042 §16',  description: 'Insider trading in contravention of Investment and Securities Act 2007.', jurisdiction: 'Nigeria', severity: 'critical' },
    { id: 9,  entry_no: '0009', category: 'AML',        provision: 'CBN/REG/001 §9.1', description: 'Failure to freeze accounts of sanctioned persons or entities upon identification.', jurisdiction: 'Nigeria', severity: 'critical' },
    { id: 10, entry_no: '0010', category: 'Data',       provision: 'NDPC/GUI/005 §11', description: 'Cross-border transfer of personal data without adequate safeguards.', jurisdiction: 'Nigeria', severity: 'medium' },
];

const mockPaginated: PaginatedSanctions = {
    data: mockData,
    links: [
        { url: null, label: '&laquo; Previous', active: false },
        { url: '#', label: '1', active: true },
        { url: '#', label: '2', active: false },
        { url: null, label: 'Next &raquo;', active: false },
    ],
    from: 1,
    to: 25,
    total: 417,
    per_page: 25,
};

const categoryOptions = [
    { value: 'AML', label: 'AML' },
    { value: 'Capital', label: 'Capital' },
    { value: 'Data', label: 'Data' },
    { value: 'Tax', label: 'Tax' },
    { value: 'Insurance', label: 'Insurance' },
    { value: 'Pension', label: 'Pension' },
    { value: 'Securities', label: 'Securities' },
];

const severityOptions = [
    { value: 'critical', label: 'Critical' },
    { value: 'high', label: 'High' },
    { value: 'medium', label: 'Medium' },
    { value: 'low', label: 'Low' },
];

export default function SanctionsIndex({
    sanctions = mockPaginated,
    filters: initialFilters = {},
    categories = categoryOptions,
    severities = severityOptions,
    exposureSummary,
}: Props) {
    const [filterValues, setFilterValues] = useState<Record<string, string>>({
        search: initialFilters.search ?? '',
        category: initialFilters.category ?? '',
        severity: initialFilters.severity ?? '',
    });

    const [sortKey, setSortKey] = useState('');
    const [sortDir, setSortDir] = useState<'asc' | 'desc'>('asc');

    const debounceTimer = useRef<ReturnType<typeof setTimeout> | null>(null);

    const isActive = Object.values(filterValues).some((v) => v !== '');
    const hasSearchResults = filterValues.search !== '' && sanctions.data.length > 0;

    const handleFilterChange = useCallback((id: string, value: string) => {
        if (id === 'search') {
            if (debounceTimer.current) clearTimeout(debounceTimer.current);
            debounceTimer.current = setTimeout(() => {
                setFilterValues((prev) => ({ ...prev, [id]: value }));
            }, 300);
        } else {
            setFilterValues((prev) => ({ ...prev, [id]: value }));
        }
    }, []);

    const handleReset = () => {
        if (debounceTimer.current) clearTimeout(debounceTimer.current);
        setFilterValues({ search: '', category: '', severity: '' });
    };

    const handleSort = (key: string) => {
        if (sortKey === key) setSortDir((d) => (d === 'asc' ? 'desc' : 'asc'));
        else { setSortKey(key); setSortDir('asc'); }
    };

    const filterConfigs = [
        { id: 'search', label: 'Full-text search', type: 'text' as const, placeholder: 'Name, jurisdiction, provision…', flex: 3 },
        { id: 'category', label: 'Category', type: 'select' as const, options: categories },
        { id: 'severity', label: 'Severity', type: 'select' as const, options: severities },
    ];

    const columns: Column<Sanction>[] = [
        {
            key: 'entry_no',
            header: '#',
            width: 'w-12',
            render: (row) => <span className="font-mono text-xs text-gray-600">{row.entry_no}</span>,
        },
        { key: 'category', header: 'Category', sortable: true },
        { key: 'provision', header: 'Provision' },
        {
            key: 'description',
            header: 'Description',
            render: (row) => (
                <span className="line-clamp-2 max-w-xs text-xs" title={row.description}>
                    {row.description.length > 120 ? `${row.description.slice(0, 120)}…` : row.description}
                </span>
            ),
        },
        { key: 'jurisdiction', header: 'Jurisdiction', sortable: true },
        {
            key: 'severity',
            header: 'Severity',
            sortable: true,
            render: (row) => (
                <StatusBadge
                    variant={row.severity as 'critical' | 'high' | 'medium' | 'low'}
                    dot
                />
            ),
        },
        {
            key: 'actions',
            header: '',
            render: (row) => (
                <IconButton label={`View entry ${row.entry_no}`} size="sm">
                    <EyeIcon className="w-4 h-4" aria-hidden />
                </IconButton>
            ),
        },
    ];

    return (
        <AuthenticatedLayout>
            <Head title="Sanctions Knowledge Base" />

            <PageHeader
                breadcrumb={[{ label: 'Sanctions KB' }]}
                title="Sanctions Knowledge Base"
                subtitle={`${sanctions.total} sanction entries — full-text searchable`}
                actions={
                    <SecondaryButton>
                        <ArrowDownTrayIcon aria-hidden className="w-4 h-4" />
                        Export
                    </SecondaryButton>
                }
            />

            <FilterBar
                filters={filterConfigs}
                values={filterValues}
                onChange={handleFilterChange}
                onReset={handleReset}
                isActive={isActive}
            />

            {hasSearchResults && exposureSummary && (
                <Card hover={false} padding="sm" className="mb-4">
                    <div className="flex items-center gap-6 text-sm">
                        <span className="font-semibold">{exposureSummary.count} matches</span>
                        {exposureSummary.highestSeverity && (
                            <StatusBadge
                                variant={exposureSummary.highestSeverity as 'critical' | 'high' | 'medium' | 'low'}
                                label={`Highest: ${exposureSummary.highestSeverity}`}
                            />
                        )}
                    </div>
                </Card>
            )}

            <Card hover={false} padding="none">
                <DataTable
                    columns={columns}
                    data={sanctions.data}
                    sortKey={sortKey}
                    sortDir={sortDir}
                    onSort={handleSort}
                    emptyState={
                        <EmptyState
                            icon={<ShieldExclamationIcon className="w-8 h-8 text-gray-400" aria-hidden />}
                            title="No entries match your search"
                            description="Try different keywords or clear the filters."
                        />
                    }
                />
                <Pagination
                    links={sanctions.links}
                    from={sanctions.from}
                    to={sanctions.to}
                    total={sanctions.total}
                />
            </Card>
        </AuthenticatedLayout>
    );
}
