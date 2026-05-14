<?php

declare(strict_types=1);

namespace Modules\Library\Filament\Resources\RegulatorResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Modules\Library\Filament\Resources\RegulatorResource;

class ViewRegulator extends ViewRecord
{
    protected static string $resource = RegulatorResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\EditAction::make()];
    }
}
