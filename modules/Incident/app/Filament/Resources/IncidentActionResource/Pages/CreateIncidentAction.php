<?php

declare(strict_types=1);

namespace Modules\Incident\Filament\Resources\IncidentActionResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Incident\Filament\Resources\IncidentActionResource;

class CreateIncidentAction extends CreateRecord
{
    protected static string $resource = IncidentActionResource::class;
}
