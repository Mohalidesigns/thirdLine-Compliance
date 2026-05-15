<?php

declare(strict_types=1);

namespace Modules\Incident\Filament\Resources\IncidentActionResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Modules\Incident\Filament\Resources\IncidentActionResource;

class ListIncidentActions extends ListRecords
{
    protected static string $resource = IncidentActionResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
