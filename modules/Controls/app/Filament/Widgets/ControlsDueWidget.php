<?php

declare(strict_types=1);

namespace Modules\Controls\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Modules\Controls\Models\Control;

class ControlsDueWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $dueSoon = Control::query()
            ->where('status', 'active')
            ->where('next_test_due', '<=', now()->addDays(7)->toDateString())
            ->count();

        $overdue = Control::query()
            ->where('status', 'active')
            ->where('next_test_due', '<', now()->toDateString())
            ->count();

        $active = Control::where('status', 'active')->count();

        return [
            Stat::make('Controls Due (7 days)', $dueSoon)
                ->description('Controls requiring testing soon')
                ->color($dueSoon > 0 ? 'warning' : 'success')
                ->icon('heroicon-o-clock'),
            Stat::make('Overdue Controls', $overdue)
                ->description('Past their test due date')
                ->color($overdue > 0 ? 'danger' : 'success')
                ->icon('heroicon-o-exclamation-triangle'),
            Stat::make('Active Controls', $active)
                ->description('Total controls in active status')
                ->color('primary')
                ->icon('heroicon-o-shield-check'),
        ];
    }
}
