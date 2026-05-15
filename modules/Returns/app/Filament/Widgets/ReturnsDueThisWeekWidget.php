<?php

declare(strict_types=1);

namespace Modules\Returns\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Modules\Returns\Models\ReturnRun;

class ReturnsDueThisWeekWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $weekEnd = now()->endOfWeek();
        $weekStart = now()->startOfWeek();

        $dueThisWeek = ReturnRun::whereBetween('due_at', [$weekStart, $weekEnd])
            ->whereIn('status', ['scheduled', 'in_progress', 'late'])
            ->count();

        $late = ReturnRun::where('status', 'late')->count();

        $pendingAck = ReturnRun::where('status', 'submitted_pending_ack')->count();

        return [
            Stat::make('Due This Week', $dueThisWeek)
                ->description('Returns with due dates this week')
                ->color($dueThisWeek > 0 ? 'warning' : 'success')
                ->icon('heroicon-o-calendar'),
            Stat::make('Late Returns', $late)
                ->description('Overdue and not yet submitted')
                ->color($late > 0 ? 'danger' : 'success')
                ->icon('heroicon-o-exclamation-circle')
                ->url(route('filament.admin.resources.return-runs.index')),
            Stat::make('Pending Acknowledgement', $pendingAck)
                ->description('Submitted, awaiting regulator ACK')
                ->color($pendingAck > 0 ? 'info' : 'gray')
                ->icon('heroicon-o-clock'),
        ];
    }
}
