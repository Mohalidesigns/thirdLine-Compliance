<?php

declare(strict_types=1);

namespace Modules\Incident\Filament\Resources\IncidentResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Modules\Incident\Filament\Resources\IncidentResource;
use Modules\Incident\Services\IncidentService;

class CreateIncident extends CreateRecord
{
    protected static string $resource = IncidentResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(IncidentService::class)->create($data, auth()->id());
    }
}
