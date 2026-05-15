<?php

declare(strict_types=1);

namespace Modules\Incident\Filament\Resources\IncidentResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Modules\Incident\Filament\Resources\IncidentResource;

class ListIncidents extends ListRecords
{
    protected static string $resource = IncidentResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
