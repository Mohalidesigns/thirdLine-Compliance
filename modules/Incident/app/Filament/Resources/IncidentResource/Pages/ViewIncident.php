<?php

declare(strict_types=1);

namespace Modules\Incident\Filament\Resources\IncidentResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Modules\Incident\Filament\Resources\IncidentResource;

class ViewIncident extends ViewRecord
{
    protected static string $resource = IncidentResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\EditAction::make()];
    }
}
