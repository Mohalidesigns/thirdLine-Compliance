<?php

declare(strict_types=1);

namespace Modules\Incident\Filament\Resources\IncidentNotificationResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\Incident\Filament\Resources\IncidentNotificationResource;

class ListIncidentNotifications extends ListRecords
{
    protected static string $resource = IncidentNotificationResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
