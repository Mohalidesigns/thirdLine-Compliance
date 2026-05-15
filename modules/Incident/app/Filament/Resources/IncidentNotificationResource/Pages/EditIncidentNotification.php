<?php

declare(strict_types=1);

namespace Modules\Incident\Filament\Resources\IncidentNotificationResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Modules\Incident\Filament\Resources\IncidentNotificationResource;

class EditIncidentNotification extends EditRecord
{
    protected static string $resource = IncidentNotificationResource::class;
}
