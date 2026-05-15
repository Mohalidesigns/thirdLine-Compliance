<?php

declare(strict_types=1);

namespace Modules\Incident\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Modules\Incident\Models\Incident;
use Modules\Incident\Models\IncidentNotification;

class OverdueIncidentNotificationsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 0;

    protected function getStats(): array
    {
        $overdueNotifications = IncidentNotification::where('status', 'overdue')->count();
        $pendingNotifications = IncidentNotification::where('status', 'pending')->count();
        $openIncidents = Incident::whereIn('status', ['detected', 'triaged', 'investigating', 'remediation'])->count();

        return [
            Stat::make('Overdue Notifications', $overdueNotifications)
                ->description('Regulator notifications past deadline')
                ->color($overdueNotifications > 0 ? 'danger' : 'success')
                ->icon('heroicon-o-bell-alert')
                ->url(route('filament.admin.resources.incident-notifications.index')),
            Stat::make('Pending Notifications', $pendingNotifications)
                ->description('Notifications awaiting submission')
                ->color($pendingNotifications > 0 ? 'warning' : 'gray')
                ->icon('heroicon-o-clock'),
            Stat::make('Open Incidents', $openIncidents)
                ->description('Incidents not yet resolved')
                ->color($openIncidents > 0 ? 'warning' : 'success')
                ->icon('heroicon-o-exclamation-triangle')
                ->url(route('filament.admin.resources.incidents.index')),
        ];
    }
}
