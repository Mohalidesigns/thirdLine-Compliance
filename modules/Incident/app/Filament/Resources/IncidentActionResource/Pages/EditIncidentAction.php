<?php

declare(strict_types=1);

namespace Modules\Incident\Filament\Resources\IncidentActionResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Modules\Incident\Filament\Resources\IncidentActionResource;

class EditIncidentAction extends EditRecord
{
    protected static string $resource = IncidentActionResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
