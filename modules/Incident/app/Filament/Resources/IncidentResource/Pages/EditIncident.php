<?php

declare(strict_types=1);

namespace Modules\Incident\Filament\Resources\IncidentResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Modules\Incident\Filament\Resources\IncidentResource;

class EditIncident extends EditRecord
{
    protected static string $resource = IncidentResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
