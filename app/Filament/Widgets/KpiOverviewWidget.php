<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Modules\Library\Models\AreaOfFocus;
use Modules\Library\Models\Instrument;
use Modules\Library\Models\Obligation;
use Modules\Library\Models\Regulator;
use Modules\Policy\Models\Policy;

class KpiOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $totalObligations = Obligation::count();
        $totalRegulators = Regulator::count();
        $highRisk = Instrument::whereHas('riskRating', fn ($q) => $q->where('name', 'High'))->count();
        $areasOfFocus = AreaOfFocus::count();
        $activePolicies = 0;

        if (class_exists(Policy::class)) {
            $activePolicies = Policy::whereIn('state', ['published', 'in_force'])->count();
        }

        return [
            Stat::make('Total Obligations', $totalObligations)
                ->description('Active compliance obligations')
                ->color('primary'),
            Stat::make('Regulators', $totalRegulators)
                ->description('Regulatory bodies tracked')
                ->color('warning'),
            Stat::make('High Risk Instruments', $highRisk)
                ->description('Instruments rated high risk')
                ->color('danger'),
            Stat::make('Areas of Focus', $areasOfFocus)
                ->description('Distinct compliance areas')
                ->color('info'),
            Stat::make('Active Policies', $activePolicies)
                ->description('Published or in-force policies')
                ->color('success'),
        ];
    }
}
