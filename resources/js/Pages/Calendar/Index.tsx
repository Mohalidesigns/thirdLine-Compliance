import { useState, useEffect } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import PageHeader from '@/Components/PageHeader';
import Card from '@/Components/Card';
import DataTable, { Column } from '@/Components/DataTable';
import StatusBadge from '@/Components/StatusBadge';
import EmptyState from '@/Components/EmptyState';
import SecondaryButton from '@/Components/SecondaryButton';
import { Head } from '@inertiajs/react';
import { CalendarDaysIcon, ChevronLeftIcon, ChevronRightIcon } from '@heroicons/react/24/outline';

interface CalendarEvent {
    id: number;
    due_date: string;
    obligation: string;
    instrument: string;
    status: string;
    days_until: number;
}

interface Props {
    events?: CalendarEvent[];
    month?: number;
    year?: number;
    viewMode?: 'month' | 'list';
}

const mockEvents: CalendarEvent[] = [
    { id: 1, due_date: '2026-05-20', obligation: 'Monthly AML returns to NFIU', instrument: 'CBN/REG/001', status: 'overdue', days_until: -5 },
    { id: 2, due_date: '2026-05-28', obligation: 'Q2 capital adequacy report to CBN', instrument: 'CBN/BSD/2020', status: 'medium', days_until: 3 },
    { id: 3, due_date: '2026-06-01', obligation: 'NDPC data audit submission', instrument: 'NDPC/GUI/005', status: 'medium', days_until: 7 },
    { id: 4, due_date: '2026-06-10', obligation: 'Annual NAICOM solvency filing', instrument: 'NAICOM/CIR/033', status: 'info', days_until: 16 },
    { id: 5, due_date: '2026-06-15', obligation: 'SEC quarterly investment report', instrument: 'SEC/CIR/042', status: 'info', days_until: 21 },
    { id: 6, due_date: '2026-06-30', obligation: 'H1 pension contribution reconciliation', instrument: 'PENCOM/REG/011', status: 'low', days_until: 36 },
    { id: 7, due_date: '2026-07-15', obligation: 'FIRS transfer pricing documentation', instrument: 'FIRS/CIR/019', status: 'low', days_until: 51 },
    { id: 8, due_date: '2026-07-31', obligation: 'NDIC deposit insurance premium payment', instrument: 'NDIC/DIR/007', status: 'low', days_until: 67 },
];

function daysUntilColor(days: number): string {
    if (days < 0)  return 'text-red-600 font-semibold';
    if (days < 7)  return 'text-red-500 font-medium';
    if (days < 30) return 'text-amber-600 font-medium';
    return 'text-green-600';
}

function daysUntilLabel(days: number): string {
    if (days < 0)  return `${Math.abs(days)}d overdue`;
    if (days === 0) return 'Today';
    return `${days}d`;
}

export default function CalendarIndex({
    events = mockEvents,
    month: initialMonth,
    year: initialYear,
    viewMode: initialViewMode,
}: Props) {
    const now = new Date();

    const [currentMonth, setCurrentMonth] = useState(initialMonth ?? now.getMonth() + 1);
    const [currentYear, setCurrentYear] = useState(initialYear ?? now.getFullYear());

    const [viewMode, setViewMode] = useState<'month' | 'list'>(() => {
        if (typeof window !== 'undefined') {
            return (localStorage.getItem('calendar-view') as 'list' | 'month') ?? initialViewMode ?? 'list';
        }
        return initialViewMode ?? 'list';
    });

    useEffect(() => {
        localStorage.setItem('calendar-view', viewMode);
    }, [viewMode]);

    const monthLabel = new Date(currentYear, currentMonth - 1).toLocaleString('en-US', {
        month: 'long',
        year: 'numeric',
    });

    const prevMonth = () => {
        if (currentMonth === 1) { setCurrentMonth(12); setCurrentYear((y) => y - 1); }
        else setCurrentMonth((m) => m - 1);
    };

    const nextMonth = () => {
        if (currentMonth === 12) { setCurrentMonth(1); setCurrentYear((y) => y + 1); }
        else setCurrentMonth((m) => m + 1);
    };

    const goToday = () => {
        setCurrentMonth(now.getMonth() + 1);
        setCurrentYear(now.getFullYear());
    };

    const columns: Column<CalendarEvent>[] = [
        { key: 'due_date', header: 'Due Date', sortable: true },
        { key: 'obligation', header: 'Obligation' },
        { key: 'instrument', header: 'Instrument', sortable: true },
        {
            key: 'status',
            header: 'Status',
            sortable: true,
            render: (row) => (
                <StatusBadge
                    variant={row.status as 'overdue' | 'medium' | 'info' | 'low'}
                />
            ),
        },
        {
            key: 'days_until',
            header: 'Days',
            sortable: true,
            render: (row) => (
                <span className={daysUntilColor(row.days_until)}>
                    {daysUntilLabel(row.days_until)}
                </span>
            ),
        },
    ];

    return (
        <AuthenticatedLayout>
            <Head title="Compliance Calendar" />

            <PageHeader
                breadcrumb={[{ label: 'Calendar' }]}
                title="Compliance Calendar"
                subtitle={monthLabel}
                actions={
                    <div className="flex items-center gap-2">
                        <SecondaryButton onClick={prevMonth} aria-label="Previous month">
                            <ChevronLeftIcon aria-hidden className="w-4 h-4" />
                        </SecondaryButton>
                        <SecondaryButton onClick={goToday}>Today</SecondaryButton>
                        <SecondaryButton onClick={nextMonth} aria-label="Next month">
                            <ChevronRightIcon aria-hidden className="w-4 h-4" />
                        </SecondaryButton>

                        {/* View toggle */}
                        <div className="flex border border-gray-200 rounded-lg overflow-hidden ml-2" role="group" aria-label="View mode">
                            <button
                                type="button"
                                onClick={() => setViewMode('list')}
                                className={`px-3 py-1.5 text-xs font-semibold transition-colors ${
                                    viewMode === 'list'
                                        ? 'bg-primary text-white'
                                        : 'bg-white text-gray-600 hover:bg-gray-50'
                                }`}
                                aria-pressed={viewMode === 'list'}
                            >
                                List
                            </button>
                            <button
                                type="button"
                                disabled
                                title="Coming soon"
                                aria-label="Month view — coming soon"
                                className="px-3 py-1.5 text-xs font-semibold bg-white text-gray-300 cursor-not-allowed border-l border-gray-200"
                            >
                                Month
                            </button>
                        </div>
                    </div>
                }
            />

            <Card hover={false} padding="none">
                <DataTable
                    columns={columns}
                    data={events}
                    sortKey="due_date"
                    sortDir="asc"
                    emptyState={
                        <EmptyState
                            icon={<CalendarDaysIcon className="w-8 h-8 text-gray-400" aria-hidden />}
                            title="No due dates this period"
                            description="Obligations with due dates will appear here."
                        />
                    }
                />
            </Card>
        </AuthenticatedLayout>
    );
}
